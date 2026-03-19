import './styles/admin.scss';
import 'eonasdan-bootstrap-datetimepicker';
// import 'typeahead';
// import Bloodhound from "bloodhound-js";

$(function() {
    // Datetime picker initialization.
    // See https://eonasdan.github.io/bootstrap-datetimepicker/
    $('[data-toggle="datetimepicker"]').datetimepicker({
        icons: {
            time: 'fa fa-clock-o',
            date: 'fa fa-calendar',
            up: 'fa fa-chevron-up',
            down: 'fa fa-chevron-down',
            previous: 'fa fa-chevron-left',
            next: 'fa fa-chevron-right',
            today: 'fa fa-check-circle-o',
            clear: 'fa fa-trash',
            close: 'fa fa-remove'
        }
    });
});

// Handling the modal confirmation message.
// $(document).on('submit', 'form[data-confirmation]', function (event) {
//     var $form = $(this),
//         $confirm = $('#confirmationModal');
//
//     if ($confirm.data('result') !== 'yes') {
//         //cancel submit event
//         event.preventDefault();
//
//         $confirm
//             .off('click', '#btnYes')
//             .on('click', '#btnYes', function () {
//                 $confirm.data('result', 'yes');
//                 $form.find('input[type="submit"]').attr('disabled', 'disabled');
//                 $form.submit();
//             })
//             .modal('show');
//     }
// });
