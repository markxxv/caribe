@section('metaTitle', 'Страница не найдена — Visita Caribe')
@section('metaDescription', 'Маршрут не найден. Вернитесь на главную Visita Caribe или выберите путешествие по Латинской Америке.')
@section('robots', 'noindex, follow')
@section('ogType', 'website')

@php
    // Four hand-picked tours, then the newest published trips if some are unavailable.
    // A missing database should never turn an HTTP 404 into an HTTP 500.
    $featuredSlugs = [
        'istoricheskiy-siti-tur-po-buenos-ayresu',
        'tur-v-bariloche-s-reyndzherom-4kh4',
        'tur-po-severu-argentiny-khukhuy-tukuman-salta-katamarka',
        'tur-po-argentine-tropicheskaya-selva-vodopady-iguasu-i-iezuitskie-missii',
    ];

    $topTours = collect();

    try {
        $topTours = \App\Models\Tour::query()
            ->where('active', true)
            ->whereIn('slug', $featuredSlugs)
            ->with('media')
            ->get()
            ->sortBy(fn ($tour) => array_search($tour->slug, $featuredSlugs, true))
            ->take(4)
            ->values();

        if ($topTours->count() < 4) {
            $moreTours = \App\Models\Tour::query()
                ->where('active', true)
                ->whereNotIn('id', $topTours->pluck('id')->all())
                ->with('media')
                ->orderByRaw('COALESCE(published_at, created_at) DESC')
                ->limit(4 - $topTours->count())
                ->get();

            $topTours = $topTours->concat($moreTours);
        }
    } catch (\Throwable $exception) {
        $topTours = collect();
    }
@endphp

<x-layout>
    <main class="overflow-hidden bg-[#f6f7f3]">
        <section class="relative isolate overflow-hidden bg-[#102e29] text-white">
            <div aria-hidden="true" class="pointer-events-none absolute -right-28 -top-28 size-[380px] rounded-full bg-[#3d8065]/20 blur-[90px] sm:size-[560px]"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-52 left-[-15%] size-[400px] rounded-full bg-[#7f9e80]/10 blur-[100px]"></div>

            <div class="relative mx-auto grid max-w-[1320px] items-center gap-8 px-5 pb-16 pt-32 sm:px-8 sm:pb-20 sm:pt-36 lg:min-h-[570px] lg:grid-cols-[minmax(0,1fr)_minmax(0,0.8fr)] lg:gap-16 lg:px-10 lg:pb-24 lg:pt-40">
                <div class="relative z-10 max-w-[640px]">
                    <div class="mb-7 inline-flex items-center gap-3 text-sm text-[#dbdcb2] sm:mb-9">
                        <span class="flex size-8 items-center justify-center rounded-full bg-white/10">
                            <x-tabler-route-off class="size-[18px]" stroke-width="1.5" />
                        </span>
                        <span class="tracking-[0.1em]">Ошибка 404</span>
                    </div>

                    <h1 class="max-w-[620px] text-[42px] font-normal tracking-[-0.05em] sm:text-6xl lg:text-[76px] lg:leading-[1.08]">
                        Кажется, мы<br>
                        <span class="text-[#d4dcbb]">сбились с маршрута.</span>
                    </h1>

                    <p class="mt-6 max-w-[420px] text-base leading-7 text-white/65 sm:text-lg sm:leading-8">
                        Такой страницы здесь нет. Но впереди ещё много интересных мест — выберите, куда отправиться дальше.
                    </p>

                    <nav aria-label="Куда перейти" class="mt-9 flex flex-wrap items-center gap-3 sm:mt-10">
                        <a href="{{ route('home') }}" class="inline-flex min-h-12 items-center justify-center gap-3 rounded-full bg-[#e9d998] px-6 text-sm font-medium text-[#17362f] transition-colors hover:bg-[#f4e5b0]">
                            На главную
                            <x-tabler-arrow-up-right class="size-[18px]" stroke-width="1.6" />
                        </a>
                        <a href="{{ route('tours.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-white/10 px-5 text-sm font-medium text-white transition-colors hover:bg-white/20">
                            Туры
                        </a>
                        <a href="{{ route('blog.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-white/10 px-5 text-sm font-medium text-white transition-colors hover:bg-white/20">
                            Блог
                        </a>
                    </nav>
                </div>

                <div aria-hidden="true" class="pointer-events-none relative hidden select-none items-center justify-center lg:flex">
                    <span class="absolute size-[330px] rounded-full bg-[#234e41]/50 blur-sm"></span>
                    <span class="relative text-[270px] font-semibold tracking-[-0.12em] text-white/[0.11] xl:text-[340px]">404</span>
                    <span class="absolute bottom-[16%] right-[14%] flex size-20 rotate-[-18deg] items-center justify-center rounded-full bg-[#e9d998] text-[#183b30] shadow-[0_24px_65px_rgba(0,0,0,0.2)]">
                        <x-tabler-compass class="size-11" stroke-width="1.3" />
                    </span>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-[1320px] px-5 py-14 sm:px-8 sm:py-20 lg:px-10 lg:py-24" aria-labelledby="not-found-tours-title">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4 sm:mb-10">
                <div>
                    <p class="mb-3 text-sm text-[#5a7c68]">Продолжить путешествие</p>
                    <h2 id="not-found-tours-title" class="text-[30px] font-normal tracking-[-0.04em] text-[#163c32] sm:text-[42px]">
                        Возможно, вам сюда
                    </h2>
                    <p class="mt-3 max-w-lg text-sm leading-6 text-[#68776d] sm:text-base">
                        {{ $topTours->count() === 4 ? 'Четыре маршрута, с которых можно начать знакомство с Латинской Америкой.' : 'Подобрали несколько маршрутов, с которых можно начать знакомство с Латинской Америкой.' }}
                    </p>
                </div>

                <a href="{{ route('tours.index') }}" class="group inline-flex items-center gap-2 text-sm font-medium text-[#1d5c48] transition-colors hover:text-[#0b3528]">
                    Все туры
                    <x-tabler-arrow-up-right class="size-[18px] transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" stroke-width="1.5" />
                </a>
            </div>

            @if($topTours->isNotEmpty())
                <div class="grid grid-cols-2 gap-x-3 gap-y-7 sm:gap-6 lg:grid-cols-4 lg:gap-5 xl:gap-7">
                    @foreach($topTours as $tour)
                        <a href="{{ route('tours.show', ['slug' => $tour->slug]) }}" data-404-tour-card
                           class="group min-w-0" aria-label="Открыть тур: {{ $tour->title }}">
                            <div class="relative aspect-square overflow-hidden rounded-xl bg-[#dbe5d8] sm:rounded-2xl">
                                @if($cover = $tour->getFirstMedia('tour_cover'))
                                    <img src="{{ $cover->getAvailableUrl(['square_card']) }}"
                                         alt="{{ $tour->title }}" loading="lazy" decoding="async"
                                         class="size-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.055]">
                                @else
                                    <div class="flex size-full items-center justify-center bg-gradient-to-br from-[#cadccc] to-[#90b3a0]">
                                        <x-tabler-map-2 class="size-12 text-[#426d58]/60" stroke-width="1.15" />
                                    </div>
                                @endif

                                <div class="absolute inset-0 bg-gradient-to-t from-[#132d25]/35 via-transparent to-transparent opacity-70"></div>
                                <span class="absolute bottom-3 right-3 flex size-9 items-center justify-center rounded-full bg-white/95 text-[#183b30] transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5 sm:bottom-4 sm:right-4">
                                    <x-tabler-arrow-up-right class="size-[19px]" stroke-width="1.6" />
                                </span>
                            </div>
                            <div class="pt-3 sm:pt-4">
                                <h3 class="line-clamp-2 text-sm font-medium leading-5 tracking-[-0.015em] text-[#1b3830] transition-colors group-hover:text-[#27805b] sm:text-lg sm:leading-6">
                                    {{ $tour->title }}
                                </h3>
                                <span class="mt-2 inline-flex items-center gap-2 text-xs text-[#638272] sm:text-sm">
                                    Открыть маршрут
                                    <x-tabler-arrow-right class="size-3.5 transition-transform group-hover:translate-x-1" stroke-width="1.5" />
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl bg-white p-8 sm:p-10">
                    <p class="text-base text-[#456353]">В каталоге можно найти экскурсии и авторские маршруты.</p>
                    <a href="{{ route('tours.index') }}" class="mt-5 inline-flex items-center gap-2 text-sm font-medium text-[#1d5c48]">
                        Перейти к турам <x-tabler-arrow-up-right class="size-4" stroke-width="1.5" />
                    </a>
                </div>
            @endif
        </section>
    </main>
</x-layout>
