<x-layouts.app>
    <div class="max-w-4xl mx-auto p-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold">My Drafts</h1>
        </div>

        @if (session()->has('success'))
        <div class="mb-6 p-4 text-green-700 bg-green-100 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        <div class="space-y-6">
            @forelse ($drafts as $draft)
            <article class="p-6 bg-white rounded-lg shadow border border-gray-100">
                <h2 class="text-2xl font-semibold mb-2">
                    <a href="{{ route('drafts.edit', $draft->id()) }}" class="text-indigo-600 hover:text-indigo-800">{{ $draft->get('title') }}</a>
                </h2>
                <div class="text-sm text-gray-500 mb-4">
                    <span class="inline-block px-2 py-0.5 mr-2 rounded bg-yellow-100 text-yellow-800">Draft</span>
                    Saved {{ $draft->lastModified()->format('M j, Y') }}
                </div>
                <div class="text-gray-700">
                    {{-- Rendered markdown -> plain text. Blade escapes the result, so nothing here can inject markup. --}}
                    {{ \Illuminate\Support\Str::limit(html_entity_decode(strip_tags((string) $draft->augmentedValue('content'))), 200) }}
                </div>
                <div class="mt-4 flex items-center gap-4 text-sm">
                    <a href="{{ route('drafts.edit', $draft->id()) }}" class="text-indigo-600 hover:text-indigo-800">Edit</a>
                    <form method="POST" action="{{ route('drafts.destroy', $draft->id()) }}"
                        onsubmit="return confirm('Delete this draft permanently? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                    </form>
                </div>
            </article>
            @empty
            <p class="text-gray-500">You have no drafts.</p>
            @endforelse

            @if ($drafts->lastPage() > 1)
            <nav class="flex items-center justify-between pt-2 text-sm" aria-label="Pagination">
                @if ($drafts->previousPageUrl())
                <a href="{{ $drafts->previousPageUrl() }}" class="text-indigo-600 hover:text-indigo-800">&larr; Newer</a>
                @else<span></span>@endif
                <span class="text-gray-500">Page {{ $drafts->currentPage() }} of {{ $drafts->lastPage() }}</span>
                @if ($drafts->nextPageUrl())
                <a href="{{ $drafts->nextPageUrl() }}" class="text-indigo-600 hover:text-indigo-800">Older &rarr;</a>
                @else<span></span>@endif
            </nav>
            @endif
        </div>
    </div>
</x-layouts.app>
