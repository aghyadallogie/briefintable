<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl">{{ $editing ? 'Edit draft' : 'Create a Briefing' }}</h1>
    {{-- Same entrance as the briefing cards (fade and rise; a plain fade for reduced motion). No hover lift: it is a form. --}}
    <div class="card rise-in max-w-3xl mt-6 p-6">
        @if (session()->has('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
        @endif

        @if ($blockedReason)
        <div class="alert alert-danger mb-4" role="alert">
            {{ $blockedReason }} Your text is still below if you want to copy it.
        </div>
        @endif

        <form wire:submit="save" class="space-y-6">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-ink">Title</label>
                <input type="text" id="title" wire:model="title" class="field">
                @error('title') <span class="text-xs text-danger-ink">{{ $message }}</span> @enderror
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-sm font-medium text-ink">Content</label>
                <textarea id="content" wire:model="content" rows="8" class="field"></textarea>
                @error('content') <span class="text-xs text-danger-ink">{{ $message }}</span> @enderror
            </div>

            <!-- Published -->
            @if ($canPublish)
            <div class="flex items-center gap-2">
                <input type="checkbox" id="published" wire:model="published"
                    class="rounded border-line-strong accent-primary focus:ring-primary">
                <label for="published" class="text-sm font-medium text-ink">Publish immediately</label>
                @error('published') <span class="text-xs text-danger-ink">{{ $message }}</span> @enderror
            </div>
            @else
            <p class="text-sm text-subtle">Your briefing is saved as a draft and an admin will publish it.</p>
            @endif

            <!-- Submit -->
            <div class="flex justify-end items-center gap-3">
                <a href="{{ $backUrl }}" class="btn btn-secondary">Back</a>
                @unless ($blockedReason)
                <button type="submit" class="btn btn-primary">
                    {{ $editing ? 'Save changes' : 'Save Briefing' }}
                </button>
                @endunless
            </div>
        </form>
    </div>
</div>
