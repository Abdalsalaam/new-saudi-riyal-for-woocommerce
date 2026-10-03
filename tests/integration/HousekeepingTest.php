<?php
/**
 * Plugin row link to the settings, and cleanup on uninstall.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 */

namespace NSRWC\Tests;

use WP_UnitTestCase;

/**
 * Housekeeping tests.
 */
class HousekeepingTest extends WP_UnitTestCase {

	public function test_plugin_row_links_to_the_settings_first(): void {
		$links = apply_filters( 'plugin_action_links_saudi-riyal-symbol-for-woocommerce/saudi-riyal-symbol-for-woocommerce.php', array( 'deactivate' => '<a href="#">Deactivate</a>' ) );

		$this->assertSame( 'settings', array_key_first( $links ) );
		$this->assertStringContainsString( 'page=wc-settings', $links['settings'] );
		$this->assertStringContainsString( 'tab=general#nsrwc_symbol_size', $links['settings'] );
		$this->assertArrayHasKey( 'deactivate', $links );
	}

	public function test_plugin_row_link_has_no_anchor_on_a_non_gulf_store(): void {
		update_option( 'woocommerce_currency', 'USD' );
		nsrwc_reset_currency_cache();

		$links = apply_filters( 'plugin_action_links_saudi-riyal-symbol-for-woocommerce/saudi-riyal-symbol-for-woocommerce.php', array() );

		update_option( 'woocommerce_currency', 'SAR' );
		nsrwc_reset_currency_cache();

		$this->assertStringContainsString( 'tab=general"', $links['settings'] );
		$this->assertStringNotContainsString( '#nsrwc_symbol_size', $links['settings'] );
	}

	public function test_uninstall_removes_the_options(): void {
		update_option( 'nsrwc_symbol_size', 130 );
		update_option( 'nsrwc_symbol_color', '#1a7f37' );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'saudi-riyal-symbol-for-woocommerce/saudi-riyal-symbol-for-woocommerce.php' );
		}
		require dirname( __DIR__, 2 ) . '/uninstall.php';

		$this->assertFalse( get_option( 'nsrwc_symbol_size' ) );
		$this->assertFalse( get_option( 'nsrwc_symbol_color' ) );
	}
}
