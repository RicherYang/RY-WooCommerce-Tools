<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

class RY_SmilePay_Gateway_Cvs_Fami extends RY_SmilePay_Gateway_Base
{
    public $payment_type = 6;

    public function __construct()
    {
        $this->id = 'ry_smilepay_cvs_fami';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via CVS Fami', 'ry-woocommerce-tools');
        $this->method_title = __('SmilePay CVS Fami', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->get_code_mode = true;

        $this->form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings-smilepay-gateway-cvs.php');
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->expire_date = (int) $this->get_option('expire_date', 4320);
        $this->min_amount = (int) $this->get_option('min_amount', 0);
        $this->max_amount = (int) $this->get_option('max_amount', 0);

        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'admin_payment_info']);

        parent::__construct();
    }

    public function is_available()
    {
        if ('yes' == $this->enabled && WC()->cart) {
            $total = $this->get_order_total();

            if ($total > 0) {
                if ($total < 34) {
                    return false;
                }
                if ($this->min_amount > 0 and $total < $this->min_amount) {
                    return false;
                }
                if ($this->max_amount > 0 and $total > $this->max_amount) {
                    return false;
                }
            }
        }

        return parent::is_available();
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('Pay via SmilePay CVS Fami', 'ry-woocommerce-tools'));
        wc_reduce_stock_levels($order_id);

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }

    public function process_admin_options()
    {
        $_POST['woocommerce_ry_smilepay_cvs_fami_expire_date'] = (int) $_POST['woocommerce_ry_smilepay_cvs_fami_expire_date'];
        if ($_POST['woocommerce_ry_smilepay_cvs_fami_expire_date'] < 120 || $_POST['woocommerce_ry_smilepay_cvs_fami_expire_date'] > 10080) {
            $_POST['woocommerce_ry_smilepay_cvs_fami_expire_date'] = 4320;
            WC_Admin_Settings::add_error(__('CVS payment deadline out of range. Set as default value.', 'ry-woocommerce-tools'));
        }

        $_POST['woocommerce_ry_smilepay_cvs_fami_min_amount'] = (int) $_POST['woocommerce_ry_smilepay_cvs_fami_min_amount'];
        if ($_POST['woocommerce_ry_smilepay_cvs_fami_min_amount'] > 0 && $_POST['woocommerce_ry_smilepay_cvs_fami_min_amount'] < 35) {
            $_POST['woocommerce_ry_smilepay_cvs_fami_min_amount'] = 0;
            /* translators: %s: Gateway method title */
            WC_Admin_Settings::add_error(sprintf(__('%s minimum amount out of range. Set as default value.', 'ry-woocommerce-tools'), $this->method_title));
        }

        $_POST['woocommerce_ry_smilepay_cvs_fami_max_amount'] = (int) $_POST['woocommerce_ry_smilepay_cvs_fami_max_amount'];
        if ($_POST['woocommerce_ry_smilepay_cvs_fami_max_amount'] > 20000) {
            /* translators: %1$s: Gateway method title, %2$d normal maximum */
            WC_Admin_Settings::add_message(sprintf(__('%1$s maximum amount more then normal maximum (%2$d).', 'ry-woocommerce-tools'), $this->method_title, 20000));
        }

        parent::process_admin_options();
    }

    public function admin_payment_info($order)
    {
        if ($order->get_payment_method() != 'ry_smilepay_cvs_fami') {
            return;
        } ?>
<h3 style="clear:both"><?=__('Payment details', 'ry-woocommerce-tools') ?>
</h3>
<table>
    <tr>
        <td><?=__('CVS code', 'ry-woocommerce-tools') ?>
        </td>
        <td><?=$order->get_meta('_smilepay_cvs_PaymentNo') ?>
        </td>
    </tr>
    <tr>
        <td><?=__('Payment deadline', 'ry-woocommerce-tools') ?>
        </td>
        <td><?=$order->get_meta('_smilepay_cvs_ExpireDate') ?>
        </td>
    </tr>
</table>
<?php
    }
}
