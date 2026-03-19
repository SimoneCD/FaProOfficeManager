import { Controller } from "@hotwired/stimulus";
import axios from 'axios';
import {document} from "../../node_modules/postcss/lib/postcss.mjs";

export default class extends Controller {
    static values = {
        'template': String,
        'offer': String,
    }
    static classes = [ 'active', 'inactive' ]
    static targets = ["name", "title", "message", "src", "id", "type", "attachment", "attachmentname", "invoice"]
    changeMailTitle(event) {
        this.updateIframe(event.currentTarget, '#mail-title');
    }
    changeMailMessage(event) {
        console.log('changeMailMessage');
        this.updateIframe(event.currentTarget, '#mail-message');
    }
    setMailContent(params, data) {
        console.log('setMailContent');
        console.log(data);
        this.nameTarget.textContent =  data.name;
        this.nameTarget.value =  data.name;
        this.titleTarget.value = data.title;
        this.typeTarget.value = 'privat';
        this.messageTarget.value = '...';
        this.idTarget.value = data.id;
        //this.srcTarget.src = data.emailSrcParam;
        let that = this;
        $(document).ready(function() {
            $(".reminder_menu").css("display", "none")
            $(".email_menu").css("display", "inline")
            console.log(params.attachurl);
            axios.get(params.attachurl).then((response) => {
                $('#offerattachsId').html(response.data);
                $('#default_message').click();
            },(error) => {
                console.log('error');
                console.log(error);
            });
        });
    }
    setReminderContent(params, data) {
        console.log('setReminderContent');
        console.log(Object.keys(data));
        console.log(Object.keys(params));
        this.nameTarget.textContent =  data.name;
        this.nameTarget.value =  data.name;
        this.invoiceTarget.value = data.invoice;
        this.titleTarget.value = data.title;
        this.typeTarget.value = data.type;
        this.messageTarget.value = '...';
        this.idTarget.value = data.id;

        //this.srcTarget.src = data.emailSrcParam;
        // action:"click->email#launchReminder"
        // emailAttachurlParam:"/ajax/ajax-mail/attachments/2206"
        // emailSrcParam:"/ajax/ajax-mail/create/1115/reminder_1"
        // emailUrlParam:"/ajax/ajax-mail/load/1115/detail/reminder_1?invoice=472"
        // id:"1115"
        // invoice:"472"
        // name:" Michael Foss (mfoss@volleasy.de)"
        // offer:"2206"
        // title:"Neue Nachricht"
        // type:"1"
        let that = this;
        $(".reminder_menu").css("display", "inline");
        $(".email_menu").css("display", "none");
        axios.get(data.emailUrlParam).then((response) => {
            console.log(response.data);
            $('.reminder_menu .offerattachsId').html(response.data);
            $(".reminder_menu .mr_" + data.type).click();
        },(error) => {
            console.log('error');
            console.log(error);
        });
    }
    changeMailContent(event) {
        console.log('changeMailContent');
        this.templateValue = event.currentTarget.value;
        let src = event.params.src;/*'/ajax/ajax-mail/create/'+this.idTarget.value+'/'+this.templateValue;*/
        src = src.replace('iiii', this.idTarget.value);
        this.srcTarget.src = src.replace('vvvv', this.invoiceTarget.value);
        let that = this;
        $('.start_btn').removeClass('btn-primary');
        // $(that).addClass('btn-primary')
        event.currentTarget.classList.add('btn-primary');
        this.typeTarget.value = (this.templateValue.length ? this.templateValue :  'default');
        let load = event.params.load;/*'/ajax/ajax-mail/load/'+this.idTarget.value+'/detail/'+this.typeTarget.value*/;
        load = load.replace('iiii', this.idTarget.value);/*'/ajax/ajax-mail/load/'+this.idTarget.value+'/detail/'+this.typeTarget.value*/;
        load = load.replace('vvvv', this.invoiceTarget.value);/*'/ajax/ajax-mail/load/'+this.idTarget.value+'/detail/'+this.typeTarget.value*/;
        axios.get(load).then((response) => {
            $(that).addClass('btn_primary');
            that.titleTarget.value = response.data.title;
            that.messageTarget.value = response.data.message;

            var editor = CKEDITOR.instances['sf_textarea'];
            if (editor) {
                console.log('hasE');
                CKEDITOR.instances['sf_textarea'].setData(response.data.message);
                CKEDITOR.instances['sf_textarea'].updateElement();
            } else {
                console.log('loadE');this.loadEditor();
            }
        },(error) => {
            console.log('error');
            console.log(error);
        });

    }
    loadEditor() {
        console.log('loadEditor');
        //if (CKEDITOR.instances['sf_textarea']) { console.log('destroy');CKEDITOR.instances['sf_textarea'].destroy(true); }
        let vars = {
            toolbarCanCollapse: true,
            scayt_autoStartup:true,
            scayt_sLang:'de_DE',
            modal: true,
            autoOpen: false,
            language: 'de',
            uiColor: '#ffffff',
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
            removeButtons: 'Subscript,Superscript,Copy,Image,Paste,PasteText,PasteFromWord,Cut,Maximize,About'
        };


        var editor = CKEDITOR.instances['sf_textarea'];
        if (editor) {
            console.log('editor exist');
        } else {
            console.log('not exist');
            CKEDITOR.replace('sf_textarea', vars);
            CKEDITOR.on('instanceReady', function(){
                $.each( CKEDITOR.instances, function(instance) {
                    CKEDITOR.instances[instance].on("change", function(e) {
                        for ( instance in CKEDITOR.instances ) {
                            console.log('update element 2');
                            CKEDITOR.instances[instance].updateElement();
                            console.log(CKEDITOR.instances[instance].getData());
                            //$('#sf_textarea').val(CKEDITOR.instances[instance].getData());
                            $('#sf_textarea').click();
                        }
                    });
                });
            });
        }

    }
    saveMailContent(event) {
        console.log('saveMailContent');
        for ( var instance in CKEDITOR.instances ) { CKEDITOR.instances[instance].updateElement(); }
        axios.post('/ajax/ajax-mail/send/'+this.idTarget.value+'/'+(this.templateValue ?? 'default'),
            {
                'title': this.titleTarget.value,
                'message': this.messageTarget.value,
                'type': (this.templateValue ?? 'default'),
                'attachment': this.attachmentTarget.value,
                'attachmentName': this.attachmentnameTarget.value
            },
            {'method': 'post'}
        ).then((response) => {
            $('.email_modal').find('.close').click();
        },(error) => {
            console.log('error');
            console.log(error);
        });
    }

    updateIframe(target, messageID) {
        console.log('updateIframe');
        let iWin = this.srcTarget.contentWindow;
        let iDoc = iWin.document;
        if(iWin.$ === undefined){
            let jq = iDoc.createElement('script');
            jq.type = 'text/javascript';
            jq.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
            iDoc.head.appendChild(jq);
            let jqS = iDoc.createElement('script');
            jqS.type = 'text/javascript';
            jqS.src = 'https://code.jquery.com/ui/1.13.0/jquery-ui.min.js';
            iDoc.head.appendChild(jqS);
        }
        if (messageID === '#mail-message') {
            this.messageTarget.value = target.value;
        } else if (messageID === '#mail-title') {
            this.titleTarget.value = target.value;
        }
        if (iWin['$'] !== undefined && iWin['$'].length) {
            let div = iWin['$'](messageID)[0];
            div.innerHTML = target.value;
        }

    }
}