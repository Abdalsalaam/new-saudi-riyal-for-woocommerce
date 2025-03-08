/**
 * Wraps currency symbols in a span for styling purposes.
 *
 * @param {(string|HTMLElement)} element Selector string or DOM element to process.
 */
document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	/**
	 * Wrap currency symbol in span with specified class.
	 *
	 * @param {(HTMLElement)} el HTMLElement.
	 */
	function wrapCurrencySymbol( el ) {
		if ( el.querySelector( '.sar-currency-symbol' ) ) {
			return; // Already wrapped, skip to avoid infinite loops.
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
	 * Observe changes within target elements safely without infinite loop.
	 *
	 * @param {HTMLElement} el
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

	// Observe DOM mutations to dynamically handle currency formatting.
	let observer = new MutationObserver( function( mutations ) {
		mutations.forEach( function( mutation ) {
			mutation.addedNodes.forEach( function( node ) {
				if ( 1 !== node.nodeType ) {
					return;
				}

				if ( node.classList.contains( 'wc-block-formatted-money-amount' ) ) {
					wrapCurrencySymbol( node );
					observeElementChanges( node );
				}

				node.querySelectorAll( '.wc-block-formatted-money-amount' ).forEach( function( childNode ) {
					wrapCurrencySymbol( childNode );
					observeElementChanges( childNode );
				} );
			} );
		} );
	} );

	observer.observe( document.body, {
		childList: true,
		subtree: true
	} );

	// Initial call to process existing elements on page load.
	document.querySelectorAll( '.wc-block-formatted-money-amount' ).forEach( function( el ) {
		wrapCurrencySymbol( el );
		observeElementChanges( el );
	} );
} );
