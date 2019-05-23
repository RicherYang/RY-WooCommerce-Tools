var ecpayShippingInfo;

jQuery(function($) {

$(document.body).on('updated_checkout', function(e, data) {
	if( typeof data.fragments.ecpay_shipping_info !== 'undefined' ) {
		ecpayShippingInfo = data.fragments.ecpay_shipping_info;
		$('.woocommerce-shipping-fields__field-wrapper p:not(.cvs-info)').hide();
		$('.woocommerce-shipping-fields__field-wrapper p#shipping_first_name_field').show();
		$('.woocommerce-shipping-fields__field-wrapper p#shipping_last_name_field').show();
		$('.woocommerce-shipping-fields__field-wrapper p.cvs-info').show();
		if( $('#ship-to-different-address-checkbox').prop('checked') === false ) {
			$('#ship-to-different-address-checkbox').click();
		}
		if( $('input#LogisticsSubType').length ) {
			if( $('input#LogisticsSubType').val() == ecpayShippingInfo.postData.LogisticsSubType ) {
				$('#CVSStoreName_field strong').text($('input#CVSStoreName').val());
				$('#CVSAddress_field strong').text($('input#CVSAddress').val());
				$('#CVSTelephone_field strong').text($('input#CVSTelephone').val());

				$('.choose_cvs .show_choose_cvs_name').show();
				$('.choose_cvs .choose_cvs_name').text($('input#CVSStoreName').val());
			} else {
				RYECPayRemoveSendCvs();
			}
		} else {
			RYECPayRemoveSendCvs();
		}
	} else {
		$('.woocommerce-shipping-fields__field-wrapper p.cvs-info').hide();
		$('.woocommerce-shipping-fields__field-wrapper p:not(.cvs-info)').show();
		RYECPayRemoveSendCvs();
	}

	if( window.sessionStorage.getItem('RYECPayTempCheckoutForm') !== null ) {
		var formData = JSON.parse(window.sessionStorage.getItem('RYECPayTempCheckoutForm'));
		var notSetData = ['payment_method', 'LogisticsSubType', 'CVSStoreID', 'CVSStoreName', 'CVSAddress', 'CVSTelephone'];
		for( var idx in formData ) {
			if( formData[idx].name.substr(0, 1) == '_' ) {
			} else if( notSetData.includes(formData[idx].name) ) {
			} else {
				var $item = jQuery('[name="' + formData[idx].name + '"]');
				switch( $item.prop('tagName') ) {
					case 'INPUT':
						if( $item.attr('type') == 'checkbox' ) {
							$item.prop('checked', $item.val() == formData[idx].value);
							break;
						}
					case 'TEXTAREA':
						$item.val(formData[idx].value);
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

function RYECPaySendCvsPost(){
	window.sessionStorage.setItem('RYECPayTempCheckoutForm', JSON.stringify(jQuery('form.checkout').serializeArray()));
	var html = '<form id="RYECPaySendCvsForm" action="' + ecpayShippingInfo.postUrl + '" method="post">';
	for( var idx in ecpayShippingInfo.postData ) {
		html += '<input type="hidden" name="' + idx + '" value="' + ecpayShippingInfo.postData[idx] + '">';
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
