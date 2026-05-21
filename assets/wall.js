( function () {
	'use strict';

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	if ( reduce ) {
		return;
	}

	var FADE = 450;

	document.querySelectorAll( '.smp-wall' ).forEach( init );

	function init( wall ) {
		var grid = wall.querySelector( '.smp-wall__grid' );
		if ( ! grid ) {
			return;
		}

		var tiles = Array.prototype.slice.call( grid.querySelectorAll( '.smp-wall__tile' ) );
		if ( tiles.length < 4 ) {
			return;
		}

		var interval   = clampInt( wall.getAttribute( 'data-interval' ), 7800, 600, 60000 );
		var swaps      = clampInt( wall.getAttribute( 'data-swaps' ), 3, 1, 30 );
		var canAnimate = typeof tiles[ 0 ].animate === 'function';

		var busy    = [];
		var timer   = null;
		var visible = true;

		// ── scheduling (recursive timeout so each tick can jitter) ─────────

		function jittered() {
			var spread = interval * 0.25;
			return Math.max( 500, interval + ( Math.random() * 2 - 1 ) * spread );
		}

		function start() {
			if ( timer === null && visible && ! document.hidden ) {
				timer = setTimeout( function run() {
					tick();
					timer = setTimeout( run, jittered() );
				}, jittered() );
			}
		}

		function stop() {
			if ( timer !== null ) {
				clearTimeout( timer );
				timer = null;
			}
		}

		// ── one round of random crossfades ─────────────────────────────────

		function tick() {
			if ( ! visible || document.hidden ) {
				return;
			}
			for ( var i = 0; i < swaps; i++ ) {
				var pair = pick();
				if ( pair ) {
					swap( pair[ 0 ], pair[ 1 ] );
				}
			}
		}

		function pick() {
			var a = rand();
			var b = rand();
			var guard = 0;
			while ( ( a === b || isBusy( a ) || isBusy( b ) ) && guard++ < 40 ) {
				a = rand();
				b = rand();
			}
			if ( a === b || isBusy( a ) || isBusy( b ) ) {
				return null;
			}
			return [ a, b ];
		}

		function rand() {
			return tiles[ ( Math.random() * tiles.length ) | 0 ];
		}

		function isBusy( node ) {
			return busy.indexOf( node ) !== -1;
		}

		function release( node ) {
			var i = busy.indexOf( node );
			if ( i !== -1 ) {
				busy.splice( i, 1 );
			}
		}

		// ── crossfade: fade both cells out, exchange their contents while
		//    invisible, then fade back in — no movement across the grid ─────

		function swap( a, b ) {
			var ca = a.firstElementChild;
			var cb = b.firstElementChild;
			if ( ! ca || ! cb ) {
				return;
			}

			busy.push( a, b );

			if ( ! canAnimate ) {
				a.appendChild( cb );
				b.appendChild( ca );
				release( a );
				release( b );
				return;
			}

			var out = 2;
			var afterOut = function () {
				if ( --out > 0 ) {
					return;
				}
				a.appendChild( cb );
				b.appendChild( ca );

				var inn = 2;
				var afterIn = function () {
					if ( --inn === 0 ) {
						release( a );
						release( b );
					}
				};
				fade( ca, 0, 1, afterIn );
				fade( cb, 0, 1, afterIn );
			};

			fade( ca, 1, 0, afterOut );
			fade( cb, 1, 0, afterOut );
		}

		function fade( node, from, to, done ) {
			var anim = node.animate(
				[ { opacity: from }, { opacity: to } ],
				{ duration: FADE, easing: 'ease', fill: 'forwards' }
			);
			anim.addEventListener( 'finish', function () {
				node.style.opacity = String( to );
				anim.cancel();
				done();
			} );
		}

		// ── lifecycle: pause off-screen and on hidden tabs ─────────────────

		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				stop();
			} else {
				start();
			}
		} );

		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( function ( entries ) {
				visible = entries[ 0 ].isIntersecting;
				if ( visible ) {
					start();
				} else {
					stop();
				}
			}, { threshold: 0 } ).observe( wall );
		} else {
			start();
		}

		function clampInt( value, def, lo, hi ) {
			value = parseInt( value, 10 );
			if ( isNaN( value ) ) {
				value = def;
			}
			return Math.max( lo, Math.min( hi, value ) );
		}
	}
} )();
