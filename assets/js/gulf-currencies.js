/**
 * Gulf Currencies Symbol Fix for WooCommerce.
 *
 * WooCommerce's classic templates wrap the symbol in
 * .woocommerce-Price-currencySymbol, which the stylesheet targets directly.
 * The Cart/Checkout blocks and the React admin screens render a price as one
 * flat text node instead, so there is nothing to target. This script finds
 * those nodes and tags their parent.
 *
 * Because that parent usually holds the digits too, tagging it must not throw
 * away the theme's font. The element's own resolved stack is captured into a
 * custom property first, and the stylesheet prepends the glyph font to it — so
 * only the Gulf codepoints (see the @font-face unicode-range) come from the
 * bundled font and everything else keeps rendering exactly as the theme meant.
 */
( function() {
	'use strict';

	// Determine class based on context
	const isAdmin = document.body.classList.contains( 'wp-admin' );
	const currencyClass = isAdmin ? 'gulf-currency-dashboard' : 'gulf-currency';

	// Build symbols array from localized data or fallbacks (SAR=U+20C1, AED=U+E001, OMR=U+E900)
	const symbols = ( typeof nsrwcGulfCurrencies !== 'undefined' && nsrwcGulfCurrencies.symbols )
		? Object.values( nsrwcGulfCurrencies.symbols )
		: [ '\u20C1', '\uE001', '\uE900' ];

	// Pre-compiled regex for O(1) symbol detection
	const symbolRegex = new RegExp( '[' + symbols.join( '' ) + ']' );

	// Track processed elements to avoid duplicate work
	const processed = new WeakSet();

	// Debounce state
	let pending = [];
	let scheduled = false;

	/**
	 * Mark element with currency class, preserving its inherited font stack.
	 */
	function markElement( el ) {
		if ( ! el || ! el.classList || processed.has( el ) ) {
			return;
		}

		// Capture the stack the element already resolves to, before our class
		// lands, so non-Gulf characters in the same element are untouched.
		try {
			const inherited = window.getComputedStyle( el ).fontFamily;
			if ( inherited && inherited.indexOf( 'gulf-currencies' ) === -1 ) {
				el.style.setProperty( '--nsrwc-font-stack', inherited );
			}
		} catch ( e ) {
			// getComputedStyle can throw on detached nodes; the stylesheet's
			// own fallback stack covers that case.
		}

		el.classList.add( currencyClass );
		processed.add( el );
	}

	/**
	 * Process a text node - mark its parent if it contains a symbol.
	 */
	function processTextNode( node ) {
		if ( node.nodeValue && symbolRegex.test( node.nodeValue ) ) {
			markElement( node.parentElement );
		}
	}

	/**
	 * Scan all text nodes in a container.
	 */
	function scanContainer( root ) {
		const walker = document.createTreeWalker( root, NodeFilter.SHOW_TEXT );
		let node;
		while ( ( node = walker.nextNode() ) ) {
			processTextNode( node );
		}
	}

	/**
	 * Process pending nodes in a batch.
	 */
	function flushPending() {
		scheduled = false;
		const nodes = pending;
		pending = [];

		for ( let i = 0, len = nodes.length; i < len; i++ ) {
			const node = nodes[ i ];
			if ( node.nodeType === 3 ) {
				processTextNode( node );
			} else if ( node.nodeType === 1 && node.isConnected ) {
				scanContainer( node );
			}
		}
	}

	/**
	 * Schedule pending nodes processing.
	 *
	 * requestAnimationFrame is throttled to a standstill in background tabs, so
	 * a timeout runs alongside it: whichever fires first does the work, and
	 * flushPending is idempotent.
	 */
	function schedulePending() {
		if ( scheduled ) {
			return;
		}
		scheduled = true;

		if ( typeof window.requestAnimationFrame === 'function' ) {
			window.requestAnimationFrame( flushPending );
		}
		window.setTimeout( flushPending, 50 );
	}

	/**
	 * MutationObserver callback.
	 */
	function onMutation( mutations ) {
		for ( let i = 0, len = mutations.length; i < len; i++ ) {
			const m = mutations[ i ];

			// Collect added nodes
			const added = m.addedNodes;
			for ( let j = 0, jLen = added.length; j < jLen; j++ ) {
				pending.push( added[ j ] );
			}

			// Handle character data changes immediately
			if ( m.type === 'characterData' ) {
				processTextNode( m.target );
			}
		}

		if ( pending.length ) {
			schedulePending();
		}
	}

	// Create observer
	const observer = new MutationObserver( onMutation );

	/**
	 * Initialize scanning and observing.
	 */
	function init() {
		scanContainer( document.body );
		observer.observe( document.body, {
			childList: true,
			subtree: true,
			characterData: true
		} );
	}

	// Run when ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
