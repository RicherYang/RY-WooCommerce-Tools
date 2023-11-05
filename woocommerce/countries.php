<?php

final class RY_WT_WC_Countries
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_Countries
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_filter('woocommerce_localisation_address_formats', [$this, 'add_address_format']);

        add_filter('woocommerce_form_field_ry-hidden-country', [$this, 'field_hidden_country'], 20, 4);
        add_filter('woocommerce_form_field_ry-hidden-text', [$this, 'field_hidden_text'], 20, 4);

        if ('no' === RY_WT::get_option('show_country_select', 'yes')) {
            add_filter('woocommerce_billing_fields', [$this, 'hide_country']);
            add_filter('woocommerce_shipping_fields', [$this, 'hide_country']);
        }
        if ('yes' === RY_WT::get_option('last_name_first', 'no')) {
            add_filter('woocommerce_default_address_fields', [$this, 'last_name_first']);
        }
        if ('yes' === RY_WT::get_option('address_zip_first', 'no')) {
            add_filter('woocommerce_default_address_fields', [$this, 'address_zip_first']);
        }
    }

    public function add_address_format($address_formats)
    {
        $address_formats['TW'] = "{postcode}\n{country} {state} {city}\n{address_1} {address_2}\n{company} {last_name} {first_name}";
        if (is_admin()) {
            $address_formats['CVS'] = "{last_name}{first_name}\n{shipping_type}\n{cvs_store_name} ({cvs_store_ID})\n";
        } else {
            $address_formats['CVS'] = "{cvs_store_name} ({cvs_store_ID})\n{cvs_store_address}\n{cvs_store_telephone}\n{last_name} {first_name}\n";
        }

        return $address_formats;
    }

    public function field_hidden_country($field, $key, $args, $value)
    {
        $custom_attributes = $this->form_field_custom_attributes($args);
        if(empty($value)) {
            $countries = 'shipping_country' === $key ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();
            $value = array_key_first($countries);
        }

        $field = '<input type="hidden" class="' . esc_attr(implode(' ', $args['input_class'])) . '" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" value="' . esc_attr($value) . '" ' . implode(' ', $custom_attributes) . '>';

        return $field;
    }

    public function field_hidden_text($field, $key, $args, $value)
    {
        $custom_attributes = $this->form_field_custom_attributes($args);
        if ($args['required']) {
            $args['class'][] = 'validate-required';
            $required = ' <abbr class="required" title="' . esc_attr__('required', 'ry-woocommerce-tools') . '">*</abbr>';
        } else {
            $required = '';
        }
        $field = '<label class="' . esc_attr(implode(' ', $args['label_class'])) . '">' . $args['label'] . $required . '</label>';
        $field .= '<strong>' . esc_html($value) . '</strong>';
        $field .= '<input type="hidden" class="' . esc_attr(implode(' ', $args['input_class'])) . '" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" value="' . esc_attr($value) . '" ' . implode(' ', $custom_attributes) . '>';

        $sort = $args['priority'] ? $args['priority'] : '';
        $field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr($sort) . '">%3$s</p>';
        $container_class = esc_attr(implode(' ', $args['class']));
        $container_ID = esc_attr($args['id']) . '_field';
        $field = sprintf($field_container, $container_class, $container_ID, $field);

        return $field;
    }

    public function hide_country($fields)
    {
        if (isset($fields['billing_country'])) {
            $fields['billing_country']['type'] = 'ry-hidden-country';
            $fields['billing_country']['required'] = false;
        }
        if (isset($fields['shipping_country'])) {
            $fields['shipping_country']['type'] = 'ry-hidden-country';
            $fields['shipping_country']['required'] = false;
        }

        return $fields;
    }

    public function last_name_first($fields)
    {
        $priority = [];
        foreach(['first_name', 'last_name'] as $type) {
            if(isset($fields[$type], $fields[$type]['priority'])) {
                $priority[$type] = $fields[$type]['priority'];
            }
        }

        if(2 === count($priority)) {
            if($priority['first_name'] < $priority['last_name']) {
                $fields['first_name']['priority'] = $priority['last_name'];
                $fields['last_name']['priority'] = $priority['first_name'];

                $first_class_key = array_search('form-row-first', $fields['first_name']['class']);
                $last_class_key = array_search('form-row-last', $fields['last_name']['class']);
                if(false !== $first_class_key && false !== $last_class_key) {
                    unset($fields['first_name']['class'][$first_class_key]);
                    unset($fields['last_name']['class'][$last_class_key]);

                    $fields['first_name']['class'][] = 'form-row-last';
                    $fields['last_name']['class'][] = 'form-row-first';
                }
            }
        }

        return $fields;
    }

    public function address_zip_first($fields)
    {
        $priority = [];
        foreach(['postcode', 'state', 'city', 'address_1', 'address_2'] as $type) {
            if(isset($fields[$type], $fields[$type]['priority'])) {
                $priority[] = $fields[$type]['priority'];
            }
        }
        sort($priority);
        foreach(['postcode', 'state', 'city', 'address_1', 'address_2'] as $type) {
            if(isset($fields[$type])) {
                $fields[$type]['priority'] = array_shift($priority);
            }
        }

        return $fields;
    }

    protected function form_field_custom_attributes($args)
    {
        $custom_attributes = [];
        $args['custom_attributes'] = array_filter((array) $args['custom_attributes'], 'strlen');
        if (!empty($args['custom_attributes']) && is_array($args['custom_attributes'])) {
            foreach ($args['custom_attributes'] as $attribute => $attribute_value) {
                $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
            }
        }

        return $custom_attributes;
    }
}
