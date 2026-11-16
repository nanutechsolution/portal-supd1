@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="mt-12 flex flex-wrap items-center justify-center gap-1.5">
        @if ($paginator->onFirstPage())
            <span class="inline-flex h-9 w-9 items-center justify-center rounded border border-ink-100 text-ink-300" aria-hidden="true">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               class="inline-flex h-9 w-9 items-center justify-center rounded border border-ink-200 text-ink-600 hover:border-ink-400 hover:text-ink-950">
                <span class="sr-only">Halaman sebelumnya</span>
                <span aria-hidden="true">&lsaquo;</span>
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="inline-flex h-9 items-center px-2 text-sm text-ink-400">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"
                              class="inline-flex h-9 min-w-9 items-center justify-center rounded bg-ink-900 px-2 text-sm font-semibold text-white">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                           class="inline-flex h-9 min-w-9 items-center justify-center rounded border border-ink-200 px-2 text-sm text-ink-700 hover:border-ink-400 hover:text-ink-950">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               class="inline-flex h-9 w-9 items-center justify-center rounded border border-ink-200 text-ink-600 hover:border-ink-400 hover:text-ink-950">
                <span class="sr-only">Halaman berikutnya</span>
                <span aria-hidden="true">&rsaquo;</span>
            </a>
        @else
            <span class="inline-flex h-9 w-9 items-center justify-center rounded border border-ink-100 text-ink-300" aria-hidden="true">&rsaquo;</span>
        @endif
    </nav>
@endif
