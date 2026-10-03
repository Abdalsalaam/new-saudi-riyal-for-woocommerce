<?php
/**
 * Remove the plugin's options when it is deleted through the Plugins screen.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 * @since   2.4
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'nsrwc_symbol_size' );
delete_option( 'nsrwc_symbol_color' );
