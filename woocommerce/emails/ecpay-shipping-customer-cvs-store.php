<?php
if (!class_exists('RY_ECPay_Shipping_Email_Customer_CVS_Store', false)) {
    class RY_ECPay_Shipping_Email_Customer_CVS_Store extends WC_Email
    {
        public function __construct()
        {
            $this->id = 'ry_ecpay_shipping_customer_cvs_store';
            $this->customer_email = true;

            $this->title = __('Product sended to cvs store', 'ry-woocommerce-tools');
            $this->description = __('This is an order notification sent to customers after the product send to CVS store with ECPay shipping.', 'ry-woocommerce-tools');
            $this->template_base = RY_WT_PLUGIN_DIR . 'templates/';
            $this->template_html = 'emails/customer-cvs-store.php';
            $this->template_plain = 'emails/plain/customer-cvs-store.php';
            $this->placeholders = [
                '{site_title}' => $this->get_blogname()
            ];

            add_action('ry_ecpay_shipping_cvs_to_store_notification', [$this, 'trigger'], 10, 2);

            parent::__construct();
        }

        public function get_default_subject()
        {
            return __('Your {site_title} order product send to CVS store', 'ry-woocommerce-tools');
        }

        public function get_default_heading()
        {
            return __('Product Pickup Notice', 'ry-woocommerce-tools');
        }

        public function trigger($order_id, $order = false)
        {
            $this->setup_locale();

            if ($order_id && ! is_a($order, 'WC_Order')) {
                $order = wc_get_order($order_id);
            }

            if (is_a($order, 'WC_Order')) {
                $this->object = $order;
                $this->recipient = $this->object->get_billing_email();
            }

            if ($this->is_enabled() && $this->get_recipient()) {
                $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
            }

            $this->restore_locale();
        }

        public function get_content_html()
        {
            $args = [
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_default_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this,
            ];
            if (version_compare(WC_VERSION, '3.7.0', '>=')) {
                $args['additional_content'] = $this->get_additional_content();
            }
            return wc_get_template_html($this->template_html, $args, '', RY_WT_PLUGIN_DIR . 'templates/');
        }

        public function get_content_plain()
        {
            $args = [
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_default_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this,
            ];
            if (version_compare(WC_VERSION, '3.7.0', '>=')) {
                $args['additional_content'] = $this->get_additional_content();
            }
            return wc_get_template_html($this->template_plain, $args, '', RY_WT_PLUGIN_DIR . 'templates/');
        }

        public function get_default_additional_content()
        {
            return __('We look forward to fulfilling your order soon.', 'woocommerce');
        }
    }
}

return new RY_ECPay_Shipping_Email_Customer_CVS_Store();
