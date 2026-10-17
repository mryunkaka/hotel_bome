<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>@include('partials.head')</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased">
    <main class="flex min-h-screen items-center justify-center p-6">
        <section class="w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm">
            <a href="{{ route('home') }}" class="mb-8 flex justify-center" wire:navigate><x-app-logo-icon class="h-10 w-10 fill-current text-zinc-900" /></a>
            {{ $slot }}
        </section>
    </main>
</body>
</html>
