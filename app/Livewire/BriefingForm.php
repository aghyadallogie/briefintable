<?php

namespace App\Livewire;

use App\Support\OwnDrafts;
use App\Support\ReservedSlugs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Statamic\Facades\Entry;
use Statamic\Support\Str;

/**
 * Handles the creation of new briefings via a Livewire full-page component.
 *
 * Validates input, generates a unique slug, assigns the authenticated user
 * as the author, and persists the entry to the database. Cache invalidation
 * is handled automatically by the EntrySaved event in AppServiceProvider.
 */
#[Layout('components.layouts.app')]
class BriefingForm extends Component
{
    public string $title = '';

    public string $content = '';

    public bool $published = false;

    /** Id of the draft being edited, or null when creating. Locked so the browser can't swap it. */
    #[Locked]
    public ?string $draftId = null;

    /** Set when an edit can no longer be saved. The form stays visible so the text can be copied. */
    #[Locked]
    public ?string $blockedReason = null;

    /**
     * Where the Back button goes: the page the user came from, captured once when the form opens.
     * Livewire's later requests all come from this page, so the referer is no use after that.
     */
    #[Locked]
    public string $backUrl = '';

    public function mount(?string $draft = null): void
    {
        $this->backUrl = $this->previousUrl($draft === null ? '/briefings' : route('drafts.index', absolute: false));

        if ($draft === null) {
            return;
        }

        $entry = Entry::find($draft);
        $state = OwnDrafts::stateFor(Auth::user(), $entry);

        // 404 for anything that isn't yours, so ids don't reveal what exists.
        abort_unless(in_array($state, [OwnDrafts::DRAFT, OwnDrafts::PUBLISHED], true), 404);

        if ($state === OwnDrafts::PUBLISHED) {
            session()->flash('error', 'That briefing has been published, so it can no longer be edited here.');
            $this->redirect(route('drafts.index'));

            return;
        }

        $this->draftId = $entry->id();
        $this->title = (string) $entry->get('title');
        $this->content = (string) $entry->get('content');
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'published' => 'boolean',
        ];
    }

    public function save()
    {
        // Livewire actions are their own requests, so the route middleware doesn't cover them.
        abort_unless(Gate::allows('create briefings entries'), 403);

        // A blocked form (its draft went live or disappeared) stays blocked.
        abort_if($this->blockedReason !== null, 409);

        $this->validate();

        // $published comes from the browser, so it only counts for users allowed to publish.
        // Everyone else (contributors) always saves a draft for an admin to publish in the CP.
        $publish = $this->published && $this->canPublish();

        if ($this->draftId) {
            return $this->updateDraft($publish);
        }

        $entry = Entry::make();
        $entry->collection('briefings')
            ->blueprint('briefing')
            ->slug($this->uniqueSlug())
            ->published($publish)
            ->data([
                'title' => $this->title,
                'content' => $this->content,
                'author' => Auth::id(),
            ]);

        $entry->save();

        session()->flash('success', $this->canPublish()
            ? 'Briefing successfully created!'
            : 'Submitted for review. An admin will publish it.');

        $this->reset(['title', 'content', 'published']);
    }

    /**
     * The slug and author stay as they were; only the text (and, for publishers, the status) change.
     *
     * The ownership check happens inside the row lock, right before the write, so a draft an admin
     * publishes at the same moment is never overwritten. If it went live or was deleted, nothing is
     * saved and the form stays on screen with the author's text so they can copy it.
     */
    protected function updateDraft(bool $publish)
    {
        $state = OwnDrafts::whileLocked($this->draftId, function ($entry) use ($publish) {
            $state = OwnDrafts::stateFor(Auth::user(), $entry);

            if ($state === OwnDrafts::DRAFT) {
                $entry->published($publish)
                    ->data($entry->data()->merge([
                        'title' => $this->title,
                        'content' => $this->content,
                    ])->all())
                    ->save();
            }

            return $state;
        });

        abort_if($state === OwnDrafts::FOREIGN, 404);

        if ($state === OwnDrafts::PUBLISHED || $state === OwnDrafts::MISSING) {
            $this->blockedReason = $state === OwnDrafts::PUBLISHED
                ? 'This briefing was published while you were editing it, so your changes were not saved.'
                : 'This draft no longer exists, so your changes were not saved.';

            return;
        }

        session()->flash('success', $publish ? 'Briefing published.' : 'Draft updated.');

        return $this->redirect(route('drafts.index'));
    }

    /**
     * The referring page, if it is one of ours and worth going back to; otherwise the fallback.
     * Off-site referers (an open redirect), this very page (a reload), and Statamic's login
     * screens (which bounce a signed-in user away again) all fall back.
     */
    protected function previousUrl(string $fallback): string
    {
        $previous = url()->previous($fallback);
        $path = '/'.ltrim((string) parse_url($previous, PHP_URL_PATH), '/');

        $usable = str_starts_with($previous, request()->getSchemeAndHttpHost().'/')
            && $previous !== request()->fullUrl()
            && ! str_starts_with($path, '/cp/auth');

        return $usable ? $previous : url($fallback);
    }

    protected function canPublish(): bool
    {
        return Gate::allows('publish briefings entries');
    }

    protected function uniqueSlug(): string
    {
        $base = (string) Str::slug($this->title) ?: 'briefing';

        // Reserved slugs (e.g. "create") count as taken, so a briefing titled "Create" becomes "create-2".
        $taken_slugs = [
            ...ReservedSlugs::briefings(),
            ...Entry::query()
                ->where('collection', 'briefings')
                ->where('slug', 'like', $base.'%')
                ->get()
                ->map->slug()
                ->all(),
        ];

        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $taken_slugs, true)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.briefing-form', [
            'canPublish' => $this->canPublish(),
            'editing' => $this->draftId !== null,
            'blockedReason' => $this->blockedReason,
        ]);
    }
}
