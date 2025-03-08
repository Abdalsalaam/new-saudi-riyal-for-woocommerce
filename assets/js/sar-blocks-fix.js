/**
 * Wraps currency symbols in a span for styling purposes.
 *
 * @param {(string|HTMLElement)} element Selector string or DOM element to process.
 */
document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	/**
	 * Wrap currency symbol in a span element.
	 *
	 * @param {HTMLElement} el The HTML element to process.
	 */
	function wrapCurrencySymbol( el ) {
		if ( el.querySelector( '.sar-currency-symbol' ) ) {
			return;
		}

		let text = el.textContent.trim();
		let match = text.match( /^(\D+)(.*)$/ );

		if ( match ) {
			let symbol = match[ 1 ];
			let rest = match[ 2 ];

			el.innerHTML = '<span class="sar-currency-symbol">' + symbol + '</span>' + rest;
		}
	}

	/**
	 * Observe DOM element for content changes safely.
	 *
	 * @param {HTMLElement} el The HTML element to observe.
	 */
	function observeElementChanges( el ) {
		let contentObserver = new MutationObserver( function() {
			if ( ! el.querySelector( '.sar-currency-symbol' ) ) {
				wrapCurrencySymbol( el );
			}
		} );

		contentObserver.observe( el, {
			characterData: true,
			childList: true,
			subtree: true
		} );
	}

	/**
	 * Initialize observer for dynamic DOM changes.
	 */
	let observer = new MutationObserver( function( mutations ) {
		mutations.forEach( function( mutation ) {
			mutation.addedNodes.forEach( function( node ) {
				if ( node.nodeType !== 1 ) {
					return;
				}

				[ 'wc-block-formatted-money-amount', 'wc-block-components-product-price__value', 'wc-block-components-product-price__regular' ].forEach( function( className ) {
					if ( node.classList.contains( className ) ) {
						wrapCurrencySymbol( node );
						observeElementChanges( node );
					}

					node.querySelectorAll( '.' + className ).forEach( function( childNode ) {
						wrapCurrencySymbol( childNode );
						observeElementChanges( childNode );
					} );
				} );
			} );
		} );
	} );

	observer.observe( document.body, {
		childList: true,
		subtree: true
	} );

	// Process existing elements on initial load.
	[ '.wc-block-formatted-money-amount', '.wc-block-components-product-price__value', '.wc-block-components-product-price__regular' ].forEach( function( selector ) {
		document.querySelectorAll( selector ).forEach( function( el ) {
			wrapCurrencySymbol( el );
			observeElementChanges( el );
		} );
	} );
} );
