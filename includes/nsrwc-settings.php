<?php
/**
 * Symbol size and color settings.
 *
 * Two fields added to WooCommerce > Settings > General, directly under "Currency
 * position", so the merchant configures the symbol where the rest of the currency
 * options already live. Both default to empty, which means "leave the symbol as the
 * theme renders it", so a store that never touches them behaves exactly as before.
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 * @since   2.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Smallest accepted symbol size, as a percentage of the price text.
 */
const NSRWC_SYMBOL_SIZE_MIN = 50;

/**
 * Largest accepted symbol size, as a percentage of the price text.
 */
const NSRWC_SYMBOL_SIZE_MAX = 200;

/**
 * The size that means "do nothing": the glyph renders at the price text size.
 */
const NSRWC_SYMBOL_SIZE_DEFAULT = 100;

/**
 * Field definitions, in WooCommerce settings API format.
 *
 * @since 2.4
 *
 * @return array[]
 */
function nsrwc_get_symbol_settings_fields(): array {
	return array(
		array(
			'id'                => 'nsrwc_symbol_size',
			'title'             => __( 'Currency symbol size', 'saudi-riyal-symbol-for-woocommerce' ),
			'desc'              => sprintf(
				/* translators: 1: minimum percentage, 2: maximum percentage */
				__( 'Size of the Gulf currency symbol as a percentage of the price text, from %1$d to %2$d. Leave empty to keep the default size.', 'saudi-riyal-symbol-for-woocommerce' ),
				NSRWC_SYMBOL_SIZE_MIN,
				NSRWC_SYMBOL_SIZE_MAX
			),
			'desc_tip'          => true,
			'type'              => 'number',
			'placeholder'       => (string) NSRWC_SYMBOL_SIZE_DEFAULT,
			'suffix'            => '%',
			'css'               => 'width: 80px;',
			'default'           => '',
			'custom_attributes' => array(
				'min'  => NSRWC_SYMBOL_SIZE_MIN,
				'max'  => NSRWC_SYMBOL_SIZE_MAX,
				'step' => 1,
			),
		),
		array(
			'id'       => 'nsrwc_symbol_color',
			'title'    => __( 'Currency symbol color', 'saudi-riyal-symbol-for-woocommerce' ),
			'desc'     => __( 'Color of the Gulf currency symbol on your store pages. Leave empty to match the price text. Emails and PDF invoices show the symbol as an image, so the color does not apply there. Pick a color that stays readable on your buttons too, since prices can appear inside them.', 'saudi-riyal-symbol-for-woocommerce' ),
			'desc_tip' => true,
			'type'     => 'color',
			'default'  => '',
		),
	);
}

/**
 * Insert the fields after "Currency position" on the General tab.
 *
 * Only for stores that actually render the glyph: on a USD store the fields would
 * configure something the shopper never sees.
 *
 * @since 2.4
 *
 * @param array $settings General tab settings.
 *
 * @return array
 */
function nsrwc_add_symbol_settings( $settings ) {
	if ( ! is_array( $settings ) || ! nsrwc_should_load_assets() ) {
		return $settings;
	}

	// array_splice() works on positions, not keys, and the keys WooCommerce builds
	// are not guaranteed to be sequential, so count the position by hand.
	$position = 0;

	foreach ( $settings as $setting ) {
		++$position;

		if ( isset( $setting['id'] ) && 'woocommerce_currency_pos' === $setting['id'] ) {
			$fields = nsrwc_get_symbol_settings_fields();

			if ( nsrwc_promote_mawsim() ) {
				$fields[] = nsrwc_get_mawsim_settings_row();
			}

			array_splice( $settings, $position, 0, $fields );
			break;
		}
	}

	return $settings;
}

add_filter( 'woocommerce_general_settings', 'nsrwc_add_symbol_settings' );

/**
 * Normalise a raw size value to a stored one.
 *
 * Empty, non-numeric, non-positive and default (100) inputs all store as an empty
 * string, so "no customisation" has exactly one representation. Anything else is an
 * integer clamped into the accepted range.
 *
 * @since 2.4
 *
 * @param mixed $value Raw value.
 *
 * @return int|string Integer size, or '' for the default.
 */
function nsrwc_sanitize_symbol_size( $value ) {
	if ( is_array( $value ) || ! is_numeric( $value ) ) {
		return '';
	}

	$size = (int) $value;

	if ( $size <= 0 ) {
		return '';
	}

	$size = max( NSRWC_SYMBOL_SIZE_MIN, min( NSRWC_SYMBOL_SIZE_MAX, $size ) );

	return NSRWC_SYMBOL_SIZE_DEFAULT === $size ? '' : $size;
}

add_filter( 'woocommerce_admin_settings_sanitize_option_nsrwc_symbol_size', 'nsrwc_sanitize_symbol_size' );

/**
 * Normalise a raw color value to a stored one.
 *
 * Only 3- or 6-digit hex colors are stored. The value ends up inside a CSS rule, so
 * anything else is dropped rather than escaped.
 *
 * @since 2.4
 *
 * @param mixed $value Raw value.
 *
 * @return string Hex color, or '' for none.
 */
function nsrwc_sanitize_symbol_color( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$color = sanitize_hex_color( trim( $value ) );

	return is_string( $color ) ? $color : '';
}

add_filter( 'woocommerce_admin_settings_sanitize_option_nsrwc_symbol_color', 'nsrwc_sanitize_symbol_color' );

/**
 * Configured symbol size.
 *
 * Re-sanitised on read so a value written outside the settings form (REST, WP-CLI,
 * a migration) cannot produce invalid CSS.
 *
 * @since 2.4
 *
 * @return int|null Percentage of the price text, or null for the default.
 */
function nsrwc_get_symbol_size() {
	$size = nsrwc_sanitize_symbol_size( get_option( 'nsrwc_symbol_size', '' ) );

	return '' === $size ? null : $size;
}

/**
 * Configured symbol color.
 *
 * @since 2.4
 *
 * @return string Hex color, or '' when the symbol should match the price text.
 */
function nsrwc_get_symbol_color(): string {
	return nsrwc_sanitize_symbol_color( get_option( 'nsrwc_symbol_color', '' ) );
}

/**
 * CSS that applies the configured size and color.
 *
 * Returns an empty string for default settings, so a store that never changed them
 * gets exactly the stylesheet it had before.
 *
 * Size uses the `size-adjust` descriptor on a second @font-face for the same family.
 * It matches the face in style.css on every descriptor that takes part in font
 * matching (family, weight, style, unicode-range), so browsers prefer the later one
 * and only the Gulf glyphs scale. Digits, the theme font and the line box they share
 * are untouched, which is what makes it work inside flat block-rendered prices too.
 * Browsers without `size-adjust` ignore it and show the default size.
 *
 * Color can only be applied to an element that holds nothing but the glyph:
 * WooCommerce's own symbol wrapper and the wrapper the plugin's script adds. The
 * parent-tagged `.gulf-currency` elements also hold the digits, so they stay out.
 *
 * @since 2.4
 *
 * @param int|null $size  Percentage of the price text, or null for the default.
 * @param string   $color Hex color, or '' for none.
 *
 * @return string
 */
function nsrwc_get_custom_symbol_css( $size, string $color ): string {
	$css = '';

	if ( is_int( $size ) && NSRWC_SYMBOL_SIZE_DEFAULT !== $size ) {
		$plugin_file = dirname( __DIR__ ) . '/saudi-riyal-symbol-for-woocommerce.php';
		$font        = static function ( string $extension ) use ( $plugin_file ): string {
			return esc_url( plugins_url( 'assets/fonts/gulf-currencies.' . $extension, $plugin_file ) );
		};

		// Keep in step with the @font-face in assets/css/style.css.
		$css .= "@font-face {\n"
			. "\tfont-family: 'gulf-currencies';\n"
			. "\tsrc: url('" . $font( 'woff' ) . "') format('woff'),\n"
			. "\t\turl('" . $font( 'ttf' ) . "') format('truetype'),\n"
			. "\t\turl('" . $font( 'svg' ) . "') format('svg');\n"
			. "\tfont-weight: normal;\n"
			. "\tfont-style: normal;\n"
			. "\tfont-display: block;\n"
			. "\tunicode-range: U+20C1, U+E001, U+E002, U+E900;\n"
			. "\tsize-adjust: " . $size . "%;\n"
			. "}\n";
	}

	if ( '' !== $color ) {
		$selectors = array( '.woocommerce-Price-currencySymbol', '.nsrwc-symbol', '.sar-currency-symbol' );

		$css .= implode( ",\n", $selectors ) . " {\n\tcolor: " . $color . ";\n}\n";

		// A struck-through regular price is usually muted by the theme; a brand-colored
		// glyph next to grey digits would look like a mistake, so it inherits there.
		$css .= implode(
			",\n",
			array_map(
				static function ( string $selector ): string {
					return 'del ' . $selector;
				},
				$selectors
			)
		) . " {\n\tcolor: inherit;\n}\n";
	}

	return $css;
}

/**
 * Attach the custom CSS to the plugin stylesheet on the front end.
 *
 * Admin screens are left alone: the settings describe how the storefront should
 * look, and a brand color would fight the admin color schemes and status badges.
 *
 * @since 2.4
 *
 * @return void
 */
function nsrwc_add_custom_symbol_css() {
	if ( is_admin() || ! wp_style_is( 'gulf-currencies-style', 'enqueued' ) ) {
		return;
	}

	$css = nsrwc_get_custom_symbol_css( nsrwc_get_symbol_size(), nsrwc_get_symbol_color() );

	if ( '' !== $css ) {
		wp_add_inline_style( 'gulf-currencies-style', $css );
	}
}

/**
 * Height of the symbol image used in HTML emails and PDF invoices.
 *
 * Those contexts never load the stylesheet, so the size setting is applied to the
 * image directly. Formatted without the server locale: a float cast under a
 * comma-decimal locale would produce "1,3em", which is not CSS.
 *
 * @since 2.4
 *
 * @return string CSS length, for example "1em" or "1.3em".
 */
function nsrwc_get_symbol_image_height(): string {
	$size = nsrwc_get_symbol_size();

	if ( null === $size ) {
		return '1em';
	}

	$em = number_format( $size / 100, 2, '.', '' );
	$em = rtrim( rtrim( $em, '0' ), '.' );

	return $em . 'em';
}
