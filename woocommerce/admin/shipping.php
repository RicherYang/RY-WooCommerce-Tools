<?php

final class RY_WT_WC_Admin_Shipping
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_Admin_Shipping
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'add_scripts']);
        add_action('woocommerce_update_order', [$this, 'save_order_update']);

        add_filter('woocommerce_admin_shipping_fields', [$this, 'set_cvs_shipping_fields'], 99);
        add_filter('woocommerce_shipping_address_map_url_parts', [$this, 'fix_cvs_map_address']);
        add_filter('woocommerce_admin_order_actions', [$this, 'add_admin_order_actions'], 10, 2);

        add_filter('woocommerce_order_actions', [$this, 'add_order_actions']);
        add_action('woocommerce_order_action_send_at_cvs_email', [$this, 'send_at_cvs_email']);

        add_action('wp_ajax_RY_delete_shipping_info', [$this, 'delete_shipping_info']);
    }

    public function add_scripts()
    {
        $asset_info = include RY_WT_PLUGIN_DIR . 'assets/admin/ry-shipping.asset.php';

        wp_enqueue_script('ry-admin-shipping', RY_WT_PLUGIN_URL . 'assets/admin/ry-shipping.js', $asset_info['dependencies'], $asset_info['version'], true);
        wp_localize_script('ry-admin-shipping', 'ryAdminShippingParams', [
            'ajax_url' => admin_url('admin-ajax.php'),
            '_nonce' => [
                'get' => wp_create_nonce('get-shipping-info'),
                'delete' => wp_create_nonce('delete-shipping-info'),
                'smilepay' => wp_create_nonce('smilepay-shipping-no'),
            ],
            'i18n' => [
                'delete_shipping_info' => __('It only delete the information at website.', 'ry-woocommerce-tools'),
            ],
        ]);

        wp_enqueue_style('ry-admin-shipping', RY_WT_PLUGIN_URL . 'assets/admin/ry-shipping.css', [], $asset_info['version']);
    }

    public function save_order_update($order_ID)
    {

        if (isset($_POST['_shipping_cvs_store_ID'])) {
            $order = wc_get_order($order_ID);
            $shipping_method = $this->get_ry_shipping_method($order);
            if ($shipping_method && false !== strpos($shipping_method, '_cvs')) {
                remove_action('woocommerce_update_order', [$this, 'save_order_update']);

                $order->update_meta_data('_shipping_cvs_store_ID', wc_clean(wp_unslash($_POST['_shipping_cvs_store_ID'] ?? '')));
                $order->update_meta_data('_shipping_cvs_store_name', wc_clean(wp_unslash($_POST['_shipping_cvs_store_name'] ?? '')));
                $order->update_meta_data('_shipping_cvs_store_address', wc_clean(wp_unslash($_POST['_shipping_cvs_store_address'] ?? '')));
                $order->update_meta_data('_shipping_cvs_store_telephone', wc_clean(wp_unslash($_POST['_shipping_cvs_store_telephone'] ?? '')));
                $order->set_shipping_address_1(wc_clean(wp_unslash($_POST['_shipping_cvs_store_address'] ?? '')));
                $order->save();

                add_action('woocommerce_update_order', [$this, 'save_order_update']);
            }
        }
    }

    public function set_cvs_shipping_fields($shipping_fields)
    {
        global $theorder;

        $shipping_method = $this->get_ry_shipping_method($theorder);
        if ($shipping_method) {
            if (false !== strpos($shipping_method, 'cvs')) {
                $shipping_fields['cvs_store_ID'] = [
                    'label' => __('Store ID', 'ry-woocommerce-tools'),
                    'show' => false,
                ];
                $shipping_fields['cvs_store_name'] = [
                    'label' => __('Store Name', 'ry-woocommerce-tools'),
                    'show' => false,
                ];
                $shipping_fields['cvs_store_address'] = [
                    'label' => __('Store Address', 'ry-woocommerce-tools'),
                    'show' => false,
                ];
                $shipping_fields['cvs_store_telephone'] = [
                    'label' => __('Store Telephone', 'ry-woocommerce-tools'),
                    'show' => false,
                ];
            }
            $shipping_fields['phone'] = [
                'label' => __('Phone', 'ry-woocommerce-tools'),
            ];
        }
        return $shipping_fields;
    }

    public function fix_cvs_map_address($address)
    {
        if (isset($address['cvs_address'])) {
            $address = [
                $address['cvs_address'],
            ];
        }
        return $address;
    }

    public function add_admin_order_actions($actions, $object)
    {
        if ($object->has_status(['ry-at-cvs'])) {
            $actions['complete'] = [
                'url' => wp_nonce_url(admin_url('admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $object->get_id()), 'woocommerce-mark-order-status'),
                'name' => __('Complete', 'woocommerce'),
                'action' => 'complete',
            ];
        }

        return $actions;
    }

    public function add_order_actions($order_actions)
    {
        global $theorder, $post;

        if (!is_object($theorder)) {
            $theorder = wc_get_order($post->ID);
        }

        $shipping_method = $this->get_ry_shipping_method($theorder);
        if ($shipping_method) {
            if ($theorder->has_status(['ry-at-cvs'])) {
                $order_actions['send_at_cvs_email'] = __('Resend at cvs notification', 'ry-woocommerce-tools');
            }
        }

        return $order_actions;
    }

    public function delete_shipping_info()
    {
        check_ajax_referer('delete-shipping-info');

        $order_ID = (int) wp_unslash($_POST['orderid'] ?? 0);
        $logistics_ID = wp_unslash($_POST['id'] ?? '');

        $order = wc_get_order($order_ID);
        if (!empty($order)) {
            foreach(['_ecpay_shipping_info', '_newebpay_shipping_info', '_smilepay_shipping_info'] as $meta_key) {
                $shipping_list = $order->get_meta($meta_key, true);
                if (is_array($shipping_list)) {
                    foreach ($shipping_list as $idx => $info) {
                        if ($info['ID'] == $logistics_ID) {
                            unset($shipping_list[$idx]);
                            $order->update_meta_data($meta_key, $shipping_list);
                            $order->save();
                        }
                    }
                }
            }
        }

        wp_die();
    }

    public function send_at_cvs_email($order)
    {
        do_action('ry_shipping_customer_cvs_store', $order->get_id(), $order);
    }

    protected function get_ry_shipping_method($order)
    {
        if (!is_a($order, 'WC_Order')) {
            return false;
        }

        $shipping_items = $order->get_items('shipping');
        if (!is_array($shipping_items)) {
            return false;
        }
        $shipping_item = array_shift($shipping_items);
        if (empty($shipping_item)) {
            return false;
        }

        $shipping_method = false;
        if (false === $shipping_method && class_exists('RY_WT_WC_ECPay_Shipping')) {
            $shipping_method = RY_WT_WC_ECPay_Shipping::instance()->get_order_support_shipping($shipping_item);
        }
        if (false === $shipping_method && class_exists('RY_WT_WC_NewebPay_Shipping')) {
            $shipping_method = RY_WT_WC_NewebPay_Shipping::instance()->get_order_support_shipping($shipping_item);
        }
        if (false === $shipping_method && class_exists('RY_WT_WC_SmilePay_Shipping')) {
            $shipping_method = RY_WT_WC_SmilePay_Shipping::instance()->get_order_support_shipping($shipping_item);
        }

        return $shipping_method;
    }

    protected function get_all_cvs_methods()
    {
        $cvs_methods = [];
        if (class_exists('RY_WT_WC_ECPay_Shipping')) {
            $cvs_methods = array_merge($cvs_methods, array_keys(RY_WT_WC_ECPay_Shipping::$support_methods));
        }
        if (class_exists('RY_WT_WC_NewebPay_Shipping')) {
            $cvs_methods = array_merge($cvs_methods, array_keys(RY_WT_WC_NewebPay_Shipping::$support_methods));
        }
        if (class_exists('RY_WT_WC_SmilePay_Shipping')) {
            $cvs_methods = array_merge($cvs_methods, array_keys(RY_WT_WC_SmilePay_Shipping::$support_methods));
        }
        $cvs_methods = array_filter($cvs_methods, function ($method) {
            return false !== strpos($method, '_cvs');
        });
        return $cvs_methods;
    }
}
