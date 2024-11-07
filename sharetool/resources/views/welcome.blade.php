<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>ShareTool</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="antialiased font-sans">
        <div data-swapbackground>
            <div style="background-image: url({{ Vite::asset('resources/images/background/0.jpg') }})" data-swapbackground-active></div>
            @for ($i = 1; $i <= 9; $i++)
            <div style="background-image: url({{ Vite::asset("resources/images/background/$i.jpg") }})"></div>
            @endfor
        </div>

        <div class="bg-gray-50">
            <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
                <div class="relative w-full max-w-2xl lg:max-w-7xl">

                    <div class="max-w-xl mx-auto rounded-lg overflow-hidden shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] bg-zinc-900 ring-zinc-800 text-white/70">
                        <img alt="ShareTool" title="ShareTool" src="{{ Vite::asset('resources/images/banner.jpg') }}" width="608" height="467"/>

                        <div class="p-6 sm:pt-5">
                            <p class="my-4 text-sm/relaxed">
                                {{ __('ShareTool lets you easily share images, videos and files with your friends.') }}
                            </p>

                            @if (Route::has('login'))
                                <livewire:welcome.navigation />
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
