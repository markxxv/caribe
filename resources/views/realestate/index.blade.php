@section('metaTitle', $selectedLocation ? 'Недвижимость в ' . $selectedLocation . ' — Visita Caribe' : 'Недвижимость в Латинской Америке — Visita Caribe')
@section('metaDescription', 'Дома, квартиры и участки в Латинской Америке. Подбор недвижимости по местоположению, бюджету и характеристикам.')
@section('canonicalUrl', $canonicalUrl)

<x-layout>
    @php
        $featured = !$hasFilters && $properties->currentPage() === 1 ? $properties->first() : null;
        $cards = $featured ? $properties->getCollection()->skip(1) : $properties->getCollection();
        $locationValue = $state['location_type'] ? $state['location_type'] . '/' . implode('/', $state['location_slugs']) : '';
        $priceLabel = $state['price_min'] !== null || $state['price_max'] !== null
            ? ($state['price_min'] ? 'от ' . number_format($state['price_min'], 0, ',', ' ') : '')
                . ($state['price_min'] && $state['price_max'] ? ' — ' : '')
                . ($state['price_max'] ? 'до ' . number_format($state['price_max'], 0, ',', ' ') : '')
            : ($state['currency'] ? 'Валюта: ' . $state['currency'] : 'Любая цена');
    @endphp

    <main class="min-h-screen bg-[#f5f6f2] text-[#17362f]">
        <section class="relative overflow-hidden bg-[#153b35] pb-28 pt-32 text-white sm:pt-36 lg:pb-32 lg:pt-40">
            <div class="pointer-events-none absolute -right-20 -top-36 size-[500px] rounded-full bg-[#7b9c8d]/15 blur-3xl lg:right-[5%]" aria-hidden="true"></div>
            <div class="pointer-events-none absolute bottom-0 right-0 hidden h-[300px] w-[38%] opacity-20 lg:block" aria-hidden="true"
                style="background: repeating-radial-gradient(circle at 100% 100%, transparent 0 44px, #b6cbbd 45px 46px, transparent 47px 90px)"></div>

            <div class="relative mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
                <nav aria-label="Навигация" class="mb-9 flex items-center gap-2 text-xs text-white/55">
                    <a href="/" class="transition hover:text-white">Главная</a>
                    <x-tabler-chevron-right class="size-3.5" stroke-width="1.5" />
                    <span class="text-white/90">Недвижимость</span>
                </nav>

                <div class="grid items-end gap-7 lg:grid-cols-[minmax(0,1fr)_290px] lg:gap-12">
                    <div>
                        <p class="mb-5 flex items-center gap-3 text-[11px] font-medium uppercase tracking-[0.2em] text-[#c9d9c6]">
                            <span class="h-px w-9 bg-[#d6c796]"></span>
                            Visita Caribe · Real estate
                        </p>
                        <h1 class="max-w-[830px] text-[37px] font-normal tracking-[-0.045em] sm:text-5xl lg:text-[62px] lg:leading-[1.1]">
                            @if($selectedLocation)
                                Недвижимость в <span class="text-[#c9d9c6]">{{ $selectedLocation }}</span>
                            @else
                                Найдите своё место <span class="text-[#c9d9c6]">под солнцем.</span>
                            @endif
                        </h1>
                    </div>
                    <div class="lg:pb-1">
                        <p class="max-w-[310px] text-sm leading-7 text-white/65">
                            Дома, квартиры и пространства для новой главы жизни. Исследуйте предложения в Латинской Америке.
                        </p>
                        <div class="mt-6 flex items-center gap-3">
                            <span class="text-3xl font-normal tabular-nums text-white">{{ number_format($properties->total(), 0, ',', ' ') }}</span>
                            <span class="text-xs leading-5 text-white/50">объектов<br>по вашему запросу</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="catalog-search" aria-label="Поиск недвижимости" class="relative z-[80] mx-auto -mt-16 max-w-[1320px] lg:z-30" px-4 sm:px-6 lg:px-8">
            <button type="button" data-catalog-open class="flex min-h-14 w-full items-center justify-between gap-4 rounded-2xl bg-white px-5 text-sm font-medium text-[#17362f] shadow-[0_12px_40px_rgba(19,46,38,0.1)] lg:hidden">
                <span class="flex items-center gap-3"><x-tabler-adjustments-horizontal class="size-5" stroke-width="1.6" /> Подобрать недвижимость</span>
                <x-tabler-arrow-right class="size-5" stroke-width="1.6" />
            </button>

            <form data-catalog-search data-location-value="{{ $locationValue }}" action="{{ route('realestate.index') }}" method="get"
                class="fixed inset-0 z-[120] hidden flex-col bg-[#f5f6f2] text-[#17362f] lg:static lg:flex lg:rounded-[23px] lg:bg-white lg:p-2 lg:shadow-[0_20px_65px_rgba(10,37,30,0.12)]">
                <div class="flex shrink-0 items-center justify-between bg-white px-5 py-5 lg:hidden">
                    <span class="text-lg font-medium">Параметры поиска</span>
                    <button type="button" data-catalog-close aria-label="Закрыть фильтры" class="flex size-10 items-center justify-center rounded-full bg-[#f1f3ef]"><x-tabler-x class="size-5" stroke-width="1.6" /></button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-5 pt-4 lg:overflow-visible lg:p-0">
                    <div class="grid gap-2 lg:grid-cols-[190px_minmax(190px,1.55fr)_minmax(130px,1fr)_minmax(150px,1fr)_128px]">
                        <fieldset class="flex min-w-0 flex-col justify-center rounded-xl bg-white px-4 py-3 lg:bg-[#f1f3ef]">
                            <legend class="sr-only">Операция</legend>
                            <p class="mb-2 text-[11px] text-[#829189]">Операция</p>
                            <div class="grid grid-cols-3 gap-1 rounded-lg bg-[#e7ebe6] p-1">
                                @foreach(['' => 'Все', 'buy' => 'Купить', 'rent' => 'Снять'] as $value => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="catalog-deal" value="{{ $value }}" data-deal class="peer sr-only" @checked(($state['deal'] ?? '') === $value)>
                                        <span class="flex h-9 items-center justify-center rounded-md text-xs font-medium text-[#63786d] transition peer-checked:bg-white peer-checked:text-[#17362f] peer-checked:shadow-sm peer-focus-visible:outline-2 peer-focus-visible:outline-[#17362f]">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="relative min-w-0 rounded-xl bg-white px-4 py-3 lg:bg-[#f1f3ef]" data-location-wrap>
                            <label for="catalog-location" class="mb-1.5 block text-[11px] text-[#829189]">Страна, регион или город</label>
                            <div class="flex items-center gap-2">
                                <x-tabler-map-pin class="size-[18px] shrink-0 text-[#71877d]" stroke-width="1.5" />
                                <input id="catalog-location" data-location-input type="search" value="{{ $selectedLocation }}" placeholder="Выберите местоположение" autocomplete="off"
                                    class="h-9 w-full min-w-0 bg-transparent text-sm text-[#17362f] outline-none placeholder:text-[#7d8c83]" aria-label="Местоположение" aria-expanded="false" aria-controls="catalog-location-options">
                            </div>
                            <div id="catalog-location-options" data-location-options class="relative left-0 right-0 top-full z-50 mt-2 hidden max-h-[340px] lg:absolute" overflow-y-auto rounded-xl bg-white p-2 shadow-[0_18px_55px_rgba(10,37,30,0.2)] lg:min-w-[330px]">
                                <button type="button" data-location-option data-value="" data-search="любое место" class="flex w-full items-center rounded-lg px-3 py-3 text-left text-sm text-[#17362f] hover:bg-[#f1f3ef]">Любое местоположение</button>
                                @foreach($locations as $place)
                                    <button type="button" data-location-option data-value="{{ $place['value'] }}" data-search="{{ $place['label'] }} {{ $place['context'] }}"
                                        class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-[#f1f3ef]">
                                        <span class="min-w-0 truncate text-sm text-[#203e36]">{{ $place['label'] }}</span>
                                        <span class="shrink-0 text-[10px] text-[#9aa79d]">{{ $place['context'] }}</span>
                                    </button>
                                @endforeach
                                <p data-location-empty class="hidden px-3 py-5 text-sm text-[#7c8b81]">Ничего не найдено</p>
                            </div>
                        </div>

                        <label class="flex min-w-0 flex-col justify-center rounded-xl bg-white px-4 py-3 lg:bg-[#f1f3ef]">
                            <span class="mb-1.5 text-[11px] text-[#829189]">Тип недвижимости</span>
                            <select data-field="type" class="h-9 w-full min-w-0 cursor-pointer bg-transparent text-sm text-[#17362f] outline-none">
                                <option value="">Любой тип</option>
                                @foreach($types as $value => $label)
                                    <option value="{{ $value }}" @selected($state['type'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="relative min-w-0 rounded-xl bg-white lg:bg-[#f1f3ef]" data-price-wrap>
                            <button type="button" data-price-toggle aria-expanded="false" class="flex min-h-[78px] w-full items-center justify-between gap-2 px-4 py-3 text-left">
                                <span class="min-w-0">
                                    <span class="mb-1.5 block text-[11px] text-[#829189]">Бюджет</span>
                                    <span data-price-label class="block truncate text-sm text-[#17362f]">{{ $priceLabel }}</span>
                                </span>
                                <x-tabler-chevron-down class="size-4 shrink-0 text-[#71877d]" stroke-width="1.5" />
                            </button>
                            <div data-price-panel class="relative left-0 right-0 top-full z-40 mt-2 hidden rounded-xl lg:absolute" bg-white p-4 shadow-[0_18px_55px_rgba(10,37,30,0.18)] lg:min-w-[310px]">
                                <label class="block text-xs text-[#6c8277]" for="catalog-currency">Валюта</label>
                                <select id="catalog-currency" data-field="currency" class="mt-1.5 h-11 w-full rounded-lg bg-[#f1f3ef] px-3 text-sm outline-none">
                                    <option value="">Все валюты</option>
                                    @foreach($currencies as $currency)
                                        <option value="{{ $currency }}" @selected($state['currency'] === $currency)>{{ $currency }}</option>
                                    @endforeach
                                </select>
                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <label class="text-xs text-[#6c8277]">Цена от
                                        <input data-field="price-min" type="number" min="1" step="1" inputmode="numeric" value="{{ $state['price_min'] }}" placeholder="От"
                                            class="mt-1.5 h-11 w-full min-w-0 rounded-lg bg-[#f1f3ef] px-3 text-sm text-[#17362f] outline-none">
                                    </label>
                                    <label class="text-xs text-[#6c8277]">Цена до
                                        <input data-field="price-max" type="number" min="1" step="1" inputmode="numeric" value="{{ $state['price_max'] }}" placeholder="До"
                                            class="mt-1.5 h-11 w-full min-w-0 rounded-lg bg-[#f1f3ef] px-3 text-sm text-[#17362f] outline-none">
                                    </label>
                                </div>
                                <p class="mt-3 text-xs leading-5 text-[#91a097]">Цены сравниваются только в выбранной валюте.</p>
                                <button type="button" data-price-apply class="mt-4 h-10 w-full rounded-lg bg-[#173b35] text-xs font-medium text-white">Применить цену</button>
                            </div>
                        </div>

                        <button type="submit" class="hidden min-h-[78px] items-center justify-center gap-2 rounded-xl bg-[#d9c998] px-4 text-sm font-medium text-[#153b35] transition hover:bg-[#e8dcaf] lg:flex">
                            <x-tabler-search class="size-5" stroke-width="1.7" /> Найти
                        </button>
                    </div>

                    <div class="flex items-center justify-between gap-4 px-1 pt-4 lg:px-3 lg:pb-2">
                        <button type="button" data-advanced-toggle aria-expanded="false" class="inline-flex items-center gap-2 text-xs font-medium text-[#4a6d5c] transition hover:text-[#153b35]">
                            <x-tabler-adjustments-horizontal class="size-4" stroke-width="1.5" /> Расширенный поиск
                            <x-tabler-chevron-down data-advanced-chevron class="size-4 transition-transform" stroke-width="1.5" />
                        </button>
                        <a href="{{ route('realestate.index') }}" class="text-xs text-[#8b9a90] transition hover:text-[#17362f]">Сбросить фильтры</a>
                    </div>

                    <div data-advanced class="mt-4 hidden rounded-2xl bg-[#f1f3ee] p-4 sm:p-6">
                        <div class="grid gap-x-6 gap-y-6 sm:grid-cols-2 lg:grid-cols-4">
                            <label class="block">
                                <span class="mb-2 block text-xs text-[#5a7165]">Спальни, от</span>
                                <select data-field="beds" class="h-12 w-full rounded-lg bg-white px-3 text-sm outline-none">
                                    <option value="">Любое количество</option>
                                    @for($n = 1; $n <= 6; $n++)
                                        <option value="{{ $n }}" @selected($state['beds'] === $n)>{{ $n }}+</option>
                                    @endfor
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-xs text-[#5a7165]">Ванные, от</span>
                                <select data-field="baths" class="h-12 w-full rounded-lg bg-white px-3 text-sm outline-none">
                                    <option value="">Любое количество</option>
                                    @for($n = 1; $n <= 6; $n++)
                                        <option value="{{ $n }}" @selected($state['baths'] === $n)>{{ $n }}+</option>
                                    @endfor
                                </select>
                            </label>
                            <div>
                                <span class="mb-2 block text-xs text-[#5a7165]">Крытая площадь, м²</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <input data-field="area-min" type="number" min="1" step="1" inputmode="numeric" value="{{ $state['area_min'] }}" placeholder="От" aria-label="Площадь от" class="h-12 w-full min-w-0 rounded-lg bg-white px-3 text-sm outline-none">
                                    <input data-field="area-max" type="number" min="1" step="1" inputmode="numeric" value="{{ $state['area_max'] }}" placeholder="До" aria-label="Площадь до" class="h-12 w-full min-w-0 rounded-lg bg-white px-3 text-sm outline-none">
                                </div>
                            </div>
                            <label class="block">
                                <span class="mb-2 block text-xs text-[#5a7165]">Предложение</span>
                                <select data-field="offer" class="h-12 w-full rounded-lg bg-white px-3 text-sm outline-none">
                                    <option value="">Все объекты</option>
                                    <option value="newbuild" @selected($state['offer'] === 'newbuild')>Новостройки</option>
                                    <option value="resale" @selected($state['offer'] === 'resale')>Вторичная недвижимость</option>
                                </select>
                            </label>
                        </div>
                        <div class="mt-6 max-w-xs">
                            <label class="block">
                                <span class="mb-2 block text-xs text-[#5a7165]">Номер объекта</span>
                                <input data-field="ref" type="number" min="1" step="1" inputmode="numeric" value="{{ $state['ref'] }}" placeholder="ID объекта" class="h-12 w-full rounded-lg bg-white px-3 text-sm outline-none">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 bg-white px-4 pb-[calc(1rem+env(safe-area-inset-bottom))] pt-3 lg:hidden">
                    <button type="submit" class="flex h-13 w-full items-center justify-center gap-2 rounded-xl bg-[#173b35] text-sm font-medium text-white">
                        <x-tabler-search class="size-4.5" stroke-width="1.6" /> Показать недвижимость
                    </button>
                </div>
            </form>
        </section>

        <section class="mx-auto max-w-[1320px] px-4 pb-24 pt-12 sm:px-6 lg:px-8 lg:pt-16">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-5">
                <div>
                    <p class="mb-2 text-[11px] font-medium uppercase tracking-[0.18em] text-[#809487]">Подборка Visita Caribe</p>
                    <h2 class="text-[27px] font-normal tracking-[-0.035em] sm:text-[35px]">
                        {{ $hasFilters ? 'Результаты поиска' : 'Актуальные предложения' }}
                        <span class="ml-1 align-top text-sm font-normal text-[#93a098]">{{ number_format($properties->total(), 0, ',', ' ') }}</span>
                    </h2>
                </div>
                <label class="flex items-center gap-2 text-xs text-[#74877b]">
                    Сортировать
                    <select data-catalog-sort class="cursor-pointer rounded-lg bg-white px-3 py-2.5 text-xs font-medium text-[#214238] outline-none">
                        <option value="recent" @selected($state['sort'] === 'recent')>Сначала новые</option>
                        <option value="price-low" @selected($state['sort'] === 'price-low')>Сначала дешевле</option>
                        <option value="price-high" @selected($state['sort'] === 'price-high')>Сначала дороже</option>
                    </select>
                </label>
            </div>

            @if($properties->isEmpty())
                <div class="flex min-h-[320px] flex-col items-center justify-center rounded-[24px] bg-white px-6 py-14 text-center">
                    <x-tabler-home-search class="size-10 text-[#96aa9a]" stroke-width="1.1" />
                    <h3 class="mt-6 text-2xl font-normal tracking-tight">По этим условиям пока нет объектов</h3>
                    <p class="mt-3 max-w-sm text-sm leading-7 text-[#7a8c7f]">Попробуйте другой регион, тип недвижимости или расширьте диапазон цен.</p>
                    <a href="{{ route('realestate.index') }}" class="mt-6 rounded-full bg-[#173b35] px-6 py-3 text-xs font-medium text-white transition hover:bg-[#2b5b4d]">Посмотреть весь каталог</a>
                </div>
            @else
                @if($featured)
                    @php
                        $leadMedia = $featured->getFirstMedia('photos') ?: $featured->getFirstMedia('cover');
                        $leadConversion = $leadMedia?->collection_name === 'cover' ? 'big' : 'photo_large';
                        $leadImage = $leadMedia ? ($leadMedia->hasGeneratedConversion($leadConversion) ? $leadMedia->getUrl($leadConversion) : $leadMedia->getUrl()) : null;
                        $leadLocation = $featured->city?->name ?? $featured->region?->name ?? $featured->country?->name;
                    @endphp
                    <a href="{{ route('realestate.show', ['url' => $featured->slug]) }}"
                        class="group mb-7 grid min-h-[340px] overflow-hidden rounded-[23px] bg-[#e3eae2] lg:grid-cols-[1.35fr_1fr]">
                        <div class="relative min-h-[270px] overflow-hidden bg-[#aabbb0] lg:min-h-[390px]">
                            @if($leadImage)
                                <img src="{{ $leadImage }}" alt="{{ $featured->title }}" width="1200" height="800" fetchpriority="high" decoding="async" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.035]">
                            @else
                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-[linear-gradient(145deg,#aac1b1,#d8e2d3)] text-[#537064]">
                                    <x-tabler-building-estate class="size-16" stroke-width="1" />
                                    <span class="text-xs">Фото объекта готовится</span>
                                </div>
                            @endif
                            <span class="absolute left-5 top-5 rounded-full bg-white/95 px-4 py-2 text-[11px] font-medium text-[#224237]">Выбор редакции</span>
                        </div>
                        <div class="flex flex-col justify-between p-6 sm:p-9 lg:p-11">
                            <div>
                                <p class="mb-5 flex items-center gap-2 text-xs text-[#6c8575]">
                                    <x-tabler-map-pin class="size-4" stroke-width="1.5" /> {{ $leadLocation ?: 'Латинская Америка' }}
                                </p>
                                <p class="mb-3 text-[11px] font-medium uppercase tracking-[0.15em] text-[#799183]">
                                    {{ $types[$featured->property_type ?? ''] ?? 'Недвижимость' }}
                                    @if($featured->newbuild) · Новостройка @endif
                                </p>
                                <h3 class="max-w-[390px] text-[27px] font-normal leading-[1.2] tracking-[-0.035em] text-[#183c32] sm:text-4xl">{{ $featured->title }}</h3>
                                <div class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-xs text-[#6a8073]">
                                    @if($featured->bedrooms !== null)<span>{{ $featured->bedrooms }} спален</span>@endif
                                    @if($featured->bathrooms !== null)<span>{{ $featured->bathrooms }} ванных</span>@endif
                                    @if($featured->covered_area !== null)<span>{{ number_format((float) $featured->covered_area, 0, ',', ' ') }} м²</span>@endif
                                </div>
                            </div>
                            <div class="mt-10 flex items-end justify-between gap-3">
                                <div>
                                    <p class="mb-1 text-[11px] text-[#83968a]">Стоимость</p>
                                    <p class="text-2xl font-medium tracking-tight text-[#183c32]">
                                        @if($featured->price !== null)
                                            {{ number_format((float) $featured->price, 0, ',', ' ') }} <span class="text-base font-normal text-[#829789]">{{ $featured->currency }}</span>
                                        @else
                                            <span class="text-lg">По запросу</span>
                                        @endif
                                    </p>
                                </div>
                                <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#173b35] text-white transition group-hover:bg-[#2d6753]">
                                    <x-tabler-arrow-up-right class="size-5" stroke-width="1.5" />
                                </span>
                            </div>
                        </div>
                    </a>
                @endif

                <div class="grid gap-x-6 gap-y-9 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($cards as $property)
                        @php
                            $media = $property->getFirstMedia('photos') ?: $property->getFirstMedia('cover');
                            $conversion = $media?->collection_name === 'cover' ? 'cover' : 'photo_thumb';
                            $image = $media ? ($media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl()) : null;
                            $placeName = $property->city?->name ?? $property->region?->name ?? $property->country?->name;
                        @endphp
                        <article class="group min-w-0">
                            <a href="{{ route('realestate.show', ['url' => $property->slug]) }}" class="block">
                                <div class="relative aspect-[1.3] overflow-hidden rounded-[18px] bg-[#dce7dd]">
                                    @if($image)
                                        <img src="{{ $image }}" alt="{{ $property->title }}" loading="lazy" decoding="async" width="680" height="520" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.045]">
                                    @else
                                        <div class="flex h-full flex-col items-center justify-center gap-3 bg-[linear-gradient(145deg,#c1d0c3,#e8ede3)] text-[#7c9b85]">
                                            <x-tabler-building-estate class="size-12" stroke-width="1" />
                                            <span class="text-xs">Фото объекта готовится</span>
                                        </div>
                                    @endif
                                    <div class="absolute left-3.5 top-3.5 flex flex-wrap gap-1.5">
                                        @if($property->newbuild)
                                            <span class="rounded-full bg-[#d9c998] px-3 py-1.5 text-[10px] font-medium text-[#193b32]">Новостройка</span>
                                        @endif
                                        @if($property->operation === 'rent')
                                            <span class="rounded-full bg-white/95 px-3 py-1.5 text-[10px] font-medium text-[#193b32]">Аренда</span>
                                        @endif
                                    </div>
                                    <span class="absolute bottom-3.5 right-3.5 flex size-10 items-center justify-center rounded-full bg-white text-[#214438] opacity-0 transition-all group-hover:opacity-100 group-focus-within:opacity-100">
                                        <x-tabler-arrow-up-right class="size-5" stroke-width="1.5" />
                                    </span>
                                </div>
                                <div class="px-1 pt-4">
                                    <div class="mb-2.5 flex items-center justify-between gap-3 text-xs text-[#839489]">
                                        <span class="truncate">{{ $placeName ?: 'Латинская Америка' }}</span>
                                        <span class="shrink-0">{{ $types[$property->property_type ?? ''] ?? 'Недвижимость' }}</span>
                                    </div>
                                    <h3 class="line-clamp-2 min-h-[48px] text-lg font-normal leading-[1.35] tracking-[-0.02em] text-[#173b35] transition-colors group-hover:text-[#47745a]">{{ $property->title }}</h3>
                                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-[#829487]">
                                        @if($property->bedrooms !== null)<span>{{ $property->bedrooms }} сп.</span>@endif
                                        @if($property->bathrooms !== null)<span>{{ $property->bathrooms }} ван.</span>@endif
                                        @if($property->covered_area !== null)<span>{{ number_format((float) $property->covered_area, 0, ',', ' ') }} м²</span>@endif
                                        @if($property->land_area !== null)<span>Участок {{ number_format((float) $property->land_area, 0, ',', ' ') }} м²</span>@endif
                                    </div>
                                    <p class="mt-4 text-xl font-medium tracking-tight text-[#173b35]">
                                        @if($property->price !== null)
                                            {{ number_format((float) $property->price, 0, ',', ' ') }} <span class="text-sm font-normal text-[#819489]">{{ $property->currency }}</span>
                                        @else
                                            <span class="text-base">Цена по запросу</span>
                                        @endif
                                    </p>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>

                @if($properties->lastPage() > 1)
                    <nav aria-label="Навигация по страницам" class="mt-16 flex flex-wrap items-center justify-center gap-2">
                        @if($properties->currentPage() > 1)
                            <a href="{{ \App\Support\Realestate\CatalogFilters::url(array_replace($state, ['page' => $properties->currentPage() - 1])) }}" aria-label="Предыдущая страница" class="flex size-11 items-center justify-center rounded-full bg-white text-[#173b35] transition hover:bg-[#e3eae2]">
                                <x-tabler-chevron-left class="size-5" stroke-width="1.5" />
                            </a>
                        @endif
                        @for($number = max(1, $properties->currentPage() - 2); $number <= min($properties->lastPage(), $properties->currentPage() + 2); $number++)
                            <a href="{{ \App\Support\Realestate\CatalogFilters::url(array_replace($state, ['page' => $number])) }}"
                                @if($number === $properties->currentPage()) aria-current="page" @endif
                                class="flex size-11 items-center justify-center rounded-full text-sm font-medium {{ $number === $properties->currentPage() ? 'bg-[#173b35] text-white' : 'bg-white text-[#173b35] transition hover:bg-[#e3eae2]' }}">{{ $number }}</a>
                        @endfor
                        @if($properties->hasMorePages())
                            <a href="{{ \App\Support\Realestate\CatalogFilters::url(array_replace($state, ['page' => $properties->currentPage() + 1])) }}" aria-label="Следующая страница" class="flex size-11 items-center justify-center rounded-full bg-white text-[#173b35] transition hover:bg-[#e3eae2]">
                                <x-tabler-chevron-right class="size-5" stroke-width="1.5" />
                            </a>
                        @endif
                    </nav>
                @endif
            @endif
        </section>
    </main>
</x-layout>
