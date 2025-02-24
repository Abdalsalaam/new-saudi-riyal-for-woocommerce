<?php
/**
 * Plugin Name: New Saudi Riyal symbol for WooCommerce
 * Description: Ensure your store use the new Saudi Riyal symbol.
 * Version: 1.0
 * Author: Abdalsalaam Halawa
 * Author URI: https://profiles.wordpress.org/abdalsalaam/
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC tested up to: 9.7
 *
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
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
		$format = str_replace( '%1$s', '<span class="sar-currency-symbol">%1$s</span>', $format );
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
