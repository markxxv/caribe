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
        $description = strip_tags(str_ireplace(['<br>', '<br/>', '<br />', '</p>'], "\n", $realestate->description ?? ''));
        $galleryImages = $realestate->getMedia('photos');

        if ($galleryImages->isEmpty() && ($cover = $realestate->getFirstMedia('cover'))) {
            $galleryImages = collect([$cover]);
        }
    @endphp

    <main class="min-h-screen bg-white pt-32 pb-20 text-zinc-900 md:pt-40">
        <div class="container mx-auto max-w-5xl px-5 md:px-8">
            <p class="mb-5 text-sm text-zinc-500">
                <a href="/" class="hover:text-zinc-900">Главная</a>
                <span class="mx-2">/</span>
                Недвижимость
            </p>

            @unless($realestate->active)
                <div class="mb-6 rounded-xl bg-amber-50 px-5 py-4 text-sm text-amber-900">
                    Предпросмотр — объект пока не опубликован.
                </div>
            @endunless

            <div class="mb-10">
                <div class="mb-3 flex flex-wrap gap-x-4 gap-y-2 text-sm text-zinc-500">
                    @if($type)<span>{{ $type }}</span>@endif
                    @if($operation)<span>{{ $operation }}</span>@endif
                    @if($location)<span>{{ $location }}</span>@endif
                    @if($realestate->newbuild)<span>Новостройка</span>@endif
                    @if($realestate->sold)<span>Продано</span>@endif
                </div>

                <h1 class="max-w-4xl text-3xl font-semibold tracking-tight md:text-4xl">{{ $realestate->title }}</h1>

                @if($realestate->address)
                    <p class="mt-3 text-base text-zinc-500">{{ $realestate->address }}</p>
                @endif

                @if($realestate->price !== null)
                    <div class="mt-6 text-2xl font-semibold">
                        {{ number_format((float) $realestate->price, 0, ',', ' ') }} {{ $realestate->currency }}
                    </div>
                @endif
            </div>

            @if($galleryImages->isNotEmpty())
                <section class="mb-12 -mx-5 max-w-4xl md:mx-0 md:mb-14" aria-label="Фотографии недвижимости">
                    <div data-realestate-gallery>
                        <div class="relative overflow-hidden bg-zinc-100 md:rounded-xl">
                            <div data-gallery-viewport class="overflow-hidden" aria-roledescription="карусель" aria-label="Фотографии объекта">
                                <div class="flex touch-pan-y">
                                    @foreach($galleryImages as $photo)
                                        @php
                                            $isCover = $photo->collection_name === 'cover';
                                            $largeConversion = $isCover ? 'big' : 'photo_large';
                                            $imageUrl = $photo->hasGeneratedConversion($largeConversion)
                                                ? $photo->getUrl($largeConversion)
                                                : $photo->getUrl();
                                        @endphp
                                        <figure class="min-w-0 flex-[0_0_100%]">
                                            <img src="{{ $imageUrl }}"
                                                alt="{{ $realestate->title }} — фото {{ $loop->iteration }}"
                                                class="aspect-[4/3] w-full object-cover md:aspect-[16/10]"
                                                width="1600" height="1000"
                                                {{ $loop->first ? 'fetchpriority=high' : 'loading=lazy' }}
                                                decoding="async">
                                        </figure>
                                    @endforeach
                                </div>
                            </div>

                            @if($galleryImages->count() > 1)
                                <button type="button" data-gallery-prev aria-label="Предыдущее фото"
                                    class="absolute left-4 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-900 shadow-sm transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900 md:inline-flex">
                                    <x-tabler-chevron-left class="size-5" />
                                </button>
                                <button type="button" data-gallery-next aria-label="Следующее фото"
                                    class="absolute right-4 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-900 shadow-sm transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900 md:inline-flex">
                                    <x-tabler-chevron-right class="size-5" />
                                </button>
                                <div class="absolute bottom-4 right-4 rounded-full bg-zinc-950/70 px-3 py-1.5 text-xs font-medium tabular-nums text-white"
                                    aria-live="polite" aria-atomic="true" data-gallery-counter>
                                    1 / {{ $galleryImages->count() }}
                                </div>
                            @endif
                        </div>

                        @if($galleryImages->count() > 1)
                            <div data-gallery-thumbnails
                                class="mt-3 flex gap-2 overflow-x-auto px-5 pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden md:px-0"
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
                                        class="shrink-0 overflow-hidden rounded-lg transition-opacity duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900 {{ $loop->first ? 'opacity-100' : 'opacity-50' }}">
                                        <img src="{{ $thumbUrl }}" alt="" loading="lazy" decoding="async"
                                            class="h-14 w-[76px] object-cover md:h-16 md:w-24" width="96" height="64">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            <section class="mb-12">
                <h2 class="mb-5 text-xl font-semibold">Характеристики</h2>
                <dl class="grid grid-cols-2 gap-x-8 gap-y-5 text-sm sm:grid-cols-3">
                    @foreach([
                        'Комнаты' => $realestate->rooms,
                        'Спальни' => $realestate->bedrooms,
                        'Ванные' => $realestate->bathrooms,
                        'Машиноместа' => $realestate->parking_spaces,
                        'Крытая площадь' => $realestate->covered_area !== null ? number_format((float) $realestate->covered_area, 0, ',', ' ') . ' м²' : null,
                        'Полукрытая площадь' => $realestate->semi_covered_area !== null ? number_format((float) $realestate->semi_covered_area, 0, ',', ' ') . ' м²' : null,
                        'Участок' => $realestate->land_area !== null ? number_format((float) $realestate->land_area, 0, ',', ' ') . ' м²' : null,
                        'Год постройки' => $realestate->construction_year,
                    ] as $label => $value)
                        @if($value !== null)
                            <div>
                                <dt class="text-zinc-500">{{ $label }}</dt>
                                <dd class="mt-1 font-medium">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </section>

            @if(trim($description) !== '')
                <section class="max-w-3xl">
                    <h2 class="mb-5 text-xl font-semibold">Описание</h2>
                    <div class="whitespace-pre-line text-base text-zinc-700">{{ trim($description) }}</div>
                </section>
            @endif
        </div>
    </main>
</x-layout>
