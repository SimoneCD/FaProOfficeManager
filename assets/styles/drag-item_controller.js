import { Controller } from 'stimulus'
import axios from 'axios'

export default class extends Controller {
    static values = {
        offerId: String,
        offerNumber: String,
        offerAllow: Array,
        offerFrom: String,
    }
    static targets = [ "box", "item" ]

    dragstart(event) {
        console.log('dragstart')
        if ('' === event.target.getAttribute("data-allow")) {
            event.dataTransfer.setData("application/drag-key", event.target.getAttribute("data-allow"))
            event.dataTransfer.effectAllowed = "move"
        }
        console.log(event.target)
    }
    dragover(event) {
        console.log('dragover')
        event.preventDefault()
        return true
    }
    dragenter(event) {
        console.log('dragenter')
        console.log(event.target)
        event.preventDefault()
    }
    drop(event) {
        console.log('drop')
        var data = event.dataTransfer.getData("application/drag-key")
        const dropTarget = event.target

        console.log(data)
        console.log(dropTarget)
        // if (event.target.hasOwnProperty('data-from') && event.target.getAttribute("data-from"))
        // const draggedItem = this.element.querySelector(`[data-st-offer='${data}']`);
        // const positionComparison = dropTarget.compareDocumentPosition(draggedItem)
        // if ( positionComparison & 4) {
        //     event.target.insertAdjacentElement('beforebegin', draggedItem)
        // } else if ( positionComparison & 2) {
        //     event.target.insertAdjacentElement('afterend', draggedItem)
        // }
        event.preventDefault()
    }
    dragend(event) {
        console.log('dragged')
    }
}
