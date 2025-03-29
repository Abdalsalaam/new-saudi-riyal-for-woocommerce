<?php
/**
 * Plugin Name: Saudi Riyal Symbol for WooCommerce - رمز الريال السعودي
 * Plugin URI: https://wordpress.org/plugins/saudi-riyal-symbol-for-woocommerce
 * Description: Ensure your store use the new Saudi Riyal symbol.
 * Version: 1.6
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
 * Plugin version.
 */
const NSRWC_VERSION = '1.6';

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
		NSRWC_VERSION
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
		array(),
		NSRWC_VERSION,
		array( 'in_footer' => true, )
	);
}

add_action( 'wp_enqueue_scripts', 'nsrwc_enqueue_frontend_scripts' );

/**
 * Force currency position : "left with space".
 *
 * @param $option String Current position.
 *
 * @return string
 */
function nsrwc_woocommerce_currency_pos( $option ) {
	if ( 'SAR' === get_woocommerce_currency() ) {
		return 'left_space';
	}

	return $option;
}

add_filter( 'option_woocommerce_currency_pos', 'nsrwc_woocommerce_currency_pos', 9999 );

/**
 * Wrap currency symbol with a span.
 *
 * @param $format
 *
 * @return string
 */
function nsrwc_wrap_currency_symbol( $format ) {
	if ( nsrwc_is_doing_pdf() ) {
		return class_exists( 'WCPDF_Custom_PDF_Maker_mPDF' ) ? '%2$s&nbsp;%1$s' : $format;
	}

	if ( 'SAR' !== get_woocommerce_currency() || nsrwc_is_doing_email() ) {
		return $format;
	}

	return str_replace( '%1$s', '<span class="sar-currency-symbol">%1$s</span>', $format );
}

add_filter( 'woocommerce_price_format', 'nsrwc_wrap_currency_symbol', 9999, 1 );

/**
 * Replace SAR currency symbol.
 *
 * @param $currency_symbol
 * @param $currency
 *
 * @return string
 */
function nsrwc_replace_sar_currency_symbol( $currency_symbol, $currency ) {
	if ( 'SAR' !== $currency ) {
		return $currency_symbol;
	}

	if ( nsrwc_is_doing_email() || nsrwc_is_doing_pdf() ) {
		return '<img src="' . plugins_url( 'assets/saudi-riyal-font/Saudi_Riyal_Symbol-1.png', __FILE__ ) . '" alt="' . $currency . '" style="vertical-align: middle; margin: 0 !important; height: 1em; font-size: inherit !important;">';
	}

	return '&#xe900;';
}

add_filter( 'woocommerce_currency_symbol', 'nsrwc_replace_sar_currency_symbol', 10002, 2 );

/**
 * Add css style to emails.
 *
 * @param string $css CSS code.
 *
 * @return string
 */
function nsrwc_email_styles_filter( $css ) {
	// Fix emails amount direction.
	$css .= "
	.woocommerce-Price-amount {
		direction: ltr;
		display:inline-block;
	}
	";

	return $css;
}

add_filter( 'woocommerce_email_styles', 'nsrwc_email_styles_filter', 10, 1 );

/**
 * Declare WooCommerce features compatibility to hide warnings.
 *
 * @return void
 */
function nsrwc_declare_features_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
	}
}

add_action( 'before_woocommerce_init', 'nsrwc_declare_features_compatibility', 10 );

/**
 * Check if it is an email process.
 *
 * @return bool
 */
function nsrwc_is_doing_email() {
	if (
		doing_action( 'woocommerce_email_header' ) ||
		doing_action( 'woocommerce_email_footer' ) ||
		doing_action( 'woocommerce_email_order_details' ) ||
		doing_action( 'woocommerce_email_order_meta' ) ||
		did_action( 'woocommerce_before_email_order' )
	) {
		return true;
	}

	return false;
}

/**
 * Check if it is a PDF file.
 *
 * @return bool
 */
function nsrwc_is_doing_pdf() {
	if ( ! wp_doing_ajax() || ! isset( $_GET['action'] ) ) {
		return false;
	}

	if (
		'generate_wpo_wcpdf' === $_GET['action'] ||
		'wpifw_generate_invoice' === $_GET['action']
	) {
		return true;
	}

	return false;
}
