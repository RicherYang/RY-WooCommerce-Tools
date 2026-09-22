<?php

defined('ABSPATH') or exit;

abstract class RY_WT_Meta_Box
{
    protected static function get_order_object($data_object)
    {
        if ($data_object instanceof WP_Post) {
            return wc_get_order($data_object->ID);
        }

        if ($data_object instanceof WC_Order) {
            return $data_object;
        }

        if (is_int($data_object)) {
            return wc_get_order($data_object);
        }

        return false;
    }
}
