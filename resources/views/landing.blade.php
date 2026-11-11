<x-layouts.app
    title="Briefintable | Clear, reviewed briefings"
    description="Briefintable is where contributors write and editors approve. Every briefing is reviewed before it goes live.">

    <main>
        {{-- Hero --}}
        <section class="px-6 pt-10 pb-6">
            <div class="card mx-auto max-w-5xl p-8 md:p-12">
                <div class="grid items-center gap-10 md:grid-cols-2">
                    <div>
                        <p class="rise-in text-xs font-semibold tracking-widest text-primary uppercase" style="--i: 0">Briefintable</p>
                        <h1 class="rise-in mt-3 text-4xl sm:text-5xl" style="--i: 1">Clear, reviewed briefings.</h1>
                        <p class="rise-in mt-5 text-lg leading-relaxed text-muted" style="--i: 2">
                            Briefintable is where contributors write and editors approve. Every briefing is reviewed
                            before it goes live, so what you read is worth your time.
                        </p>

                        <div class="rise-in mt-8 flex flex-wrap items-center gap-3" style="--i: 3">
                            <a href="{{ route('briefings.index') }}" class="btn btn-primary">Read the briefings</a>
                            @auth
                            <a href="{{ route('drafts.index') }}" class="btn btn-secondary">My drafts</a>
                            @else
                            <a href="{{ route('login') }}" class="btn btn-secondary">Log in</a>
                            @endauth
                        </div>
                    </div>

                    {{-- Decorative: a draft becoming a published briefing --}}
                    <div class="rise-in relative mx-auto hidden h-72 w-full max-w-sm md:block" style="--i: 2" aria-hidden="true">
                        <div class="absolute inset-x-4 top-2 h-56 rotate-3 rounded-card bg-primary-soft"></div>
                        <div class="absolute inset-x-2 top-5 h-56 -rotate-2 rounded-card bg-primary/15"></div>
                        <div class="card absolute inset-x-0 top-10 p-5 shadow-lift">
                            <div class="flex items-center justify-between">
                                <span class="badge badge-warning">Draft</span>
                                <svg class="h-4 w-4 text-line-strong" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                                <span class="badge badge-success">Published</span>
                            </div>
                            <div class="mt-4 h-3 w-3/4 rounded bg-ink"></div>
                            <div class="mt-4 space-y-2">
                                <div class="h-2 rounded bg-line"></div>
                                <div class="h-2 rounded bg-line"></div>
                                <div class="h-2 w-5/6 rounded bg-line"></div>
                                <div class="h-2 w-2/3 rounded bg-line"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Value points --}}
        <section class="px-6 py-6" aria-labelledby="why-heading">
            <div class="card rise-in mx-auto max-w-5xl p-8 md:p-12" style="--i: 4">
                <h2 id="why-heading" class="sr-only">Why Briefintable</h2>
                <ul class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <li>
                        <svg class="h-7 w-7 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                        <h3 class="mt-4 text-base">Reviewed before publishing</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">Contributors submit drafts. An editor publishes only what is ready.</p>
                    </li>
                    <li>
                        <svg class="h-7 w-7 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12H12m-8.25 5.25h16.5" />
                        </svg>
                        <h3 class="mt-4 text-base">Easy to scan</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">A clear title and a short preview on every briefing, so you decide in seconds what to read.</p>
                    </li>
                    <li>
                        <svg class="h-7 w-7 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        <h3 class="mt-4 text-base">Your drafts, your control</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">Edit or delete your own drafts any time before an editor publishes them.</p>
                    </li>
                    <li>
                        <svg class="h-7 w-7 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                        <h3 class="mt-4 text-base">Open to read</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">Published briefings are public. No account needed to read them.</p>
                    </li>
                </ul>
            </div>
        </section>

        {{-- How it works --}}
        <section class="px-6 py-10" aria-labelledby="how-heading">
            <div class="mx-auto max-w-5xl">
                <h2 id="how-heading" class="rise-in text-2xl" style="--i: 5">How it works</h2>
                <ol class="mt-8 grid gap-6 md:grid-cols-3">
                    <li class="card rise-in p-6" style="--i: 5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-contrast">1</span>
                            <h3 class="text-base">Write</h3>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-muted">Log in and write a briefing. It is saved as a draft that only you can see.</p>
                    </li>
                    <li class="card rise-in p-6" style="--i: 6">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-contrast">2</span>
                            <h3 class="text-base">Review</h3>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-muted">An editor checks it in the control panel and publishes it when it is ready.</p>
                    </li>
                    <li class="card rise-in p-6" style="--i: 7">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-contrast">3</span>
                            <h3 class="text-base">Read</h3>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-muted">Published briefings appear on the public list straight away.</p>
                    </li>
                </ol>
            </div>
        </section>

        {{-- Latest briefings (real data, cached per version) --}}
        <section class="px-6 pb-10" aria-labelledby="latest-heading">
            <div class="mx-auto max-w-5xl">
                <div class="rise-in flex flex-wrap items-end justify-between gap-2" style="--i: 5">
                    <div>
                        <h2 id="latest-heading" class="text-2xl">Latest briefings</h2>
                        <p class="mt-1 text-sm text-subtle">{{ number_format($count) }} {{ \Illuminate\Support\Str::plural('briefing', $count) }} published</p>
                    </div>
                    <a href="{{ route('briefings.index') }}" class="link text-sm">All briefings &rarr;</a>
                </div>

                @if (count($latest))
                <div class="mt-6 grid gap-6 md:grid-cols-3">
                    @foreach ($latest as $briefing)
                    <article class="card card-lift rise-in flex flex-col p-6" style="--i: {{ 5 + $loop->index }}">
                        <p class="text-xs text-subtle">{{ $briefing['date'] }}</p>
                        <h3 class="mt-2 text-lg">
                            <a href="{{ $briefing['url'] }}" class="hover:text-primary">{{ $briefing['title'] }}</a>
                        </h3>
                        <p class="mt-3 flex-1 text-sm leading-relaxed text-muted">{{ $briefing['excerpt'] }}</p>
                        <a href="{{ $briefing['url'] }}" class="link mt-4 text-sm">Read briefing &rarr;</a>
                    </article>
                    @endforeach
                </div>
                @else
                <p class="card rise-in mt-6 p-6 text-subtle" style="--i: 5">The first briefings are on their way.</p>
                @endif
            </div>
        </section>

        {{-- Closing call to action --}}
        @guest
        <section class="px-6 pb-16">
            <div class="rise-in mx-auto max-w-5xl rounded-card bg-primary px-8 py-12 text-center shadow-card" style="--i: 8">
                <h2 class="text-2xl text-primary-contrast sm:text-3xl">Ready to write one?</h2>
                <p class="mx-auto mt-3 max-w-xl text-primary-contrast/85">Log in to submit your first briefing. An editor will take it from there.</p>
                <a href="{{ route('login') }}" class="btn btn-inverse mt-8">Log in</a>
            </div>
        </section>
        @else
        @can(\App\Support\Briefings::CAN_CREATE)
        <section class="px-6 pb-16">
            <div class="rise-in mx-auto max-w-5xl rounded-card bg-primary px-8 py-12 text-center shadow-card" style="--i: 8">
                <h2 class="text-2xl text-primary-contrast sm:text-3xl">Have something to share?</h2>
                <p class="mx-auto mt-3 max-w-xl text-primary-contrast/85">Write a briefing now. It is saved as a draft until an editor publishes it.</p>
                <a href="{{ route('briefings.create') }}" class="btn btn-inverse mt-8">Write a briefing</a>
            </div>
        </section>
        @endcan
        @endguest
    </main>

    <footer class="border-t border-line bg-surface px-6 py-8">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 text-sm text-subtle">
            <p>&copy; {{ date('Y') }} Aghyad Al Logie</p>
            <nav class="flex gap-6" aria-label="Footer">
                <a href="{{ route('briefings.index') }}" class="hover:text-primary">Briefings</a>
                @guest
                <a href="{{ route('login') }}" class="hover:text-primary">Log in</a>
                @endguest
            </nav>
        </div>
    </footer>
</x-layouts.app>