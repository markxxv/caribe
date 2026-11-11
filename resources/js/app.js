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
