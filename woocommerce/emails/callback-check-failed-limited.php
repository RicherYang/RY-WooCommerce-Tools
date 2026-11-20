<?php

defined('ABSPATH') or exit;

if (!class_exists('RY_Callback_Check_Failed_Limited', false)) {
    class RY_Callback_Check_Failed_Limited extends WC_Email
    {
        public function __construct()
        {
            $this->id = 'ry_callback_check_failed_limited';
            $this->title = __('Callback check failed limited', 'ry-woocommerce-tools');
            $this->description = __('Notifies admins when callback check failed is limited.', 'ry-woocommerce-tools');
            $this->template_base = RY_WT_PLUGIN_DIR . 'templates/';
            $this->template_html = 'emails/callback-check-failed-limited.php';
            $this->template_plain = 'emails/plain/callback-check-failed-limited.php';
            $this->placeholders = [
                'provider',
            ];

            add_action('ry_callback_check_failed_limited_notification', [$this, 'trigger'], 10, 2);

            parent::__construct();

            $this->recipient = $this->get_option('recipient', get_option('admin_email'));
        }

        public function get_default_subject()
        {
            return __('[{site_title}]: "{provider}" Callback check failed limited', 'ry-woocommerce-tools');
        }

        public function get_default_heading()
        {
            return __('Callback check failed limited', 'ry-woocommerce-tools');
        }

        /**
         * @param string $type
         */
        public function trigger($type, $seconds = false)
        {
            $this->setup_locale();

            $this->placeholders['{provider}'] = $type;
            $this->placeholders['{seconds}'] = $seconds;

            if ($this->is_enabled() && $this->get_recipient()) {
                $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
            }

            $this->restore_locale();
        }

        public function get_content_html()
        {
            $args = [
                'provider' => $this->placeholders['{provider}'],
                'seconds' => $this->placeholders['{seconds}'],
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
                'provider' => $this->placeholders['{provider}'],
                'seconds' => $this->placeholders['{seconds}'],
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

return new RY_Callback_Check_Failed_Limited();
