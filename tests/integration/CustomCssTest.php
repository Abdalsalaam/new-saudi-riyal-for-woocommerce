<?php
/**
 * The CSS generated from the size and color settings, and where it is attached.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 */

namespace NSRWC\Tests;

use WP_UnitTestCase;

/**
 * Custom CSS tests.
 */
class CustomCssTest extends WP_UnitTestCase {

	/**
	 * Start from a SAR store with default settings and a clean styles registry.
	 */
	public function set_up(): void {
		parent::set_up();
		update_option( 'woocommerce_currency', 'SAR' );
		delete_option( 'nsrwc_symbol_size' );
		delete_option( 'nsrwc_symbol_color' );
		nsrwc_reset_currency_cache();
		$GLOBALS['wp_styles'] = null;
	}

	public function tear_down(): void {
		$GLOBALS['wp_styles'] = null;
		set_current_screen( 'front' );
		parent::tear_down();
	}

	public function test_no_css_for_default_settings(): void {
		$this->assertSame( '', nsrwc_get_custom_symbol_css( null, '' ) );
	}

	public function test_size_emits_a_font_face_override_that_mirrors_the_stylesheet(): void {
		$css = nsrwc_get_custom_symbol_css( 130, '' );

		$this->assertStringContainsString( '@font-face', $css );
		$this->assertStringContainsString( "font-family: 'gulf-currencies'", $css );
		$this->assertStringContainsString( 'size-adjust: 130%', $css );
		$this->assertStringContainsString( 'font-display: block', $css );
		$this->assertStringNotContainsString( 'color:', $css );

		// The override must match the face in style.css exactly on the descriptors
		// that take part in font matching, otherwise it would stop being an override.
		$stylesheet = file_get_contents( dirname( __DIR__, 2 ) . '/assets/css/style.css' );
		preg_match( '/unicode-range:\s*([^;]+);/', $stylesheet, $match );
		$this->assertNotEmpty( $match, 'style.css lost its unicode-range' );
		$this->assertStringContainsString( 'unicode-range: ' . trim( $match[1] ) . ';', $css );
	}

	public function test_font_face_override_uses_absolute_font_urls(): void {
		$css = nsrwc_get_custom_symbol_css( 80, '' );

		$this->assertStringContainsString( plugins_url( 'assets/fonts/gulf-currencies.woff', dirname( __DIR__, 2 ) . '/saudi-riyal-symbol-for-woocommerce.php' ), $css );
		$this->assertStringNotContainsString( '../fonts/', $css );
	}

	public function test_color_emits_a_rule_for_glyph_wrappers_only(): void {
		$css = nsrwc_get_custom_symbol_css( null, '#1a7f37' );

		$this->assertStringNotContainsString( '@font-face', $css );
		$this->assertMatchesRegularExpression( '/\.woocommerce-Price-currencySymbol[^{]*\.nsrwc-symbol[^{]*\{[^}]*color: #1a7f37;/s', $css );
		// Parent-tagged block prices hold the digits too, so they must not be recolored.
		$this->assertStringNotContainsString( '.gulf-currency', $css );
	}

	public function test_color_is_muted_inside_struck_through_prices(): void {
		$css = nsrwc_get_custom_symbol_css( null, '#1a7f37' );

		$this->assertMatchesRegularExpression( '/del \.woocommerce-Price-currencySymbol[^{]*\{[^}]*color: inherit;/s', $css );
	}

	public function test_both_settings_combine(): void {
		$css = nsrwc_get_custom_symbol_css( 150, '#abc' );

		$this->assertStringContainsString( 'size-adjust: 150%', $css );
		$this->assertStringContainsString( 'color: #abc;', $css );
	}

	public function test_inline_css_is_attached_to_the_stylesheet_on_the_front_end(): void {
		update_option( 'nsrwc_symbol_size', 130 );
		update_option( 'nsrwc_symbol_color', '#1a7f37' );

		nsrwc_enqueue_font_css();

		$inline = implode( "\n", (array) wp_styles()->get_data( 'gulf-currencies-style', 'after' ) );
		$this->assertStringContainsString( 'size-adjust: 130%', $inline );
		$this->assertStringContainsString( '#1a7f37', $inline );
	}

	public function test_no_inline_css_for_default_settings(): void {
		nsrwc_enqueue_font_css();

		$this->assertTrue( wp_style_is( 'gulf-currencies-style', 'enqueued' ) );
		$this->assertFalse( wp_styles()->get_data( 'gulf-currencies-style', 'after' ) );
	}

	public function test_admin_screens_keep_the_default_rendering(): void {
		update_option( 'nsrwc_symbol_size', 130 );
		update_option( 'nsrwc_symbol_color', '#1a7f37' );
		set_current_screen( 'dashboard' );

		nsrwc_enqueue_font_css();

		$this->assertTrue( is_admin() );
		$this->assertTrue( wp_style_is( 'gulf-currencies-style', 'enqueued' ) );
		$this->assertFalse( wp_styles()->get_data( 'gulf-currencies-style', 'after' ) );
	}

	public function test_nothing_is_enqueued_on_a_non_gulf_store(): void {
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'nsrwc_symbol_color', '#1a7f37' );
		nsrwc_reset_currency_cache();

		nsrwc_enqueue_font_css();

		$this->assertFalse( wp_style_is( 'gulf-currencies-style', 'enqueued' ) );
	}
}
