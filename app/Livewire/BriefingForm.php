<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
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

        $this->validate();

        // $published comes from the browser, so it only counts for users allowed to publish.
        // Everyone else (contributors) always saves a draft for an admin to publish in the CP.
        $publish = $this->published && $this->canPublish();

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

    protected function canPublish(): bool
    {
        return Gate::allows('publish briefings entries');
    }

    protected function uniqueSlug(): string
    {
        $base = (string) Str::slug($this->title) ?: 'briefing';

        $taken_slugs = Entry::query()
            ->where('collection', 'briefings')
            ->where('slug', 'like', $base.'%')
            ->get()
            ->map->slug()
            ->all();

        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $taken_slugs, true)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.briefing-form', ['canPublish' => $this->canPublish()]);
    }
}
