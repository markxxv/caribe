@section('metaTitle', $record->title . ' | Visita Caribe')
@section('metaDescription', $description)
@section('canonicalUrl', $canonical)
@section('structuredData')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection

<x-layout :edit-record="$record">
    <main>
        <header class="bg-[#153b35] pb-12 pt-28 text-white sm:pb-16 sm:pt-36">
            <div class="container mx-auto px-4 md:px-8">
                <nav aria-label="Хлебные крошки" class="mb-8 flex flex-wrap items-center gap-2 text-sm text-white/65">
                    <a href="{{ route('home') }}" class="hover:text-white">Главная</a>
                    <x-tabler-chevron-right class="size-4" />
                    <a href="{{ route('events.index') }}" class="hover:text-white">Афиша мероприятий</a>
                </nav>
                @if($record->country)
                    <span class="mb-5 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm text-[#d7e6c9]"><x-tabler-map-pin class="size-4" /> {{ $record->country->title }}</span>
                @endif
                <h1 class="max-w-4xl text-3xl font-medium tracking-tight sm:text-4xl">{{ $record->title }}</h1>
            </div>
        </header>

        <section aria-label="Календарь мероприятий" class="bg-[#f6f7f3] py-8 md:py-16">
            <div class="mx-auto max-w-5xl px-4 md:px-8">
                <div class="mb-5 flex items-center justify-between gap-3 text-sm text-[#52645b]">
                    <h2 class="font-medium text-[#153b35]">Программа событий</h2>
                    <span>{{ $items->count() }} {{ trans_choice('событие|события|событий', $items->count(), [], 'ru') }}</span>
                </div>
                <div class="space-y-3 sm:space-y-4">
                    @forelse($items as $item)
                        <article id="event-{{ $loop->iteration }}" class="grid scroll-mt-24 grid-cols-[86px_minmax(0,1fr)] items-start gap-3 rounded-xl bg-white p-3 sm:grid-cols-[130px_minmax(0,1fr)] sm:gap-6 sm:p-6 lg:gap-8">
                            <time datetime="{{ $item['date'] }}" aria-label="{{ $item['day'] }} {{ $item['month'] }} {{ $item['year'] }}" class="flex flex-col items-center rounded-lg bg-[#e9f0e6] px-1 py-4 text-center text-[#153b35] sm:py-6">
                                <span class="text-4xl font-medium tabular-nums tracking-tight sm:text-6xl">{{ $item['day'] }}</span>
                                <span class="mt-1 text-xs font-medium sm:text-sm">{{ $item['month'] }}</span>
                            </time>
                            <div class="min-w-0 py-1 sm:py-2">
                                <h3 class="mb-2 text-lg font-medium tracking-tight text-[#153b35] sm:text-2xl">{{ $item['title'] }}</h3>
                                @if(filled($item['announcement'] ?? null))
                                    <p class="whitespace-pre-line text-sm text-[#48584f] sm:text-base">{{ $item['announcement'] }}</p>
                                @endif
                                @if(filled($item['location'] ?? null))
                                    <div class="mt-4 text-sm text-[#52645b]">
                                        @if($item['map_url'])
                                            <a href="{{ $item['map_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex max-w-full items-start gap-2 font-medium text-emerald-800 hover:underline"><x-tabler-map-pin class="size-5 shrink-0" /><span>{{ $item['location'] }}</span><x-tabler-external-link class="size-4 shrink-0" /></a>
                                        @else
                                            <span class="inline-flex max-w-full items-start gap-2"><x-tabler-map-pin class="size-5 shrink-0" /><span>{{ $item['location'] }}</span></span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="rounded-xl bg-white p-8 text-center text-gray-600">Мероприятия скоро появятся в афише.</p>
                    @endforelse
                </div>
                <a href="{{ route('events.index') }}" class="mt-8 inline-flex items-center gap-2 rounded-lg bg-[#153b35] px-5 py-3 text-sm font-medium text-white hover:bg-[#28584b]"><x-tabler-arrow-left class="size-4" /> Все подборки</a>
            </div>
        </section>
    </main>
</x-layout>
