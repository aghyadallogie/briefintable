<nav class="sticky top-0 z-50 border-b border-line bg-surface px-6 py-4 shadow-card">
    <div class="mx-auto flex max-w-5xl items-center justify-between">
        <!-- Logo / Home -->
        @auth
        <a href="{{ route('drafts.index') }}" class="font-heading text-xl font-bold text-primary">{{ auth()->user()->email() }}</a>
        @else
        <a href="{{ route('home') }}" class="font-heading text-xl font-bold text-primary">Briefintable</a>
        @endauth

        <!-- Links -->
        <div class="flex items-center gap-5">
            @unless (request()->is('briefings'))
            <a href="/briefings" class="text-muted hover:text-primary">All Briefings</a>
            @endunless
            @auth
            @unless (request()->is('briefings/create'))
            <a href="/briefings/create" class="btn btn-primary btn-sm">Create New</a>
            @endunless
            {{-- Statamic's own logout route; sends people back to the public list afterwards. --}}
            <a href="{{ cp_route('logout', ['redirect' => '/briefings']) }}" class="text-muted hover:text-primary">Logout</a>
            @else
            @unless (request()->is('cp'))
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Login</a>
            @endunless
            @endauth
        </div>
    </div>
</nav>
