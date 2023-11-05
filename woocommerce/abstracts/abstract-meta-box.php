<?php

abstract class RY_WT_WC_Meta_Box
{
    protected static function get_order_object($data_object)
    {
        if (is_a($data_object, 'WP_Post')) {
            return wc_get_order($data_object->ID);
        }

        if (is_a($data_object, 'WC_Order')) {
            return $data_object;
        }

        return false;
    }
}
