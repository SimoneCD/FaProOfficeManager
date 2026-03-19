import { Controller } from '@hotwired/stimulus';
import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.js';
import moment from 'moment'; // Moment.js importieren

export default class extends Controller {
    static targets = ['bookingModal'];
    modal = null;

    connect() {
        const locale = this.element.dataset.locale || 'de';
        moment.locale(locale);

        document.querySelectorAll('.booking_event').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                handleBookingEventClick(e);
            });
        });
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

    async updateBooking(event) {
        console.log('updateBooking called')
        event.preventDefault();

        const form = document.getElementById('universalModalForm');
        const formData = new FormData(form);
        console.log(formData.get('user'))
        console.log(formData.get('title'))
        if (formData.get('title') === 'Aufgabe' && (formData.get('user') === null || formData.get('user') === '')) {
            document.getElementById('userTask').style.borderColor = 'red';
        } else if (formData.get('customerId') === '') {
            document.getElementById('customerName').style.borderColor = 'red';
        }  else {
            document.getElementById('userTask').style.borderColor = '#fff';
            document.getElementById('customerName').style.borderColor = '#fff';

            console.log(formData);
            const bookingData = Object.fromEntries(formData)
            console.log(bookingData);
            console.log(event);

            try {
                const response = await fetch(`/termine/${bookingData.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(bookingData)
                })
                console.log(response);

                if (response.ok) {
                    // Modal schließen und Calendar aktualisieren
                    const modalElement = document.getElementById('universalModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    modal.hide();
                    //this.calendar.refetchEvents()
                    window.location.reload();
                } else {
                    console.error('Update fehlgeschlagen:', response.text())
                }
            } catch (error) {
                console.error('Fehler beim Update:', error)
            }
        }
    }

    async saveCalendarEvent(event) {
        console.log('createBooking called')
        event.preventDefault();
        document.addEventListener('DOMContentLoaded', () => {
            console.log('createBooking loaded');
        })
        const form = document.getElementById('universalModalForm');
        const formData = new FormData(form);
        if (formData.get('customerId') === '') {
            document.getElementById('customerName').style.borderColor = 'red';
        } else {
            document.getElementById('customerName').style.borderColor = 'white';
            const eventData = {
                title: formData.get('title'),
                customerId: formData.get('customerId'),
                user: formData.get('user'),
                userTask: formData.get('user_task'),
                date: formData.get('date'),
                beginAt: formData.get('begin_at'),
                endAt: formData.get('end_at'),
                notice: formData.get('notice'),
            };
            console.log(eventData);
            const response = await fetch('/bookings/api/event/create/' + eventData.customerId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(eventData)
            });
            if (response.ok) {
                // Modal schließen und Calendar aktualisieren
                const modalElement = document.getElementById('universalModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                modal.hide();
                //this.calendar.refetchEvents()
                //window.location.reload();
            } else {
                console.error('Update fehlgeschlagen:', response.text())
            }
        }
    }
}