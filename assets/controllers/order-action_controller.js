import { Controller } from "@hotwired/stimulus";
import axios from 'axios';

export default class extends Controller {
    static values = {
        'url': String,
        'id': Number,
        'price': Number,
        'amount': Number,
        'offers': Array,
        'sendurl': String,
    }
    static classes = [ 'active', 'inactive' ]
    static targets = ['name', 'checkbox','url'];

    checkboxChanger(event) {
        let target = event.currentTarget;
        axios.post(event.params.url).then((response) => {
            console.log(target);
            let opElm = $('#product_order_price');
            let aElm = $('#product_order_amount');

            let newAmount = parseFloat(response.data.amount);
            let newPrice = parseFloat(response.data.price);

            let oldAmount = parseFloat(aElm.val());
            let oldPrice = parseFloat(opElm.val());

            let replacePrice;
            let replaceAmount;
            if (target.checked) {
                replaceAmount = (newAmount+oldAmount);
                replacePrice = (newPrice+oldPrice);
            } else {
                replaceAmount = (oldAmount-newAmount);
                replacePrice = (oldPrice-newPrice);
            }

            let price = (parseFloat(replacePrice)).toFixed(2);
            aElm.val(replaceAmount);
            opElm.val(price);
        },(error) => {
            console.log('error');
            console.log(error);
        });
        // this.checkboxTargets.forEach((child) => {
        //      console.log(event.params.url);
        //      console.log(event.currentTarget.value);
        //      console.log(child.disabled);
        //      console.log(child.checked);
        // });
        // fetch(this.urlValue).then(
        //     /* … */
        //     this.formTarget.innerHTML = '<div>test</div>'
        // );
    }
    orderComplete(event) {
        let amount = $('#product_order_amount').val();
        let info = $('#product_order_name').val();
        let price = $('#product_order_price').val();
        let url = $('#target_url').attr('data-sendurl');
        let offers;
        var selected = [];
        $('#checkboxes input:checked').each(function() {
            selected.push($(this).attr('name'));
        });
        $('input[name="offerId"]:checked').each(function (elm, i) {
            console.log($(elm).attr('checked'));
            console.log($(elm).attr('disabled'));
            console.log(i);
            selected.push($(this).val());
        });
        console.log(event.params);
        console.log(selected);
        console.log(url);
        axios.post(url, {
            'amount':amount+'',
            'price':price+'',
            'name':info+'',
            'offers':selected
        }).then((response) => {
            $('#order_modal_close').click();
            window.location.reload();
        },(error) => {
            console.log('error');
            console.log(error);
        });
    }
    setOrderContent(data) {
        console.log(data);
        this.nameTarget.textContent = data.name;
        this.idValue = data.id;
        let offers = data.offers.split(',');
        axios.post(data.url, {'offers': offers}).then((response) => {
            console.log(response);
            $('#modalContent').html(response.data);
        },(error) => {
            console.log('error');
            console.log(error);
        });
    }
}