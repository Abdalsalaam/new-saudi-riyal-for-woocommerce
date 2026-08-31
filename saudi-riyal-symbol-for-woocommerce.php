<?php
/**
 * Plugin Name: Gulf currencies Symbols for WooCommerce - رمز الريال السعودي والدرهم الإماراتي
 * Plugin URI: https://wordpress.org/plugins/saudi-riyal-symbol-for-woocommerce
 * Description: Adds support for the new Saudi Riyal symbol, UAE Dirham and Omani Rial symbols, in WooCommerce.
 * Version: 2.3
 * Author: Abdalsalaam Halawa
 * Author URI: https://halawa.io
 * Text Domain: saudi-riyal-symbol-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.5
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 11.1
 *
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Saudi_Riyal_Symbol_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nsrwc_wc_plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

if (
	! in_array( $nsrwc_wc_plugin_path, wp_get_active_and_valid_plugins(), true )
	&& ! in_array( $nsrwc_wc_plugin_path, wp_get_active_network_plugins(), true )
) {
	return;
}

/**
 * Plugin version.
 */
const NSRWC_VERSION = '2.3';

/**
 * Gulf currencies configuration.
 *
 * `char`     The glyph we render for display. SAR uses U+20C1 SAUDI RIYAL SIGN, the
 *            codepoint officially assigned by Unicode. AED and OMR have no assigned
 *            codepoint yet, so they use Private Use Area codepoints that only resolve
 *            against the bundled webfont — which is exactly why they must never reach
 *            feeds, APIs or any other machine-readable output.
 * `png`      Raster fallback for email and PDF, where webfonts are unreliable.
 *
 * @since 2.3 `char` replaces the previous HTML-entity form; entities leaked verbatim
 *            into consumers that treat the symbol as plain text.
 */
const NSRWC_GULF_CURRENCIES = array(
	'SAR' => array(
		'char' => "\u{20C1}",
		'png'  => 'SAR.png',
	),
	'AED' => array(
		'char' => "\u{E001}",
		'png'  => 'AED.png',
	),
	'OMR' => array(
		'char' => "\u{E900}",
		'png'  => 'OMR.png',
	),
);

/**
 * Load plugin text domain for translations.
 *
 * @return void
 */
function nsrwc_load_textdomain() {
	load_plugin_textdomain(
		'saudi-riyal-symbol-for-woocommerce',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}

add_action( 'plugins_loaded', 'nsrwc_load_textdomain' );

/**
 * Get the current active Gulf currency code if applicable.
 *
 * Resolving the active currency is surprisingly expensive once a multi-currency
 * plugin is installed, and WooCommerce asks for the symbol once per rendered
 * price. The result is memoised per request; multi-currency plugins that switch
 * currency mid-request can clear it with `nsrwc_reset_currency_cache()`.
 *
 * @return string|false Currency code (SAR, AED, OMR) or false if not a Gulf currency.
 */
function nsrwc_get_current_gulf_currency() {
	if ( isset( $GLOBALS['nsrwc_currency_cache'] ) ) {
		return $GLOBALS['nsrwc_currency_cache'];
	}

	$resolved        = false;
	$gulf_currencies = array_keys( NSRWC_GULF_CURRENCIES );

	// Check default WooCommerce currency.
	$wc_currency = get_woocommerce_currency();
	if ( in_array( $wc_currency, $gulf_currencies, true ) ) {
		$resolved = $wc_currency;
	}

	// Support for WOOCS - WooCommerce Currency Switcher.
	if ( false === $resolved && class_exists( 'WOOCS' ) ) {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- Global owned by the WOOCS plugin.
		global $WOOCS;
		if ( $WOOCS && ! empty( $WOOCS->current_currency ) && in_array( $WOOCS->current_currency, $gulf_currencies, true ) ) {
			$resolved = $WOOCS->current_currency;
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	}

	// Support for Multi Currency for WooCommerce by VillaTheme.
	if ( false === $resolved && function_exists( 'wmc_get_current_currency' ) ) {
		$current = wmc_get_current_currency();
		if ( in_array( $current, $gulf_currencies, true ) ) {
			$resolved = $current;
		}
	}

	// Support for WooCommerce Multi-Currency.
	if ( false === $resolved && class_exists( 'WOOMC\\Model\\Currency' ) ) {
		$current_currency = apply_filters( 'woocommerce_currency', get_woocommerce_currency() );
		if ( in_array( $current_currency, $gulf_currencies, true ) ) {
			$resolved = $current_currency;
		}
	}

	$GLOBALS['nsrwc_currency_cache'] = $resolved;

	return $resolved;
}

/**
 * Clear the memoised active-currency lookup.
 *
 * Multi-currency plugins that switch currency part-way through a request should
 * call this so the next rendered price picks up the new currency.
 *
 * @since 2.3
 *
 * @return void
 */
function nsrwc_reset_currency_cache() {
	unset( $GLOBALS['nsrwc_currency_cache'] );
}

add_action( 'woocommerce_currency_changed', 'nsrwc_reset_currency_cache' );

/**
 * Check if the current currency is a supported Gulf currency.
 *
 * @return bool
 */
function nsrwc_is_gulf_currency() {
	return false !== nsrwc_get_current_gulf_currency();
}

/**
 * Whether the plugin's presentation assets apply to this request.
 *
 * The custom glyphs only resolve against the bundled webfont, so they must not be
 * emitted on requests where that stylesheet is never loaded — otherwise shoppers
 * get tofu boxes. Multi-currency stores whose base currency is not a Gulf currency
 * can force this on with the `nsrwc_load_assets` filter.
 *
 * @since 2.3
 *
 * @return bool
 */
function nsrwc_should_load_assets() {
	return (bool) apply_filters( 'nsrwc_load_assets', nsrwc_is_gulf_currency() );
}

/**
 * Whether the request is being served to a machine rather than a browser.
 *
 * REST (other than the Store API, which drives the Cart/Checkout blocks), WP-CLI,
 * cron, XML-RPC and feeds all produce output that some other system parses. The
 * custom glyphs are meaningless there — AED and OMR in particular are Private Use
 * Area codepoints — so those contexts always get WooCommerce's own symbol.
 *
 * @since 2.3
 *
 * @return bool
 */
function nsrwc_is_machine_context() {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}

	if ( function_exists( 'wp_doing_cron' ) ? wp_doing_cron() : ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		return true;
	}

	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		return true;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST && ! nsrwc_is_store_api_request() ) {
		return true;
	}

	if ( did_action( 'parse_query' ) && is_feed() ) {
		return true;
	}

	return false;
}

/**
 * Whether the current REST request targets the WooCommerce Store API.
 *
 * The Store API is the data source for the Cart and Checkout blocks, so its
 * responses are rendered to a shopper and should carry the glyph. Every other
 * REST namespace is treated as machine output.
 *
 * @since 2.3
 *
 * @return bool
 */
function nsrwc_is_store_api_request() {
	$route = '';

	if ( ! empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
		$route = (string) $GLOBALS['wp']->query_vars['rest_route'];
	} elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$route = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	}

	return false !== strpos( $route, '/wc/store/' );
}

/**
 * Whether the custom glyph should be rendered for this currency, right now.
 *
 * This is the single gate every symbol replacement passes through. Third-party
 * code can opt a context out entirely with the `nsrwc_render_glyph` filter.
 *
 * @since 2.3
 *
 * @param string $currency Currency code being rendered.
 *
 * @return bool
 */
function nsrwc_should_render_glyph( $currency ) {
	$render = true;

	if ( ! isset( NSRWC_GULF_CURRENCIES[ $currency ] ) ) {
		$render = false;
	} elseif ( ! nsrwc_should_load_assets() ) {
		// The webfont is not loaded on this request, so the glyph would be tofu.
		$render = false;
	} elseif ( nsrwc_is_machine_context() ) {
		$render = false;
	} elseif ( nsrwc_is_seo_or_llm_bot() ) {
		$render = false;
	}

	/**
	 * Filters whether the Gulf currency glyph is rendered.
	 *
	 * Return false to make the plugin fall back to WooCommerce's own symbol, for
	 * example inside a product feed, an export, or a payment gateway payload.
	 *
	 * @since 2.3
	 *
	 * @param bool   $render   Whether to render the glyph.
	 * @param string $currency Currency code.
	 */
	return (bool) apply_filters( 'nsrwc_render_glyph', $render, $currency );
}

/**
 * Enqueue front-end CSS if currency is a Gulf currency.
 *
 * @return void
 */
function nsrwc_enqueue_font_css() {
	if ( ! nsrwc_should_load_assets() ) {
		return;
	}

	wp_enqueue_style(
		'gulf-currencies-style',
		plugins_url( 'assets/css/style.css', __FILE__ ),
		array(),
		NSRWC_VERSION
	);
}

add_action( 'wp_enqueue_scripts', 'nsrwc_enqueue_font_css' );
add_action( 'admin_enqueue_scripts', 'nsrwc_enqueue_font_css' );

/**
 * Enqueue front-end JS to fix blocks based products price currency.
 *
 * @return void
 */
function nsrwc_enqueue_frontend_scripts() {
	if ( ! nsrwc_should_load_assets() ) {
		return;
	}

	$current_currency = nsrwc_get_current_gulf_currency();

	wp_enqueue_script(
		'gulf-currencies-blocks-fix',
		plugins_url( 'assets/js/gulf-currencies.js', __FILE__ ),
		array(),
		NSRWC_VERSION,
		array( 'in_footer' => true )
	);

	// Pass currency glyphs to JavaScript for detection.
	$currency_symbols = array_map(
		static function ( $config ) {
			return $config['char'];
		},
		NSRWC_GULF_CURRENCIES
	);

	wp_localize_script(
		'gulf-currencies-blocks-fix',
		'nsrwcGulfCurrencies',
		array(
			'symbols'         => $currency_symbols,
			'currentCurrency' => $current_currency,
		)
	);
}

add_action( 'wp_enqueue_scripts', 'nsrwc_enqueue_frontend_scripts' );
add_action( 'admin_enqueue_scripts', 'nsrwc_enqueue_frontend_scripts' );

/**
 * Flag the document so the stylesheet can scope the symbol spacing.
 *
 * @since 2.3
 *
 * @param array|string $classes Existing body classes.
 *
 * @return array|string
 */
function nsrwc_body_class( $classes ) {
	if ( ! nsrwc_should_load_assets() ) {
		return $classes;
	}

	// The position class lets the stylesheet put the spacing on the correct side
	// without guessing from the DOM: the amount is a bare text node, so CSS alone
	// cannot tell whether the symbol precedes or follows it.
	$position = get_option( 'woocommerce_currency_pos', 'left' );
	$added    = 'nsrwc-gulf-currency nsrwc-pos-' . sanitize_html_class( (string) $position );

	if ( is_array( $classes ) ) {
		foreach ( explode( ' ', $added ) as $class ) {
			$classes[] = $class;
		}
		return $classes;
	}

	return trim( $classes . ' ' . $added );
}

add_filter( 'body_class', 'nsrwc_body_class' );
add_filter( 'admin_body_class', 'nsrwc_body_class' );

/**
 * Adjust currency format for PDF contexts.
 *
 * @param string $format Price format string.
 *
 * @return string
 */
function nsrwc_wrap_currency_symbol( $format ) {
	if ( nsrwc_is_doing_pdf() && class_exists( 'WCPDF_Custom_PDF_Maker_mPDF' ) ) {
		return '%2$s&nbsp;%1$s';
	}

	return $format;
}

add_filter( 'woocommerce_price_format', 'nsrwc_wrap_currency_symbol', 9999, 1 );

/**
 * Replace Gulf currency symbols with custom font glyphs.
 *
 * Returns a plain string in every case — never HTML — outside of email and PDF
 * rendering, where a raster image is the only thing that renders reliably.
 *
 * @param string $currency_symbol Original currency symbol.
 * @param string $currency        Currency code.
 *
 * @return string
 */
function nsrwc_replace_gulf_currency_symbol( $currency_symbol, $currency ) {
	if ( ! nsrwc_should_render_glyph( $currency ) ) {
		return $currency_symbol;
	}

	$config = NSRWC_GULF_CURRENCIES[ $currency ];

	if ( nsrwc_is_doing_email() || nsrwc_is_doing_pdf() ) {
		// Plain-text email parts must never carry markup.
		if ( nsrwc_is_doing_plain_text_email() ) {
			return $currency_symbol;
		}

		return '<img src="' . esc_url( plugins_url( 'assets/icons/png/' . $config['png'], __FILE__ ) ) . '" alt="' . esc_attr( $currency ) . '" style="vertical-align: middle; margin: 0 !important; height: 1em; font-size: inherit !important;">';
	}

	return $config['char'];
}

add_filter( 'woocommerce_currency_symbol', 'nsrwc_replace_gulf_currency_symbol', 20, 2 );

/**
 * Replace Gulf currency symbols in the plural symbol array.
 *
 * Third-party plugins that read symbols via get_woocommerce_currency_symbols()
 * only trigger the plural `woocommerce_currency_symbols` filter, bypassing the
 * singular one above. Mirror the replacement here so those consumers also pick
 * up our custom glyphs.
 *
 * @param array $symbols Currency code => symbol map.
 *
 * @return array
 */
function nsrwc_replace_gulf_currency_symbols_array( $symbols ) {
	foreach ( array_keys( NSRWC_GULF_CURRENCIES ) as $code ) {
		if ( isset( $symbols[ $code ] ) ) {
			$symbols[ $code ] = nsrwc_replace_gulf_currency_symbol( $symbols[ $code ], $code );
		}
	}

	return $symbols;
}

add_filter( 'woocommerce_currency_symbols', 'nsrwc_replace_gulf_currency_symbols_array', 20, 1 );

/**
 * Add css style to emails.
 *
 * @param string $css CSS code.
 *
 * @return string
 */
function nsrwc_email_styles_filter( $css ) {
	// Fix emails amount direction.
	$css .= '
	.woocommerce-Price-amount {
		direction: ltr;
		display: inline-block;
	}
	';

	return $css;
}

add_filter( 'woocommerce_email_styles', 'nsrwc_email_styles_filter', 10, 1 );

/**
 * Declare WooCommerce features compatibility to hide warnings.
 *
 * @return void
 */
function nsrwc_declare_features_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__ );
	}
}

add_action( 'before_woocommerce_init', 'nsrwc_declare_features_compatibility', 10 );

/**
 * Track whether the email part currently rendering is the plain-text variant.
 *
 * @since 2.3
 *
 * @param mixed $order          Order object.
 * @param bool  $sent_to_admin  Whether the email goes to an admin.
 * @param bool  $plain_text     Whether this is the plain-text part.
 *
 * @return void
 */
function nsrwc_track_plain_text_email( $order = null, $sent_to_admin = false, $plain_text = false ) {
	$GLOBALS['nsrwc_plain_text_email'] = (bool) $plain_text;
}

add_action( 'woocommerce_email_order_details', 'nsrwc_track_plain_text_email', 1, 3 );

/**
 * Whether the email part currently rendering is plain text.
 *
 * @since 2.3
 *
 * @return bool
 */
function nsrwc_is_doing_plain_text_email() {
	return ! empty( $GLOBALS['nsrwc_plain_text_email'] );
}

/**
 * Check if it is an email process.
 *
 * Every check is scoped to an action that is currently executing. `did_action()`
 * must not be used here: it stays true for the rest of the request, which would
 * leak the raster image into every price rendered afterwards — including REST
 * responses and product feeds generated in the same request.
 *
 * @return bool
 */
function nsrwc_is_doing_email() {
	return (
		doing_action( 'woocommerce_email_header' ) ||
		doing_action( 'woocommerce_email_footer' ) ||
		doing_action( 'woocommerce_email_order_details' ) ||
		doing_action( 'woocommerce_email_order_meta' ) ||
		doing_action( 'woocommerce_email_attachments' ) ||
		doing_action( 'woocommerce_email_before_order_table' ) ||
		doing_action( 'woocommerce_email_after_order_table' ) ||
		doing_action( 'woocommerce_email_customer_details' ) ||
		doing_action( 'woocommerce_before_email_order' )
	);
}

/**
 * Check if it is a PDF file.
 *
 * @return bool
 */
function nsrwc_is_doing_pdf() {
	if ( ! wp_doing_ajax() || ! isset( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context probe.
		return false;
	}

	$action = sanitize_key( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context probe.

	return in_array( $action, array( 'generate_wpo_wcpdf', 'wpifw_generate_invoice' ), true );
}

/**
 * Detect SEO and LLM crawlers via the User-Agent header.
 *
 * The plugin's custom glyphs use new/PUA Unicode codepoints that crawlers
 * (Googlebot, GPTBot, ClaudeBot, etc.) cannot render in text extraction,
 * which breaks price parsing in structured data and page content. For these
 * bots we fall back to WooCommerce's default symbol. Result is cached in a
 * static so the User-Agent is parsed only once per request.
 *
 * @since 2.1
 *
 * @return bool
 */
function nsrwc_is_seo_or_llm_bot(): bool {
	static $is_bot = null;

	if ( null !== $is_bot ) {
		return $is_bot;
	}

	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$is_bot = false;
		return $is_bot;
	}

	$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );

	$bot_tokens = array(
		// SEO crawlers.
		'Googlebot',
		'Google-InspectionTool',
		'Storebot-Google',
		'AdsBot-Google',
		'Google-Shopping',
		'Googlebot-Image',
		'Bingbot',
		'AdIdxBot',
		'Slurp',
		'DuckDuckBot',
		'Baiduspider',
		'YandexBot',
		// Social / ad platform crawlers.
		'facebookexternalhit',
		'facebookcatalog',
		'meta-externalagent',
		'Twitterbot',
		'Pinterestbot',
		'TikTokSpider',
		'SnapchatAds',
		// LLM / AI crawlers.
		'GPTBot',
		'OAI-SearchBot',
		'ChatGPT-User',
		'ClaudeBot',
		'Claude-Web',
		'PerplexityBot',
		'Applebot',
		'Bytespider',
		'cohere-ai',
		'anthropic-ai',
	);

	$is_bot = false;
	foreach ( $bot_tokens as $token ) {
		if ( false !== stripos( $user_agent, $token ) ) {
			$is_bot = true;
			break;
		}
	}

	return $is_bot;
}

/**
 * Load admin notices and marketing class.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-nsrwc-admin-notices.php';
