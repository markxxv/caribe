@php
    $country = $place->region->country;
    $region = $place->region;
    $propertyCount = $properties->total();
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
        <section class="relative isolate overflow-hidden bg-[#153b35] text-white">
            @if($coverUrl)
                <img src="{{ $coverUrl }}" alt="" fetchpriority="high" decoding="async" width="1600" height="900"
                    class="pointer-events-none absolute inset-0 -z-20 h-full w-full object-cover object-center">
            @else
                <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-20 bg-[radial-gradient(ellipse_at_80%_30%,#477361_0%,transparent_55%),linear-gradient(120deg,#173b35,#244b41)]"></div>
            @endif
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-r from-[#0b2721]/95 via-[#0b2721]/75 to-[#0b2721]/30"></div>
            <div class="mx-auto flex min-h-[330px] max-w-[1320px] flex-col justify-end px-4 pb-12 pt-32 sm:min-h-[350px] sm:px-6 sm:pb-14 lg:min-h-[365px] lg:px-8 lg:pt-36">
                <p class="mb-4 text-xs font-medium text-[#d2e1d3]">{{ $country->name }} · {{ $region->name }}</p>
                <h1 class="max-w-[900px] text-[44px] font-normal tracking-[-0.05em] sm:text-[58px] lg:text-[66px] lg:leading-[1.08]">{{ $place->name }}</h1>
                @if(filled($place->meta_title))
                    <p class="mt-4 max-w-[750px] text-sm leading-7 text-white/85 sm:text-base">{{ $place->meta_title }}</p>
                @endif
                <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-white/80">
                    <span>{{ number_format($propertyCount, 0, ',', ' ') }} объектов</span>
                    <a href="#city-properties" class="inline-flex items-center gap-2 font-medium text-[#ecdfaa] transition hover:text-white">
                        Смотреть предложения
                        <x-tabler-arrow-down class="size-4" stroke-width="1.5" />
                    </a>
                </div>
            </div>
        </section>

        <section id="city-properties" class="bg-[#edf1eb] py-14 sm:py-16">
            <div class="mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
                <div class="mb-10 flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <p class="mb-3 text-xs text-[#799082]">Каталог · {{ $place->name }}</p>
                        <h2 class="text-3xl font-normal tracking-tight sm:text-4xl">Недвижимость в городе</h2>
                    </div>
                    <span class="text-sm text-[#7a8b7f]">{{ number_format($propertyCount, 0, ',', ' ') }} объектов</span>
                </div>
                <nav aria-label="Типы недвижимости" class="mb-8 flex flex-wrap gap-2">
                    <a href="{{ $housesUrl }}" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-medium text-[#173b35] transition hover:bg-[#dbe4d9]">
                        Дома и виллы <x-tabler-arrow-up-right class="size-4" stroke-width="1.5" />
                    </a>
                    <a href="{{ $apartmentsUrl }}" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-medium text-[#173b35] transition hover:bg-[#dbe4d9]">
                        Квартиры <x-tabler-arrow-up-right class="size-4" stroke-width="1.5" />
                    </a>
                </nav>
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

        @if($description !== '')
            <section id="city-description" aria-labelledby="city-description-heading" class="mx-auto max-w-[1320px] px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                <div class="max-w-[900px]">
                    <p class="mb-3 text-xs text-[#7c9584]">О городе</p>
                    <h2 id="city-description-heading" class="mb-8 text-3xl font-normal tracking-tight sm:text-4xl">{{ $place->name }}</h2>
                    <div class="text-[15px] leading-8 text-[#465d51] [&_p]:mb-5 [&_h2]:mb-4 [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:font-normal [&_h2]:tracking-tight [&_h2]:text-[#193e35] [&_h3]:mb-3 [&_h3]:mt-8 [&_h3]:text-xl [&_h3]:font-medium [&_h3]:text-[#193e35] [&_ul]:mb-5 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:mb-5 [&_ol]:list-decimal [&_ol]:pl-6 [&_li]:mb-2 [&_a]:font-medium [&_a]:text-[#215c47] [&_a]:underline [&_blockquote]:my-7 [&_blockquote]:border-l-2 [&_blockquote]:border-[#b9c9b6] [&_blockquote]:pl-5">
                        {!! $place->description !!}
                    </div>
                </div>
            </section>
        @endif

        @if($nearbyCities->isNotEmpty())
            <section class="mx-auto max-w-[1320px] px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <p class="mb-3 text-xs text-[#7a9182]">Сначала {{ $region->name }}, затем остальные регионы</p>
                <h2 class="mb-8 text-3xl font-normal tracking-tight sm:text-4xl">Другие города</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($nearbyCities as $nearby)
                        @php
                            $nearbyUrl = $nearby->publicCoverUrl();
                        @endphp
                        <a href="{{ $nearby->publicUrl() }}" class="group relative flex min-h-48 items-end overflow-hidden rounded-[20px] bg-[#2e6353] p-5 text-white">
                            @if($nearbyUrl)
                                <img src="{{ $nearbyUrl }}" alt="" loading="lazy" decoding="async" width="780" height="600" class="absolute inset-0 size-full object-cover transition-transform duration-700 group-hover:scale-105">
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-[#08251d]/90 to-transparent"></div>
                            <div class="relative w-full">
                                <p class="mb-2 text-xs text-white/70">{{ $nearby->region->name }} · {{ $nearby->region->country->name }}</p>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-lg font-medium">{{ $nearby->name }}</span>
                                    <x-tabler-arrow-up-right class="size-4 shrink-0" stroke-width="1.5" />
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</x-layout>
