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
	 * Prefix for the per-user dismissal meta keys.
	 *
	 * Bumping this suffix shows a new notice once to every user, including those
	 * who had dismissed the previous one, without touching their old meta. Bump it
	 * only when the notice is about something new, never to repeat the same one.
	 *
	 * @since 2.3
	 *
	 * @var string
	 */
	const DISMISS_META_PREFIX = 'nsrwc_notice_2_4';

	/**
	 * Meta key holding the permanent dismissal flag.
	 *
	 * @since 2.3
	 *
	 * @return string
	 */
	private static function permanent_dismiss_key(): string {
		return self::DISMISS_META_PREFIX . '_permanently_dismissed';
	}

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

		if ( ! nsrwc_is_gulf_currency() || ! nsrwc_promote_mawsim() ) {
			return false;
		}

		if ( ! $this->is_relevant_screen() ) {
			return false;
		}

		$user_id = get_current_user_id();

		// One dismissal is final. The earlier support notice came back after a
		// week; a plugin recommendation that does that is a nag.
		return ! get_user_meta( $user_id, self::permanent_dismiss_key(), true );
	}

	/**
	 * Screens where a merchant is thinking about the store, not writing a post.
	 * Deliberately not every WooCommerce screen: orders and products are daily work.
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	private function is_relevant_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen ) {
			return false;
		}

		return in_array( $screen->id, array( 'dashboard', 'plugins', 'woocommerce_page_wc-settings' ), true );
	}

	/**
	 * Display the Mawsim notice, with the support link kept underneath.
	 *
	 * @return void
	 */
	public function display_support_notice(): void {
		if ( ! $this->can_show_notices() ) {
			return;
		}

		$allowed = array(
			'a' => array(
				'href'   => true,
				'class'  => true,
				'target' => true,
				'rel'    => true,
			),
		);

		?>
		<div class="notice notice-info is-dismissible nsrwc-admin-notice" data-notice="nsrwc-support-notice">
			<p>
				<strong><?php esc_html_e( 'Sales goals for the Gulf shopping calendar', 'saudi-riyal-symbol-for-woocommerce' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'Mawsim is a free plugin from the developer of this plugin. It sets a sales goal, ranks the upcoming occasions (Ramadan, Eid, Founding Day, White Friday) by what they earned your store before, and shows when to start preparing.', 'saudi-riyal-symbol-for-woocommerce' ); ?>
			</p>
			<p>
				<?php echo wp_kses( nsrwc_get_mawsim_install_anchor( __( 'Install Mawsim (free)', 'saudi-riyal-symbol-for-woocommerce' ), 'button button-primary' ), $allowed ); ?>
				<a href="https://mawsim.store/?utm_source=gulf-currencies-plugin&amp;utm_medium=admin-notice" target="_blank" rel="noopener" class="button button-secondary" style="margin-inline-start: 6px;">
					<?php esc_html_e( 'See how it works', 'saudi-riyal-symbol-for-woocommerce' ); ?>
				</a>
			</p>
			<p>
				<em>
					<?php
					printf(
						/* translators: %s: support link */
						esc_html__( 'Need help with the currency symbol? %s.', 'saudi-riyal-symbol-for-woocommerce' ),
						'<a href="https://wordpress.org/support/plugin/saudi-riyal-symbol-for-woocommerce/" target="_blank" rel="noopener">' . esc_html__( 'Get support', 'saudi-riyal-symbol-for-woocommerce' ) . '</a>'
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

		update_user_meta( get_current_user_id(), self::permanent_dismiss_key(), true );

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

		if ( get_user_meta( get_current_user_id(), self::permanent_dismiss_key(), true ) ) {
			return;
		}

		if ( ! $this->can_show_notices() ) {
			return;
		}

		// The install link opens the plugin-information modal, which needs Thickbox.
		if ( current_user_can( 'install_plugins' ) ) {
			add_thickbox();
		}

		wp_enqueue_script(
			'nsrwc-admin-notice',
			plugins_url( 'assets/js/admin-notice.js', __DIR__ ),
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
		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=general' );

		// The fields are only on the page for stores that render the glyph.
		if ( nsrwc_should_load_assets() ) {
			$settings_url .= '#nsrwc_symbol_size';
		}

		$custom_links = array(
			'settings' => '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'saudi-riyal-symbol-for-woocommerce' ) . '</a>',
			'support'  => '<a href="https://wordpress.org/support/plugin/saudi-riyal-symbol-for-woocommerce/" target="_blank">' . __( 'Get Support', 'saudi-riyal-symbol-for-woocommerce' ) . '</a>',
			'hire'     => '<a href="https://halawa.io" target="_blank" style="color:#00a32a;font-weight:bold;">' . __( 'Hire Developer', 'saudi-riyal-symbol-for-woocommerce' ) . '</a>',
		);

		return array_merge( $custom_links, $links );
	}
}

// Initialize the class.
new NSRWC_Admin_Notices();
