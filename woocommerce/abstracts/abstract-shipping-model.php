<?php

abstract class RY_WT_Shipping_Model extends RY_WT_Model
{
    protected $cvs_hide_fields = [];

    public function add_cvs_info($fields)
    {
        $shipping_phone_required = false;
        if (!isset($fields['shipping']['shipping_phone'])) {
            $fields['shipping']['shipping_phone'] = [
                'label' => __('Phone', 'ry-woocommerce-tools'),
                'required' => $shipping_phone_required,
                'type' => 'tel',
                'validate' => ['phone'],
                'class' => ['form-row-wide'],
                'priority' => 100,
            ];
        } else {
            $shipping_phone_required = $fields['shipping']['shipping_phone']['required'];
        }

        $cvs_hide_fields = ['shipping_postcode', 'shipping_state', 'shipping_city', 'shipping_address_1', 'shipping_address_2'];
        if (is_checkout()) {
            foreach ($cvs_hide_fields as $key) {
                if (isset($fields['shipping'][$key])) {
                    if (isset($fields['shipping'][$key]['class'])) {
                        if (!is_array($fields['shipping'][$key]['class'])) {
                            $fields['shipping'][$key]['class'] = [$fields['shipping'][$key]['class']];
                        }
                    } else {
                        $fields['shipping'][$key]['class'] = [];
                    }
                    $fields['shipping'][$key]['class'][] = 'ry-cvs-hide';
                }
            }
            foreach ($this->cvs_hide_fields as $key) {
                if (isset($fields['shipping'][$key])) {
                    if (isset($fields['shipping'][$key]['class'])) {
                        if (!is_array($fields['shipping'][$key]['class'])) {
                            $fields['shipping'][$key]['class'] = [$fields['shipping'][$key]['class']];
                        }
                    } else {
                        $fields['shipping'][$key]['class'] = [];
                    }
                    $fields['shipping'][$key]['class'][] = 'ry-' . substr($this->model_type, 0, -9) . '-cvs-hide';
                }
            }
        }

        if (did_action('woocommerce_checkout_process')) {
            $used = false;
            $used_cvs = false;
            $shipping_method = isset($_POST['shipping_method']) ? wc_clean($_POST['shipping_method']) : [];
            foreach ($shipping_method as $method) {
                $method = strstr($method, ':', true);
                if ($method && array_key_exists($method, static::$support_methods)) {
                    $used = true;
                    if (str_contains($method, '_cvs')) {
                        $used_cvs = true;
                    }
                }
            }

            if ($used_cvs) {
                foreach ($cvs_hide_fields as $key) {
                    if (isset($fields['shipping'][$key])) {
                        $fields['shipping'][$key]['required'] = false;
                    }
                }
                foreach ($this->cvs_hide_fields as $key) {
                    if (isset($fields['shipping'][$key])) {
                        $fields['shipping'][$key]['required'] = false;
                    }
                }

                $fields['shipping']['shipping_phone']['required'] = true;
            } elseif ($used) {
                $fields['shipping']['shipping_phone']['required'] = true;
            } else {
                $fields['shipping']['shipping_phone']['required'] = $shipping_phone_required;
            }
        }

        return $fields;
    }
}
