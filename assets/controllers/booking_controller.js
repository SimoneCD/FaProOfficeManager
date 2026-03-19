import { Controller } from '@hotwired/stimulus';
import axios from 'axios';
import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';

export default class extends Controller {
    static values = { 'url': String, 'id': Number }
    static targets = ["modal"]
    book(event) {
        let url = $(event.currentTarget).attr('data-url');
        event.currentTarget.checked = false;
        if (confirm('Ist das Produkt für dieses Angebot bestellt?')) {
            event.currentTarget.disabled = true;
            event.currentTarget.checked = true;
            axios.post(url).then((response) => {
            }, (error) => {
                console.log('error');
                console.log(error);
            });
        } else {
            event.currentTarget.checked = false;
        }
    }
    getPartydays() {
        window.location.reload();

        return true;
    }
    launchBooking(event) {
        let modalController = this.application.getControllerForElementAndIdentifier(
            this.ordermodalTarget,
            "modal"
        );
        modalController.open();
    }
}