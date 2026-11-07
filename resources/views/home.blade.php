@section('metaTitle', 'Туры в Аргентине с гидом Елена Вивас')
@section('metaDescription', 'Ваш незабываемый отдых в самых экзотических местах Латинской Америки от Патагонии до Карибов')

<x-layout>
    <section
        x-data="heroSection"
        x-init="init()"
        class="relative min-h-screen overflow-hidden selection:bg-white/20 selection:text-white"
        x-ref="container"
        @mousemove="handleMouseMove"
    >
        <!-- Background with scroll-based zoom using CSS variables -->
        <div class="absolute inset-0 z-0 hero-background">
            <div
                class="absolute inset-0 bg-cover bg-center hero-image"
                style="background-image: url('/img/main_head_background.webp'); background-position: 58% center;"
            ></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-black/20"></div>
            <div
                class="absolute inset-0 bg-gradient-to-r from-emerald-900/30 to-blue-900/30 mix-blend-soft-light"
                :style="`transform: translate(${mouseX}px, ${mouseY}px)`"
            ></div>
        </div>

        <script>
            // Simple scroll-based zoom effect
            document.addEventListener('DOMContentLoaded', () => {
                const handleScroll = () => {
                    const scrollY = window.scrollY;
                    const scale = 1 + (scrollY / 400) * 0.2; // Same formula as before
                    document.documentElement.style.setProperty('--hero-scale', scale);
                };

                window.addEventListener('scroll', handleScroll);
                handleScroll(); // Initialize on load
            });
        </script>

        <style>
            /* CSS for zoom effect */
            :root {
                --hero-scale: 1;
            }

            .hero-background .hero-image {
                transform: scale(var(--hero-scale));
                transition: transform 0.1s ease-out;
            }
        </style>

        <!-- Hero Content - Using flex instead of absolute positioning -->
        <div class="relative w-full z-10 py-24 min-h-screen flex items-center">
            <div class="container mx-auto px-4 md:px-8">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                    <div
                        class="md:col-span-9 lg:col-span-9 space-y-8"
                        x-show="isLoaded"
                        x-transition:enter="transition ease-out duration-1200"
                        x-transition:enter-start="opacity-0 translate-y-12"
                        x-transition:enter-end="opacity-100 translate-y-0"

                    >
                        <!-- Small Indicator -->
                        <div class="flex items-center gap-4">
                            <div class="w-2 h-2 bg-white rounded-full"></div>
                            <span
                                class="text-xs tracking-[0.25em] text-white/80 uppercase"
                                x-show="isLoaded"
                                x-transition:enter="transition ease-out duration-1000 delay-500"
                                x-transition:enter-start="opacity-0 -translate-x-8"
                                x-transition:enter-end="opacity-100 translate-x-0"
                            >
                                  роводник в мир латинской америки
                                </span>
                        </div>

                        <!-- Main Heading -->
                        <div class="space-y-4">
                            <h1 class="text-4xl md:text-6xl font-semibold text-white tracking-tight leading-[1.1]">
                                Каникулы и жизнь<br/> в Латинской Америке
                            </h1>
                        </div>

                        <!-- Description -->
                        <div class="max-w-lg">
                            <p class="text-xl md:text-2xl font-light text-white/90 leading-relaxed">
                                Ваш незабываемый отдых в самых экзотических местах Латинской Америки
                            </p>
                        </div>

                        <!-- Quick links (replaces Trust Indicators) -->
                        <div class="pt-6 md:pt-8">
                            <div class="flex flex-wrap gap-3 md:gap-4">
                                <a href="#tours"
                                   class="group inline-flex items-center gap-2.5 px-4 py-3 rounded-full
                  bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20
                  backdrop-blur-sm transition-colors">
                                    <x-tabler-map-2 class="w-5 h-5 text-white/90" stroke-width="1.3"/>
                                    <span class="text-xs md:text-sm uppercase tracking-[0.22em] text-white/90">Туры</span>
                                    <x-tabler-arrow-right class="w-4 h-4 text-white/60 group-hover:text-white/90 transition-colors" stroke-width="1.6"/>
                                </a>

                                <a href="/emigration"
                                   class="group inline-flex items-center gap-2.5 px-4 py-3 rounded-full
                  bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20
                  backdrop-blur-sm transition-colors">
                                    <x-tabler-e-passport class="w-5 h-5 text-white/90" stroke-width="1.3"/>
                                    <span class="text-xs md:text-sm uppercase tracking-[0.22em] text-white/90">Эммиграция</span>
                                    <x-tabler-arrow-right class="w-4 h-4 text-white/60 group-hover:text-white/90 transition-colors" stroke-width="1.6"/>
                                </a>

                                <a href="/real-estate"
                                   class="group inline-flex items-center gap-2.5 px-4 py-3 rounded-full
                  bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20
                  backdrop-blur-sm transition-colors">
                                    <x-tabler-home-search class="w-5 h-5 text-white/90" stroke-width="1.3"/>
                                    <span class="text-xs md:text-sm uppercase tracking-[0.22em] text-white/90">Недвижимость</span>
                                    <x-tabler-arrow-right class="w-4 h-4 text-white/60 group-hover:text-white/90 transition-colors" stroke-width="1.6"/>
                                </a>

                                <a href="/ads"
                                   class="group inline-flex items-center gap-2.5 px-4 py-3 rounded-full
                  bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20
                  backdrop-blur-sm transition-colors">
                                    <x-tabler-speakerphone class="w-5 h-5 text-white/90" stroke-width="1.3"/>
                                    <span class="text-xs md:text-sm uppercase tracking-[0.22em] text-white/90">Объявления</span>
                                    <x-tabler-arrow-right class="w-4 h-4 text-white/60 group-hover:text-white/90 transition-colors" stroke-width="1.6"/>
                                </a>
                            </div>
                        </div>


                        <!-- Trust Indicators - Refined and Elegant -->
{{--                        <div class="hidden md:block border-t border-white/10 pt-8 mt-8">--}}
{{--                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-12 gap-y-6">--}}
{{--                                <div class="flex items-start gap-4">--}}
{{--                                    <div>--}}
{{--                                        <h3 class="text-white text-base mb-2">Индивидуальный подход</h3>--}}
{{--                                        <p class="text-white/70 text-sm leading-relaxed">Маршруты, созданные под ваши интересы и предпочтения</p>--}}
{{--                                    </div>--}}
{{--                                </div>--}}

{{--                                <div class="flex items-start gap-4">--}}
{{--                                    <div>--}}
{{--                                        <h3 class="text-white text-base mb-2">С 2011 года в Галисии</h3>--}}
{{--                                        <p class="text-white/70 text-sm leading-relaxed">Более 10 лет опыта проведения авторских туров по Северу Испании</p>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <!-- CTA Buttons -->--}}
{{--                        <div class="flex flex-col sm:flex-row gap-4 pt-8">--}}
{{--                            <a href="#tours" class="group relative px-10 py-4 border border-emerald-600/80 rounded-full overflow-hidden text-center">--}}
{{--                                    <span class="relative z-10 text-sm uppercase tracking-[0.2em] text-white group-hover:text-emerald-600 transition-colors duration-500">--}}
{{--                                      Все туры--}}
{{--                                    </span>--}}
{{--                                <div class="absolute inset-0 w-full bg-emerald-600 group-hover:w-0 transition-all duration-500 ease-out"></div>--}}
{{--                            </a>--}}
{{--                            <a href="#contact" class="px-10 text-emerald-500 py-4 border border-transparent text-sm uppercase tracking-[0.2em] text-white/80 hover:text-white transition-colors duration-300 flex items-center justify-center sm:justify-start gap-2">--}}
{{--                                Услуги--}}
{{--                                <x-tabler-arrow-right class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" />--}}
{{--                            </a>--}}
{{--                        </div>--}}
                    </div>
                </div>
            </div>
        </div>


        <!-- Gradient overlay at the bottom -->
{{--        <div class="absolute bottom-0 left-0 right-0 w-full h-[180px] bg-gradient-to-t from-white to-transparent"></div>--}}


    </section>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('heroSection', () => ({
                isLoaded: false,
                mouseX: 0,
                mouseY: 0,
                cursorX: 0,
                cursorY: 0,
                btnHover: false,
                hoveredLink: null,

                init() {
                    // Set loaded state after a small delay
                    setTimeout(() => {
                        this.isLoaded = true;
                    }, 100);
                },

                handleMouseMove(e) {
                    const rect = this.$refs.container.getBoundingClientRect();

                    // Calculate mouse position relative to container (-0.5 to 0.5 range)
                    const relativeX = (e.clientX - rect.left) / rect.width - 0.5;
                    const relativeY = (e.clientY - rect.top) / rect.height - 0.5;

                    // Scale for the parallax effect
                    this.mouseX = relativeX * 20;
                    this.mouseY = relativeY * 20;

                    // Update cursor position with smoothing
                    this.cursorX = e.clientX - 32; // Half of cursor width
                    this.cursorY = e.clientY - 32; // Half of cursor height
                },

                linkHover(index) {
                    this.hoveredLink = index;
                },

                linkReset() {
                    this.hoveredLink = null;
                }
            }));
        });
    </script>

{{--    <section class="py-16 md:py-24 bg-white">--}}
{{--        <div class="container mx-auto px-4 md:px-8">--}}
{{--            <!-- Grid -->--}}
{{--            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">--}}
{{--                <!-- Tours -->--}}
{{--                <a href="#tours"--}}
{{--                   class="group relative rounded-2xl bg-gray-50 hover:bg-white transition-all duration-300 overflow-hidden--}}
{{--                      ring-1 ring-black/5 hover:ring-emerald-500/30 hover:shadow-lg">--}}
{{--                    <div class="p-6 md:p-7">--}}
{{--                        <div class="flex items-start justify-between gap-6">--}}
{{--                            <div class="space-y-2">--}}
{{--                                <div class="flex items-center gap-3">--}}
{{--                                    <x-tabler-map-2 class="w-10 h-10 text-emerald-600" stroke-width="1.2"/>--}}
{{--                                    <h3 class="text-lg font-semibold text-gray-900">--}}
{{--                                        Туры--}}
{{--                                    </h3>--}}
{{--                                </div>--}}
{{--                                <p class="text-sm text-gray-600 leading-relaxed max-w-[28ch]">--}}
{{--                                    Авторские маршруты, логистика, места — без суеты.--}}
{{--                                </p>--}}
{{--                            </div>--}}

{{--                            <x-tabler-arrow-right class="w-5 h-5 text-gray-400 group-hover:text-emerald-600 transition-colors"--}}
{{--                                                  stroke-width="1.6"/>--}}
{{--                        </div>--}}

{{--                        <div class="mt-5 pt-5 border-t border-black/5 flex items-center justify-between">--}}
{{--                        <span class="text-xs tracking-[0.22em] uppercase text-gray-500">--}}
{{--                            Смотреть--}}
{{--                        </span>--}}
{{--                            <span class="text-xs text-gray-400 group-hover:text-gray-500 transition-colors">--}}
{{--                            #tours--}}
{{--                        </span>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </a>--}}

{{--                <!-- Emigration -->--}}
{{--                <a href="/emigration"--}}
{{--                   class="group relative rounded-2xl bg-gray-50 hover:bg-white transition-all duration-300 overflow-hidden--}}
{{--                      ring-1 ring-black/5 hover:ring-emerald-500/30 hover:shadow-lg">--}}
{{--                    <div class="p-6 md:p-7">--}}
{{--                        <div class="flex items-start justify-between gap-6">--}}
{{--                            <div class="space-y-2">--}}
{{--                                <div class="flex items-center gap-3">--}}
{{--                                    <x-tabler-e-passport class="w-10 h-10 text-emerald-600" stroke-width="1.2"/>--}}
{{--                                    <h3 class="text-lg font-semibold text-gray-900">--}}
{{--                                        Эммиграция--}}
{{--                                    </h3>--}}
{{--                                </div>--}}
{{--                                <p class="text-sm text-gray-600 leading-relaxed max-w-[28ch]">--}}
{{--                                    Подскажем путь, документы и реальные шаги.--}}
{{--                                </p>--}}
{{--                            </div>--}}

{{--                            <x-tabler-arrow-right class="w-5 h-5 text-gray-400 group-hover:text-emerald-600 transition-colors"--}}
{{--                                                  stroke-width="1.6"/>--}}
{{--                        </div>--}}

{{--                        <div class="mt-5 pt-5 border-t border-black/5 flex items-center justify-between">--}}
{{--                        <span class="text-xs tracking-[0.22em] uppercase text-gray-500">--}}
{{--                            Перейти--}}
{{--                        </span>--}}
{{--                            <span class="text-xs text-gray-400 group-hover:text-gray-500 transition-colors">--}}
{{--                            /emigration--}}
{{--                        </span>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </a>--}}

{{--                <!-- Real estate -->--}}
{{--                <a href="/real-estate"--}}
{{--                   class="group relative rounded-2xl bg-gray-50 hover:bg-white transition-all duration-300 overflow-hidden--}}
{{--                      ring-1 ring-black/5 hover:ring-emerald-500/30 hover:shadow-lg">--}}
{{--                    <div class="p-6 md:p-7">--}}
{{--                        <div class="flex items-start justify-between gap-6">--}}
{{--                            <div class="space-y-2">--}}
{{--                                <div class="flex items-center gap-3">--}}
{{--                                    <x-tabler-home-search class="w-10 h-10 text-emerald-600" stroke-width="1.2"/>--}}
{{--                                    <h3 class="text-lg font-semibold text-gray-900">--}}
{{--                                        Недвижимость--}}
{{--                                    </h3>--}}
{{--                                </div>--}}
{{--                                <p class="text-sm text-gray-600 leading-relaxed max-w-[28ch]">--}}
{{--                                    Подбор, проверка, сделки — без сюрпризов.--}}
{{--                                </p>--}}
{{--                            </div>--}}

{{--                            <x-tabler-arrow-right class="w-5 h-5 text-gray-400 group-hover:text-emerald-600 transition-colors"--}}
{{--                                                  stroke-width="1.6"/>--}}
{{--                        </div>--}}

{{--                        <div class="mt-5 pt-5 border-t border-black/5 flex items-center justify-between">--}}
{{--                        <span class="text-xs tracking-[0.22em] uppercase text-gray-500">--}}
{{--                            Открыть--}}
{{--                        </span>--}}
{{--                            <span class="text-xs text-gray-400 group-hover:text-gray-500 transition-colors">--}}
{{--                            /real-estate--}}
{{--                        </span>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </a>--}}

{{--                <!-- Ads -->--}}
{{--                <a href="/ads"--}}
{{--                   class="group relative rounded-2xl bg-gray-50 hover:bg-white transition-all duration-300 overflow-hidden--}}
{{--                      ring-1 ring-black/5 hover:ring-emerald-500/30 hover:shadow-lg">--}}
{{--                    <div class="p-6 md:p-7">--}}
{{--                        <div class="flex items-start justify-between gap-6">--}}
{{--                            <div class="space-y-2">--}}
{{--                                <div class="flex items-center gap-3">--}}
{{--                                    <x-tabler-speakerphone class="w-10 h-10 text-emerald-600" stroke-width="1.2"/>--}}
{{--                                    <h3 class="text-lg font-semibold text-gray-900">--}}
{{--                                        Объявления--}}
{{--                                    </h3>--}}
{{--                                </div>--}}
{{--                                <p class="text-sm text-gray-600 leading-relaxed max-w-[28ch]">--}}
{{--                                    Актуальные предложения и свежие находки.--}}
{{--                                </p>--}}
{{--                            </div>--}}

{{--                            <x-tabler-arrow-right class="w-5 h-5 text-gray-400 group-hover:text-emerald-600 transition-colors"--}}
{{--                                                  stroke-width="1.6"/>--}}
{{--                        </div>--}}

{{--                        <div class="mt-5 pt-5 border-t border-black/5 flex items-center justify-between">--}}
{{--                        <span class="text-xs tracking-[0.22em] uppercase text-gray-500">--}}
{{--                            Смотреть--}}
{{--                        </span>--}}
{{--                            <span class="text-xs text-gray-400 group-hover:text-gray-500 transition-colors">--}}
{{--                            /ads--}}
{{--                        </span>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </a>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </section>--}}



    <section id="tours" class="py-24 bg-gray-50 overflow-hidden">
        <div class="container mx-auto px-4 md:px-8">
            <!-- Section Header -->
            <div class="max-w-3xl mx-auto text-center mb-16">
                <span class="inline-block text-emerald-600 font-medium tracking-widest text-sm uppercase mb-2">Лучшие предложения</span>
                <h2 class="text-4xl md:text-5xl font-semibold text-gray-900 leading-tight mb-4">Популярные туры</h2>
                <p class="text-lg text-gray-600">Откройте для себя уникальную латинскую америку</p>
            </div>

                <!-- Tours Grid -->
            <div class="relative">
                <!-- Decorative Elements -->
                <div class="absolute -left-16 -top-16 w-64 h-64 bg-emerald-100 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
                <div class="absolute -right-16 -bottom-16 w-64 h-64 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-6 relative z-10">
                    @foreach($tours as $tour)
                        <div class="group relative overflow-hidden rounded-lg bg-white shadow-md transition-all duration-300 hover:shadow-xl">
                            <!-- Tour Image Wrapper -->
                            <div class="relative overflow-hidden aspect-[3/2]">
                                @if ($cover = $tour->getFirstMedia('tour_cover'))
                                    <img
                                        src="{{ $cover->getFullUrl('cover') }}"
                                        alt="{{ $tour->title }}"
                                        class="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-110"
                                    >
                                @endif
{{--                                @if($tour->duration_days && $tour->duration_days > 0)--}}
{{--                                <div class="absolute top-4 left-4 bg-black/70 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full">--}}
{{--                                    <span class="flex items-center gap-1.5">--}}
{{--                                      <x-tabler-clock class="w-3.5 h-3.5" />--}}
{{--                                      {{ $tour->duration_days }} дн.--}}
{{--                                    </span>--}}
{{--                                </div>--}}
{{--                                @endif--}}
                                    @if($tour->countries && $tour->countries->count() > 0)
                                        <div class="absolute top-4 left-4 bg-black/70 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full">
                                        <span class="flex items-center gap-1.5">
                                          <x-tabler-world class="w-3.5 h-3.5" />
                                           {{ $tour->countries->pluck('title')->join(', ') }}
                                        </span>
                                        </div>
                                    @endif

                                <div class="absolute top-4 right-4 bg-emerald-500/90 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full">
                                    <span class="flex items-center gap-1.5">
                                      <x-tabler-star-filled class="w-3.5 h-3.5" />
                                      5
                                    </span>
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </div>

                            <!-- Tour Content -->
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-emerald-600 transition-colors">
                                    {{ $tour->title }}
                                </h3>
{{--                                <p class="text-gray-600 mb-4 line-clamp-2">{{ $tour->getSummaryAttribute() }}</p>--}}
                                <a
                                    href="{{ route('tours.show', $tour->slug) }}"
                                    class="inline-flex items-center text-sm font-medium text-emerald-600 hover:text-emerald-800 transition-colors"
                                >
                                    Подробнее
                                    <x-tabler-arrow-right class="w-4 h-4 ml-1.5 transition-transform group-hover:translate-x-1" />
                                </a>
                            </div>

                            <!-- Full card link -->
                            <a  href="{{ route('tours.show', $tour->slug) }}" class="absolute inset-0 z-10" aria-hidden="true"></a>
                        </div>
                    @endforeach
                </div>

                <!-- View All Buttons -->
                <div class="mt-12 text-center">
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="/tours" class="group relative px-10 py-4 border border-emerald-600/80 rounded-full overflow-hidden text-center">
                                <span class="relative z-10 text-sm uppercase tracking-[0.2em] text-emerald-600 group-hover:text-white transition-colors duration-500">
                                  Все туры
                                </span>
                            <div class="absolute inset-0 w-0 bg-emerald-600 group-hover:w-full transition-all duration-500 ease-out"></div>
                        </a>

                        <a href="/contact" class="px-10 py-4 border border-transparent text-sm uppercase tracking-[0.2em] text-gray-500 hover:text-emerald-600 transition-colors duration-300 flex items-center justify-center sm:justify-start gap-2 group">
                            Связаться с нами
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M5 12l14 0"></path>
                                <path d="M13 18l6 -6"></path>
                                <path d="M13 6l6 6"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .animate-blob {
                animation: blob-bounce 7s infinite ease;
            }

            .animation-delay-2000 {
                animation-delay: 2s;
            }

            @keyframes blob-bounce {
                0% {
                    transform: translate(0px, 0px) scale(1);
                }
                33% {
                    transform: translate(30px, -50px) scale(1.1);
                }
                66% {
                    transform: translate(-20px, 20px) scale(0.9);
                }
                100% {
                    transform: translate(0px, 0px) scale(1);
                }
            }
        </style>
    </section>

    <section id="blog" class="bg-amber-100 py-24 overflow-hidden">
        <div class="container mx-auto px-4 md:px-8">
            <!-- Section Header -->
            <div class="max-w-3xl mx-auto text-center mb-16">
                <span class="inline-block text-emerald-600 font-medium tracking-widest text-sm uppercase mb-2">откройте неизведаное</span>
                <h2 class="text-4xl md:text-5xl font-semibold text-gray-900 leading-tight mb-4">Интересное в блоге</h2>
                <p class="text-lg text-gray-600">Всё о путешествиях и жизни в латинской америке</p>
            </div>

                <!-- Tours Grid -->
            <div class="relative">
                <!-- Decorative Elements -->
                <div class="absolute -left-16 -top-16 w-64 h-64 bg-emerald-100 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
                <div class="absolute -right-16 -bottom-16 w-64 h-64 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-6 relative z-10">
                    @foreach ($blogs as $blog)
                        <div class="group relative overflow-hidden rounded-lg bg-white shadow-md transition-all duration-300 hover:shadow-xl">
                            <!-- Tour Image Wrapper -->
                            <div class="relative overflow-hidden aspect-[3/2]">
                                @if ($cover = $blog->getFirstMedia('blog_cover'))
                                    <img
                                        src="{{ $cover->getFullUrl('cover') }}"
                                        alt="{{ $blog->title }}"
                                        class="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-110"
                                    >
                                @endif


                                @if($blog->countries && $blog->countries->count() > 0)
                                    <div class="absolute top-4 left-4 bg-black/70 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full">
                                        <span class="flex items-center gap-1.5">
                                          <x-tabler-world class="w-3.5 h-3.5" />
                                           {{ $blog->countries->pluck('title')->join(', ') }}
                                        </span>
                                    </div>
                                @endif

                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </div>

                            <!-- Tour Content -->
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-emerald-600 transition-colors">
                                    {{ $blog->title }}
                                </h3>
                                {{--                                <p class="text-gray-600 mb-4 line-clamp-2">{{ $tour->getSummaryAttribute() }}</p>--}}
                                <a
                                    href="{{ route('blog.show', $blog->slug) }}"
                                    class="inline-flex items-center text-sm font-medium text-emerald-600 hover:text-emerald-800 transition-colors"
                                >
                                    Подробнее
                                    <x-tabler-arrow-right class="w-4 h-4 ml-1.5 transition-transform group-hover:translate-x-1" />
                                </a>
                            </div>

                            <!-- Full card link -->
                            <a href="{{ route('blog.show', $blog->slug) }}" class="absolute inset-0 z-10" aria-hidden="true"></a>
                        </div>
                    @endforeach
                </div>

                <!-- View All Buttons -->
                <div class="mt-12 text-center">
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('blog.index') }}" class="group relative px-10 py-4 border border-emerald-600/80 rounded-full overflow-hidden text-center">
                                <span class="relative z-10 text-sm uppercase tracking-[0.2em] text-emerald-600 group-hover:text-white transition-colors duration-500">
                                  Больше статей
                                </span>
                            <div class="absolute inset-0 w-0 bg-emerald-600 group-hover:w-full transition-all duration-500 ease-out"></div>
                        </a>

                    </div>
                </div>
            </div>
        </div>

        <style>
            .animate-blob {
                animation: blob-bounce 7s infinite ease;
            }

            .animation-delay-2000 {
                animation-delay: 2s;
            }

            @keyframes blob-bounce {
                0% {
                    transform: translate(0px, 0px) scale(1);
                }
                33% {
                    transform: translate(30px, -50px) scale(1.1);
                }
                66% {
                    transform: translate(-20px, 20px) scale(0.9);
                }
                100% {
                    transform: translate(0px, 0px) scale(1);
                }
            }
        </style>
    </section>



</x-layout>
