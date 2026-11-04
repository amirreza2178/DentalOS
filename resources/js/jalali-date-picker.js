import {
    MONTH_NAMES,
    WEEKDAY_LABELS,
    formatJalali,
    jalaliMonthLength,
    parseJalali,
    saturdayWeekdayIndex,
    toJalali,
} from './jalali';

function todayJalaliParts(timeZone) {
    const formatter = new Intl.DateTimeFormat('en-CA', {
        timeZone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
    const parts = Object.fromEntries(
        formatter.formatToParts(new Date()).map((part) => [part.type, part.value]),
    );
    const gy = Number(parts.year);
    const gm = Number(parts.month);
    const gd = Number(parts.day);

    return toJalali(gy, gm, gd);
}

function compareJalali(a, b) {
    if (a.jy !== b.jy) {
        return a.jy - b.jy;
    }

    if (a.jm !== b.jm) {
        return a.jm - b.jm;
    }

    return a.jd - b.jd;
}

function shiftMonth(jy, jm, delta) {
    let year = jy;
    let month = jm + delta;

    while (month < 1) {
        month += 12;
        year -= 1;
    }

    while (month > 12) {
        month -= 12;
        year += 1;
    }

    return { jy: year, jm: month };
}

function dayStatus(root, jalaliKey) {
    const statuses = root._dayStatuses || {};

    return statuses[jalaliKey] || null;
}

function isDisabled(root, parts) {
    const key = formatJalali(parts.jy, parts.jm, parts.jd);
    const status = dayStatus(root, key);
    const min = root._min;
    const max = root._max;
    const bookingOnly = root.dataset.bookingOnly === '1';

    if (min && compareJalali(parts, min) < 0) {
        return true;
    }

    if (max && compareJalali(parts, max) > 0) {
        return true;
    }

    if (bookingOnly) {
        return status !== 'open';
    }

    if (status === 'closed' || status === 'full') {
        return root.dataset.disableUnavailable === '1';
    }

    return false;
}

function statusClass(status) {
    if (status === 'open') {
        return 'dos-cal-day--open';
    }

    if (status === 'full') {
        return 'dos-cal-day--full';
    }

    if (status === 'closed') {
        return 'dos-cal-day--closed';
    }

    return '';
}

function renderMonth(root) {
    const grid = root.querySelector('[data-cal-grid]');
    const title = root.querySelector('[data-cal-title]');
    const input = root.querySelector('[data-cal-input]');
    const live = root.querySelector('[data-cal-live]');

    if (!grid || !title || !input) {
        return;
    }

    const { jy, jm } = root._view;
    const today = root._today;
    const selected = parseJalali(input.value);
    title.textContent = `${MONTH_NAMES[jm]} ${jy}`;

    const firstWeekday = saturdayWeekdayIndex(jy, jm, 1);
    const length = jalaliMonthLength(jy, jm);
    const cells = [];

    for (let i = 0; i < firstWeekday; i += 1) {
        cells.push('<span class="dos-cal-day dos-cal-day--empty" aria-hidden="true"></span>');
    }

    for (let day = 1; day <= length; day += 1) {
        const parts = { jy, jm, jd: day };
        const key = formatJalali(jy, jm, day);
        const status = dayStatus(root, key);
        const disabled = isDisabled(root, parts);
        const isToday = today && compareJalali(parts, today) === 0;
        const isSelected = selected && compareJalali(parts, selected) === 0;
        const classes = [
            'dos-cal-day',
            statusClass(status),
            isToday ? 'dos-cal-day--today' : '',
            isSelected ? 'dos-cal-day--selected' : '',
            disabled ? 'dos-cal-day--disabled' : '',
        ]
            .filter(Boolean)
            .join(' ');

        const labelBits = [key];
        if (status === 'open') {
            labelBits.push('قابل رزرو');
        } else if (status === 'full') {
            labelBits.push('پر');
        } else if (status === 'closed') {
            labelBits.push('غیرفعال');
        }
        if (isToday) {
            labelBits.push('امروز');
        }

        cells.push(
            `<button type="button" class="${classes}" data-cal-day="${key}" ${disabled ? 'disabled aria-disabled="true"' : ''} aria-label="${labelBits.join('، ')}" ${isSelected ? 'aria-pressed="true"' : 'aria-pressed="false"'}>${day}</button>`,
        );
    }

    grid.innerHTML = cells.join('');

    if (live) {
        live.textContent = selected
            ? `تاریخ انتخاب‌شده ${formatJalali(selected.jy, selected.jm, selected.jd)}`
            : 'تاریخی انتخاب نشده است';
    }
}

function setOpen(root, open) {
    const panel = root.querySelector('[data-cal-panel]');

    if (root.dataset.display === 'inline') {
        root.dataset.open = '1';
        if (panel) {
            panel.hidden = false;
        }

        return;
    }

    root.dataset.open = open ? '1' : '0';
    if (panel) {
        panel.hidden = !open;
    }

    const toggle = root.querySelector('[data-cal-toggle]');
    if (toggle) {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
}

function selectDay(root, key) {
    const input = root.querySelector('[data-cal-input]');
    if (!input) {
        return;
    }

    input.value = key;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));

    renderMonth(root);
    setOpen(root, false);

    if (root.dataset.autoSubmit === '1') {
        const form = root.closest('form');
        if (form) {
            form.requestSubmit();
        }
    }
}

function bindRoot(root) {
    if (root.dataset.calBound === '1') {
        return;
    }

    root.dataset.calBound = '1';

    const timeZone = root.dataset.timezone || 'Asia/Tehran';
    root._today = todayJalaliParts(timeZone);
    root._min = parseJalali(root.dataset.min || '');
    root._max = parseJalali(root.dataset.max || '');

    try {
        root._dayStatuses = JSON.parse(root.dataset.dayStatuses || '{}');
    } catch {
        root._dayStatuses = {};
    }

    const input = root.querySelector('[data-cal-input]');
    const selected = parseJalali(input?.value || '') || root._today;
    root._view = { jy: selected.jy, jm: selected.jm };

    if (root.dataset.display === 'inline') {
        setOpen(root, true);
    }

    root.querySelector('[data-cal-prev]')?.addEventListener('click', () => {
        root._view = shiftMonth(root._view.jy, root._view.jm, -1);
        renderMonth(root);
    });

    root.querySelector('[data-cal-next]')?.addEventListener('click', () => {
        root._view = shiftMonth(root._view.jy, root._view.jm, 1);
        renderMonth(root);
    });

    root.querySelector('[data-cal-today]')?.addEventListener('click', () => {
        root._view = { jy: root._today.jy, jm: root._today.jm };
        if (!isDisabled(root, root._today)) {
            selectDay(root, formatJalali(root._today.jy, root._today.jm, root._today.jd));
        } else {
            renderMonth(root);
        }
    });

    root.querySelector('[data-cal-toggle]')?.addEventListener('click', () => {
        setOpen(root, root.dataset.open !== '1');
    });

    root.querySelector('[data-cal-grid]')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-cal-day]');
        if (!button || button.disabled) {
            return;
        }

        selectDay(root, button.getAttribute('data-cal-day'));
    });

    input?.addEventListener('change', () => {
        const parsed = parseJalali(input.value);
        if (parsed) {
            root._view = { jy: parsed.jy, jm: parsed.jm };
        }
        renderMonth(root);
    });

    renderMonth(root);
}

export function initJalaliDatePickers(scope = document) {
    scope.querySelectorAll('[data-jalali-date-picker]').forEach((root) => bindRoot(root));
}

document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-jalali-date-picker][data-display="popover"][data-open="1"]').forEach((root) => {
        if (!root.contains(event.target)) {
            setOpen(root, false);
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('[data-jalali-date-picker][data-display="popover"][data-open="1"]').forEach((root) => {
        setOpen(root, false);
    });
});
