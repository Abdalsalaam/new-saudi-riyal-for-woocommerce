/**
 * Gulf Currencies Symbol Fix for WooCommerce.
 *
 * Adds 'gulf-currency' class to the direct parent element of Gulf currency symbols
 * to enable proper font rendering.
 */
document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	// Determine if we're in the admin dashboard
	var isAdmin = document.body.classList.contains( 'wp-admin' );
	var currencyClass = isAdmin ? 'gulf-currency-dashboard' : 'gulf-currency';

	// Get currency symbols from localized data.
	var gulfSymbols = [];
	if ( typeof nsrwcGulfCurrencies !== 'undefined' && nsrwcGulfCurrencies.symbols ) {
		gulfSymbols = Object.values( nsrwcGulfCurrencies.symbols );
	}

	// Fallback symbols if localized data not available.
	// SAR = U+20C1, AED = U+E001, OMR = U+E900
	if ( gulfSymbols.length === 0 ) {
		gulfSymbols = [ '\u20C1', '\uE001', '\uE900' ];
	}

	/**
	 * Check if text contains any Gulf currency symbol.
	 *
	 * @param {string} text Text to check.
	 * @return {boolean} True if contains a Gulf currency symbol.
	 */
	function containsGulfCurrencySymbol( text ) {
		return gulfSymbols.some( function( symbol ) {
			return text.indexOf( symbol ) !== -1;
		} );
	}

	/**
	 * Find and mark direct parents of text nodes containing Gulf currency symbols.
	 *
	 * @param {HTMLElement} container The container to scan.
	 */
	function findAndMarkCurrencySymbols( container ) {
		// Create a TreeWalker to find all text nodes
		var walker = document.createTreeWalker(
			container,
			NodeFilter.SHOW_TEXT,
			null,
			false
		);

		var textNode;
		while ( textNode = walker.nextNode() ) {
			if ( containsGulfCurrencySymbol( textNode.textContent ) ) {
				// Add class to the direct parent element of this text node
				var parent = textNode.parentElement;
				if ( parent && parent.classList && ! parent.classList.contains( currencyClass ) ) {
					parent.classList.add( currencyClass );
				}
			}
		}
	}

	/**
	 * Initialize observer for dynamic DOM changes.
	 */
	var observer = new MutationObserver( function( mutations ) {
		mutations.forEach( function( mutation ) {
			// Handle added nodes
			mutation.addedNodes.forEach( function( node ) {
				if ( node.nodeType === 3 ) {
					// Text node added directly
					if ( containsGulfCurrencySymbol( node.textContent ) ) {
						var parent = node.parentElement;
						if ( parent && parent.classList && ! parent.classList.contains( currencyClass ) ) {
							parent.classList.add( currencyClass );
						}
					}
				} else if ( node.nodeType === 1 ) {
					// Element node - scan its text nodes
					findAndMarkCurrencySymbols( node );
				}
			} );

			// Handle text content changes
			if ( mutation.type === 'characterData' ) {
				var textNode = mutation.target;
				if ( containsGulfCurrencySymbol( textNode.textContent ) ) {
					var parent = textNode.parentElement;
					if ( parent && parent.classList && ! parent.classList.contains( currencyClass ) ) {
						parent.classList.add( currencyClass );
					}
				}
			}
		} );
	} );

	observer.observe( document.body, {
		childList: true,
		subtree: true,
		characterData: true
	} );

	// Process all existing elements on initial load.
	findAndMarkCurrencySymbols( document.body );
} );
