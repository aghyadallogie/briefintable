<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold">{{ $editing ? 'Edit draft' : 'Create a Briefing' }}</h1>
    <div class="max-w-3xl mt-6 p-6 bg-white rounded-lg shadow-md">
        @if (session()->has('success'))
        <div class="mb-4 p-4 text-green-700 bg-green-100 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        @if ($blockedReason)
        <div class="mb-4 p-4 text-red-700 bg-red-100 rounded-lg" role="alert">
            {{ $blockedReason }} Your text is still below if you want to copy it.
        </div>
        @endif

        <form wire:submit="save" class="space-y-6">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" id="title" wire:model="title"
                    class="mt-1 p-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                <textarea id="content" wire:model="content" rows="8"
                    class="mt-1 p-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                @error('content') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Published -->
            @if ($canPublish)
            <div class="flex items-center gap-2">
                <input type="checkbox" id="published" wire:model="published"
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="published" class="text-sm font-medium text-gray-700">Publish immediately</label>
                @error('published') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            @else
            <p class="text-sm text-gray-500">Your briefing is saved as a draft and an admin will publish it.</p>
            @endif

            <!-- Submit -->
            <div class="flex justify-end items-center gap-3">
                <a href="{{ $backUrl }}"
                    class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Back
                </a>
                @unless ($blockedReason)
                <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ $editing ? 'Save changes' : 'Save Briefing' }}
                </button>
                @endunless
            </div>
        </form>
    </div>
</div>