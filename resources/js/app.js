import './bootstrap';

// Keep Embla out of the bundle used by other pages.
if (document.querySelector('[data-realestate-gallery]')) {
    import('./realestate-gallery.js').then(({ initRealestateGalleries }) => {
        initRealestateGalleries();
    });
}

if (document.querySelector('[data-catalog-search]')) {
    import('./realestate-catalog.js').then(({ initRealestateCatalog }) => {
        initRealestateCatalog();
    });
}

const siteMenu = document.querySelector('[data-site-mobile-menu]');
const menuToggle = document.querySelector('[data-site-menu-toggle]');
const menuClose = document.querySelector('[data-site-menu-close]');

if (siteMenu && menuToggle && menuClose) {
    const setOpen = (open) => {
        siteMenu.classList.toggle('hidden', !open);
        menuToggle.setAttribute('aria-expanded', String(open));
        menuToggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
        menuToggle.querySelector('[data-site-menu-open-icon]')?.classList.toggle('hidden', open);
        menuToggle.querySelector('[data-site-menu-close-icon]')?.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
        if (open) menuClose.focus();
        else menuToggle.focus();
    };

    menuToggle.addEventListener('click', () => setOpen(siteMenu.classList.contains('hidden')));
    menuClose.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !siteMenu.classList.contains('hidden')) setOpen(false);
    });
}
