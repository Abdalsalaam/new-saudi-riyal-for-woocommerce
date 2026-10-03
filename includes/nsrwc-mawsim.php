<?php
/**
 * Mawsim, the developer's sales-goals plugin, introduced where it is relevant.
 *
 * Every store running this plugin sells in a Gulf currency, and the Gulf shopping
 * calendar (Ramadan, Eid, Founding Day, White Friday) is exactly what Mawsim ranks by
 * revenue. The mention lives next to the currency settings and in a one-time notice,
 * links to the in-admin install screen, and disappears once Mawsim is installed.
 * Hosts can switch it off with the `nsrwc_promote_mawsim` filter.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 * @since   2.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress.org slug of Mawsim.
 */
const NSRWC_MAWSIM_SLUG = 'mawsim';

/**
 * Whether Mawsim is present on this site, active or not.
 *
 * A file check rather than is_plugin_active(): an installed-but-inactive copy means
 * the merchant already decided, and the install link would be pointless anyway.
 *
 * @since 2.4
 *
 * @return bool
 */
function nsrwc_is_mawsim_installed(): bool {
	return file_exists( trailingslashit( WP_PLUGIN_DIR ) . NSRWC_MAWSIM_SLUG . '/mawsim.php' );
}

/**
 * Whether to mention Mawsim at all.
 *
 * @since 2.4
 *
 * @return bool
 */
function nsrwc_promote_mawsim(): bool {
	/**
	 * Filters whether the plugin mentions Mawsim in the admin.
	 *
	 * @since 2.4
	 *
	 * @param bool $promote False once Mawsim is installed.
	 */
	return (bool) apply_filters( 'nsrwc_promote_mawsim', ! nsrwc_is_mawsim_installed() );
}

/**
 * Where "Install Mawsim" should take the current user.
 *
 * Users who can install plugins get the plugin-information modal, which has the
 * Install button and never leaves wp-admin. Everyone else gets the WordPress.org page.
 *
 * @since 2.4
 *
 * @return array{url: string, in_admin: bool}
 */
function nsrwc_get_mawsim_install_link(): array {
	if ( current_user_can( 'install_plugins' ) ) {
		return array(
			'url'      => self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . NSRWC_MAWSIM_SLUG . '&TB_iframe=true&width=772&height=800' ),
			'in_admin' => true,
		);
	}

	return array(
		'url'      => 'https://wordpress.org/plugins/' . NSRWC_MAWSIM_SLUG . '/',
		'in_admin' => false,
	);
}

/**
 * Anchor markup for the install link.
 *
 * @since 2.4
 *
 * @param string $label Link text.
 * @param string $classes Extra CSS classes.
 *
 * @return string
 */
function nsrwc_get_mawsim_install_anchor( string $label, string $classes = '' ): string {
	$link = nsrwc_get_mawsim_install_link();

	if ( $link['in_admin'] ) {
		return '<a href="' . esc_url( $link['url'] ) . '" class="' . esc_attr( trim( 'thickbox open-plugin-details-modal ' . $classes ) ) . '">' . esc_html( $label ) . '</a>';
	}

	return '<a href="' . esc_url( $link['url'] ) . '" class="' . esc_attr( $classes ) . '" target="_blank" rel="noopener">' . esc_html( $label ) . '</a>';
}

/**
 * The settings row shown under the symbol settings.
 *
 * An `info` row has no option behind it, so WooCommerce renders it and never saves it.
 *
 * @since 2.4
 *
 * @return array
 */
function nsrwc_get_mawsim_settings_row(): array {
	return array(
		'type'  => 'info',
		'title' => __( 'Seasonal sales', 'saudi-riyal-symbol-for-woocommerce' ),
		'text'  => sprintf(
			/* translators: %s: "Install Mawsim" link */
			__( 'Mawsim, from the developer of this plugin, ranks upcoming occasions such as Ramadan, Eid and White Friday by what they earned your store before, and sets a sales goal to match. %s, free on WordPress.org.', 'saudi-riyal-symbol-for-woocommerce' ),
			nsrwc_get_mawsim_install_anchor( __( 'Install Mawsim', 'saudi-riyal-symbol-for-woocommerce' ) )
		),
	);
}

/**
 * Load Thickbox on the WooCommerce settings screen so the row's link opens the
 * plugin-information modal there too, even after the notice was dismissed.
 *
 * @since 2.4
 *
 * @return void
 */
function nsrwc_enqueue_mawsim_modal_assets() {
	$screen = get_current_screen();

	if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
		return;
	}

	if ( nsrwc_promote_mawsim() && nsrwc_should_load_assets() && current_user_can( 'install_plugins' ) ) {
		add_thickbox();
	}
}

add_action( 'admin_enqueue_scripts', 'nsrwc_enqueue_mawsim_modal_assets' );
