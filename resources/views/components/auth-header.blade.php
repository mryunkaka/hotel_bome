@props([
    'title',
    'description',
])

<div class="w-full text-center">
    <h1 class="text-2xl font-semibold">{{ $title }}</h1>
    <p class="mt-2 text-sm text-zinc-600">{{ $description }}</p>
</div>
