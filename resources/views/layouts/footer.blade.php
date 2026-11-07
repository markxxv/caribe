<footer id="site_footer" class="relative overflow-hidden bg-[#102a27] text-white">
    <div class="pointer-events-none absolute -right-36 -top-48 size-[460px] rounded-full bg-emerald-600/10 blur-3xl" aria-hidden="true"></div>

    <div class="container relative mx-auto px-4 py-14 md:px-8 md:py-20">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-4">
                <a href="/" class="inline-flex items-center gap-4 text-white transition-opacity hover:opacity-80" aria-label="Visita Caribe — на главную">
                    <img src="/img/logo.svg" alt="" class="h-12 w-auto" width="43" height="48" loading="lazy">
                    <span class="text-sm font-medium uppercase tracking-[0.2em]">Visita Caribe</span>
                </a>

                <p class="mt-7 max-w-sm text-2xl font-normal leading-snug tracking-tight text-white md:text-[28px]">
                    Ваш проводник по Латинской Америке.
                </p>
                <p class="mt-4 max-w-xs text-sm leading-7 text-white/55">
                    Авторские экскурсии и путешествия с Еленой Вивас — от Буэнос-Айреса до Патагонии.
                </p>
            </div>

            <nav aria-label="Туры и экскурсии" class="lg:col-span-5">
                <h2 class="text-[11px] font-medium uppercase tracking-[0.2em] text-[#eccd31]">Туры и экскурсии</h2>
                <div class="mt-6 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                    <a href="/tours/argentina" class="group inline-flex items-start justify-between gap-3 text-sm leading-6 text-white/75 transition-colors hover:text-white">
                        <span>Туры по Аргентине</span>
                        <x-tabler-arrow-up-right class="mt-1 size-4 shrink-0 text-white/35 transition-all group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-[#eccd31]" stroke-width="1.5" />
                    </a>
                    <a href="/tours/istoricheskiy-siti-tur-po-buenos-ayresu" class="group inline-flex items-start justify-between gap-3 text-sm leading-6 text-white/75 transition-colors hover:text-white">
                        <span>Сити-тур по Буэнос-Айресу</span>
                        <x-tabler-arrow-up-right class="mt-1 size-4 shrink-0 text-white/35 transition-all group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-[#eccd31]" stroke-width="1.5" />
                    </a>
                    <a href="/tours/tur-v-bariloche-s-reyndzherom-4kh4" class="group inline-flex items-start justify-between gap-3 text-sm leading-6 text-white/75 transition-colors hover:text-white">
                        <span>Тур в Барилоче</span>
                        <x-tabler-arrow-up-right class="mt-1 size-4 shrink-0 text-white/35 transition-all group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-[#eccd31]" stroke-width="1.5" />
                    </a>
                    <a href="/tours/gastronomicheskiy-tur-po-buenos-ayresu" class="group inline-flex items-start justify-between gap-3 text-sm leading-6 text-white/75 transition-colors hover:text-white">
                        <span>Гастрономический тур в Аргентине</span>
                        <x-tabler-arrow-up-right class="mt-1 size-4 shrink-0 text-white/35 transition-all group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-[#eccd31]" stroke-width="1.5" />
                    </a>
                </div>
            </nav>

            <div class="lg:col-span-3">
                <h2 class="text-[11px] font-medium uppercase tracking-[0.2em] text-[#eccd31]">Контакты</h2>
                <p class="mt-6 max-w-xs text-sm leading-6 text-white/55">Поможем выбрать маршрут и спланировать поездку.</p>
                <address class="mt-6 flex flex-col gap-4 not-italic">
                    <a href="https://t.me/elenavivas" target="_blank" rel="noopener noreferrer" class="group inline-flex items-start gap-3 text-sm leading-6 text-white/85 transition-colors hover:text-white">
                        <x-tabler-brand-telegram class="mt-0.5 size-5 shrink-0 text-[#eccd31]" stroke-width="1.5" />
                        <span class="group-hover:underline">@elenavivas</span>
                    </a>
                    <a href="mailto:elena.vivas.garcia@gmail.com" class="group inline-flex items-start gap-3 text-sm leading-6 text-white/85 transition-colors hover:text-white">
                        <x-tabler-mail class="mt-0.5 size-5 shrink-0 text-[#eccd31]" stroke-width="1.5" />
                        <span class="break-all group-hover:underline">elena.vivas.garcia@gmail.com</span>
                    </a>
                    <a href="tel:+5491137630036" class="group inline-flex items-start gap-3 text-sm leading-6 text-white/85 transition-colors hover:text-white">
                        <x-tabler-phone class="mt-0.5 size-5 shrink-0 text-[#eccd31]" stroke-width="1.5" />
                        <span class="group-hover:underline">+54 9 11 3763 0036</span>
                    </a>
                </address>
            </div>
        </div>

        <div class="mt-12 h-px bg-white/10 md:mt-16" aria-hidden="true"></div>
        <div class="flex flex-col gap-5 pt-6 text-xs leading-6 text-white/45 md:flex-row md:items-start md:justify-between">
            <p>
                © {{ date('Y') }} Visita Caribe. Все права защищены.
                <span class="block">Копирование контента с сайта не допускается без согласования.</span>
            </p>
            <a href="https://vivasart.com/" target="_blank" rel="noopener noreferrer" class="w-fit transition-colors hover:text-white">
                Designed by Mark Vi
            </a>
        </div>
    </div>
</footer>

{{-- Floating WhatsApp booking widget, shared by all pages through the layout. --}}
<a href="https://wa.me/34660024093" target="_blank" rel="noopener noreferrer" aria-label="Заказать экскурсию в WhatsApp" class="group fixed bottom-[calc(1rem+env(safe-area-inset-bottom))] right-4 z-[60] flex items-center gap-3 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#43ad69] md:bottom-7 md:right-7 md:gap-5">
    <span class="rounded-full bg-black px-4 py-3 text-xs font-normal text-white shadow-[0_10px_30px_rgba(0,0,0,0.18)] transition-colors group-hover:bg-[#192b24] sm:px-5 sm:text-base">
        Заказать экскурсию
    </span>
    <span class="flex size-16 shrink-0 items-center justify-center rounded-full bg-[#43ad69] text-white shadow-[0_12px_35px_rgba(28,112,57,0.22)] transition-transform duration-200 group-hover:scale-105 sm:size-[76px]" aria-hidden="true">
        <x-tabler-brand-whatsapp class="size-8 sm:size-10" stroke-width="1.8" />
    </span>
</a>
