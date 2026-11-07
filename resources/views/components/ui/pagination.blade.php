{{-- Shared pagination for LengthAwarePaginator and simple Paginator. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Навигация по страницам" class="flex justify-center">
        <div class="inline-flex max-w-full items-center gap-1 rounded-full bg-white p-1.5 shadow-[0_8px_30px_rgba(16,42,39,0.09)]">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Предыдущая страница" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-[#102a27]/25">
                    <x-tabler-chevron-left class="size-5" stroke-width="1.8" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Предыдущая страница" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-[#102a27] transition-colors hover:bg-[#edf3ef] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#102a27]">
                    <x-tabler-chevron-left class="size-5" stroke-width="1.8" />
                </a>
            @endif

            {{-- Numbered pages are only provided by LengthAwarePaginator. --}}
            @if (isset($elements))
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-hidden="true" class="hidden size-10 shrink-0 items-center justify-center text-sm text-[#102a27]/40 sm:inline-flex">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" aria-label="Страница {{ $page }}" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-[#102a27] text-sm font-medium text-white">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" aria-label="Страница {{ $page }}" class="{{ abs($page - $paginator->currentPage()) <= 1 ? 'inline-flex' : 'hidden sm:inline-flex' }} size-10 shrink-0 items-center justify-center rounded-full text-sm font-medium text-[#102a27]/75 transition-colors hover:bg-[#edf3ef] hover:text-[#102a27] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#102a27]">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endif

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Следующая страница" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-[#102a27] transition-colors hover:bg-[#edf3ef] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#102a27]">
                    <x-tabler-chevron-right class="size-5" stroke-width="1.8" />
                </a>
            @else
                <span aria-disabled="true" aria-label="Следующая страница" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-[#102a27]/25">
                    <x-tabler-chevron-right class="size-5" stroke-width="1.8" />
                </span>
            @endif
        </div>
    </nav>
@endif
