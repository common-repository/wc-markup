window.onload = function () {
    (function (jQuery) {

        jQuery('#product_catchecklist li input').on('click', function (i, s) {
            if (jQuery(this).is(':checked')) {

                let id = jQuery(this).val();
                let endpoint = markupforwc_ajax_object.site_url + '/wp-json/mc-for-wc/v1/markup-price/';
                jQuery.ajax({
                    url: endpoint + id,
                    contentType: "application/json",
                    dataType: 'json',
                    success: function (result) {
                        if (!result.category_type && !result.category_price) {
                            return false;
                        } else {
                            jQuery('#_markup_pricing_type').val(result.category_type).trigger("change");
                            jQuery('#_price_for_markup').val(result.category_price);
                        }
                    }
                })

            }
        });

        jQuery('#woocommerce-product-data').on('woocommerce_variations_loaded', function (event) {

            jQuery('.woocommerce_variation').each(function (index, o) {

                jQuery('#product_catchecklist li input').on('click', function (i, s) {
                    if (jQuery(this).is(':checked')) {

                        let id = jQuery(this).val();
                        let endpoint = '/wp-json/mc-for-wc/v1/markup-price/';
                        jQuery.ajax({
                            url: endpoint + id,
                            contentType: "application/json",
                            dataType: 'json',
                            success: function (result) {
                                if (!result.category_type && !result.category_price) {
                                    return false;
                                } else {
                                    let markup_type_field = jQuery('#markup_price_type_' + index);
                                    let markup_price_field = jQuery('#markup_price_' + index );

                                    console.log(jQuery(markup_price_field));

                                    markup_type_field.each(function (i, o) {
                                        console.log('here');
                                        console.log(jQuery(this).val);
                                        jQuery(this).val(result.category_type).trigger("change");
                                    });
                                    markup_price_field.each(function (i, o) {
                                        jQuery(this).val(result.category_price);
                                    });
                                }
                            }
                        })

                    }
                });


            });

        });

    })(jQuery);
};
