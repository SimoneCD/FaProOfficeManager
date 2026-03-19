import { Controller } from '@hotwired/stimulus';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.js';
import moment from 'moment'; // Moment.js importieren

export default class extends Controller {
    static targets = ['calendar'];
    modal = null;
    currentModalType = null; // Neue Eigenschaft

    connect() {
        const locale = this.element.dataset.locale || 'de';
        moment.locale(locale);

        document.querySelectorAll('.booking_event').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                handleBookingEventClick(e);
            });
        });
        if (document.getElementById('calendar')) {
            this.calendar = new Calendar(this.element, {
                plugins: [dayGridPlugin, interactionPlugin, listPlugin, timeGridPlugin],
                locale: locale,
                initialView: 'dayGridMonth',
                initialDate: new Date().toISOString().split('T')[0],
                timeZone: 'Europe/Berlin',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                navLinks: true,
                selectable: false,
                selectMirror: true,
                editable: false,
                dayMaxEvents: true,
                dateClick: this.handleDateClick.bind(this),
                events: {
                    url: '/termine/alle',
                    success: (data) => {},
                    failure: () => console.error('Fehler beim Laden der Events')
                },
                eventClick: this.handleEventClick.bind(this)
            });
            this.calendar.render();
        }
        this.modal = new bootstrap.Modal(document.getElementById('universalModal'));
        this.initModalLogic();
        document.getElementById("autojson").hidden;
    }
    handleBookingEventClick(event) {
        console.log(event.currentTarget.dataset);
        const name = event.currentTarget.dataset.name;
        const number = event.currentTarget.dataset.number;
        const actionUrl = event.currentTarget.dataset.action;
        fetch(actionUrl, { method: 'GET' })
            .then(response => response.text())
            .then(data => {
                this.configureModal('edit', {
                    title: `<strong>Termin für ${name} anlegen</strong>`,
                    content: data,
                    footer: `Angebotsnummer: ${number}`
                });
                this.modal.show();
            })
            .catch(error => console.error('Fehler beim Laden des Termin-Modals:', error));
    }
    async handleEventClick(info) {
        info.jsEvent.preventDefault();
        try {
            const response = await fetch(`/termine/${info.event._def.publicId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const booking = await response.json();
            console.log('Booking', booking)
            this.configureModal('edit', booking);
            const autocompleteController = this.application.controllers.find(c => c.identifier === 'custom-autocomplete' && c.element === this.modal._element);
            if (autocompleteController) {
                autocompleteController.initializeSuggestions();
            }
            this.modal.show();
        } catch (error) {
            console.error('Fehler beim Laden des Bookings:', error);
        }
    }
    async handleLinkClick(event) {
        console.log(event.dataset)

    }

    handleDateClick(info) {
        const form = document.getElementById('universalModalForm');
        form.reset();
        console.log(info);
        document.getElementById('begin_at').value = info.date;
        document.getElementById('end_at').value = info.date;
        this.configureModal('create', { date: info.dateStr });
        const autocompleteController = this.application.controllers.find(c => c.identifier === 'custom-autocomplete' && c.element === this.modal._element);
        if (autocompleteController) {
            autocompleteController.initializeSuggestions();
        }
        this.modal.show();
    }

    configureModal(type, data = {}) {
        this.currentModalType = type;
        console.log('Data:', data);
        const modalTitle = document.getElementById("universalModalTitle");
        const modalFooter = document.getElementById("modalFooter");
        const titleSelect = document.getElementById("titleSelect");
        let beginAt = document.getElementById("begin_at");
        let endAt = document.getElementById("end_at");

        modalFooter.innerHTML = "";
        if (type === 'create') {
            modalTitle.textContent = 'Neuer Kalendereintrag';
            modalFooter.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-bd-primary" data-action="click->booking-update#saveCalendarEvent">Speichern</button>
            `;
            titleSelect.value = 'Montage/Installation';
            beginAt.value = data.date + 'T10:00:00';
            endAt.value = data.date + 'T13:00:00';
            let setDate = moment(data.date + 'T10:00:00')
            document.getElementById('endAtText').textContent = setDate.format('DD.MM.YYYY')
            document.getElementById('modalDate').value = data.date || '';
            this.toggleFields('Montage/Installation');
        } else if (type === 'edit') {
            console.log('Edit-Modus');
            document.getElementById("bookingId").value = data.id || '';
            titleSelect.value = data.title;
            if (endAt.value === beginAt.value) {
                let endDate = moment(endAt.value).add(1, 'h')
                endAt.value = endDate.format('YYYY-MM-DDTHH:mm')
            }
            if (data.title === 'Aufgabe') {
                modalTitle.textContent = `Aufgabe für ${data.customer_name || ''} bearbeiten`;
                const userTask = document.getElementById('userTask');
                let endAtText = document.getElementById("endAtText");
                endAtText.text = endAt.value;
                userTask.value = data.user_task;
            } else {
                modalTitle.textContent = data.title+`-Termin für ${data.customer_name || ''} bearbeiten`;
            }
            document.getElementById("customerName").value = data.customer_name;
            document.getElementById("customerId").value = data.customer_id;
            document.getElementById("begin_at").value = this.formatDateTimeLocal(data.begin_at);
            document.getElementById("end_at").value = this.formatDateTimeLocal(data.end_at);
            document.getElementById("notice").value = data.notice || '';
            this.toggleFields(data.title);
            let buttons = '';
            if (data.offer) buttons = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <a href="${data.offer}" id="offerLink" class="btn btn-default">Aufmass</a>`;
            if (data.customer_link) buttons += `<a href="${data.customer_link}" id="customerLink" class="btn btn-default">Profil</a>`;
            modalFooter.innerHTML = buttons + '<button type="button" class="btn btn-primary" data-action="click->booking-update#updateBooking">Speichern</button>';
        }
    }

    initModalLogic() {
        const beginAt = document.getElementById('begin_at');
        const endAt = document.getElementById('end_at');
        const titleSelect = document.getElementById('titleSelect');
        titleSelect.addEventListener('change', (event) => this.toggleFields(event.target.value));
        if (beginAt.value) this.updateAt(beginAt, endAt);
        beginAt.addEventListener('change', () => this.updateAt(beginAt, endAt));

    }

    toggleFields(title) {
        console.log(title);
        const taskUserField = document.getElementById('taskUserField');
        const dateTimeField = document.getElementById("dateTimeField");
        const dateTimeText = document.getElementById("dateTimeText");
        const beginAtField = document.getElementById('beginAtField');
        if (title === 'Aufgabe') {
            taskUserField.style.display = 'block';
            dateTimeField.style.display = 'none';
            dateTimeText.style.display = 'block';
            const endAtText = document.getElementById('endAtText');
            if (endAtText.text === '') {
                let setDate = moment(document.getElementById('begin_at').value)
                endAtText.textContent = setDate.format('DD.MM.YYYY')
            }
        } else {
            taskUserField.style.display = 'none';
            dateTimeField.style.display = 'flex';
            dateTimeText.style.display = 'none';
            beginAtField.style.display = 'block';
        }
    }

    updateAt(obj, endObj) {
        const titleSelect = document.getElementById('titleSelect');

        const date = moment(obj.value);
        const type = titleSelect.value;
        console.log('type:',type);
        if (date.isValid()) {
            if (type === 'Anrufen') {
                date.add(15, 'minutes');
            } else {
                date.add(4, 'hours');
            }
            endObj.value = date.format('YYYY-MM-DDTHH:mm');
        }
    }

    formatDateTimeLocal(dateString) {
        const date = new Date(dateString);
        date.setHours(date.getHours() + 1);
        return date.toISOString().slice(0, 16);
    }

    addOneHour(startTime) {
        const [hours, minutes] = startTime.split(':');
        const date = new Date();
        date.setHours(parseInt(hours, 10));
        date.setMinutes(parseInt(minutes, 10));
        date.setHours(date.getHours() + 1);
        const newHours = date.getHours().toString().padStart(2, '0');
        const newMinutes = date.getMinutes().toString().padStart(2, '0');
        return `${newHours}:${newMinutes}`;
    }
}