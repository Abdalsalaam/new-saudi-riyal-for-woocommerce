<?php
/**
 * Symbol size and color settings: storage, sanitisation and accessors.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 */

namespace NSRWC\Tests;

use WC_Admin_Settings;
use WP_UnitTestCase;

/**
 * Settings tests.
 */
class SettingsTest extends WP_UnitTestCase {

	/**
	 * Start every test from a SAR store with default settings.
	 */
	public function set_up(): void {
		parent::set_up();
		update_option( 'woocommerce_currency', 'SAR' );
		delete_option( 'nsrwc_symbol_size' );
		delete_option( 'nsrwc_symbol_color' );
		nsrwc_reset_currency_cache();
	}

	/**
	 * Save both fields through WooCommerce's own settings saver, as the admin form does.
	 *
	 * @param mixed $size  Raw size input.
	 * @param mixed $color Raw color input.
	 */
	private function save_through_woocommerce( $size, $color ): void {
		$_POST = array(
			'nsrwc_symbol_size'  => $size,
			'nsrwc_symbol_color' => $color,
		);

		WC_Admin_Settings::save_fields( nsrwc_get_symbol_settings_fields(), $_POST );

		$_POST = array();
	}

	public function test_defaults_are_unset(): void {
		$this->assertNull( nsrwc_get_symbol_size() );
		$this->assertSame( '', nsrwc_get_symbol_color() );
	}

	/**
	 * Size inputs and what should be stored.
	 */
	public function size_inputs(): array {
		return array(
			'empty keeps default'      => array( '', '' ),
			'default size is default'  => array( '100', '' ),
			'valid value'              => array( '130', '130' ),
			'decimal is truncated'     => array( '87.6', '87' ),
			'below floor is clamped'   => array( '10', '50' ),
			'above ceiling is clamped' => array( '999', '200' ),
			'junk keeps default'       => array( 'big', '' ),
			'negative keeps default'   => array( '-5', '' ),
			'zero keeps default'       => array( '0', '' ),
		);
	}

	/**
	 * @dataProvider size_inputs
	 *
	 * @param string $input    Raw value from the form.
	 * @param string $expected Stored value.
	 */
	public function test_size_is_sanitised_when_saved( string $input, string $expected ): void {
		$this->save_through_woocommerce( $input, '' );

		$this->assertSame( $expected, (string) get_option( 'nsrwc_symbol_size' ) );
	}

	/**
	 * Color inputs and what should be stored.
	 */
	public function color_inputs(): array {
		return array(
			'empty'            => array( '', '' ),
			'six digit hex'    => array( '#1A7F37', '#1A7F37' ),
			'three digit hex'  => array( '#abc', '#abc' ),
			'missing hash'     => array( '1A7F37', '' ),
			'named color'      => array( 'green', '' ),
			'eight digit hex'  => array( '#1A7F37FF', '' ),
			'script injection' => array( '#fff;background:url(x)', '' ),
		);
	}

	/**
	 * @dataProvider color_inputs
	 *
	 * @param string $input    Raw value from the form.
	 * @param string $expected Stored value.
	 */
	public function test_color_is_sanitised_when_saved( string $input, string $expected ): void {
		$this->save_through_woocommerce( '', $input );

		$this->assertSame( $expected, get_option( 'nsrwc_symbol_color' ) );
	}

	public function test_accessors_read_saved_values(): void {
		$this->save_through_woocommerce( '130', '#1a7f37' );

		$this->assertSame( 130, nsrwc_get_symbol_size() );
		$this->assertSame( '#1a7f37', nsrwc_get_symbol_color() );
	}

	public function test_accessors_tolerate_values_written_outside_the_form(): void {
		update_option( 'nsrwc_symbol_size', '999' );
		update_option( 'nsrwc_symbol_color', 'red' );

		$this->assertSame( 200, nsrwc_get_symbol_size() );
		$this->assertSame( '', nsrwc_get_symbol_color() );
	}

	public function test_fields_are_added_after_currency_position_on_a_gulf_store(): void {
		$ids = array_column( $this->general_settings(), 'id' );

		$position = array_search( 'woocommerce_currency_pos', $ids, true );

		$this->assertNotFalse( $position );
		$this->assertSame( 'nsrwc_symbol_size', $ids[ $position + 1 ] );
		$this->assertSame( 'nsrwc_symbol_color', $ids[ $position + 2 ] );
	}

	public function test_insertion_survives_entries_without_an_id_and_gapped_keys(): void {
		add_filter( 'nsrwc_promote_mawsim', '__return_false' );

		$settings = array(
			3  => array( 'type' => 'title' ),
			7  => array( 'id' => 'woocommerce_currency' ),
			9  => array( 'id' => 'woocommerce_currency_pos' ),
			12 => array( 'id' => 'woocommerce_price_thousand_sep' ),
		);

		$ids = array_map(
			static function ( $setting ) {
				return isset( $setting['id'] ) ? $setting['id'] : '(no id)';
			},
			nsrwc_add_symbol_settings( $settings )
		);

		remove_filter( 'nsrwc_promote_mawsim', '__return_false' );

		$this->assertSame(
			array( '(no id)', 'woocommerce_currency', 'woocommerce_currency_pos', 'nsrwc_symbol_size', 'nsrwc_symbol_color', 'woocommerce_price_thousand_sep' ),
			array_values( $ids )
		);
	}

	public function test_fields_are_absent_on_a_non_gulf_store(): void {
		update_option( 'woocommerce_currency', 'USD' );
		nsrwc_reset_currency_cache();

		$ids = array_column( $this->general_settings(), 'id' );

		$this->assertNotContains( 'nsrwc_symbol_size', $ids );
		$this->assertNotContains( 'nsrwc_symbol_color', $ids );
	}

	public function test_fields_can_be_forced_on_through_the_assets_filter(): void {
		update_option( 'woocommerce_currency', 'USD' );
		nsrwc_reset_currency_cache();
		add_filter( 'nsrwc_load_assets', '__return_true' );

		$ids = array_column( $this->general_settings(), 'id' );

		remove_filter( 'nsrwc_load_assets', '__return_true' );

		$this->assertContains( 'nsrwc_symbol_size', $ids );
	}

	/**
	 * The General tab's default section, as WooCommerce builds it.
	 */
	private function general_settings(): array {
		$pages = \WC_Admin_Settings::get_settings_pages();

		foreach ( $pages as $page ) {
			if ( 'general' === $page->get_id() ) {
				return $page->get_settings_for_section( '' );
			}
		}

		$this->fail( 'General settings page not found.' );
	}
}
