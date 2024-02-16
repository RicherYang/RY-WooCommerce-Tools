<?php

final class RY_WT_WC_ECPay_Shipping extends RY_WT_WC_Model
{
    public static $support_methods = [
        'ry_ecpay_shipping_cvs_711' => 'RY_ECPay_Shipping_CVS_711',
        'ry_ecpay_shipping_cvs_family' => 'RY_ECPay_Shipping_CVS_Family',
        'ry_ecpay_shipping_cvs_hilife' => 'RY_ECPay_Shipping_CVS_Hilife',
        'ry_ecpay_shipping_cvs_ok' => 'RY_ECPay_Shipping_CVS_Ok',
        'ry_ecpay_shipping_home_post' => 'RY_ECPay_Shipping_Home_Post',
        'ry_ecpay_shipping_home_tcat' => 'RY_ECPay_Shipping_Home_Tcat'
    ];

    protected static $_instance = null;

    protected $js_data;
    protected $log_source = 'ry_ecpay_shipping';

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

        $this->log_enabled = 'yes' === RY_WT::get_option('ecpay_shipping_log', 'no');
        $this->testmode = 'yes' === RY_WT::get_option('ecpay_shipping_testmode', 'no');

        RY_WT_WC_Shipping::instance();
        RY_WT_WC_ECPay_Shipping_Response::instance();

        add_filter('woocommerce_shipping_methods', [$this, 'add_method']);

        add_filter('woocommerce_checkout_fields', [$this, 'add_cvs_info'], 9999);
        add_filter('woocommerce_update_order_review_fragments', [$this, 'checkout_choose_cvs_info']);
        add_action('woocommerce_checkout_create_order_shipping_item', [$this, 'remove_metadata']);
        add_action('woocommerce_checkout_create_order', [$this, 'save_order_cvs_info'], 20, 2);

        if ('yes' === RY_WT::get_option('ecpay_shipping_auto_get_no', 'yes')) {
            add_action('woocommerce_order_status_processing', [$this, 'get_code'], 10, 2);
        }

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/admin.php';
            RY_WT_WC_ECPay_Shipping_Admin::instance();
        } else {
            add_action('woocommerce_review_order_after_shipping', [$this, 'checkout_choose_cvs']);
            add_action('template_redirect', [$this, 'save_cvs_info']);
            add_filter('default_checkout_LogisticsSubType', [$this, 'get_cvs_info'], 10, 2);
            add_filter('default_checkout_CVSStoreID', [$this, 'get_cvs_info'], 10, 2);
            add_filter('default_checkout_CVSStoreName', [$this, 'get_cvs_info'], 10, 2);
            add_filter('default_checkout_CVSAddress', [$this, 'get_cvs_info'], 10, 2);
            add_filter('default_checkout_CVSTelephone', [$this, 'get_cvs_info'], 10, 2);
            add_filter('default_checkout_CVSOutSide', [$this, 'get_cvs_info'], 10, 2);

        }
    }

    public function add_method($shipping_methods)
    {
        $shipping_methods = array_merge($shipping_methods, self::$support_methods);
        if (RY_WT::get_option('ecpay_shipping_cvs_type') == 'B2C') {
            unset($shipping_methods['ry_ecpay_shipping_cvs_ok']);
        }

        return $shipping_methods;
    }

    public function add_cvs_info($fields)
    {
        if (!isset($fields['shipping']['shipping_phone'])) {
            $fields['shipping']['shipping_phone'] = [
                'label' => __('Phone', 'ry-woocommerce-tools'),
                'required' => true,
                'type' => 'tel',
                'validate' => ['phone'],
                'class' => ['form-row-wide'],
                'priority' => 100
            ];
        } else {
            $fields['shipping']['shipping_phone']['required'] = true;
            $fields['shipping']['shipping_phone']['type'] = 'tel';
        }

        $fields['shipping']['LogisticsSubType'] = [
            'required' => false,
            'type' => 'hidden'
        ];
        $fields['shipping']['CVSStoreID'] = [
            'required' => false,
            'type' => 'hidden'
        ];
        $fields['shipping']['CVSStoreName'] = [
            'label' => __('Store Name', 'ry-woocommerce-tools'),
            'required' => false,
            'type' => 'ry-hidden-text',
            'class' => ['form-row-wide', 'cvs-info'],
            'priority' => 110
        ];
        $fields['shipping']['CVSAddress'] = [
            'label' => __('Store Address', 'ry-woocommerce-tools'),
            'required' => false,
            'type' => 'ry-hidden-text',
            'class' => ['form-row-wide', 'cvs-info'],
            'priority' => 111
        ];
        $fields['shipping']['CVSTelephone'] = [
            'label' => __('Store Telephone', 'ry-woocommerce-tools'),
            'required' => false,
            'type' => 'ry-hidden-text',
            'class' => ['form-row-wide', 'cvs-info'],
            'priority' => 112
        ];

        if (is_checkout()) {
            $chosen_method = isset(WC()->session->chosen_shipping_methods) ? WC()->session->chosen_shipping_methods : [];
            $used_cvs = false;
            if (count($chosen_method)) {
                foreach ($chosen_method as $method) {
                    $method = strstr($method, ':', true);
                    if ($method && array_key_exists($method, self::$support_methods) && false !== strpos($method, 'cvs')) {
                        $used_cvs = true;
                        break;
                    }
                }
            }

            if ($used_cvs) {
                foreach ($fields['shipping'] as $key => $filed) {
                    if (isset($filed['class'])) {
                        if (!in_array('cvs-info', $filed['class'])) {
                            if (!in_array($key, ['shipping_first_name', 'shipping_last_name', 'shipping_country', 'shipping_phone'])) {
                                $fields['shipping'][$key]['class'][] = 'ry-hide';
                            }
                        }
                    } elseif (isset($filed['type'])) {
                        if ('hidden' !== $filed['type']) {
                            $fields['shipping'][$key]['class'] = ['ry-hide'];
                        }
                    }
                }
            } else {
                $fields['shipping']['CVSStoreName']['class'][] = 'ry-hide';
                $fields['shipping']['CVSAddress']['class'][] = 'ry-hide';
                $fields['shipping']['CVSTelephone']['class'][] = 'ry-hide';
            }
        }

        if (did_action('woocommerce_checkout_process')) {
            $used = false;
            $used_cvs = false;
            $shipping_method = isset($_POST['shipping_method']) ? wc_clean($_POST['shipping_method']) : [];
            foreach ($shipping_method as $method) {
                $method = strstr($method, ':', true);
                if ($method && array_key_exists($method, self::$support_methods)) {
                    $used = true;
                    if (false !== strpos($method, 'cvs')) {
                        $used_cvs = true;
                    }
                }
            }

            if ($used_cvs) {
                $fields['shipping']['shipping_country']['required'] = false;
                $fields['shipping']['shipping_address_1']['required'] = false;
                $fields['shipping']['shipping_address_2']['required'] = false;
                $fields['shipping']['shipping_city']['required'] = false;
                $fields['shipping']['shipping_state']['required'] = false;
                $fields['shipping']['shipping_postcode']['required'] = false;

                $fields['shipping']['shipping_phone']['required'] = true;
                $fields['shipping']['CVSStoreName']['required'] = true;
            } elseif ($used) {
                $fields['shipping']['shipping_phone']['required'] = true;
            } else {
                $fields['shipping']['shipping_phone']['required'] = false;
            }
        }

        return $fields;
    }

    public function checkout_choose_cvs_info($fragments)
    {
        if (!empty($this->js_data)) {
            $fragments['ecpay_shipping_info'] = $this->js_data;
        }

        return $fragments;
    }

    public function remove_metadata($item)
    {
        if (array_key_exists($item->get_method_id(), self::$support_methods)) {
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
        $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
        if (!is_array($shipping_list)) {
            $shipping_list = [];
        }
        if (0 === count($shipping_list)) {
            RY_WT_WC_ECPay_Shipping_Api::instance()->get_code($order_ID);
        }
    }

    public function checkout_choose_cvs()
    {
        $chosen_shipping = wc_get_chosen_shipping_method_ids();
        $chosen_shipping = array_intersect($chosen_shipping, array_keys(self::$support_methods));
        $this->js_data = [];

        if (isset($chosen_shipping[0])) {
            if (false === strpos($chosen_shipping[0], 'cvs')) {
                $this->js_data['ecpay_home'] = true;
            } else {
                wc_get_template('cart/cart-choose-cvs.php', [
                    'post_url' => RY_WT_WC_ECPay_Shipping_Api::instance()->get_map_post_url()
                ], '', RY_WT_PLUGIN_DIR . 'templates/');

                list($MerchantID, $HashKey, $HashIV, $cvs_type) = $this->get_api_info();
                $method_class = self::$support_methods[$chosen_shipping[0]];

                $subtype = $method_class::Shipping_Sub_Type;
                if('C2C' === $cvs_type) {
                    $subtype .= 'C2C';
                }
                if('B2C' === $cvs_type) {
                    if('UNIMART' === $method_class::Shipping_Sub_Type) {
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

                        if(isset($temp_list[3])) {
                            $subtype .= 'FREEZE';
                        }
                    }
                }
                $this->js_data['postData'] = [
                    'MerchantID' => $MerchantID,
                    'LogisticsType' => $method_class::Shipping_Type,
                    'LogisticsSubType' => $subtype,
                    'IsCollection' => 'Y',
                    'ServerReplyURL' => esc_url(WC()->api_request_url('ry_ecpay_map_callback'))
                ];
            }
        }

        wp_localize_script('ry-wt-shipping', 'ry_shipping_params', [
            'postUrl' => RY_WT_WC_ECPay_Shipping_Api::instance()->get_map_post_url()
        ]);

        wp_enqueue_script('ry-wt-shipping');
    }

    public function save_cvs_info()
    {
        if (isset($_POST['ry-ecpay-cvsmap-info'])) {
            $cvs_info = (array) json_decode(wp_unslash($_POST['ry-ecpay-cvsmap-info']), true);
            $cvs_info['shipping_methods'] = WC()->session->get('chosen_shipping_methods');
            WC()->session->set('ry-ecpay-cvs-info', $cvs_info);
        }
    }

    public function get_cvs_info($value, $input)
    {
        if(in_array($input, ['LogisticsSubType', 'CVSStoreID', 'CVSStoreName', 'CVSAddress', 'CVSTelephone', 'CVSOutSide'])) {
            $cvs_info = (array) WC()->session->get('ry-ecpay-cvs-info', []);
            if(isset($cvs_info['shipping_methods']) && $cvs_info['shipping_methods'] === WC()->session->get('chosen_shipping_methods')) {
                $value = $cvs_info[$input] ?? '';
                if ('' === $value) {
                    $value = null;
                }
            }
        }
        return $value;
    }

    public function get_api_info()
    {
        $cvs_type = RY_WT::get_option('ecpay_shipping_cvs_type');
        if ($this->testmode) {
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

    public function get_order_support_shipping($item)
    {
        foreach (self::$support_methods as $method => $method_class) {
            if (0 === strpos($item->get_method_id(), $method)) {
                return $method;
            }
        }

        return false;
    }
}
