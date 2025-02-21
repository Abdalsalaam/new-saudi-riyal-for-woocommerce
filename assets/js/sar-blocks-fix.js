jQuery( document ).ready( function ( $ ) {
	function wrapCurrencySymbol( element ) {
		$( element ).each( function () {
			var text = $( this ).text().trim()
			// Match leading non-digit characters as the symbol
			var match = text.match( /^(\D+)(.*)$/ )
			if ( match ) {
				var symbol = match[1]
				var rest = match[2]
				$( this ).html( '<span class="sar-currency-symbol">' + symbol + '</span>' + rest )
			}
		} )
	}

	var observer = new MutationObserver( function ( mutations ) {
		mutations.forEach( function ( mutation ) {
			mutation.addedNodes.forEach( function ( node ) {
				// Process only element nodes
				if ( node.nodeType === 1 ) {
					var $node = $( node )
					// If the added node itself has the target class, wrap its currency symbol.
					if ( $node.hasClass( 'wc-block-formatted-money-amount' ) ) {
						wrapCurrencySymbol( $node )
					}

					// Also check for any descendants with the target class.
					$node.find( '.wc-block-formatted-money-amount' ).each( function () {
						wrapCurrencySymbol( this )
					} )
				}
			} )
		} )
	} )

	observer.observe( document.body, {
		childList: true,
		subtree: true
	} )
} )
