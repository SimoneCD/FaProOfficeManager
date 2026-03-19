import { Controller } from "@hotwired/stimulus";
import axios from 'axios';

export default class extends Controller {
    static targets = [ 'customer', 'name', 'chat', 'message','panel','popup' ];
    static values = {
        'customer': Number,
    }
    addAnswer(event) {
        let that = this;
        axios.post(event.params.url, {
            'note':this.messageTarget.value,
            'customer': event.params.customer
        }).then((response) => {
            that.chatTarget.innerHTML = response.data;
            that.chatTarget.scrollTop = that.chatTarget.scrollHeight;
            $('.chatbox-close').click();
        });
    }
    loadChat(event) {
        let that = this;
        let e = event;
        console.log(event.params.customername);
        axios.post(event.params.url).then((response) => {
            that.chatTarget.innerHTML = response.data;
            if (event.params.customername)  {
                that.nameTarget.innerHTML = e.params.customername;
            }
            that.chatTarget.scrollTop = that.chatTarget.scrollHeight;
        });
    }
    loadCustomer(event) {
        let that = this;
        let e = event;
        console.log('loard');
        console.log(event.params);
        axios.post(event.params.url).then((response) => {
            that.panelTarget.innerHTML = response.data.panel.content;
            that.popupTarget.innerHTML = response.data.popup.content;
            that.nameTarget.innerHTML = event.params.name;
        });
    }
}