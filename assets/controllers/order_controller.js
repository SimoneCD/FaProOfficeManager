import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

export default class extends Controller {
    static values = {
        'url': String,
        'id': Number,
    }
    static targets = ["ordermodal"];
    order(event) {
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
    reload() {
        window.location.reload();
        return true;
    }
    launchOrder(event) {
        let modalController = this.application.getControllerForElementAndIdentifier(
            this.ordermodalTarget,
            "modal"
        );
        let orderActionController = this.application.getControllerForElementAndIdentifier(
            this.ordermodalTarget,
            "order-action"
        );
        orderActionController.setOrderContent(event.currentTarget.dataset);
        modalController.open();
    }
}