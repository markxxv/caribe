export function initRealestateCatalog() {
    const form = document.querySelector('[data-catalog-search]');
    if (!form) return;

    const field = (name) => form.querySelector('[data-field="' + name + '"]');
    const panel = form;
    const openButton = document.querySelector('[data-catalog-open]');
    const closeButton = form.querySelector('[data-catalog-close]');
    const locationInput = form.querySelector('[data-location-input]');
    const locationWrap = form.querySelector('[data-location-wrap]');
    const locationOptions = form.querySelector('[data-location-options]');
    const locationEmpty = form.querySelector('[data-location-empty]');
    const locations = Array.from(form.querySelectorAll('[data-location-option]'));
    const priceToggle = form.querySelector('[data-price-toggle]');
    const pricePanel = form.querySelector('[data-price-panel]');
    const priceLabel = form.querySelector('[data-price-label]');
    const advancedToggle = form.querySelector('[data-advanced-toggle]');
    const advanced = form.querySelector('[data-advanced]');
    const chevron = form.querySelector('[data-advanced-chevron]');
    const sorting = document.querySelector('[data-catalog-sort]');
    let location = form.dataset.locationValue || '';

    const normalize = (value) => String(value || '')
        .toLocaleLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const closeLocations = () => {
        locationOptions.classList.add('hidden');
        locationInput.setAttribute('aria-expanded', 'false');
    };

    const closePrice = () => {
        pricePanel.classList.add('hidden');
        priceToggle.setAttribute('aria-expanded', 'false');
    };

    function renderLocations() {
        const query = normalize(locationInput.value.trim());
        let shown = 0;
        for (const item of locations) {
            const matches = normalize(item.dataset.search).includes(query) && shown < 50;
            item.classList.toggle('hidden', !matches);
            if (matches) shown++;
        }
        locationEmpty.classList.toggle('hidden', shown !== 0);
        locationOptions.classList.remove('hidden');
        locationInput.setAttribute('aria-expanded', 'true');
    }

    locationInput.addEventListener('focus', renderLocations);
    locationInput.addEventListener('input', () => {
        location = '';
        renderLocations();
    });

    for (const item of locations) {
        item.addEventListener('click', () => {
            location = item.dataset.value || '';
            locationInput.value = location ? item.querySelector('span')?.textContent.trim() || '' : '';
            closeLocations();
        });
    }

    priceToggle.addEventListener('click', () => {
        closeLocations();
        const isOpening = pricePanel.classList.contains('hidden');
        pricePanel.classList.toggle('hidden', !isOpening);
        priceToggle.setAttribute('aria-expanded', String(isOpening));
    });

    function refreshPriceLabel() {
        const min = field('price-min').value.trim();
        const max = field('price-max').value.trim();
        const currency = field('currency').value;
        priceLabel.textContent = (min || max)
            ? ((min ? 'от ' + Number(min).toLocaleString('ru-RU') : '') +
                (min && max ? ' — ' : '') +
                (max ? 'до ' + Number(max).toLocaleString('ru-RU') : '') +
                (currency ? ' ' + currency : ''))
            : (currency ? 'Валюта: ' + currency : 'Любая цена');
    }

    for (const control of [field('price-min'), field('price-max'), field('currency')]) {
        control.addEventListener('input', refreshPriceLabel);
        control.addEventListener('change', refreshPriceLabel);
    }

    form.querySelector('[data-price-apply]').addEventListener('click', () => {
        if (!validateRange('price-min', 'price-max')) return;
        if ((field('price-min').value || field('price-max').value) && !field('currency').value) {
            field('currency').value = 'USD';
        }
        refreshPriceLabel();
        closePrice();
    });

    advancedToggle.addEventListener('click', () => {
        const isOpening = advanced.classList.contains('hidden');
        advanced.classList.toggle('hidden', !isOpening);
        advancedToggle.setAttribute('aria-expanded', String(isOpening));
        chevron.classList.toggle('rotate-180', isOpening);
    });

    const hasAdvancedValues = ['beds', 'baths', 'area-min', 'area-max', 'offer', 'ref']
        .some((name) => field(name).value !== '');
    if (hasAdvancedValues) advancedToggle.click();

    function openMobile() {
        panel.classList.remove('hidden');
        panel.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        closeButton.focus();
    }

    function closeMobile() {
        panel.classList.add('hidden');
        panel.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        closeLocations();
        closePrice();
        openButton.focus();
    }

    openButton.addEventListener('click', openMobile);
    closeButton.addEventListener('click', closeMobile);

    document.addEventListener('click', (event) => {
        if (!locationWrap.contains(event.target)) closeLocations();
        if (!form.querySelector('[data-price-wrap]').contains(event.target)) closePrice();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        closePrice();
        closeLocations();
        if (window.innerWidth < 1024 && !panel.classList.contains('hidden')) closeMobile();
    });

    // Prevent page scrolling from remaining locked when switching to desktop.
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) document.body.classList.remove('overflow-hidden');
    });

    function validateRange(minName, maxName) {
        const min = field(minName);
        const max = field(maxName);
        min.setCustomValidity('');
        max.setCustomValidity('');

        if (!min.checkValidity()) return min.reportValidity();
        if (!max.checkValidity()) return max.reportValidity();
        if (min.value && max.value && Number(min.value) > Number(max.value)) {
            max.setCustomValidity('Максимальное значение должно быть не меньше минимального');
            max.reportValidity();
            return false;
        }
        return true;
    }

    function searchUrl() {
        if (!validateRange('price-min', 'price-max') || !validateRange('area-min', 'area-max')) return null;
        if (!field('ref').checkValidity()) return field('ref').reportValidity() && null;

        const segments = ['/realestate'];
        const deal = form.querySelector('[data-deal]:checked')?.value;
        if (deal) segments.push(deal);
        if (location) segments.push('in', ...location.split('/'));

        const type = field('type').value;
        if (type) segments.push('type', type);

        const minPrice = field('price-min').value.trim();
        const maxPrice = field('price-max').value.trim();
        let currency = field('currency').value;
        if ((minPrice || maxPrice) && !currency) {
            currency = 'USD';
            field('currency').value = currency;
            refreshPriceLabel();
        }
        if (currency) segments.push('currency', currency);
        if (minPrice || maxPrice) segments.push('price', minPrice + '-' + maxPrice);

        const minArea = field('area-min').value.trim();
        const maxArea = field('area-max').value.trim();
        if (minArea || maxArea) segments.push('area', minArea + '-' + maxArea);

        for (const key of ['beds', 'baths', 'ref']) {
            const value = field(key).value;
            if (value) segments.push(key, value);
        }

        const offer = field('offer').value;
        if (offer) segments.push(offer);

        const sort = sorting?.value || 'recent';
        if (sort !== 'recent') segments.push('sort', sort);

        return segments.join('/');
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const url = searchUrl();
        if (url) window.location.assign(url);
    });

    sorting?.addEventListener('change', () => {
        const url = searchUrl();
        if (url) window.location.assign(url);
    });
}
