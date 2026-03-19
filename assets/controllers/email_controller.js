import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["modal"];
    initialize() {
        // console.log(this.modalTarget);
    }

    launchEmail(event) {
        let modalController = this.application.getControllerForElementAndIdentifier(
            this.modalTarget,
            "modal"
        );
        let modalActionController = this.application.getControllerForElementAndIdentifier(
            this.modalTarget,
            "modal-action"
        );

        console.log('launchEmail [params, dataset]');
        console.log(event.params);
        console.log(event.currentTarget.dataset);
        modalActionController.setMailContent(event.params, event.currentTarget.dataset);
        modalController.open();

    }
    launchReminder(event) {
        let modalController = this.application.getControllerForElementAndIdentifier(
            this.modalTarget,
            "modal"
        );
        let modalActionController = this.application.getControllerForElementAndIdentifier(
            this.modalTarget,
            "modal-action"
        );
        console.log('launchReminder [params, dataset]');
        console.log(event.params);
        console.log(event.currentTarget.dataset);
        modalActionController.setReminderContent(event.params, event.currentTarget.dataset);
        modalController.open();

    }
}