@php
    $country = $place->region->country;
    $region = $place->region;
    $propertyCount = $properties->total();
    $descriptionText = \Illuminate\Support\Str::squish(strip_tags($place->description ?? ''));
    $cityUrl = $place->publicUrl();
    $paginationUrl = fn (int $number) => $number === 1
        ? $cityUrl
        : route('realestate.city.page', ['country' => $country->slug, 'region' => $region->slug, 'city' => $place->slug, 'page' => $number]);

    $propertyItems = $properties->getCollection()->values()->map(fn ($property, $index) => [
        '@type' => 'ListItem',
        'position' => $properties->firstItem() + $index,
        'url' => route('realestate.show', ['url' => $property->slug]),
        'name' => $property->title,
    ])->all();

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'CollectionPage',
                '@id' => $canonicalUrl . '#webpage',
                'url' => $canonicalUrl,
                'name' => $metaTitle,
                'description' => $metaDescription,
                'inLanguage' => 'ru',
                'about' => [
                    '@type' => 'City',
                    'name' => $place->name,
                    'containedInPlace' => ['@type' => 'AdministrativeArea', 'name' => $region->name],
                ],
                'mainEntity' => ['@id' => $canonicalUrl . '#list'],
            ],
            [
                '@type' => 'ItemList',
                '@id' => $canonicalUrl . '#list',
                'numberOfItems' => count($propertyItems),
                'itemListElement' => $propertyItems,
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Недвижимость', 'item' => route('realestate.index')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $place->name, 'item' => $cityUrl],
                ],
            ],
        ],
    ];

    if ($coverUrl) {
        $structuredData['@graph'][0]['image'] = $coverUrl;
    }
@endphp

@section('metaTitle', $metaTitle)
@section('metaDescription', $metaDescription)
@section('canonicalUrl', $canonicalUrl)
@section('ogType', 'website')
@if($coverUrl)
    @section('ogImage', $coverUrl)
@endif
@if(! $indexable)
    @section('robots', 'noindex, follow')
@else
    @section('structuredData')
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endsection
@endif

<x-layout>
    <main class="min-h-screen bg-[#f5f6f2] text-[#17362f]">
        <section class="overflow-hidden bg-[#153b35] pt-28 text-white sm:pt-32 lg:pt-36">
            <div class="mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
                <nav aria-label="Навигация" class="mb-8 flex flex-wrap items-center gap-2 text-xs text-white/60">
                    <a href="{{ route('home') }}" class="transition hover:text-white">Главная</a>
                    <x-tabler-chevron-right class="size-3.5" stroke-width="1.5" />
                    <a href="{{ route('realestate.index') }}" class="transition hover:text-white">Недвижимость</a>
                    <x-tabler-chevron-right class="size-3.5" stroke-width="1.5" />
                    <span class="text-white">{{ $place->name }}</span>
                </nav>
                <div class="grid items-center gap-9 pb-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.88fr)] lg:gap-16 lg:pb-20">
                    <div class="min-w-0">
                        <p class="mb-5 flex items-center gap-2 text-xs text-[#b4c7bc]">
                            <x-tabler-map-pin class="size-4" stroke-width="1.5" />
                            {{ $country->name }} <span class="text-white/35">/</span> {{ $region->name }}
                        </p>
                        <p class="mb-4 text-xs font-medium uppercase tracking-[0.15em] text-[#d7ca94]">Города · Недвижимость</p>
                        <h1 class="max-w-[670px] text-[43px] font-normal tracking-[-0.05em] sm:text-[64px] lg:text-[76px] lg:leading-[1.07]">{{ $place->name }}</h1>
                        <p class="mt-6 max-w-[540px] text-sm leading-8 text-[#c6d8cc] sm:text-base">
                            {{ $descriptionText !== '' ? \Illuminate\Support\Str::limit($descriptionText, 200) : 'Город, его районы и предложения недвижимости. Изучайте объекты, цены и доступные варианты покупки и аренды.' }}
                        </p>
                        <a href="#city-properties" class="group mt-8 inline-flex items-center gap-3 rounded-full bg-[#d8c995] px-5 py-3 text-sm font-medium text-[#173b35] transition hover:bg-white">
                            Объекты недвижимости
                            <x-tabler-arrow-down class="size-4 transition-transform group-hover:translate-y-0.5" stroke-width="1.6" />
                        </a>
                    </div>
                    <div class="relative aspect-[5/4] min-w-0 overflow-hidden rounded-[24px] bg-[#426858] lg:aspect-[4/4.3]">
                        @if($coverUrl)
                            <img src="{{ $coverUrl }}" alt="{{ $place->name }} — город" fetchpriority="high" decoding="async" width="1400" height="1100" class="absolute inset-0 size-full object-cover">
                        @else
                            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_80%_20%,#91b09a_0%,transparent_45%),linear-gradient(145deg,#658d79,#193f35)]"></div>
                            <x-tabler-map-pin class="absolute left-1/2 top-1/2 size-28 -translate-x-1/2 -translate-y-1/2 text-white/30" stroke-width="0.8" />
                        @endif
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-[#102d27]/80 to-transparent px-6 pb-6 pt-20 sm:px-8">
                            <p class="text-xs text-white/75">Объекты в каталоге</p>
                            <p class="mt-1 flex items-baseline gap-2 text-[34px] font-normal tabular-nums">
                                {{ number_format($propertyCount, 0, ',', ' ') }}
                                <span class="text-sm text-white/75">актуальных предложений</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-[1320px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_310px] lg:gap-20">
                <div class="min-w-0">
                    <p class="mb-3 text-xs text-[#7c9584]">Знакомство с городом</p>
                    <h2 class="mb-8 text-3xl font-normal tracking-tight sm:text-4xl">О городе {{ $place->name }}</h2>
                    @if(filled($place->description))
                        <div class="text-[15px] leading-8 text-[#465d51] [&_p]:mb-5 [&_h2]:mb-4 [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:font-normal [&_h2]:tracking-tight [&_h2]:text-[#193e35] [&_h3]:mb-3 [&_h3]:mt-8 [&_h3]:text-xl [&_h3]:font-medium [&_h3]:text-[#193e35] [&_ul]:mb-5 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:mb-5 [&_ol]:list-decimal [&_ol]:pl-6 [&_li]:mb-2 [&_a]:font-medium [&_a]:text-[#215c47] [&_a]:underline [&_blockquote]:my-7 [&_blockquote]:border-l-2 [&_blockquote]:border-[#b9c9b6] [&_blockquote]:pl-5">
                            {!! $place->description !!}
                        </div>
                    @else
                        <p class="max-w-[630px] text-base leading-8 text-[#63766b]">Информация о городе дополняется. Пока можно ознакомиться с объектами недвижимости и их характеристиками.</p>
                    @endif
                </div>
                <aside aria-label="Недвижимость в городе" class="lg:pt-10">
                    <div class="rounded-[23px] bg-white p-7 lg:sticky lg:top-28">
                        <p class="text-xs text-[#839589]">Недвижимость · {{ $place->name }}</p>
                        <p class="mt-3 text-4xl font-normal tabular-nums text-[#173b35]">{{ number_format($propertyCount, 0, ',', ' ') }}</p>
                        <p class="mt-2 text-sm text-[#73877a]">актуальных объектов</p>
                        <div class="my-6 h-px bg-[#edf0e9]"></div>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between gap-3"><span class="text-[#6e8376]">Продажа</span><span class="font-medium tabular-nums">{{ $saleCount }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-[#6e8376]">Аренда</span><span class="font-medium tabular-nums">{{ $rentCount }}</span></div>
                        </div>
                        <div class="mt-7 grid gap-2.5">
                            <a href="{{ url('/realestate/buy/in/city/' . $locationPath) }}" class="flex items-center justify-between rounded-full bg-[#173b35] px-5 py-3.5 text-sm font-medium text-white transition hover:bg-[#2b5b4d]">
                                Купить недвижимость
                                <x-tabler-arrow-up-right class="size-4" stroke-width="1.5" />
                            </a>
                            <a href="{{ url('/realestate/rent/in/city/' . $locationPath) }}" class="flex items-center justify-between rounded-full bg-[#f0f3ed] px-5 py-3.5 text-sm font-medium text-[#173b35] transition hover:bg-[#e2eae0]">
                                Арендовать
                                <x-tabler-arrow-up-right class="size-4" stroke-width="1.5" />
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section id="city-properties" class="bg-[#edf1eb] py-16 sm:py-20">
            <div class="mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
                <div class="mb-10 flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <p class="mb-3 text-xs text-[#799082]">Каталог · {{ $place->name }}</p>
                        <h2 class="text-3xl font-normal tracking-tight sm:text-4xl">Недвижимость в городе</h2>
                    </div>
                    <span class="text-sm text-[#7a8b7f]">{{ number_format($propertyCount, 0, ',', ' ') }} объектов</span>
                </div>
                @if($properties->isEmpty())
                    <div class="rounded-[23px] bg-white px-6 py-14 text-center">
                        <x-tabler-home-search class="mx-auto size-10 text-[#90a998]" stroke-width="1.3" />
                        <h3 class="mt-5 text-xl font-normal">Пока нет доступных объектов</h3>
                        <p class="mx-auto mt-3 max-w-md text-sm leading-7 text-[#74887b]">Посмотрите предложения в других городах или вернитесь в общий каталог.</p>
                        <a href="{{ route('realestate.index') }}" class="mt-6 inline-flex rounded-full bg-[#173b35] px-6 py-3 text-sm text-white">Смотреть весь каталог</a>
                    </div>
                @else
                    @include('realestate.partials.cards', ['properties' => $properties, 'types' => $types])
                    @if($properties->lastPage() > 1)
                        <nav aria-label="Навигация по страницам" class="mt-14 flex flex-wrap items-center justify-center gap-2">
                            @if($properties->currentPage() > 1)
                                <a href="{{ $paginationUrl($properties->currentPage() - 1) }}" aria-label="Предыдущая страница" class="flex size-11 items-center justify-center rounded-full bg-white text-[#173b35] transition hover:bg-[#e2eae0]"><x-tabler-chevron-left class="size-5" stroke-width="1.5" /></a>
                            @endif
                            @for($number = max(1, $properties->currentPage() - 2); $number <= min($properties->lastPage(), $properties->currentPage() + 2); $number++)
                                <a href="{{ $paginationUrl($number) }}" @if($number === $properties->currentPage()) aria-current="page" @endif class="flex size-11 items-center justify-center rounded-full text-sm font-medium {{ $number === $properties->currentPage() ? 'bg-[#173b35] text-white' : 'bg-white text-[#173b35] transition hover:bg-[#e2eae0]' }}">{{ $number }}</a>
                            @endfor
                            @if($properties->hasMorePages())
                                <a href="{{ $paginationUrl($properties->currentPage() + 1) }}" aria-label="Следующая страница" class="flex size-11 items-center justify-center rounded-full bg-white text-[#173b35] transition hover:bg-[#e2eae0]"><x-tabler-chevron-right class="size-5" stroke-width="1.5" /></a>
                            @endif
                        </nav>
                    @endif
                @endif
            </div>
        </section>

        @if($nearbyCities->isNotEmpty())
            <section class="mx-auto max-w-[1320px] px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <p class="mb-3 text-xs text-[#7a9182]">Рядом с {{ $place->name }}</p>
                <h2 class="mb-8 text-3xl font-normal tracking-tight sm:text-4xl">Другие города региона</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($nearbyCities as $nearby)
                        @php
                            $nearbyCover = $nearby->getFirstMedia('cover');
                            $nearbyUrl = $nearbyCover ? ($nearbyCover->hasGeneratedConversion('cover') ? $nearbyCover->getUrl('cover') : $nearbyCover->getUrl()) : null;
                        @endphp
                        <a href="{{ $nearby->publicUrl() }}" class="group relative flex min-h-48 items-end overflow-hidden rounded-[20px] bg-[#2e6353] p-5 text-white">
                            @if($nearbyUrl)
                                <img src="{{ $nearbyUrl }}" alt="" loading="lazy" decoding="async" width="780" height="600" class="absolute inset-0 size-full object-cover transition-transform duration-700 group-hover:scale-105">
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-[#08251d]/90 to-transparent"></div>
                            <div class="relative flex w-full items-center justify-between gap-3">
                                <span class="text-lg font-medium">{{ $nearby->name }}</span>
                                <x-tabler-arrow-up-right class="size-4" stroke-width="1.5" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</x-layout>
