(() => {
    'use strict';
    const navigation = document.getElementById('app-navigation');
    const toggle = document.getElementById('navigation-toggle');
    if (navigation && toggle) {
        toggle.hidden = false;
        const setOpen = (open) => {
            navigation.hidden = !open;
            toggle.setAttribute('aria-expanded', String(open));
            toggle.textContent = open ? 'Hide menu' : 'Show menu';
        };
        setOpen(!navigation.hidden);
        toggle.addEventListener('click', () => {
            setOpen(navigation.hidden);
            if (!navigation.hidden) navigation.querySelector('a')?.focus();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !navigation.hidden) {
                setOpen(false);
                toggle.focus();
            }
        });
    }
    const controls = document.getElementById('form-font-controls');
    if (!controls) return;
    controls.hidden = false;
    const smaller = document.getElementById('form-font-smaller');
    const larger = document.getElementById('form-font-larger');
    const reset = document.getElementById('form-font-reset');
    const output = document.getElementById('form-font-size');
    const key = 'mcs-pi-entry-font-size';
    let size = 14;
    try {
        const saved = localStorage.getItem(key);
        if (saved !== null && Number.isFinite(Number(saved))) size = Math.round(Number(saved));
    } catch (_) { /* Form controls also work when storage is unavailable. */ }
    const applySize = () => {
        size = Math.max(12, Math.min(20, size));
        document.body.style.setProperty('--entry-font-size', size + 'px');
        output.textContent = size + 'px';
        smaller.disabled = size === 12;
        larger.disabled = size === 20;
        try { localStorage.setItem(key, String(size)); } catch (_) {}
    };
    smaller.addEventListener('click', () => { size -= 1; applySize(); });
    larger.addEventListener('click', () => { size += 1; applySize(); });
    reset.addEventListener('click', () => { size = 14; applySize(); });
    applySize();
})();
