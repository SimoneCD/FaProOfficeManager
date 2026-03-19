import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

export default class extends Controller
{
    static targets = ['protocolcontent'];

    loadProtocolPage(event) {
        let that = this;
        $('.innerCheckMenu').removeClass('btn_primary');
        $(event.currentTarget).addClass('btn_primary');
        jQuery.ajax({
            url: event.params.url,
            data: {},
            method: 'POST',
            success: function(xr){
                that.protocolcontentTarget.innerHTML = xr;

                signSignatur('1');
                signSignatur('2');
                signSignatur('3');
            }
        });
    }

    submitProtocol(event) {
        event.preventDefault();
        let formData = new FormData(document.querySelector('form[id="signatureForm"]'));
        let that = this;
        let form = $('form#signatureForm');
        $.ajax({
            'url': document.querySelector('form[id="signatureForm"]').attr('data-action'),
            'data': formData,
            'method': 'post',
            success: function (rx) {
                // console.log(rx);
                // window.location.reload();
            }
        });
    }
}
