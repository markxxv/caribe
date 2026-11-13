@props(['baseUrl', 'filters', 'countries' => collect(), 'tours' => false, 'categories' => collect()])

@php
    $search = $filters['search'] ?? null;
    $clearSearchUrl = $search
        ? \App\Support\Content\ListingFilters::url($baseUrl, array_merge($filters, ['search' => null]), 1, $tours)
        : null;
@endphp

<div class="flex min-w-0 items-center gap-2 py-2.5 md:gap-3 md:py-3">
    <form data-listing-filters data-base="{{ $baseUrl }}" data-tours="{{ $tours ? '1' : '0' }}"
        action="{{ $baseUrl }}" method="GET" role="search"
        class="flex min-w-0 flex-1 items-center gap-2 md:gap-3">
        @if($countries->isNotEmpty())
            <input type="hidden" name="country" value="{{ $filters['country'] ?? 'all' }}" data-country-value>
        @endif

        {{-- Основной вид: страна и/или категории. Поиск свёрнут и открывается кнопкой справа. --}}
        <div data-listing-default class="flex min-w-0 flex-1 items-center gap-2 md:gap-3">
            @if($countries->isNotEmpty())
                <label data-listing-country class="flex min-w-0 flex-1 items-center gap-2 rounded-full bg-gray-50 px-3 py-2.5 text-gray-700 md:flex-none md:px-4">
                    <x-tabler-world class="size-4 shrink-0" stroke-width="1.5" />
                    <span class="sr-only">Страна</span>
                    <select aria-label="Страна" data-country-select
                        class="w-full min-w-0 cursor-pointer bg-transparent text-base outline-none md:w-auto md:max-w-44 md:text-sm">
                        <option value="all">Все страны</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->slug }}" @selected(($filters['country'] ?? null) === $country->slug)>{{ $country->title }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            @if($search)
                <a href="{{ $clearSearchUrl }}" aria-label="Сбросить поиск «{{ $search }}»"
                    class="inline-flex max-w-[45%] shrink-0 items-center gap-1.5 rounded-full bg-[#edf3ef] py-2 pl-3.5 pr-2.5 text-sm text-[#102a27] transition-colors hover:bg-[#dfeae3]">
                    <span class="truncate">{{ $search }}</span>
                    <x-tabler-x class="size-4 shrink-0" stroke-width="1.8" />
                </a>
            @endif

            @if($categories->isNotEmpty())
                <nav aria-label="Категории" data-listing-categories
                    class="relative min-w-0 flex-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <ul class="flex w-max items-center gap-1">
                        @foreach($categories as $category)
                            <li>
                                <a href="{{ $category['url'] }}" @if($category['active']) aria-current="page" @endif
                                    class="block whitespace-nowrap rounded-full px-3.5 py-2 text-sm font-medium transition-colors {{ $category['active'] ? 'bg-[#163d32] text-white' : 'text-[#102a27]/70 hover:bg-gray-100 hover:text-[#102a27]' }}">
                                    {{ $category['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>

        <div data-listing-search id="listing-search-panel"
            class="hidden min-w-0 flex-1 items-center gap-2 md:max-w-xl">
            <label class="min-w-0 flex-1">
                <span class="sr-only">Поиск по материалам</span>
                <input type="search" name="search" value="{{ $search ?? '' }}"
                    placeholder="Поиск по материалам" autocomplete="off" maxlength="120"
                    class="w-full min-w-0 rounded-full bg-gray-50 px-4 py-2.5 text-base text-gray-800 outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 md:text-sm">
            </label>
            <button type="submit" aria-label="Найти"
                class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-[#163d32] text-white transition-colors hover:bg-emerald-800 md:h-10 md:w-auto md:px-5">
                <x-tabler-search class="size-5 md:hidden" stroke-width="1.8" />
                <span class="hidden md:inline">Найти</span>
            </button>
        </div>

        <button type="button" data-listing-open-search aria-controls="listing-search-panel" aria-expanded="false" aria-label="Открыть поиск"
            class="inline-flex size-11 shrink-0 items-center justify-center gap-2 rounded-full bg-[#163d32] text-white transition-colors hover:bg-emerald-800 md:h-10 md:w-auto md:px-4">
            <x-tabler-search class="size-5 md:size-4" stroke-width="1.8" />
            <span class="hidden text-sm md:inline">Поиск</span>
        </button>
        <button type="button" data-listing-close-search aria-label="Закрыть поиск"
            class="hidden size-11 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-700 transition-colors hover:bg-gray-200 md:size-10">
            <x-tabler-x class="size-5" stroke-width="1.8" />
        </button>
    </form>

    @if(($filters['country'] ?? null))
        <a href="{{ $baseUrl }}" class="hidden shrink-0 text-sm text-gray-500 hover:text-emerald-700 lg:inline-flex">Сбросить</a>
    @endif
</div>
