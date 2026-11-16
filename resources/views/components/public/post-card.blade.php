@props(['post', 'variant' => 'default', 'showExcerpt' => true])

@php
    $url = route('posts.show', $post);
    $image = $post->featuredImageUrl();
    $date = $post->published_at?->translatedFormat('j M Y');
@endphp

@if ($variant === 'compact')
    <article class="group flex gap-3">
        <a href="{{ $url }}" class="shrink-0 overflow-hidden rounded" tabindex="-1" aria-hidden="true">
            <img src="{{ $image }}" alt="" loading="lazy" width="96" height="72"
                 class="h-[72px] w-24 object-cover transition duration-500 group-hover:scale-105">
        </a>
        <div class="min-w-0">
            <a href="{{ route('categories.show', $post->category) }}" class="eyebrow text-accent-600">
                {{ $post->category->name }}
            </a>
            <h3 class="mt-1 font-serif text-[15px] font-bold leading-snug text-ink-950">
                <a href="{{ $url }}" class="headline-link line-clamp-2">{{ $post->title }}</a>
            </h3>
            <p class="mt-1 text-[11px] text-ink-500">{{ $date }}</p>
        </div>
    </article>
@elseif ($variant === 'horizontal')
    <article class="group grid gap-4 sm:grid-cols-[220px_1fr]">
        <a href="{{ $url }}" class="block overflow-hidden rounded" tabindex="-1" aria-hidden="true">
            <img src="{{ $image }}" alt="" loading="lazy" width="440" height="300"
                 class="aspect-[3/2] w-full object-cover transition duration-500 group-hover:scale-105">
        </a>
        <div>
            <a href="{{ route('categories.show', $post->category) }}" class="eyebrow text-accent-600">
                {{ $post->category->name }}
            </a>
            <h3 class="mt-1.5 font-serif text-xl font-bold leading-snug text-ink-950">
                <a href="{{ $url }}" class="headline-link">{{ $post->title }}</a>
            </h3>
            @if ($showExcerpt && $post->excerpt)
                <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-600">{{ $post->excerpt }}</p>
            @endif
            <p class="mt-2 text-xs text-ink-500">
                {{ $date }}
                @if ($post->author)
                    &middot; <a href="{{ route('archive.index', ['penulis' => $post->author->getKey()]) }}" class="hover:text-accent-600">{{ $post->author->name }}</a>
                @endif
            </p>
        </div>
    </article>
@else
    <article class="group flex h-full flex-col">
        <a href="{{ $url }}" class="block overflow-hidden rounded" tabindex="-1" aria-hidden="true">
            <img src="{{ $image }}" alt="" loading="lazy" width="640" height="400"
                 class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-105">
        </a>
        <div class="flex flex-1 flex-col pt-3">
            <a href="{{ route('categories.show', $post->category) }}" class="eyebrow text-accent-600">
                {{ $post->category->name }}
            </a>
            <h3 class="mt-1.5 font-serif text-lg font-bold leading-snug text-ink-950">
                <a href="{{ $url }}" class="headline-link">{{ $post->title }}</a>
            </h3>
            @if ($showExcerpt && $post->excerpt)
                <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-600">{{ $post->excerpt }}</p>
            @endif
            <p class="mt-auto pt-3 text-xs text-ink-500">
                {{ $date }}
                @if ($post->author)
                    &middot; <a href="{{ route('archive.index', ['penulis' => $post->author->getKey()]) }}" class="hover:text-accent-600">{{ $post->author->name }}</a>
                @endif
            </p>
        </div>
    </article>
@endif
