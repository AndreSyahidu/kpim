/* MBD KPI Command Center — front-end behaviour */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		// Mobile navigation toggle.
		var toggle = document.querySelector( '.mbd-nav-toggle' );
		var nav = document.querySelector( '.mbd-nav' );
		if ( toggle && nav ) {
			toggle.addEventListener( 'click', function () {
				nav.classList.toggle( 'is-open' );
			} );
		}

		// Confirm on destructive buttons not already wired with inline confirm.
		var dangers = document.querySelectorAll( '.mbd-btn-danger[data-confirm]' );
		dangers.forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				if ( ! window.confirm( btn.getAttribute( 'data-confirm' ) ) ) {
					e.preventDefault();
				}
			} );
		} );
	} );
}() );
