import { Controller } from '@hotwired/stimulus';

/**
 * hide and show the offer box details
 */
export default class extends Controller {
    static values = {
        offerId: String,
        offerNumber: String,
    }
    viewBox (event) {
        var key = $('#non-kj'+this.offerIdValue);
        if (key.hasClass('d-none')) {
            key.removeClass('d-none');
        } else {
            key.addClass('d-none');
        }
    }
}
