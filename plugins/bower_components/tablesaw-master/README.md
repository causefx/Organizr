# Tablesaw [![Build Status](https://img.shields.io/travis/filamentgroup/tablesaw/master.svg)](https://travis-ci.org/filamentgroup/tablesaw)

[![Filament Group](http://filamentgroup.com/images/fg-logo-positive-sm-crop.png) ](http://www.filamentgroup.com/)

A set of jQuery plugins for responsive tables.

## Table Modes

### Stack

The Stack Table stacks the table headers to a two column layout with headers on the left when the viewport width is less than `40em` (`640px`).

```html
<table class="tablesaw tablesaw-stack" data-tablesaw-mode="stack">
```

![](docs/stack.gif)

* [Stack Table Demo](http://filamentgroup.github.io/tablesaw/demo/stack.html)

If you only want to use the Stack Table and don’t want all the extra features below (save yourself some bytes), Tablesaw provides a Stack-Only version.

* [Stack-Only Table Demo](http://filamentgroup.github.io/tablesaw/demo/stackonly.html)

#### Opt out of inline labels

To opt-out of inline label creation (the table header cell text that shows at small breakpoints) on a per-table basis, use `<table data-tablesaw-no-labels>`; on a per-row basis, use `<tr data-tablesaw-no-labels>`.

### Toggle

The Column Toggle Table allows the user to select which columns they want to be visible.

    <table data-tablesaw-mode="columntoggle">

Table headers must have a `data-tablesaw-priority` attribute to be eligible to toggle. `data-tablesaw-priority` is a numeric value from 1 to 6, which determine default breakpoints at which a column will show. The breakpoint defaults are:

```html
<th data-tablesaw-priority="persist"><!-- Not eligible for toggle, always shows --></th>
<th data-tablesaw-priority="1"><!-- Shows at (min-width: 20em) (320px) --></th>
<th data-tablesaw-priority="2"><!-- Shows at (min-width: 30em) (480px) --></th>
<th data-tablesaw-priority="3"><!-- Shows at (min-width: 40em) (640px) --></th>
<th data-tablesaw-priority="4"><!-- Shows at (min-width: 50em) (800px) --></th>
<th data-tablesaw-priority="5"><!-- Shows at (min-width: 60em) (960px) --></th>
<th data-tablesaw-priority="6"><!-- Shows at (min-width: 70em) (1120px) --></th>
```

Keep in mind that the priorities are not exclusive—multiple columns can reuse the same priority value.

#### Add a Mini Map

    <table data-tablesaw-mode="columntoggle" data-tablesaw-minimap>

![](docs/columntoggle-minimap.gif)

* [Column Toggle Demo](http://filamentgroup.github.io/tablesaw/demo/toggle.html)

### Swipe

Allows the user to use the swipe gesture (or use the left and right buttons) to navigate the columns.

```html
<table data-tablesaw-mode="swipe">
```

Columns also respect the `data-tablesaw-priority="persist"` attribute.

```html
<th data-tablesaw-priority="persist"><!-- Always shows --></th>
```

#### Add a Mini Map

```html
<table data-tablesaw-mode="swipe" data-tablesaw-minimap>
```

![](docs/swipe-minimap.gif)

* [Swipe Demo](http://filamentgroup.github.io/tablesaw/demo/swipe.html)

#### Advanced: Configure Swipe Thresholds

Add a Tablesaw `config` object after including the Tablesaw JavaScript.

```js
Tablesaw.config = {
  swipe: {
    horizontalThreshold: 15,
    verticalThreshold: 20
  }
};
```

* [Configure Swipe Threshold Demo](http://filamentgroup.github.io/tablesaw/demo/swipe-config.html)

## Mini Map

Use `data-tablesaw-minimap` to add a series of small dots to show which columns are currently visible and which are hidden. Only available on `swipe` and `columntoggle` tables. Examples available above.

## Mode Switcher

```html
<table data-tablesaw-mode-switch>

<!-- With a different default mode -->
<table data-tablesaw-mode="swipe" data-tablesaw-mode-switch>

<!-- Exclude a mode from the switcher -->
<table data-tablesaw-mode-switch data-tablesaw-mode-exclude="columntoggle">
```

![](docs/mode-switch.gif)

* [Mode Switcher Demo](http://filamentgroup.github.io/tablesaw/demo/modeswitch.html)

## Sortable

The “sortable” option allows the user to sort the table data by clicking on the table headers. Since all the columns may not be visible on smaller breakpoints (or not there at all if using the “stack” table mode), relying solely on the column headers to choose the table sort isn’t practical. To address this, there is an optional `data-tablesaw-sortable-switch` attribute on the table that adds a select menu auto-populated with the names of each column in the table with options for choosing ascending or descending sort direction. Data options on table headers can be used to control which columns are sortable (`data-tablesaw-sortable-col`) and the default sort order (`data-tablesaw-sortable-default-col`).

```html
<table data-tablesaw-sortable>
    <thead>
        <tr>
            <!-- Default column -->
            <th data-tablesaw-sortable-col data-tablesaw-sortable-default-col>Rank</th>
            <th data-tablesaw-sortable-col>Movie Title</th>
            <th data-tablesaw-sortable-col data-sortable-numeric>Year</th>
            <th data-tablesaw-sortable-col data-sortable-numeric><abbr title="Rotten Tomato Rating">Rating</abbr></th>
            <!-- Unsortable column -->
            <th>Reviews</th>
        </tr>
    </thead>
    ...
```

Use `data-tablesaw-sortable-switch` to add a select form element to manually choose the sort order.

    <table data-tablesaw-sortable data-tablesaw-sortable-switch>

![](docs/sortable.png)

* [Sortable Demo](http://filamentgroup.github.io/tablesaw/demo/sort.html)

### Custom Sort Functions

We also provide the option to specify Custom Sort functions on individual columns ([working example](http://filamentgroup.github.io/tablesaw/demo/sort-custom.html)). In the contrived example below, we want to sort full dates (e.g. `12/02/2014`) just on the year.

```
// Add a data function to the table header cell
$( "th#custom-sort" ).data( "tablesaw-sort", function( ascending ) {
    // return a function
    return function( a, b ) {
        // use a.cell and b.cell for cell values
        var dateA = a.cell.split( "/" ),
            dateB = b.cell.split( "/" ),
            yearA = parseInt( dateA[ 2 ], 10 ),
            yearB = parseInt( dateB[ 2 ], 10 );

        if( ascending ) {
            return yearA > yearB;
        } else { // descending
            return yearA < yearB;
        }
    };
});
```

## Kitchen ~~Table~~ Sink

All of the above options combined into a single table.

* [Kitchen Sink Demo](http://filamentgroup.github.io/tablesaw/demo/kitchensink.html)

## Getting Started

TableSaw requires [Respond.js](https://github.com/scottjehl/Respond) for IE8- support.

### Using Stack-Only TableSaw

As shown above, we provide a Stack-mode-only package of TableSaw. It’s a barebones version that doesn’t include any of the other features above.

```html
<link rel="stylesheet" href="tablesaw.css">
<!--[if lt IE 9]><script src="dependencies/respond.js"></script><!--<![endif]-->
<script src="tablesaw.js"></script>
<script src="tablesaw-init.js"></script>
```

And then:

```html
<table class="tablesaw tablesaw-stack" data-tablesaw-mode="stack">
```

Check out [the Stack-Only demo](http://filamentgroup.github.io/tablesaw/demo/stackonly.html) to see a working example.

### Using Stack-Only TableSaw SCSS Mixin

To easily customize the breakpoint at which the stack table switches, use the SCSS mixin.  First, include the `tablesaw.stackonly.scss` file instead of `tablesaw.stackonly.css` in your SASS. Then, use a parent selector on your table.

```html
<div class="my-parent-selector">
    <table class="tablesaw" data-tablesaw-mode="stack">
```

Include the mixin like so:

```scss
.my-parent-selector {
  @include tablesaw-stack( 50em );
}
```

The argument to `tablesaw-stack` is the breakpoint at which the table will switch from columns to stacked.

### The Full TableSaw 

If you want the other modes, it’ll take a little bit more configuration.

```html
<link rel="stylesheet" href="tablesaw.css">

<!--[if lt IE 9]><script src="dependencies/respond.js"></script><!--<![endif]-->
<script src="dependencies/jquery.js"></script>
<script src="tablesaw.js"></script>
<script src="tablesaw-init.js"></script>
```

Check out any of the demos above for complete working examples.

### Using Bare TableSaw 

Tablesaw is designed to be a drop-in solution, providing table styles as well as responsive table functionality.

If you would like the full functionality of the TableSaw plugin. but the plugin's default table styles don't fit in with your project, use the `tablesaw.bare.css` file instead of the standard `tablesaw.css` file for a much lighter default style which is significantly easier to customize.

To see what all TableSaw functionality looks like with this alternate stylesheet applied.

* [Bare CSS Demo](http://filamentgroup.github.io/tablesaw/demo/bare.html)

## [Tests](http://filamentgroup.github.io/tablesaw/test/tablesaw.html)

## Building the Project Locally

Run `npm install` to install dependencies and then `grunt` to build the project files into the `dist` folder.
