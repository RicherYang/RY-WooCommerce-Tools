import $ from 'jquery';

import './ry-shipping.scss';

$(function () {
    if ($('#_shipping_cvs_store_ID').length) {
        $('.edit_address').on('click', function () {
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
        const $metainfo = $metabox.find('.inside');
        const blockinfo = function () {
            $metainfo.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        };
        $metabox.on('click', '.ry-delete-shipping-info', function () {
            if (window.confirm(ryAdminShippingParams.i18n.delete_shipping_info)) {
                const $btn = $(this);
                blockinfo();
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'RY_delete_shipping_info',
                        id: $btn.data('id'),
                        orderid: $btn.data('orderid'),
                        _ajax_nonce: ryAdminShippingParams._nonce.delete
                    }
                }).done(function () {
                    $btn.closest('tr').remove();
                    if ($btn.closest('table').find('tbody tr').length == 0) {
                        $metainfo.find('.button:gt(0)').remove();
                    }
                }).always(function () {
                    $metainfo.unblock();
                });
            }
        });

        $metabox.on('click', '.ry-ecpay-shipping-info', function () {
            const $btn = $(this);
            blockinfo();
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'RY_ecpay_shipping_info',
                    orderid: $btn.data('orderid'),
                    temp: $btn.data('temp'),
                    collection: $btn.data('collection'),
                    _ajax_nonce: ryAdminShippingParams._nonce.get
                }
            }).always(function () {
                location.reload();
            });
        });

        $metabox.on('click', '.ry-smilepay-shipping-info', function () {
            const $btn = $(this);
            blockinfo();
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'RY_smilepay_shipping_info',
                    orderid: $btn.data('orderid'),
                    temp: $btn.data('temp'),
                    collection: $btn.data('collection'),
                    _ajax_nonce: ryAdminShippingParams._nonce.get
                },
                dataType: 'text'
            }).done(function (data) {
                if (data.substr(0, 8) == 'https://' || data.substr(0, 7) == 'http://') {
                    window.location = data;
                }
            }).always(function () {
                $metainfo.unblock();
            });
        });

        $metabox.on('click', '.ry-smilepay-shipping-no', function () {
            const $btn = $(this);
            blockinfo();
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'RY_smilepay_shipping_no',
                    id: $btn.data('id'),
                    orderid: $btn.data('orderid'),
                    _ajax_nonce: ryAdminShippingParams._nonce.smilepay
                }
            }).always(function () {
                location.reload();
            });
        });
    }

    $(document).on('change', '.ry-shipping-cost_requires', function () {
        const $form = $(this).closest('form');
        if ($form.length) {
            const $minAmountField = $form.find('.ry-shipping-min_amount')
            switch ($(this).val()) {
                case 'min_amount':
                case 'min_amount_or_coupon':
                case 'min_amount_and_coupon':
                case 'min_amount_except_discount':
                case 'min_amount_except_discount_or_coupon':
                case 'min_amount_except_discount_and_coupon':
                    if ($minAmountField.closest('tr').length == 0) {
                        $minAmountField.closest('fieldset').show();
                        $form.find('label[for="' + $minAmountField.attr('id') + '"]').show();
                    } else {
                        $minAmountField.closest('tr').show();
                    }
                    break;
                default:
                    if ($minAmountField.closest('tr').length == 0) {
                        $minAmountField.closest('fieldset').hide();
                        $form.find('label[for="' + $minAmountField.attr('id') + '"]').hide();
                    } else {
                        $minAmountField.closest('tr').hide();
                    }
                    break;
            }
        }
    });

    $('.ry-shipping-cost_requires').trigger('change');
    $(document).on('wc_backbone_modal_loaded', function (e, target) {
        if ('wc-modal-shipping-method-settings' === target) {
            const $rySelect = $('#wc-backbone-modal-dialog .ry-shipping-cost_requires');
            if ($rySelect.length) {
                $rySelect.trigger('change');
            }
        }
    });
});
