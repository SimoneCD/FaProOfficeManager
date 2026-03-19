import Popover from 'stimulus-popover';

export default class extends Popover {
    connect() {
        super.connect();
    }
    async show(e) {
        if (this.hasCardTarget) {
            return;
        }
        let elm = $('div[data-popover-target="card"]');
        if (elm) {
            elm.remove();
        }
        return super.show(e);
    }
    hide() {
        let modalController = this.application.getControllerForElementAndIdentifier(
            this.faqmodalTarget,
            "modal"
        );
        if (modalController) {
            modalController.close();
        }
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        super.hide();
    }
}