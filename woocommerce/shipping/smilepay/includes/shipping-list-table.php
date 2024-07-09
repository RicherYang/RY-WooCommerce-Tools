<?php

include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/info-list-table.php';

class RY_SmilePay_Shipping_Info_List_Table extends RY_WT_Shipping_Info_List_Table
{
    protected $info_meta_key = '_smilepay_shipping_info';

    public function prepare_items($order = null)
    {
        parent::prepare_items($order);

        foreach($this->items as $idx => $item) {
            if(!isset($this->items[$idx]['LogisticsType'])) {
                $this->items[$idx]['LogisticsType'] = 'CVS';
            }
            if(!isset($this->items[$idx]['store_ID'])) {
                $this->items[$idx]['store_ID'] = $item['storeID'];
            }
        }
    }

    public function get_columns()
    {
        $columns = [
            'id' => __('SmilePay shipping ID', 'ry-woocommerce-tools'),
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

    public function column_type($item)
    {
        if ('CVS' == $item['LogisticsType']) {
            echo esc_html_x('CVS', 'shipping type', 'ry-woocommerce-tools');
        }
        if ('HOME' == $item['LogisticsType']) {
            echo esc_html_x('Home', 'shipping type', 'ry-woocommerce-tools');
        }

        echo ' (' . esc_html($item['type']) . ')';
    }

    public function column_status($item)
    {
        $status_msg = [
            '1' => '寄貨中',
            '2' => '已到收件門市',
            '3' => '已取貨',
            '4' => '退貨',
            '5' => '退貨到店',
            '6' => '退貨取貨',
            '7' => '退貨至物流中心',
            '812' => '收件門市資料更新',
            '822' => '退貨門市資料更新',
            '811' => '收件門市重選門市',
            '821' => '退貨門市重選門市',
            '901' => '商品遺失查詢中',
            '902' => '商品確定已遺失',
            '903' => '進入判賠流程',
            '904' => '商品判賠完成',
            '905' => '物流中心待宅配',
            '906' => '提交宅配資料',
            '907' => '宅配出貨',
        ];

        if(!empty($item['PaymentNo'])) {
            echo esc_html($status_msg[$item['status']] ?? $item['status']);
        }
    }

    public function column_action($item)
    {
        if (empty($item['PaymentNo'])) {
            echo '<button type="button" class="button ry-smilepay-shipping-no" data-orderid="' . esc_attr($this->order->get_id()) . '" data-id="' . esc_attr($item['ID']) . '">' . esc_html__('Get no', 'ry-woocommerce-tools') . '</button>';
        } else {
            $url = add_query_arg([
                'orderid' => $this->order->get_id(),
                'id' => $item['ID'],
            ], admin_url('admin-post.php?action=ry-print-smilepay-shipping'));

            echo '<a class="button" href="' . esc_url($url) . '">' . esc_html__('Print', 'ry-woocommerce-tools') . '</a>';
        }

        parent::column_action($item);
    }
}
