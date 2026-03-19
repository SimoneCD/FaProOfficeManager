import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

export default class extends Controller {
    static values = {
        'offerId': String,
        'question': Number,
        'imgCount': Number,
        'imgName': String,
        'docCount': Number,
        'docName': String,
    }

    static targets = [ 'preview', 'name', 'image', 'document','action' ,'fileclick']

    clickImage(event) {
        // let elm = event.currentTarget.getAttribute('data-file-id')
        let elm = this.fileclickTarget;
        elm.click();
     }
    clickDocument(event) {
        let elm = this.fileclickTarget;
        elm.click();
    }
    clickPreview(event) {
        console.log(event.currentTarget.src);
    }
    remove(event) {
        let elmId = event.currentTarget.getAttribute('data-image-id');
        let url = event.currentTarget.getAttribute('data-url');
        let loopId = event.currentTarget.getAttribute('data-loop-id');

        if (event.currentTarget.getAttribute('data-url')) {
            axios.post(url,
                {},
                {'method': 'post'}
            ).then (function (result){
                $('.gal-'+elmId).remove();
            });
        }
    }
    removeDoc(event) {
        if (event.currentTarget.getAttribute('data-upload-url-param')) {
            axios.post(event.params.url,
                {},
                {'method': 'post'}
            ).then (function (result){
                console.log(event.params.id);
                $('.doc-'+event.params.id).remove();
            });
        }
    }
    addImagePreview(event) {
        const current = event.currentTarget;
        let url = event.currentTarget.getAttribute('data-url');
        for (let fi = 0; fi<current.files.length;fi++) {
            let fileX = current.files[fi];
            let that = this;
            let reader = new FileReader();
            reader.readAsText(fileX, 'UTF-8');
            reader.onload = function(event) {
                var formData = new FormData();
                // add assoc key values, this will be posts values
                formData.append("file", fileX, fileX.name);
                formData.append("upload_file", true);
                $.ajax({
                    type: "POST",
                    url: url,
                    data: formData,
                    success: function (data) {
                        that.previewTarget.innerHTML = (that.previewTarget.innerHTML+data);
                    },
                    error: function (error) {
                    },
                    async: true,
                    cache: false,
                    contentType: false,
                    processData: false,
                    timeout: 60000
                });
            };
        }
    }
    addDocumentPreview(event)
    {

        const current = event.currentTarget;
        let url = event.currentTarget.getAttribute('data-url');
        let that = this;
        for (let fi = 0; fi<current.files.length;fi++) {
            let fileX = current.files[fi];
            let that = this;
            let reader = new FileReader();
            reader.readAsText(fileX, 'UTF-8');
            reader.onload = function (event) {
                var formData = new FormData();
                // add assoc key values, this will be posts values
                formData.append("file", fileX, fileX.name);
                formData.append("upload_file", true);
                $.ajax({
                    type: "POST",
                    url: url,
                    data: formData,
                    success: function (data) {
                        that.previewTarget.innerHTML = (that.previewTarget.innerHTML+data);
                    },
                    error: function (error) {
                    },
                    async: true,
                    cache: false,
                    contentType: false,
                    processData: false,
                    timeout: 60000
                });
            };
        }
    }
}
