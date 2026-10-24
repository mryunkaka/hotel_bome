<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123c3a">
    <title>Hotel Bome — {{ __('Hospitality, thoughtfully managed') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-950 antialiased">
    <header class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-5 py-5 sm:px-8 lg:px-12">
        <a href="{{ route('home') }}" class="flex min-h-11 items-center gap-3 rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-800" aria-label="Hotel Bome home">
            <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-950 text-lg font-semibold text-white">B</span>
            <span class="text-lg font-semibold tracking-tight">Hotel Bome</span>
        </a>
        <nav aria-label="{{ __('Main navigation') }}">
            @if (Route::has('login'))
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-full bg-emerald-950 px-5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-800">{{ __('Open workspace') }}</a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-full border border-stone-300 px-5 text-sm font-semibold text-stone-900 transition hover:border-emerald-800 hover:text-emerald-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-800">{{ __('Log in') }}</a>
                @endauth
            @endif
        </nav>
    </header>

    <main class="mx-auto grid min-h-[calc(100svh-5rem)] w-full max-w-7xl items-center gap-12 px-5 pb-16 pt-10 sm:px-8 lg:grid-cols-[1.1fr_0.9fr] lg:px-12 lg:py-16">
        <section class="max-w-2xl">
            <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-900/15 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-950">
                <span class="size-2 rounded-full bg-emerald-700" aria-hidden="true"></span>
                {{ __('Hospitality, thoughtfully managed') }}
            </p>
            <h1 class="max-w-xl text-5xl font-semibold leading-[1.04] tracking-tight text-stone-950 sm:text-6xl lg:text-7xl">{{ __('A calmer way to run your hotel.') }}</h1>
            <p class="mt-6 max-w-xl text-lg leading-8 text-stone-600">{{ __('One considered workspace for the people and places that make every stay matter.') }}</p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-emerald-950 px-7 text-sm font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-800">{{ __('Go to your workspace') }}<span class="ms-2" aria-hidden="true">→</span></a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-emerald-950 px-7 text-sm font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-800">{{ __('Log in to Hotel Bome') }}<span class="ms-2" aria-hidden="true">→</span></a>
                    @endauth
                @endif
            </div>
        </section>

        <aside class="relative overflow-hidden rounded-[2rem] bg-emerald-950 p-7 text-white shadow-xl shadow-emerald-950/10 sm:p-10 lg:min-h-[27rem] lg:p-12" aria-label="{{ __('Hotel Bome workspace') }}">
            <div class="absolute -right-16 -top-20 size-64 rounded-full border border-white/10" aria-hidden="true"></div>
            <div class="absolute -right-5 -top-9 size-44 rounded-full border border-white/10" aria-hidden="true"></div>
            <div class="relative flex h-full min-h-64 flex-col justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-100">{{ __('Designed around the day') }}</p>
                    <h2 class="mt-3 max-w-sm text-3xl font-medium leading-tight tracking-tight sm:text-4xl">{{ __('Clearer work. More room for hospitality.') }}</h2>
                </div>
                <div class="mt-12 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-white/15 bg-white/5 p-4">
                        <span class="mb-4 block size-2 rounded-full bg-teal-300" aria-hidden="true"></span>
                        <p class="text-sm font-medium">{{ __('People first') }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/5 p-4">
                        <span class="mb-4 block size-2 rounded-full bg-amber-300" aria-hidden="true"></span>
                        <p class="text-sm font-medium">{{ __('Work in one place') }}</p>
                    </div>
                </div>
            </div>
        </aside>
    </main>

    <footer class="mx-auto flex w-full max-w-7xl flex-col gap-2 border-t border-stone-200 px-5 py-6 text-sm text-stone-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
        <span>© {{ date('Y') }} Hotel Bome</span>
        <span>{{ __('Hospitality, thoughtfully managed') }}</span>
    </footer>
</body>
</html>
