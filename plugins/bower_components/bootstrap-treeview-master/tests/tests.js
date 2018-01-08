/* global $, module, test, equal, ok */

;(function () {

	'use strict';

	function init(options) {
		return $('#treeview').treeview(options);
	}

	function getOptions(el) {
		return el.data().treeview.options;
	}

	var data = [
		{
			text: 'Parent 1',
			nodes: [
				{
					text: 'Child 1',
					nodes: [
						{
							text: 'Grandchild 1'
						},
						{
							text: 'Grandchild 2'
						}
					]
				},
				{
					text: 'Child 2'
				}
			]
		},
		{
			text: 'Parent 2'
		},
		{
			text: 'Parent 3'
		},
		{
			text: 'Parent 4'
		},
		{
			text: 'Parent 5'
		}
	];

	var json = '[' +
		'{' +
			'"text": "Parent 1",' +
			'"nodes": [' +
				'{' +
					'"text": "Child 1",' +
					'"nodes": [' +
						'{' +
							'"text": "Grandchild 1"' +
						'},' +
						'{' +
							'"text": "Grandchild 2"' +
						'}' +
					']' +
				'},' +
				'{' +
					'"text": "Child 2"' +
				'}' +
			']' +
		'},' +
		'{' +
			'"text": "Parent 2"' +
		'},' +
		'{' +
			'"text": "Parent 3"' +
		'},' +
		'{' +
			'"text": "Parent 4"' +
		'},' +
		'{' +
			'"text": "Parent 5"' +
		'}' +
	']';

	module('Options');

	test('Options setup', function () {
		// First test defaults option values
		var el = init(),
			options = getOptions(el);
		ok(options, 'Defaults created ok');
		equal(options.levels, 2, 'levels default ok');
		equal(options.expandIcon, 'glyphicon glyphicon-plus', 'expandIcon default ok');
		equal(options.collapseIcon, 'glyphicon glyphicon-minus', 'collapseIcon default ok');
		equal(options.emptyIcon, 'glyphicon', 'emptyIcon default ok');
		equal(options.nodeIcon, '', 'nodeIcon default ok');
		equal(options.selectedIcon, '', 'selectedIcon default ok');
		equal(options.checkedIcon, 'glyphicon glyphicon-check', 'checkedIcon default ok');
		equal(options.uncheckedIcon, 'glyphicon glyphicon-unchecked', 'uncheckedIcon default ok');
		equal(options.color, undefined, 'color default ok');
		equal(options.backColor, undefined, 'backColor default ok');
		equal(options.borderColor, undefined, 'borderColor default ok');
		equal(options.onhoverColor, '#F5F5F5', 'onhoverColor default ok');
		equal(options.selectedColor, '#FFFFFF', 'selectedColor default ok');
		equal(options.selectedBackColor, '#428bca', 'selectedBackColor default ok');
		equal(options.searchResultColor, '#D9534F', 'searchResultColor default ok');
		equal(options.searchResultBackColor, undefined, 'searchResultBackColor default ok');
		equal(options.enableLinks, false, 'enableLinks default ok');
		equal(options.highlightSelected, true, 'highlightSelected default ok');
		equal(options.highlightSearchResults, true, 'highlightSearchResults default ok');
		equal(options.showBorder, true, 'showBorder default ok');
		equal(options.showIcon, true, 'showIcon default ok');
		equal(options.showCheckbox, false, 'showCheckbox default ok');
		equal(options.showTags, false, 'showTags default ok');
		equal(options.multiSelect, false, 'multiSelect default ok');
		equal(options.onNodeChecked, null, 'onNodeChecked default ok');
		equal(options.onNodeCollapsed, null, 'onNodeCollapsed default ok');
		equal(options.onNodeDisabled, null, 'onNodeDisabled default ok');
		equal(options.onNodeEnabled, null, 'onNodeEnabled default ok');
		equal(options.onNodeExpanded, null, 'onNodeExpanded default ok');
		equal(options.onNodeSelected, null, 'onNodeSelected default ok');
		equal(options.onNodeUnchecked, null, 'onNodeUnchecked default ok');
		equal(options.onNodeUnselected, null, 'onNodeUnselected default ok');
		equal(options.onSearchComplete, null, 'onSearchComplete default ok');
		equal(options.onSearchCleared, null, 'onSearchCleared default ok');

		// Then test user options are correctly set
		var opts = {
			levels: 99,
			expandIcon: 'glyphicon glyphicon-expand',
			collapseIcon: 'glyphicon glyphicon-collapse',
			emptyIcon: 'glyphicon',
			nodeIcon: 'glyphicon glyphicon-stop',
			selectedIcon: 'glyphicon glyphicon-selected',
			checkedIcon: 'glyphicon glyphicon-checked-icon',
			uncheckedIcon: 'glyphicon glyphicon-unchecked-icon',
			color: 'yellow',
			backColor: 'purple',
			borderColor: 'purple',
			onhoverColor: 'orange',
			selectedColor: 'yellow',
			selectedBackColor: 'darkorange',
			searchResultColor: 'yellow',
			searchResultBackColor: 'darkorange',
			enableLinks: true,
			highlightSelected: false,
			highlightSearchResults: true,
			showBorder: false,
			showIcon: false,
			showCheckbox: true,
			showTags: true,
			multiSelect: true,
			onNodeChecked: function () {},
			onNodeCollapsed: function () {},
			onNodeDisabled: function () {},
			onNodeEnabled: function () {},
			onNodeExpanded: function () {},
			onNodeSelected: function () {},
			onNodeUnchecked: function () {},
			onNodeUnselected: function () {},
			onSearchComplete: function () {},
			onSearchCleared: function () {}
		};

		options = getOptions(init(opts));
		ok(options, 'User options created ok');
		equal(options.levels, 99, 'levels set ok');
		equal(options.expandIcon, 'glyphicon glyphicon-expand', 'expandIcon set ok');
		equal(options.collapseIcon, 'glyphicon glyphicon-collapse', 'collapseIcon set ok');
		equal(options.emptyIcon, 'glyphicon', 'emptyIcon set ok');
		equal(options.nodeIcon, 'glyphicon glyphicon-stop', 'nodeIcon set ok');
		equal(options.selectedIcon, 'glyphicon glyphicon-selected', 'selectedIcon set ok');
		equal(options.checkedIcon, 'glyphicon glyphicon-checked-icon', 'checkedIcon set ok');
		equal(options.uncheckedIcon, 'glyphicon glyphicon-unchecked-icon', 'uncheckedIcon set ok');
		equal(options.color, 'yellow', 'color set ok');
		equal(options.backColor, 'purple', 'backColor set ok');
		equal(options.borderColor, 'purple', 'borderColor set ok');
		equal(options.onhoverColor, 'orange', 'onhoverColor set ok');
		equal(options.selectedColor, 'yellow', 'selectedColor set ok');
		equal(options.selectedBackColor, 'darkorange', 'selectedBackColor set ok');
		equal(options.searchResultColor, 'yellow', 'searchResultColor set ok');
		equal(options.searchResultBackColor, 'darkorange', 'searchResultBackColor set ok');
		equal(options.enableLinks, true, 'enableLinks set ok');
		equal(options.highlightSelected, false, 'highlightSelected set ok');
		equal(options.highlightSearchResults, true, 'highlightSearchResults set ok');
		equal(options.showBorder, false, 'showBorder set ok');
		equal(options.showIcon, false, 'showIcon set ok');
		equal(options.showCheckbox, true, 'showCheckbox set ok');
		equal(options.showTags, true, 'showTags set ok');
		equal(options.multiSelect, true, 'multiSelect set ok');
		equal(typeof options.onNodeChecked, 'function', 'onNodeChecked set ok');
		equal(typeof options.onNodeCollapsed, 'function', 'onNodeCollapsed set ok');
		equal(typeof options.onNodeDisabled, 'function', 'onNodeDisabled set ok');
		equal(typeof options.onNodeEnabled, 'function', 'onNodeEnabled set ok');
		equal(typeof options.onNodeExpanded, 'function', 'onNodeExpanded set ok');
		equal(typeof options.onNodeSelected, 'function', 'onNodeSelected set ok');
		equal(typeof options.onNodeUnchecked, 'function', 'onNodeUnchecked set ok');
		equal(typeof options.onNodeUnselected, 'function', 'onNodeUnselected set ok');
		equal(typeof options.onSearchComplete, 'function', 'onSearchComplete set ok');
		equal(typeof options.onSearchCleared, 'function', 'onSearchCleared set ok');
	});

	test('Links enabled', function () {
		init({enableLinks:true, data:data});
		ok($('.list-group-item:first').children('a').length, 'Links are enabled');

	});


	module('Data');

	test('Accepts JSON', function () {
		var el = init({levels:1,data:json});
		equal($(el.selector + ' ul li').length, 5, 'Correct number of root nodes');

	});


	module('Behaviour');

	test('Is chainable', function () {
		var el = init();
		equal(el.addClass('test').attr('class'), 'treeview test', 'Is chainable');
	});

	test('Correct initial levels shown', function () {

		var el = init({levels:1,data:data});
		equal($(el.selector + ' ul li').length, 5, 'Correctly display 5 root nodes when levels set to 1');

		el = init({levels:2,data:data});
		equal($(el.selector + ' ul li').length, 7, 'Correctly display 5 root and 2 child nodes when levels set to 2');

		el = init({levels:3,data:data});
		equal($(el.selector + ' ul li').length, 9, 'Correctly display 5 root, 2 children and 2 grand children nodes when levels set to 3');
	});

	test('Expanding a node', function () {

		var cbWorked, onWorked = false;
		init({
			data: data,
			levels: 1,
			onNodeExpanded: function(/*event, date*/) {
				cbWorked = true;
			}
		})
		.on('nodeExpanded', function(/*event, date*/) {
			onWorked = true;
		});

		var nodeCount = $('.list-group-item').length;
		var el = $('.expand-icon:first');
		el.trigger('click');
		ok(($('.list-group-item').length > nodeCount), 'Number of nodes are increased, so node must have expanded');
		ok(cbWorked, 'onNodeExpanded function was called');
		ok(onWorked, 'nodeExpanded was fired');
	});

	test('Collapsing a node', function () {

		var cbWorked, onWorked = false;
		init({
			data: data,
			levels: 2,
			onNodeCollapsed: function(/*event, date*/) {
				cbWorked = true;
			}
		})
		.on('nodeCollapsed', function(/*event, date*/) {
			onWorked = true;
		});

		var nodeCount = $('.list-group-item').length;
		var el = $('.expand-icon:first');
		el.trigger('click');
		ok(($('.list-group-item').length < nodeCount), 'Number of nodes has decreased, so node must have collapsed');
		ok(cbWorked, 'onNodeCollapsed function was called');
		ok(onWorked, 'nodeCollapsed was fired');
	});

	test('Selecting a node', function () {

		var cbWorked, onWorked = false;
		var $tree = init({
			data: data,
			onNodeSelected: function(/*event, date*/) {
				cbWorked = true;
			}
		})
		.on('nodeSelected', function(/*event, date*/) {
			onWorked = true;
		});
		var options = getOptions($tree);

		// Simulate click
		$('.list-group-item:first').trigger('click');

		// Has class node-selected
		ok($('.list-group-item:first').hasClass('node-selected'), 'Node is correctly selected : class "node-selected" added');
		
		// Only one can be selected
		ok(($('.node-selected').length === 1), 'There is only one selected node');

		// Has correct icon
		var iconClass = options.selectedIcon || options.nodeIcon;
		ok(!iconClass || $('.expand-icon:first').hasClass(iconClass), 'Node icon is correct');

		// Events triggered
		ok(cbWorked, 'onNodeSelected function was called');
		ok(onWorked, 'nodeSelected was fired');
	});

	test('Unselecting a node', function () {

		var cbWorked, onWorked = false;
		var $tree = init({
			data: data,
			onNodeUnselected: function(/*event, date*/) {
				cbWorked = true;
			}
		})
		.on('nodeUnselected', function(/*event, date*/) {
			onWorked = true;
		});
		var options = getOptions($tree);

		// First select a node
		$('.list-group-item:first').trigger('click');
		cbWorked = onWorked = false;

		// Simulate click
		$('.list-group-item:first').trigger('click');

		// Has class node-selected
		ok(!$('.list-group-item:first').hasClass('node-selected'), 'Node is correctly unselected : class "node-selected" removed');
		
		// Only one can be selected
		ok(($('.node-selected').length === 0), 'There are no selected nodes');

		// Has correct icon
		ok(!options.nodeIcon || $('.expand-icon:first').hasClass(options.nodeIcon), 'Node icon is correct');
		
		// Events triggered
		ok(cbWorked, 'onNodeUnselected function was called');
		ok(onWorked, 'nodeUnselected was fired');
	});

	test('Selecting multiple nodes (multiSelect true)', function () {

		init({
			data: data,
			multiSelect: true
		});

		var $firstEl = $('.list-group-item:nth-child(1)').trigger('click');
		var $secondEl = $('.list-group-item:nth-child(2)').trigger('click');

		$firstEl = $('.list-group-item:nth-child(1)');
		$secondEl = $('.list-group-item:nth-child(2)');

		ok($firstEl.hasClass('node-selected'), 'First node is correctly selected : class "node-selected" added');
		ok($secondEl.hasClass('node-selected'), 'Second node is correctly selected : class "node-selected" added');
		ok(($('.node-selected').length === 2), 'There are two selected nodes');
	});

	test('Clicking a non-selectable, collapsed node expands the node', function () {
		var testData = $.extend(true, {}, data);
		testData[0].selectable = false;

		var cbCalled, onCalled = false;
		init({
			levels: 1,
			data: testData,
			onNodeSelected: function(/*event, date*/) {
				cbCalled = true;
			}
		})
		.on('nodeSelected', function(/*event, date*/) {
			onCalled = true;
		});

		var nodeCount = $('.list-group-item').length;
		var el = $('.list-group-item:first');
		el.trigger('click');
		el = $('.list-group-item:first');
		ok(!el.hasClass('node-selected'), 'Node should not be selected');
		ok(!cbCalled, 'onNodeSelected function should not be called');
		ok(!onCalled, 'nodeSelected should not fire');
		ok(($('.list-group-item').length > nodeCount), 'Number of nodes are increased, so node must have expanded');
	});

	test('Clicking a non-selectable, expanded node collapses the node', function () {
		var testData = $.extend(true, {}, data);
		testData[0].selectable = false;

		var cbCalled, onCalled = false;
		init({
			levels: 2,
			data: testData,
			onNodeSelected: function(/*event, date*/) {
				cbCalled = true;
			}
		})
		.on('nodeSelected', function(/*event, date*/) {
			onCalled = true;
		});

		var nodeCount = $('.list-group-item').length;
		var el = $('.list-group-item:first');
		el.trigger('click');
		el = $('.list-group-item:first');

		ok(!el.hasClass('node-selected'), 'Node should not be selected');
		ok(!cbCalled, 'onNodeSelected function should not be called');
		ok(!onCalled, 'nodeSelected should not fire');
		ok(($('.list-group-item').length < nodeCount), 'Number of nodes has decreased, so node must have collapsed');
	});

	test('Checking a node', function () {

		// setup test
		var cbWorked, onWorked = false;
		var $tree = init({
			data: data,
			showCheckbox: true,
			onNodeChecked: function(/*event, date*/) {
				cbWorked = true;
			}
		})
		.on('nodeChecked', function(/*event, date*/) {
			onWorked = true;
		});
		var options = getOptions($tree);

		// simulate click event on check icon
		var $el = $('.check-icon:first');
		$el.trigger('click');

		// check state is correct
		$el = $('.check-icon:first');
		ok(($el.attr('class').indexOf(options.checkedIcon) !== -1), 'Node is checked : icon is correct');
		ok(cbWorked, 'onNodeChecked function was called');
		ok(onWorked, 'nodeChecked was fired');
	});

	test('Unchecking a node', function () {

		// setup test
		var cbWorked, onWorked = false;
		var $tree = init({
			data: data,
			showCheckbox: true,
			onNodeUnchecked: function(/*event, date*/) {
				cbWorked = true;
			}
		})
		.on('nodeUnchecked', function(/*event, date*/) {
			onWorked = true;
		});
		var options = getOptions($tree);

		// first check a node
		var $el = $('.check-icon:first');
		$el.trigger('click');

		// then simulate unchecking a node
		cbWorked = onWorked = false;
		$el = $('.check-icon:first');
		$el.trigger('click');

		// check state is correct
		$el = $('.check-icon:first');
		ok(($el.attr('class').indexOf(options.uncheckedIcon) !== -1), 'Node is unchecked : icon is correct');
		ok(cbWorked, 'onNodeUnchecked function was called');
		ok(onWorked, 'nodeUnchecked was fired');
	});


	module('Methods');

	test('getNode', function () {
		var $tree = init({ data: data });
		var nodeParent1 = $tree.treeview('getNode', 0);
		equal(nodeParent1.text, 'Parent 1', 'Correct node returned : requested "Parent 1", got "Parent 1"');
	});

	test('getParent', function () {
		var $tree = init({ data: data });
		var nodeChild1 = $tree.treeview('getNode', 1);
		var parentNode = $tree.treeview('getParent', nodeChild1);
		equal(parentNode.text, 'Parent 1', 'Correct node returned : requested parent of "Child 1", got "Parent 1"');
	});

	test('getSiblings', function () {
		var $tree = init({ data: data });

		// Test root level, internally uses the this.tree
		var nodeParent1 = $tree.treeview('getNode', 0);
		var nodeParent1Siblings = $tree.treeview('getSiblings', nodeParent1);
		var isArray = (nodeParent1Siblings instanceof Array);
		var countOK = nodeParent1Siblings.length === 4;
		var resultsOK = nodeParent1Siblings[0].text === 'Parent 2';
		resultsOK = resultsOK && nodeParent1Siblings[1].text === 'Parent 3';
		resultsOK = resultsOK && nodeParent1Siblings[2].text === 'Parent 4';
		resultsOK = resultsOK && nodeParent1Siblings[3].text === 'Parent 5';
		ok(isArray, 'Correct siblings for "Parent 1" [root] : is array');
		ok(countOK, 'Correct siblings for "Parent 1" [root] : count OK');
		ok(resultsOK, 'Correct siblings for "Parent 1" [root] : results OK');

		// Test non root level, internally uses getParent.nodes
		var nodeChild1 = $tree.treeview('getNode', 1);
		var nodeChild1Siblings = $tree.treeview('getSiblings', nodeChild1);
		var isArray = (nodeChild1Siblings instanceof Array);
		var countOK = nodeChild1Siblings.length === 1;
		var results = nodeChild1Siblings[0].text === 'Child 2'
		ok(isArray, 'Correct siblings for "Child 1" [non root] : is array');
		ok(countOK, 'Correct siblings for "Child 1" [non root] : count OK');
		ok(results, 'Correct siblings for "Child 1" [non root] : results OK');
	});

	test('getSelected', function () {
		var $tree = init({ data: data })
			.treeview('selectNode', 0);

		var selectedNodes = $tree.treeview('getSelected');
		ok((selectedNodes instanceof Array), 'Result is an array');
		equal(selectedNodes.length, 1, 'Correct number of nodes returned');
		equal(selectedNodes[0].text, 'Parent 1', 'Correct node returned');
	});

	test('getUnselected', function () {
		var $tree = init({ data: data })
			.treeview('selectNode', 0);

		var unselectedNodes = $tree.treeview('getUnselected');
		ok((unselectedNodes instanceof Array), 'Result is an array');
		equal(unselectedNodes.length, 8, 'Correct number of nodes returned');
	});

	// Assumptions:
	// Default tree + expanded to 2 levels,
	// means 1 node 'Parent 1' should be expanded and therefore returned
	test('getExpanded', function () {
		var $tree = init({ data: data });
		var expandedNodes = $tree.treeview('getExpanded');
		ok((expandedNodes instanceof Array), 'Result is an array');
		equal(expandedNodes.length, 1, 'Correct number of nodes returned');
		equal(expandedNodes[0].text, 'Parent 1', 'Correct node returned');
	});

	// Assumptions:
	// Default tree + expanded to 2 levels, means only 'Parent 1' should be expanded
	// as all other parent nodes have no children their state will be collapsed
	// which means 8 of the 9 nodes should be returned
	test('getCollapsed', function () {
		var $tree = init({ data: data });
		var collapsedNodes = $tree.treeview('getCollapsed');
		ok((collapsedNodes instanceof Array), 'Result is an array');
		equal(collapsedNodes.length, 8, 'Correct number of nodes returned');
	});

	test('getChecked', function () {
		var $tree = init({ data: data, showCheckbox: true })
			.treeview('checkNode', 0);

		var checkedNodes = $tree.treeview('getChecked');
		ok((checkedNodes instanceof Array), 'Result is an array');
		equal(checkedNodes.length, 1, 'Correct number of nodes returned');
		equal(checkedNodes[0].text, 'Parent 1', 'Correct node returned');
	});

	test('getUnchecked', function () {
		var $tree = init({ data: data })
			.treeview('checkNode', 0);

		var uncheckedNodes = $tree.treeview('getUnchecked');
		ok((uncheckedNodes instanceof Array), 'Result is an array');
		equal(uncheckedNodes.length, 8, 'Correct number of nodes returned');
	});

	test('getDisabled', function () {
		var $tree = init({ data: data })
			.treeview('disableNode', 0);

		var disabledNodes = $tree.treeview('getDisabled');
		ok((disabledNodes instanceof Array), 'Result is an array');
		equal(disabledNodes.length, 1, 'Correct number of nodes returned');
		equal(disabledNodes[0].text, 'Parent 1', 'Correct node returned');
	});

	test('getEnabled', function () {
		var $tree = init({ data: data })
			.treeview('disableNode', 0);

		var enabledNodes = $tree.treeview('getEnabled');
		ok((enabledNodes instanceof Array), 'Result is an array');
		equal(enabledNodes.length, 8, 'Correct number of nodes returned');
	});

	test('disableAll / enableAll', function () {
		var $tree = init({ data: data, levels: 1 });

		$tree.treeview('disableAll');
		equal($($tree.selector + ' ul li.node-disabled').length, 5, 'Disable all works, 9 nodes with node-disabled class');

		$tree.treeview('enableAll');
		equal($($tree.selector + ' ul li.node-disabled').length, 0, 'Check all works, 9 nodes non with node-disabled class');
	});

	test('disableNode / enableNode', function () {
		var $tree = init({ data: data, levels: 1 });
		var nodeId = 0;
		var node = $tree.treeview('getNode', 0);

		// Disable node using node id
		$tree.treeview('disableNode', nodeId);
		ok($('.list-group-item:first').hasClass('node-disabled'), 'Disable node (by id) : Node has class node-disabled');
		ok(($('.node-disabled').length === 1), 'Disable node (by id) : There is only one disabled node');

		// Enable node using node id
		$tree.treeview('enableNode', nodeId);
		ok(!$('.list-group-item:first').hasClass('node-disabled'), 'Enable node (by id) : Node does not have class node-disabled');
		ok(($('.node-checked').length === 0), 'Enable node (by id) : There are no disabled nodes');

		// Disable node using node
		$tree.treeview('disableNode', node);
		ok($('.list-group-item:first').hasClass('node-disabled'), 'Disable node (by node) : Node has class node-disabled');
		ok(($('.node-disabled').length === 1), 'Disable node (by node) : There is only one disabled node');

		// Enable node using node
		$tree.treeview('enableNode', node);
		ok(!$('.list-group-item:first').hasClass('node-disabled'), 'Enable node (by node) : Node does not have class node-disabled');
		ok(($('.node-checked').length === 0), 'Enable node (by node) : There are no disabled nodes');
	});

	test('toggleNodeDisabled', function () {
		var $tree = init({ data: data, levels: 1 });
		var nodeId = 0;
		var node = $tree.treeview('getNode', 0);

		// Toggle disabled using node id
		$tree.treeview('toggleNodeDisabled', nodeId);
		ok($('.list-group-item:first').hasClass('node-disabled'), 'Toggle node (by id) : Node has class node-disabled');
		ok(($('.node-disabled').length === 1), 'Toggle node (by id) : There is only one disabled node');

		// Toggle disabled using node
		$tree.treeview('toggleNodeDisabled', node);
		ok(!$('.list-group-item:first').hasClass('node-disabled'), 'Toggle node (by node) : Node does not have class node-disabled');
		ok(($('.node-disabled').length === 0), 'Toggle node (by node) : There are no disabled nodes');
	});

	test('checkAll / uncheckAll', function () {
		var $tree = init({ data: data, levels: 3, showCheckbox: true });

		$tree.treeview('checkAll');
		equal($($tree.selector + ' ul li.node-checked').length, 9, 'Check all works, 9 nodes with node-checked class');
		equal($($tree.selector + ' ul li .glyphicon-check').length, 9, 'Check all works, 9 nodes with glyphicon-check icon');

		$tree.treeview('uncheckAll');
		equal($($tree.selector + ' ul li.node-checked').length, 0, 'Check all works, 9 nodes non with node-checked class');
		equal($($tree.selector + ' ul li .glyphicon-unchecked').length, 9, 'Check all works, 9 nodes with glyphicon-unchecked icon');
	});

	test('checkNode / uncheckNode', function () {
		var $tree = init({ data: data, showCheckbox: true });
		var options = getOptions($tree);
		var nodeId = 0;
		var node = $tree.treeview('getNode', 0);

		// Check node using node id
		$tree.treeview('checkNode', nodeId);
		ok($('.list-group-item:first').hasClass('node-checked'), 'Check node (by id) : Node has class node-checked');
		ok(($('.node-checked').length === 1), 'Check node (by id) : There is only one checked node');
		ok($('.check-icon:first').hasClass(options.checkedIcon), 'Check node (by id) : Node icon is correct');

		// Uncheck node using node id
		$tree.treeview('uncheckNode', nodeId);
		ok(!$('.list-group-item:first').hasClass('node-checked'), 'Uncheck node (by id) : Node does not have class node-checked');
		ok(($('.node-checked').length === 0), 'Uncheck node (by id) : There are no checked nodes');
		ok($('.check-icon:first').hasClass(options.uncheckedIcon), 'Uncheck node (by id) : Node icon is correct');

		// Check node using node
		$tree.treeview('checkNode', node);
		ok($('.list-group-item:first').hasClass('node-checked'), 'Check node (by node) : Node has class node-checked');
		ok(($('.node-checked').length === 1), 'Check node (by node) : There is only one checked node');
		ok($('.check-icon:first').hasClass(options.checkedIcon), 'Check node (by node) : Node icon is correct');

		// Uncheck node using node
		$tree.treeview('uncheckNode', node);
		ok(!$('.list-group-item:first').hasClass('node-checked'), 'Uncheck node (by node) : Node does not have class node-checked');
		ok(($('.node-checked').length === 0), 'Uncheck node (by node) : There are no checked nodes');
		ok($('.check-icon:first').hasClass(options.uncheckedIcon), 'Uncheck node (by node) : Node icon is correct');
	});

	test('toggleNodeChecked', function () {
		var $tree = init({ data: data, showCheckbox: true });
		var options = getOptions($tree);
		var nodeId = 0;
		var node = $tree.treeview('getNode', 0);

		// Toggle checked using node id
		$tree.treeview('toggleNodeChecked', nodeId);
		ok($('.list-group-item:first').hasClass('node-checked'), 'Toggle node (by id) : Node has class node-checked');
		ok(($('.node-checked').length === 1), 'Toggle node (by id) : There is only one checked node');
		ok($('.check-icon:first').hasClass(options.checkedIcon), 'Toggle node (by id) : Node icon is correct');

		// Toggle checked using node
		$tree.treeview('toggleNodeChecked', node);
		ok(!$('.list-group-item:first').hasClass('node-checked'), 'Toggle node (by node) : Node does not have class node-checked');
		ok(($('.node-checked').length === 0), 'Toggle node (by node) : There are no checked nodes');
		ok($('.check-icon:first').hasClass(options.uncheckedIcon), 'Toggle node (by node) : Node icon is correct');
	});

	test('selectNode / unselectNode', function () {
		var $tree = init({ data: data, selectedIcon: 'glyphicon glyphicon-selected' });
		var nodeId = 0;
		var node = $tree.treeview('getNode', 0);

		// Select node using node id
		$tree.treeview('selectNode', nodeId);
		ok($('.list-group-item:first').hasClass('node-selected'), 'Select node (by id) : Node has class node-selected');
		ok(($('.node-selected').length === 1), 'Select node (by id) : There is only one selected node');

		// Unselect node using node id
		$tree.treeview('unselectNode', nodeId);
		ok(!$('.list-group-item:first').hasClass('node-selected'), 'Unselect node (by id) : Node does not have class node-selected');
		ok(($('.node-selected').length === 0), 'Unselect node (by id) : There are no selected nodes');

		// Select node using node
		$tree.treeview('selectNode', node);
		ok($('.list-group-item:first').hasClass('node-selected'), 'Select node (by node) : Node has class node-selected');
		ok(($('.node-selected').length === 1), 'Select node (by node) : There is only one selected node');

		// Unselect node using node id
		$tree.treeview('unselectNode', node);
		ok(!$('.list-group-item:first').hasClass('node-selected'), 'Unselect node (by node) : Node does not have class node-selected');
		ok(($('.node-selected').length === 0), 'Unselect node (by node) : There are no selected nodes');
	});

	test('toggleNodeSelected', function () {
		var $tree = init({ data: data });
		var nodeId = 0;
		var node = $tree.treeview('getNode', 0);

		// Toggle selected using node id
		$tree.treeview('toggleNodeSelected', nodeId);
		ok($('.list-group-item:first').hasClass('node-selected'), 'Toggle node (by id) : Node has class node-selected');
		ok(($('.node-selected').length === 1), 'Toggle node (by id) : There is only one selected node');

		// Toggle selected using node
		$tree.treeview('toggleNodeSelected', node);
		ok(!$('.list-group-item:first').hasClass('node-selected'), 'Toggle node (by id) : Node does not have class node-selected');
		ok(($('.node-selected').length === 0), 'Toggle node (by node) : There are no selected nodes');
	});

	test('expandAll / collapseAll', function () {
		var $tree = init({ data: data, levels: 1 });
		equal($($tree.selector + ' ul li').length, 5, 'Starts in collapsed state, 5 root nodes displayed');

		$tree.treeview('expandAll');
		equal($($tree.selector + ' ul li').length, 9, 'Expand all works, all 9 nodes displayed');

		$tree.treeview('collapseAll');
		equal($($tree.selector + ' ul li').length, 5, 'Collapse all works, 5 original root nodes displayed');

		$tree.treeview('expandAll', { levels: 1 });
		equal($($tree.selector + ' ul li').length, 7, 'Expand all (levels = 1) works, correctly displayed 7 nodes');
	});

	test('expandNode / collapseNode / toggleExpanded', function () {
		var $tree = init({ data: data, levels: 1 });
		equal($($tree.selector + ' ul li').length, 5, 'Starts in collapsed state, 5 root nodes displayed');

		$tree.treeview('expandNode', 0);
		equal($($tree.selector + ' ul li').length, 7, 'Expand node (by id) works, 7 nodes displayed');

		$tree.treeview('collapseNode', 0);
		equal($($tree.selector + ' ul li').length, 5, 'Collapse node (by id) works, 5 original nodes displayed');

		$tree.treeview('toggleNodeExpanded', 0);
		equal($($tree.selector + ' ul li').length, 7, 'Toggle node (by id) works, 7 nodes displayed');

		$tree.treeview('toggleNodeExpanded', 0);
		equal($($tree.selector + ' ul li').length, 5, 'Toggle node (by id) works, 5 original nodes displayed');

		$tree.treeview('expandNode', [ 0, { levels: 2 } ]);
		equal($($tree.selector + ' ul li').length, 9, 'Expand node (levels = 2, by id) works, 9 nodes displayed');

		$tree = init({ data: data, levels: 1 });
		equal($($tree.selector + ' ul li').length, 5, 'Reset to collapsed state, 5 root nodes displayed');

		var nodeParent1 = $tree.treeview('getNode', 0);
		$tree.treeview('expandNode', nodeParent1);
		equal($($tree.selector + ' ul li').length, 7, 'Expand node (by node) works, 7 nodes displayed');

		$tree.treeview('collapseNode', nodeParent1);
		equal($($tree.selector + ' ul li').length, 5, 'Collapse node (by node) works, 5 original nodes displayed');

		$tree.treeview('toggleNodeExpanded', nodeParent1);
		equal($($tree.selector + ' ul li').length, 7, 'Toggle node (by node) works, 7 nodes displayed');

		$tree.treeview('toggleNodeExpanded', nodeParent1);
		equal($($tree.selector + ' ul li').length, 5, 'Toggle node (by node) works, 5 original nodes displayed');

		$tree.treeview('expandNode', [ nodeParent1, { levels: 2 } ]);
		equal($($tree.selector + ' ul li').length, 9, 'Expand node (levels = 2, by node) works, 9 nodes displayed');
	});

	test('revealNode', function () {
		var $tree = init({ data: data, levels: 1 });

		$tree.treeview('revealNode', 1); // Child_1
		equal($($tree.selector + ' ul li').length, 7, 'Reveal node (by id) works, reveal Child 1 and 7 nodes displayed');

		var nodeGrandchild1 = $tree.treeview('getNode', 2); // Grandchild 1
		$tree.treeview('revealNode', nodeGrandchild1);
		equal($($tree.selector + ' ul li').length, 9, 'Reveal node (by node) works, reveal Grandchild 1 and 9 nodes displayed');
	});

	test('search', function () {
		var cbWorked, onWorked = false;
		var $tree = init({
			data: data,
			onSearchComplete: function(/*event, results*/) {
				cbWorked = true;
			}
		})
		.on('searchComplete', function(/*event, results*/) {
			onWorked = true;
		});

		// Case sensitive, exact match
		var result = $tree.treeview('search', [ 'Parent 1', { ignoreCase: false, exactMatch: true } ]);
		equal(result.length, 1, 'Search "Parent 1" case sensitive, exact match - returns 1 result');

		// Case sensitive, like
		result = $tree.treeview('search', [ 'Parent', { ignoreCase: false, exactMatch: false } ]);
		equal(result.length, 5, 'Search "Parent" case sensitive, exact match - returns 5 results');

		// Case insensitive, exact match
		result = $tree.treeview('search', [ 'parent 1', { ignoreCase: true, exactMatch: true } ]);
		equal(result.length, 1, 'Search "parent 1" case insensitive, exact match - returns 1 result');

		// Case insensitive, like
		result = $tree.treeview('search', [ 'parent', { ignoreCase: true, exactMatch: false } ]);
		equal(result.length, 5, 'Search "parent" case insensitive, exact match - returns 5 results')

		// Check events fire
		ok(cbWorked, 'onSearchComplete function was called');
		ok(onWorked, 'searchComplete was fired');
	});

	test('clearSearch', function () {
		var cbWorked, onWorked = false;
		var $tree = init({
			data: data,
			onSearchCleared: function(/*event, results*/) {
				cbWorked = true;
			}
		})
		.on('searchCleared', function(/*event, results*/) {
			onWorked = true;
		});

		// Check results are cleared
		$tree.treeview('search', [ 'Parent 1', { ignoreCase: false, exactMatch: true } ]);
		equal($tree.find('.search-result').length, 1, 'Search results highlighted');
		$tree.treeview('clearSearch');
		equal($tree.find('.search-result').length, 0, 'Search results cleared');

		// Check events fire
		ok(cbWorked, 'onSearchCleared function was called');
		ok(onWorked, 'searchCleared was fired');
	});

}());
