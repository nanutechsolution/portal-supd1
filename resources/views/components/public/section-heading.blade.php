@props(['title', 'href' => null, 'linkLabel' => 'Lihat semua', 'description' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-end justify-between gap-3 border-b border-ink-200 pb-3']) }}>
    <div>
        <h2 class="flex items-center gap-3 font-serif text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">
            <span class="inline-block h-6 w-1 rounded-full bg-accent-400" aria-hidden="true"></span>
            {{ $title }}
        </h2>
        @if ($description)
            <p class="mt-1.5 pl-4 text-sm text-ink-500">{{ $description }}</p>
        @endif
    </div>
    @if ($href)
        <a href="{{ $href }}" class="group inline-flex items-center gap-1 text-sm font-semibold text-accent-600 hover:text-accent-700">
            {{ $linkLabel }}
            <span aria-hidden="true" class="transition group-hover:translate-x-0.5">&rarr;</span>
        </a>
    @endif
</div>
