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
		var snapTimer;
		var velocity = 0;
		var velSamples = [];
		var VEL_WINDOW = 100;   // ms window over which release velocity is measured
		var isDragging = false;
		var didDrag = false;

		// Momentum tuning. Velocity is tracked in px/ms; the ticker turns it
		// into distance per frame via MOMENTUM_STEP and decays it by FRICTION.
		var FRICTION = 0.94;       // per-frame velocity decay during momentum
		var MIN_VELOCITY = 0.05;   // below this, momentum stops and snaps
		var MOMENTUM_STEP = 16;    // ms/frame used to turn velocity into distance
		var MAX_THROW_CARDS = 3;   // cap a single fling to this many cards

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

		// ── momentum helpers ──────────────────────────────────────────────

		// Stop dead wherever we are. Used on press/touchstart so a tap halts a
		// running fling instead of letting it coast under the finger.
		function freeze() {
			velocity = 0;
			velSamples.length = 0;
			clearTimeout( snapTimer );
			xTarget = xPos;
		}

		// Sample the drag position over a short window so release velocity
		// reflects the real fling speed, not one noisy sample. performance.now()
		// has sub-ms resolution, avoiding the spikes that Date.now()'s 1ms
		// resolution produced on slow drags (tiny dt → huge dx/dt).
		function trackVelocity( pos ) {
			var now = performance.now();
			velSamples.push( { x: pos, t: now } );
			while ( velSamples.length > 2 && now - velSamples[ 0 ].t > VEL_WINDOW ) {
				velSamples.shift();
			}
			var first = velSamples[ 0 ];
			var span  = now - first.t;
			velocity  = span > 0 ? ( pos - first.x ) / span : 0;
		}

		// Hand a drag off to the ticker's momentum phase. Cap the fling so a hard
		// swipe can't overshoot more than a few cards, then let friction + snap
		// take over. Tiny flicks just snap immediately.
		function releaseDrag() {
			isDragging = false;
			// If the finger was held still just before lifting, don't fling.
			var last = velSamples[ velSamples.length - 1 ];
			if ( ! last || performance.now() - last.t > 60 ) {
				velocity = 0;
			}
			var maxV = ( MAX_THROW_CARDS * cardStep() * ( 1 - FRICTION ) ) / MOMENTUM_STEP;
			velocity = gsap.utils.clamp( -maxV, maxV, velocity );
			if ( Math.abs( velocity ) < MIN_VELOCITY ) {
				velocity = 0;
				snapToNearest();
			}
		}

		// ── ticker: single source of truth for movement ───────────────────

		gsap.ticker.add( function () {
			// Momentum is integrated here, into the same xTarget that
			// normalizeLoop() adjusts, so the two can never desync.
			if ( ! isDragging && Math.abs( velocity ) > MIN_VELOCITY ) {
				xTarget = clamp( xTarget + velocity * MOMENTUM_STEP );
				velocity *= FRICTION;
				if ( Math.abs( velocity ) <= MIN_VELOCITY ) {
					velocity = 0;
					snapToNearest();
				}
			}

			var diff = xTarget - xPos;
			if ( Math.abs( diff ) < 0.05 ) {
				return;
			}
			xPos += isDragging ? diff : diff * 0.32;
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
			snapTimer = setTimeout( snapToNearest, 600 );
		}, { passive: false } );

		if ( typeof Observer !== 'undefined' ) {
			Observer.create( {
				target: wrap,
				type: 'pointer',
				dragMinimum: 3,
				onPress: function () {
					didDrag = false;
					freeze();
				},
				onDragStart: function () {
					didDrag = true;
					isDragging = true;
					velocity = 0;
					wrap.classList.add( 'is-dragging' );
				},
				onDrag: function ( self ) {
					trackVelocity( self.x );
					// Apply the incremental finger delta, not an absolute offset
					// from a fixed origin. normalizeLoop() shifts xTarget by ±step
					// when a card wraps mid-drag; an absolute mapping would clobber
					// that each move and make the carousel race.
					xTarget = clamp( xTarget + self.deltaX );
				},
				onDragEnd: function () {
					wrap.classList.remove( 'is-dragging' );
					releaseDrag();
				},
				onRelease: function () {
					// A tap (press + release with no drag) settles to the
					// nearest card instead of leaving it mid-scroll.
					if ( ! didDrag ) {
						snapToNearest();
					}
				},
				preventDefault: true,
			} );
		} else {
			var lastTouchX = 0;

			wrap.addEventListener( 'touchstart', function ( e ) {
				didDrag = false;
				freeze();
				lastTouchX = e.touches[ 0 ].clientX;
			}, { passive: true } );

			wrap.addEventListener( 'touchmove', function ( e ) {
				didDrag = true;
				isDragging = true;
				var currentX = e.touches[ 0 ].clientX;
				trackVelocity( currentX );
				// Incremental delta so normalizeLoop()'s xTarget shifts survive.
				xTarget = clamp( xTarget + ( currentX - lastTouchX ) );
				lastTouchX = currentX;
			}, { passive: true } );

			wrap.addEventListener( 'touchend', function () {
				if ( ! didDrag ) {
					snapToNearest();
					return;
				}
				releaseDrag();
			} );
		}

		// ── arrow buttons ─────────────────────────────────────────────────

		var prevBtn = wrap.querySelector( '.smp-carousel__arrow--prev' );
		var nextBtn = wrap.querySelector( '.smp-carousel__arrow--next' );

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				velocity = 0;
				xTarget = clamp( xTarget + cardStep() );
				clearTimeout( snapTimer );
				snapTimer = setTimeout( snapToNearest, 500 );
			} );
		}

		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				velocity = 0;
				xTarget = clamp( xTarget - cardStep() );
				clearTimeout( snapTimer );
				snapTimer = setTimeout( snapToNearest, 500 );
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
