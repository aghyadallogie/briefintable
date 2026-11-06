<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Statamic\Facades\Entry;
use Statamic\Support\Str;

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

        /** @var \Statamic\Entries\Entry $entry */
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
        $slug = $base;
        $suffix = 2;

        /** @var \Statamic\Query\Builder $query */
        $query = Entry::query();
        while ($query->where('collection', 'briefings')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.briefing-form');
    }
}
