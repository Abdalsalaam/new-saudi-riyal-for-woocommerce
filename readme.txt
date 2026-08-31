=== Gulf currencies Symbols for WooCommerce - رمز الريال السعودي والدرهم الإماراتي ===
Author: abdalsalaam
Author URI: https://halawa.io
Tags: SAR, AED, OMR, symbol, saudi
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
WC tested up to: 11.1
Stable tag: 2.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adds support for the new Saudi Riyal symbol, UAE Dirham and Omani Rial symbols, in WooCommerce.

== Description ==
Adds support for the new Saudi Riyal symbol, UAE Dirham and Omani Rial symbols, in WooCommerce.

إضافة ووردبريس تضيف دعم رموز العملات الخليجية الجديدة في WooCommerce:
- **(SAR)** - رمز الريال السعودي
- **(AED)** - رمز الدرهم الإماراتي
- **(OMR)** - رمز الريال العماني

== Features ==
- Supports Saudi Riyal (SAR), UAE Dirham (AED), and Omani Rial (OMR) symbols.
- Displays the currency symbols on the front-end, admin dashboard, WooCommerce emails, and PDF invoices.
- Supports RTL environments by forcing the symbol to appear on the left.
- Supports block-based themes (Cart/Checkout blocks).
- Compatible with popular currency switcher plugins (WOOCS, Multi Currency for WooCommerce, and more).

== Compatible With ==
- WooCommerce emails
- PDF Invoices & Packing Slips for WooCommerce plugin
- Challan - PDF Invoice & Packing Slip for WooCommerce plugin
- WOOCS - WooCommerce Currency Switcher
- Multi Currency for WooCommerce (VillaTheme)
- WooCommerce Multi-Currency

== Changelog ==

= 2.3 =
- Fixed the currency symbol corrupting product feeds, REST API responses and other machine-readable output, and stopped overriding the store's "Currency position" setting (stores that chose "right" will now see the symbol move there).
- Fixed the email symbol image leaking into later prices, a possible crash with multi-currency plugins, empty boxes on non-Gulf stores, and other plugins no longer being able to override the symbol.

= 2.2 =
- WordPress 7.1 and WooCommerce 11.1 compatibility.

= 2.1 =
- WordPress 7.0 and WooCommerce 10.8 compatibility.
- Return the default WooCommerce currency symbol to SEO and LLM crawlers so prices stay machine-readable in structured data and AI search results.
- Improve compatibility with third-party plugins that read currency symbols via `get_woocommerce_currency_symbols()`.
- Ensure the admin dashboard currency font is applied even when third-party admin stylesheets re-declare `font-family` on the same element.

= 2.0 =
- Added support for UAE Dirham (AED) and Omani Rial (OMR) symbols.
- Better admin dashboard symbol rendering.

= 1.9 =
- WordPress 6.9 and WooCommerce 10.3 compatibility.

= 1.8 =
- Add support for multiple currency plugins (WOOCS, Multi Currency for WooCommerce, and other popular currency switchers).

= 1.7 =
- Add `Challan - PDF Invoice & Packing Slip for WooCommerce` compatibility.
- Fix currency symbol within email attached PDF invoice.

= 1.6 =
- Add PDF Invoices & Packing Slips for WooCommerce Compatibility.

= 1.5 =
- Fixed currency symbol display in RTL emails.

= 1.4 =
- Fix sale price currency symbol in blocks based themes.

= 1.3 =
- Fixed an issue where the currency symbol didn't update correctly when changing product quantities in Cart/Checkout blocks.
- Fix currency symbol in WooCommerce emails.

= 1.2 =
- For using "left with space" currency position.

= 1.1 =
- Fix replacing the symbol within the admin dashboard.
- Declare WooCommerce features compatibility to hide warnings.

= 1.0 =
- Initial release.

== Upgrade Notice ==

= 2.3 =
Fixes the currency symbol corrupting product feeds and REST API output. The store's own "Currency position" setting is now respected instead of being forced to "left with space".
