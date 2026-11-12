@section('metaTitle', $tour->meta_title ?? $tour->title)
@section('metaDescription', $tour->meta_description ?? '')
@section('ogImage', $tour->getFirstMediaUrl('tour_cover', 'social') ?? asset('img/og-default.jpg'))

<x-layout>
    <article class="relative">
        <!-- Hero Header -->
        <header class="relative py-24 lg:py-32 text-white overflow-hidden">
            <!-- Background Image with Blur and Overlay -->
            <div class="absolute inset-0 z-0">
                @if($tour->getFirstMediaUrl('tour_cover'))
                    <img
                        src="{{ $tour->getFirstMediaUrl('tour_cover') }}"
                        alt="{{ $tour->title }}"
                        class="w-full h-full object-cover filter blur-sm scale-110"
                    >
                @endif
                <div class="absolute inset-0 bg-gradient-to-b from-black/40 to-black/60"></div>
            </div>

            <div class="container mx-auto px-4 md:px-8 relative z-10">
                <div class="">
                    <!-- Breadcrumbs -->
                    <div class="flex items-center gap-3 text-sm text-white/60 mb-8">
                        <a href="/" class="hover:text-white transition-colors">Главная</a>
                        <x-tabler-chevron-right class="w-4 h-4" />
                        <a href="/tours" class="hover:text-white transition-colors">Туры</a>
                        <x-tabler-chevron-right class="w-4 h-4" />
                        <span class="truncate max-w-xs">{{ $tour->title }}</span>
                    </div>

                    <!-- Meta info -->
                    <div class="flex flex-wrap items-center gap-4 text-white/60 mb-6">
                        <time datetime="{{ $tour->created_at->format('Y-m-d') }}" class="flex items-center gap-2">
                            <x-tabler-calendar class="w-4 h-4" />
                            {{ $tour->created_at->translatedFormat('d F Y') }}
                        </time>

                        @if($tour->countries && $tour->countries->count() > 0)
                            <span class="flex items-center gap-2">
                                <x-tabler-world class="w-4 h-4" />
                                {{ $tour->countries->pluck('title')->join(', ') }}
                            </span>
                        @endif
                    </div>

                    <!-- Title -->
                    <h1 class="text-3xl md:text-5xl font-semibold leading-tight mb-8" style="max-width: 980px">{{ $tour->title }}</h1>

                    <!-- Author info -->
                    <div class="flex items-center gap-4 mt-8 mb-0">
                        <div class="w-16 h-16 rounded-full overflow-hidden">
                            <img src="https://visitacaribe.com/img/guide_elena.png"
                                 alt="Елена Вивас"
                                 class="w-full h-full object-cover">
                        </div>
                        <div>
                            <div class="text-white/90 text-lg font-semibold">Елена Вивас</div>
                            <div class="text-white/60 text-sm">Гид в Аргентине с опытом более 15 лет</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Cover image (if different from header background) -->
        @if($tour->getFirstMediaUrl('tour_cover'))
            <div class="relative -mt-12 z-10 container mx-auto px-4 md:px-8">
                <div class="max-w-5xl mx-auto">
                    <div class="aspect-[16/9] overflow-hidden rounded-xl shadow-2xl">
                        <img src="{{ $tour->getFirstMediaUrl('tour_cover') }}" alt="{{ $tour->title }}"
                            class="h-full w-full object-cover object-top">
                    </div>
                </div>
            </div>
        @endif

        <!-- Main content -->
        <div class=" px-2 md:px-8 py-12 md:py-24">
            <div class="">
                <!-- Article content blocks -->
                <div class="prose prose-lg prose-emerald max-w-none">
                    @if($tour->content && is_array($tour->content))
                        @foreach($tour->content as $block)
                            {{-- Text Block --}}
                            @if($block['type'] === 'text' && isset($block['data']['text']))
                                <div class="max-w-3xl mx-auto text-xl content-block leading-8 my-8 space-y-8 font-[200]">
                                    {!! $block['data']['text'] !!}
                                </div>
                            @endif

                            {{-- Single Image Block --}}
                            @if($block['type'] === 'image' && isset($block['data']['image']))
                                @php
                                    $imageUuids = is_array($block['data']['image']) ? $block['data']['image'] : [$block['data']['image']];
                                    $media = $tour->getMedia('tour_builder_image')->whereIn('id', $imageUuids)->first();
                                @endphp

                                @if($media)
                                    <figure class="my-12 max-w-5xl mx-auto">
                                        <img
                                            src="{{ $media->getUrl() }}"
                                            alt="{{ $tour->title }}"
                                            class="w-full rounded-xl shadow-md"
                                        >
                                    </figure>
                                @endif
                            @endif

                            {{-- Gallery Block --}}
                            @if($block['type'] === 'gallery' && isset($block['data']['images']))
                                @php
                                    $imageUuids = is_array($block['data']['images']) ? $block['data']['images'] : [];
                                    $galleryMedia = $tour->getMedia('tour_builder_gallery')->whereIn('id', $imageUuids);
                                    $columns = $block['data']['columns'] ?? 'columns-2';
                                    $gridClass = match($columns) {
                                        'columns-3' => 'md:grid-cols-3',
                                        'columns-4' => 'md:grid-cols-4',
                                        default => 'md:grid-cols-2',
                                    };
                                @endphp

                                @if($galleryMedia->count() > 0)
                                    <div class="my-12 max-w-5xl mx-auto">
                                        <div class="grid grid-cols-1 {{ $gridClass }} gap-4">
                                            @foreach($galleryMedia as $image)
                                                <div class="aspect-2/3 rounded-xl overflow-hidden shadow-md">
                                                    <img
                                                        src="{{ $image->getUrl() }}"
                                                        alt="{{ $tour->title }}"
                                                        loading="lazy"
                                                        class="w-full h-full object-cover transition-transform hover:scale-105 duration-300"
                                                    >
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif

                            {{-- Quote Block --}}
                            @if($block['type'] === 'quote' && isset($block['data']['text']))
                                <blockquote class="not-prose my-12 bg-emerald-50 p-6 rounded-xl">
                                    <div class="max-w-5xl mx-auto">
                                        <div class="text-xl font-[200] text-gray-800">
                                            {!! $block['data']['text'] !!}
                                        </div>
                                        @if(isset($block['data']['author']) && !empty($block['data']['author']))
                                            <footer class="mt-3 text-gray-600">
                                                — {{ $block['data']['author'] }}
                                            </footer>
                                        @endif
                                    </div>
                                </blockquote>
                            @endif

                            {{-- Attach Records Block --}}
                            @if($block['type'] === 'attach_record' && isset($block['data']['items']))
                                <div class="my-12 bg-gray-50 rounded-2xl p-8">
                                    @if(isset($block['data']['title']) && !empty($block['data']['title']))
                                        <h3 class="text-2xl font-light text-gray-900 mb-6">{{ $block['data']['title'] }}</h3>
                                    @endif

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        @foreach($block['data']['items'] as $item)
                                            @if(isset($item['model']) && isset($item['record_id']))
                                                @php
                                                    $record = $item['model']::find($item['record_id']);
                                                @endphp

                                                @if($record)
                                                    <a href="{{ $record->url ?? '#' }}" class="group block bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
                                                        @if(method_exists($record, 'getFirstMediaUrl') && $record->getFirstMediaUrl())
                                                            <div class="aspect-[16/9] overflow-hidden">
                                                                <img
                                                                    src="{{ $record->getFirstMediaUrl() }}"
                                                                    alt="{{ $record->title }}"
                                                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                                                >
                                                            </div>
                                                        @endif
                                                        <div class="p-5">
                                                            <h4 class="font-medium text-gray-900 group-hover:text-emerald-600 transition-colors line-clamp-2">
                                                                {{ $record->title }}
                                                            </h4>
                                                            <span class="inline-flex items-center gap-1 mt-3 text-sm text-emerald-600">
                                                                Читать далее
                                                                <x-tabler-arrow-right class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                                            </span>
                                                        </div>
                                                    </a>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- List Block --}}
                            @if($block['type'] === 'list' && isset($block['data']['items']))
                                <div class="max-w-3xl mx-auto text-xl content-block leading-8 my-8 space-y-8 font-[200]">
                                    @if(isset($block['data']['title']) && !empty($block['data']['title']))
                                        <h3 class="text-2xl font-light text-gray-900 mb-6">{{ $block['data']['title'] }}</h3>
                                    @endif

                                    <div class="space-y-6">
                                        @foreach($block['data']['items'] as $item)
                                            <div class="flex gap-4 p-5 bg-white rounded-xl border border-gray-100 hover:border-emerald-200 transition-colors duration-300">
                                                @if(isset($item['photo']) && !empty($item['photo']))
                                                    <div class="flex-shrink-0 w-24 h-24 rounded-lg overflow-hidden">
                                                        <img
                                                            src="{{ Storage::url($item['photo']) }}"
                                                            alt="{{ $item['heading'] ?? '' }}"
                                                            class="w-full h-full object-cover"
                                                        >
                                                    </div>
                                                @endif

                                                <div class="flex-1">
                                                    @if(isset($item['heading']) && !empty($item['heading']))
                                                        <h4 class="font-medium text-gray-900 mb-2">{{ $item['heading'] }}</h4>
                                                    @endif

                                                    @if(isset($item['text']) && !empty($item['text']))
                                                        <p class="text-gray-600 text-sm leading-relaxed">{{ $item['text'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>

                <!-- Guide Information Block -->
                <div class="mt-16 rounded-2xl bg-gradient-to-br from-emerald-50 to-white p-8 border border-emerald-100/50 shadow-sm">
                    <div class="flex flex-col md:flex-row gap-8 items-center md:items-start">
                        <div class="w-28 h-28 rounded-full overflow-hidden flex-shrink-0 border-4 border-white shadow-md">
                            <img
                                src="/img/elena_vivas_galicia_guide.jpg"
                                alt="Елена Вивас - профессиональный гид в Аргентине"
                                class="w-full h-full object-cover"
                            >
                        </div>

                        <div class="flex-1 text-center md:text-left">
                            <h3 class="text-2xl font-light text-gray-900 mb-2">Откройте Аргентину с профессиональным гидом</h3>
                            <h4 class="text-xl font-medium text-emerald-600 mb-4">Елена Вивас</h4>

                            <p class="text-gray-600 mb-6">Профессиональный гид в Аргентине с опытом более 15 лет. Геолог, историк. Эксперт по культуре, традициям и гастрономии северной Испании.</p>

                            <div class="flex flex-col md:flex-row md:items-center gap-4 md:gap-8 mb-6">
                                <div class="flex items-center gap-3 justify-center md:justify-start">
                                    <span class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                        <x-tabler-mail class="w-5 h-5 text-emerald-600" />
                                    </span>
                                    <a href="mailto:elena.vivas.garcia@gmail.com" class="text-gray-700 hover:text-emerald-600 transition-colors">
                                        elena.vivas.garcia@gmail.com
                                    </a>
                                </div>

                                <div class="flex items-center gap-3 justify-center md:justify-start">
                                    <span class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                        <x-tabler-phone class="w-5 h-5 text-emerald-600" />
                                    </span>
                                    <a href="tel:+5491137630036" class="text-gray-700 hover:text-emerald-600 transition-colors">
                                        +54 9 11 3763 0036
                                    </a>
                                </div>
                            </div>

                            <div class="flex justify-center md:justify-start">
                                <a href="/tours" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-full hover:bg-emerald-700 transition-colors">
                                    Узнать о турах
                                    <x-tabler-arrow-right class="w-4 h-4" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </article>

    <style>
        /* Prose styling */
        .prose {
            color: #374151;
        }

        .prose blockquote p {
            margin: 0;
        }

        .prose h2 {
            font-size: 1.875rem;
            font-weight: 500;
            line-height: 1.3;
            margin-top: 2.5em;
            margin-bottom: 1.25rem;
            color: #111827;
        }

        .prose h3 {
            font-size: 1.5rem;
            font-weight: 500;
            line-height: 1.4;
            margin-top: 2em;
            margin-bottom: 1rem;
            color: #111827;
        }

        .prose strong {
            font-weight: 500;
            color: #111827;
        }

        .prose a {
            color: #047857;
            text-decoration: none;
            border-bottom: 1px solid #047857;
            transition: border-color 0.2s, color 0.2s;
        }

        .prose a:hover {
            color: #065f46;
            border-color: #065f46;
        }

        .prose ul {
            list-style-type: none;
            margin-top: 1.25em;
            margin-bottom: 1.25em;
            padding-left: 1.625em;
        }

        .prose ul li {
            position: relative;
            padding-left: 1.5em;
            margin-bottom: 0.5em;
        }

        .prose ul li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0.75em;
            height: 5px;
            width: 5px;
            border-radius: 50%;
            background-color: #10b981;
        }

        .prose ol {
            counter-reset: item;
            list-style-type: none;
            margin-top: 1.25em;
            margin-bottom: 1.25em;
            padding-left: 1.625em;
        }

        .prose ol li {
            position: relative;
            padding-left: 2.5em;
            margin-bottom: 0.75em;
            counter-increment: item;
        }

        .prose ol li::before {
            content: counter(item, decimal-leading-zero);
            position: absolute;
            left: 0;
            top: 0;
            font-weight: 500;
            color: #10b981;
            font-size: 0.875rem;
        }

        .prose img {
            border-radius: 0.75rem;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        @media (max-width: 768px) {
            .prose h2 {
                font-size: 1.5rem;
            }

            .prose h3 {
                font-size: 1.25rem;
            }
        }
    </style>
</x-layout>
