<?php
/**
 * Plugin Name: New Saudi Riyal symbol for WooCommerce
 * Description: Ensure your store use the new Saudi Riyal symbol.
 * Version: 1.0
 * Author: Abdalsalaam Halawa
 * Author URI: https://profiles.wordpress.org/abdalsalaam/
 * Tested up to: 6.7
 * Requires PHP: 7.4
 *
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue front-end CSS if currency is SAR.
 *
 * @return void
 */
function nsrwc_enqueue_sar_frontend_css() {
	if ( 'SAR' !== get_woocommerce_currency() ) {
		return;
	}

	wp_enqueue_style(
		'sar-frontend-style',
		plugins_url( 'assets/saudi-riyal-font/style.css', __FILE__ ),
		array(),
		'1.0'
	);

	wp_enqueue_script(
		'sar-blocks-fix',
		plugins_url( 'assets/js/sar-blocks-fix.js', __FILE__ ),
		array( 'jquery' ),
		'1.0',
		true
	);
}

add_action( 'wp_enqueue_scripts', 'nsrwc_enqueue_sar_frontend_css' );

/**
 * Wrap currency symbol with a span.
 *
 * @param $format
 * @param $currency_pos
 *
 * @return string
 */
function nsrwc_wrap_currency_symbol( $format, $currency_pos ) {
	if ( 'SAR' === get_woocommerce_currency() ) {
		// Force left position regardless of WooCommerce settings
		return '<span class="sar-currency-symbol">%1$s</span> %2$s';
	}

	return $format;
}

add_filter( 'woocommerce_price_format', 'nsrwc_wrap_currency_symbol', 10, 2 );

/**
 * Replace SAR currency symbol.
 *
 * @param $currency_symbol
 * @param $currency
 *
 * @return string
 */
function nsrwc_replace_sar_currency_symbol( $currency_symbol, $currency ) {
	if ( 'SAR' === $currency ) {
		return '';
	}

	return $currency_symbol;
}

add_filter( 'woocommerce_currency_symbol', 'nsrwc_replace_sar_currency_symbol', 10, 2 );

/**
 * Force left position for SAR currency
 */

/**
 * Enqueue admin CSS if currency is SAR.
 *
 * @return void
 */
function nsrwc_enqueue_sar_admin_css() {
    if ( 'SAR' !== get_woocommerce_currency() ) {
        return;
    }

    wp_enqueue_style(
        'sar-admin-style',
        plugins_url( 'assets/saudi-riyal-font/style.css', __FILE__ ),
        array(),
        '1.0'
    );

}

add_action( 'admin_enqueue_scripts', 'nsrwc_enqueue_sar_admin_css' );

/**
 * Add admin-specific inline CSS for SAR symbol
 */
function nsrwc_add_admin_inline_css() {
    if ( 'SAR' !== get_woocommerce_currency() ) {
        return;
    }

    $css = "
    .woocommerce-Price-currencySymbol,
    .wc_payment_method .amount,
    .woocommerce-table--order-details tfoot tr td,
    .woocommerce-table--order-details tbody tr td {
        font-family: 'saudi-riyal-font' !important;
    }
    ";
    
    wp_add_inline_style( 'sar-admin-style', $css );
}
add_action( 'admin_enqueue_scripts', 'nsrwc_add_admin_inline_css' );

/**
 * Apply SAR symbol in admin order list
 */
function nsrwc_admin_order_amount_symbol( $formatted_total, $order ) {
    if ( is_admin() && 'SAR' === get_woocommerce_currency() ) {
        return '<span class="sar-currency-symbol">' . $formatted_total . '</span>';
    }
    return $formatted_total;
}
add_filter( 'woocommerce_admin_order_total', 'nsrwc_admin_order_amount_symbol', 10, 2 );
