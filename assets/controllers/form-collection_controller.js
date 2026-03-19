import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["collectionContainer"]

    static values = {
        index    : Number,
        prototype: String,
    }

    addCollectionElement(event)
    {
        // const item = this.collectionContainerTarget;
        // item.innerHTML = item.innerHTML + this.prototypeValue.replace(/__name__/g, this.indexValue);

        const item = document.createElement('div');
        item.classList = 'row';
        item.innerHTML = this.prototypeValue.replace(/__name__/g, this.indexValue);

        this.collectionContainerTarget.appendChild(item);
        this.indexValue++;
    }
}