/* global gsap, Observer */
( function () {
	'use strict';

	if ( typeof gsap === 'undefined' ) {
		return;
	}

	if ( typeof Observer !== 'undefined' ) {
		gsap.registerPlugin( Observer );
	}

	document.querySelectorAll( '.smp-carousel' ).forEach( init );

	function init( wrap ) {
		var track  = wrap.querySelector( '.smp-carousel__track' );
		var cards  = gsap.utils.toArray( '.smp-carousel__card', track );
		var isLoop = wrap.getAttribute( 'data-loop' ) !== '0';
		var visibleCount = parseInt( wrap.getAttribute( 'data-visible' ), 10 ) || 5;
		var isReady = false;

		if ( ! track || ! cards.length ) {
			return;
		}

		var xPos    = 0;
		var xTarget = 0;
		var xAtDragStart = 0;
		var snapTimer;
		var lastX = 0;
		var lastTime = 0;
		var velocity = 0;

		// ── helpers ──────────────────────────────────────────────────────

		function minBound() {
			return Math.min( 0, wrap.offsetWidth - track.scrollWidth );
		}

		function clamp( v ) {
			if ( isLoop ) {
				return v;
			}
			return gsap.utils.clamp( minBound(), 0, v );
		}

		function refreshCards() {
			cards = gsap.utils.toArray( '.smp-carousel__card', track );
		}

		function colGap() {
			return parseFloat( getComputedStyle( track ).columnGap ) || 24;
		}

		function cardStep() {
			return cards[ 0 ].offsetWidth + colGap();
		}

		function centerOffset() {
			return ( wrap.offsetWidth - cards[ 0 ].offsetWidth ) / 2;
		}

		function loopBufferCount() {
			if ( cards.length < 2 ) {
				return 0;
			}

			return Math.min(
				cards.length - 1,
				Math.max( 0, Math.floor( Math.min( visibleCount, cards.length ) / 2 ) )
			);
		}

		function loopBaseOffset() {
			return centerOffset() - ( cardStep() * loopBufferCount() );
		}

		function snapBaseOffset() {
			if ( isLoop ) {
				return loopBaseOffset();
			}

			return centerOffset() - ( cardStep() * loopBufferCount() );
		}

		function snapToNearest() {
			var step = cardStep();
			var base = snapBaseOffset();
			var idx  = Math.round( ( base - xTarget ) / step );

			if ( ! isLoop ) {
				idx = Math.max( 0, Math.min( cards.length - 1, idx ) );
			}

			xTarget = clamp( base - ( idx * step ) );
		}

		function normalizeLoop() {
			var step;
			var base;

			if ( ! isLoop || cards.length < 2 ) {
				return;
			}

			step = cardStep();
			base = loopBaseOffset();

			while ( xPos <= base - step ) {
				track.appendChild( cards[ 0 ] );
				xPos += step;
				xTarget += step;
				refreshCards();
			}

			while ( xPos >= base + step ) {
				track.insertBefore( cards[ cards.length - 1 ], cards[ 0 ] );
				xPos -= step;
				xTarget -= step;
				refreshCards();
			}
		}

		function setInitialPosition() {
			var buffer;
			var i;

			if ( ! cards.length ) {
				return;
			}

			buffer = loopBufferCount();

			if ( isLoop && buffer ) {
				for ( i = 0; i < buffer; i++ ) {
					track.insertBefore( cards[ cards.length - 1 ], cards[ 0 ] );
					refreshCards();
				}
			}

			xPos = isLoop ? loopBaseOffset() : centerOffset() - ( cardStep() * buffer );
			xTarget = xPos;

			if ( ! isLoop ) {
				xPos = clamp( xPos );
				xTarget = xPos;
			}

			gsap.set( track, { x: xPos } );
			updateCards();
			if ( ! isReady ) {
				isReady = true;
				wrap.classList.remove( 'is-loading' );
				wrap.setAttribute( 'aria-busy', 'false' );
			}
		}

		// ── scale / opacity per card ──────────────────────────────────────

		function updateCards() {
			var wrapLeft = wrap.getBoundingClientRect().left;
			var mid      = wrapLeft + wrap.offsetWidth / 2;
			var step     = cardStep();
			var maxDist  = step * Math.max( 1.6, ( visibleCount - 1 ) / 2 + 0.4 );

			cards.forEach( function ( card ) {
				var r    = card.getBoundingClientRect();
				var dist = Math.abs( ( r.left + r.width / 2 ) - mid );
				var t    = gsap.utils.clamp( 0, 1, dist / maxDist );
				var z    = Math.max( 1, Math.round( ( 1 - t ) * 100 ) );
				// Ease the opacity curve so cards next to the centre stay more
				// visible, while distant cards still fall to ~0.
				var opacityT = Math.pow( t, 1.5 );

				gsap.set( card, {
					scale:        gsap.utils.interpolate( 1.08, 0.58, t ),
					'--smp-fade': gsap.utils.interpolate( 0, 0.2, t ),
					opacity:      gsap.utils.interpolate( 1, 0.05, opacityT ),
					zIndex:       z,
				} );
			} );
		}

		// ── ticker for lerp movement ──────────────────────────────────────

		gsap.ticker.add( function () {
			var diff = xTarget - xPos;
			if ( Math.abs( diff ) < 0.05 ) {
				return;
			}
			xPos += diff * 0.18;
			normalizeLoop();
			gsap.set( track, { x: xPos } );
			updateCards();
		} );

		// ── input: wheel + pointer drag ────────────────────────────────────

		wrap.addEventListener( 'wheel', function ( e ) {
			var primaryDelta = Math.abs( e.deltaY ) >= Math.abs( e.deltaX ) ? e.deltaY : e.deltaX;

			if ( ! primaryDelta ) {
				return;
			}

			e.preventDefault();
			xTarget = clamp( xTarget - ( primaryDelta * 0.85 ) );
			velocity = 0;
			clearTimeout( snapTimer );
			snapTimer = setTimeout( snapToNearest, 220 );
		}, { passive: false } );

		if ( typeof Observer !== 'undefined' ) {
			Observer.create( {
				target: wrap,
				type: 'pointer',
				dragMinimum: 6,
				onDragStart: function () {
					xAtDragStart = xTarget;
					lastX = 0;
					lastTime = Date.now();
					velocity = 0;
					wrap.classList.add( 'is-dragging' );
				},
				onDrag: function ( self ) {
					var now = Date.now();
					var dt = Math.max( 1, now - lastTime );
					var dx = self.x - self.startX - lastX;

					velocity = dx / dt;
					lastX = self.x - self.startX;
					lastTime = now;

					xTarget = clamp( xAtDragStart + self.x - self.startX );
				},
				onDragEnd: function () {
					wrap.classList.remove( 'is-dragging' );
					applyMomentum();
				},
				preventDefault: true,
			} );
		} else {
			var touchStartX = 0;
			var touchStartTarget = 0;
			var touchLastX = 0;
			var touchLastTime = 0;

			wrap.addEventListener( 'touchstart', function ( e ) {
				touchStartX = e.touches[ 0 ].clientX;
				touchStartTarget = xTarget;
				touchLastX = touchStartX;
				touchLastTime = Date.now();
				velocity = 0;
			}, { passive: true } );

			wrap.addEventListener( 'touchmove', function ( e ) {
				var now = Date.now();
				var dt = Math.max( 1, now - touchLastTime );
				var currentX = e.touches[ 0 ].clientX;
				var dx = currentX - touchLastX;

				velocity = dx / dt;
				touchLastX = currentX;
				touchLastTime = now;

				var delta = currentX - touchStartX;
				xTarget = clamp( touchStartTarget + delta );
			}, { passive: true } );

			wrap.addEventListener( 'touchend', applyMomentum );
		}

		// ── momentum scrolling ────────────────────────────────────────────

		function applyMomentum() {
			var speed = Math.abs( velocity );
			if ( speed < 0.1 ) {
				snapToNearest();
				return;
			}

			var decelerationTime = Math.min( 800, speed * 1000 );
			var decelerationDistance = velocity * decelerationTime * 0.5;
			var targetX = clamp( xTarget + decelerationDistance );

			clearTimeout( snapTimer );
			gsap.to( { x: xTarget }, {
				x: targetX,
				duration: decelerationTime / 1000,
				ease: 'power2.out',
				onUpdate: function ( tween ) {
					xTarget = tween.targets()[ 0 ].x;
				},
				onComplete: snapToNearest,
			} );
		}

		// ── arrow buttons ─────────────────────────────────────────────────

		var prevBtn = wrap.querySelector( '.smp-carousel__arrow--prev' );
		var nextBtn = wrap.querySelector( '.smp-carousel__arrow--next' );

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				xTarget = clamp( xTarget + cardStep() );
				clearTimeout( snapTimer );
				snapTimer = setTimeout( snapToNearest, 350 );
			} );
		}

		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				xTarget = clamp( xTarget - cardStep() );
				clearTimeout( snapTimer );
				snapTimer = setTimeout( snapToNearest, 350 );
			} );
		}

		// ── initial state ─────────────────────────────────────────────────

		// Wait for images so offsetWidth is accurate
		if ( document.readyState === 'complete' ) {
			setInitialPosition();
		} else {
			window.addEventListener( 'load', setInitialPosition );
		}

		window.addEventListener( 'resize', function () {
			snapToNearest();
			updateCards();
		} );
	}
} )();
