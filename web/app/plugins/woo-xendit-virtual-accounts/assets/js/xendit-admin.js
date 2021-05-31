jQuery(function($) {
    $( '.do-manual-refund' ).click(function(e) {
        ajaxCompleteHandler();
    });

    function ajaxCompleteHandler () {
        $(document).ajaxComplete(function (e, xhr, options) {
            xenditRedirect(xhr.responseJSON);
        });
    }

    function xenditRedirect (response) {
        var amount = $('#refund_amount').val();
        var invoiceForm = $('input[value="Xendit_invoice"]').attr('id');

        if (!invoiceForm) {
            return;
        }

        var invoiceInputId = invoiceForm.replace('key', 'value');
        var invoiceId = $('#' + invoiceInputId).val();
        var token = encodeURIComponent(xendit_pub_api_key);

        if (parseInt(amount) >= 10000) {
            window.open('https://tpi.xendit.co/payment/xendit/invoice/' + invoiceId + '/refund-request?token=' + token + '&amount=' + amount);
        }
    }
}); 