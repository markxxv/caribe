@props(['baseUrl', 'filters', 'countries' => collect(), 'tours' => false])

@php
    $url = fn (array $overrides = []) => \App\Support\Content\ListingFilters::url(
        $baseUrl,
        array_merge($filters, $overrides, ['page' => 1]),
        null,
        $tours
    );
@endphp

<div class="flex flex-wrap items-center gap-3 py-4">
    <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
        <x-tabler-filter class="size-4" stroke-width="1.5" /> Фильтры
    </span>

    <form data-listing-filters data-base="{{ $baseUrl }}" data-tours="{{ $tours ? '1' : '0' }}"
        action="{{ $baseUrl }}" method="GET" class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
        <input type="hidden" name="country" value="{{ $filters['country'] ?? 'all' }}" data-country-value>
        @if($countries->isNotEmpty())
            <label class="inline-flex items-center gap-2 rounded-full bg-gray-50 px-4 py-2 text-sm text-gray-700">
                <x-tabler-world class="size-4 shrink-0" stroke-width="1.5" />
                <span class="sr-only">Страна</span>
                <select aria-label="Страна" data-country-select
                    class="max-w-44 cursor-pointer bg-transparent outline-none">
                    <option value="all">Все страны</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->slug }}" @selected(($filters['country'] ?? null) === $country->slug)>{{ $country->title }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        <div class="flex min-w-0 flex-1 items-center gap-2 sm:max-w-sm">
            <label class="min-w-0 flex-1">
                <span class="sr-only">Поиск по материалам</span>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="Поиск по материалам" autocomplete="off" maxlength="120"
                    class="w-full min-w-0 rounded-full bg-gray-50 px-4 py-2.5 text-sm text-gray-800 outline-none focus-visible:ring-2 focus-visible:ring-emerald-600">
            </label>
            <button type="submit" class="shrink-0 rounded-full bg-[#163d32] px-5 py-2.5 text-sm text-white transition-colors hover:bg-emerald-800">Найти</button>
        </div>
    </form>

    @if($filters['country'] ?? null)
        <a href="{{ $url(['country' => null]) }}"
            class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-2 text-sm text-emerald-800 hover:bg-emerald-200">
            {{ $countries->firstWhere('slug', $filters['country'])?->title ?? $filters['country'] }}
            <x-tabler-x class="size-3.5" stroke-width="1.5" />
        </a>
    @endif
    @if($filters['search'] ?? null)
        <a href="{{ $url(['search' => null]) }}"
            class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-2 text-sm text-emerald-800 hover:bg-emerald-200">
            {{ $filters['search'] }}
            <x-tabler-x class="size-3.5" stroke-width="1.5" />
        </a>
    @endif
    @if(($filters['country'] ?? null) || ($filters['search'] ?? null))
        <a href="{{ $baseUrl }}" class="text-sm text-gray-600 hover:text-emerald-700">Сбросить всё</a>
    @endif
</div>
