/**
 * Enables filtration delay for keeping the browser more responsive while 
 * searching for a longer keyword.
 *
 * This can be particularly useful when working with server-side processing,
 * where you wouldn't typically want an Ajax request to be made with every key
 * press the user makes when searching the table.
 *
 *  @name fnSetFilteringDelay
 *  @summary Add a key debouce delay to the global filtering input of a table
 *  @author [Zygimantas Berziunas](http://www.zygimantas.com/), 
 *    [Allan Jardine](http://www.sprymedia.co.uk/) and _vex_
 *
 *  @example
 *    $(document).ready(function() {
 *        $('.dataTable').dataTable().fnSetFilteringDelay();
 *    } );
 */

jQuery.fn.dataTableExt.oApi.fnSetFilteringDelay = function ( oSettings, iDelay ) {
	var _that = this;

	if ( iDelay === undefined ) {
		iDelay = 250;
	}

	this.each( function ( i ) {
		$.fn.dataTableExt.iApiIndex = i;
		var
			$this = this,
			oTimerId = null,
			sPreviousSearch = null,
			anControl = $( 'input', _that.fnSettings().aanFeatures.f );

			anControl.unbind( 'keyup search input' ).bind( 'keyup search input', function() {
			var $$this = $this;

			if (sPreviousSearch === null || sPreviousSearch != anControl.val()) {
				window.clearTimeout(oTimerId);
				sPreviousSearch = anControl.val();
				oTimerId = window.setTimeout(function() {
					$.fn.dataTableExt.iApiIndex = i;
					_that.fnFilter( anControl.val() );
				}, iDelay);
			}
		});

		return this;
	} );
	return this;
};
