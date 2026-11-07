@section('metaTitle', 'Блог о Аргентине - Статьи, советы и путеводители')
@section('metaDescription', 'Читайте интересные статьи о Аргентине, культуре, традициях и достопримечательностях северной Испании от профессионального гида')

<x-layout>
    <!-- Hero Section -->
    <section class="relative py-20 lg:py-28 bg-gray-100 overflow-hidden">
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,rgba(16,185,129,0.3),transparent_50%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_80%,rgba(59,130,246,0.3),transparent_50%)]"></div>
        </div>

        <div class="container mx-auto px-4 md:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
                    Всё о Латинской Америке
                </h1>
                <p class="text-xl text-gray-600 mb-8">
                    Откройте для себя культуру, традиции и достопримечательности Латинской Америки
                </p>

{{--                <!-- Search Bar -->--}}
{{--                <form method="GET" action="{{ route('blog.index') }}" class="max-w-2xl mx-auto">--}}
{{--                    <div class="relative">--}}
{{--                        <input--}}
{{--                            type="text"--}}
{{--                            name="search"--}}
{{--                            value="{{ request('search') }}"--}}
{{--                            placeholder="Поиск статей..."--}}
{{--                            class="w-full px-6 py-4 pr-12 rounded-full border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition-all"--}}
{{--                        >--}}
{{--                        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-emerald-600 text-white rounded-full hover:bg-emerald-700 transition-colors">--}}
{{--                            <x-tabler-search class="w-5 h-5" />--}}
{{--                        </button>--}}
{{--                    </div>--}}
{{--                    <input type="hidden" name="country" value="{{ request('country', 'all') }}">--}}
{{--                    --}}{{-- <input type="hidden" name="category" value="{{ request('category', 'all') }}"> --}}
{{--                </form>--}}
            </div>
        </div>
    </section>

    <!-- Filters Section -->
    <section class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm">
        <div class="container mx-auto px-4 md:px-8">
            <div class="flex items-center gap-4 py-4 overflow-x-auto">
                <span class="text-sm font-medium text-gray-700 whitespace-nowrap flex items-center gap-2">
                    <x-tabler-filter class="w-4 h-4" />
                    Фильтры:
                </span>

                <!-- Country Filter -->
                <form method="GET" action="{{ route('blog.index') }}" id="filterForm" class="flex items-center gap-3">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    {{-- <input type="hidden" name="category" value="{{ request('category', 'all') }}"> --}}

                    <div class="flex items-center gap-2 bg-gray-50 rounded-full px-4 py-2">
                        <x-tabler-world class="w-4 h-4 text-gray-600" />
                        <select
                            name="country"
                            onchange="document.getElementById('filterForm').submit()"
                            class="bg-transparent border-none text-sm focus:ring-0 cursor-pointer"
                        >
                            <option value="all">Все страны</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ request('country') == $country->id ? 'selected' : '' }}>
                                    {{ $country->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Prepared for future categories
                    <div class="flex items-center gap-2 bg-gray-50 rounded-full px-4 py-2">
                        <x-tabler-category class="w-4 h-4 text-gray-600" />
                        <select
                            name="category"
                            onchange="document.getElementById('filterForm').submit()"
                            class="bg-transparent border-none text-sm focus:ring-0 cursor-pointer"
                        >
                            <option value="all">Все категории</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    --}}
                </form>

                <!-- Active Filters Display -->
                @if(request('country') && request('country') !== 'all')
                    <a href="{{ route('blog.index', array_filter(request()->except('country'))) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-100 text-emerald-700 rounded-full text-sm hover:bg-emerald-200 transition-colors">
                        {{ $countries->firstWhere('id', request('country'))->title ?? '' }}
                        <x-tabler-x class="w-3 h-3" />
                    </a>
                @endif

                @if(request('search'))
                    <a href="{{ route('blog.index', array_filter(request()->except('search'))) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-100 text-emerald-700 rounded-full text-sm hover:bg-emerald-200 transition-colors">
                        "{{ request('search') }}"
                        <x-tabler-x class="w-3 h-3" />
                    </a>
                @endif

                @if(request()->hasAny(['country', 'search']))
                    <a href="{{ route('blog.index') }}"
                       class="text-sm text-gray-600 hover:text-emerald-600 transition-colors whitespace-nowrap">
                        Сбросить всё
                    </a>
                @endif
            </div>
        </div>
    </section>

    <!-- Blog Posts Grid -->
    <section class="py-12 md:py-16">
        <div class="container mx-auto px-4 md:px-8">
            @if($blogs->count() > 0)
                <!-- Results Count -->
                <div class="mb-8">
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
                                @if($blog->getFirstMediaUrl('blog_cover'))
                                    <img
                                        src="{{ $blog->getFirstMediaUrl('blog_cover', 'cover') }}"
                                        alt="{{ $blog->title }}"
                                        class="w-full h-full object-cover group-hover:scale-110 duration-300" style="transition: 0.4s"
                                    >
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
                                    <time datetime="{{ $blog->created_at->format('Y-m-d') }}" class="flex items-center gap-1">
                                        <x-tabler-calendar class="w-3 h-3" />
                                        {{ $blog->created_at->translatedFormat('d M Y') }}
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
                    {{ $blogs->withQueryString()->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-6">
                        <x-tabler-article-off class="w-10 h-10 text-gray-400" />
                    </div>
                    <h3 class="text-2xl font-light text-gray-900 mb-2">Статей не найдено</h3>
                    <p class="text-gray-600 mb-6">
                        @if(request()->hasAny(['country', 'search']))
                            Попробуйте изменить параметры поиска
                        @else
                            В данный момент нет опубликованных статей
                        @endif
                    </p>
                    @if(request()->hasAny(['country', 'search']))
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
