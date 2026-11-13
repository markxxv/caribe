@props(['record', 'block'])

@php
    $ids = array_values(array_filter((array) ($block['data']['images'] ?? [])));

    // Preserve the exact order chosen in the builder, not Media Library upload order.
    $available = $record->getMedia($record::mediaPrefix() . '_builder_gallery')->keyBy('id');
    $images = collect($ids)->map(fn ($id) => $available->get($id))->filter()->values();

    $columns = $block['data']['columns'] ?? 'columns-2';
    $gridClass = match ($columns) {
        'columns-3' => 'sm:grid-cols-2 lg:grid-cols-3',
        'columns-4' => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2',
    };

    // Old Visita Caribe editorial layout: two portraits per row, with an
    // unpaired final picture displayed wide across both columns.
    $isTwoColumnGallery = ! in_array($columns, ['columns-3', 'columns-4'], true);
    $wideLast = $isTwoColumnGallery && $images->count() % 2 === 1;
@endphp

@if($images->isNotEmpty())
    <div data-editorial-gallery class="not-prose mx-auto my-12 max-w-5xl">
        <div class="grid grid-cols-1 gap-3 {{ $gridClass }}">
            @foreach($images as $image)
                <figure class="m-0 min-w-0 overflow-hidden {{ $wideLast && $loop->last ? 'sm:col-span-2' : '' }}">
                    <img src="{{ $image->getAvailableUrl(['builder']) }}"
                         alt="{{ $record->title }} — фото {{ $loop->iteration }}"
                         loading="lazy" decoding="async"
                         class="block h-auto w-full {{ $wideLast && $loop->last ? 'sm:aspect-[3/2]' : 'sm:aspect-[4/5]' }} sm:h-full sm:object-cover">
                </figure>
            @endforeach
        </div>
    </div>
@endif
