<?php

final class RY_WT
{
    public const Option_Prefix = 'RY_WT_';

    public static $min_WooCommerce_version = '8.0.0';

    protected static $_instance = null;

    public static function instance(): RY_WT
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        load_plugin_textdomain('ry-woocommerce-tools', false, plugin_basename(dirname(__DIR__)) . '/languages');

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'includes/update.php';
            RY_WT_Update::update();

            include_once RY_WT_PLUGIN_DIR . 'includes/admin.php';
            RY_WT_Admin::instance();
        }

        include_once RY_WT_PLUGIN_DIR . 'includes/cron.php';
        RY_WT_Cron::add_action();

        add_action('woocommerce_loaded', [$this, 'do_woo_init']);
    }

    public function do_woo_init(): void
    {
        if (!defined('WC_VERSION')) {
            return;
        }
        if (version_compare(WC_VERSION, self::$min_WooCommerce_version, '<')) {
            return;
        }

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-model.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-payment-gateway.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-shipping-method.php';

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/account.php';
        RY_WT_WC_Account::instance();
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/countries.php';
        RY_WT_WC_Countries::instance();

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/admin.php';
            RY_WT_WC_Admin::instance();
        } else {
            add_action('wp_enqueue_scripts', [$this, 'load_scripts']);
        }

        if ('yes' === self::get_option('enabled_ecpay_gateway', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway.php';
            RY_WT_WC_ECPay_Gateway::instance();
        }
        if ('yes' === self::get_option('enabled_ecpay_shipping', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/shipping.php';
            RY_WT_WC_ECPay_Shipping::instance();
        }

        if ('yes' === self::get_option('enabled_newebpay_gateway', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/gateway.php';
            RY_WT_WC_NewebPay_Gateway::instance();
        }
        if ('yes' === self::get_option('enabled_newebpay_shipping', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/newebpay/shipping.php';
            RY_WT_WC_NewebPay_Shipping::instance();
        }

        if ('yes' === self::get_option('enabled_smilepay_gateway', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/gateway.php';
            RY_WT_WC_SmilePay_Gateway::instance();
        }
        if ('yes' === self::get_option('enabled_smilepay_shipping', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/shipping.php';
            RY_WT_WC_SmilePay_Shipping::instance();
        }

        do_action('wp_woo_tools_loaded');
    }

    public function load_scripts()
    {
        wp_register_script('ry-wt-shipping', RY_WT_PLUGIN_URL . 'style/js/shipping.js', ['jquery'], RY_WT_VERSION, true);

    }

    public static function get_option($option, $default = false)
    {
        return get_option(self::Option_Prefix . $option, $default);
    }

    public static function update_option($option, $value, $autoload = null): bool
    {
        return update_option(self::Option_Prefix . $option, $value, $autoload);
    }

    public static function delete_option($option): bool
    {
        return delete_option(self::Option_Prefix . $option);
    }

    public static function plugin_activation(): void
    {
        if (!wp_next_scheduled('ry_check_ntp_time')) {
            self::update_option('ntp_time_error', false);
            wp_schedule_event(time(), 'daily', 'ry_check_ntp_time');
        }
    }

    public static function plugin_deactivation(): void
    {
        wp_unschedule_hook('ry_check_ntp_time');
    }

    // just for i18n use
    private static function bank_code_list()
    {
        _x('004', 'Bank code', 'ry-woocommerce-tools'); // 臺灣銀行
        _x('005', 'Bank code', 'ry-woocommerce-tools'); // 臺灣土地銀行
        _x('006', 'Bank code', 'ry-woocommerce-tools'); // 合作金庫商業銀行
        _x('007', 'Bank code', 'ry-woocommerce-tools'); // 第一商業銀行
        _x('008', 'Bank code', 'ry-woocommerce-tools'); // 華南商業銀行
        _x('009', 'Bank code', 'ry-woocommerce-tools'); // 彰化商業銀行
        _x('011', 'Bank code', 'ry-woocommerce-tools'); // 上海商業儲蓄銀行
        _x('012', 'Bank code', 'ry-woocommerce-tools'); // 台北富邦商業銀行
        _x('013', 'Bank code', 'ry-woocommerce-tools'); // 國泰世華商業銀行
        _x('015', 'Bank code', 'ry-woocommerce-tools'); // 中國輸出入銀行"
        _x('016', 'Bank code', 'ry-woocommerce-tools'); // 高雄銀行
        _x('017', 'Bank code', 'ry-woocommerce-tools'); // 兆豐國際商業銀行
        _x('021', 'Bank code', 'ry-woocommerce-tools'); // 花旗(台灣)商業銀行
        _x('048', 'Bank code', 'ry-woocommerce-tools'); // 王道商業銀行
        _x('050', 'Bank code', 'ry-woocommerce-tools'); // 臺灣中小企業銀行
        _x('052', 'Bank code', 'ry-woocommerce-tools'); // 渣打國際商業銀行
        _x('053', 'Bank code', 'ry-woocommerce-tools'); // 台中商業銀行
        _x('054', 'Bank code', 'ry-woocommerce-tools'); // 京城商業銀行
        _x('081', 'Bank code', 'ry-woocommerce-tools'); // 滙豐(台灣)商業銀行
        _x('101', 'Bank code', 'ry-woocommerce-tools'); // 瑞興商業銀行
        _x('102', 'Bank code', 'ry-woocommerce-tools'); // 華泰商業銀行
        _x('103', 'Bank code', 'ry-woocommerce-tools'); // 臺灣新光商業銀行
        _x('108', 'Bank code', 'ry-woocommerce-tools'); // 陽信商業銀行
        _x('118', 'Bank code', 'ry-woocommerce-tools'); // 板信商業銀行
        _x('147', 'Bank code', 'ry-woocommerce-tools'); // 三信商業銀行
        _x('803', 'Bank code', 'ry-woocommerce-tools'); // 聯邦商業銀行
        _x('805', 'Bank code', 'ry-woocommerce-tools'); // 遠東國際商業銀行
        _x('806', 'Bank code', 'ry-woocommerce-tools'); // 元大商業銀行
        _x('807', 'Bank code', 'ry-woocommerce-tools'); // 永豐商業銀行
        _x('808', 'Bank code', 'ry-woocommerce-tools'); // 玉山商業銀行
        _x('809', 'Bank code', 'ry-woocommerce-tools'); // 凱基商業銀行
        _x('810', 'Bank code', 'ry-woocommerce-tools'); // 星展(台灣)商業銀行
        _x('812', 'Bank code', 'ry-woocommerce-tools'); // 台新國際商業銀行
        _x('816', 'Bank code', 'ry-woocommerce-tools'); // 安泰商業銀行
        _x('822', 'Bank code', 'ry-woocommerce-tools'); // 中國信託商業銀行
        _x('823', 'Bank code', 'ry-woocommerce-tools'); // 將來商業銀行
        _x('824', 'Bank code', 'ry-woocommerce-tools'); // 連線商業銀行
        _x('826', 'Bank code', 'ry-woocommerce-tools'); // 樂天國際商業銀行

        _x('020', 'Bank code', 'ry-woocommerce-tools'); // 日商瑞穗銀行
        _x('022', 'Bank code', 'ry-woocommerce-tools'); // 美商美國銀行
        _x('023', 'Bank code', 'ry-woocommerce-tools'); // 泰國盤谷銀行
        _x('025', 'Bank code', 'ry-woocommerce-tools'); // 菲商菲律賓首都銀行
        _x('028', 'Bank code', 'ry-woocommerce-tools'); // 美商美國紐約梅隆銀行
        _x('029', 'Bank code', 'ry-woocommerce-tools'); // 新加坡商大華銀行
        _x('030', 'Bank code', 'ry-woocommerce-tools'); // 美商道富銀行
        _x('037', 'Bank code', 'ry-woocommerce-tools'); // 法商法國興業銀行
        _x('039', 'Bank code', 'ry-woocommerce-tools'); // 澳商澳盛銀行集團
        _x('072', 'Bank code', 'ry-woocommerce-tools'); // 德商德意志銀行
        _x('075', 'Bank code', 'ry-woocommerce-tools'); // 香港商東亞銀行
        _x('076', 'Bank code', 'ry-woocommerce-tools'); // 美商摩根大通銀行
        _x('078', 'Bank code', 'ry-woocommerce-tools'); // 新加坡商星展銀行
        _x('082', 'Bank code', 'ry-woocommerce-tools'); // 法商法國巴黎銀行
        _x('083', 'Bank code', 'ry-woocommerce-tools'); // 英商渣打銀行
        _x('085', 'Bank code', 'ry-woocommerce-tools'); // 新加坡商新加坡華僑銀行
        _x('086', 'Bank code', 'ry-woocommerce-tools'); // 法商東方匯理銀行
        _x('092', 'Bank code', 'ry-woocommerce-tools'); // 瑞士商瑞士銀行
        _x('093', 'Bank code', 'ry-woocommerce-tools'); // 荷蘭商安智銀行
        _x('097', 'Bank code', 'ry-woocommerce-tools'); // 美商富國銀行
        _x('098', 'Bank code', 'ry-woocommerce-tools'); // 日商三菱日聯銀行
        _x('321', 'Bank code', 'ry-woocommerce-tools'); // 日商三井住友銀行
        _x('324', 'Bank code', 'ry-woocommerce-tools'); // 美商花旗銀行
        _x('325', 'Bank code', 'ry-woocommerce-tools'); // 香港商香港上海滙豐銀行
        _x('326', 'Bank code', 'ry-woocommerce-tools'); // 西班牙商西班牙對外銀行
        _x('328', 'Bank code', 'ry-woocommerce-tools'); // 法商法國外貿銀行
        _x('329', 'Bank code', 'ry-woocommerce-tools'); // 印尼商印尼人民銀行
        _x('330', 'Bank code', 'ry-woocommerce-tools'); // 韓商韓亞銀行

        return false;
    }
}
