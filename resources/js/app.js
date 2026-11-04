import './bootstrap';
import { initJalaliDatePickers } from './jalali-date-picker';

initJalaliDatePickers();

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

    const isIgnoredControl = (el) => {
        if (!(el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement || el instanceof HTMLSelectElement)) {
            return true;
        }

        if (el.disabled || el.readOnly) {
            return true;
        }

        const type = (el.type || '').toLowerCase();

        return ['hidden', 'submit', 'button', 'reset', 'image', 'file'].includes(type);
    };

    /**
     * Soft reload must never erase in-progress receptionist drafts
     * (reject reasons, search boxes, date filters mid-edit, etc.).
     */
    const formHasUnsavedDraft = () => {
        for (const form of document.querySelectorAll('form')) {
            if (form.dataset.allowSoftRefresh === '1') {
                continue;
            }

            for (const el of Array.from(form.elements)) {
                if (isIgnoredControl(el)) {
                    continue;
                }

                if (el instanceof HTMLInputElement && (el.type === 'checkbox' || el.type === 'radio')) {
                    if (el.checked !== el.defaultChecked) {
                        return true;
                    }

                    continue;
                }

                if (el instanceof HTMLSelectElement) {
                    const current = Array.from(el.selectedOptions).map((option) => option.value).join('\0');
                    const defaults = Array.from(el.options)
                        .filter((option) => option.defaultSelected)
                        .map((option) => option.value)
                        .join('\0');

                    if (current !== defaults) {
                        return true;
                    }

                    continue;
                }

                if ((el.value ?? '') !== (el.defaultValue ?? '')) {
                    return true;
                }
            }
        }

        return false;
    };

    ['keydown', 'pointerdown', 'input', 'change', 'scroll'].forEach((name) => {
        document.addEventListener(name, markInteraction, { passive: true });
    });

    window.setInterval(() => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        const active = document.activeElement;
        const typing = active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);

        if (typing || formHasUnsavedDraft() || Date.now() - lastInteraction < 20000) {
            return;
        }

        window.location.reload();
    }, seconds * 1000);
})();
