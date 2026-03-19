import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { url: String };
    static targets = ["button"];
    suggestionsDiv = null;

    connect() {
        console.log('Autocomplete connected');
        this.element.addEventListener('input', this.search.bind(this));
        this.suggestionsDiv = document.createElement('div');
        this.suggestionsDiv.className = 'autocomplete-suggestions';
        // Nicht direkt anhängen – erst bei Bedarf
    }

    initializeSuggestions() {
        const t = document.getElementById("autojson");
        if (t && !t.contains(this.suggestionsDiv)) {
            t.appendChild(this.suggestionsDiv);
        } else if (!t) {
            console.error("Container #autojson nicht gefunden");
        }
    }

    async search(event) {
        this.initializeSuggestions();
        const query = event.target.value;
        if (query.length < 2) {
            this.suggestionsDiv.innerHTML = '';
            return;
        }
        const response = await fetch(this.urlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ q: query })
        });
        if (!response.ok) {
            throw new Error(`HTTP-Fehler! Status: ${response.status}`);
        }

        const customersResponse = await response.json();
        const customers = Array.isArray(customersResponse.customers) ? customersResponse.customers : [];

        this.suggestionsDiv.innerHTML = customers.map(customer => `
            <div 
                class="autocomplete-suggestion" 
                data-id="${customer.id}" 
                data-action="click->custom-autocomplete#select"
            >${customer.name} ${customer.surName}</div>
        `).join('');
    }

    select(event) {
        const suggestion = event.target;
        const form = this.element.querySelector('form');
        const id = form.querySelector('[name="customerId"]');
        form.querySelector('[name="customer"]').value = suggestion.textContent.trim();
        id.value = suggestion.dataset.id;
        this.suggestionsDiv.innerHTML = '';
    }
}