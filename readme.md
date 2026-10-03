![riyal-cover.png](riyal-cover.png)

# Gulf Currencies Symbols for WooCommerce
[WordPress.org repo](https://wordpress.org/plugins/saudi-riyal-symbol-for-woocommerce/)

## Description

### English

Adds support for the new Saudi Riyal (SAR), UAE Dirham (AED), and Omani Rial (OMR) symbols in WooCommerce.

This plugin replaces the default currency symbols with the official new symbols:
- **Saudi Riyal (SAR)** - New symbol as announced by the Saudi Central Bank (SAMA)
- **UAE Dirham (AED)** - Official Dirham symbol
- **Omani Rial (OMR)** - Official Rial symbol

For more details about the Saudi Riyal symbol, please refer to the [Saudi Central Bank announcement](https://www.sama.gov.sa/en-US/Currency/SRS/Pages/default.aspx).

### العربية

إضافة ووردبريس تضيف دعم رموز العملات الخليجية الجديدة في WooCommerce:
- **(SAR)** - رمز الريال السعودي
- **(AED)** - رمز الدرهم الإماراتي
- **(OMR)** - رمز الريال العماني

## Features
- Supports Saudi Riyal (SAR), UAE Dirham (AED), and Omani Rial (OMR) symbols.
- Displays the currency symbols on the front-end, admin dashboard, WooCommerce emails, and PDF invoices.
- Supports RTL environments, and respects the store's own WooCommerce "Currency position" setting.
- Supports block-based themes (Cart/Checkout blocks).
- Compatible with popular currency switcher plugins (WOOCS, Multi Currency for WooCommerce, and more).
- Symbol size and color settings under WooCommerce > Settings > General > Currency options.

## More from the developer
- [Mawsim - Sales Goals & Marketing Calendar for WooCommerce](https://wordpress.org/plugins/mawsim/): set a sales goal, see which upcoming occasions (Ramadan, Eid, Founding Day, White Friday) can get you there, and know when to start preparing. Free.

## Development

```bash
composer install && npm install
composer lint        # WordPress coding standards, PHP 7.4 compatibility
npm run test:js      # browser script, in jsdom
npx wp-env start     # WordPress + WooCommerce on http://localhost:8895
composer test        # PHPUnit against the running wp-env
```

## Compatible With
- WooCommerce emails
- PDF Invoices & Packing Slips for WooCommerce plugin
- Challan - PDF Invoice & Packing Slip for WooCommerce plugin
- WOOCS - WooCommerce Currency Switcher
- Multi Currency for WooCommerce (VillaTheme)
- WooCommerce Multi-Currency

## Changelog

### 2.4
- Added "Currency symbol size" and "Currency symbol color" settings under WooCommerce > Settings > General > Currency options. Size applies on store pages, emails and PDF invoices; color applies on store pages.
- The Cart and Checkout blocks now get the same size and color as the rest of the store.
- Added a "Settings" link on the Plugins screen. The plugin removes its options when deleted.
- The admin notice now introduces Mawsim, the developer's free sales-goals plugin, with a link to install it from the plugin details screen. It is shown once, only on the dashboard, Plugins and WooCommerce settings screens, and never once Mawsim is installed.

### 2.3
- Fixed the currency symbol corrupting product feeds, REST API responses and other machine-readable output, and stopped overriding the store's "Currency position" setting (stores that chose "right" will now see the symbol move there).
- Fixed the email symbol image leaking into later prices, a possible crash with multi-currency plugins, empty boxes on non-Gulf stores, and other plugins no longer being able to override the symbol.

### 2.2
- WordPress 7.1 and WooCommerce 11.1 compatibility.

### 2.1
- WordPress 7.0 and WooCommerce 10.8 compatibility.
- Return the default WooCommerce currency symbol to SEO and LLM crawlers so prices stay machine-readable in structured data and AI search results.
- Improve compatibility with third-party plugins that read currency symbols via `get_woocommerce_currency_symbols()`.
- Ensure the admin dashboard currency font is applied even when third-party admin stylesheets re-declare `font-family` on the same element.

### 2.0
- Added support for UAE Dirham (AED) and Omani Rial (OMR) symbols.
- Better admin dashboard symbol rendering.

### 1.9
- WordPress 6.9 and WooCommerce 10.3 compatibility.

### 1.8
- Add support for multiple currency plugins (WOOCS, Multi Currency for WooCommerce, and other popular currency switchers).

### 1.7
- Add `Challan - PDF Invoice & Packing Slip for WooCommerce` compatibility.
- Fix currency symbol within email attached PDF invoice.

### 1.6
- Add PDF Invoices & Packing Slips for WooCommerce Compatibility.

### 1.5
- Fixed currency symbol display in RTL emails.

### 1.4
- Fix sale price currency symbol in blocks based themes.

### 1.3
- Fixed an issue where the currency symbol didn't update correctly when changing product quantities in Cart/Checkout blocks.
- Fix currency symbol in WooCommerce emails.

### 1.2
- For using "left with space" currency position.

### 1.1
- Fix replacing the symbol within the admin dashboard.
- Declare WooCommerce features compatibility to hide warnings.

### 1.0
- Initial release.
