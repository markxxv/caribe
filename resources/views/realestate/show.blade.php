@section('metaTitle', $realestate->meta_title ?: $realestate->title)
@section('metaDescription', $realestate->meta_description ?: '')

@if($realestate->active)
    @section('canonicalUrl', route('realestate.show', ['url' => $realestate->slug]))
@endif

<x-layout>
    @php
        $type = \App\Filament\Resources\Realestate\Schemas\RealestateForm::propertyTypes()[$realestate->property_type ?? ''] ?? $realestate->property_type;
        $operation = \App\Filament\Resources\Realestate\Schemas\RealestateForm::operationTypes()[$realestate->operation ?? ''] ?? $realestate->operation;
        $location = collect([
            $realestate->district?->name,
            $realestate->city?->name,
            $realestate->region?->name,
            $realestate->country?->name,
        ])->filter()->unique()->join(', ');

        $plainDescription = strip_tags(str_ireplace(
            ['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'],
            "\n",
            $realestate->description ?? ''
        ));
        $description = trim(preg_replace('/\n{3,}/u', "\n\n", str_replace(["\r\n", "\r"], "\n", html_entity_decode($plainDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        $descriptionBlocks = collect(preg_split('/\n\s*\n/u', $description))->map(fn ($block) => trim($block))->filter();

        $galleryImages = $realestate->getMedia('photos');

        if ($galleryImages->isEmpty() && ($cover = $realestate->getFirstMedia('cover'))) {
            $galleryImages = collect([$cover]);
        }

        // Embla needs four slides to loop a two-photo gallery reliably.
        $gallerySlides = $galleryImages->count() === 2
            ? $galleryImages->concat($galleryImages)
            : $galleryImages;

        $specifications = collect([
            'Комнаты' => $realestate->rooms,
            'Спальни' => $realestate->bedrooms,
            'Ванные комнаты' => $realestate->bathrooms,
            'Машиноместа' => $realestate->parking_spaces,
            'Крытая площадь' => $realestate->covered_area !== null ? number_format((float) $realestate->covered_area, 0, ',', ' ') . ' м²' : null,
            'Полукрытая площадь' => $realestate->semi_covered_area !== null ? number_format((float) $realestate->semi_covered_area, 0, ',', ' ') . ' м²' : null,
            'Площадь участка' => $realestate->land_area !== null ? number_format((float) $realestate->land_area, 0, ',', ' ') . ' м²' : null,
            'Год постройки' => $realestate->construction_year,
        ])->filter(fn ($value) => $value !== null && $value !== '');

        $highlights = collect([
            'Спальни' => $realestate->bedrooms,
            'Ванные' => $realestate->bathrooms,
            'Площадь' => $realestate->covered_area !== null ? number_format((float) $realestate->covered_area, 0, ',', ' ') . ' м²' : null,
            'Участок' => $realestate->land_area !== null ? number_format((float) $realestate->land_area, 0, ',', ' ') . ' м²' : null,
        ])->filter(fn ($value) => $value !== null && $value !== '');

        $inquiryUrl = 'https://wa.me/5491137630036?text=' . rawurlencode(
            'Здравствуйте! Меня интересует объект «' . $realestate->title . '»: ' .
            route('realestate.show', ['url' => $realestate->slug])
        );
    @endphp

    <main class="min-h-screen bg-[#f7f8f5] pb-20 pt-32 text-[#17342f] sm:pb-24 md:pt-40">
        <div class="mx-auto max-w-[1260px] px-4 sm:px-6 lg:px-8">
            <nav aria-label="Навигация" class="mb-8 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-[#73817b] sm:mb-10">
                <a href="/" class="transition-colors hover:text-[#17342f]">Главная</a>
                <x-tabler-chevron-right class="size-3.5" stroke-width="1.5" />
                <span class="text-[#17342f]">Недвижимость</span>
            </nav>

            @unless($realestate->active)
                <div class="mb-7 rounded-2xl bg-amber-50 px-5 py-4 text-sm text-amber-900">
                    Предпросмотр — объект пока не опубликован.
                </div>
            @endunless

            <header class="mb-8 max-w-5xl sm:mb-10">
                <div class="mb-4 flex flex-wrap items-center gap-2 text-xs text-[#65776f] sm:text-sm">
                    @if($type)<span>{{ $type }}</span>@endif
                    @if($type && $operation)<span class="text-[#b6c3b9]">/</span>@endif
                    @if($operation)<span>{{ $operation }}</span>@endif
                    @if($realestate->newbuild)
                        <span class="rounded-full bg-[#e8eee7] px-3 py-1 text-xs font-medium text-[#275b49]">Новостройка</span>
                    @endif
                    @if($realestate->sold)
                        <span class="rounded-full bg-[#f2e7dc] px-3 py-1 text-xs font-medium text-[#946442]">Продано</span>
                    @endif
                </div>

                <h1 class="max-w-[950px] text-3xl font-medium tracking-tight text-[#102a27] sm:text-4xl lg:text-[44px] lg:leading-[1.15]">
                    {{ $realestate->title }}
                </h1>

                @if($location || $realestate->address)
                    <div class="mt-4 flex items-start gap-2 text-sm text-[#73817b] sm:text-base">
                        <x-tabler-map-pin class="mt-0.5 size-4 shrink-0" stroke-width="1.6" />
                        <p>
                            @if($location){{ $location }}@endif
                            @if($location && $realestate->address)<span class="mx-1">·</span>@endif
                            @if($realestate->address){{ $realestate->address }}@endif
                        </p>
                    </div>
                @endif
            </header>

            <div class="grid items-start gap-x-8 gap-y-8 lg:grid-cols-[minmax(0,1fr)_340px] xl:gap-x-11">
                <section class="min-w-0" aria-label="Фотографии недвижимости">
                    @if($galleryImages->isNotEmpty())
                        <div data-realestate-gallery>
                            <div class="relative overflow-hidden rounded-2xl bg-[#e5e9e3] sm:rounded-[22px]">
                                <div data-gallery-viewport class="overflow-hidden" aria-roledescription="карусель" aria-label="Фотографии объекта">
                                    <div class="flex touch-pan-y">
                                        @foreach($gallerySlides as $photo)
                                            @php
                                                $photoNumber = ($loop->index % $galleryImages->count()) + 1;
                                                $isDuplicate = $loop->index >= $galleryImages->count();
                                                $isCover = $photo->collection_name === 'cover';
                                                $largeConversion = $isCover ? 'big' : 'photo_large';
                                                $imageUrl = $photo->hasGeneratedConversion($largeConversion)
                                                    ? $photo->getUrl($largeConversion)
                                                    : $photo->getUrl();
                                            @endphp
                                            <figure class="min-w-0 flex-[0_0_100%]" @if($isDuplicate) aria-hidden="true" @endif>
                                                <img src="{{ $imageUrl }}"
                                                    alt="{{ $isDuplicate ? '' : $realestate->title . ' — фото ' . $photoNumber }}"
                                                    class="aspect-[4/3] w-full object-cover sm:aspect-[16/10]"
                                                    width="1600" height="1000"
                                                    @if($loop->first) fetchpriority="high" @else loading="lazy" @endif
                                                    decoding="async">
                                            </figure>
                                        @endforeach
                                    </div>
                                </div>

                                @if($galleryImages->count() > 1)
                                    <button type="button" data-gallery-prev aria-label="Предыдущее фото"
                                        class="absolute left-3 top-1/2 inline-flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-[#102a27] shadow-sm transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#102a27] sm:left-5 sm:size-11">
                                        <x-tabler-chevron-left class="size-5" stroke-width="1.6" />
                                    </button>
                                    <button type="button" data-gallery-next aria-label="Следующее фото"
                                        class="absolute right-3 top-1/2 inline-flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-[#102a27] shadow-sm transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#102a27] sm:right-5 sm:size-11">
                                        <x-tabler-chevron-right class="size-5" stroke-width="1.6" />
                                    </button>
                                    <div class="absolute bottom-4 right-4 rounded-full bg-[#102a27]/85 px-3.5 py-1.5 text-xs font-medium tabular-nums text-white"
                                        aria-live="polite" aria-atomic="true" data-gallery-counter>
                                        1 / {{ $galleryImages->count() }}
                                    </div>
                                @endif
                            </div>

                            @if($galleryImages->count() > 1)
                                <div data-gallery-thumbnails
                                    class="mt-3 flex gap-2.5 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                                    role="group" aria-label="Выбрать фотографию">
                                    @foreach($galleryImages as $photo)
                                        @php
                                            $thumbConversion = $photo->collection_name === 'cover' ? 'thumb' : 'photo_thumb';
                                            $thumbUrl = $photo->hasGeneratedConversion($thumbConversion)
                                                ? $photo->getUrl($thumbConversion)
                                                : $photo->getUrl();
                                        @endphp
                                        <button type="button" data-gallery-thumb="{{ $loop->index }}"
                                            aria-label="Показать фото {{ $loop->iteration }} из {{ $galleryImages->count() }}"
                                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                            class="shrink-0 overflow-hidden rounded-xl transition-opacity duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#102a27] {{ $loop->first ? 'opacity-100' : 'opacity-50' }}">
                                            <img src="{{ $thumbUrl }}" alt="" loading="lazy" decoding="async"
                                                class="h-[68px] w-[96px] object-cover sm:h-[82px] sm:w-[116px]"
                                                width="116" height="82">
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex aspect-[4/3] flex-col items-center justify-center gap-3 rounded-[22px] bg-[#e8ece6] text-[#82938a] sm:aspect-[16/10]">
                            <x-tabler-home class="size-11" stroke-width="1.1" />
                            <p class="text-sm">Фотографии объекта скоро появятся</p>
                        </div>
                    @endif
                </section>

                <aside class="min-w-0 lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:self-stretch" aria-label="Цена и связь">
                    <div class="rounded-[24px] bg-[#102a27] p-6 text-white shadow-[0_18px_45px_rgba(16,42,39,0.12)] sm:p-8 lg:sticky lg:top-28">
                        <p class="text-xs font-medium uppercase tracking-[0.15em] text-[#e2c34d]">Стоимость объекта</p>

                        <p class="mt-3 break-words text-[32px] font-medium tracking-tight sm:text-4xl lg:text-[35px]">
                            @if($realestate->price !== null)
                                {{ number_format((float) $realestate->price, 0, ',', ' ') }}
                                <span class="text-xl font-normal text-white/70">{{ $realestate->currency }}</span>
                            @else
                                <span class="text-2xl">Цена по запросу</span>
                            @endif
                        </p>

                        @if($highlights->isNotEmpty())
                            <dl class="mt-9 grid grid-cols-2 gap-x-4 gap-y-7">
                                @foreach($highlights as $label => $value)
                                    <div>
                                        <dt class="text-xs text-white/55">{{ $label }}</dt>
                                        <dd class="mt-2 text-lg font-medium text-white">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        <a href="{{ $inquiryUrl }}" target="_blank" rel="noopener noreferrer"
                            class="group mt-9 flex min-h-13 items-center justify-center gap-2 rounded-full bg-[#eccd31] px-5 py-3.5 text-center text-sm font-medium text-[#102a27] transition-colors hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#eccd31]">
                            <x-tabler-brand-whatsapp class="size-5 shrink-0" stroke-width="1.7" />
                            {{ $realestate->sold ? 'Уточнить статус объекта' : 'Узнать подробнее' }}
                            <x-tabler-arrow-up-right class="size-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" stroke-width="1.7" />
                        </a>

                        <p class="mt-4 text-center text-xs text-white/55">
                            Ответим на вопросы об объекте и просмотре
                        </p>
                        <a href="tel:+5491137630036" class="mt-5 block text-center text-sm text-white/80 transition-colors hover:text-white">
                            +54 9 11 3763 0036
                        </a>
                    </div>
                </aside>

                <div class="min-w-0 space-y-11 lg:col-start-1 lg:row-start-2 sm:space-y-14">
                    @if($specifications->isNotEmpty())
                        <section aria-labelledby="realestate-specifications" class="rounded-[22px] bg-white p-6 sm:p-8">
                            <h2 id="realestate-specifications" class="text-xl font-medium tracking-tight text-[#102a27] sm:text-2xl">
                                Характеристики
                            </h2>

                            <dl class="mt-7 grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-4">
                                @foreach($specifications as $label => $value)
                                    <div>
                                        <dt class="text-xs text-[#77877e] sm:text-sm">{{ $label }}</dt>
                                        <dd class="mt-2 text-lg font-medium text-[#102a27] sm:text-xl">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endif

                    @if($descriptionBlocks->isNotEmpty())
                        <section aria-labelledby="realestate-description" class="px-1 sm:px-2">
                            <h2 id="realestate-description" class="mb-7 text-2xl font-medium tracking-tight text-[#102a27] sm:mb-8 sm:text-3xl">
                                Об объекте
                            </h2>

                            <div class="space-y-5 text-[15px] leading-7.5 text-[#485a52] sm:text-base sm:leading-8">
                                @foreach($descriptionBlocks as $block)
                                    @if(mb_strlen($block) <= 70 && mb_strtoupper($block) === $block && preg_match('/\p{L}/u', $block))
                                        <h3 class="pt-5 text-base font-medium text-[#102a27] sm:text-lg">
                                            {{ $block }}
                                        </h3>
                                    @elseif(preg_match('/^\s*[-•]\s+/u', $block))
                                        <p class="flex gap-3">
                                            <span class="mt-3 size-1.5 shrink-0 rounded-full bg-[#b69a39]"></span>
                                            <span class="whitespace-pre-line">{{ preg_replace('/^\s*[-•]\s+/u', '', $block) }}</span>
                                        </p>
                                    @else
                                        <p class="whitespace-pre-line">{{ $block }}</p>
                                    @endif
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    </main>
</x-layout>
