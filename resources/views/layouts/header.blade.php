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
<header id="site_header" x-data="{mobileMenuOpen: false, langMenuOpen: false, currentLang: 'ru'}" class="fixed top-0 left-0 w-full z-50">
    <div class="container px-2 mx-auto">
        <div class="flex items-center justify-between py-6">
            <!-- Left Side -->
            <div class="w-1/3">
                <div class="md:flex hidden justify-start items-center space-x-8 text-xs uppercase tracking-widest relative z-10">
                    <a href="/services" class="nav-link text-white hover:text-gg-gold transition-colors duration-300">Услуги</a>
                    <a href="/tours" class="nav-link text-white hover:text-gg-gold transition-colors duration-300">Туры</a>
                    <a href="/blog" class="nav-link text-white hover:text-gg-gold transition-colors duration-300">Блог</a>
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

            <!-- Mobile Menu Button - Only visible on mobile -->
            <div class="md:hidden absolute right-4">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="text-white focus:outline-none">
                    <svg x-show="!mobileMenuOpen" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg x-show="mobileMenuOpen" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>


        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-black/95 backdrop-blur-md" style="display: none;">
        <div class="relative h-screen flex flex-col justify-center items-center p-8 space-y-8 text-center bg-black/90 backdrop-blur-sm">
            <button @click="mobileMenuOpen = false" class="absolute top-6 right-6 text-white focus:outline-none">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="">
                <img src="/img/logo.png" alt="Elena Vivas - Туры в Аргентине" class="w-full h-full object-contain max-w-[180px]">
            </div>

            <nav class="flex flex-col space-y-6 text-sm uppercase tracking-widest">
                <a href="/" class="text-white hover:text-gg-gold transition-colors duration-300">Главная</a>
                <a href="/tours" class="text-white hover:text-gg-gold transition-colors duration-300">Туры</a>
                <a href="/blog" class="text-white hover:text-gg-gold transition-colors duration-300">Блог</a>
                <a href="/about" class="text-white hover:text-gg-gold transition-colors duration-300">Обо мне</a>
                <a href="/contact" class="text-white hover:text-gg-gold transition-colors duration-300">Контакты</a>
            </nav>

            <div class="pt-8 mt-8 border-t border-white/10 w-24"></div>

            <div class="flex items-center space-x-6">
                <a href="https://www.instagram.com/helenkavivas" rel=" nofollow" target="_blank" class="text-white/60 hover:text-white transition-colors duration-300">
                    <x-tabler-brand-instagram class="w-6 h-6" stroke-width="1.2"  />
                </a>
                <a href="https://www.facebook.com/helenkavivas" rel=" nofollow" target="_blank"  class="text-white/60 hover:text-white transition-colors duration-300">
                    <x-tabler-brand-facebook class="w-6 h-6" stroke-width="1.2" />
                </a>
                <a href="https://www.youtube.com/@ElenaVivas" rel=" nofollow" target="_blank" class="text-white/60 hover:text-white transition-colors duration-300">
                    <x-tabler-brand-youtube class="w-6 h-6" stroke-width="1.2" />
                </a>
            </div>
        </div>
    </div>
</header>
