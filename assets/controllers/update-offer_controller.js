import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

/**
 * update the status by button click in offer_index
 */
export default class extends Controller {
    static values = {
        'offerId': String,
        'updateUrl': String,
        'status': String
    }

    static targets = [ "element" ]
    copy(event) {
        if (event.clipboardData) {
            event.clipboardData.setData("text/plain", span.textContent);
        }
    }
    updateStatus(event) {
        this.statusValue = event.currentTarget.getAttribute('data-status-param');
        let then = this;
        let thenTarget = event.currentTarget;
        if (confirm('Der Status des Angebots wird verändert ('+this.statusValue+'), ist das gewollt?')) {
            axios.post(this.updateUrlValue, {
                status: this.statusValue
            }).then(function (response) {
                if (then.hasElementTarget) {
                    let $t = $('#status_'+then.statusValue);
                    if ($t) {
                        thenTarget.classList.add('d-none');
                        thenTarget.classList.add('hidden');
                        thenTarget.setAttribute('disabled',true);
                        $t.html($t.html()+then.elementTarget.innerHTML)
                    }
                }
                then.elementTarget.remove();
            });
        }
    }
}
