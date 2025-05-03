<?php

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

final class RY_WT_WC_ECPay_Shipping extends RY_WT_Shipping_Model
{
    public static $support_methods = [
        'ry_ecpay_shipping_cvs_711' => 'RY_ECPay_Shipping_CVS_711',
        'ry_ecpay_shipping_cvs_family' => 'RY_ECPay_Shipping_CVS_Family',
        'ry_ecpay_shipping_cvs_hilife' => 'RY_ECPay_Shipping_CVS_Hilife',
        'ry_ecpay_shipping_cvs_ok' => 'RY_ECPay_Shipping_CVS_Ok',
        'ry_ecpay_shipping_home_post' => 'RY_ECPay_Shipping_Home_Post',
        'ry_ecpay_shipping_home_tcat' => 'RY_ECPay_Shipping_Home_Tcat',
    ];

    protected static $_instance = null;

    protected $js_data;

    protected $model_type = 'ecpay_shipping';

    public static function instance(): RY_WT_WC_ECPay_Shipping
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-api-ecpay.php';

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/shipping-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/shipping-response.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/shipping-method.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/shipping-cvs-711.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/shipping-cvs-family.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/shipping-cvs-hilife.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/shipping-cvs-ok.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/shipping-home-post.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/shipping-home-tcat.php';

        RY_WT_WC_Shipping::instance();
        RY_WT_WC_ECPay_Shipping_Response::instance();

        add_filter('woocommerce_shipping_methods', [$this, 'add_method']);

        add_filter('woocommerce_checkout_fields', [$this, 'add_cvs_info'], 9999);
        add_filter('woocommerce_update_order_review_fragments', [$this, 'checkout_choose_cvs_info']);
        add_action('woocommerce_checkout_create_order_shipping_item', [$this, 'remove_metadata'], 10, 4);

        if ('yes' === RY_WT::get_option('ecpay_shipping_auto_get_no', 'yes')) {
            add_action('woocommerce_order_status_processing', [$this, 'get_code'], 10, 2);
        }

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/admin.php';
            RY_WT_WC_ECPay_Shipping_Admin::instance();
        } else {
            add_action('woocommerce_review_order_after_shipping', [$this, 'checkout_choose_cvs']);
            add_action('template_redirect', [$this, 'save_cvs_info']);
            add_action('woocommerce_after_checkout_validation', [$this, 'check_choose_cvs'], 10, 2);
            add_action('woocommerce_store_api_checkout_update_customer_from_request', [$this, 'check_choose_cvs'], 10, 2);

            add_filter('default_checkout_RY_LogisticsSubType', [$this, 'get_cvs_info'], 10, 2);
            add_filter('default_checkout_RY_CVSStoreID', [$this, 'get_cvs_info'], 10, 2);
            add_filter('default_checkout_RY_CVSStoreName', [$this, 'get_cvs_info'], 10, 2);
            add_filter('default_checkout_RY_CVSAddress', [$this, 'get_cvs_info'], 10, 2);
            add_filter('default_checkout_RY_CVSTelephone', [$this, 'get_cvs_info'], 10, 2);
            add_filter('default_checkout_RY_CVSOutSide', [$this, 'get_cvs_info'], 10, 2);
        }
    }

    public function add_method($shipping_methods)
    {
        $shipping_methods = array_merge($shipping_methods, self::$support_methods);
        if ('B2C' === RY_WT::get_option('ecpay_shipping_cvs_type')) {
            unset($shipping_methods['ry_ecpay_shipping_cvs_ok']);
        }

        return $shipping_methods;
    }

    public function add_cvs_info($fields)
    {
        $fields = parent::add_cvs_info($fields);

        $fields['rycvs']['RY_LogisticsSubType'] = [
            'required' => false,
            'type' => 'hidden',
        ];
        $fields['rycvs']['RY_CVSStoreID'] = [
            'required' => false,
            'type' => 'hidden',
        ];
        $fields['rycvs']['RY_CVSStoreName'] = [
            'required' => false,
            'type' => 'hidden',
        ];
        $fields['rycvs']['RY_CVSAddress'] = [
            'required' => false,
            'type' => 'hidden',
        ];
        $fields['rycvs']['RY_CVSTelephone'] = [
            'required' => false,
            'type' => 'hidden',
        ];
        $fields['rycvs']['RY_CVSOutSide'] = [
            'required' => false,
            'type' => 'hidden',
        ];

        return $fields;
    }

    public function checkout_choose_cvs_info($fragments)
    {
        if (!empty($this->js_data)) {
            $fragments['ry_shipping_info'] = $this->js_data;
        }

        return $fragments;
    }

    public function remove_metadata($item, $package_key, $package, $order)
    {
        if (isset(self::$support_methods[$item->get_method_id()])) {
            if ('CVS' === $item->get_meta('LogisticsType')) {
                $this->save_order_cvs_info($order, $item->get_meta('LogisticsInfo'));
            }
            $item->delete_meta_data('LogisticsType');
            $item->delete_meta_data('LogisticsSubType');
            $item->delete_meta_data('LogisticsInfo');
        }
    }

    public function save_order_cvs_info($order, $data)
    {
        if (!empty($data['CVSStoreID'])) {
            $order->set_shipping_company('');
            $order->set_shipping_address_2('');
            $order->set_shipping_city('');
            $order->set_shipping_state('');
            $order->set_shipping_postcode('');

            $order->update_meta_data('_shipping_cvs_store_ID', $data['CVSStoreID']);
            $order->update_meta_data('_shipping_cvs_store_name', $data['CVSStoreName']);
            $order->update_meta_data('_shipping_cvs_store_address', $data['CVSAddress']);
            $order->update_meta_data('_shipping_cvs_store_telephone', $data['CVSTelephone']);
            $order->set_shipping_address_1($data['CVSAddress']);
        }
    }

    public function get_code($order_ID, $order)
    {
        foreach ($order->get_items('shipping') as $shipping_item) {
            $shipping_method = $this->get_order_support_shipping($shipping_item);
            if ($shipping_method) {
                $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
                if (!is_array($shipping_list)) {
                    $shipping_list = [];
                }
                if (0 === count($shipping_list)) {
                    RY_WT_WC_ECPay_Shipping_Api::instance()->get_code($order_ID);
                }
                break;
            }
        }
    }

    public function checkout_choose_cvs()
    {
        $chosen_shipping = wc_get_chosen_shipping_method_ids();
        $chosen_shipping = array_intersect($chosen_shipping, array_keys(self::$support_methods));
        $this->js_data = [];

        if (count($chosen_shipping)) {
            $chosen_shipping = array_shift($chosen_shipping);
            if (str_contains($chosen_shipping, '_cvs')) {
                $this->js_data['ecpay_cvs'] = true;

                wc_get_template('cart/cart-choose-cvs.php', [
                    'post_url' => RY_WT_WC_ECPay_Shipping_Api::instance()->get_map_post_url(),
                ], '', RY_WT_PLUGIN_DIR . 'templates/');

                list($MerchantID, $HashKey, $HashIV, $cvs_type) = $this->get_api_info();
                $method_class = self::$support_methods[$chosen_shipping];

                $subtype = $method_class::Shipping_Sub_Type;
                if ('C2C' === $cvs_type) {
                    $subtype .= 'C2C';
                }
                if ('B2C' === $cvs_type) {
                    if ('UNIMART' === $method_class::Shipping_Sub_Type) {
                        $temp_list = [];
                        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                            $temp = $cart_item['data']->get_meta('_ry_shipping_temp', true);
                            if (empty($temp) && 'variation' === $cart_item['data']->get_type()) {
                                $parent_product = wc_get_product($cart_item['data']->get_parent_id());
                                $temp = $parent_product->get_meta('_ry_shipping_temp', true);
                            }
                            $temp = in_array($temp, $method_class::get_support_temp()) ? $temp : '1';
                            $temp_list[$temp] = true;
                        }

                        if (isset($temp_list[3])) {
                            $subtype .= 'FREEZE';
                        }
                    }
                }
                $this->js_data['postData'] = [
                    'MerchantID' => $MerchantID,
                    'LogisticsType' => $method_class::Shipping_Type,
                    'LogisticsSubType' => $subtype,
                    'IsCollection' => 'Y',
                    'ServerReplyURL' => esc_url(add_query_arg([
                        'ry-ecpay-map-redirect' => 'ry-ecpay-map-redirect',
                        'lang' => get_locale(),
                    ], WC()->api_request_url('ry_ecpay_map_callback'))),
                ];
            } else {
                $this->js_data['ecpay_home'] = true;
            }
        }
    }

    public function save_cvs_info()
    {
        if (isset($_POST['ry-ecpay-cvsmap-info'])) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $cvs_info = (array) json_decode(base64_decode(wp_unslash($_POST['ry-ecpay-cvsmap-info']), true), true);  // phpcs:ignore WordPress.Security.NonceVerification.Missing , WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            if (is_array($cvs_info) && 6 === count($cvs_info)) {
                $cvs_info['shipping_methods'] = WC()->session->get('chosen_shipping_methods', []);
                foreach ($cvs_info['shipping_methods'] as $package_key => $t) {
                    WC()->session->set('shipping_for_package_' . $package_key, '');
                }
                WC()->session->set('ry-ecpay-cvs-info', $cvs_info);
            } else {
                WC()->session->set('ry-ecpay-cvs-info', []);
            }
        }
    }

    public function check_choose_cvs($data, $errors)
    {
        if (WC()->cart && WC()->cart->needs_shipping()) {
            $cvs_method = false;
            $chosen_shipping = wc_get_chosen_shipping_method_ids();
            $chosen_shipping = array_intersect($chosen_shipping, array_keys(self::$support_methods));
            if (count($chosen_shipping)) {
                $chosen_shipping = array_shift($chosen_shipping);
                if (str_contains($chosen_shipping, '_cvs')) {
                    $cvs_method = true;
                }
            }

            if ($cvs_method) {
                $csv_info = WC()->session->get('ry-ecpay-cvs-info', []);

                if (!isset($csv_info['LogisticsSubType']) || !str_starts_with($csv_info['LogisticsSubType'], $chosen_shipping::Shipping_Sub_Type)) {
                    // 傳統結帳
                    if (is_array($data)) {
                        $errors->add('shipping', __('No convenience store has been chosen.', 'ry-woocommerce-tools'));
                    } else {
                        throw new RouteException('woocommerce_rest_checkout_missing_required_field', __('No convenience store has been chosen.', 'ry-woocommerce-tools'), 400); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    }
                }
            }
        }
    }

    public function get_cvs_info($value, $input)
    {
        $cvs_info = (array) WC()->session->get('ry-ecpay-cvs-info', []);
        if (isset($cvs_info['shipping_methods']) && $cvs_info['shipping_methods'] === WC()->session->get('chosen_shipping_methods')) {
            return $cvs_info[substr($input, 3)] ?? '';
        }

        return '';
    }

    public function get_api_info()
    {
        $cvs_type = RY_WT::get_option('ecpay_shipping_cvs_type');
        if ($this->is_testmode()) {
            if ('C2C' === $cvs_type) {
                $MerchantID = '2000933';
                $HashKey = 'XBERn1YOvpM9nfZc';
                $HashIV = 'h1ONHk4P4yqbl5LK';
            } else {
                $MerchantID = '2000132';
                $HashKey = '5294y06JbISpM5x9';
                $HashIV = 'v77hoKGq4kWxNNIS';
            }
        } else {
            $MerchantID = RY_WT::get_option('ecpay_shipping_MerchantID');
            $HashKey = RY_WT::get_option('ecpay_shipping_HashKey');
            $HashIV = RY_WT::get_option('ecpay_shipping_HashIV');
        }

        return [$MerchantID, $HashKey, $HashIV, $cvs_type];
    }

    public function get_order_support_shipping($shipping_item)
    {
        $method_ID = $shipping_item->get_method_id();
        if (isset(self::$support_methods[$method_ID])) {
            return $method_ID;
        }

        return false;
    }
}
