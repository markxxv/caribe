export function initListingFilters() {
    document.querySelectorAll('[data-listing-filters]').forEach((form) => {
        const countryInput = form.querySelector('[data-country-value]');
        const countrySelect = form.querySelector('[data-country-select]');
        const countryWrap = form.querySelector('[data-listing-country]');
        const searchPanel = form.querySelector('[data-listing-search]');
        const searchInput = form.querySelector('[name="search"]');
        const openButton = form.querySelector('[data-listing-open-search]');
        const closeButton = form.querySelector('[data-listing-close-search]');
        let searchOpen = false;

        const setSearchOpen = (open, focus = true) => {
            if (!openButton || !searchPanel || !countryWrap || !closeButton) return;
            searchOpen = open;

            countryWrap.classList.toggle('hidden', open);
            countryWrap.classList.toggle('flex', !open);

            searchPanel.classList.toggle('hidden', !open);
            searchPanel.classList.toggle('flex', open);

            openButton.classList.toggle('hidden', open);
            openButton.classList.toggle('inline-flex', !open);
            openButton.setAttribute('aria-expanded', String(open));

            closeButton.classList.toggle('hidden', !open);
            closeButton.classList.toggle('inline-flex', open);

            if (open && focus) searchInput?.focus({ preventScroll: true });
            if (!open) {
                // Discard unsent edits when closing; switching country keeps the applied search.
                searchInput.value = searchInput.defaultValue;
                if (focus) openButton.focus();
            }
        };

        openButton?.addEventListener('click', () => setSearchOpen(true));
        closeButton?.addEventListener('click', () => setSearchOpen(false));

        form.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && searchOpen) {
                event.preventDefault();
                setSearchOpen(false);
            }
        });

        window.addEventListener('resize', () => {
            if (searchOpen && window.innerWidth >= 768) setSearchOpen(false, false);
        });

        const navigate = () => {
            const country = countrySelect?.value || countryInput?.value || 'all';
            const search = searchInput?.value.trim().replace(/[\\/]+/g, ' ').replace(/\s+/g, ' ');
            const segments = [form.dataset.base.replace(/\/$/, '')];

            if (country && country !== 'all') {
                if (form.dataset.tours === '1' && country === 'argentina') {
                    segments.push('argentina');
                } else {
                    segments.push('country', encodeURIComponent(country));
                }
            }
            if (search) segments.push('search', encodeURIComponent(search));

            window.location.assign(segments.join('/'));
        };

        countrySelect?.addEventListener('change', () => {
            if (countryInput) countryInput.value = countrySelect.value;
            navigate();
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            navigate();
        });
    });
}
