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
            '1' => _x('寄貨中', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '2' => _x('已到收件門市', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '3' => _x('已取貨', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '4' => _x('退貨', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '5' => _x('退貨到店', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '6' => _x('退貨取貨', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '7' => _x('退貨至物流中心', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '812' => _x('收件門市資料更新', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '822' => _x('退貨門市資料更新', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '811' => _x('收件門市重選門市', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '821' => _x('退貨門市重選門市', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '901' => _x('商品遺失查詢中', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '902' => _x('商品確定已遺失', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '903' => _x('進入判賠流程', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '904' => _x('商品判賠完成', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '905' => _x('物流中心待宅配', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '906' => _x('提交宅配資料', 'SmilePay shipping status', 'ry-woocommerce-tools'),
            '907' => _x('宅配出貨', 'SmilePay shipping status', 'ry-woocommerce-tools'),
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
                'id' => $item['ID']
            ], admin_url('admin-post.php?action=ry-print-smilepay-shipping'));

            echo '<a class="button" href="' . esc_url($url) . '">' . esc_html__('Print', 'ry-woocommerce-tools') . '</a>';
        }

        parent::column_action($item);
    }
}
