import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["hiddenInput","button","button2", "modal", "content", "answers"];


    showModal(event) {
        event.preventDefault();
        this.modalTarget.style.display = "block";
        this.contentTarget.focus();
        // Annahme, dass der Button ein <i>-Tag mit Icon-Klasse enthält
        this.buttonTarget.querySelector('i').textContent = 'cancel';
        this.buttonTarget.querySelector('i').css = 'color:red';
        this.setActionToClose();
    }
    showAnswer(event) {
        event.preventDefault();
        this.modalTarget.style.display = "block";
        this.contentTarget.focus();
        // Annahme, dass der Button ein <i>-Tag mit Icon-Klasse enthält
        this.buttonTarget.getAttribute('data-value');
        this.buttonTarget.querySelector('i').textContent = 'cancel';
        this.setAnswerActionToClose();
    }

    closeModal(event) {
        event.preventDefault();
        this.modalTarget.style.display = "none";
        this.buttonTarget.querySelector('i').textContent = 'add_circle';
        this.buttonTarget.querySelector('i').css = 'color:black';
        this.setActionToShow();
    }
    closeAnswer(event) {
        event.preventDefault();
        this.modalTarget.style.display = "none";
        this.buttonTarget.querySelector('i').textContent = 'post_add';
        this.buttonTarget.querySelector('i').css = 'color:black';
        this.setAnswerActionToShow();
    }

    setActionToClose() {
        this.buttonTarget.setAttribute('data-action', 'click->note#closeModal');
    }
    setAnswerActionToClose() {
        this.buttonTarget.setAttribute('data-action', 'click->note#closeAnswer');
    }

    setActionToShow() {
        this.buttonTarget.setAttribute('data-action', 'click->note#showModal');
    }
    setAnswerActionToShow() {
        this.buttonTarget.setAttribute('data-action', 'click->note#showAnswer');
    }
    connect() {
        const infoButton = this.button2Targets.find(button => button.getAttribute('data-value') === 'info');
        if (infoButton) {
            infoButton.classList.add('active'); // Korrekte Methode zum Hinzufügen einer Klasse
            //console.log('Info Button gefunden und aktiviert:', infoButton);
            // Optional: Setze den Wert des versteckten Inputs
            const typeSelect = document.getElementById('action_log_type');
            if (typeSelect) {
                typeSelect.value = 'info';
            }
        }
    }

    setActive(event) {
        event.preventDefault();
        this.button2Targets.forEach(button => {
            button.classList.remove('active');
        });
        const button = event.currentTarget;
        button.classList.add('active');
        this.setActiveType(button.getAttribute('data-value'));
    }

    setActiveType(value) {
        const typeSelectId = 'note_type_' + this.element.dataset.offerId;
        const typeSelect = document.getElementById(typeSelectId);

        if (typeSelect) {
            typeSelect.value = value;

            this.button2Targets.forEach(button => {
                const isActive = button.getAttribute('data-value') === value;
                button.classList.toggle('active', isActive);
            });

            //console.log('Type updated to:', value); // Debug logging
        }
    }
    saveNote(event) {
        event.preventDefault();
        const content = this.contentTarget;
        const typeSelectId = 'note_type_' + this.element.dataset.offerId;
        const typeSelect = document.getElementById(typeSelectId);
        const typeUrlId = 'note_save_url_' + typeSelect.value;
        const typeUrl = document.getElementById(typeUrlId);
        let val = content.value;
        let type = typeSelect.value;
        let typeUrl2 = typeUrl ? typeUrl.value : '/hd/app/user/';
        fetch(content.dataset.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `noteContent=${encodeURIComponent(val)}&type=${encodeURIComponent(type)}`
        }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.addAccordionItem(data, typeSelectId, typeUrl2);
                } else {
                    // Handle errors
                    console.error('Failed to save note:', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    saveAnswer(event) {
        event.preventDefault();
        const content = this.contentTarget;
        const typeUrl = document.getElementById('auser_answer_url_' + this.element.dataset.answerId);
        let val = content.value;
        let typeUrll = '/hd/app/user/';
        //console.log('Content to send:', val);

        fetch(content.dataset.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `noteContent=${encodeURIComponent(val)}&type=answer`
        }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide the modal
                    this.modalTarget.style.display = "none";
                    // Reload the window to show the new entry
                    // window.location.reload();
                    // console.log(typeUrl);
                    // console.log(typeUrll);
                    this.addAnswerItem(data, typeUrll);
                } else {
                    // Handle errors
                    console.error('Failed to save note:', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    loadUrl(event) {
        window.location.href = event.params.url;
    }
    addAccordionItem(data, id, url) {
        // Zugriff auf den accordionController
        const boxId = this.element.dataset.offerId;
        const accordion = document.getElementById('_note_answers_box_'+boxId);
        if (accordion) {
            const newItem = document.createElement('div');
            newItem.classList.add('accordion-item');

            newItem.innerHTML = `
            <div class="row">
                <div class="col-3 p-0" style="font-size: 12px;text-align: center;">
                    <img class="circle" title="${data.username}" src="${url}${data.avatar}" alt="${data.username} Avatar" style="border-radius: 50%;">
                </div>
                <div class="col-9 p-0" data-action="click->accordion#toggle">
                    <div class="accordion-title">
                        ${data.content}
                    </div>
                </div>
            </div>
            <div class="row accordion-content is-hidden">
                <div class="col-3 p-2">
                    <div class="accordion-body">
                        ${data.date}
                    </div>
                </div>
                <div class="col-9 p-2">
                    <div class="accordion-body">
                        Notiz von ${data.username} - ${data.title}
                    </div>
                </div>
            </div>
            `;

            // Das neue Element zum Accordion hinzufügen
            accordion.prepend(newItem);
        } else {
            console.error('Accordion Item Target nicht gefunden im accordionController');
        }
    }
    addAnswerItem(data, url) {
        // console.log(data);
        // Zugriff auf den accordionController
            const accordion = this.answersTarget;

            if (accordion) {
                const newItem = document.createElement('div');
                newItem.classList.add('accordion-item');

                newItem.innerHTML = `
<div class="row" id="answerRow${data.id}">
    <div class="col-3 p-0" style="font-size: 12px;text-align: center;">
        <img class="circle" title="${data.username}" src="${url}${data.avatar}" alt="${data.username} Avatar" style="width:26px;height:26px;border-radius: 50%;">
    </div>
    <div class="col-7 text-left p-1">
        <div class="accordion-title">
            ${data.content}
        </div>
    </div>
</div>
                `;

                // Das neue Element zum Accordion hinzufügen
                accordion.prepend(newItem);
            } else {
                console.error('Accordion Item Target nicht gefunden im accordionController');
            }
    }
    deleteNoteItem(event) {
        const current = event.currentTarget;
        const id = current.getAttribute('data-id');
        const url = current.getAttribute('data-url');
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        }).then(response => response.json())
            .then(data => {
                const typeSelect = document.getElementById('noteRow'+id);
                typeSelect.style.display = "none";
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    deleteAnswerItem(event) {
        const current = event.currentTarget;
        const id = current.getAttribute('data-id');
        const url = current.getAttribute('data-url');
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        }).then(response => response.json())
            .then(data => {
                const typeSelect = document.getElementById('answerRow'+id);
                typeSelect.style.display = "none";
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
}