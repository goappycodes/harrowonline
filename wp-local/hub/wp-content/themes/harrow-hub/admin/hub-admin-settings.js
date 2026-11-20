/*
 * wp-admin > Hub settings: keep the band preview in step with the form, and say when there are unsaved changes.
 * The form fields are named hub_nl[key] (the newsletter list rows hub_nl[editions][row][0..3]).
 */
( function () {
	var form = document.getElementById( 'hub-nl-form' );
	var preview = document.getElementById( 'hub-nl-preview' );
	if ( ! form || ! preview ) {
		return;
	}
	var dirty = document.getElementById( 'hub-nl-dirty' );

	function field( key ) {
		return form.querySelector( '[name="hub_nl[' + key + ']"]:not([type="hidden"])' );
	}

	function text( key ) {
		var el = preview.querySelector( '[data-preview="' + key + '"]' );
		var input = field( key );
		if ( ! el || ! input ) {
			return;
		}
		var v = input.value.trim();
		el.textContent = v !== '' ? v : ( input.getAttribute( 'placeholder' ) || el.getAttribute( 'data-default' ) || '' );
	}

	function editions() {
		var list = preview.querySelector( '[data-preview="editions"]' );
		if ( ! list ) {
			return;
		}
		var rows = {};
		form.querySelectorAll( '[name^="hub_nl[editions]["]' ).forEach( function ( input ) {
			var m = input.name.match( /^hub_nl\[editions\]\[(\d+)\]\[(\d)\]$/ );
			if ( ! m ) {
				return;
			}
			rows[ m[1] ] = rows[ m[1] ] || {};
			if ( input.type === 'checkbox' ) {
				rows[ m[1] ][ m[2] ] = input.checked;
			} else if ( input.type !== 'hidden' ) {
				rows[ m[1] ][ m[2] ] = input.value.trim();
			}
		} );
		list.innerHTML = '';
		Object.keys( rows ).forEach( function ( k ) {
			var r = rows[ k ];
			if ( ! r[1] ) {
				return;
			}
			var li = document.createElement( 'li' );
			var box = document.createElement( 'span' );
			box.className = 'hub-nl-preview__box' + ( r[3] ? ' is-on' : '' );
			var name = document.createElement( 'strong' );
			name.textContent = r[1];
			li.appendChild( box );
			li.appendChild( name );
			if ( r[2] ) {
				var d = document.createElement( 'span' );
				d.textContent = r[2];
				li.appendChild( d );
			}
			list.appendChild( li );
		} );
	}

	function show() {
		var toggle = form.querySelector( 'input[type="checkbox"][name="hub_nl[show]"]' );
		if ( toggle ) {
			preview.setAttribute( 'data-off', toggle.checked ? '' : '1' );
		}
	}

	function update() {
		[ 'title', 'text', 'placeholder', 'button' ].forEach( text );
		editions();
		show();
	}

	function changed() {
		update();
		if ( dirty ) {
			dirty.hidden = false;
		}
	}

	form.addEventListener( 'input', changed );
	form.addEventListener( 'change', changed );
	// Rows added or removed in the newsletter list. Only the form's cards: the preview is inside the form too, and
	// watching it would set off an endless loop.
	var cards = form.querySelector( '.hub-settings__main' );
	if ( cards ) {
		new MutationObserver( update ).observe( cards, { childList: true, subtree: true } );
	}
	form.addEventListener( 'submit', function () {
		if ( dirty ) {
			dirty.hidden = true;
		}
	} );
	update();
}() );
