<?php

abstract class RY_WT_WC_Shipping_Method extends WC_Shipping_Method
{
    public string $cost;

    public string $cost_requires = '';

    public int $min_amount = 0;

    public function init()
    {
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->tax_status = $this->get_option('tax_status');
        $this->cost = $this->get_option('cost', '');
        $this->cost_requires = $this->get_option('cost_requires', '');
        $this->min_amount = (int) $this->get_option('min_amount', 0);

        if (!wc_tax_enabled()) {
            unset($this->instance_form_fields['tax_status']);
        }

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    public static function get_support_temp()
    {
        return ['1'];
    }

    public function is_available($package)
    {
        $available = $this->is_enabled();

        if ($available) {
            $temps = $this->get_package_temp($package);
            $available = 0 === count(array_diff($temps, $this->get_support_temp()));
        }

        if ($available) {
            $shipping_classes = WC()->shipping->get_shipping_classes();
            if (!empty($shipping_classes)) {
                $found_shipping_class = [];
                foreach ($package['contents'] as $values) {
                    if ($values['data']->needs_shipping()) {
                        $shipping_class_slug = $values['data']->get_shipping_class();
                        $shipping_class = get_term_by('slug', $shipping_class_slug, 'product_shipping_class');
                        if ($shipping_class && $shipping_class->term_id) {
                            $found_shipping_class[$shipping_class->term_id] = true;
                        }
                    }
                }
                foreach ($found_shipping_class as $shipping_class_term_id => $value) {
                    if ('yes' !== $this->get_option('class_available_' . $shipping_class_term_id, 'yes')) {
                        $available = false;
                        break;
                    }
                }
            }
        }

        if ($available) {
            return parent::is_available($package);
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $available, $package, $this);
    }

    public function calculate_shipping($package = [])
    {
        $rate = [
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => 0,
            'package' => $package,
            'meta_data' => [],
        ];
        $rate = $this->add_rate_meta_data($rate);

        $temps = $this->get_package_temp($package);
        $temps = array_diff($temps, $this->get_support_temp());
        $rate['cost'] = $this->evaluate_cost($this->cost, apply_filters('ry_shipping_evaluate_cost_args', [
            'temps' => implode(',', $temps),
            'qty' => $this->get_package_qty($package),
            'weight' => ceil($this->get_package_weight($package)),
        ], $package, $rate));

        if ($rate['cost'] > 0) {
            $has_coupon = $this->check_has_coupon($this->cost_requires, ['coupon', 'min_amount_or_coupon', 'min_amount_and_coupon', 'min_amount_except_discount_or_coupon', 'min_amount_except_discount_and_coupon']);
            $has_min_amount = $this->check_has_min_amount($this->cost_requires, ['min_amount', 'min_amount_or_coupon', 'min_amount_and_coupon']);
            $has_min_amount_original = $this->check_has_min_amount($this->cost_requires, ['min_amount_except_discount', 'min_amount_except_discount_or_coupon', 'min_amount_except_discount_and_coupon'], true);

            $set_cost_zero = match ($this->cost_requires) {
                'coupon' => $has_coupon,
                'min_amount' => $has_min_amount,
                'min_amount_or_coupon' => $has_min_amount || $has_coupon,
                'min_amount_and_coupon' => $has_min_amount && $has_coupon,
                'min_amount_except_discount' => $has_min_amount_original,
                'min_amount_except_discount_or_coupon' => $has_min_amount_original || $has_coupon,
                'min_amount_except_discount_and_coupon' => $has_min_amount_original && $has_coupon,
                default => false,
            };

            if ($set_cost_zero) {
                $rate['cost'] = 0;
            }
        }

        $this->add_rate($rate);
        do_action('woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate);
    }

    protected function get_package_temp($package)
    {
        $temps = [];
        foreach ($package['contents'] as $values) {
            if ($values['quantity'] > 0 && $values['data']->needs_shipping()) {
                $temp = $values['data']->get_meta('_ry_shipping_temp', true);
                if (empty($temp) && 'variation' === $values['data']->get_type()) {
                    $parent_product = wc_get_product($values['data']->get_parent_id());
                    $temp = $parent_product->get_meta('_ry_shipping_temp', true);
                }
                $temps[] = empty($temp) ? '1' : $temp;
            }
        }
        return array_unique($temps);
    }

    public function get_package_qty($package)
    {
        $total_quantity = 0;
        foreach ($package['contents'] as $values) {
            if ($values['quantity'] > 0 && $values['data']->needs_shipping()) {
                $total_quantity += $values['quantity'];
            }
        }
        return $total_quantity;
    }

    protected function get_package_weight($package)
    {
        WC()->cart->get_cart_contents_weight();
        $total_weight = 0;
        foreach ($package['contents'] as $values) {
            if ($values['quantity'] > 0 && $values['data']->needs_shipping()) {
                if ($values['data']->has_weight()) {
                    $total_weight += $values['data']->get_weight() * $values['quantity'];
                } else {
                    $total_weight += 0.1 * $values['quantity'];
                }
            }
        }
        return $total_weight;
    }

    protected function add_rate_meta_data($rate)
    {
        return $rate;
    }

    protected function check_has_coupon($requires, $check_requires_list): bool
    {
        if (in_array($requires, $check_requires_list)) {
            $coupons = WC()->cart->get_coupons();
            if ($coupons) {
                foreach ($coupons as $code => $coupon) {
                    if ($coupon->is_valid() && $coupon->get_free_shipping()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function check_has_min_amount($requires, $check_requires_list, $original = false): bool
    {
        if (in_array($requires, $check_requires_list)) {
            $total = WC()->cart->get_displayed_subtotal();
            if (false === $original) {
                if ('incl' === WC()->cart->get_tax_price_display_mode()) {
                    $total = round($total - (WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total()), wc_get_price_decimals());
                } else {
                    $total = round($total - WC()->cart->get_cart_discount_total(), wc_get_price_decimals());
                }
            }
            if ($total >= $this->min_amount) {
                return true;
            }
        }

        return false;
    }

    public function shortcode_addfee($atts)
    {
        $atts = shortcode_atts([
            'offisland' => '',
            'cool' => '',
            'cool_2' => '',
            'cool_3' => '',
        ], $atts, 'addfee');
        $add_fee = 0;

        if (isset($atts['ry-offisland'])) {
            if ($atts['ry-offisland'] == '1' && $atts['offisland']) {
                $add_fee += $atts['offisland'];
            }
        }

        if (isset($atts['ry-temps'])) {
            $temps = explode(',', $atts['ry-temps']);
            if ((in_array('2', $temps) || in_array('3', $temps)) && $atts['cool']) {
                $add_fee += $atts['cool'];
            }
            if (in_array('2', $temps) && $atts['cool_2']) {
                $add_fee += $atts['cool_2'];
            }
            if (in_array('3', $temps) && $atts['cool_3']) {
                $add_fee += $atts['cool_3'];
            }
        }

        return (string) $add_fee;
    }

    protected function evaluate_cost(string $sum, array $args)
    {
        global $shortcode_tags;

        if (empty($sum)) {
            return 0;
        }

        include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';

        $tmp_shortcode_tags = $shortcode_tags;
        remove_all_shortcodes();

        $args = array_merge([
            'qty' => 1,
            'weight' => 0,
        ], $args);

        add_filter('shortcode_atts_addfee', function ($out) use ($args) {
            foreach ($args as $key => $value) {
                $out['ry-' . $key] = $value;
            }
            return $out;
        });
        add_shortcode('addfee', [$this, 'shortcode_addfee']);

        $sum = do_shortcode(str_replace(
            ['[weight]', '[qty]'],
            [$args['weight'], $args['qty']],
            $sum
        ));
        $shortcode_tags = $tmp_shortcode_tags;

        $sum = preg_replace('/\s+/', '', $sum);
        $sum = rtrim(ltrim($sum, "\t\n\r\0\x0B+*/"), "\t\n\r\0\x0B+-*/");
        return round($sum ? WC_Eval_Math::evaluate($sum) : 0, wc_get_price_decimals());
    }

    public function sanitize_cost($value)
    {
        $value = is_null($value) ? '' : $value;
        $value = wp_kses_post(trim(wp_unslash($value)));

        $dummy_cost = $this->evaluate_cost($value, [
            'offisland' => '0',
            'temps' => '1',
            'qty' => 1,
            'weight' => 1,
        ]);
        if (false === $dummy_cost) {
            throw new Exception(WC_Eval_Math::$last_error);
        }
        return $value;
    }
}
