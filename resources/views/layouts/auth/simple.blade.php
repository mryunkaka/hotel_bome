<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full">
<head>@include('partials.head')</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <main class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(420px,0.9fr)]">
        <section class="relative hidden overflow-hidden bg-zinc-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-amber-800/40 via-zinc-950 to-zinc-950"></div>
            <div class="relative flex items-center gap-3">
                <span class="flex size-11 items-center justify-center rounded-xl border border-white/15 bg-white/10"><x-app-logo-icon class="size-6 fill-current" /></span>
                <span class="text-sm font-semibold tracking-[0.2em] uppercase">{{ config('app.name', 'Hotel Bome') }}</span>
            </div>
            <div class="relative max-w-xl pb-8">
                <p class="mb-5 text-xs font-semibold tracking-[0.25em] text-amber-300 uppercase">Hospitality, thoughtfully managed</p>
                <h1 class="text-5xl leading-tight font-medium tracking-tight xl:text-6xl">A calmer way to run your hotel.</h1>
                <p class="mt-6 max-w-md text-base leading-7 text-zinc-300">Manage the details behind every great stay from one considered workspace.</p>
            </div>
            <p class="relative text-xs text-zinc-500">&copy; {{ date('Y') }} {{ config('app.name', 'Hotel Bome') }}. {{ __('All rights reserved.') }}</p>
        </section>
        <section class="flex min-h-screen items-center justify-center px-6 py-12 sm:px-10">
            <div class="w-full max-w-md">
                <div class="mb-10 flex items-center gap-3 lg:hidden"><span class="flex size-10 items-center justify-center rounded-xl bg-zinc-900 text-white"><x-app-logo-icon class="size-5 fill-current" /></span><span class="font-semibold tracking-wide">{{ config('app.name', 'Hotel Bome') }}</span></div>
                {{ $slot }}
                <p class="mt-10 text-center text-xs text-zinc-500">{{ __('Secure hotel operations workspace') }}</p>
            </div>
        </section>
    </main>
    @fluxScripts
</body>
</html>
