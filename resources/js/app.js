import './bootstrap';

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-nav-toggle]');

    if (!toggle) {
        return;
    }

    const sidebar = document.getElementById('app-sidebar');

    if (!sidebar) {
        return;
    }

    const open = sidebar.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
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
