<?php

include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/info-list-table.php';

class RY_NewebPay_Shipping_Info_List_Table extends RY_WT_Shipping_Info_List_Table
{
    protected $info_meta_key = '_newebpay_shipping_info';

    public function prepare_items($order = null)
    {
        parent::prepare_items($order);

        foreach ($this->items as $idx => $item) {
            if (!isset($this->items[$idx]['LogisticsType'])) {
                $this->items[$idx]['LogisticsType'] = 'CVS';
            }
        }
    }

    public function get_columns()
    {
        return [
            'id' => __('NewebPay shipping ID', 'ry-woocommerce-tools'),
            'type' => __('Shipping Type', 'ry-woocommerce-tools'),
            'no' => __('Shipping no', 'ry-woocommerce-tools'),
            'store' => __('Store ID', 'ry-woocommerce-tools'),
            'amount' => __('Declare amount', 'ry-woocommerce-tools'),
            'money' => __('Collection of money', 'ry-woocommerce-tools'),
            'change_time' => __('Status change time', 'ry-woocommerce-tools'),
            'create_time' => __('Create time', 'ry-woocommerce-tools'),
            'action' => '',
        ];
    }

    public function column_type($item)
    {
        if ('CVS' == $item['LogisticsType']) {
            echo esc_html_x('CVS', 'shipping type', 'ry-woocommerce-tools');
        }
        if ('HOME' == $item['LogisticsType']) {
            echo esc_html_x('Home', 'shipping type', 'ry-woocommerce-tools');
        }

        echo ' (' . esc_html($item['Type']) . ')';
    }
}
