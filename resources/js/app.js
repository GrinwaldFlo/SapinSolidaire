function formatClientDateTime(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    const parts = new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).formatToParts(date);

    const formatted = {};

    for (const part of parts) {
        if (part.type !== 'literal') {
            formatted[part.type] = part.value;
        }
    }

    return `${formatted.day}/${formatted.month}/${formatted.year} ${formatted.hour}:${formatted.minute}`;
}

function localizeDateTimes() {
    document.querySelectorAll('[data-local-datetime]').forEach((element) => {
        const isoDate = element.dataset.localDatetime;

        if (!isoDate) {
            return;
        }

        element.textContent = formatClientDateTime(isoDate);
    });
}

document.addEventListener('DOMContentLoaded', localizeDateTimes);
document.addEventListener('livewire:load', localizeDateTimes);
document.addEventListener('livewire:update', localizeDateTimes);
document.addEventListener('livewire:navigated', localizeDateTimes);
