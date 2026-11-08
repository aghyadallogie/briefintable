<header class="sticky top-0 z-50 border-b border-line bg-surface shadow-card">
    <a href="#content" class="skip-link">Skip to content</a>

    {{-- Brand and links sit together on the left, and only the button and menus are pushed right (ml-auto).
         That keeps the links in the same place on every page, whatever the right-hand side contains. --}}
    <div class="mx-auto flex max-w-5xl items-center gap-4 px-6 py-3 md:gap-8">
        <!-- Brand: always home -->
        <a href="{{ route('home') }}" class="shrink-0 font-heading text-xl font-bold text-primary">Briefintable</a>

        <!-- Desktop links -->
        <nav aria-label="Main" class="hidden items-center gap-6 md:flex">
            @foreach ($links as $link)
            <a href="{{ $link['url'] }}"
                @if ($link['current']) aria-current="page" @endif
                @class(['nav-link', 'nav-link-active' => $link['current']])>{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-3">
            @if ($cta)
            <a href="{{ $cta['url'] }}" class="btn btn-primary btn-sm hidden md:inline-flex">{{ $cta['label'] }}</a>
            @endif

            @if ($account)
            <!-- Account menu -->
            <details data-menu class="relative">
                <summary class="menu-toggle">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-soft text-sm font-semibold text-primary">{{ $account['initial'] }}</span>
                    <span class="sr-only">Account menu</span>
                </summary>
                <div class="menu-panel absolute right-0 z-10 mt-2 w-64 p-2">
                    <p class="px-3 pt-2 text-xs text-subtle">Signed in as</p>
                    <p class="px-3 pb-2 text-sm font-medium break-all text-ink">{{ $account['email'] }}</p>
                    <hr class="my-1 border-line">
                    <a href="{{ route('drafts.index') }}" class="menu-item">My drafts</a>
                    <a href="{{ $account['logout'] }}" class="menu-item">Log out</a>
                </div>
            </details>
            @endif

            <!-- Phone menu -->
            <details data-menu class="group md:hidden">
                <summary class="menu-toggle p-2">
                    <svg class="h-6 w-6 group-open:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    <svg class="hidden h-6 w-6 group-open:block" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    <span class="sr-only">Menu</span>
                </summary>
                <div class="absolute inset-x-0 top-full border-b border-line bg-surface px-6 py-4 shadow-lift">
                    <nav aria-label="Mobile" class="flex flex-col">
                        @foreach ($links as $link)
                        <a href="{{ $link['url'] }}"
                            @if ($link['current']) aria-current="page" @endif
                            @class(['menu-item py-3', 'font-semibold text-primary' => $link['current']])>{{ $link['label'] }}</a>
                        @endforeach
                    </nav>
                    @if ($cta)
                    <a href="{{ $cta['url'] }}" class="btn btn-primary mt-3 w-full">{{ $cta['label'] }}</a>
                    @endif
                </div>
            </details>
        </div>
    </div>
</header>
