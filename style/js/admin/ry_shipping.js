jQuery(function ($) {
    if ($('#_shipping_cvs_store_ID').length) {
        $('a.load_customer_shipping').remove();
        $('a.billing-same-as-shipping').remove();

        $('#_shipping_company').parent().hide();
        $('#_shipping_address_1').parent().hide();
        $('#_shipping_address_2').parent().hide();
        $('#_shipping_city').parent().hide();
        $('#_shipping_postcode').parent().hide();
        $('#_shipping_country').parent().hide();
        $('#_shipping_state').parent().hide();
    }
});
