<?php
/**
 * Confirms the test environment boots WooCommerce and the plugin together.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 */

namespace NSRWC\Tests;

use WP_UnitTestCase;

/**
 * Smoke test for the bootstrap.
 */
class BootstrapTest extends WP_UnitTestCase {

	/**
	 * Both plugins must be loaded, otherwise every other test is meaningless.
	 */
	public function test_plugin_and_woocommerce_are_loaded(): void {
		$this->assertTrue( class_exists( 'WooCommerce' ) );
		$this->assertTrue( function_exists( 'nsrwc_should_render_glyph' ) );
		$this->assertSame( NSRWC_VERSION, get_file_data( dirname( __DIR__, 2 ) . '/saudi-riyal-symbol-for-woocommerce.php', array( 'Version' => 'Version' ) )['Version'] );
	}

	/**
	 * A SAR store renders the glyph through wc_price() on a plain front-end request.
	 */
	public function test_sar_store_renders_glyph_in_wc_price(): void {
		update_option( 'woocommerce_currency', 'SAR' );
		nsrwc_reset_currency_cache();

		$this->assertStringContainsString( "\u{20C1}", wc_price( 10 ) );
	}
}
