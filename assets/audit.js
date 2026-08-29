( function () {
	'use strict';

	var strings = window.vfAudit || { copied: 'Copied', copyFull: 'Copy full ID' };

	document.addEventListener( 'click', function ( event ) {
		var btn = event.target.closest( '.vfaudit-copy' );
		if ( ! btn ) {
			return;
		}
		event.preventDefault();

		var id = btn.getAttribute( 'data-id' ) || '';
		var done = function () {
			var original = btn.textContent;
			btn.textContent = strings.copied;
			btn.disabled = true;
			setTimeout( function () {
				btn.textContent = original;
				btn.disabled = false;
			}, 1200 );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( id ).then( done ).catch( function () {} );
			return;
		}

		var field = document.createElement( 'textarea' );
		field.value = id;
		field.setAttribute( 'readonly', '' );
		field.style.position = 'absolute';
		field.style.left = '-9999px';
		document.body.appendChild( field );
		field.select();
		try {
			document.execCommand( 'copy' );
			done();
		} catch ( e ) {}
		document.body.removeChild( field );
	} );
} )();
