=== RY Tools for WooCommerce ===
Contributors: fantasyworld
Donate link: https://www.paypal.me/RicherYang
Tags: woocommerce, payment, gateway, shipping
Requires at least: 6.3
Requires PHP: 8.0
Tested up to: 6.6
Stable tag: 3.4.14
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

* PHP 8.0+
* WordPress 6.3+
* WooCommerce 8.0+

== Frequently Asked Questions ==
Basically I didn't follow the support forum at wordpress.org.
Please visit the [plugin forum page](https://ry-plugin.com/ry-woocommerce-tools/forum " ") with any questions.

== Screenshots ==

1. Basic WooCommerce settings.
2. ECPay gateway global settings.
3. SmilePay gateway global settings.

== Changelog ==

= 3.4.14 - 2024-10-07 =
* 更新 - 配合藍新調整金流參數內容。
* 修正 - 前台工具列未隱藏上線狀態。
* 修正 - 後台手動移除物流單後顯示錯誤。

= 3.4.13 - 2024-09-16 =
* 修正 - 修正速買配金物流串接時的錯誤。

= 3.4.12 - 2024-09-15 =
* 新增 - 使用區塊結帳時提示支援度問題。

= 3.4.11 - 2024-09-10 =
* 修正 - 運送到不同地址的電話欄位必填選填提示未正確切換。

= 3.4.10 - 2024-09-02 =
* 修正 - 信用卡分期選單在沒有輸入描述的時候無法顯示。

= 3.4.9 - 2024-08-27 =
* 新增 - 綠界選擇超商的回傳網址增加語系參數。

= 3.4.8 - 2024-08-05 =
* 修正 - 傳送物流收件人手機號碼時過濾 - 符號。

= 3.4.7 - 2024-08-02 =
* 修正 - 調整綠界測試模式使用的 MerchantID。
* 修正 - 調整綠界物流商品運送價值的計算方式。

= 3.4.6 - 2024-07-14 =
* 修正 - 修復後台訂單物流的 JavaScript 錯誤。

= 3.4.5 - 2024-07-12 =
* 修正 - 調整後台取得物流單號的動作。
* 修正 - 調整銀行代碼轉換名稱的方法。

= 3.4.4 - 2024/07/09 =
* 新增 - 相容的 RY Tools (Pro) for WooCommerce 版本資訊。

= 3.4.3 - 2024/07/01 =
* 修正 - 非外掛新增的運送方式的運送電話必填性質調整。

= 3.4.2 - 2024/06/11 =
* 修正 - 綠界選取超商門市後，結帳欄位內容未復原問題。

= 3.4.1 - 2024/06/11 =
* 更新 - 取消超商取貨需客戶運送地址限制。
* 修正 - 速買配串接金額問題。

= 3.3.4 - 2024/05/08 =
* 修正 - 綠界調整為等待取貨前置狀態驗證錯誤。

= 3.3.3 - 2024/04/12 =
* 配合 WordPress 6.5 進行調整。
* 修正 - 調整註冊自定義的訂單狀態邏輯。

= 3.3.2 - 2024/04/09 =
* 修正 - 配合藍新調整 API 傳遞參數。

= 3.3.1 - 2024/04/01 =
* 修正 - 移除不必要的設定選項。

= 3.3.0 - 2024/02/29 =
* 變更外掛名稱。
* 修正頁面資訊輸出驗證不完全導致的潛在安全性風險。

= 3.2.4 - 2024/02/24 =
* 修正頁面資訊輸出驗證不完全導致的潛在安全性風險
* 調整程式碼架構。

= 3.2.3 - 2024/02/23 =
* 修正 - 後台手動取得物流單號後，物流資訊可能未正常更新。

= 3.2.2 - 2024/02/18 =
* 修正 - 配合 WooCommerce 8.6 日誌紀錄調整紀錄內容。
* 修正 - 在藍新超商取貨與非藍新物流間切換導致無法正常結帳。
* 修正 - 速買配無法刪除物流資訊紀錄。

= 3.2.1 - 2024/02/16 =
* 修正 - 以商品原價做為物流申報貨品價值。
* 修正 - 手動取得物流單後改為獨立按鈕，原本是合併於訂單動作選單中。
* 修正 - 更新速買配商標圖示。
* 修正 - 部分文字輸出未進行安全性過濾。

= 3.1.0 - 2024/01/29 =
* 修正 - 調整程式碼避免固定參數被修正。
* 修正 - WooCommerce 新版物流參數欄位顯示異常。
* 新增 - 紀錄物流單申請的代收金額。

= 3.0.7 - 2024/01/27 =
* 修正 - 綠界超商條碼顯示錯誤。

= 3.0.6 - 2023/12/19 =
* 調整外掛限制支援 PHP 版本為 8.0 或以上版本。
* 修正 - 條碼繳費時無法正確顯示條碼。
* 修正 - 修正部分金流資訊頁面顯示可能的 XSS 安全性問題。

= 3.0.5 - 2023/11/29 =
* 修正 - 部分網站設定可能導致 JavaScript 參數錯誤的問題。

= 3.0.3.1 - 2023/11/14 =
* 修正 - 藍新超商取貨無法使用貨到付款錯誤

= 3.0.2 - 2023/11/07 =
* 修正 - 無法單獨啟動綠界金流模組錯誤。

= 3.0.1 - 2023/11/06 =
* 修正 - 修正無法儲存訂單錯誤。
* 修正 - 後台無法正確顯示訂單的超商資訊說明。

= 3.0.0 - 2023/11/05 =
* 調整外掛程式碼架構。
* 變更 - 支援 HPOS。
