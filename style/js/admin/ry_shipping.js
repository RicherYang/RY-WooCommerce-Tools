
jQuery(function($) {
	if( $('#_shipping_cvs_store_ID').length ) {
		$('a.load_customer_shipping').remove();
		$('a.billing-same-as-shipping').remove();
	}
});
