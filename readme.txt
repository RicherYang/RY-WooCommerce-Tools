=== RY WooCommerce Tools ===
Contributors: fantasyworld
Donate link: https://www.paypal.me/RicherYang
Tags: woocommerce, payment, gateway, shipping, ecpay, newebpay, smilepay
Requires at least: 5.6
Requires PHP: 7.4
Tested up to: 5.9
Stable tag: 1.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Accept your WooCommerce store payment with ECPay / NewebPay / SmilePay and shipping with ECPay / NewebPay / SmilePay.

== Description ==

Let your WooCommerce store support ECPay / NewebPay / SmilePay.

Let your WooCommerce store more with Taiwan's habits.

== Installation ==

= Minimum Requirements =

* PHP 7.4+
* WordPress 5.6+
* WooCommerce 5.0+


== Frequently Asked Questions ==
Basically I didn't follow the support forum at wordpress.org.
Please visit the [plugin page](https://richer.tw/ry-woocommerce-tools/ " ") with any questions.

== Screenshots ==

1. Basic WooCommerce settings.
2. ECPay gateway global settings.
3. ECPay shipping global settings.
4. NewebPay gateway global settings.

== Changelog ==

= 1.9.0 - 2022/04/04 =
* 更新 - 刪除無用程式碼。
* 更新 - 調整訂單明細物流資訊表格欄位名稱。
* 更新 - "取消" 依據重量加成運費自動取得複數物流單號功能。

= 1.8.18 - 2022/03/29 =
* 更新 - 修正綠界宅配手機號碼填寫確認錯誤。

= 1.8.17 - 2022/03/26 =
* 更新 - 調整結帳選擇超商按鈕的 html 結構。

= 1.8.16 - 2022/03/24 =
* 新增 - 收到第三方回傳內容訂單號可以客製化轉回真實訂單號碼。

= 1.8.14 - 2022/02/28 =
* 更新 - 調整綠界物流 API 內容。

= 1.8.13 - 2022/02/25 =
* 更新 - 修正樣板部分文字輸出未經過過濾。

= 1.8.12 - 2022/02/11 =
* 更新 - 調整綠界超商取貨逾時退貨的可能狀態代碼。
* 更新 - 變更訂單狀態為超商取貨專用狀態前，檢查目前狀態是否符合預期。

= 1.8.11 - 2022/01/17 =
* 更新 - 調整綠界物流自動完成訂單設定為自動跟隨物流狀態調整訂單狀態

= 1.8.10 - 2022/01/02 =
* 更新 - 修正綠界超商取貨產生複數託運單的時候代收款設定錯誤
* 調整 - 調整後台訂單物資訊預設顯示位置

= 1.8.9 - 2021/12/21 =
* 調整 - PHP 版本需求

= 1.8.8 - 2021/12/27 =
* 調整 - 調整後台設定說明文字
* 更新 - 調整除錯模式預設值

= 1.8.7 - 2021/12/18 =
* 更新 - 修正藍新 ATM 帳號資訊無法顯示

= 1.8.5 - 2021/12/18 =
* 更新 - 修正藍新超商取貨付款結帳失敗

= 1.8.4 - 2021/12/16 =
* 更新 - 調整金流最小訂購金額未限制情況下最小金額的問題

= 1.8.3 - 2021/12/15 =
* 更新 - 修正因為 cookie SameSite 的設定導致結帳從第三方跳回時呈現登出狀態的問題

= 1.8.2 - 2021/11/25 =
* 更新 - 修正超商取貨收件人電話重複出現

= 1.8.1 - 2021/11/22 =
* 更新 - 修正 PHP8 相容性

= 1.8.0 - 2021/11/13 =
* 更新 - 調整物流物件的通用性
* 更新 - 移除使用到 WooCommerce 建議棄用的功能 ( WC_Cart->tax_display_cart )。

[See changelog for all versions](https://richer.tw/ry-woocommerce-tools/history/).
