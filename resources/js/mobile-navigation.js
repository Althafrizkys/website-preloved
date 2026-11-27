document.addEventListener('DOMContentLoaded', () => {
    const navigation = document.querySelector('[data-mobile-navigation]');
    const drawer = navigation?.querySelector('[data-mobile-navigation-drawer]');
    const overlay = navigation?.querySelector('[data-mobile-navigation-overlay]');
    const toggle = document.querySelector('[data-mobile-navigation-toggle]');

    if (!navigation || !drawer || !overlay || !toggle) {
        return;
    }

    let isOpen = false;
    let previouslyFocused = null;
    const desktopBreakpoint = window.matchMedia('(min-width: 1024px)');

    const closeNavigation = (restoreFocus = true) => {
        if (!isOpen) {
            return;
        }

        isOpen = false;
        navigation.setAttribute('aria-hidden', 'true');
        navigation.classList.add('pointer-events-none');
        drawer.classList.add('-translate-x-full');
        overlay.classList.add('opacity-0');
        document.body.classList.remove('overflow-hidden');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Buka menu navigasi');

        if (restoreFocus && previouslyFocused instanceof HTMLElement) {
            previouslyFocused.focus();
        }
    };

    const openNavigation = () => {
        if (isOpen) {
            return;
        }

        isOpen = true;
        previouslyFocused = document.activeElement;
        navigation.setAttribute('aria-hidden', 'false');
        navigation.classList.remove('pointer-events-none');
        document.body.classList.add('overflow-hidden');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Tutup menu navigasi');

        window.requestAnimationFrame(() => {
            if (!isOpen) {
                return;
            }

            drawer.classList.remove('-translate-x-full');
            overlay.classList.remove('opacity-0');
            drawer.querySelector('button[aria-label="Tutup menu navigasi"]')?.focus();
        });
    };

    toggle.addEventListener('click', openNavigation);
    overlay.addEventListener('click', () => closeNavigation());

    navigation.addEventListener('click', (event) => {
        if (event.target.closest('[data-mobile-navigation-close]')) {
            closeNavigation();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (!isOpen) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeNavigation();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusableElements = drawer.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement?.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement?.focus();
        }
    });

    desktopBreakpoint.addEventListener('change', (event) => {
        if (event.matches) {
            closeNavigation(false);
        }
    });
});
