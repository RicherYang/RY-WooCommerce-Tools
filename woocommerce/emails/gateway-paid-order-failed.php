<?php

defined('ABSPATH') or exit;

if (!class_exists('RY_Gateway_Paid_Order_Failed', false)) {
    class RY_Gateway_Paid_Order_Failed extends WC_Email
    {
        public function __construct()
        {
            $this->id = 'ry_gateway_paid_order_failed';
            $this->title = __('Paid order get failed', 'ry-woocommerce-tools');
            $this->description = __('Notifies admins when an order in paid, but the gateway response pay failed.', 'ry-woocommerce-tools');
            $this->template_base = RY_WT_PLUGIN_DIR . 'templates/';
            $this->template_html = 'emails/gateway-paid-order-failed.php';
            $this->template_plain = 'emails/plain/gateway-paid-order-failed.php';
            $this->placeholders = [
                '{order_date}' => '',
                '{order_number}' => '',
            ];

            add_action('ry_gateway_paid_order_failed_notification', [$this, 'trigger']);

            parent::__construct();

            $this->recipient = $this->get_option('recipient', get_option('admin_email'));
        }

        public function get_default_subject()
        {
            return __('[{site_title}]: Order #{order_number} gateway response pay failed', 'ry-woocommerce-tools');
        }

        public function get_default_heading()
        {
            return __('Order gateway failed: #{order_number}', 'ry-woocommerce-tools');
        }

        public function trigger($order_ID)
        {
            $this->setup_locale();

            $order = wc_get_order($order_ID);
            if ($order instanceof WC_Order) {
                $this->object = $order;

                $this->placeholders['{order_date}'] = wc_format_datetime($this->object->get_date_created());
                $this->placeholders['{order_number}'] = $this->object->get_order_number();
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
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => true,
                'plain_text' => false,
                'email' => $this,
            ];
            return wc_get_template_html($this->template_html, $args, '', RY_WT_PLUGIN_DIR . 'templates/');
        }

        public function get_content_plain()
        {
            $args = [
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => true,
                'plain_text' => true,
                'email' => $this,
            ];
            return wc_get_template_html($this->template_plain, $args, '', RY_WT_PLUGIN_DIR . 'templates/');
        }

        public function get_default_additional_content()
        {
            return '';
        }

        public function init_form_fields()
        {
            parent::init_form_fields();

            $offset = array_search('enabled', array_keys($this->form_fields)) + 1;
            $this->form_fields = array_slice($this->form_fields, 0, $offset, true)
                + [
                    'recipient' => [
                        'title' => __('Recipient(s)', 'ry-woocommerce-tools'),
                        'type' => 'text',
                        /* translators: %s: admin email */
                        'description' => sprintf(__('Enter recipients (comma separated) for this email. Defaults to %s.', 'ry-woocommerce-tools'), '<code>' . esc_attr(get_option('admin_email')) . '</code>'),
                        'placeholder' => '',
                        'default' => '',
                        'desc_tip' => true,
                    ],
                ]
                + array_slice($this->form_fields, $offset, null, true);
        }
    }
}

return new RY_Gateway_Paid_Order_Failed();
