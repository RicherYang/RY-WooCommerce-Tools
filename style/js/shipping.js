let ecpayShippingInfo;

jQuery(function ($) {
    $('.woocommerce-checkout p.ry-hide').hide();

    $(document.body).on('updated_checkout', function (e, data) {
        if (data !== undefined) {
            if (data.fragments.ecpay_shipping_info !== undefined) {
                if (data.fragments.ecpay_shipping_info.postData === undefined) {
                    $('.woocommerce-shipping-fields__field-wrapper p.cvs-info').hide();
                    $('.woocommerce-shipping-fields__field-wrapper p:not(.cvs-info)').show();
                    $('.woocommerce-shipping-fields__field-wrapper p#shipping_phone_field').show();
                    RYECPayRemoveSendCvs();
                } else {
                    ecpayShippingInfo = data.fragments.ecpay_shipping_info.postData;
                    $('.woocommerce-shipping-fields__field-wrapper p:not(.cvs-info)').hide();
                    $('.woocommerce-shipping-fields__field-wrapper p#shipping_first_name_field').show();
                    $('.woocommerce-shipping-fields__field-wrapper p#shipping_last_name_field').show();
                    $('.woocommerce-shipping-fields__field-wrapper p#shipping_country_field').show();
                    $('.woocommerce-shipping-fields__field-wrapper p#shipping_phone_field').show();
                    $('.woocommerce-shipping-fields__field-wrapper p.cvs-info').show();
                    if ($('#ship-to-different-address-checkbox').prop('checked') === false) {
                        $('#ship-to-different-address-checkbox').click();
                    }
                    if ($('input#LogisticsSubType').length) {
                        if ($('input#LogisticsSubType').val() == ecpayShippingInfo.LogisticsSubType) {
                            $('#CVSStoreName_field strong').text($('input#CVSStoreName').val());
                            $('#CVSAddress_field strong').text($('input#CVSAddress').val());
                            $('#CVSTelephone_field strong').text($('input#CVSTelephone').val());

                            if ($('input#CVSStoreName').val() != '') {
                                $('.choose_cvs .show_choose_cvs_name').show();
                                $('.choose_cvs .choose_cvs_name').text($('input#CVSStoreName').val());
                            }
                        } else {
                            RYECPayRemoveSendCvs();
                        }
                    } else {
                        RYECPayRemoveSendCvs();
                    }
                }
            } else if (data.fragments.newebpay_shipping_info !== undefined) {
                $('.woocommerce-shipping-fields__field-wrapper p').hide();
                if ($('#ship-to-different-address-checkbox').prop('checked') === false) {
                    $('#ship-to-different-address-checkbox').click();
                }
            } else if (data.fragments.smilepay_shipping_info !== undefined) {
                $('.woocommerce-shipping-fields__field-wrapper p:not(.cvs-info)').hide();
                $('.woocommerce-shipping-fields__field-wrapper p#shipping_first_name_field').show();
                $('.woocommerce-shipping-fields__field-wrapper p#shipping_last_name_field').show();
                $('.woocommerce-shipping-fields__field-wrapper p#shipping_country_field').show();
                $('.woocommerce-shipping-fields__field-wrapper p#shipping_phone_field').show();
                $('.woocommerce-shipping-fields__field-wrapper p.cvs-info').show();
                $('.woocommerce-shipping-fields__field-wrapper #CVSStoreName_field').hide();
                $('.woocommerce-shipping-fields__field-wrapper #CVSAddress_field').hide();
                $('.woocommerce-shipping-fields__field-wrapper #CVSTelephone_field').hide();
                if ($('#ship-to-different-address-checkbox').prop('checked') === false) {
                    $('#ship-to-different-address-checkbox').click();
                }
            } else {
                $('.woocommerce-shipping-fields__field-wrapper p.cvs-info').hide();
                $('.woocommerce-shipping-fields__field-wrapper p:not(.cvs-info)').show();
                RYECPayRemoveSendCvs();
            }
        }

        if (window.sessionStorage.getItem('RYECPayTempCheckoutForm') !== null) {
            let formData = JSON.parse(window.sessionStorage.getItem('RYECPayTempCheckoutForm')),
                notSetData = ['LogisticsSubType', 'CVSStoreID', 'CVSStoreName', 'CVSAddress', 'CVSTelephone', 'terms'];
            for (const key in formData) {
                if (formData[key].name.substr(0, 1) == '_') {
                } else if (notSetData.includes(formData[key].name)) {
                } else {
                    let $item = jQuery('[name="' + formData[key].name + '"]');
                    switch ($item.prop('tagName')) {
                        case 'INPUT':
                            if ($item.attr('type') == 'checkbox') {
                                if ($item.prop('checked') === false) {
                                    $item.trigger('click');
                                }
                                break;
                            }
                            if ($item.attr('type') == 'radio') {
                                $item = jQuery('[name="' + formData[key].name + '"][value="' + formData[key].value + '"]');
                                if ($item.prop('checked') === false) {
                                    $item.trigger('click');
                                }
                                break;
                            }
                        case 'TEXTAREA':
                        case 'SELECT':
                            const oldVal = $item.val();
                            $item.val(formData[key].value);
                            if (oldVal != formData[key].value) {
                                $item.trigger('change');
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
            window.sessionStorage.removeItem('RYECPayTempCheckoutForm');
        }
    });
});

function RYECPaySendCvsPost(postUrl) {
    if (postUrl === undefined && ry_shipping_params !== undefined) {
        postUrl = ry_shipping_params.postUrl;
    }
    window.sessionStorage.setItem('RYECPayTempCheckoutForm', JSON.stringify(jQuery('form.checkout').serializeArray()));
    let html = '<form id="RYECPaySendCvsForm" action="' + postUrl + '" method="post">';
    for (const key in ecpayShippingInfo) {
        html += '<input type="hidden" name="' + key + '" value="' + ecpayShippingInfo[key] + '">';
    }
    if (window.innerWidth < 1024) {
        html += '<input type="hidden" name="Device" value="1">';
    }
    html += '</form>';
    document.body.innerHTML += html;
    document.getElementById('RYECPaySendCvsForm').submit();
}

function RYECPayRemoveSendCvs() {
    jQuery('input#CVSStoreName').remove();
    jQuery('input#CVSAddress').remove();
    jQuery('input#CVSTelephone').remove();
    jQuery('#CVSStoreName_field strong').text('');
    jQuery('#CVSAddress_field strong').text('');
    jQuery('#CVSTelephone_field strong').text('');
    jQuery('.choose_cvs .show_choose_cvs_name').hide();
}
