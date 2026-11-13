@section('metaTitle', 'Блог об Аргентине и Латинской Америке | Visita Caribe')
@section('metaDescription', 'Статьи Елены Вивас об Аргентине и Латинской Америке: путешествия, история, культура, природа и полезная информация о регионах.')
@section('ogType', 'website')
@if (! $listingFilters['search'] && $listingFilters['page'] === 1)
    @section('canonicalUrl', \App\Support\Content\ListingFilters::url($baseUrl, $listingFilters))
@else
    @section('robots', 'noindex, follow')
@endif

<x-layout>
    <!-- Editorial hero -->
    <section class="relative overflow-hidden bg-[#153b35] pb-12 pt-28 text-white sm:pb-14 sm:pt-32 lg:pb-16 lg:pt-36">
        <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-40 size-[500px] rounded-full bg-[#7b9c8d]/15 blur-3xl lg:right-[5%]"></div>
        <div aria-hidden="true" class="pointer-events-none absolute bottom-0 right-0 hidden h-[300px] w-[38%] opacity-20 lg:block"
            style="background: repeating-radial-gradient(circle at 100% 100%, transparent 0 44px, #b6cbbd 45px 46px, transparent 47px 90px)"></div>

        <div class="relative mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
            <nav aria-label="Навигация" class="mb-6 flex items-center gap-2 text-xs text-white/55">
                <a href="/" class="transition hover:text-white">Главная</a>
                <x-tabler-chevron-right class="size-3.5" stroke-width="1.5" />
                <span class="text-white/90">Блог</span>
            </nav>

            <div class="grid items-end gap-5 lg:grid-cols-[minmax(0,1fr)_290px] lg:gap-12">
                <div class="min-w-0">
                    <h1 class="max-w-[830px] text-[37px] font-normal tracking-[-0.045em] sm:text-5xl lg:text-[62px] lg:leading-[1.1]">
                        Всё о <span class="text-[#c9d9c6]">Латинской Америке</span>
                    </h1>
                </div>

                <div class="lg:pb-1">
                    <p class="max-w-[310px] text-sm leading-7 text-white/65">
                        Откройте для себя культуру, традиции и достопримечательности Латинской Америки
                    </p>
                    <div class="mt-4 flex items-center gap-3">
                        <span class="text-3xl font-normal tabular-nums text-white">{{ number_format($blogs->total(), 0, ',', ' ') }}</span>
                        <span class="text-xs leading-5 text-white/50">статей<br>по вашему запросу</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="sticky top-0 z-40 bg-white shadow-sm">
        <div class="container mx-auto px-4 md:px-8">
            <x-ui.listing-filters :base-url="$baseUrl" :filters="$listingFilters" :countries="$countries" />
        </div>
    </section>

        <!-- Blog Posts Grid -->
    <section class="py-8 md:py-14">
        <div class="container mx-auto px-4 md:px-8">
            @if($blogs->count() > 0)
                <!-- Results Count -->
                <div class="mb-6 md:mb-8">
                    <p class="text-gray-600">
                        Найдено статей: <span class="font-medium text-gray-900">{{ $blogs->total() }}</span>
                    </p>
                </div>

                <!-- Blog Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($blogs as $blog)
                        <article class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300">
                            <!-- Image -->
                            <a href="{{ route('blog.show', $blog->slug) }}" class="block relative aspect-[16/9] overflow-hidden bg-gradient-to-br from-emerald-100 to-blue-100">
                                @if($cover = $blog->getFirstMedia('blog_cover'))
                                    <img src="{{ $cover->getAvailableUrl(['big']) }}" alt="{{ $blog->title }}" loading="lazy" decoding="async"
                                        class="h-full w-full object-cover object-top">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-emerald-500">
                                        <x-tabler-article class="w-16 h-16 opacity-30" />
                                    </div>
                                @endif

                                <!-- Overlay on hover -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </a>

                            <!-- Content -->
                            <div class="p-6">
                                <!-- Meta Information -->
                                <div class="flex flex-wrap items-center gap-3 mb-4 text-xs text-gray-500">
                                    <time datetime="{{ $blog->display_date->format('Y-m-d') }}" class="flex items-center gap-1">
                                        <x-tabler-calendar class="w-3 h-3" />
                                        {{ $blog->display_date->translatedFormat('d M Y') }}
                                    </time>

                                    @if($blog->countries && $blog->countries->count() > 0)
                                        <span class="flex items-center gap-1">
                                            <x-tabler-map-pin class="w-3 h-3" />
                                            {{ $blog->countries->first()->title }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Title -->
                                <a href="{{ route('blog.show', $blog->slug) }}">
                                    <h2 class="text-xl font-medium text-gray-900 mb-3 line-clamp-2 group-hover:text-emerald-600 transition-colors">
                                        {{ $blog->title }}
                                    </h2>
                                </a>

                                <!-- Description -->
                                @if($blog->meta_description)
                                    <p class="text-gray-600 text-sm line-clamp-3 mb-4">
                                        {{ $blog->meta_description }}
                                    </p>
                                @endif

                                <!-- Read More -->
                                <a href="{{ route('blog.show', $blog->slug) }}"
                                   class="inline-flex items-center gap-2 text-emerald-600 hover:gap-3 transition-all duration-300 text-sm font-medium">
                                    Читать далее
                                    <x-tabler-arrow-right class="w-4 h-4" />
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-12">
                    {{ $blogs->links('components.ui.path-pagination', ['baseUrl' => $baseUrl, 'listingFilters' => $listingFilters]) }}
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-6">
                        <x-tabler-article-off class="w-10 h-10 text-gray-400" />
                    </div>
                    <h3 class="text-2xl font-light text-gray-900 mb-2">Статей не найдено</h3>
                    <p class="text-gray-600 mb-6">
                        @if(($listingFilters['country'] || $listingFilters['search']))
                            Попробуйте изменить параметры поиска
                        @else
                            В данный момент нет опубликованных статей
                        @endif
                    </p>
                    @if(($listingFilters['country'] || $listingFilters['search']))
                        <a href="{{ route('blog.index') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-full hover:bg-emerald-700 transition-colors">
                            Показать все статьи
                            <x-tabler-refresh class="w-4 h-4" />
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </section>


    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Custom select styling */
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            padding-right: 2rem;
        }

        /* Smooth transitions */
        * {
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Hide scrollbar for filter section on mobile */
        .overflow-x-auto::-webkit-scrollbar {
            display: none;
        }

        .overflow-x-auto {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</x-layout>
