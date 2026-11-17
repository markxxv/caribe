@section('metaTitle', 'Афиша мероприятий в Латинской Америке | Visita Caribe')
@section('metaDescription', $description)
@section('canonicalUrl', $canonical)
@section('structuredData')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection

<x-layout>
    <main>
        <header class="bg-[#153b35] pb-12 pt-28 text-white sm:pb-16 sm:pt-36">
            <div class="container mx-auto px-4 md:px-8">
                <nav aria-label="Хлебные крошки" class="mb-8 flex items-center gap-2 text-sm text-white/65">
                    <a href="{{ route('home') }}" class="hover:text-white">Главная</a>
                    <x-tabler-chevron-right class="size-4" />
                    <span>Афиша мероприятий</span>
                </nav>
                <div class="grid items-end gap-5 md:grid-cols-[1fr_330px]">
                    <div>
                        <span class="mb-4 inline-flex items-center gap-2 text-sm text-[#c3d6ba]"><x-tabler-calendar-event class="size-5" /> Календарь событий</span>
                        <h1 class="text-4xl font-medium tracking-tight">Куда сходить</h1>
                    </div>
                    <p class="max-w-sm text-base text-white/75">Культурная жизнь Латинской Америки: от небольших концертов до больших городских событий.</p>
                </div>
            </div>
        </header>

        <section class="bg-[#f6f7f3] py-10 md:py-16">
            <div class="container mx-auto px-4 md:px-8">
                <h2 class="mb-6 text-2xl font-medium text-[#153b35]">Подборки мероприятий</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                    @forelse($events as $event)
                        @php($items = $event->chronologicalItems())
                        <article class="group flex flex-col rounded-xl bg-white p-5 transition-shadow hover:shadow-md md:p-7">
                            <div class="mb-6 flex items-center justify-between gap-3 text-sm text-[#52645b]">
                                <span class="inline-flex items-center gap-2"><x-tabler-map-pin class="size-4" /> {{ $event->country?->title ?? 'Афиша' }}</span>
                                <span class="rounded-full bg-[#edf2e9] px-3 py-1 tabular-nums">{{ $items->count() }} {{ trans_choice('событие|события|событий', $items->count(), [], 'ru') }}</span>
                            </div>
                            <h3 class="mb-5 text-2xl font-medium tracking-tight text-[#153b35]"><a href="{{ $event->url }}" class="hover:text-emerald-700">{{ $event->title }}</a></h3>
                            <div class="mb-6 space-y-3">
                                @foreach($items->take(3) as $item)
                                    <div class="flex items-start gap-3 text-sm">
                                        <time datetime="{{ $item['date'] }}" class="w-24 shrink-0 font-medium text-[#69836d]">{{ $item['day'] }} {{ $item['month'] }}</time>
                                        <span class="min-w-0 text-[#37483d]">{{ $item['title'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <a href="{{ $event->url }}" class="mt-auto inline-flex items-center gap-2 self-start rounded-lg bg-[#153b35] px-4 py-3 text-sm font-medium text-white transition hover:bg-[#28584b]">Смотреть афишу <x-tabler-arrow-right class="size-4" /></a>
                        </article>
                    @empty
                        <p class="col-span-full rounded-xl bg-white p-8 text-center text-gray-600">Скоро здесь появятся новые подборки.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </main>
</x-layout>
