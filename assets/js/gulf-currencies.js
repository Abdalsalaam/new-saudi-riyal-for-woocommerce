/**
 * Gulf Currencies Symbol Fix for WooCommerce.
 *
 * Adds 'gulf-currency' class to the direct parent element of Gulf currency symbols
 * to enable proper font rendering.
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
	let rafId = null;

	/**
	 * Mark element with currency class.
	 */
	function markElement( el ) {
		if ( el && el.classList && ! processed.has( el ) ) {
			el.classList.add( currencyClass );
			processed.add( el );
		}
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
	 * Process pending nodes in batched animation frame.
	 */
	function flushPending() {
		rafId = null;
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
	 */
	function schedulePending() {
		if ( ! rafId ) {
			rafId = requestAnimationFrame( flushPending );
		}
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
