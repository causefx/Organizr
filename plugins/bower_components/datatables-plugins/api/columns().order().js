/**
 * The DataTables core library provides the ability to set the ordering via the
 * `dt-api column().order()` method, but there is no plural equivalent. While
 * multi-column ordering can be set using `dt-api order()` that method requires
 * that column indexes be used.
 *
 * This plug-in provides the plural `columns().order()` method so you can set
 * multi-column ordering, while retaining the benefits of the `dt-api columns()`
 * selector options.
 *
 *  @name columns().order()
 *  @summary Apply multi-column ordering through the columns() API method.
 *  @author [Allan Jardine](http://sprymedia.co.uk)
 *  @requires DataTables 1.10+
 *  @param {string|array} dir The order to apply to the columns selected. This
 *    can be a string (`asc` or `desc`) which will be applied to all columns,
 *    or an array (again `asc` or `desc` as the elements in the array) which is
 *    the same length as the number of columns selected, and will be applied to
 *    the columns in sequence.
 *
 * @returns {DataTables.Api} DataTables API instance
 *
 *  @example
 *    // Apply multi-column sorting with a common direction
 *    table.columns( [ 1, 2 ] ).order( 'desc' ).draw();
 *
 *  @example
 *    // Multi-column sorting with individual direction for the columns
 *    table.columns( [ 1, 2 ] ).order( [ 'desc', 'asc' ] ).draw();
 *
 *  @example
 *    // Multi-column sorting based on a name selector
 *    table.columns( [ 'sign_up_date:name', 'user_name:name' ] ).order( 'desc' ).draw();
 */

$.fn.dataTable.Api.register( 'columns().order()', function ( dir ) {
  return this.iterator( 'columns', function ( settings, columns ) {
    var a = [];
    
    for ( var i=0, ien=columns.length ; i<ien ; i++ ) {
      a.push( [ columns[i], $.isArray(dir) ? dir[i] : dir ] );
    }
    
    new $.fn.dataTable.Api( settings ).order( a );
  } );
} );
