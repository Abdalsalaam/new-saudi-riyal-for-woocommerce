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
	 * @param {(string|HTMLElement)} element Selector or HTMLElement.
	 */
	function wrapCurrencySymbol( element ) {
		let elements = ( typeof element === 'string' ) ? document.querySelectorAll( element ) : [ element ];

		elements.forEach( function( el ) {
			let text = el.textContent.trim();
			let match = text.match( /^(\D+)(.*)$/ );

			if ( match ) {
				let symbol = match[ 1 ];
				let rest = match[ 2 ];

				el.innerHTML = '<span class="sar-currency-symbol">' + symbol + '</span>' + rest;
			}
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
				}

				node.querySelectorAll( '.wc-block-formatted-money-amount' ).forEach( function( childNode ) {
					wrapCurrencySymbol( childNode );
				} );
			} );
		} );
	} );

	observer.observe( document.body, {
		childList: true,
		subtree: true
	} );

	// Initial call to process existing elements on page load.
	wrapCurrencySymbol( '.wc-block-formatted-money-amount' );
} );
