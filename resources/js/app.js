import './bootstrap';

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-nav-toggle]');

    if (toggle) {
        const sidebar = document.getElementById('app-sidebar');

        if (!sidebar) {
            return;
        }

        const open = sidebar.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        return;
    }

    const copyButton = event.target.closest('[data-copy-text]');

    if (!copyButton) {
        return;
    }

    const text = copyButton.getAttribute('data-copy-text') ?? '';
    const feedback = copyButton.getAttribute('data-copy-feedback') ?? 'کپی شد';
    const original = copyButton.textContent;

    const markCopied = () => {
        copyButton.textContent = feedback;
        window.setTimeout(() => {
            copyButton.textContent = original;
        }, 1800);
    };

    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(text).then(markCopied).catch(() => {
            window.prompt('کد پیگیری را کپی کنید:', text);
        });
    } else {
        window.prompt('کد پیگیری را کپی کنید:', text);
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-booking-submit]');

    if (!form || form.dataset.submitting === '1') {
        if (form?.dataset.submitting === '1') {
            event.preventDefault();
        }

        return;
    }

    form.dataset.submitting = '1';

    form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    });
});

(() => {
    const seconds = Number(document.body?.dataset?.softRefresh || 0);

    if (!Number.isFinite(seconds) || seconds < 30) {
        return;
    }

    let lastInteraction = Date.now();

    const markInteraction = () => {
        lastInteraction = Date.now();
    };

    ['keydown', 'pointerdown', 'input', 'scroll'].forEach((name) => {
        document.addEventListener(name, markInteraction, { passive: true });
    });

    window.setInterval(() => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        const active = document.activeElement;
        const typing = active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);

        if (typing || Date.now() - lastInteraction < 15000) {
            return;
        }

        window.location.reload();
    }, seconds * 1000);
})();
