{{-- URL-based pagination: page number is a path segment, never ?page=N. --}}
@php
    $link = fn (int $page) => \App\Support\Content\ListingFilters::url($baseUrl, $listingFilters, $page, $isTours ?? false);
@endphp
@if($paginator->hasPages())
<nav role="navigation" aria-label="Навигация по страницам" class="flex justify-center">
    <div class="inline-flex max-w-full items-center gap-1 rounded-full bg-white p-1.5 shadow-[0_8px_30px_rgba(16,42,39,0.09)]">
        @if($paginator->onFirstPage())
            <span aria-disabled="true" class="inline-flex size-10 items-center justify-center rounded-full text-[#102a27]/25"><x-tabler-chevron-left class="size-5" stroke-width="1.8" /></span>
        @else
            <a href="{{ $link($paginator->currentPage() - 1) }}" rel="prev" aria-label="Предыдущая страница" class="inline-flex size-10 items-center justify-center rounded-full text-[#102a27] hover:bg-[#edf3ef]"><x-tabler-chevron-left class="size-5" stroke-width="1.8" /></a>
        @endif

        @foreach($elements as $element)
            @if(is_string($element))
                <span aria-hidden="true" class="hidden size-10 items-center justify-center text-sm text-[#102a27]/40 sm:inline-flex">{{ $element }}</span>
            @else
                @foreach($element as $page => $ignored)
                    @if($page === $paginator->currentPage())
                        <span aria-current="page" class="inline-flex size-10 items-center justify-center rounded-full bg-[#102a27] text-sm font-medium text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $link((int) $page) }}" aria-label="Страница {{ $page }}" class="{{ abs($page - $paginator->currentPage()) <= 1 ? 'inline-flex' : 'hidden sm:inline-flex' }} size-10 items-center justify-center rounded-full text-sm text-[#102a27]/75 hover:bg-[#edf3ef]">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if($paginator->hasMorePages())
            <a href="{{ $link($paginator->currentPage() + 1) }}" rel="next" aria-label="Следующая страница" class="inline-flex size-10 items-center justify-center rounded-full text-[#102a27] hover:bg-[#edf3ef]"><x-tabler-chevron-right class="size-5" stroke-width="1.8" /></a>
        @else
            <span aria-disabled="true" class="inline-flex size-10 items-center justify-center rounded-full text-[#102a27]/25"><x-tabler-chevron-right class="size-5" stroke-width="1.8" /></span>
        @endif
    </div>
</nav>
@endif
