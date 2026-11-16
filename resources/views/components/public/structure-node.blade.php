@props(['unit', 'depth' => 1])

@php
    $photoUrl = $unit->photoUrl();
    $children = $unit->visibleChildren ?? collect();
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border border-ink-100 bg-white']) }}>
    <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start">
        @if ($photoUrl)
            <img src="{{ $photoUrl }}"
                 alt="Foto {{ $unit->official_name ?: $unit->name }}"
                 class="h-16 w-16 shrink-0 rounded-full border border-ink-100 object-cover"
                 loading="lazy">
        @else
            <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-ink-50 text-ink-400" aria-hidden="true">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                </svg>
            </span>
        @endif

        <div class="min-w-0">
            @if ($depth === 1)
                <h2 class="font-serif text-xl font-bold leading-snug text-ink-950">{{ $unit->name }}</h2>
            @else
                <h3 class="font-serif text-lg font-bold leading-snug text-ink-950">{{ $unit->name }}</h3>
            @endif

            @if (filled($unit->official_name))
                <p class="mt-1 text-sm font-semibold text-ink-700">{{ $unit->official_name }}</p>
            @else
                <p class="mt-1 text-xs text-ink-400">Nama pejabat belum tersedia.</p>
            @endif

            @if (filled($unit->description))
                <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ink-600">{{ $unit->description }}</p>
            @endif
        </div>
    </div>

    @if ($children->isNotEmpty())
        <div class="border-t border-dashed border-ink-100 px-4 pb-5 pt-4 sm:px-5">
            <p class="eyebrow mb-3 text-ink-400">Unit bawahan</p>
            <div class="space-y-4 border-l-2 border-ink-100 pl-4 sm:pl-6">
                @foreach ($children as $child)
                    <x-public.structure-node :unit="$child" :depth="$depth + 1" />
                @endforeach
            </div>
        </div>
    @endif
</div>
