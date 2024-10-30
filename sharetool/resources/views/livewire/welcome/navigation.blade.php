<nav class="-mx-3 flex flex-1 justify-center">
    @auth
        <a
            href="{{ url('/dashboard') }}"
            class="rounded-md bg-white hover:bg-gray-400 uppercase font-bold px-6 py-2 text-zinc-900 transition mx-2"
        >
            Dashboard
        </a>
    @else
        <a
            href="{{ route('login') }}"
            class="rounded-md bg-white hover:bg-gray-400 uppercase font-bold px-6 py-2 text-zinc-900 transition mx-2"
        >
            Log in
        </a>

        @if (Route::has('register'))
            <a
                href="{{ route('register') }}"
                class="rounded-md bg-white hover:bg-gray-400 uppercase font-bold px-6 py-2 text-zinc-900 transition mx-2"
            >
                Register
            </a>
        @endif
    @endauth
</nav>
