<?php

final class RY_WT_Shipping
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
        add_filter('wc_order_statuses', [$this, 'add_order_statuses']);
        add_filter('woocommerce_reports_order_statuses', [$this, 'add_reports_order_statuses']);
        add_filter('woocommerce_order_is_paid_statuses', [$this, 'add_order_is_paid_statuses']);
        $this->register_order_statuses();

        add_filter('woocommerce_get_order_address', [$this, 'show_store_in_address'], 10, 3);
        add_filter('woocommerce_formatted_address_replacements', [$this, 'add_cvs_address_replacements'], 10, 2);

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/shipping-base.php';
        } else {
            wp_register_script('ry-wt-shipping', RY_WT_PLUGIN_URL . 'style/js/ry-shipping.js', ['jquery'], RY_WT_VERSION, true);
        }
    }

    public function add_order_statuses($order_statuses)
    {
        $order_statuses['wc-ry-at-cvs'] = _x('Wait pickup (cvs)', 'Order status', 'ry-woocommerce-tools');
        $order_statuses['wc-ry-out-cvs'] = _x('Overdue return (cvs)', 'Order status', 'ry-woocommerce-tools');
        if (defined('RY_WTP_VERSION')) {
            $order_statuses['wc-ry-transporting'] = _x('Transporting', 'Order status', 'ry-woocommerce-tools');
        }

        return $order_statuses;
    }

    public function add_reports_order_statuses($order_statuses)
    {
        $order_statuses[] = 'ry-at-cvs';
        $order_statuses[] = 'ry-out-cvs';
        $order_statuses[] = 'ry-transporting';

        return $order_statuses;
    }

    public function add_order_is_paid_statuses($statuses)
    {
        $statuses[] = 'ry-at-cvs';
        $statuses[] = 'ry-out-cvs';
        $statuses[] = 'ry-transporting';

        return $statuses;
    }

    public function register_order_statuses()
    {
        register_post_status('wc-ry-at-cvs', [
            'label' => _x('Wait pickup (cvs)', 'Order status', 'ry-woocommerce-tools'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            /* translators: %s: number of orders */
            'label_count' => _n_noop('Wait pickup (cvs) <span class="count">(%s)</span>', 'Wait pickup (cvs) <span class="count">(%s)</span>', 'ry-woocommerce-tools'),
        ]);

        register_post_status('wc-ry-out-cvs', [
            'label' => _x('Overdue return (cvs)', 'Order status', 'ry-woocommerce-tools'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            /* translators: %s: number of orders */
            'label_count' => _n_noop('Overdue return (cvs) <span class="count">(%s)</span>', 'Overdue return (cvs) <span class="count">(%s)</span>', 'ry-woocommerce-tools'),
        ]);

        register_post_status('wc-ry-transporting', [
            'label' => _x('Transporting', 'Order status', 'ry-woocommerce-tools'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            /* translators: %s: number of orders */
            'label_count' => _n_noop('Transporting <span class="count">(%s)</span>', 'Transporting <span class="count">(%s)</span>', 'ry-woocommerce-tools'),
        ]);
    }

    public function show_store_in_address($address, $type, $order)
    {
        if ($type == 'shipping') {
            if (!empty($order->get_meta('_shipping_cvs_store_ID'))) {
                $items_shipping = $order->get_items('shipping');
                $items_shipping = array_shift($items_shipping);
                if ($items_shipping) {
                    $items_shipping = $items_shipping->get_method_id();

                    if ('yes' === RY_WT::get_option('enabled_ecpay_shipping', 'no')) {
                        if (array_key_exists($items_shipping, RY_ECPay_Shipping::$support_methods)) {
                            return $this->set_store_address($address, $order, $items_shipping);
                        }
                    }

                    if ('yes' === RY_WT::get_option('enabled_newebpay_shipping', 'no')) {
                        if (array_key_exists($items_shipping, RY_NewebPay_Shipping::$support_methods)) {
                            return $this->set_store_address($address, $order, $items_shipping);
                        }
                    }

                    if ('yes' === RY_WT::get_option('enabled_smilepay_shipping', 'no')) {
                        if (array_key_exists($items_shipping, RY_SmilePay_Shipping::$support_methods)) {
                            return $this->set_store_address($address, $order, $items_shipping);
                        }
                    }
                }
            }
        }

        return $address;
    }

    protected function set_store_address($address, $order, $items_shipping)
    {
        $shipping_methods = WC()->shipping->get_shipping_methods();
        if (isset($shipping_methods[$items_shipping])) {
            $address['shipping_type'] = $shipping_methods[$items_shipping]->get_method_title();
        } else {
            $address['shipping_type'] = (string) $items_shipping;
        }

        $address['cvs_store_ID'] = $order->get_meta('_shipping_cvs_store_ID');
        $address['cvs_store_name'] = $order->get_meta('_shipping_cvs_store_name');
        $address['cvs_address'] = $order->get_meta('_shipping_cvs_store_address');
        $address['cvs_telephone'] = $order->get_meta('_shipping_cvs_store_telephone');
        if (version_compare(WC_VERSION, '5.6.0', '<')) {
            $address['phone'] = $order->get_meta('_shipping_phone');
        } else {
            $address['phone'] = $order->get_shipping_phone();
        }
        $address['country'] = 'CVS';

        return $address;
    }

    public function add_cvs_address_replacements($replacements, $args)
    {
        if (isset($args['cvs_store_ID'])) {
            if (isset($args['shipping_type'])) {
                $replacements['{shipping_type}'] = $args['shipping_type'];
            }
            $replacements['{cvs_store_ID}'] = $args['cvs_store_ID'];
            $replacements['{cvs_store_name}'] = $args['cvs_store_name'];
            $replacements['{cvs_store_address}'] = $args['cvs_address'];
            $replacements['{cvs_store_telephone}'] = $args['cvs_telephone'];
            $replacements['{phone}'] = $args['phone'];
        }

        return $replacements;
    }
}

RY_WT_Shipping::instance();
