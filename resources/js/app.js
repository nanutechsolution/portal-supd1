/**
 * Portal SUPD I — interaksi ringan tanpa dependency tambahan.
 */
document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initGalleryLightbox();
    initCopyLinks();
    initRubrikScroller();
});

function initMobileMenu() {
    const toggle = document.querySelector('[data-mobile-toggle]');
    const menu = document.querySelector('[data-mobile-menu]');

    if (!toggle || !menu) {
        return;
    }

    const setOpen = (open) => {
        menu.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
        setOpen(menu.classList.contains('hidden'));
    });

    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });
}

function initGalleryLightbox() {
    const modal = document.querySelector('[data-lightbox]');

    if (!modal) {
        return;
    }

    const image = modal.querySelector('[data-lightbox-image]');
    const caption = modal.querySelector('[data-lightbox-caption]');
    const closeButton = modal.querySelector('[data-lightbox-close]');

    const close = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        image.setAttribute('src', '');
    };

    document.querySelectorAll('[data-lightbox-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            image.setAttribute('src', trigger.dataset.src);
            image.setAttribute('alt', trigger.dataset.title || '');
            caption.textContent = trigger.dataset.title || '';
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        });
    });

    closeButton?.addEventListener('click', close);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            close();
        }
    });
}

function initRubrikScroller() {
    const scroller = document.querySelector('[data-rubrik-scroll]');

    if (!scroller) {
        return;
    }

    const strip = scroller.parentElement;
    const prev = strip.querySelector('[data-rubrik-prev]');
    const next = strip.querySelector('[data-rubrik-next]');
    const fadeLeft = strip.querySelector('[data-rubrik-fade="left"]');
    const fadeRight = strip.querySelector('[data-rubrik-fade="right"]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const setVisible = (element, visible) => {
        if (!element) {
            return;
        }

        element.classList.toggle('opacity-0', !visible);
        element.classList.toggle('pointer-events-none', !visible);
    };

    const update = () => {
        const maxScroll = Math.max(0, scroller.scrollWidth - scroller.clientWidth);
        const atStart = scroller.scrollLeft <= 1;
        const atEnd = scroller.scrollLeft >= maxScroll - 1;
        const scrollable = maxScroll > 1;

        const canGoBack = scrollable && !atStart;
        const canGoForward = scrollable && !atEnd;

        setVisible(prev, canGoBack);
        setVisible(next, canGoForward);
        setVisible(fadeLeft, canGoBack);
        setVisible(fadeRight, canGoForward);

        const syncButton = (button, visible, fallback, fallbackVisible) => {
            if (!button) {
                return;
            }

            if (!visible && document.activeElement === button) {
                if (fallback && fallbackVisible) {
                    fallback.focus({ preventScroll: true });
                } else {
                    button.blur();
                }
            }

            button.setAttribute('aria-hidden', visible ? 'false' : 'true');
            button.tabIndex = visible ? 0 : -1;
        };

        syncButton(prev, canGoBack, next, canGoForward);
        syncButton(next, canGoForward, prev, canGoBack);
    };

    const scrollByPage = (direction) => {
        const amount = Math.max(160, Math.round(scroller.clientWidth * 0.6));

        scroller.scrollBy({
            left: direction * amount,
            behavior: reduceMotion.matches ? 'auto' : 'smooth',
        });
    };

    prev?.addEventListener('click', () => scrollByPage(-1));
    next?.addEventListener('click', () => scrollByPage(1));
    scroller.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);

    if ('ResizeObserver' in window) {
        new ResizeObserver(update).observe(scroller);
    }

    document.fonts?.ready.then(update);
    update();
}

function initCopyLinks() {
    document.querySelectorAll('[data-copy-link]').forEach((button) => {
        button.addEventListener('click', async () => {
            const original = button.dataset.label || button.textContent.trim();

            try {
                await navigator.clipboard.writeText(window.location.href);
                button.textContent = 'Tautan disalin';
            } catch {
                button.textContent = 'Salin manual';
            }

            window.setTimeout(() => {
                button.textContent = original;
            }, 2200);
        });
    });
}
