@props(['title' => 'Briefing Editor', 'description' => null, 'footer' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @if ($description)
    <meta name="description" content="{{ $description }}">
    @endif
    @vite(['resources/css/site.css', 'resources/js/site.js'])
    @livewireStyles
</head>

<body class="min-h-screen">
    <x-navbar />
    <main id="content">
        {{ $slot }}
    </main>
    {{ $footer }}
    @livewireScripts
</body>

</html>
