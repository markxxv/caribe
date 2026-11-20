@section('metaTitle', $item['title'] . ' | Visita Caribe')
@section('metaDescription', $description)
@section('canonicalUrl', $canonical)
@section('structuredData')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection

<x-layout :edit-record="$record">
    <main>
        <header class="bg-[#153b35] pb-12 pt-28 text-white sm:pb-16 sm:pt-36">
            <div class="mx-auto max-w-5xl px-4 md:px-8">
                <nav aria-label="Хлебные крошки" class="mb-8 flex flex-wrap items-center gap-2 text-sm text-white/65">
                    <a href="{{ route('home') }}" class="hover:text-white">Главная</a>
                    <x-tabler-chevron-right class="size-4" />
                    <a href="{{ route('events.index') }}" class="hover:text-white">Афиша</a>
                    <x-tabler-chevron-right class="size-4" />
                    <a href="{{ $record->url }}" class="hover:text-white">{{ $record->title }}</a>
                </nav>
                <div class="mb-5 flex flex-wrap items-center gap-2 text-sm text-[#d7e6c9]">
                    <x-tabler-calendar-event class="size-5" />
                    <time datetime="{{ $details['start_date'] }}">{{ $item['day'] }} {{ $item['month'] }} {{ $item['year'] }}@if($details['start_time']), {{ $details['start_time'] }}@endif</time>
                    @if($details['event_status'] !== 'EventScheduled')
                        <span class="rounded-full bg-white/10 px-3 py-1">{{ \App\Support\Events\EventDetails::STATUSES[$details['event_status']] }}</span>
                    @endif
                </div>
                <h1 class="max-w-4xl text-3xl font-medium tracking-tight sm:text-4xl">{{ $item['title'] }}</h1>
            </div>
        </header>

        <section class="bg-[#f6f7f3] py-8 md:py-16">
            <div class="mx-auto max-w-5xl px-4 md:px-8">
                <article class="space-y-6 rounded-xl bg-white p-5 sm:p-8">
                    @if(filled($item['announcement'] ?? null))
                        <p class="whitespace-pre-line text-base text-[#48584f] sm:text-lg">{{ $item['announcement'] }}</p>
                    @endif
                    @if($details['end_date'])
                        <div class="flex items-start gap-3 text-sm text-[#52645b]">
                            <x-tabler-calendar-event class="size-5 shrink-0" />
                            <div>Окончание: <time datetime="{{ $details['end_date'] }}">{{ \Carbon\CarbonImmutable::parse($details['end_date'])->locale('ru')->isoFormat('D MMMM YYYY') }}@if($details['end_time']), {{ $details['end_time'] }}@endif</time></div>
                        </div>
                    @endif
                    @if(filled($item['location'] ?? null) || $details['street_address'] || $details['city'])
                        <div class="flex items-start gap-3 text-sm text-[#52645b]">
                            <x-tabler-map-pin class="size-5 shrink-0" />
                            <div class="space-y-1">
                                @if($item['map_url'])
                                    <a href="{{ $item['map_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 font-medium text-emerald-800 hover:underline">{{ ($item['location'] ?? null) ?: ($details['street_address'] ?: $details['city']) }} <x-tabler-external-link class="size-4 shrink-0" /></a>
                                @elseif(filled($item['location'] ?? null))
                                    <p class="font-medium">{{ $item['location'] }}</p>
                                @endif
                                <p>{{ implode(', ', array_filter([$details['street_address'] && ! str_contains($item['location'] ?? '', $details['street_address']) ? $details['street_address'] : null, $details['city'], $details['country']])) }}</p>
                            </div>
                        </div>
                    @endif
                    @if($details['performer'])
                        <div class="flex items-start gap-3 text-sm text-[#52645b]"><x-tabler-users class="size-5 shrink-0" /><span>Исполнитель: {{ $details['performer'] }}</span></div>
                    @endif
                    @if($details['price'] !== null || $details['availability'] || $details['ticket_url'])
                        <div class="flex flex-wrap items-center gap-3">
                            @if($details['price'] !== null)
                                <span class="rounded-lg bg-[#e9f0e6] px-4 py-2 font-medium text-[#153b35]">{{ $details['price'] === 0.0 ? 'Бесплатно' : 'От ' . number_format($details['price'], 2, '.', ' ') . ' ' . $details['currency'] }}</span>
                            @endif
                            @if($details['availability'])
                                <span class="text-sm text-[#52645b]">{{ \App\Support\Events\EventDetails::AVAILABILITIES[$details['availability']] }}</span>
                            @endif
                            @if($details['ticket_url'])
                                <a href="{{ $details['ticket_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg bg-[#153b35] px-4 py-2 font-medium text-white hover:bg-[#28584b]">Билеты <x-tabler-external-link class="size-4" /></a>
                            @endif
                        </div>
                    @endif
                </article>
                <a href="{{ $record->url }}" class="mt-8 inline-flex items-center gap-2 rounded-lg bg-[#153b35] px-5 py-3 text-sm font-medium text-white hover:bg-[#28584b]"><x-tabler-arrow-left class="size-4" /> Вся программа</a>
            </div>
        </section>
    </main>
</x-layout>
