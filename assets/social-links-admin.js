( function ( $ ) {
	'use strict';

	$( function () {
		var data  = window.SMP_SocialLinks || {};
		var icons = data.iconOptions || {};
		var svgs  = data.icons || {};
		var i18n  = data.i18n || {};

		/* ----------------------------------------------------------------
		 * Live preview
		 * -------------------------------------------------------------- */
		var $preview = $( '.smp-social-preview .smp-social' );

		function val( sel ) {
			return $.trim( $( sel ).val() || '' );
		}

		function updatePreview() {
			if ( ! $preview.length ) {
				return;
			}

			var style = $( 'input[name="style"]:checked' ).val() || 'branded';
			var shape = val( '#smp-shape' ) || 'circle';
			var align = val( '#smp-align' ) || 'left';
			var size  = parseInt( val( '#smp-size' ), 10 );
			var pad   = parseInt( val( '#smp-padding' ), 10 );
			var gap   = parseInt( val( '#smp-gap' ), 10 );
			var color = val( '#smp-color' );
			var bg    = val( '#smp-bg' );
			var bw    = parseInt( val( '#smp-border' ), 10 );
			var bc    = val( '#smp-border-color' );

			if ( isNaN( size ) ) { size = 20; }
			if ( isNaN( pad ) ) { pad = 0; }
			if ( isNaN( gap ) ) { gap = 0; }
			if ( isNaN( bw ) ) { bw = 0; }

			// Wrapper classes.
			var cls = 'smp-social smp-social--' + style + ' smp-social--' + shape + ' smp-social--align-' + align;
			if ( bg ) {
				cls += ' smp-social--has-bg';
			}
			$preview.attr( 'class', cls );

			// Shared CSS custom properties.
			var css = '--smp-sl-size:' + size + 'px;--smp-sl-pad:' + pad + 'px;--smp-sl-gap:' + gap + 'px;';
			if ( color ) { css += '--smp-sl-color:' + color + ';'; }
			if ( bg ) { css += '--smp-sl-bg:' + bg + ';'; }
			if ( bw > 0 ) {
				css += '--smp-sl-border-w:' + bw + 'px;';
				if ( bc ) { css += '--smp-sl-border-c:' + bc + ';'; }
			}
			$preview.attr( 'style', css );

			// Swap the glyphs to match the chosen icon set.
			var variant = style === 'minimalist' ? 'minimal' : 'branded';
			$preview.find( '.smp-social__link' ).each( function () {
				var key = $( this ).data( 'icon' );
				if ( svgs[ key ] && svgs[ key ][ variant ] ) {
					$( this ).find( '.smp-social__icon' ).html( svgs[ key ][ variant ] );
				}
			} );
		}

		// React to appearance changes.
		$( document ).on(
			'input change',
			'input[name="style"], #smp-shape, #smp-size, #smp-padding, #smp-gap, #smp-align, #smp-border',
			updatePreview
		);

		/* ----------------------------------------------------------------
		 * Colour pickers (wired to refresh the preview)
		 * -------------------------------------------------------------- */
		if ( $.fn.wpColorPicker ) {
			$( '.smp-color-field' ).wpColorPicker( {
				change: function () {
					// Let WP write the value back to the input first.
					setTimeout( updatePreview, 0 );
				},
				clear: function () {
					setTimeout( updatePreview, 0 );
				}
			} );
		}

		// Sync once on load.
		updatePreview();

		/* ----------------------------------------------------------------
		 * Extras repeater
		 * -------------------------------------------------------------- */
		function escapeHtml( str ) {
			return $( '<div/>' ).text( str == null ? '' : str ).html();
		}

		function iconOptionsHtml() {
			var html = '';
			$.each( icons, function ( key, label ) {
				html += '<option value="' + escapeHtml( key ) + '">' + escapeHtml( label ) + '</option>';
			} );
			return html;
		}

		function targetOptionsHtml() {
			return '<option value="">' + escapeHtml( i18n.inheritTab || 'Use default' ) + '</option>' +
				'<option value="_blank">' + escapeHtml( i18n.newTab || 'New tab' ) + '</option>' +
				'<option value="_self">' + escapeHtml( i18n.sameTab || 'Same tab' ) + '</option>';
		}

		var $body = $( '.smp-extras-body' );
		// Seed from existing rows; only ever increments so indices stay unique.
		var nextIndex = $body.find( '.smp-extra-row' ).length;

		$( '.smp-add-extra' ).on( 'click', function () {
			var i = nextIndex++;
			var row = '<tr class="smp-extra-row">' +
				'<td class="smp-col-icon"><select name="extras[' + i + '][icon]">' + iconOptionsHtml() + '</select></td>' +
				'<td><input type="text" class="regular-text" name="extras[' + i + '][label]" value="" placeholder="' + escapeHtml( i18n.labelPh || '' ) + '" /></td>' +
				'<td><input type="url" class="large-text" name="extras[' + i + '][url]" value="" placeholder="https://" /></td>' +
				'<td class="smp-col-target"><select name="extras[' + i + '][target]">' + targetOptionsHtml() + '</select></td>' +
				'<td class="smp-col-remove"><button type="button" class="button-link smp-remove-extra">' + escapeHtml( i18n.remove || 'Remove' ) + '</button></td>' +
				'</tr>';
			$body.append( row );
		} );

		$body.on( 'click', '.smp-remove-extra', function () {
			$( this ).closest( '.smp-extra-row' ).remove();
		} );

		/* ----------------------------------------------------------------
		 * Copy-to-clipboard buttons
		 * -------------------------------------------------------------- */
		$( '.smp-copy-btn' ).on( 'click', function () {
			var el = document.getElementById( $( this ).data( 'clipboard-target' ) );
			if ( ! el ) {
				return;
			}
			var text = el.textContent;
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( text );
			} else {
				var ta = document.createElement( 'textarea' );
				ta.value = text;
				document.body.appendChild( ta );
				ta.select();
				try {
					document.execCommand( 'copy' );
				} catch ( e ) {}
				document.body.removeChild( ta );
			}
			if ( i18n.copied ) {
				window.alert( i18n.copied );
			}
		} );
	} );
} )( jQuery );
