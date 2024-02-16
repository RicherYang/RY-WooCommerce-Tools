<?php

include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/info-list-table.php';

class RY_ECPay_Shipping_Info_List_Table extends RY_WT_Shipping_Info_List_Table
{
    protected $info_meta_key = '_ecpay_shipping_info';

    public function get_columns()
    {
        $columns = [
            'id' => __('ECPay shipping ID', 'ry-woocommerce-tools'),
            'type' => __('Shipping Type', 'ry-woocommerce-tools'),
            'no' => __('Shipping no', 'ry-woocommerce-tools'),
            'store' => __('Store ID', 'ry-woocommerce-tools'),
            'status' => __('Shipping status', 'ry-woocommerce-tools'),
            'amount' => __('Declare amount', 'ry-woocommerce-tools'),
            'money' => __('Collection of money', 'ry-woocommerce-tools'),
            'change_time' => __('Status change time', 'ry-woocommerce-tools'),
            'create_time' => __('Create time', 'ry-woocommerce-tools'),
            'action' => '',
        ];
        return $columns;
    }

    public function column_money($item)
    {
        if('Y' === $item['IsCollection']) {
            esc_html_e('Yes', 'ry-woocommerce-tools');
        } elseif('N' === $item['IsCollection']) {
            esc_html_e('No', 'ry-woocommerce-tools');
        } else {
            esc_html_e('Yes', 'ry-woocommerce-tools');
            echo ' ( ' . esc_html($item['IsCollection']) . ' )';
        }
    }

    public function column_action($item)
    {
        $url = add_query_arg([
            'orderid' => $this->order->get_id(),
            'id' => $item['ID']
        ], admin_url('admin-post.php?action=ry-print-ecpay-shipping'));

        echo '<a class="button" href="' . esc_url($url) . '">' . esc_html__('Print', 'ry-woocommerce-tools') . '</a>';

        parent::column_action($item);
    }
}
