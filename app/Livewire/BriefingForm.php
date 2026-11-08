<?php

namespace App\Livewire;

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

        $entry = $this->ownDraft($draft);

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

        // Checked again on every save: the draft may have been published or deleted since the page loaded.
        $draft = $this->draftId ? $this->ownDraft($this->draftId) : null;

        $this->validate();

        // $published comes from the browser, so it only counts for users allowed to publish.
        // Everyone else (contributors) always saves a draft for an admin to publish in the CP.
        $publish = $this->published && $this->canPublish();

        if ($draft) {
            return $this->updateDraft($draft, $publish);
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

    /** The slug and author stay as they were; only the text (and, for publishers, the status) change. */
    protected function updateDraft($draft, bool $publish)
    {
        $draft->published($publish)
            ->data($draft->data()->merge([
                'title' => $this->title,
                'content' => $this->content,
            ])->all())
            ->save();

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

    /** 404 (not 403) for anything that isn't the current user's own unpublished briefing. */
    protected function ownDraft(string $id)
    {
        $entry = Entry::find($id);

        abort_unless($entry && Gate::allows('manage-own-draft', $entry), 404);

        return $entry;
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
        ]);
    }
}
