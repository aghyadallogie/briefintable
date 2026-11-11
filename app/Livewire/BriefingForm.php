<?php

namespace App\Livewire;

use App\Support\Briefings;
use App\Support\BriefingSlugs;
use App\Support\DraftState;
use App\Support\OwnDrafts;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Statamic\Facades\Entry;

/**
 * The form for writing a briefing and for editing one of your own drafts (a Livewire full-page component).
 *
 * Creating: validates the input, takes a unique slug from BriefingSlugs::unique(),makes the signed-in user the
 * author and saves the entry.
 * Editing: only the author, only while it is still a draft, under a row lock (see OwnDrafts).
 * Only users who may publish can publish from here; everyone else always saves a draft.
 * Cache invalidation is handled by the EntrySaved listener in AppServiceProvider.
 */
#[Layout('components.layouts.app')]
class BriefingForm extends Component
{
    private const savesPerMinute = 10;

    public string $title = '';

    public string $content = '';

    public bool $published = false;

    // Id of the draft being edited, or null when creating. Locked so the browser can't swap it.
    #[Locked]
    public ?string $draftId = null;

    // Set when an edit can no longer be saved. The form stays visible so the text can be copied.
    #[Locked]
    public ?string $blockedReason = null;

    // Where the Back button goes: the page the user came from, captured once when the form opens.
    #[Locked]
    public string $backUrl = '';

    public function mount(?string $draft = null): void
    {
        $this->backUrl = $this->previousUrl(route($draft === null ? 'briefings.index' : 'drafts.index', absolute: false));

        if ($draft === null) {
            return;
        }

        $entry = Entry::find($draft);
        $state = OwnDrafts::stateFor(Auth::user(), $entry);

        // 404 for anything that isn't yours or published, so ids don't reveal what exists.
        abort_unless($state->isYours(), 404);

        if ($state === DraftState::Published) {
            session()->flash('error', 'That briefing has been published, so it can no longer be edited here.');
            $this->redirect(route('drafts.index'));

            return;
        }

        // The entry is yours and still a draft, so the form can be filled with its text.
        $this->draftId = $entry->id();
        $this->title = (string) $entry->get('title');
        $this->content = (string) $entry->get('content');
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:20000',
            'published' => 'boolean',
        ];
    }

    /**
     * Saves the form: creates a new briefing, or updates the draft being edited.
     *
     * - The permission is re-checked here because Livewire actions are their own requests,
     *   so the route middleware doesn't cover them (403).
     * - A blocked form (its draft went live or disappeared) stays blocked (409).
     * - Each user may save savesPerMinute times a minute. It is counted before validating, so
     *   invalid submissions count too, and the limit is per user, so one user can't use up another's.
     * - The "published" flag comes from the browser, so it only counts for users allowed to
     *   publish; everyone else always saves a draft.
     */
    public function save(): void
    {
        abort_unless(Gate::allows(Briefings::CAN_CREATE), 403);

        abort_if($this->blockedReason !== null, 409);

        $limiterKey = 'briefing-form:'.Auth::id();

        if (! RateLimiter::attempt($limiterKey, self::savesPerMinute, fn () => true, 60)) {
            $this->addError('throttle', 'You are saving too fast. Please try again in '.RateLimiter::availableIn($limiterKey).' seconds.');

            return;
        }

        $this->validate();

        $canPublish = $this->canPublish();
        $publish = $this->published && $canPublish;

        if ($this->draftId) {
            $this->updateDraft($publish);

            return;
        }

        $this->createBriefing($publish, $canPublish);
    }

    protected function createBriefing(bool $publish, bool $canPublish): void
    {
        $entry = Entry::make();
        $entry->collection(Briefings::COLLECTION)
            ->blueprint('briefing')
            ->slug(BriefingSlugs::unique($this->title))
            ->published($publish)
            ->data([
                'title' => $this->title,
                'content' => $this->content,
                'author' => Auth::id(),
            ]);

        $entry->save();

        session()->flash('success', $canPublish
            ? 'Briefing successfully created!'
            : 'Submitted for review. An admin will publish it.');

        $this->reset(['title', 'content', 'published']);
    }

    /**
     * The slug and author stay as they were; only the text and state change.
     *
     * The ownership check happens inside the row lock, right before the write, so a draft an admin
     * publishes at the same moment is never overwritten. If it went live or was deleted, nothing is
     * saved and the form stays on screen with the author's text so they can copy it.
     */
    protected function updateDraft(bool $publish): void
    {
        $state = OwnDrafts::whileLocked($this->draftId, function ($entry) use ($publish) {
            $state = OwnDrafts::stateFor(Auth::user(), $entry);

            if ($state === DraftState::Draft) {
                $entry->published($publish)
                    ->data($entry->data()->merge([
                        'title' => $this->title,
                        'content' => $this->content,
                    ])->all())
                    ->save();
            }

            return $state;
        });

        abort_if($state === DraftState::Foreign, 404);

        if ($state === DraftState::Published || $state === DraftState::Missing) {
            $this->blockedReason = $state === DraftState::Published
                ? 'This briefing was published while you were editing it, so your changes were not saved.'
                : 'This draft no longer exists, so your changes were not saved.';

            return;
        }

        session()->flash('success', $publish ? 'Briefing published.' : 'Draft updated.');

        $this->redirect(route('drafts.index'));
    }

    /** Resolves a safe, internal URL to send the user back to, preventing open redirects and reload loops. */
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
        return Gate::allows(Briefings::CAN_PUBLISH);
    }

    public function render(): View
    {
        return view('livewire.briefing-form', [
            'canPublish' => $this->canPublish(),
            'editing' => $this->draftId !== null,
        ]);
    }
}
