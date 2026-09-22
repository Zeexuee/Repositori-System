import Alpine from 'alpinejs';

// Pastikan tampilan zoom otomatis 90% untuk seluruh browser & proses sistem
const applySystemZoom = () => {
    if (typeof document === 'undefined') return;

    if (document.documentElement) {
        // Terapkan native zoom 90% / 0.9
        document.documentElement.style.zoom = '90%';

        // Fallback jika browser lawas tidak mendukung CSS zoom
        if (!('zoom' in document.documentElement.style) && (!window.CSS || !CSS.supports || !CSS.supports('zoom', '0.9'))) {
            document.documentElement.style.transform = 'scale(0.9)';
            document.documentElement.style.transformOrigin = 'top center';
            document.documentElement.style.width = '111.1111%';
        }
    }
};

applySystemZoom();
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applySystemZoom);
}

window.Alpine = Alpine;

Alpine.start();
