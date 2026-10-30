<?php
/**
 * REST requests from a logged-in browser session keep the glyph; other REST output does not.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 */

namespace NSRWC\Tests;

use WP_UnitTestCase;

/**
 * REST context tests.
 */
class RestContextTest extends WP_UnitTestCase {

	/**
	 * Run count of the authentication filter before the test touched it.
	 *
	 * @var int|null
	 */
	private $auth_filter_count;

	public function set_up(): void {
		parent::set_up();
		update_option( 'woocommerce_currency', 'SAR' );
		nsrwc_reset_currency_cache();

		$this->auth_filter_count = $GLOBALS['wp_filters']['rest_authentication_errors'] ?? null;
		unset( $GLOBALS['wp_filters']['rest_authentication_errors'], $GLOBALS['wp_rest_auth_cookie'] );
	}

	public function tear_down(): void {
		if ( null === $this->auth_filter_count ) {
			unset( $GLOBALS['wp_filters']['rest_authentication_errors'] );
		} else {
			$GLOBALS['wp_filters']['rest_authentication_errors'] = $this->auth_filter_count;
		}
		unset( $GLOBALS['wp_rest_auth_cookie'], $GLOBALS['wp_rest_server'], $_SERVER['HTTP_X_WP_NONCE'] );
		parent::tear_down();
	}

	/**
	 * Run core's REST authentication for a logged-in user, as a request would.
	 *
	 * @param mixed $cookie_status Value core stores in `$wp_rest_auth_cookie`.
	 * @param bool  $send_nonce    Whether the request carries a `wp_rest` nonce.
	 */
	private function authenticate( $cookie_status, bool $send_nonce ): void {
		// Core sends a refreshed nonce header on success; the spy server records it instead.
		$GLOBALS['wp_rest_server']      = new \Spy_REST_Server();
		$GLOBALS['wp_rest_auth_cookie'] = $cookie_status;
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		unset( $_SERVER['HTTP_X_WP_NONCE'], $_REQUEST['_wpnonce'] );

		if ( $send_nonce ) {
			$_SERVER['HTTP_X_WP_NONCE'] = wp_create_nonce( 'wp_rest' );
		}

		apply_filters( 'rest_authentication_errors', null );
	}

	public function test_cookie_with_valid_nonce_is_a_browser_request(): void {
		$this->authenticate( true, true );

		$this->assertTrue( nsrwc_is_browser_rest_request() );
	}

	public function test_cookie_without_nonce_is_not_a_browser_request(): void {
		// Core logs the user out of a cookie request that has no nonce.
		$this->authenticate( true, false );

		$this->assertFalse( is_user_logged_in() );
		$this->assertFalse( nsrwc_is_browser_rest_request() );
	}

	public function test_other_authentication_is_not_a_browser_request(): void {
		// Application passwords and API keys log a user in without the auth cookie.
		$this->authenticate( null, false );

		$this->assertTrue( is_user_logged_in() );
		$this->assertFalse( nsrwc_is_browser_rest_request() );
	}

	public function test_cookie_is_not_trusted_before_authentication_runs(): void {
		$GLOBALS['wp_rest_auth_cookie'] = true;
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$this->assertFalse( nsrwc_is_browser_rest_request() );
	}

	/**
	 * REST_REQUEST is a constant, so this runs in its own process.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_rest_symbol_depends_on_the_session(): void {
		define( 'REST_REQUEST', true );
		$_SERVER['REQUEST_URI'] = '/wp-json/store-credits/v1/customers/status';

		$this->authenticate( true, true );
		$this->assertSame( "\u{20C1}", get_woocommerce_currency_symbol( 'SAR' ) );

		$this->authenticate( null, false );
		$this->assertNotSame( "\u{20C1}", get_woocommerce_currency_symbol( 'SAR' ) );
	}
}
