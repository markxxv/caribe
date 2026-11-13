@props(['block'])

@php
    $types = [
        \App\Models\Tour::class => 'Тур',
        \App\Models\Blog::class => 'Статья',
        \App\Models\News::class => 'Новость',
        \App\Models\PersonalBlog::class => 'История',
        \App\Models\Recipe::class => 'Рецепт',
    ];

    // Keep builder order, ignore deleted or unpublished records.
    $related = collect($block['data']['items'] ?? [])
        ->filter(fn ($item) => is_array($item) && isset($item['model'], $item['record_id']) && isset($types[$item['model']]))
        ->map(function ($item) use ($types) {
            $model = $item['model'];
            $record = $model::query()
                ->whereKey($item['record_id'])
                ->where('active', true)
                ->with('media')
                ->first();

            if (! $record) {
                return null;
            }

            $cover = $record->getFirstMedia($record::mediaPrefix() . '_cover');

            return [
                'title' => $record->title,
                'url' => $record->url,
                'type' => $types[$model],
                'image' => $cover?->getAvailableUrl(['square_card', 'cover']),
            ];
        })
        ->filter()
        ->values();
@endphp

@if($related->isNotEmpty())
    <section data-related-records class="not-prose mx-auto my-12 max-w-3xl sm:my-16" aria-label="{{ $block['data']['title'] ?? 'Материалы по теме' }}">
        @if(filled($block['data']['title'] ?? null))
            <h3 class="mb-5 max-w-3xl text-xl font-medium leading-snug tracking-[-0.025em] text-[#193b30] sm:mb-7 sm:text-[27px]">
                {{ $block['data']['title'] }}
            </h3>
        @endif

        <div class="flex flex-col gap-3 sm:gap-4">
            @foreach($related as $item)
                <a data-related-record-item href="{{ $item['url'] }}"
                    class="group flex min-w-0 items-center gap-4 rounded-2xl p-2 no-underline transition-colors duration-200 hover:bg-[#f3f6f2] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#24664e] sm:gap-5 sm:p-3">
                    <div class="size-24 shrink-0 overflow-hidden rounded-xl bg-[#e8efea] sm:size-28">
                        @if($item['image'])
                            <img src="{{ $item['image'] }}" alt="" loading="lazy" decoding="async"
                                width="112" height="112"
                                class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
                        @else
                            <div class="flex size-full items-center justify-center text-[#83a392]">
                                <x-tabler-photo class="size-9" stroke-width="1.3" />
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <span class="mb-1.5 block text-xs font-normal text-[#678678]">{{ $item['type'] }}</span>
                        <h4 class="line-clamp-3 text-[16px] font-medium leading-[1.35] tracking-[-0.02em] text-[#20392f] transition-colors group-hover:text-[#23714f] sm:text-[17px]">
                            {{ $item['title'] }}
                        </h4>
                    </div>

                    <x-tabler-arrow-up-right class="size-[18px] shrink-0 self-center text-[#9aac9e] transition-all duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-[#23714f]" stroke-width="1.5" />
                </a>
            @endforeach
        </div>
    </section>
@endif
