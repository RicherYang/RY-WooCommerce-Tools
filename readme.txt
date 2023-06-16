=== RY WooCommerce Tools ===
Contributors: fantasyworld
Donate link: https://www.paypal.me/RicherYang
Tags: woocommerce, payment, gateway, shipping, ecpay, newebpay, smilepay
Requires at least: 5.6
Requires PHP: 7.4
Tested up to: 6.2
Stable tag: 2.0.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Accept your WooCommerce store payment with ECPay / NewebPay / SmilePay and shipping with ECPay / NewebPay / SmilePay.

== Description ==

Let your WooCommerce store support ECPay / NewebPay / SmilePay.

Let your WooCommerce store more with Taiwan's habits.

= Contributors =
You can contribute to this plugin to [GitHub repository](https://github.com/RicherYang/RY-WooCommerce-Tools)

== Installation ==

= Minimum Requirements =

* PHP 7.4+
* WordPress 5.6+
* WooCommerce 5.0+


== Frequently Asked Questions ==
Basically I didn't follow the support forum at wordpress.org.
Please visit the [plugin forum page](https://ry-plugin.com/ry-woocommerce-tools/forum " ") with any questions.

== Screenshots ==

1. Basic WooCommerce settings.
2. ECPay gateway global settings.
3. ECPay shipping global settings.
4. NewebPay gateway global settings.

== Changelog ==

= 2.0.1 - 2023/06/16 =
* 變更 - 修正使用者於第三方付款頁面直接返回上一頁時頁面內容卡住的問題。

= 2.0.0 - 2023/05/27 =
* 變更 - 調整授權條款為 GPLv3。
* 新增 - 支援綠界物流回應狀態碼 2076 。

= 1.12.7.1 - 2023/05/03 =
* 更新 - 調整 WooCommerce Blocks 的相容性提示內容。

= 1.12.7 - 2023/05/03 =
* 新增 - 提示並未測試與 WooCommerce Blocks 的相容性。

= 1.12.6.1 - 2023/03/30 =
* 更新 - 調整 WordPress 核心測試相容版本。

= 1.12.6 = 2023/03/21 =
* 更新 - 修正可能的網址 XSS 風險。
* 更新 - 調整 PHP 引入檔案的寫法。

= 1.12.5 - 2023/02/19 =
* 更新 - 修正 PHP 8.1 相容性問題。

= 1.12.4 - 2023/02/08 =
* 更新 - 修正使用手機無法完整瀏覽物流資訊問題。

= 1.12.3.2 - 2023/01/11 =
* 更新 - 修正可能產生 PHP 錯誤紀錄的問題。

= 1.12.3.1 - 2023/01/03 =
* 更新 - 修正錯字。

= 1.12.3 - 2022/12/18 =
* 更新 - 調整除錯紀錄原則。

= 1.12.2 - 2022/12/09 =
* 更新 - 將運送中訂單狀態加入，以避免因為停用付費版導致訂單無法觀看。

= 1.12.1 - 2022/10/04 =
* 更新 - 調整不直接對 postmeta 進行操作。

= 1.12.0 - 2022/09/26 =
* 更新 - 使用藍新 API V2.0

= 1.11.4 - 2022/09/05 =
* 更新 - 使用 Google Fonts 目前版本的引用連結

= 1.11.3 - 2022/09/04 =
* 新增 - 綠界回應收款狀態，當該狀態代碼無對應動作則訂單備註加入說明

= 1.11.2 - 2022/08/10 =
* 更新 - 修正綠界選擇超商手機板支援錯誤。

= 1.11.0 - 2022/07/27 =
* 新增 - 支援綠界宅配-郵局

= 1.10.3 - 2022/07/27 =
* 新增 - 可設定綠界物流預設取件時間與包裝尺寸
* 新增 - 可依照商品尺寸調整綠界的包裝尺寸

= 1.10.1 - 2022/07/18 =
* 更新 - 修正無運送方式的訂單會無法更新。

= 1.10.0 - 2022/07/17 =
* 更新 - 調整設定說明。
* 更新 - 調整物流程式前台效能。
* 更新 - 速買配物流，「訂單自動完成」設定調整為「自動更新訂單狀態」設定
