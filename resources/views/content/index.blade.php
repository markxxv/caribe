@section('metaTitle', $cfg['metaTitle'])
@section('metaDescription', $cfg['metaDescription'])
@section('ogType', 'website')
@if (! request()->hasAny(['country', 'category', 'search', 'page']))
    @section('canonicalUrl', $indexUrl)
@else
    @section('robots', 'noindex, follow')
@endif

@php
    $withQuery = function (array $q) use ($indexUrl) {
        $q = array_filter($q);
        return $indexUrl . ($q ? '?' . http_build_query($q) : '');
    };
@endphp

<x-layout>
    <!-- Editorial hero -->
    <section class="relative overflow-hidden bg-[#153b35] pb-14 pt-32 text-white sm:pb-16 sm:pt-36 lg:pb-20 lg:pt-40">
        <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-40 size-[500px] rounded-full bg-[#7b9c8d]/15 blur-3xl lg:right-[5%]"></div>
        <div aria-hidden="true" class="pointer-events-none absolute bottom-0 right-0 hidden h-[300px] w-[38%] opacity-20 lg:block"
            style="background: repeating-radial-gradient(circle at 100% 100%, transparent 0 44px, #b6cbbd 45px 46px, transparent 47px 90px)"></div>

        <div class="relative mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
            <nav aria-label="Навигация" class="mb-9 flex items-center gap-2 text-xs text-white/55">
                <a href="/" class="transition hover:text-white">Главная</a>
                <x-tabler-chevron-right class="size-3.5" stroke-width="1.5" />
                <span class="text-white/90">{{ $cfg['title'] }}</span>
            </nav>

            <div class="grid items-end gap-7 lg:grid-cols-[minmax(0,1fr)_290px] lg:gap-12">
                <div class="min-w-0">
                    <p class="mb-5 flex items-center gap-3 text-[11px] font-medium uppercase tracking-[0.2em] text-[#c9d9c6]">
                        <span class="h-px w-9 bg-[#d6c796]"></span>
                        Visita Caribe · Истории
                    </p>
                    <h1 class="max-w-[830px] text-[37px] font-normal tracking-[-0.045em] sm:text-5xl lg:text-[62px] lg:leading-[1.1]">
                        {{ $heading }}
                    </h1>
                </div>

                <div class="lg:pb-1">
                    <p class="max-w-[310px] text-sm leading-7 text-white/65">
                        {{ $cfg['lead'] }}
                    </p>
                    <div class="mt-6 flex items-center gap-3">
                        <span class="text-3xl font-normal tabular-nums text-white">{{ number_format($items->total(), 0, ',', ' ') }}</span>
                        <span class="text-xs leading-5 text-white/50">{{ $cfg['unit'] }}<br>по вашему запросу</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filters Section -->
    <section class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm">
        <div class="container mx-auto px-4 md:px-8">
            <div class="flex items-center gap-4 py-4 overflow-x-auto">
                <span class="text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                    <x-tabler-filter class="w-4 h-4" />
                    Фильтры:
                </span>

                <!-- Country Filter -->
                <form method="GET" action="{{ $indexUrl }}" id="filterForm" class="flex items-center gap-3">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    {{-- <input type="hidden" name="category" value="{{ request('category', 'all') }}"> --}}

@if($countries->isNotEmpty())
                    <div class="flex items-center gap-2 bg-gray-50 rounded-full px-4 py-2">
                        <x-tabler-world class="w-4 h-4 text-gray-600" />
                        <select
                            name="country"
                            onchange="document.getElementById('filterForm').submit()"
                            class="bg-transparent border-none text-sm focus:ring-0 cursor-pointer"
                        >
                            <option value="all">Все страны</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ request('country') == $country->id ? 'selected' : '' }}>
                                    {{ $country->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
@endif

                    {{-- Prepared for future categories
                    <div class="flex items-center gap-2 bg-gray-50 rounded-full px-4 py-2">
                        <x-tabler-category class="w-4 h-4 text-gray-600" />
                        <select
                            name="category"
                            onchange="document.getElementById('filterForm').submit()"
                            class="bg-transparent border-none text-sm focus:ring-0 cursor-pointer"
                        >
                            <option value="all">Все категории</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    --}}
                </form>

                <!-- Active Filters Display -->
                @if(request('country') && request('country') !== 'all')
                    <a href="{{ $withQuery(request()->except('country')) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-100 text-emerald-700 rounded-full text-sm hover:bg-emerald-200 transition-colors">
                        {{ $countries->firstWhere('id', request('country'))->title ?? '' }}
                        <x-tabler-x class="w-3 h-3" />
                    </a>
                @endif

                @if(request('search'))
                    <a href="{{ $withQuery(request()->except('search')) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-100 text-emerald-700 rounded-full text-sm hover:bg-emerald-200 transition-colors">
                        "{{ request('search') }}"
                        <x-tabler-x class="w-3 h-3" />
                    </a>
                @endif

                @if(request()->hasAny(['country', 'search']))
                    <a href="{{ $indexUrl }}"
                       class="text-sm text-gray-600 hover:text-emerald-600 transition-colors whitespace-nowrap">
                        Сбросить всё
                    </a>
                @endif
            </div>
        </div>
    </section>

    <!-- Blog Posts Grid -->
    <section class="py-12 md:py-16">
        <div class="container mx-auto px-4 md:px-8">
            @if($items->count() > 0)
                <!-- Results Count -->
                <div class="mb-8">
                    <p class="text-gray-600">
                        {{ $cfg['found'] }}: <span class="font-medium text-gray-900">{{ $items->total() }}</span>
                    </p>
                </div>

                <!-- Blog Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($items as $item)
                        <article class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300">
                            <!-- Image -->
                            <a href="{{ $item->url }}" class="block relative aspect-[16/9] overflow-hidden bg-gradient-to-br from-emerald-100 to-blue-100">
                                @if($cover = $item->getFirstMedia($item::mediaPrefix() . '_cover'))
                                    <img src="{{ $cover->getAvailableUrl(['big']) }}" alt="{{ $item->title }}" loading="lazy" decoding="async"
                                        class="h-full w-full object-cover object-top">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-emerald-500">
                                        <x-tabler-article class="w-16 h-16 opacity-30" />
                                    </div>
                                @endif

                                <!-- Overlay on hover -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </a>

                            <!-- Content -->
                            <div class="p-6">
                                <!-- Meta Information -->
                                <div class="flex flex-wrap items-center gap-3 mb-4 text-xs text-gray-500">
                                    <time datetime="{{ $item->display_date->format('Y-m-d') }}" class="flex items-center gap-1">
                                        <x-tabler-calendar class="w-3 h-3" />
                                        {{ $item->display_date->translatedFormat('d M Y') }}
                                    </time>

                                    @if($item->countries && $item->countries->count() > 0)
                                        <span class="flex items-center gap-1">
                                            <x-tabler-map-pin class="w-3 h-3" />
                                            {{ $item->countries->first()->title }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Title -->
                                <a href="{{ $item->url }}">
                                    <h2 class="text-xl font-medium text-gray-900 mb-3 line-clamp-2 group-hover:text-emerald-600 transition-colors">
                                        {{ $item->title }}
                                    </h2>
                                </a>

                                <!-- Description -->
                                @if($item->excerpt)
                                    <p class="text-gray-600 text-sm line-clamp-3 mb-4">
                                        {{ $item->excerpt }}
                                    </p>
                                @endif

                                <!-- Read More -->
                                <a href="{{ $item->url }}"
                                   class="inline-flex items-center gap-2 text-emerald-600 hover:gap-3 transition-all duration-300 text-sm font-medium">
                                    Читать далее
                                    <x-tabler-arrow-right class="w-4 h-4" />
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-12">
                    {{ $items->withQueryString()->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-6">
                        <x-tabler-article-off class="w-10 h-10 text-gray-400" />
                    </div>
                    <h3 class="text-2xl font-light text-gray-900 mb-2">{{ $cfg['empty'] }}</h3>
                    <p class="text-gray-600 mb-6">
                        @if(request()->hasAny(['country', 'search']))
                            Попробуйте изменить параметры поиска
                        @else
                            В данный момент нет опубликованных материалов
                        @endif
                    </p>
                    @if(request()->hasAny(['country', 'search']))
                        <a href="{{ $indexUrl }}"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-full hover:bg-emerald-700 transition-colors">
                            Показать все
                            <x-tabler-refresh class="w-4 h-4" />
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </section>


    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Custom select styling */
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            padding-right: 2rem;
        }

        /* Smooth transitions */
        * {
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Hide scrollbar for filter section on mobile */
        .overflow-x-auto::-webkit-scrollbar {
            display: none;
        }

        .overflow-x-auto {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</x-layout>
