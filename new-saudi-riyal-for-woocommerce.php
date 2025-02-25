<?php
/**
 * Plugin Name: New Saudi Riyal symbol for WooCommerce
 * Description: Ensure your store use the new Saudi Riyal symbol.
 * Version: 1.1
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

$WC_plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

if (
	! in_array( $WC_plugin_path, wp_get_active_and_valid_plugins() )
	&& ! in_array( $WC_plugin_path, wp_get_active_network_plugins() )
) {
	return;
}

/**
 * Enqueue front-end CSS if currency is SAR.
 *
 * @return void
 */
function nsrwc_enqueue_font_css() {
	if ( 'SAR' !== get_woocommerce_currency() ) {
		return;
	}

	wp_enqueue_style(
		'sar-frontend-style',
		plugins_url( 'assets/saudi-riyal-font/style.css', __FILE__ ),
		array(),
		'1.0'
	);
}

add_action( 'wp_enqueue_scripts', 'nsrwc_enqueue_font_css' );
add_action( 'admin_enqueue_scripts', 'nsrwc_enqueue_font_css' );

/**
 * Enqueue front-end JS to fix blocks based products price currency.
 *
 * @return void
 */
function nsrwc_enqueue_frontend_scripts() {
	if ( 'SAR' !== get_woocommerce_currency() ) {
		return;
	}

	wp_enqueue_script(
		'sar-blocks-fix',
		plugins_url( 'assets/js/sar-blocks-fix.js', __FILE__ ),
		array( 'jquery' ),
		'1.0',
		true
	);
}

add_action( 'wp_enqueue_scripts', 'nsrwc_enqueue_frontend_scripts' );

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
		return '&#xe900;';
	}

	return $currency_symbol;
}

add_filter( 'woocommerce_currency_symbol', 'nsrwc_replace_sar_currency_symbol', 10, 2 );

/**
 * Declare WooCommerce features compatibility to hide warnings.
 *
 * @return void
 */
function nsrwc_declare_features_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}

add_action( 'before_woocommerce_init', 'nsrwc_declare_features_compatibility', 10 );
