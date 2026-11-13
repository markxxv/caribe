export function initListingFilters() {
    document.querySelectorAll('[data-listing-filters]').forEach((form) => {
        const countryInput = form.querySelector('[data-country-value]');
        const countrySelect = form.querySelector('[data-country-select]');
        const searchInput = form.querySelector('[name="search"]');

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
            countryInput.value = countrySelect.value;
            navigate();
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            navigate();
        });
    });
}
