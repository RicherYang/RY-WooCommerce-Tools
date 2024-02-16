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

    let $metabox = $('#ry-ecpay-shipping-info');
    if ($metabox.length == 0) {
        $metabox = $('#ry-newebpay-shipping-info');
    }
    if ($metabox.length == 0) {
        $metabox = $('#ry-smilepay-shipping-info');
    }
    if ($metabox.length) {
        $metabox.on('click', '.ry-delete-shipping-info', function () {
            if (window.confirm(ry_wt_admin_shipping.i18n.delete_shipping_info)) {
                let $btn = $(this),
                    $tr = $(this).closest('tr');
                $tr.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
                $.post(ry_wt_admin_shipping.ajax_url, {
                    action: 'RY_delete_shipping_info',
                    id: $btn.data('id'),
                    orderid: $btn.data('orderid'),
                    security: ry_wt_admin_shipping.nonce.delete_shipping_info
                }, function () {
                    let $table = $tr.closest('table');
                    $tr.remove();
                    if ($table.find('tbody tr').length == 0) {
                        $metabox.find('.inside .button:gt(0)').remove();
                    }
                }, 'text');
            }
        });

        $metabox.on('click', '.ry-ecpay-shipping-info', function () {
            let $btn = $(this);
            $metabox.find('.inside').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $.post(ry_wt_admin_shipping.ajax_url, {
                action: 'RY_ecpay_shipping_info',
                orderid: $btn.data('orderid'),
                temp: $btn.data('temp'),
                collection: $btn.data('collection'),
                security: ry_wt_admin_shipping.nonce.get_shipping_info
            }, function (html_str) {
                $metabox.find('.inside').html(html_str);
            }, 'html');
        });

        $metabox.on('click', '.ry-smilepay-shipping-info', function () {
            let $btn = $(this);
            $metabox.find('.inside').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $.post(ry_wt_admin_shipping.ajax_url, {
                action: 'RY_smilepay_shipping_info',
                orderid: $btn.data('orderid'),
                temp: $btn.data('temp'),
                collection: $btn.data('collection'),
                security: ry_wt_admin_shipping.nonce.get_shipping_info
            }, function (text_str) {
                $metabox.find('.inside').unblock();
                if (text_str.substr(0, 8) == 'https://') {
                    window.location = text_str;
                }
            }, 'text');
        });

        $metabox.on('click', '.ry-smilepay-shipping-no', function () {
            let $btn = $(this),
                $tr = $(this).closest('tr');
            $tr.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
            $.post(ry_wt_admin_shipping.ajax_url, {
                action: 'RY_smilepay_shipping_no',
                id: $btn.data('id'),
                orderid: $btn.data('orderid'),
                security: ry_wt_admin_shipping.nonce.smilepay_shipping_no
            }, function (html_str) {
                $metabox.find('.inside').html(html_str);
            }, 'html');
        });
    }
});
