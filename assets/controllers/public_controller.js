import { Controller } from "@hotwired/stimulus";
import axios from 'axios';

export default class extends Controller {
    static targets = [ 'loadData', 'loadTitle', 'chat', 'message' ];
    static values = {
        'customer': Number,
    }
    sendMessage(evebt) {
        let that = this;
        axios.post(event.params.url, {
            'note':this.messageTarget.value,
            'customer': evebt.params.customer
        }).then((response) => {
            if (response.data === false) {
                //window.location.href = window.location+'/logout';
            }
            that.chatTarget.innerHTML = response.data;
            location.hash = "#loadedMessage";
        });
    }
    dataWindowLoader(event) {
        let target = event.currentTarget;
        let that = this;
        axios.post(event.params.url,{
            'customer':event.params.customer
        }).then((response) => {
            if (response.data === false) {
                //window.location.href = window.location+'/logout';
            }
            that.loadDataTarget.innerHTML = response.data;
            location.hash = '#';
            location.hash = "#loadData";
        });
    }
}