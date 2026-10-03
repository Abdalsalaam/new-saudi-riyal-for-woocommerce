<?php
/**
 * The Mawsim announcement: the settings row and the admin notice.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 */

namespace NSRWC\Tests;

use NSRWC_Admin_Notices;
use WP_UnitTestCase;

/**
 * Mawsim promotion tests.
 */
class MawsimPromotionTest extends WP_UnitTestCase {

	/**
	 * Administrator who can install plugins.
	 *
	 * @var int
	 */
	private $admin;

	public function set_up(): void {
		parent::set_up();
		update_option( 'woocommerce_currency', 'SAR' );
		nsrwc_reset_currency_cache();
		$this->admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin );
		set_current_screen( 'dashboard' );
	}

	public function tear_down(): void {
		set_current_screen( 'front' );
		remove_all_filters( 'nsrwc_promote_mawsim' );
		parent::tear_down();
	}

	private function render_notice(): string {
		ob_start();
		( new NSRWC_Admin_Notices() )->display_support_notice();
		return (string) ob_get_clean();
	}

	private function settings_rows(): array {
		return nsrwc_add_symbol_settings( array( array( 'id' => 'woocommerce_currency_pos' ) ) );
	}

	public function test_settings_section_ends_with_a_mawsim_row_that_installs_in_place(): void {
		$rows = $this->settings_rows();
		$last = end( $rows );

		$this->assertSame( 'info', $last['type'] );
		$this->assertArrayNotHasKey( 'id', $last, 'an id would make WooCommerce save it as an option' );
		$this->assertStringContainsString( 'plugin-install.php?tab=plugin-information', $last['text'] );
		$this->assertStringContainsString( 'plugin=mawsim', $last['text'] );
		$this->assertStringContainsString( 'open-plugin-details-modal', $last['text'] );
		$this->assertStringContainsString( 'Mawsim', $last['text'] );
	}

	public function test_settings_row_is_absent_once_mawsim_is_installed(): void {
		add_filter( 'nsrwc_promote_mawsim', '__return_false' );

		$types = array_column( $this->settings_rows(), 'type' );

		$this->assertNotContains( 'info', $types );
	}

	public function test_notice_leads_with_mawsim_and_an_install_link(): void {
		$html = $this->render_notice();

		$this->assertStringContainsString( 'nsrwc-admin-notice', $html );
		$this->assertStringContainsString( 'Mawsim', $html );
		$this->assertStringContainsString( 'plugin-install.php?tab=plugin-information', $html );
		$this->assertStringContainsString( 'plugin=mawsim', $html );
		$this->assertStringContainsString( 'https://mawsim.store', $html );
		$this->assertStringContainsString( 'wordpress.org/support/plugin/saudi-riyal-symbol-for-woocommerce', $html, 'support link stays' );
	}

	public function test_notice_links_to_wordpress_org_for_users_who_cannot_install_plugins(): void {
		// Shop managers have manage_woocommerce but not install_plugins; give one manage_options to pass the gate.
		$manager = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		get_role( 'shop_manager' )->add_cap( 'manage_options' );
		wp_set_current_user( $manager );

		$html = $this->render_notice();

		get_role( 'shop_manager' )->remove_cap( 'manage_options' );

		$this->assertStringContainsString( 'https://wordpress.org/plugins/mawsim/', $html );
		$this->assertStringNotContainsString( 'plugin-install.php', $html );
	}

	public function test_notice_is_not_shown_once_mawsim_is_installed(): void {
		add_filter( 'nsrwc_promote_mawsim', '__return_false' );

		$this->assertSame( '', $this->render_notice() );
	}

	public function test_notice_is_limited_to_the_dashboard_plugins_and_woocommerce_screens(): void {
		set_current_screen( 'edit-post' );
		$this->assertSame( '', $this->render_notice() );

		set_current_screen( 'plugins' );
		$this->assertStringContainsString( 'Mawsim', $this->render_notice() );

		set_current_screen( 'woocommerce_page_wc-settings' );
		$this->assertStringContainsString( 'Mawsim', $this->render_notice() );
	}

	public function test_dismissal_meta_is_versioned_so_the_new_notice_shows_once_to_everyone(): void {
		$this->assertSame( 'nsrwc_notice_2_4', NSRWC_Admin_Notices::DISMISS_META_PREFIX );

		update_user_meta( $this->admin, 'nsrwc_notice_2_3_permanently_dismissed', true );
		$this->assertStringContainsString( 'Mawsim', $this->render_notice() );

		update_user_meta( $this->admin, 'nsrwc_notice_2_4_permanently_dismissed', true );
		$this->assertSame( '', $this->render_notice() );
	}

	public function test_settings_screen_loads_thickbox_for_the_install_modal(): void {
		$GLOBALS['wp_scripts'] = null;
		set_current_screen( 'woocommerce_page_wc-settings' );

		nsrwc_enqueue_mawsim_modal_assets();

		$this->assertTrue( wp_script_is( 'thickbox', 'enqueued' ) );
		$GLOBALS['wp_scripts'] = null;
	}

	public function test_other_screens_do_not_load_thickbox(): void {
		$GLOBALS['wp_scripts'] = null;
		set_current_screen( 'edit-post' );

		nsrwc_enqueue_mawsim_modal_assets();

		$this->assertFalse( wp_script_is( 'thickbox', 'enqueued' ) );
		$GLOBALS['wp_scripts'] = null;
	}

	public function test_mawsim_detection_looks_for_the_plugin_file(): void {
		$this->assertFalse( nsrwc_is_mawsim_installed(), 'wp-env has no Mawsim' );
		$this->assertTrue( nsrwc_promote_mawsim() );
	}
}
