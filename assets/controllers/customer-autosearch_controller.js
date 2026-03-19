import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

/**
 * Autosearch used in new inquiry
 */
export default class extends Controller {
    static values = {
        'q': String,
        'url': String,
        'customer': Number,
    }

    static targets = ['input', 'results','hidden']

    select(event) {
        this.customerValue = event.currentTarget.getAttribute('data-id')
        const customerName = event.currentTarget.getAttribute('data-name')
        if (this.hasHiddenTarget) {
            this.hiddenTarget.value = this.customerValue
            this.inputTarget.value = customerName
            this.resultsTarget.innerHTML = ''
            $('#step').hide();
        }
    }
    autosearch(event) {
        this.qValue = event.currentTarget.value;
        let then = this;
        if (this.qValue.length >= 1) {
            $('#step').hide();
            axios.post(this.urlValue, {
                "q": this.qValue
            }).then(function (response) {
                if (then.hasResultsTarget) {
                    then.resultsTarget.innerHTML = response.data
                }
            });
        } else {
            then.resultsTarget.innerHTML = ''
            $('#step').show();
        }
    }
}
