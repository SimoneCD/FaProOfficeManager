function delay(callback, ms) {
    var timer = 0;
    return function() {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            callback.apply(context, args);
        }, ms || 0);
    };
}
$(document).ready(function (){
    $(document).on('change', '.customer_select', function () {
        if ($(this).val() === '0') {
            $('#step').show();
        } else {
            $('#step').hide();
        }
    });

    $(document).on('submit', 'form[name="inquiry"]', function () {
        if ($('.customer_select').val() !== '0') {
            $('#step').text();
        }
    });
    function calc()
    {
        if ($('#product_tax') !== undefined) {
            let tax = parseFloat($('#product_tax').val());
            let price = parseFloat($('#product_price').val());
            if (price !== '' && tax !== '') {
                let brutto = (tax*price/100)+price;
                $('#brutto_calc').text(new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(brutto));
            }
        }
    }

    $(document).on('click', '#save_booking', function (e) {
        e.preventDefault();
        let offer = $(this).attr('data-id');
        let url = $('#ttrz'+offer).attr('data-action');
        $.ajax({
            'url': url + '',
            'data': $('#ttrz'+offer).serialize(),
            'method': 'post',
            'success': function (ax) {
                $('#t-blocks'+offer).append(ax);
                $('#customer_j_'+offer).addClass('active_box');
                $('#customer_j_'+offer).addClass('active_but_icon');
                $('#terminModal .close').click();
            }
        });
    });
    $(document).on('change', '.ajax_update_offer_status', function (e) {
        $.ajax({
            'url': $(this).attr('data-action') + '',
            'data': {
                'status': $(this).val(),
            },
            'method': 'post',
        });
    });
    $(document).on('click', '.deleteAjaxTermin', function (e) {
        let formId =  $(this).attr('data-id');
        if (confirm('\nTermin '+formId+' löschen?'))
            $.ajax({
                'url': $('#deleteForm'+formId).attr('data-action')+ '',
                'data': $('#'+formId).serialize(),
                'method': 'post',
                'success': function (ax) {
                    $('#tb'+formId).hide();
                    if ($('#deleteTr'+formId) !== undefined) {
                        $('#deleteTr'+formId).hide();
                    }
                }
            });
    })
    $(document).on('change', '.user_called', function (e) {
        $.ajax({
            'url': $(this).attr('data-action').replace('cccc', ($(this).prop('checked') ? '1':'0')),
            'data': {'called': $(this).is(':checked')},
            'method': 'post',
        });
    });
    $(document).on('change', '.user_solar', function (e) {
        let that = this;
        $.ajax({
            'url': $(this).attr('data-action').replace('cccc', ($(this).prop('checked') ? '1':'0')),
            'data': {'solar': $(this).is(':checked')},
            'method': 'post',
            'success': function (ax) {
                if (ax === true) {
                    $(that).closest('.customer_block').html('');
                }
            }
        });
    });
    $(document).on('change', '.user_urgent', function (e) {
        let dataClass = $(this).attr('data-mclass');
        let checked = $(this).prop('checked') ;
        $.ajax({
            'url': $(this).attr('data-action').replace('cccc',  (checked ? '1' : '0')),
            'data': {'urgent': checked},
            'method': 'post',
            'success': function () {
                if (checked) {
                    $('#'+dataClass).removeClass('d-none');
                } else {
                    $('#'+dataClass).addClass('d-none');
                }
            }
        });
    });
    $(document).on('change', '.filter', function (e) {
        $(this).closest('form').find('.btn-primary').click();
    });
    $(document).on('change', '.user_delete', function (e) {
        let dataClass = $(this).attr('data-mclass');
        let checked = $(this).prop('checked') ;
        $.ajax({
            'url': $(this).attr('data-action').replace('cccc', (checked ? '1' : '0')),
            'data': {'delete': $(this).is(':checked')},
            'method': 'post',
            'success': function () {
                if (checked) {
                    $('#'+dataClass).removeClass('d-none');
                } else {
                    $('#'+dataClass).addClass('d-none');
                }
            }
        });
    });

    $(document).on('change', '.user_inquiry', function (e) {
        let url = $(this).attr('data-action');
        url = url.replace('uuuu', $(this).val())
        $.ajax({
            'url': url,
            'data': {},
            'method': 'post',
            'success': function (ax) {
                if (ax === true) {
                    $('#montur_boods').click();//window.location.reload();
                }
            }
        });
    });
    $(document).on('change', '.user_push', function (e) {
        let url = $(this).attr('data-action');
        $.ajax({
            'url': url,
            'data': $(this).serialize(),
            'method': 'post',
            'success': function (ax) {
                if (ax === true) {
                    $('#montur_boods').click();//window.location.reload();
                }
            }
        });
    });
});
