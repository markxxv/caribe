@section('metaTitle', 'Контакты Елены Вивас — туры по Аргентине | Visita Caribe')
@section('metaDescription', 'Свяжитесь с Еленой Вивас для организации туров и экскурсий по Аргентине. Telegram @elenavivas, телефон +54 9 11 3763 0036 и электронная почта.')
@section('canonicalUrl', route('contact'))

<x-layout>
    <main class="bg-[#f7f8f5]">
        <section class="relative isolate overflow-hidden bg-[#102a27] text-white">
            <div class="pointer-events-none absolute inset-0 bg-[url('/img/main_head_background.webp')] bg-cover bg-center opacity-20" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-[#102a27] via-[#102a27]/95 to-[#102a27]/65" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -right-40 -bottom-64 size-[550px] rounded-full bg-[#eccd31]/10 blur-3xl" aria-hidden="true"></div>

            <div class="container relative mx-auto px-4 pb-24 pt-36 md:px-8 md:pb-32 md:pt-48">
                <div class="max-w-4xl">
                    <p class="flex items-center gap-3 text-[11px] font-medium uppercase tracking-[0.24em] text-[#eccd31]">
                        <span class="size-1.5 rounded-full bg-[#eccd31]"></span>
                        Visita Caribe / Контакты
                    </p>

                    <h1 class="mt-7 max-w-3xl text-4xl font-medium tracking-tight sm:text-5xl lg:text-6xl">
                        Давайте обсудим <span class="text-[#eccd31]">ваше путешествие.</span>
                    </h1>

                    <p class="mt-7 max-w-xl text-sm leading-7 text-white/75 md:text-base md:leading-8">
                        Планируете поездку в Аргентину или выбираете экскурсию? Напишите Елене Вивас — обсудим ваши планы и подберём подходящий маршрут.
                    </p>

                    <a href="https://t.me/elenavivas" target="_blank" rel="noopener noreferrer" class="group mt-9 inline-flex min-h-13 items-center justify-center gap-3 rounded-full bg-[#eccd31] px-6 py-3.5 text-sm font-medium text-[#102a27] transition-colors hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#eccd31]">
                        <x-tabler-brand-telegram class="size-5 shrink-0" stroke-width="1.7" />
                        Написать в Telegram
                        <x-tabler-arrow-up-right class="size-4 shrink-0 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" stroke-width="1.7" />
                    </a>

                    <p class="mt-4 text-xs text-white/50">Telegram: @elenavivas</p>
                </div>
            </div>
        </section>

        <section class="relative -mt-8 pb-16 md:-mt-12 md:pb-24" aria-labelledby="contact-options-title">
            <div class="container relative mx-auto px-4 md:px-8">
                <div class="grid gap-4 md:grid-cols-2 md:gap-6">
                    <a href="mailto:elena.vivas.garcia@gmail.com" class="group flex min-w-0 flex-col justify-between rounded-2xl bg-white p-6 shadow-[0_12px_40px_rgba(16,42,39,0.06)] transition-transform duration-300 hover:-translate-y-1 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#102a27] sm:p-8">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex size-11 items-center justify-center rounded-full bg-[#eef3ec] text-[#102a27]">
                                <x-tabler-mail class="size-5" stroke-width="1.5" />
                            </span>
                            <x-tabler-arrow-up-right class="size-5 text-[#102a27]/40 transition-all group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-[#102a27]" stroke-width="1.5" />
                        </div>
                        <div class="mt-12">
                            <p class="text-xs uppercase tracking-[0.18em] text-[#62736e]">Электронная почта</p>
                            <h2 id="contact-options-title" class="mt-3 break-all text-lg font-medium tracking-tight text-[#102a27] sm:text-xl md:break-normal">elena.vivas.garcia@gmail.com</h2>
                            <p class="mt-3 text-sm text-[#62736e]">Для вопросов о турах и сотрудничестве</p>
                        </div>
                    </a>

                    <a href="tel:+5491137630036" class="group flex min-w-0 flex-col justify-between rounded-2xl bg-white p-6 shadow-[0_12px_40px_rgba(16,42,39,0.06)] transition-transform duration-300 hover:-translate-y-1 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#102a27] sm:p-8">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex size-11 items-center justify-center rounded-full bg-[#eef3ec] text-[#102a27]">
                                <x-tabler-phone class="size-5" stroke-width="1.5" />
                            </span>
                            <x-tabler-arrow-up-right class="size-5 text-[#102a27]/40 transition-all group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-[#102a27]" stroke-width="1.5" />
                        </div>
                        <div class="mt-12">
                            <p class="text-xs uppercase tracking-[0.18em] text-[#62736e]">Телефон</p>
                            <h2 class="mt-3 text-xl font-medium tracking-tight text-[#102a27] sm:text-2xl">+54 9 11 3763 0036</h2>
                            <p class="mt-3 text-sm text-[#62736e]">Звонки из любой страны</p>
                        </div>
                    </a>
                </div>

                <div class="mt-12 flex flex-col gap-5 md:mt-16 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.16em] text-[#6d847a]">Планируете маршрут?</p>
                        <p class="mt-2 max-w-xl text-lg font-medium text-[#102a27]">Посмотрите наши туры и выберите направление.</p>
                    </div>
                    <a href="/tours/argentina" class="group inline-flex items-center gap-2 text-sm font-medium text-[#102a27] transition-colors hover:text-emerald-700">
                        Смотреть туры по Аргентине
                        <x-tabler-arrow-up-right class="size-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" stroke-width="1.5" />
                    </a>
                </div>
            </div>
        </section>
    </main>
</x-layout>
