<?php

final class RY_WT_WC_Admin_Product
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_Admin_Product
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_filter('manage_edit-product_columns', [$this, 'add_columns']);
        add_action('manage_product_posts_custom_column', [$this, 'show_columns'], 10, 2);
    }

    public function add_columns($columns)
    {
        $add_columns = [
            'type_info' => __('Type info', 'ry-woocommerce-tools'),
        ];
        $pre_idx = array_search('name', array_keys($columns)) + 1;
        $pre_columns = array_splice($columns, 0, $pre_idx);
        return array_merge($pre_columns, $add_columns, $columns);
    }

    public function show_columns($column, $post_ID): void
    {
        static $product_types = null;

        if ($product_types === null) {
            $product_types = [];
            $type_options = apply_filters('product_type_options', wc_get_default_product_type_options());
            foreach (wc_get_product_types() as $type => $name) {
                $product_types[$type] = [
                    'name' => $name,
                    'list' => [],
                ];
                foreach ($type_options as $key => $option) {
                    if (str_contains($option['wrapper_class'], 'show_if_' . $type)) {
                        $product_types[$type]['list'][$key] = $option['label'];
                    }
                }
            }
        }

        if ($column === 'type_info') {
            $product = wc_get_product($post_ID);
            $type = $product->get_type();

            if (isset($product_types[$type])) {
                echo esc_html($product_types[$type]['name']);
            }

            $add_option = [];
            if ($type == 'variable') {
                $variation_ids = $product->get_visible_children();
                foreach ($variation_ids as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    foreach ($product_types[$type]['list'] as $key => $option_name) {
                        if (metadata_exists('post', $variation->get_id(), '_' . $key)) {
                            if (is_callable([$variation, "is_$key"]) ? $variation->{"is_$key"}() : 'yes' === get_post_meta($variation->get_id(), '_' . $key, true)) {
                                $add_option[] = $option_name;
                            }
                        }
                    }
                }
            } else {
                foreach ($product_types[$type]['list'] as $key => $option_name) {
                    if (metadata_exists('post', $product->get_id(), '_' . $key)) {
                        if (is_callable([$product, "is_$key"]) ? $product->{"is_$key"}() : 'yes' === get_post_meta($post_ID, '_' . $key, true)) {
                            $add_option[] = $option_name;
                        }
                    }
                }
            }
            $add_option = array_unique($add_option);
            if (!empty($add_option)) {
                echo '<br> - ' . esc_html(implode(', ', $add_option));
            }
        }
    }
}

RY_WT_WC_Admin_Product::instance();
