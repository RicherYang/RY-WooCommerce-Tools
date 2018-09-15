=== RY WooCommerce Tools ===
Contributors: Richer Yang
Donate link: https://www.paypal.me/RicherYang
Tags: woocommerce, payment, gateway, ecpay
Requires at least: 4.0
Tested up to: 4.9.8
Stable tag: 0.0.33
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Accept your WooCommerce store via ECPay payment gateway and ECPay shipping.

== Description ==

Let your WooCommerce store support ECPay payment gateway and ECPay shipping.

Let your WooCommerce store more with Taiwan's habits.

== Installation ==

= Minimum Requirements =

* WooCommerce 3.0+

== Frequently Asked Questions ==
Basically I didn't follow the support forum at wordpress.org.
Please visit the [plugin page](https://richer.tw/ry-woocommerce-tools/ " ") with any questions.

基本上我是不關注 wordpress.org 上的支援論壇的。
有任何的問題請至 [外掛支援](https://richer.tw/ry-woocommerce-tools/ " ") 提出。

== Screenshots ==

1. Basic WooCommerce settings.
2. ECPay gateway settings.
3. ECPay shipping global settings.
4. ECPay shipping settings.
5. WooCommerce Order shipping convenience store info.

== Changelog ==

= 0.0.33 - 2018/09/14 =
* 修正 - 綠界超商代碼與超商條碼的金額限制

= 0.0.32 - 2018/08/30 = 
* 修正 - 在網站名稱包含特殊字元時，串接綠界會發生錯誤
* 修正 - 調整綠界超商代碼與超商條碼的金額限制

= 0.0.31 - 2018/08/01 =
* 修正 - 綠界物流商品名稱限制調整
* 修正 - 超商取貨與運送目的地設定相容性問題

= 0.0.30 - 2018/07/10 =
* 修正 - 特定情況下，付款方式的啟用狀態標示錯誤。

= 0.0.29 - 2018/07/09 =
* 修正 - 無法結帳錯誤。

= 0.0.28 - 2018/07/08 =
* 修正 - 重新付款時的付款條件判斷錯誤。

= 0.0.27 - 2018/07/02 =
* 修正 - Text Domain 的寫法
* 修正 - 結帳頁面是否強制 SSL 模式偵錯錯誤。

= 0.0.26 - 2018/06/28 =
* 修正 - 升級到 0.0.23.1 時可能發生 php 錯誤。

= 0.0.25 - 2018/06/27 =
* 修正 - 在與 WooCommerce 3.1.0 之前版本相容性問題。

= 0.0.24 - 2018/06/26 =
* 修正 - 後台編輯訂單，編輯帳單資訊時可能跑版問題。
* 新增 - 後台編輯訂單，編輯取貨門市資訊。

= 0.0.23.1 - 2018/06/25 =
* 修正 - WordPress 時區非 UTC+8 的問題

= 0.0.22 - 2018/06/23 =
* 修正 - 在與 WooCommerce 3.2.0 之前版本相容性問題。

= 0.0.21 - 2018/06/22 =
* 修正 - 在與 WooCommerce 3.2.0 之前版本相容性問題。

= 0.0.20 - 2018/06/14 =
* 修正 - 結帳頁面跑版問題。

= 0.0.19 - 2018/06/13 =
* 修正 - 結帳頁面跑版問題。

= 0.0.18 - 2018/06/11 =
* 移除 - 單行地址設定（WooCommerce 已內鍵本功能）。
* 移除 - 姓名合併設定。
* 新增 - 先顯示姓名設定。

= 0.0.17 - 2018/06/09 =
* 新增 - 訂單取得新的超商物流編號支援貨到付款模式。

= 0.0.16 - 2018/06/07 =
* 修正 - 結帳頁面，超商收件人聯絡電話必選填顯示錯誤。

= 0.0.15.1 - 2018/05/24 =
* 修正 - 支援 WooCommerce 3.4.0。

= 0.0.15 - 2018/05/23 =
* 新增 - 綠界超商取貨可以於後台列印託運單。

= 0.0.14 - 2018/05/23 =
* 新增 - 綠界物流可設定在訂單狀態變更為處理中時是否自動取得物流編號。

= 0.0.13 - 2018/05/22 =
* 修正 - 綠界超商取貨的情況下，收件人連絡電話儲存失敗。
* 新增 - 支援綠界信用卡分期付款。

= 0.0.12 - 2018/05/11 =
* 新增 - 於訂單動作中，重新發送超商取貨通知信。

= 0.0.11 - 2018/05/07 =
* 修正 - 更新超商取貨訂單時，收件者的聯絡電話不會更新。
* 修正 - 綠界金流物流，寄件人姓名長度驗證錯誤。

= 0.0.10 - 2018/04/26 =
* 修正 - 綠界金流物流，訂單前綴字可輸入到綠界不允許的字符。

= 0.0.9 - 2018/04/21 =
* 新增 - 訂單等待取貨（便利商店）狀態。
* 修正 - 修正 GPLv2 授權條款。

= 0.0.8 - 2018/04/08 =
* 新增 - 綠界付款模組，最小訂購金額設定。

= 0.0.7 - 2018/04/02 =
* 新增 - 與 Gooogle Public NTP 進行目前時間比對。

= 0.0.6 - 2018/03/25 =
* 修正 - 在手機上選擇超商門市可能錯誤。

= 0.0.5 - 2018/03/23 =
* 新增 - 加入更多的 action/filter 方便針對本外掛功能進行再開發。

= 0.0.4.1 - 2018/03/22 =
* 修正 - 在手機瀏覽器底下，站內付結帳框無法顯示問題。

= 0.0.4 - 2018/03/21 =
* 新增 - 支援綠界科技金流服務【站內付】結帳方式。

= 0.0.3 - 2018/03/21 =
* 新增 - 當使用者取件完成後，自動完成訂單。

= 0.0.2.2 - 2018/03/15 =
* 修正 - 啟用綠界金流模組後導致【外觀 → 選單】功能異常。

= 0.0.2.1 - 2018/03/13 =
* 修正 - 綠界金流模組取消測試模式啟用錯誤。

= 0.0.2 - 2018/03/12 =
* 新增 - 支援 綠界科技物流服務。
* 修正 - 綠界科技金流服務模式。單一付款方式為一個結帳項目。

= 0.0.1 - 2018/03/08 =
* 新增 - 正式發佈。

[See changelog for all versions](https://richer.tw/ry-woocommerce-tools/history/).
