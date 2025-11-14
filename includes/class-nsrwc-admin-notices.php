<?php
/**
 * Admin Notices and Marketing Class
 *
 * Handles all admin notices, plugin promotion, and developer branding.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NSRWC_Admin_Notices
 */
class NSRWC_Admin_Notices {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_support_notice' ) );
		add_action( 'wp_ajax_nsrwc_dismiss_notice', array( $this, 'dismiss_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) . '/saudi-riyal-symbol-for-woocommerce.php' ), array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Check if user can see notices.
	 *
	 * @return bool
	 */
	private function can_show_notices(): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! nsrwc_is_sar_currency() ) {
			return false;
		}

		$user_id = get_current_user_id();

		if ( get_user_meta( $user_id, 'nsrwc_notice_permanently_dismissed', true ) ) {
			return false;
		}

		$first_dismiss_time = get_user_meta( $user_id, 'nsrwc_notice_first_dismiss_time', true );
		if ( $first_dismiss_time ) {
			$days_since_dismiss = ( time() - $first_dismiss_time ) / DAY_IN_SECONDS;
			if ( $days_since_dismiss < 7 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Display support and information notice.
	 *
	 * @return void
	 */
	public function display_support_notice(): void {
		if ( ! $this->can_show_notices() ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible nsrwc-admin-notice" data-notice="nsrwc-support-notice">
			<p>
				<strong><?php esc_html_e( 'Saudi Riyal Symbol for WooCommerce', 'saudi-riyal-symbol-for-woocommerce' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'WooCommerce will not implement the new Saudi Riyal symbol soon. I will continue to maintain this plugin and fix any issues you encounter.', 'saudi-riyal-symbol-for-woocommerce' ); ?>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: contact link */
					esc_html__( 'If you experience any issues, please %s.', 'saudi-riyal-symbol-for-woocommerce' ),
					'<a href="https://wordpress.org/support/plugin/saudi-riyal-symbol-for-woocommerce/" target="_blank">' . esc_html__( 'get support', 'saudi-riyal-symbol-for-woocommerce' ) . '</a>'
				);
				?>
			</p>
			<p style="border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px;">
				<a href="https://wordpress.org/support/plugin/saudi-riyal-symbol-for-woocommerce/reviews/#new-post" target="_blank" class="button button-primary" style="margin-inline-end: 10px;">
					⭐ <?php esc_html_e( 'Leave a 5-Star Review', 'saudi-riyal-symbol-for-woocommerce' ); ?>
				</a>
				<a href="https://halawa.io" target="_blank" class="button button-secondary">
					💼 <?php esc_html_e( 'Hire Me for Custom Development', 'saudi-riyal-symbol-for-woocommerce' ); ?>
				</a>
			</p>
			<p style="margin-top: 10px;">
				<em>
					<?php
					printf(
						/* translators: %s: developer link */
						esc_html__( 'Developed by %s - Available for WordPress & WooCommerce custom development projects.', 'saudi-riyal-symbol-for-woocommerce' ),
						'<a href="https://halawa.io" target="_blank"><strong>Abdalsalaam Halawa</strong></a>'
					);
					?>
				</em>
			</p>
		</div>
		<?php
	}

	/**
	 * Handle AJAX request to dismiss notices.
	 *
	 * @return void
	 */
	public function dismiss_notice(): void {
		check_ajax_referer( 'nsrwc_dismiss_notice', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$user_id            = get_current_user_id();
		$first_dismiss_time = get_user_meta( $user_id, 'nsrwc_notice_first_dismiss_time', true );

		if ( ! $first_dismiss_time ) {
			update_user_meta( $user_id, 'nsrwc_notice_first_dismiss_time', time() );
		} else {
			update_user_meta( $user_id, 'nsrwc_notice_permanently_dismissed', true );
		}

		wp_send_json_success( array( 'message' => 'Notice dismissed' ) );
    }

	/**
	 * Enqueue admin scripts for notice dismissal.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), 'nsrwc_notice_permanently_dismissed', true ) ) {
			return;
		}

		if ( ! $this->can_show_notices() ) {
			return;
		}

		wp_enqueue_script(
			'nsrwc-admin-notice',
			plugins_url( 'assets/js/admin-notice.js', dirname( __FILE__ ) ),
			array( 'jquery' ),
			NSRWC_VERSION,
			true
		);

		wp_localize_script(
			'nsrwc-admin-notice',
			'nsrwcAdmin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'nsrwc_dismiss_notice' ),
			)
		);
	}

	/**
	 * Add custom action links to plugin page.
	 *
	 * @param array $links Existing links.
	 *
	 * @return array Modified links.
	 */
	public function add_plugin_action_links( array $links ): array {
		$custom_links = array(
			'support' => '<a href="https://wordpress.org/support/plugin/saudi-riyal-symbol-for-woocommerce/" target="_blank">' . __( 'Get Support', 'saudi-riyal-symbol-for-woocommerce' ) . '</a>',
			'hire'    => '<a href="https://halawa.io" target="_blank" style="color:#00a32a;font-weight:bold;">' . __( 'Hire Developer', 'saudi-riyal-symbol-for-woocommerce' ) . '</a>',
		);

		return array_merge( $custom_links, $links );
	}
}

// Initialize the class.
new NSRWC_Admin_Notices();
