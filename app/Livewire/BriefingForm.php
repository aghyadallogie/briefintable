<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
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
        $this->validate();

        $entry = Entry::make();
        $entry->collection('briefings')
            ->blueprint('briefing')
            ->slug($this->uniqueSlug())
            ->published($this->published)
            ->data([
                'title' => $this->title,
                'content' => $this->content,
                'author' => Auth::id(),
            ]);

        $entry->save();

        session()->flash('success', 'Briefing successfully created!');

        $this->reset(['title', 'content', 'published']);
    }

    protected function uniqueSlug(): string
    {
        $base = (string) Str::slug($this->title) ?: 'briefing';

        $taken_slugs = Entry::query()
            ->where('collection', 'briefings')
            ->where('slug', 'like', $base . '%')
            ->get()
            ->map->slug()
            ->all();

        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $taken_slugs, true)) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.briefing-form');
    }
}
