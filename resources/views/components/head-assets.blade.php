{{-- Shared <head> extras for both layouts (the Antlers pages and the Blade/Livewire pages). Keep them identical
     so the browser treats every page the same and nothing re-flows between navigations. --}}

{{-- Start downloading the Latin font right away, ahead of the stylesheet, so text is already in Roboto Slab at
     first paint. `crossorigin` is required for font preloads even though the file is on the same origin. --}}
<link rel="preload" href="{{ Vite::asset('node_modules/@fontsource-variable/roboto-slab/files/roboto-slab-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
{{-- Browser chrome colour on phones. Keep equal to --color-primary in site.css (ThemeTokensTest checks it). --}}
<meta name="theme-color" content="#eb0b0b">
