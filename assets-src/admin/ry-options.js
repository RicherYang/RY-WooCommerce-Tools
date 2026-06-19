import $ from 'jquery';

$(function () {
    $("[name$='_shipping']").on('change', function () {
        let selected = false;
        $("[name$='_shipping']").each(function () {
            selected = selected || $(this).is(':checked');
        });

        let $shippingTitle;
        $('h2').each(function () {
            if ($(this).text().indexOf(RyAdminOptionsParams.i18n.title) >= 0) {
                $shippingTitle = $(this);
            }
        });
        if ($shippingTitle) {
            if (selected) {
                $shippingTitle.show();
                $shippingTitle.next().show();
            } else {
                $shippingTitle.hide();
                $shippingTitle.next().hide();
            }
        }
    });
});
