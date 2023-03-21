<?php

final class RY_WT_Admin_Shipping
{
    protected static $_instance = null;

    public static function instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init()
    {
        add_action('admin_enqueue_scripts', [$this, 'add_scripts']);
        add_action('woocommerce_update_order', [$this, 'save_order_update']);
        add_action('woocommerce_update_options_shipping_options', [$this, 'check_ship_destination']);
        add_action('woocommerce_shipping_zone_method_status_toggled', [$this, 'check_can_enable'], 10, 4);

        add_filter('woocommerce_admin_shipping_fields', [$this, 'set_cvs_shipping_fields'], 99);
        add_filter('woocommerce_shipping_address_map_url_parts', [$this, 'fix_cvs_map_address']);
        add_filter('woocommerce_admin_order_actions', [$this, 'add_admin_order_actions'], 10, 2);
    }

    public function add_scripts()
    {
        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if (in_array($screen_id, ['shop_order', 'edit-shop_order'])) {
            wp_enqueue_style('ry-wt-shipping-admin', RY_WT_PLUGIN_URL . 'style/admin/ry-shipping.css', [], RY_WT_VERSION);
            wp_enqueue_script('ry-wt-shipping-admin', RY_WT_PLUGIN_URL . 'style/js/admin/ry-shipping.js', ['jquery'], RY_WT_VERSION);
        }
    }

    public function save_order_update($order_id)
    {
        $order = wc_get_order($order_id);
        $shipping_method = $this->get_ry_shipping_method($order);
        if ($shipping_method) {
            if (version_compare(WC_VERSION, '5.6.0', '<')) {
                if (isset($_POST['_shipping_phone'])) {
                    $order->update_meta_data('_shipping_phone', wc_clean(wp_unslash($_POST['_shipping_phone'])));
                    $order->save_meta_data();
                }
            }

            if (strpos($shipping_method, '_cvs') !== false) {
                if (isset($_POST['_shipping_cvs_store_ID'])) {
                    $order->update_meta_data('_shipping_cvs_store_ID', wc_clean(wp_unslash($_POST['_shipping_cvs_store_ID'])));
                    $order->update_meta_data('_shipping_cvs_store_name', wc_clean(wp_unslash($_POST['_shipping_cvs_store_name'])));
                    $order->update_meta_data('_shipping_cvs_store_address', wc_clean(wp_unslash($_POST['_shipping_cvs_store_address'])));
                    $order->update_meta_data('_shipping_cvs_store_telephone', wc_clean(wp_unslash($_POST['_shipping_cvs_store_telephone'])));
                    $order->save_meta_data();

                    remove_action('woocommerce_update_order', [$this, 'save_order_update']);
                    $order->set_shipping_address_1(wc_clean(wp_unslash($_POST['_shipping_cvs_store_address'])));
                    $order->save();
                    add_action('woocommerce_update_order', [$this, 'save_order_update']);
                }
            }
        }
    }

    public function check_ship_destination()
    {
        global $wpdb;

        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            $disabled = false;
            $cvs_methods = $this->get_all_cvs_methods();
            $zones = WC_Shipping_Zones::get_zones();
            foreach ($zones as $zone_obj) {
                foreach ($zone_obj['shipping_methods'] as $shipping_method) {
                    if (!$shipping_method->is_enabled()) {
                        continue;
                    }

                    if (!in_array($shipping_method->id, $cvs_methods)) {
                        continue;
                    }

                    $wpdb->update($wpdb->prefix . 'woocommerce_shipping_zone_methods', [
                        'is_enabled' => 0
                    ], [
                        'instance_id' => $shipping_method->instance_id
                    ]);
                    $disabled = true;
                }
            }

            if ($disabled) {
                WC_Admin_Settings::add_error(__('All cvs shipping methods set to disable.', 'ry-woocommerce-tools'));
            }
        }
    }

    public function check_can_enable($instance_id, $method_id, $zone_id, $is_enabled)
    {
        global $wpdb;

        if ($is_enabled != 1) {
            return;
        }

        $cvs_methods = $this->get_all_cvs_methods();
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            if (in_array($method_id, $cvs_methods)) {
                $wpdb->update($wpdb->prefix . 'woocommerce_shipping_zone_methods', [
                    'is_enabled' => 0
                ], [
                    'instance_id' => absint($instance_id)
                ]);
            }
        }
    }

    public function set_cvs_shipping_fields($shipping_fields)
    {
        global $theorder;

        $shipping_method = $this->get_ry_shipping_method($theorder);
        if ($shipping_method) {
            if (strpos($shipping_method, 'cvs') !== false) {
                $shipping_fields['cvs_store_ID'] = [
                    'label' => __('Store ID', 'ry-woocommerce-tools'),
                    'show' => false
                ];
                $shipping_fields['cvs_store_name'] = [
                    'label' => __('Store Name', 'ry-woocommerce-tools'),
                    'show' => false
                ];
                $shipping_fields['cvs_store_address'] = [
                    'label' => __('Store Address', 'ry-woocommerce-tools'),
                    'show' => false
                ];
                $shipping_fields['cvs_store_telephone'] = [
                    'label' => __('Store Telephone', 'ry-woocommerce-tools'),
                    'show' => false
                ];
            }
            $shipping_fields['phone'] = [
                'label' => __('Phone', 'woocommerce')
            ];
        } elseif ('yes' === RY_WT::get_option('keep_shipping_phone', 'no')) {
            $shipping_fields['phone'] = [
                'label' => __('Phone', 'ry-woocommerce-tools')
            ];
        }
        return $shipping_fields;
    }

    public function fix_cvs_map_address($address)
    {
        if (isset($address['cvs_address'])) {
            $address = [
                $address['cvs_address']
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
        if ($shipping_method === false && class_exists('RY_ECPay_Shipping')) {
            $shipping_method = RY_ECPay_Shipping::get_order_support_shipping($shipping_item);
        }
        if ($shipping_method === false && class_exists('RY_NewebPay_Shipping')) {
            $shipping_method = RY_NewebPay_Shipping::get_order_support_shipping($shipping_item);
        }
        if ($shipping_method === false && class_exists('RY_SmilePay_Shipping')) {
            $shipping_method = RY_SmilePay_Shipping::get_order_support_shipping($shipping_item);
        }

        return $shipping_method;
    }

    protected function get_all_cvs_methods()
    {
        $cvs_methods = [];
        if (class_exists('RY_ECPay_Shipping')) {
            $cvs_methods = array_merge($cvs_methods, array_keys(RY_ECPay_Shipping::$support_methods));
        }
        if (class_exists('RY_NewebPay_Shipping')) {
            $cvs_methods = array_merge($cvs_methods, array_keys(RY_NewebPay_Shipping::$support_methods));
        }
        if (class_exists('RY_SmilePay_Shipping')) {
            $cvs_methods = array_merge($cvs_methods, array_keys(RY_SmilePay_Shipping::$support_methods));
        }
        $cvs_methods = array_filter($cvs_methods, function ($method) {
            return strpos($method, '_cvs') !== false;
        });
        return $cvs_methods;
    }
}

RY_WT_Admin_Shipping::instance();
