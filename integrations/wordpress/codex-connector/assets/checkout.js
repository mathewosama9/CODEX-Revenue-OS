/**
 * CODEX Connector – checkout capture.
 *
 * Sends { phone, consent } to the store's own wc-ajax endpoint (nonce-protected).
 * The server only stores the phone / notifies CODEX when consent is ticked.
 * No secret is present here.
 */
( function () {
	'use strict';

	var cfg = window.codexConnector;
	if ( ! cfg || ! cfg.endpoint || ! cfg.nonce ) {
		return;
	}

	var DEBOUNCE_MS = 800;
	var lastSent = null;
	var timer = null;

	function send( phone, consent, cartKey ) {
		var signature = [ phone, consent ? '1' : '0', cartKey || '' ].join( '|' );
		if ( signature === lastSent ) {
			return;
		}
		// Nothing to tell the server: no consent and nothing was sent before.
		if ( ! consent && lastSent === null ) {
			lastSent = signature;
			return;
		}
		lastSent = signature;

		var body = new URLSearchParams();
		body.append( 'nonce', cfg.nonce );
		body.append( 'phone', consent ? String( phone || '' ).slice( 0, 40 ) : '' );
		body.append( 'consent', consent ? '1' : '0' );

		try {
			window.fetch( cfg.endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString(),
			} ).catch( function () {
				lastSent = null; // Allow a retry on the next change.
			} );
		} catch ( e ) {
			lastSent = null;
		}
	}

	function schedule( fn ) {
		window.clearTimeout( timer );
		timer = window.setTimeout( fn, DEBOUNCE_MS );
	}

	/* ------------------------------------------------------------ classic */

	function initClassic() {
		var phoneEl = document.getElementById( 'billing_phone' );
		var consentEl = document.getElementById( cfg.classicField );
		if ( ! phoneEl || ! consentEl ) {
			return false;
		}

		var cartVersion = 0;
		function read( immediate ) {
			var run = function () {
				var p = document.getElementById( 'billing_phone' );
				var c = document.getElementById( cfg.classicField );
				if ( p && c ) {
					send( p.value, c.checked, String( cartVersion ) );
				}
			};
			if ( immediate ) {
				window.clearTimeout( timer );
				run();
			} else {
				schedule( run );
			}
		}

		// Delegated so it survives checkout fragment refreshes.
		document.addEventListener( 'input', function ( e ) {
			if ( e.target && e.target.id === 'billing_phone' ) {
				read( false );
			}
		} );
		document.addEventListener( 'focusout', function ( e ) {
			if ( e.target && e.target.id === 'billing_phone' ) {
				read( true );
			}
		} );
		document.addEventListener( 'change', function ( e ) {
			if ( e.target && ( e.target.id === cfg.classicField || e.target.id === 'billing_phone' ) ) {
				read( true );
			}
		} );
		// Cart totals may change during checkout (coupon, shipping): resend, server de-dupes.
		if ( window.jQuery ) {
			window.jQuery( document.body ).on( 'updated_checkout', function () {
				cartVersion++;
				read( false );
			} );
		}
		return true;
	}

	/* ------------------------------------------------------------- blocks */

	function readBlocksConsent( wpData ) {
		var id = cfg.blocksField;
		try {
			var checkout = wpData.select( 'wc/store/checkout' );
			if ( checkout && typeof checkout.getAdditionalFields === 'function' ) {
				var fields = checkout.getAdditionalFields() || {};
				if ( Object.prototype.hasOwnProperty.call( fields, id ) ) {
					return !! fields[ id ];
				}
			}
		} catch ( e ) {}
		// Fallback: the rendered checkbox (id contains the field id).
		var boxes = document.querySelectorAll( 'input[type="checkbox"]' );
		for ( var i = 0; i < boxes.length; i++ ) {
			var bid = boxes[ i ].id || '';
			var bname = boxes[ i ].name || '';
			if ( bid.indexOf( 'whatsapp-consent' ) !== -1 || bname.indexOf( 'whatsapp-consent' ) !== -1 ) {
				return !! boxes[ i ].checked;
			}
		}
		return false;
	}

	function initBlocks() {
		var wpData = window.wp && window.wp.data;
		if ( ! wpData || typeof wpData.subscribe !== 'function' ) {
			return false;
		}
		var cartStore;
		try {
			cartStore = wpData.select( 'wc/store/cart' );
		} catch ( e ) {
			return false;
		}
		if ( ! cartStore || typeof cartStore.getCustomerData !== 'function' ) {
			return false;
		}

		var lastState = null;
		wpData.subscribe( function () {
			var store = wpData.select( 'wc/store/cart' );
			if ( ! store ) {
				return;
			}
			var customer = store.getCustomerData() || {};
			var billing = customer.billingAddress || {};
			var shipping = customer.shippingAddress || {};
			var phone = billing.phone || shipping.phone || '';
			var consent = readBlocksConsent( wpData );
			var totals = typeof store.getCartTotals === 'function' ? store.getCartTotals() || {} : {};
			var cartKey = String( totals.total_price || '' );

			var state = [ phone, consent, cartKey ].join( '|' );
			if ( state === lastState ) {
				return;
			}
			var consentChanged = lastState !== null && lastState.split( '|' )[ 1 ] !== String( consent );
			lastState = state;

			if ( consentChanged ) {
				window.clearTimeout( timer );
				send( phone, consent, cartKey );
			} else {
				schedule( function () {
					send( phone, consent, cartKey );
				} );
			}
		} );

		// The DOM checkbox fallback is not a wp.data change: listen to it directly too.
		document.addEventListener( 'change', function ( e ) {
			var t = e.target;
			if ( t && t.type === 'checkbox' && ( ( t.id || '' ) + ( t.name || '' ) ).indexOf( 'whatsapp-consent' ) !== -1 ) {
				lastState = null;
				var store = wpData.select( 'wc/store/cart' );
				var customer = ( store && store.getCustomerData() ) || {};
				var phone = ( customer.billingAddress || {} ).phone || ( customer.shippingAddress || {} ).phone || '';
				window.clearTimeout( timer );
				send( phone, !! t.checked, '' );
			}
		} );
		return true;
	}

	function start() {
		if ( document.getElementById( 'billing_phone' ) && initClassic() ) {
			return;
		}
		if ( initBlocks() ) {
			return;
		}
		// Neither checkout found: graceful no-op.
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
