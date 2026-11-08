<x-layouts.app
    title="Briefintable | Clear, reviewed briefings"
    description="Briefintable is where contributors write and editors approve. Every briefing is reviewed before it goes live.">

    <main>
        {{-- Hero --}}
        <section class="px-6 pt-10 pb-6">
            <div class="mx-auto max-w-5xl rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200 md:p-12">
                <div class="grid items-center gap-10 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold tracking-widest text-indigo-600 uppercase">Briefintable</p>
                        <h1 class="mt-3 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Clear, reviewed briefings.</h1>
                        <p class="mt-5 text-lg leading-relaxed text-gray-600">
                            Briefintable is where contributors write and editors approve. Every briefing is reviewed
                            before it goes live, so what you read is worth your time.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <a href="{{ route('briefings.index') }}"
                                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2">
                                Read the briefings
                            </a>
                            @auth
                            <a href="{{ route('drafts.index') }}"
                                class="inline-flex items-center justify-center rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2">
                                My drafts
                            </a>
                            @else
                            <a href="{{ route('login') }}"
                                class="inline-flex items-center justify-center rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2">
                                Log in
                            </a>
                            @endauth
                        </div>
                    </div>

                    {{-- Decorative: a draft becoming a published briefing --}}
                    <div class="relative mx-auto hidden h-72 w-full max-w-sm md:block" aria-hidden="true">
                        <div class="absolute inset-x-4 top-2 h-56 rotate-3 rounded-xl bg-indigo-100"></div>
                        <div class="absolute inset-x-2 top-5 h-56 -rotate-2 rounded-xl bg-indigo-200/70"></div>
                        <div class="absolute inset-x-0 top-10 rounded-xl bg-white p-5 shadow-lg ring-1 ring-gray-200">
                            <div class="flex items-center justify-between">
                                <span class="rounded bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">Draft</span>
                                <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                <span class="rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Published</span>
                            </div>
                            <div class="mt-4 h-3 w-3/4 rounded bg-gray-800"></div>
                            <div class="mt-4 space-y-2">
                                <div class="h-2 rounded bg-gray-200"></div>
                                <div class="h-2 rounded bg-gray-200"></div>
                                <div class="h-2 w-5/6 rounded bg-gray-200"></div>
                                <div class="h-2 w-2/3 rounded bg-gray-200"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Value points --}}
        <section class="px-6 py-6" aria-labelledby="why-heading">
            <div class="mx-auto max-w-5xl rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200 md:p-12">
                <h2 id="why-heading" class="sr-only">Why Briefintable</h2>
                <ul class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <li>
                        <svg class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                        <h3 class="mt-4 font-semibold text-gray-900">Reviewed before publishing</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">Contributors submit drafts. An editor publishes only what is ready.</p>
                    </li>
                    <li>
                        <svg class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12H12m-8.25 5.25h16.5" /></svg>
                        <h3 class="mt-4 font-semibold text-gray-900">Easy to scan</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">A clear title and a short preview on every briefing, so you decide in seconds what to read.</p>
                    </li>
                    <li>
                        <svg class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        <h3 class="mt-4 font-semibold text-gray-900">Your drafts, your control</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">Edit or delete your own drafts any time before an editor publishes them.</p>
                    </li>
                    <li>
                        <svg class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                        <h3 class="mt-4 font-semibold text-gray-900">Open to read</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">Published briefings are public. No account needed to read them.</p>
                    </li>
                </ul>
            </div>
        </section>

        {{-- How it works --}}
        <section class="px-6 py-10" aria-labelledby="how-heading">
            <div class="mx-auto max-w-5xl">
                <h2 id="how-heading" class="text-2xl font-bold tracking-tight text-gray-900">How it works</h2>
                <ol class="mt-8 grid gap-6 md:grid-cols-3">
                    <li class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">1</span>
                        <h3 class="mt-4 font-semibold text-gray-900">Write</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">Log in and write a briefing. It is saved as a draft that only you can see.</p>
                    </li>
                    <li class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">2</span>
                        <h3 class="mt-4 font-semibold text-gray-900">Review</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">An editor checks it in the control panel and publishes it when it is ready.</p>
                    </li>
                    <li class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">3</span>
                        <h3 class="mt-4 font-semibold text-gray-900">Read</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">Published briefings appear on the public list straight away.</p>
                    </li>
                </ol>
            </div>
        </section>

        {{-- Latest briefings (real data, cached per version) --}}
        <section class="px-6 pb-10" aria-labelledby="latest-heading">
            <div class="mx-auto max-w-5xl">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 id="latest-heading" class="text-2xl font-bold tracking-tight text-gray-900">Latest briefings</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ number_format($count) }} {{ \Illuminate\Support\Str::plural('briefing', $count) }} published</p>
                    </div>
                    <a href="{{ route('briefings.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">All briefings &rarr;</a>
                </div>

                @if (count($latest))
                <div class="mt-6 grid gap-6 md:grid-cols-3">
                    @foreach ($latest as $briefing)
                    <article class="flex flex-col rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <p class="text-xs text-gray-500">{{ $briefing['date'] }}</p>
                        <h3 class="mt-2 text-lg font-semibold text-gray-900">
                            <a href="{{ $briefing['url'] }}" class="hover:text-indigo-700">{{ $briefing['title'] }}</a>
                        </h3>
                        <p class="mt-3 flex-1 text-sm leading-relaxed text-gray-600">{{ $briefing['excerpt'] }}</p>
                        <a href="{{ $briefing['url'] }}" class="mt-4 text-sm font-semibold text-indigo-600 hover:text-indigo-800">Read briefing &rarr;</a>
                    </article>
                    @endforeach
                </div>
                @else
                <p class="mt-6 rounded-xl bg-white p-6 text-gray-500 shadow-sm ring-1 ring-gray-200">The first briefings are on their way.</p>
                @endif
            </div>
        </section>

        {{-- Closing call to action --}}
        @guest
        <section class="px-6 pb-16">
            <div class="mx-auto max-w-5xl rounded-2xl bg-indigo-600 px-8 py-12 text-center shadow-sm">
                <h2 class="text-2xl font-bold text-white sm:text-3xl">Ready to write one?</h2>
                <p class="mx-auto mt-3 max-w-xl text-indigo-100">Log in to submit your first briefing. An editor will take it from there.</p>
                <a href="{{ route('login') }}"
                    class="mt-8 inline-flex items-center justify-center rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-indigo-600">
                    Log in
                </a>
            </div>
        </section>
        @else
        @can('create briefings entries')
        <section class="px-6 pb-16">
            <div class="mx-auto max-w-5xl rounded-2xl bg-indigo-600 px-8 py-12 text-center shadow-sm">
                <h2 class="text-2xl font-bold text-white sm:text-3xl">Have something to share?</h2>
                <p class="mx-auto mt-3 max-w-xl text-indigo-100">Write a briefing now. It is saved as a draft until an editor publishes it.</p>
                <a href="{{ route('briefings.create') }}"
                    class="mt-8 inline-flex items-center justify-center rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-indigo-600">
                    Write a briefing
                </a>
            </div>
        </section>
        @endcan
        @endguest
    </main>

    <footer class="border-t border-gray-200 bg-white px-6 py-8">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} Aghyad Al Logie</p>
            <nav class="flex gap-6" aria-label="Footer">
                <a href="{{ route('briefings.index') }}" class="hover:text-indigo-600">Briefings</a>
                @guest
                <a href="{{ route('login') }}" class="hover:text-indigo-600">Log in</a>
                @endguest
            </nav>
        </div>
    </footer>
</x-layouts.app>
