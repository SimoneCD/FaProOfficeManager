import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

export default class extends Controller {
    static values = {
        'url': String,
        'id': Number,
    }
    static targets = ["modal"];
    delivered(event) {
        let url = $(event.currentTarget).attr('data-url');
        event.currentTarget.checked = false;
        if (confirm('Ist das Produkt geliefert worden?')) {
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
     dorderedAndDelivered(event) {
        let orderUrl = $(event.currentTarget).attr('data-url');
        let deliverUrl = $(event.currentTarget).attr('data-url-deliver');
        event.currentTarget.checked = false;
        if (confirm('Ist das Produkt geliefert worden?')) {
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

}