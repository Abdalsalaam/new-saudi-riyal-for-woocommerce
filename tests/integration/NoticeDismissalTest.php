<?php
/**
 * Dismissing the admin notice over AJAX.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 */

namespace NSRWC\Tests;

use NSRWC_Admin_Notices;
use WPAjaxDieContinueException;
use WP_Ajax_UnitTestCase;

/**
 * Notice dismissal tests.
 *
 * @group ajax
 */
class NoticeDismissalTest extends WP_Ajax_UnitTestCase {

	private function dismiss(): array {
		$_POST['nonce'] = wp_create_nonce( 'nsrwc_dismiss_notice' );

		try {
			$this->_handleAjax( 'nsrwc_dismiss_notice' );
		} catch ( WPAjaxDieContinueException $e ) {
			// wp_send_json_* ends the request; the test framework turns that into this exception.
			unset( $e );
		}

		return (array) json_decode( $this->_last_response, true );
	}

	public function test_one_dismissal_hides_the_notice_for_good(): void {
		$this->_setRole( 'administrator' );

		$response = $this->dismiss();

		$this->assertTrue( $response['success'] );
		$this->assertNotEmpty( get_user_meta( get_current_user_id(), NSRWC_Admin_Notices::DISMISS_META_PREFIX . '_permanently_dismissed', true ) );
	}

	public function test_users_without_manage_options_cannot_dismiss(): void {
		$this->_setRole( 'subscriber' );

		$response = $this->dismiss();

		$this->assertFalse( $response['success'] );
	}

	public function test_a_bad_nonce_is_rejected(): void {
		$this->_setRole( 'administrator' );
		$_POST['nonce'] = 'nope';

		$this->expectException( 'WPAjaxDieStopException' );
		$this->_handleAjax( 'nsrwc_dismiss_notice' );
	}
}
