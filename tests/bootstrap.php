<?php
/**
 * PHPUnit bootstrap.
 *
 * Boots the WordPress test library with WooCommerce and this plugin active, so the
 * tests exercise the real settings API, price formatting and asset pipeline. Intended
 * to run inside wp-env's tests container (`composer test`), which sets WP_TESTS_DIR.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 */

$nsrwc_plugin_dir = dirname( __DIR__ );

require_once $nsrwc_plugin_dir . '/vendor/autoload.php';

$nsrwc_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $nsrwc_tests_dir && defined( 'WP_PHPUNIT__DIR' ) ) {
	$nsrwc_tests_dir = WP_PHPUNIT__DIR;
}

if ( ! $nsrwc_tests_dir || ! file_exists( $nsrwc_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "WordPress test library not found. Run `wp-env start` and then `composer test`.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	exit( 1 );
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $nsrwc_plugin_dir . '/vendor/yoast/phpunit-polyfills' );
}

require_once $nsrwc_tests_dir . '/includes/functions.php';

/*
 * The plugin bails out unless WooCommerce is an active plugin, and the test library
 * installs a bare site. Answering the `active_plugins` option here makes WordPress
 * load both through its normal plugin loader, in the normal order.
 */
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array(
		'woocommerce/woocommerce.php',
		basename( $nsrwc_plugin_dir ) . '/saudi-riyal-symbol-for-woocommerce.php',
	),
);

require_once $nsrwc_tests_dir . '/includes/bootstrap.php';
