import './bootstrap';

// Keep Embla out of the bundle used by other pages.
if (document.querySelector('[data-realestate-gallery]')) {
    import('./realestate-gallery.js').then(({ initRealestateGalleries }) => {
        initRealestateGalleries();
    });
}
