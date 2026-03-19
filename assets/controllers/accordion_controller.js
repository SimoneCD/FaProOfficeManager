import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["accordionItem", "content"]; // Beide Targets sind notwendig

    toggle(event) {
        event.preventDefault();
        const accordionItem = event.target.closest('.accordion-item');
        const contents = accordionItem.querySelectorAll('.accordion-content');
        contents.forEach(content => {
            if (content.classList.contains('is-hidden')) {
                console.log('Opening', content);
            } else {
                console.log('Closing', content);
            }
            content.classList.toggle('is-hidden');
        });
    }

    toggleAll(event) {
        event.preventDefault();
        const allHidden = Array.from(this.accordionItemTargets).every(item =>
            item.querySelector('.accordion-content.is-hidden')
        );

        this.accordionItemTargets.forEach(item => {
            const contents = item.querySelectorAll('.accordion-content');
            if (allHidden) {
                contents.forEach(content => {
                    content.classList.remove('is-hidden');
                    item.classList.add('active');
                });
            } else {
                contents.forEach(content => {
                    content.classList.add('is-hidden');
                    item.classList.remove('active');
                });
            }
        });
    }
}