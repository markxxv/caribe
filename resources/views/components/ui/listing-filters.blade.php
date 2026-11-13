@props(['baseUrl', 'filters', 'countries' => collect(), 'tours' => false])

<div class="flex min-w-0 items-center gap-2 py-3 md:gap-3">
    <span class="hidden shrink-0 items-center gap-2 text-sm font-medium text-gray-700 lg:inline-flex">
        <x-tabler-filter class="size-4" stroke-width="1.5" /> Фильтры
    </span>

    <form data-listing-filters data-base="{{ $baseUrl }}" data-tours="{{ $tours ? '1' : '0' }}"
        action="{{ $baseUrl }}" method="GET" role="search"
        class="flex min-w-0 flex-1 items-center gap-2 md:gap-3">
        @if($countries->isNotEmpty())
            <input type="hidden" name="country" value="{{ $filters['country'] ?? 'all' }}" data-country-value>
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

        <div data-listing-search id="listing-search-panel"
            class="{{ $countries->isNotEmpty() ? 'hidden md:flex' : 'flex' }} min-w-0 flex-1 items-center gap-2 md:max-w-md">
            <label class="min-w-0 flex-1">
                <span class="sr-only">Поиск по материалам</span>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="Поиск по материалам" autocomplete="off" maxlength="120"
                    class="w-full min-w-0 rounded-full bg-gray-50 px-4 py-2.5 text-base text-gray-800 outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 md:text-sm">
            </label>
            <button type="submit" aria-label="Найти"
                class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-[#163d32] text-white transition-colors hover:bg-emerald-800 md:h-10 md:w-auto md:px-5">
                <x-tabler-search class="size-5 md:hidden" stroke-width="1.8" />
                <span class="hidden md:inline">Найти</span>
            </button>
        </div>

        @if($countries->isNotEmpty())
            <button type="button" data-listing-open-search aria-controls="listing-search-panel" aria-expanded="false" aria-label="Открыть поиск"
                class="relative inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-[#163d32] text-white md:hidden">
                <x-tabler-search class="size-5" stroke-width="1.8" />
                @if($filters['search'] ?? null)
                    <span class="absolute right-2 top-2 size-1.5 rounded-full bg-[#eccd31]" aria-hidden="true"></span>
                @endif
            </button>
            <button type="button" data-listing-close-search aria-label="Закрыть поиск"
                class="hidden size-11 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-700 md:hidden">
                <x-tabler-x class="size-5" stroke-width="1.8" />
            </button>
        @endif
    </form>

    @if(($filters['country'] ?? null) || ($filters['search'] ?? null))
        <a href="{{ $baseUrl }}" class="hidden shrink-0 text-sm text-gray-500 hover:text-emerald-700 lg:inline-flex">Сбросить</a>
    @endif
</div>
