<?php

class RY_WT_Shipping_Info_List_Table extends WP_List_Table
{
    public $order;

    public function prepare_items($order = null)
    {
        $this->_column_headers = [$this->get_columns(), [], []];

        $this->order = $order;
        $this->items = $this->order->get_meta($this->info_meta_key, true);
        if (!is_array($this->items)) {
            $this->items = [];
        }

        foreach($this->items as $idx => $item) {
            $this->items[$idx]['edit'] = wc_string_to_datetime($item['edit']);
            $this->items[$idx]['create'] = wc_string_to_datetime($item['create']);
        }
    }

    public function display()
    {
        ?>
<div class="ry-shipping-infos">
    <table class="striped widefat">
        <thead>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php $this->display_rows_or_placeholder(); ?>
        </tbody>
    </table>
</div>
<?php
    }

    public function display_action($type)
    {
        echo '<button type="button" class="button ry-' . esc_attr($type) . '-shipping-info" data-orderid="' . esc_attr($this->order->get_id()) . '">' . esc_html__('Get shipping no', 'ry-woocommerce-tools') . '</button>';

        if($this->has_items()) {
            if ('cod' === $this->order->get_payment_method()) {
                echo '<button type="button" class="button ry-' . esc_attr($type) . '-shipping-info" data-orderid="' . esc_attr($this->order->get_id()) . '" data-collection="Y">' . esc_html__('Get shipping no (cod)', 'ry-woocommerce-tools') . '</button>';
            }

            do_action('ry_shipping_info-action', $this->order, $type);
        }
    }

    protected function handle_row_actions($item, $column_name, $primary)
    {
        return '';
    }

    public function column_id($item)
    {
        echo esc_html($item['ID']);
    }

    public function column_type($item)
    {
        if ('CVS' == $item['LogisticsType']) {
            echo esc_html_x('CVS', 'shipping type', 'ry-woocommerce-tools');
        }
        if ('HOME' == $item['LogisticsType']) {
            echo esc_html_x('Home', 'shipping type', 'ry-woocommerce-tools');
        }

        if (isset($item['temp'])) {
            if (1 == $item['temp']) {
                echo ' (' . esc_html_x('Normal temperature', 'Transport temp', 'ry-woocommerce-tools') . ')';
            } elseif (2 == $item['temp']) {
                echo ' (' . esc_html_x('Refrigerated', 'Transport temp', 'ry-woocommerce-tools') . ')';
            } elseif (3 == $item['temp']) {
                echo ' (' . esc_html_x('Frozen', 'Transport temp', 'ry-woocommerce-tools') . ')';
            }
        }
    }

    public function column_no($item)
    {
        if ('CVS' == $item['LogisticsType']) {
            echo esc_html($item['PaymentNo']) . '<span class="validationno">' . esc_html($item['ValidationNo'] ?? '');
        }
        if ('HOME' == $item['LogisticsType']) {
            echo esc_html($item['BookingNote']);
        }
    }

    public function column_store($item)
    {
        if ('CVS' == $item['LogisticsType']) {
            echo esc_html($item['store_ID']);
        }
    }

    public function column_status($item)
    {
        echo esc_html($item['status_msg']);
    }

    public function column_amount($item)
    {
        echo esc_html($item['amount']);
    }

    public function column_money($item)
    {
        echo esc_html(($item['IsCollection'] == '1') ? __('Yes', 'ry-woocommerce-tools') : __('No', 'ry-woocommerce-tools'));
    }

    public function column_change_time($item)
    {
        echo esc_html(sprintf(
            /* translators: %1$s: date %2$s: time */
            _x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'),
            $item['edit']->date_i18n(wc_date_format()),
            $item['edit']->date_i18n(wc_time_format()),
        ));
    }

    public function column_create_time($item)
    {
        echo esc_html(sprintf(
            /* translators: %1$s: date %2$s: time */
            _x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'),
            $item['create']->date_i18n(wc_date_format()),
            $item['create']->date_i18n(wc_time_format()),
        ));
    }

    public function column_action($item)
    {
        echo '<button type="button" class="button ry-delete-shipping-info" data-orderid="' . esc_attr($this->order->get_id()) . '" data-id="' . esc_attr($item['ID']) . '">' . esc_html__('Delete', 'ry-woocommerce-tools') . '</button>';
    }
}
