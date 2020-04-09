jQuery(function ($) {
    if ($('#_shipping_cvs_store_ID').length) {
        $('.edit_address').click(function () {
            $('a.load_customer_shipping').remove();
            $('a.billing-same-as-shipping').remove();

            $('._shipping_company_field').hide();
            $('._shipping_address_1_field').hide();
            $('._shipping_address_2_field').hide();
            $('._shipping_city_field').hide();
            $('._shipping_postcode_field').hide();
            $('._shipping_country_field').hide();
            $('._shipping_state_field').hide();
        });
    }
});
