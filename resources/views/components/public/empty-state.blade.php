@props(['title' => 'Belum ada konten', 'message' => 'Konten untuk bagian ini belum tersedia.', 'action' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-ink-200 bg-white/60 px-6 py-14 text-center']) }}>
    <p class="font-serif text-lg font-bold text-ink-800">{{ $title }}</p>
    <p class="mx-auto mt-2 max-w-md text-sm text-ink-500">{{ $message }}</p>
    @if ($action && $actionLabel)
        <a href="{{ $action }}" class="mt-5 inline-flex rounded-full bg-ink-900 px-4 py-2 text-sm font-semibold text-white hover:bg-ink-800">
            {{ $actionLabel }}
        </a>
    @endif
</div>
