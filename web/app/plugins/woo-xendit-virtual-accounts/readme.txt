=== Plugin Name ===
Contributors: Xendit
Donate link: #
Tags: xendit, woocommerce, payment, payment gateway, virtual account, credit card, ewallet
Requires at least: 3.0.1
Tested up to: 5.7
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Xendit Payment Gateway for WooCommerce

== Description ==

Enable the following payment methods on the checkout page:

* IDR, PHP, USD
- Credit Card (Mastercard, VISA, JCB, American Express)

* IDR
- Bank Transfer (BCA, BNI, BRI, Mandiri, Permata)
- eWallet (OVO, DANA, LinkAja, QRIS, ShopeePay)
- Retail Outlet (Alfamart and Indomaret)
- Cardless Credit (Kredivo)
- Direct Debit (BRI)

* PHP
- eWallet (PayMaya, GCash, GrabPay)
- Retail Outlet (7 Eleven)
- Direct Debit (BPI)

Features:
- Support multiple currencies (IDR, PHP & USD)
- Refund via Xendisburse
- Subscription with credit card
- Allow credit/debit cards to be saved to customer's account
- XenPlatform support

== Installation ==

1. Make sure you have [WooCommerce](https://wordpress.org/plugins/woocommerce/) installed.
2. Search and install "Woocommerce – Xendit" on WordPress plugin store.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Navigate to WooCommerce -> Settings -> Payments (tab) in your WordPress dashboard.
5. Enable `Xendit – Payment Gateway` & click Manage.
5. Fill out all the required details including your Public & Secret API Key which can be found in your Xendit dashboard (https://dashboard.xendit.co/settings/developers#api-keys). You can toggle between Test or Live Environment, then save your changes.
6. Navigate back to the Payments (tab) page & toggle all the Xendit Payment Gateways that you want to enable.
7. You may also manually change each Payment Gateway's name & description (by clicking the Manage button) that will appear in your checkout page.
8. Adjust your settings and save. You can see the new payment options by viewing your WooCommerce checkout page while you have items in the cart.

== Changelog ==

= 2.24.1 =
- Increase QRIS limit to 5 million IDR

= 2.24.0 =
- Add GCash payment method for PHP currencies
- Add GrabPay payment method for PHP currencies

= 2.23.1 =
- Miscellaneous bug fixes

= 2.23.0 =
- Add BNI Syariah Payment method

= 2.22.0 =
- Enable credit card promotions during checkout - automatically applied
- Fix default status

= 2.21.0 =
- Add Card payment method for USD currencies
- Remove invoice settings
- Fix bug when sending reference id in getCustomerByReferenceId

= 2.20.0 =
- Add 7-Eleven payment method for PHP currencies
- Cleanup log

= 2.19.0 =
- Add PayMaya payment method for PHP currencies

= 2.18.4 =
- Bug fixes for Wordpress 5.7+
- Add Kredivo SOP in admin setting

= 2.18.3 =
- Update credit card logo for PH region

= 2.18.2 =
- Fix time out issue

= 2.18.1 =
- Fix CC JS for expiry date issue

= 2.18.0 =
- Customer object implementation

= 2.17.0 =
- Error message standardization
- Move Direct Debit to XenInvoice

= 2.16.2 =
- Bug fixes on getting invoice settings

= 2.16.1 =
- Improve API Key security on gateway settings
- Direct Debit bug fixes

= 2.16.0 =
- Add Shopeepay payment method

= 2.15.0 =
- Add metrics

= 2.14.0 =
- Support installment on credit card

= 2.13.0 =
- Add send environment information for debugging

= 2.12.0 =
* Add BPI payment method
* Callback changes

= 2.11.5 =
* Synchronize invoice payment method in order detail
* Reformat order notes

= 2.11.4 =
* Fix error not displayed on certain theme

= 2.11.3 =
* Miscellaneous bug fixes
* Update Email Notifications

= 2.11.2 =
* Fix error message when save settings

= 2.11.1 =
* Fix QRIS callback

= 2.11.0 =
* Add QRIS payment method

= 2.10.1 =
* Subscription bugfix

= 2.10.0 =
* New XenPlatform OnBehalfOf option

= 2.9.2 =
* Bugfix for unchanged payment status
* General housekeeping

= 2.9.1 =
* Add PHP currency for credit card payment method

= 2.9.0 =
* Add BRI direct debit
* Add saved card features

= 2.8.1 =
* Minor bug fixes on error handling

= 2.8.0 =
* Add indomaret payment method
* Move invoice creation hook
* Update outdated external links

= 2.7.2 =
* Fix CC order status update

= 2.7.1 =
* Fix redirect load
* Fix subscription timeout issue
* Fix form name

= 2.7.0 =
* add LINKAJA payment method
* Reconfigure Dynamic 3DS

= 2.6.1 =
* Improving experience of cards status update
* Improve item information handle

= 2.6.0 =
* add DANA payment method
* Fix bug

= 2.5.0 =
* Add new settings to determine invoice flow
* Handle redirect through order received page

= 2.4.2 =
* Change CC status update flow
* Refine card validation message in checkout page

= 2.4.1 =
* Check WC order status before processing callback

= 2.4.0 =
* Change OVO flow to reduce timeout case

= 2.3.2 =
* Add x-api-version header for OVO payment

= 2.3.1 =
* Fix external id format to follow API guidelines

= 2.3.0 =
* Change flow credit card charge

= 2.2.4 =
* Add more logs in the main checkout and callback process

= 2.2.3 =
* should_authenticate is more important than should_3ds

= 2.2.2 =
* Fix escape characters

= 2.2.1 =
* Miscellaneous bug fixes

= 2.2.0 =
* New cardless credit payment method - Kredivo

= 2.1.0 =
* Add dynamic 3DS feature
* Enhance subscription feature to make it usable for all merchant

= 2.0.0 =
* Merged credit card payment method - 1 plugin for all.

= 1.8.2 =
* Enhancement: Bypass minimum amount for OVO on development mode

= 1.8.1 =
* Enhancement: Use callback endpoint for ewallet completion process to make it more reliable

= 1.8.0 =
* New feature: Enable merchant to change payment description in checkout page

= 1.7.2 =
* Fix order cancellation that affect orders that are created not using xendit

= 1.7.1 =
* Remove individual enable checkmark on each payment method

= 1.7.0 =
* New feature: Enable merchant to change payment method name

= 1.6.1 =
* Bugfix faulty notification URL for new external id

= 1.6.0 =
* Add custom external id form
* Rearrange Xendit setting page

= 1.5.1 =
* Fix failed publish

= 1.5.0 =
* Add refund function

= 1.4.2 =
* Add custom expiry time in admin options

= 1.4.1 =
* Fix faulty amount validation

= 1.4.0 =
* Remove callback URL interface

= 1.3.0 =
* Add OVO payment method

= 1.2.6 =
* Add alert when changing API key

= 1.2.5 =
* Fix wrong enum names

= 1.2.3 =
* Improve logging

= 1.2.2 =
* Fix issue where notification is not processed correctly

= 1.2.1 =
* Fix callback issue

= 1.2.0 =
* Change creds field to password
* Add alfamart payment method

= 1.1.1 =
* Minor bugfix for API request

= 1.1.0 =
* Split VA payment method to individual bank
* No longer display account number info in checkout/order received page
* Now use redirect scheme with Xendit invoice UI

= 1.0.5 =
* Fix minor visual bug on Xendit icon

= 1.0.4 =
* Change plugin description

= 1.0.3 =
* Improve stability
* Add measurement

= 1.0.2 =
* Fixed amount calculation for checkout page

= 1.0.1 =
* Automatic order cancellation for expired Xendit invoice.
* Fixed bank order display

= 1.0 =
* Initial version.

== Upgrade Notice ==

= 1.0 =
Initial version.
