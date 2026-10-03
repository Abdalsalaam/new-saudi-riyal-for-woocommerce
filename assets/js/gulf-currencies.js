/**
 * Gulf Currencies Symbol Fix for WooCommerce.
 *
 * WooCommerce's classic templates wrap the symbol in
 * .woocommerce-Price-currencySymbol, which the stylesheet targets directly.
 * The Cart/Checkout blocks and the React admin screens render a price as one
 * flat text node instead, so there is nothing to target. This script finds
 * those text nodes and gives the glyph an element of its own:
 *
 * 1. Preferred: wrap each glyph in <span class="nsrwc-symbol">. That span holds
 *    nothing but the glyph, so the merchant's size and color settings can be
 *    applied to it without touching the digits.
 *
 * 2. Fallback: tag the parent element with .gulf-currency (or
 *    .gulf-currency-dashboard in wp-admin) so at least the font applies. The
 *    parent usually holds the digits too, so its own resolved font stack is
 *    captured into a custom property first and the stylesheet prepends the
 *    glyph font to it; the @font-face unicode-range then limits the glyph font
 *    to the Gulf codepoints.
 *
 * Wrapping edits a DOM that a framework may own, and a framework that finds a
 * child it did not create can duplicate the price or throw. So wrapping is
 * only done when it is known to be safe:
 *
 * - The text node must be the only child of its element. React then updates
 *   the element through textContent, replacing our wrapper along with the
 *   text, and the observer simply wraps again.
 * - If the element is React-managed (it carries React's expando properties),
 *   its `children` prop must be a plain string or number. Anything else means
 *   React holds a reference to the Text node itself and would write into it
 *   or remove it. No props marker at all (React 16 and older) fails closed.
 * - The original Text node is never removed: it stays first in place, holding
 *   the text before the first glyph, so references other code keeps to it
 *   stay valid. If that node is later rewritten or removed by its owner, the
 *   wrapper nodes created from it are cleaned up and the new text is scanned
 *   again.
 */
( function() {
	'use strict';

	// Determine class based on context
	const isAdmin = document.body.classList.contains( 'wp-admin' );
	const currencyClass = isAdmin ? 'gulf-currency-dashboard' : 'gulf-currency';

	// Elements that already hold only the glyph; never wrap inside these.
	const WRAPPER_SELECTOR = '.nsrwc-symbol, .woocommerce-Price-currencySymbol, .sar-currency-symbol';

	// Elements whose text is data, not rendered content. A glyph inside a JSON
	// state block or a <textarea> is not a price on screen, and editing it would
	// corrupt what another script reads.
	const DATA_TAGS = { SCRIPT: true, STYLE: true, TEXTAREA: true, TITLE: true, NOSCRIPT: true, TEMPLATE: true, OPTION: true };

	// Build symbols array from localized data or fallbacks (SAR=U+20C1, AED=U+E001, OMR=U+E900)
	const symbols = ( typeof nsrwcGulfCurrencies !== 'undefined' && nsrwcGulfCurrencies.symbols )
		? Object.values( nsrwcGulfCurrencies.symbols )
		: [ '⃁', '', '' ];

	// Pre-compiled regex for O(1) symbol detection
	const symbolRegex = new RegExp( '[' + symbols.join( '' ) + ']' );

	// Same set with a capture group, so split() keeps the glyphs as odd-indexed parts.
	const symbolSplitRegex = new RegExp( '([' + symbols.join( '' ) + '])' );

	// Track processed elements to avoid duplicate work
	const processed = new WeakSet();

	// Text nodes we split, mapped to { value, created }: the text we left in the
	// node and the sibling nodes we inserted after it.
	const wrapped = new WeakMap();

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
	 * Whether React owns this element, and if so whether it renders a bare
	 * string into it. Returns null when React is not involved.
	 */
	function reactTextChild( el ) {
		let isReact = false;
		let props = null;
		const keys = Object.keys( el );

		for ( let i = 0, len = keys.length; i < len; i++ ) {
			const key = keys[ i ];
			if ( key.indexOf( '__reactProps$' ) === 0 ) {
				props = el[ key ];
				isReact = true;
			} else if ( key.indexOf( '__reactFiber$' ) === 0 || key.indexOf( '__reactInternalInstance$' ) === 0 ) {
				isReact = true;
			}
		}

		if ( ! isReact ) {
			return null;
		}

		if ( ! props || typeof props !== 'object' ) {
			return false;
		}

		const children = props.children;
		return typeof children === 'string' || typeof children === 'number';
	}

	/**
	 * Whether a glyph inside this text node may be wrapped in its own element.
	 */
	function canWrap( node ) {
		const parent = node.parentElement;

		if ( ! parent || parent.firstChild !== node || parent.lastChild !== node ) {
			return false;
		}

		// An HTML <span> inside SVG <text> (Analytics chart labels) does not render,
		// so the glyph would vanish. Those keep the fallback.
		if ( parent.namespaceURI !== 'http://www.w3.org/1999/xhtml' ) {
			return false;
		}

		if ( parent.closest && parent.closest( WRAPPER_SELECTOR ) ) {
			return false;
		}

		return reactTextChild( parent ) !== false;
	}

	/**
	 * Split the glyphs out of a text node into .nsrwc-symbol spans.
	 *
	 * The node itself keeps the leading text (possibly empty) and stays in place.
	 */
	function wrapGlyphs( node ) {
		const parent = node.parentElement;
		const parts = node.nodeValue.split( symbolSplitRegex );
		const created = [];

		node.nodeValue = parts[ 0 ];

		for ( let i = 1, len = parts.length; i < len; i++ ) {
			let piece;

			if ( i % 2 === 1 ) {
				piece = document.createElement( 'span' );
				piece.className = 'nsrwc-symbol';
				piece.setAttribute( 'translate', 'no' );
				piece.textContent = parts[ i ];
			} else if ( parts[ i ] !== '' ) {
				piece = document.createTextNode( parts[ i ] );
			} else {
				continue;
			}

			parent.appendChild( piece );
			created.push( piece );
		}

		wrapped.set( node, { value: parts[ 0 ], created: created } );
	}

	/**
	 * Remove the nodes created from a split text node.
	 */
	function unwrap( node ) {
		const record = wrapped.get( node );
		if ( ! record ) {
			return;
		}

		wrapped.delete( node );

		for ( let i = 0, len = record.created.length; i < len; i++ ) {
			const piece = record.created[ i ];
			if ( piece.parentNode ) {
				piece.parentNode.removeChild( piece );
			}
		}
	}

	/**
	 * Process a text node: wrap its glyphs, or mark its parent.
	 */
	function processTextNode( node ) {
		const owner = node.parentElement;

		if ( ! owner || DATA_TAGS[ owner.tagName ] ) {
			return;
		}

		const record = wrapped.get( node );

		if ( record ) {
			if ( node.nodeValue === record.value ) {
				// Our own edit, or nothing changed.
				return;
			}
			// The owner rewrote the text we split. Start over from the new text.
			unwrap( node );
		}

		if ( ! node.nodeValue || ! symbolRegex.test( node.nodeValue ) ) {
			return;
		}

		if ( canWrap( node ) ) {
			wrapGlyphs( node );
			return;
		}

		const parent = node.parentElement;
		if ( parent && ! ( parent.closest && parent.closest( WRAPPER_SELECTOR ) ) ) {
			markElement( parent );
		}
	}

	/**
	 * Scan all text nodes in a container.
	 */
	function scanContainer( root ) {
		const walker = document.createTreeWalker( root, NodeFilter.SHOW_TEXT );
		const nodes = [];
		let node;

		// Collect first: wrapping inserts nodes, and a live walk would revisit them.
		while ( ( node = walker.nextNode() ) ) {
			nodes.push( node );
		}

		for ( let i = 0, len = nodes.length; i < len; i++ ) {
			processTextNode( nodes[ i ] );
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
				if ( node.isConnected ) {
					processTextNode( node );
				}
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

			// A split text node removed by its owner takes our pieces with it.
			const removed = m.removedNodes;
			for ( let k = 0, kLen = removed.length; k < kLen; k++ ) {
				if ( removed[ k ].nodeType === 3 && wrapped.has( removed[ k ] ) ) {
					unwrap( removed[ k ] );
				}
			}

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
