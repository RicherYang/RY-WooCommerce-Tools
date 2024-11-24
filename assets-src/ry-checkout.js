import $ from 'jquery';

let ecpayShippingInfo;

$(function () {
    const setShippingPhone = function (required) {
        if (typeof RyCheckoutParams !== 'undefined') {
            $('.woocommerce-checkout #shipping_phone_field label .required').remove();
            $('.woocommerce-checkout #shipping_phone_field label .optional').remove();
            if (required) {
                $('.woocommerce-checkout #shipping_phone_field').addClass(['validate-required']);
                $('.woocommerce-checkout #shipping_phone_field label').append(RyCheckoutParams.i18n.required);
            } else {
                $('.woocommerce-checkout #shipping_phone_field').removeClass(['validate-required']);
                $('.woocommerce-checkout #shipping_phone_field label').append(RyCheckoutParams.i18n.optional);
            }
            $('.woocommerce-checkout #shipping_phone').trigger('change');
        }
    }

    $(document.body).on('updated_checkout', function (e, data) {
        if (data !== undefined) {
            ecpayShippingInfo = undefined;
            $('.woocommerce-checkout .ry-cvs-hide').show();
            $('.woocommerce-checkout .ry-ecpay-cvs-hide').show();
            $('.woocommerce-checkout .ry-newebpay-cvs-hide').show();
            $('.woocommerce-checkout .ry-smilepay-cvs-hide').show();
            if (data.fragments.ry_shipping_info !== undefined) {
                if (data.fragments.ry_shipping_info.ecpay_cvs === true) {
                    setShippingPhone(true);
                    ecpayShippingInfo = data.fragments.ry_shipping_info.postData;
                    $('.woocommerce-checkout .ry-cvs-hide').hide();
                    $('.woocommerce-checkout .ry-ecpay-cvs-hide').hide();

                    $('.ry-cvs-store-info').hide();
                    if ($('input#RY_CVSStoreID').val() != '') {
                        $('.ry-cvs-store-info').show();
                        $('.ry-cvs-store-info > span').hide();
                        if ($('input#RY_CVSStoreName').val() != '') {
                            $('.ry-cvs-store-info .store-name').text($('input#RY_CVSStoreName').val())
                                .parent().show();
                        }
                        if ($('input#RY_CVSAddress').val() != '') {
                            $('.ry-cvs-store-info .store-address').text($('input#RY_CVSAddress').val())
                                .parent().show();
                        }
                        if ($('input#RY_CVSTelephone').val() != '') {
                            $('.ry-cvs-store-info .store-telephone').text($('input#RY_CVSTelephone').val())
                                .parent().show();
                        }
                    }
                }
                if (data.fragments.ry_shipping_info.ecpay_home === true) {
                    setShippingPhone(true);
                }

                if (data.fragments.ry_shipping_info.newebpay_cvs === true) {
                    setShippingPhone(false);
                    $('.woocommerce-checkout .ry-cvs-hide').hide();
                    $('.woocommerce-checkout .ry-newebpay-cvs-hide').hide();
                }

                if (data.fragments.ry_shipping_info.smilepay_cvs === true) {
                    setShippingPhone(true);
                    $('.woocommerce-checkout .ry-cvs-hide').hide();
                    $('.woocommerce-checkout .ry-smilepay-cvs-hide').hide();
                }
            } else {
                setShippingPhone(false);
            }
        }

        if (window.sessionStorage.getItem('RyTempCheckout') !== null) {
            let formData = JSON.parse(window.sessionStorage.getItem('RyTempCheckout'));
            for (const key in formData) {
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
            window.sessionStorage.removeItem('RyTempCheckout');
        }
    });

    $('.woocommerce-checkout').on('click', '.ry-choose-cvs', function () {
        let formData = $('form.checkout').serializeArray();
        formData = formData.filter(function (d) {
            if (d.name.substring(0, 1) == '_') {
                return false;
            }
            if (d.name.substring(0, 3) == 'RY_') {
                return false;
            }
            return ['terms'].indexOf(d.name) === -1;
        });
        window.sessionStorage.setItem('RyTempCheckout', JSON.stringify(formData));
        let html = '<form id="RyECPayChooseCvs" action="' + $(this).data('ry-url') + '" method="post">';
        for (const key in ecpayShippingInfo) {
            html += '<input type="hidden" name="' + key + '" value="' + ecpayShippingInfo[key] + '">';
        }
        if (window.innerWidth < 1024) {
            html += '<input type="hidden" name="Device" value="1">';
        }
        html += '</form>';
        document.body.innerHTML += html;
        document.getElementById('RyECPayChooseCvs').submit();
    });
});
