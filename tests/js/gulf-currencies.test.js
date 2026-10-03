/**
 * Behaviour of assets/js/gulf-currencies.js in a DOM.
 *
 * The script's job is to find Gulf glyphs in prices that WooCommerce did not wrap
 * (Cart/Checkout blocks, React admin) and give them an element the stylesheet can
 * target. The hard constraint is that it must never fight the framework that owns
 * that DOM: a mutation React or Vue does not expect is a duplicated or vanished price.
 */
const test = require( 'node:test' );
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { JSDOM } = require( 'jsdom' );

const SAR = '⃁';
const script = fs.readFileSync( path.join( __dirname, '../../assets/js/gulf-currencies.js' ), 'utf8' );

/**
 * Boot a document with the script running against it.
 *
 * @param {string} body    Body markup.
 * @param {string} [bodyClass] Body class, "wp-admin" for the admin variant.
 */
function boot( body, bodyClass = '' ) {
	const dom = new JSDOM( `<!doctype html><html><body class="${ bodyClass }">${ body }</body></html>`, {
		runScripts: 'outside-only',
		pretendToBeVisual: true,
	} );
	dom.window.eval( script );
	return dom.window;
}

/** Wait for the script's debounced observer flush (rAF and a 50ms timeout race). */
const settle = () => new Promise( ( resolve ) => setTimeout( resolve, 120 ) );

test( 'a price that is the only child of its element gets the glyph wrapped', async () => {
	const window = boot( `<span id="p">${ SAR }10.00</span>` );
	await settle();

	const price = window.document.getElementById( 'p' );
	const wrappers = price.querySelectorAll( '.nsrwc-symbol' );

	assert.equal( wrappers.length, 1 );
	assert.equal( wrappers[ 0 ].textContent, SAR );
	assert.equal( wrappers[ 0 ].getAttribute( 'translate' ), 'no' );
	assert.equal( price.textContent, `${ SAR }10.00`, 'visible text is unchanged' );
	assert.equal( price.classList.contains( 'gulf-currency' ), false, 'no parent tagging when wrapped' );
});

test( 'a glyph after the amount is wrapped too', async () => {
	const window = boot( `<span id="p">10.00 ${ SAR }</span>` );
	await settle();

	const price = window.document.getElementById( 'p' );
	assert.equal( price.querySelectorAll( '.nsrwc-symbol' ).length, 1 );
	assert.equal( price.textContent, `10.00 ${ SAR }` );
});

test( 'a text node with siblings is not split; its parent is tagged instead', async () => {
	const window = boot( `<span id="p"><b>Total:</b> ${ SAR }10.00</span>` );
	await settle();

	const price = window.document.getElementById( 'p' );
	assert.equal( price.querySelectorAll( '.nsrwc-symbol' ).length, 0 );
	assert.equal( price.classList.contains( 'gulf-currency' ), true );
	assert.equal( price.textContent, `Total: ${ SAR }10.00` );
});

test( 'inside wp-admin the fallback tag is the dashboard class', async () => {
	const window = boot( `<span id="p"><b>Total:</b> ${ SAR }10.00</span>`, 'wp-admin' );
	await settle();

	const price = window.document.getElementById( 'p' );
	assert.equal( price.classList.contains( 'gulf-currency-dashboard' ), true );
});

test( 'an element React renders with a string child is wrapped', async () => {
	const window = boot( `<span id="p">${ SAR }10.00</span>` );
	const price = window.document.getElementById( 'p' );
	// React marks host elements with these expando properties.
	price.__reactFiber$abc = {};
	price.__reactProps$abc = { children: `${ SAR }10.00` };
	// Re-insert so the observer sees it after the markers are in place.
	window.document.body.appendChild( price );
	await settle();

	assert.equal( price.querySelectorAll( '.nsrwc-symbol' ).length, 1 );
});

test( 'an element React renders with non-string children is never mutated', async () => {
	const window = boot( '' );
	const price = window.document.createElement( 'span' );
	price.textContent = `${ SAR }10.00`;
	price.__reactFiber$abc = {};
	price.__reactProps$abc = { children: [ `${ SAR }10.00` ] };
	window.document.body.appendChild( price );
	await settle();

	assert.equal( price.querySelectorAll( '.nsrwc-symbol' ).length, 0 );
	assert.equal( price.childNodes.length, 1, 'text node left intact' );
	assert.equal( price.classList.contains( 'gulf-currency' ), true );
});

test( 'a React element without the props marker (older React) is never mutated', async () => {
	const window = boot( '' );
	const price = window.document.createElement( 'span' );
	price.textContent = `${ SAR }10.00`;
	price.__reactInternalInstance$abc = {};
	window.document.body.appendChild( price );
	await settle();

	assert.equal( price.querySelectorAll( '.nsrwc-symbol' ).length, 0 );
	assert.equal( price.childNodes.length, 1 );
});

test( 'a framework rewriting the original text node does not duplicate the price', async () => {
	const window = boot( `<span id="p">${ SAR }10.00</span>` );
	await settle();

	const price = window.document.getElementById( 'p' );
	const original = price.firstChild;
	assert.equal( original.nodeType, 3, 'the original text node stays first so framework references remain valid' );

	// Vue / React with a Text fiber write the whole new string back into the node they hold.
	original.nodeValue = `${ SAR }20.00`;
	await settle();

	assert.equal( price.textContent, `${ SAR }20.00` );
	assert.equal( price.querySelectorAll( '.nsrwc-symbol' ).length, 1 );
});

test( 'a framework removing the original text node takes the wrapper with it', async () => {
	const window = boot( `<span id="p">${ SAR }10.00</span>` );
	await settle();

	const price = window.document.getElementById( 'p' );
	price.removeChild( price.firstChild );
	await settle();

	assert.equal( price.textContent, '' );
	assert.equal( price.childNodes.length, 0 );
});

test( 'a framework replacing the whole content re-wraps once', async () => {
	const window = boot( `<span id="p">${ SAR }10.00</span>` );
	await settle();

	const price = window.document.getElementById( 'p' );
	price.textContent = `${ SAR }30.00`; // React's setTextContent path.
	await settle();

	assert.equal( price.textContent, `${ SAR }30.00` );
	assert.equal( price.querySelectorAll( '.nsrwc-symbol' ).length, 1 );
});

test( 'symbols WooCommerce already wrapped are left alone', async () => {
	const window = boot( `<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">${ SAR }</span>10.00</bdi></span>` );
	await settle();

	assert.equal( window.document.querySelectorAll( '.nsrwc-symbol' ).length, 0 );
	assert.equal( window.document.querySelectorAll( '.gulf-currency' ).length, 0 );
});

test( 'a text node with several glyphs wraps each one', async () => {
	const window = boot( `<span id="p">${ SAR }10 - ${ SAR }20</span>` );
	await settle();

	const price = window.document.getElementById( 'p' );
	assert.equal( price.querySelectorAll( '.nsrwc-symbol' ).length, 2 );
	assert.equal( price.textContent, `${ SAR }10 - ${ SAR }20` );
});

test( 'text without a glyph is untouched', async () => {
	const window = boot( '<span id="p">$10.00</span>' );
	await settle();

	const price = window.document.getElementById( 'p' );
	assert.equal( price.childNodes.length, 1 );
	assert.equal( price.className, '' );
});
