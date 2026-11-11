<x-layouts.app title="My Drafts">
    <div class="max-w-4xl mx-auto p-6">
        <div class="mb-8">
            <h1 class="text-2xl">My Drafts</h1>
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
            <article class="card card-lift rise-in relative p-6" style="--i: {{ $loop->index }}">
                {{-- Icon actions, top right. The title is padded so it never runs underneath them. --}}
                <div class="card-actions absolute top-4 right-4 flex items-center gap-1">
                    <a href="{{ route('drafts.edit', $draft->id()) }}" class="icon-btn" title="Edit draft" aria-label="Edit draft">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                    </a>
                    <form method="POST" action="{{ route('drafts.destroy', $draft->id()) }}"
                        onsubmit="if (!confirm('Delete this draft permanently? This cannot be undone.')) return false; this.querySelector('button').disabled = true;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-btn icon-btn-danger cursor-pointer" title="Delete draft" aria-label="Delete draft">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </form>
                </div>

                <h2 class="text-xl mb-2 pr-20">
                    <a href="{{ route('drafts.show', $draft->id()) }}" class="text-primary hover:text-primary-hover">{{ $draft->get('title') }}</a>
                </h2>
                <div class="text-sm text-subtle mb-4">
                    <span class="badge badge-warning mr-2">Draft</span>
                    Saved {{ $draft->lastModified()->format('M j, Y') }}
                </div>
                <div class="text-muted">
                    {{-- Plain-text preview of the rendered markdown. Blade escapes the result. --}}
                    {{ \App\Support\Briefings::excerpt($draft, 200) }}
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