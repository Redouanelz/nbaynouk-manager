const calendar = document.querySelector('[data-calendar]');

if (calendar) {
    const eventModal = calendar.querySelector('[data-event-modal]');
    const dayModal = calendar.querySelector('[data-day-modal]');
    const deleteModal = calendar.querySelector('[data-delete-modal]');
    const form = calendar.querySelector('[data-event-form]');
    const modalTitle = calendar.querySelector('#calendar-modal-title');
    const dateLabel = calendar.querySelector('[data-modal-date-label]');
    const dateField = calendar.querySelector('[data-date-field]');
    const deleteButton = calendar.querySelector('[data-delete-event]');
    const submitButton = calendar.querySelector('[data-submit-event]');
    const toast = calendar.querySelector('[data-calendar-toast]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const events = new Map();
    let lastFocus = null;

    calendar.querySelectorAll('.calendar-desktop [data-event-id]').forEach(button => events.set(String(button.dataset.eventId), eventFromButton(button)));

    function eventFromButton(button) {
        return {id: Number(button.dataset.eventId), title: button.dataset.eventTitle, date: button.dataset.eventDate, color: button.dataset.eventColor, notes: button.dataset.eventNotes || ''};
    }

    function labelDate(value) {
        const date = new Date(`${value}T12:00:00`);
        return Number.isNaN(date.valueOf()) ? '' : new Intl.DateTimeFormat('fr-FR', {day: 'numeric', month: 'long', year: 'numeric'}).format(date);
    }

    function setOpen(root, open) {
        root.classList.toggle('is-open', open);
        root.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.body.classList.toggle('overflow-hidden', open);
        if (open) root.querySelector('input, button, textarea')?.focus();
        else lastFocus?.focus();
    }

    function clearErrors() {
        form.querySelectorAll('[data-error]').forEach(node => { node.textContent = ''; node.classList.add('hidden'); });
        const general = form.querySelector('[data-form-error]'); general.textContent = ''; general.classList.add('hidden');
    }

    function openCreate(date = '') {
        lastFocus = document.activeElement;
        form.reset(); clearErrors();
        form.elements.event_id.value = '';
        form.elements.event_date.value = date;
        form.elements.color.value = 'black';
        modalTitle.textContent = 'Ajouter au calendrier';
        dateLabel.textContent = date ? labelDate(date) : 'Nouvel événement';
        dateField.classList.toggle('is-compact-date', Boolean(date));
        deleteButton.classList.add('hidden'); submitButton.textContent = 'Ajouter';
        setOpen(eventModal, true);
        form.elements.title.focus();
    }

    function openEdit(event) {
        lastFocus = document.activeElement;
        clearErrors();
        form.elements.event_id.value = event.id;
        form.elements.event_date.value = event.date;
        form.elements.title.value = event.title;
        form.elements.color.value = event.color;
        form.elements.notes.value = event.notes || '';
        modalTitle.textContent = 'Modifier l’événement'; dateLabel.textContent = labelDate(event.date);
        dateField.classList.remove('is-compact-date'); deleteButton.classList.remove('hidden'); submitButton.textContent = 'Enregistrer';
        setOpen(dayModal, false); setOpen(eventModal, true); form.elements.title.focus();
    }

    function eventButton(event) {
        const button = document.createElement('button');
        button.type = 'button'; button.className = `calendar-event calendar-color-${event.color}`;
        button.dataset.eventId = event.id; button.dataset.eventTitle = event.title; button.dataset.eventDate = event.date;
        button.dataset.eventColor = event.color; button.dataset.eventNotes = event.notes || '';
        const span = document.createElement('span'); span.textContent = event.title; button.append(span);
        return button;
    }

    function eventsForDate(date) { return [...events.values()].filter(event => event.date === date).sort((a, b) => a.id - b.id); }

    function renderDate(date) {
        const dayEvents = eventsForDate(date);
        const desktop = calendar.querySelector(`[data-events-for="${date}"]`);
        if (desktop) {
            desktop.replaceChildren(...dayEvents.slice(0, 3).map(eventButton));
            const more = desktop.closest('[data-day]').querySelector('[data-show-day]');
            more.classList.toggle('hidden', dayEvents.length <= 3); more.querySelector('span').textContent = Math.max(0, dayEvents.length - 3);
        }
        const mobile = calendar.querySelector(`[data-mobile-events-for="${date}"]`);
        if (mobile) {
            mobile.replaceChildren(...dayEvents.map(eventButton));
            if (!dayEvents.length) { const empty = document.createElement('p'); empty.className = 'calendar-empty'; empty.textContent = 'Libre'; mobile.append(empty); }
        }
    }

    function showToast(message, error = false) {
        toast.querySelector('span').textContent = message; toast.classList.remove('hidden'); toast.classList.toggle('is-error', error);
        window.clearTimeout(showToast.timer); showToast.timer = window.setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    async function request(url, method, body) {
        const response = await fetch(url, {method, headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf}, body: body ? JSON.stringify(body) : undefined});
        const data = await response.json().catch(() => ({}));
        if (!response.ok) { const error = new Error(data.message || 'Une erreur est survenue.'); error.status = response.status; error.errors = data.errors; throw error; }
        return data;
    }

    calendar.addEventListener('click', event => {
        const add = event.target.closest('[data-add-event], [data-add-date]'); if (add) return openCreate(add.dataset.addDate || '');
        const eventTarget = event.target.closest('[data-event-id]'); if (eventTarget) return openEdit(events.get(String(eventTarget.dataset.eventId)) || eventFromButton(eventTarget));
        const more = event.target.closest('[data-show-day]');
        if (more) {
            lastFocus = more; const date = more.dataset.showDay; calendar.querySelector('[data-day-title]').textContent = labelDate(date);
            calendar.querySelector('[data-day-events]').replaceChildren(...eventsForDate(date).map(eventButton)); setOpen(dayModal, true);
        }
    });

    calendar.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => setOpen(eventModal, false)));
    calendar.querySelectorAll('[data-day-close]').forEach(button => button.addEventListener('click', () => setOpen(dayModal, false)));
    toast.querySelector('button').addEventListener('click', () => toast.classList.add('hidden'));

    form.addEventListener('submit', async submitEvent => {
        submitEvent.preventDefault(); clearErrors(); submitButton.disabled = true;
        const id = form.elements.event_id.value;
        const body = {title: form.elements.title.value, event_date: form.elements.event_date.value, color: form.elements.color.value, notes: form.elements.notes.value || null};
        try {
            const oldDate = id ? events.get(String(id))?.date : null;
            const data = await request(id ? `${calendar.dataset.updateUrl}/${id}` : calendar.dataset.storeUrl, id ? 'PATCH' : 'POST', body);
            events.set(String(data.event.id), data.event); if (oldDate && oldDate !== data.event.date) renderDate(oldDate); renderDate(data.event.date);
            setOpen(eventModal, false); showToast(data.message);
        } catch (error) {
            if (error.status === 422 && error.errors) Object.entries(error.errors).forEach(([field, messages]) => { const node = form.querySelector(`[data-error="${field}"]`); if (node) { node.textContent = messages[0]; node.classList.remove('hidden'); } });
            else { const general = form.querySelector('[data-form-error]'); general.textContent = id ? 'Impossible de modifier l’événement.' : 'Impossible d’ajouter l’événement.'; general.classList.remove('hidden'); }
        } finally { submitButton.disabled = false; }
    });

    deleteButton.addEventListener('click', () => { calendar.querySelector('[data-delete-title]').textContent = form.elements.title.value; setOpen(deleteModal, true); });
    calendar.querySelectorAll('[data-delete-cancel]').forEach(button => button.addEventListener('click', () => setOpen(deleteModal, false)));
    calendar.querySelector('[data-delete-confirm]').addEventListener('click', async confirm => {
        const id = form.elements.event_id.value; const old = events.get(String(id)); confirm.disabled = true;
        try { const data = await request(`${calendar.dataset.updateUrl}/${id}`, 'DELETE'); events.delete(String(id)); if (old) renderDate(old.date); setOpen(deleteModal, false); setOpen(eventModal, false); showToast(data.message); }
        catch { setOpen(deleteModal, false); showToast('Impossible de supprimer l’événement.', true); }
        finally { confirm.disabled = false; }
    });

    document.addEventListener('keydown', event => { if (event.key !== 'Escape') return; if (deleteModal.classList.contains('is-open')) setOpen(deleteModal, false); else if (dayModal.classList.contains('is-open')) setOpen(dayModal, false); else if (eventModal.classList.contains('is-open')) setOpen(eventModal, false); });
}
