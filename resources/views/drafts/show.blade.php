<x-layouts.app :title="$draft->get('title')">
    <div class="max-w-3xl mx-auto p-6 mt-10">
        <a href="{{ route('drafts.index') }}" class="link mb-4 inline-block">&larr; Back to Drafts</a>

        <h1 class="text-4xl mb-4">{{ $draft->get('title') }}</h1>

        <div class="text-sm text-subtle mb-8 border-b border-line pb-4">
            <span class="badge badge-warning mr-2">Draft</span>
            Saved {{ $draft->lastModified()->format('F j, Y') }}
        </div>

        <div class="prose prose-lg max-w-none">
            {{-- Rendered markdown. Raw HTML and javascript: links are already neutralised by the parser settings in config/statamic/markdown.php. --}}
            {!! (string) $draft->augmentedValue('content') !!}
        </div>
    </div>
</x-layouts.app>
