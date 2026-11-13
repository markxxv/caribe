import EmblaCarousel from 'embla-carousel';

export function initCategoryCarousels() {
    document.querySelectorAll('[data-category-carousel]').forEach((root) => {
        const viewport = root.querySelector('[data-carousel-viewport]');
        if (!viewport) return;

        const embla = EmblaCarousel(viewport, {
            align: 'start',
            containScroll: 'trimSnaps',
            slidesToScroll: 'auto',
        });
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const prev = root.querySelector('[data-carousel-prev]');
        const next = root.querySelector('[data-carousel-next]');

        function update() {
            const canPrev = embla.canScrollPrev();
            const canNext = embla.canScrollNext();

            if (prev) prev.disabled = !canPrev;
            if (next) next.disabled = !canNext;
            // Все категории помещаются на экране: стрелки не нужны.
            prev?.classList.toggle('md:invisible', !canPrev && !canNext);
            next?.classList.toggle('md:invisible', !canPrev && !canNext);
        }

        prev?.addEventListener('click', () => embla.scrollPrev(reduceMotion));
        next?.addEventListener('click', () => embla.scrollNext(reduceMotion));
        embla.on('select', update).on('reInit', update);
        update();

        // На странице категории показываем выбранную категорию без прокрутки к началу.
        const active = root.querySelector('[data-active]');
        if (active) {
            const slideIndex = embla.slideNodes().indexOf(active);
            const groups = embla.internalEngine?.().slideRegistry ?? [];
            const snap = groups.findIndex((group) => group.includes(slideIndex));
            if (snap > 0) embla.scrollTo(snap, true);
        }
    });
}
