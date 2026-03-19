
import { Controller } from '@hotwired/stimulus';
var editor;
export default class extends Controller {
    static targets = ['channel','user','time','urgent','message','iu','ic'];

    connect() {
        var content = [
            '',
        ].join('\n');
        function createSmileyButton() {
            const el = document.getElementById('slack_icons');
            // el.innerHTML = '&#128522;';
            // el.type = 'button';
            // // el. = 'button';
            // el.className = 'tui-slack';

            return el;
        }
        const options = {
            el: document.querySelector('#slack-m'),
            height: '200px',
            initialEditType: 'wysiwyg',
            previewStyle: 'tab',
            initialValue: content,
            usageStatistics: false,
            toolbarItems: [
                ['bold', 'italic','strike',{
                    name: 'yourSmileyButton',
                    tooltip: 'Smileys',
                    text: ':)',
                    popup: {
                        body: createSmileyButton()
                        }
                    }
                ],
                ['quote'],
                ['ol' /*'task', 'indent', 'outdent'*/],
                [/*'table', 'image',*/ 'link'],
                [/*'code', */'codeblock'],
                // // Using Option: Customize the last button
                // [{
                //     el: createLastButton(),
                //     command: 'bold',
                //     tooltip: 'Custom Bold'
                // }]
            ]
        };
        const Editor = toastui.Editor;
        editor = new Editor(options);
    }
    icon(event) {
        console.log(event.params.code);
        console.log('mkdwn');
        console.log(editor.getMarkdown());
        editor.setMarkdown(editor.getMarkdown()+' :'+event.params.code+':');
    }
    slack(event) {
        let t = $('input[name="u"]:checked').val();
        let val = $('#slack_user').val();
        jQuery.ajax({
            url: event.params.url,
            data: {
                'u':t,
                'now':$('input[name="time_shift"]:checked').val(),
                'time':this.timeTarget.value,
                'mkdwn':editor.getMarkdown(),
                'user':val.join(','),
                'channel':this.channelTarget.value
            },
            method: 'POST',
            success: function(data){
                let m = $('#slack_message');
                m.show();
                m.text(data);
                setTimeout(function () {
                    m.text('');
                    m.hide();
                }, 1000 );
            }
        });

    }
}