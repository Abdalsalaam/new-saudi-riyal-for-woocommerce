<?php
/**
 * The symbol image used in HTML emails follows the size setting.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 */

namespace NSRWC\Tests;

use WP_UnitTestCase;

/**
 * Email symbol tests.
 */
class EmailSymbolTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		update_option( 'woocommerce_currency', 'SAR' );
		delete_option( 'nsrwc_symbol_size' );
		delete_option( 'nsrwc_symbol_color' );
		nsrwc_reset_currency_cache();
		unset( $GLOBALS['nsrwc_plain_text_email'] );
	}

	/**
	 * Resolve the symbol while an email template action is running, like a template does.
	 *
	 * Uses an email action WooCommerce core attaches nothing to, so no order object is
	 * needed. The plain-text flag is recorded the way the order-details action records it.
	 *
	 * @param bool $plain_text Whether to render the plain-text part.
	 */
	private function symbol_inside_email( bool $plain_text = false ): string {
		nsrwc_track_plain_text_email( null, false, $plain_text );

		$captured = '';
		$capture  = static function () use ( &$captured ) {
			$captured = get_woocommerce_currency_symbol( 'SAR' );
		};

		add_action( 'woocommerce_email_before_order_table', $capture, 20 );
		do_action( 'woocommerce_email_before_order_table', null, false, $plain_text, null );
		remove_action( 'woocommerce_email_before_order_table', $capture, 20 );

		return $captured;
	}

	public function test_default_image_height_is_one_em(): void {
		$symbol = $this->symbol_inside_email();

		$this->assertStringContainsString( '<img ', $symbol );
		$this->assertStringContainsString( 'height: 1em;', $symbol );
	}

	/**
	 * Sizes and the resulting em heights, with no trailing zeros and a dot separator.
	 */
	public function sizes(): array {
		return array(
			array( 130, '1.3em' ),
			array( 80, '0.8em' ),
			array( 125, '1.25em' ),
			array( 200, '2em' ),
		);
	}

	/**
	 * @dataProvider sizes
	 *
	 * @param int    $size     Stored size.
	 * @param string $expected CSS height.
	 */
	public function test_image_height_follows_the_size_setting( int $size, string $expected ): void {
		update_option( 'nsrwc_symbol_size', $size );

		$this->assertStringContainsString( 'height: ' . $expected . ';', $this->symbol_inside_email() );
	}

	public function test_image_height_ignores_the_server_locale(): void {
		update_option( 'nsrwc_symbol_size', 130 );

		$previous = setlocale( LC_NUMERIC, '0' );
		$set      = setlocale( LC_NUMERIC, 'de_DE.UTF-8', 'de_DE', 'fr_FR.UTF-8', 'fr_FR' );

		$symbol = $this->symbol_inside_email();

		setlocale( LC_NUMERIC, $previous );

		if ( false === $set ) {
			$this->markTestSkipped( 'No comma-decimal locale available in this container.' );
		}

		$this->assertStringContainsString( 'height: 1.3em;', $symbol );
	}

	public function test_color_never_reaches_the_email(): void {
		update_option( 'nsrwc_symbol_color', '#1a7f37' );

		$this->assertStringNotContainsString( '1a7f37', $this->symbol_inside_email() );
	}

	public function test_plain_text_email_still_gets_the_standard_symbol(): void {
		update_option( 'nsrwc_symbol_size', 130 );

		$symbol = $this->symbol_inside_email( true );

		$this->assertStringNotContainsString( '<img', $symbol );
		$this->assertStringNotContainsString( "\u{20C1}", $symbol );
	}
}
