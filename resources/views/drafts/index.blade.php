<x-layouts.app title="My Drafts">
    <div class="max-w-4xl mx-auto p-6">
        <div class="mb-8">
            <h1 class="text-3xl">My Drafts</h1>
        </div>

        @if (session()->has('success'))
        <div class="alert alert-success mb-6">
            {{ session('success') }}
        </div>
        @endif

        @if (session()->has('error'))
        <div class="alert alert-danger mb-6" role="alert">
            {{ session('error') }}
        </div>
        @endif

        <div class="space-y-6">
            @forelse ($drafts as $draft)
            <article class="card p-6">
                <h2 class="text-2xl mb-2">
                    <a href="{{ route('drafts.edit', $draft->id()) }}" class="text-primary hover:text-primary-hover">{{ $draft->get('title') }}</a>
                </h2>
                <div class="text-sm text-subtle mb-4">
                    <span class="badge badge-warning mr-2">Draft</span>
                    Saved {{ $draft->lastModified()->format('M j, Y') }}
                </div>
                <div class="text-muted">
                    {{-- Rendered markdown -> plain text. Blade escapes the result, so nothing here can inject markup. --}}
                    {{ \Illuminate\Support\Str::limit(html_entity_decode(strip_tags((string) $draft->augmentedValue('content'))), 200) }}
                </div>
                <div class="mt-4 flex items-center gap-4 text-sm">
                    <a href="{{ route('drafts.edit', $draft->id()) }}" class="link">Edit</a>
                    <form method="POST" action="{{ route('drafts.destroy', $draft->id()) }}"
                        onsubmit="if (!confirm('Delete this draft permanently? This cannot be undone.')) return false; this.querySelector('button').disabled = true;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="font-medium text-danger-ink hover:underline">Delete</button>
                    </form>
                </div>
            </article>
            @empty
            <p class="text-subtle">You have no drafts.</p>
            @endforelse

            @if ($drafts->lastPage() > 1)
            <nav class="flex items-center justify-between pt-2 text-sm" aria-label="Pagination">
                @if ($drafts->previousPageUrl())
                <a href="{{ $drafts->previousPageUrl() }}" class="link">&larr; Newer</a>
                @else<span></span>@endif
                <span class="text-subtle">Page {{ $drafts->currentPage() }} of {{ $drafts->lastPage() }}</span>
                @if ($drafts->nextPageUrl())
                <a href="{{ $drafts->nextPageUrl() }}" class="link">Older &rarr;</a>
                @else<span></span>@endif
            </nav>
            @endif
        </div>
    </div>
</x-layouts.app>
