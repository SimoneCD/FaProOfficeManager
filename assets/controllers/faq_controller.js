import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

export default class extends Controller
{
    static values = {
        'id': String
    };
    static targets = ['video', 'image', 'fileclick', 'faqmodal'];

    editFaq(event) {
        let modalController = this.application.getControllerForElementAndIdentifier(
            this.faqmodalTarget,
            "modal"
        );
        modalController.open();
        CKEDITOR.replace((document.querySelector(event.params.id) || event.params.id), {
            toolbarCanCollapse: true,
            modal: true,
            autoOpen: false,
            language: event.params.lang,
            uiColor: event.params.color,
            toolbarGroups: [
                { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
                { name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
                { name: 'links', groups: [ 'links' ] },
                { name: 'insert', groups: [ 'insert' ] },
                { name: 'forms', groups: [ 'forms' ] },
                { name: 'tools', groups: [ 'tools' ] },
                { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
                { name: 'others', groups: [ 'others' ] },
                '/',
                { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
                { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
                { name: 'styles', groups: [ 'styles' ] },
                { name: 'colors', groups: [ 'colors' ] },
                { name: 'about', groups: [ 'about' ] }
            ],
            removeButtons: 'Subscript,Superscript,Copy,Paste,PasteText,PasteFromWord,Cut,Maximize,About'
        });
    }
    close(event) {
        let elm = $('div[data-popover-target="card"]');
        if (elm) {
            elm.remove();
        }
    }
    submitFaq(event) {
        for ( var instance in CKEDITOR.instances ) { CKEDITOR.instances[instance].updateElement(); }
        let data = new FormData(document.querySelector('form[name="faq"]'));
        let that = this;
        jQuery.ajax({
            url: event.params.url,
            data: data,
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST', // For jQuery < 1.9
            success: function(data){
                let modalController = that.application.getControllerForElementAndIdentifier(
                    that.faqmodalTarget,
                    "modal"
                );
                modalController.close();
                window.location.reload();
            }
        });
    }
    removeVideo(event) {
        jQuery.ajax({
            url: event.params.url,
            data: {},
            method: 'POST',
            success: function(data){
                $('.videoview').remove();
            }
        });
    }
    removeImage(event) {
        jQuery.ajax({
            url: event.params.url,
            data: {},
            method: 'POST',
            success: function(data){
                $('.imageview').remove();
            }
        });
    }
}
