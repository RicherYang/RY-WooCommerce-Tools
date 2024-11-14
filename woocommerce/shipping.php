<?php

final class RY_WT_WC_Shipping
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_Shipping
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_filter('wc_order_statuses', [$this, 'add_order_statuses']);
        add_filter('woocommerce_reports_order_statuses', [$this, 'add_reports_order_statuses']);
        add_filter('woocommerce_order_is_paid_statuses', [$this, 'add_order_is_paid_statuses']);
        $this->register_order_statuses();

        add_filter('woocommerce_get_order_address', [$this, 'show_store_in_address'], 10, 3);
        add_filter('woocommerce_formatted_address_replacements', [$this, 'add_cvs_address_replacements'], 10, 2);

        add_filter('woocommerce_email_classes', [$this, 'add_email_class']);
        add_filter('woocommerce_email_actions', [$this, 'add_email_action']);
        add_action('woocommerce_order_status_ry-at-cvs', [$this, 'send_at_cvs_email'], 10, 2);

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/shipping.php';
            RY_WT_WC_Admin_Shipping::instance();
        } else {
            add_action('woocommerce_review_order_after_shipping', [$this, 'set_script'], 9);
        }
    }

    public function add_order_statuses($order_statuses)
    {
        $order_statuses['wc-ry-at-cvs'] = _x('Wait pickup (cvs)', 'Order status', 'ry-woocommerce-tools');
        $order_statuses['wc-ry-out-cvs'] = _x('Overdue return (cvs)', 'Order status', 'ry-woocommerce-tools');
        $order_statuses['wc-ry-transporting'] = _x('Transporting', 'Order status', 'ry-woocommerce-tools');

        return $order_statuses;
    }

    public function add_reports_order_statuses($order_statuses)
    {
        if (is_array($order_statuses)) {
            $order_statuses[] = 'ry-at-cvs';
            $order_statuses[] = 'ry-out-cvs';
            $order_statuses[] = 'ry-transporting';
        }

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
        if ('shipping' === $type) {
            $items_shipping = $order->get_items('shipping');
            if (count($items_shipping)) {
                $method_ID = $items_shipping[array_key_first($items_shipping)]->get_method_id();

                if (class_exists('RY_WT_WC_ECPay_Shipping') && isset(RY_WT_WC_ECPay_Shipping::$support_methods[$method_ID]) && str_contains($method_ID, '_cvs')) {
                    return $this->set_store_address($address, $order, $method_ID);
                }

                if (class_exists('RY_WT_WC_NewebPay_Shipping') && isset(RY_WT_WC_NewebPay_Shipping::$support_methods[$method_ID]) && str_contains($method_ID, '_cvs')) {
                    return $this->set_store_address($address, $order, $method_ID);
                }

                if (class_exists('RY_WT_WC_SmilePay_Shipping') && isset(RY_WT_WC_SmilePay_Shipping::$support_methods[$method_ID]) && str_contains($method_ID, '_cvs')) {
                    return $this->set_store_address($address, $order, $method_ID);
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
        $address['phone'] = $order->get_shipping_phone();
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

    public function add_email_class($emails)
    {
        $emails['RY_Shipping_Email_Customer_CVS_Store'] = include RY_WT_PLUGIN_DIR . 'woocommerce/emails/shipping-customer-cvs-store.php';

        return $emails;
    }

    public function add_email_action($actions)
    {
        $actions[] = 'ry_shipping_customer_cvs_store';

        return $actions;
    }

    public function send_at_cvs_email($order_ID, $order = null)
    {
        if (!is_object($order)) {
            $order = wc_get_order($order_ID);
        }

        do_action('ry_shipping_customer_cvs_store', $order_ID, $order);
    }

    public function set_script()
    {
        wp_enqueue_script('ry-checkout');
        if (true === WC()->cart->needs_shipping_address()) {
            $fields = WC()->checkout()->get_checkout_fields('shipping');
            if (isset($fields['shipping_phone']) && false === $fields['shipping_phone']['required']) {
                wp_localize_script('ry-checkout', 'RyCheckoutParams', [
                    'i18n' => [
                        'required' => '<abbr class="required" title="' . esc_attr__('required', 'woocommerce') . '">*</abbr>',
                        'optional' => '<span class="optional">(' . esc_html__('optional', 'woocommerce') . ')</span>',
                    ],
                ]);
            }
        }
    }
}
