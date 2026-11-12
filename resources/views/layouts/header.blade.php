<style>
    body {
        font-family: futura-pt, sans-serif;
        font-weight: 300;
        font-style: normal;
    }
    .container {
        max-width: 1460px;
    }
    .nav-link {
        position: relative;
    }
    .nav-link::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 0;
        height: 1px;
        background-color: white;
        transition: width 0.3s ease;
    }
    .nav-link:hover::after {
        width: 100%;
    }
    .lang-active {
        color: #DAA520;
    }

    #site_header {
        transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: transform;
        backface-visibility: hidden;
        transform: translateY(0); /* Explicitly define starting transform */
    }
    #site_header.hdn {
        transform: translateY(-100%);
    }
    #site_header::before {
        display: block;
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        height: 100px;
        background: linear-gradient(180deg,rgba(0, 0, 0, 0.73) 0%, rgba(0, 0, 0, 0) 100%);
    }

</style>
<header id="site_header" class="fixed top-0 left-0 w-full z-50">
    <div class="container px-2 mx-auto">
        <div class="flex items-center justify-between py-6">
            <!-- Left Side -->
            <div class="w-1/3">
                <div class="md:flex hidden justify-start items-center space-x-8 text-xs uppercase tracking-widest relative z-10">
                    <a href="/tours" class="nav-link text-white hover:text-gg-gold transition-colors duration-300">Туры</a>
                    <a href="/blog" class="nav-link text-white hover:text-gg-gold transition-colors duration-300">Блог</a>
                    <a href="/realestate" class="nav-link text-white hover:text-gg-gold transition-colors duration-300">Недвижимость</a>
                </div>
            </div>

            <!-- Center Logo -->
            <div class="w-1/3 flex justify-center">
                <a href="/" class="block mx-auto">
                    <img class="shrink-0 relative z-10" src="/img/logo.svg" width="40" alt="Карибы">
                </a>
            </div>

            <!-- Right Side -->
            <div class="w-1/3">
                <div class="md:flex hidden justify-end items-center space-x-8 text-xs uppercase tracking-widest  relative z-10">
                    <a href="/contact" class="nav-link text-white hover:text-gg-gold transition-colors duration-300">Контакты</a>

                </div>
            </div>

            <!-- Mobile navigation trigger -->
            <div class="absolute right-4 z-20 md:hidden">
                <button data-site-menu-toggle type="button" aria-controls="site-mobile-menu" aria-expanded="false" aria-label="Открыть меню"
                    class="flex size-11 items-center justify-center rounded-full bg-black/20 text-white backdrop-blur-sm transition-colors hover:bg-black/35 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#eccd31]">
                    <x-tabler-menu-2 class="size-6" stroke-width="1.5" />
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Full-screen mobile navigation lives outside the transformed header, so it fills the viewport. -->
<div id="site-mobile-menu" data-site-mobile-menu role="dialog" aria-modal="true" aria-label="Меню сайта" aria-hidden="true"
    class="fixed inset-0 z-[70] hidden overflow-y-auto overscroll-contain bg-[#102a27] text-white md:hidden">
    <div class="relative flex min-h-[100dvh] flex-col overflow-hidden px-6 pb-[calc(2rem+env(safe-area-inset-bottom))] pt-[calc(1.5rem+env(safe-area-inset-top))] sm:px-10">
        <div aria-hidden="true" class="pointer-events-none absolute -right-32 -top-24 size-[420px] rounded-full bg-[#27624e]/25 blur-[110px]"></div>

        <div class="relative flex shrink-0 items-center justify-between gap-4">
            <a href="/" class="text-base font-medium tracking-[0.04em] text-white">Visita Caribe</a>
            <button data-site-menu-close type="button" aria-label="Закрыть меню"
                class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#eccd31]">
                <x-tabler-x class="size-6" stroke-width="1.5" />
            </button>
        </div>

        <div class="relative flex flex-1 flex-col justify-center py-10">
            <p class="mb-7 text-xs tracking-[0.08em] text-white/45">Разделы сайта</p>
            <nav aria-label="Основная навигация" class="flex flex-col gap-2">
                <a href="/" class="group flex items-baseline gap-5 py-2.5 transition-colors {{ request()->routeIs('home') ? 'text-[#eccd31]' : 'text-white hover:text-[#eccd31]' }}">
                    <span class="w-6 shrink-0 text-xs font-normal tabular-nums text-[#eccd31]/65">01</span>
                    <span class="text-[32px] font-normal tracking-tight transition-transform duration-200 group-hover:translate-x-1 sm:text-[38px]">Главная</span>
                </a>
                <a href="/tours" class="group flex items-baseline gap-5 py-2.5 transition-colors {{ request()->is('tours', 'tours/*') ? 'text-[#eccd31]' : 'text-white hover:text-[#eccd31]' }}">
                    <span class="w-6 shrink-0 text-xs font-normal tabular-nums text-[#eccd31]/65">02</span>
                    <span class="text-[32px] font-normal tracking-tight transition-transform duration-200 group-hover:translate-x-1 sm:text-[38px]">Туры</span>
                </a>
                <a href="/realestate" class="group flex items-baseline gap-5 py-2.5 transition-colors {{ request()->is('realestate', 'realestate/*') ? 'text-[#eccd31]' : 'text-white hover:text-[#eccd31]' }}">
                    <span class="w-6 shrink-0 text-xs font-normal tabular-nums text-[#eccd31]/65">03</span>
                    <span class="text-[32px] font-normal tracking-tight transition-transform duration-200 group-hover:translate-x-1 sm:text-[38px]">Недвижимость</span>
                </a>
                <a href="/blog" class="group flex items-baseline gap-5 py-2.5 transition-colors {{ request()->is('blog', 'blog/*') ? 'text-[#eccd31]' : 'text-white hover:text-[#eccd31]' }}">
                    <span class="w-6 shrink-0 text-xs font-normal tabular-nums text-[#eccd31]/65">04</span>
                    <span class="text-[32px] font-normal tracking-tight transition-transform duration-200 group-hover:translate-x-1 sm:text-[38px]">Блог</span>
                </a>
                <a href="/contact" class="group flex items-baseline gap-5 py-2.5 transition-colors {{ request()->routeIs('contact') ? 'text-[#eccd31]' : 'text-white hover:text-[#eccd31]' }}">
                    <span class="w-6 shrink-0 text-xs font-normal tabular-nums text-[#eccd31]/65">05</span>
                    <span class="text-[32px] font-normal tracking-tight transition-transform duration-200 group-hover:translate-x-1 sm:text-[38px]">Контакты</span>
                </a>
            </nav>
        </div>

        <div class="relative flex shrink-0 items-end justify-between gap-4">
            <div class="flex items-center gap-1">
                <a href="https://www.instagram.com/helenkavivas" target="_blank" rel="noopener noreferrer nofollow" aria-label="Instagram"
                    class="flex size-11 items-center justify-center rounded-full text-white/70 transition-colors hover:bg-white/10 hover:text-white">
                    <x-tabler-brand-instagram class="size-5" stroke-width="1.5" />
                </a>
                <a href="https://www.facebook.com/helenkavivas" target="_blank" rel="noopener noreferrer nofollow" aria-label="Facebook"
                    class="flex size-11 items-center justify-center rounded-full text-white/70 transition-colors hover:bg-white/10 hover:text-white">
                    <x-tabler-brand-facebook class="size-5" stroke-width="1.5" />
                </a>
                <a href="https://www.youtube.com/@ElenaVivas" target="_blank" rel="noopener noreferrer nofollow" aria-label="YouTube"
                    class="flex size-11 items-center justify-center rounded-full text-white/70 transition-colors hover:bg-white/10 hover:text-white">
                    <x-tabler-brand-youtube class="size-5" stroke-width="1.5" />
                </a>
            </div>
            <p class="max-w-[150px] text-right text-[11px] leading-5 text-white/45">Путешествия и недвижимость в Латинской Америке</p>
        </div>
    </div>
</div>
