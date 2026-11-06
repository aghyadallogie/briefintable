<nav class="bg-white border-b border-gray-200 px-6 py-4 sticky top-0 z-50">
    <div class="max-w-5xl mx-auto flex justify-between items-center">
        <!-- Logo / Home -->
        <a href="/" class="text-xl font-bold text-indigo-600">
            Briefintable
        </a>

        <!-- Links -->
        <div class="space-x-4">
            @unless (request()->is('briefings'))
            <a href="/briefings" class="text-gray-600 hover:text-indigo-600">All Briefings</a>
            @endunless
            @auth
            @unless (request()->is('briefings/create'))
            <a href="/briefings/create" class="text-gray-600 hover:text-indigo-600 font-semibold">Create New</a>
            @endunless
            @else
            @unless (request()->is('cp'))
            <a href="/cp" class="text-gray-600 hover:text-indigo-600">Login</a>
            @endunless
            @endauth
        </div>
    </div>
</nav>