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
    const isOpen = () => !siteMenu.classList.contains('hidden');

    const setOpen = (open, restoreFocus = true) => {
        siteMenu.classList.toggle('hidden', !open);
        siteMenu.setAttribute('aria-hidden', String(!open));
        menuToggle.setAttribute('aria-expanded', String(open));
        menuToggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
        document.body.classList.toggle('overflow-hidden', open);

        if (open) menuClose.focus();
        else if (restoreFocus) menuToggle.focus();
    };

    menuToggle.addEventListener('click', () => setOpen(!isOpen()));
    menuClose.addEventListener('click', () => setOpen(false));

    siteMenu.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => setOpen(false, false));
    });

    document.addEventListener('keydown', (event) => {
        if (!isOpen()) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false);
            return;
        }

        if (event.key !== 'Tab') return;

        const linksAndButtons = [...siteMenu.querySelectorAll('a[href], button:not([disabled])')];
        const first = linksAndButtons[0];
        const last = linksAndButtons[linksAndButtons.length - 1];
        if (!first || !last) return;

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768 && isOpen()) setOpen(false, false);
    });
}
