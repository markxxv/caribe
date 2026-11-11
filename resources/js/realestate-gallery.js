import EmblaCarousel from 'embla-carousel';

export function initRealestateGalleries() {
    document.querySelectorAll('[data-realestate-gallery]').forEach((root) => {
        const viewport = root.querySelector('[data-gallery-viewport]');
        const thumbList = root.querySelector('[data-gallery-thumbnails]');
        const thumbs = [...root.querySelectorAll('[data-gallery-thumb]')];

        if (!viewport || thumbs.length < 2) return;

        const embla = EmblaCarousel(viewport, {
            align: 'start',
            containScroll: false,
            loop: true,
            slidesToScroll: 1,
        });
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const counter = root.querySelector('[data-gallery-counter]');

        function showSelected() {
            const index = embla.selectedScrollSnap() % thumbs.length;

            if (counter) counter.textContent = (index + 1) + ' / ' + thumbs.length;

            thumbs.forEach((thumb, i) => {
                const active = i === index;
                thumb.setAttribute('aria-pressed', String(active));
                thumb.classList.toggle('opacity-100', active);
                thumb.classList.toggle('opacity-50', !active);
            });

            const activeThumb = thumbs[index];
            if (!activeThumb || !thumbList) return;

            const listBox = thumbList.getBoundingClientRect();
            const thumbBox = activeThumb.getBoundingClientRect();
            const left = thumbList.scrollLeft + thumbBox.left - listBox.left
                - (thumbList.clientWidth - thumbBox.width) / 2;

            thumbList.scrollTo({
                left,
                behavior: reduceMotion ? 'auto' : 'smooth',
            });
        }

        function move(direction) {
            // Embla may disable its internal loop when there are too few slides.
            const current = embla.selectedScrollSnap();

            if (direction === -1 && !embla.canScrollPrev()) {
                embla.scrollTo(embla.scrollSnapList().length - 1, reduceMotion);
            } else if (direction === 1 && !embla.canScrollNext()) {
                embla.scrollTo(0, reduceMotion);
            } else if (direction === -1) {
                embla.scrollPrev(reduceMotion);
            } else {
                embla.scrollNext(reduceMotion);
            }

            if (embla.selectedScrollSnap() === current) showSelected();
        }

        root.querySelector('[data-gallery-prev]')?.addEventListener('click', () => move(-1));
        root.querySelector('[data-gallery-next]')?.addEventListener('click', () => move(1));

        thumbs.forEach((thumb, index) => {
            thumb.addEventListener('click', () => {
                const current = embla.selectedScrollSnap();
                const candidates = [index];

                if (embla.scrollSnapList().length > thumbs.length) {
                    candidates.push(index + thumbs.length);
                }

                const nearest = candidates.reduce((best, choice) =>
                    Math.abs(choice - current) < Math.abs(best - current) ? choice : best
                );

                embla.scrollTo(nearest, reduceMotion);
            });
        });

        root.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                move(event.key === 'ArrowLeft' ? -1 : 1);
            }
        });

        embla.on('select', showSelected);
        embla.on('reInit', showSelected);
        showSelected();
    });
}
