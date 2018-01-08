$(function() {

        var defaultData = [
          {
            text: 'Parent 1',
            href: '#parent1',
            tags: ['4'],
            nodes: [
              {
                text: 'Child 1',
                href: '#child1',
                tags: ['2'],
                nodes: [
                  {
                    text: 'Grandchild 1',
                    href: '#grandchild1',
                    tags: ['0']
                  },
                  {
                    text: 'Grandchild 2',
                    href: '#grandchild2',
                    tags: ['0']
                  }
                ]
              },
              {
                text: 'Child 2',
                href: '#child2',
                tags: ['0']
              }
            ]
          },
          {
            text: 'Parent 2',
            href: '#parent2',
            tags: ['0']
          },
          {
            text: 'Parent 3',
            href: '#parent3',
             tags: ['0']
          },
          {
            text: 'Parent 4',
            href: '#parent4',
            tags: ['0']
          },
          {
            text: 'Parent 5',
            href: '#parent5'  ,
            tags: ['0']
          }
        ];

        var alternateData = [
          {
            text: 'Parent 1',
            tags: ['2'],
            nodes: [
              {
                text: 'Child 1',
                tags: ['3'],
                nodes: [
                  {
                    text: 'Grandchild 1',
                    tags: ['6']
                  },
                  {
                    text: 'Grandchild 2',
                    tags: ['3']
                  }
                ]
              },
              {
                text: 'Child 2',
                tags: ['3']
              }
            ]
          },
          {
            text: 'Parent 2',
            tags: ['7']
          },
          {
            text: 'Parent 3',
            icon: 'glyphicon glyphicon-earphone',
            href: '#demo',
            tags: ['11']
          },
          {
            text: 'Parent 4',
            icon: 'glyphicon glyphicon-cloud-download',
            href: '/demo.html',
            tags: ['19'],
            selected: true
          },
          {
            text: 'Parent 5',
            icon: 'glyphicon glyphicon-certificate',
            color: 'pink',
            backColor: 'red',
            href: 'http://www.tesco.com',
            tags: ['available','0']
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


        $('#treeview1').treeview({
          selectedBackColor: "#03a9f3",
          onhoverColor: "rgba(0, 0, 0, 0.05)",
          expandIcon: 'ti-plus',
          collapseIcon: 'ti-minus',
          nodeIcon: 'fa fa-folder',
          data: defaultData
        });

        $('#treeview2').treeview({
          levels: 1,
          selectedBackColor: "#03a9f3",
          onhoverColor: "rgba(0, 0, 0, 0.05)",
          expandIcon: 'ti-plus',
          collapseIcon: 'ti-minus',
          nodeIcon: 'fa fa-folder',
          data: defaultData
        });

        $('#treeview3').treeview({
          levels: 99,
          selectedBackColor: "#03a9f3",
          onhoverColor: "rgba(0, 0, 0, 0.05)",
          expandIcon: 'ti-plus',
          collapseIcon: 'ti-minus',
          nodeIcon: 'fa fa-folder',
          data: defaultData
        });

        $('#treeview4').treeview({

          color: "#428bca",
          selectedBackColor: "#03a9f3",
          onhoverColor: "rgba(0, 0, 0, 0.05)",
          expandIcon: 'ti-plus',
          collapseIcon: 'ti-minus',
          nodeIcon: 'fa fa-folder',
          data: defaultData
        });

        $('#treeview5').treeview({
         
          expandIcon: 'ti-angle-right',
          onhoverColor: "rgba(0, 0, 0, 0.05)",
          selectedBackColor: "#03a9f3",
          collapseIcon: 'ti-angle-down',
          nodeIcon: 'glyphicon glyphicon-bookmark',
          data: defaultData
        });

        $('#treeview6').treeview({
         selectedBackColor: "#03a9f3",
         onhoverColor: "rgba(0, 0, 0, 0.05)",
          expandIcon: "glyphicon glyphicon-stop",
          collapseIcon: "glyphicon glyphicon-unchecked",
          nodeIcon: "glyphicon glyphicon-user",
          showTags: true,
          data: defaultData
        });

        $('#treeview7').treeview({
          color: "#428bca",
          showBorder: false,
          data: defaultData
        });

        $('#treeview8').treeview({
          expandIcon: "glyphicon glyphicon-stop",
          collapseIcon: "glyphicon glyphicon-unchecked",
          nodeIcon: "glyphicon glyphicon-user",
          color: "yellow",
          backColor: "purple",
          onhoverColor: "orange",
          borderColor: "red",
          showBorder: false,
          showTags: true,
          highlightSelected: true,
          selectedColor: "yellow",
          selectedBackColor: "darkorange",
          data: defaultData
        });

        $('#treeview9').treeview({
          expandIcon: "glyphicon glyphicon-stop",
          collapseIcon: "glyphicon glyphicon-unchecked",
          nodeIcon: "glyphicon glyphicon-user",
          color: "yellow",
          backColor: "purple",
          onhoverColor: "orange",
          borderColor: "red",
          showBorder: false,
          showTags: true,
          highlightSelected: true,
          selectedColor: "yellow",
          selectedBackColor: "darkorange",
          data: alternateData
        });

        $('#treeview10').treeview({
          color: "#428bca",
          enableLinks: true,
          data: defaultData
        });



        var $searchableTree = $('#treeview-searchable').treeview({
          selectedBackColor: "#03a9f3",
          onhoverColor: "rgba(0, 0, 0, 0.05)",
            expandIcon: 'ti-plus',
            collapseIcon: 'ti-minus',
            nodeIcon: 'fa fa-folder',
          data: defaultData,
        });

        var search = function(e) {
          var pattern = $('#input-search').val();
          var options = {
            ignoreCase: $('#chk-ignore-case').is(':checked'),
            exactMatch: $('#chk-exact-match').is(':checked'),
            revealResults: $('#chk-reveal-results').is(':checked')
          };
          var results = $searchableTree.treeview('search', [ pattern, options ]);

          var output = '<p>' + results.length + ' matches found</p>';
          $.each(results, function (index, result) {
            output += '<p>- ' + result.text + '</p>';
          });
          $('#search-output').html(output);
        }

        $('#btn-search').on('click', search);
        $('#input-search').on('keyup', search);

        $('#btn-clear-search').on('click', function (e) {
          $searchableTree.treeview('clearSearch');
          $('#input-search').val('');
          $('#search-output').html('');
        });


        var initSelectableTree = function() {
          return $('#treeview-selectable').treeview({
            
            data: defaultData,
            multiSelect: $('#chk-select-multi').is(':checked'),
            onNodeSelected: function(event, node) {
              $('#selectable-output').prepend('<p>' + node.text + ' was selected</p>');
            },
            onNodeUnselected: function (event, node) {
              $('#selectable-output').prepend('<p>' + node.text + ' was unselected</p>');
            }
          });
        };
        var $selectableTree = initSelectableTree();

        var findSelectableNodes = function() {
          return $selectableTree.treeview('search', [ $('#input-select-node').val(), { ignoreCase: false, exactMatch: false } ]);
        };
        var selectableNodes = findSelectableNodes();

        $('#chk-select-multi:checkbox').on('change', function () {
          console.log('multi-select change');
          $selectableTree = initSelectableTree();
          selectableNodes = findSelectableNodes();          
        });

        // Select/unselect/toggle nodes
        $('#input-select-node').on('keyup', function (e) {
          selectableNodes = findSelectableNodes();
          $('.select-node').prop('disabled', !(selectableNodes.length >= 1));
        });

        $('#btn-select-node.select-node').on('click', function (e) {
          $selectableTree.treeview('selectNode', [ selectableNodes, { silent: $('#chk-select-silent').is(':checked') }]);
        });

        $('#btn-unselect-node.select-node').on('click', function (e) {
          $selectableTree.treeview('unselectNode', [ selectableNodes, { silent: $('#chk-select-silent').is(':checked') }]);
        });

        $('#btn-toggle-selected.select-node').on('click', function (e) {
          $selectableTree.treeview('toggleNodeSelected', [ selectableNodes, { silent: $('#chk-select-silent').is(':checked') }]);
        });



        var $expandibleTree = $('#treeview-expandible').treeview({
          data: defaultData,
          onNodeCollapsed: function(event, node) {
            $('#expandible-output').prepend('<p>' + node.text + ' was collapsed</p>');
          },
          onNodeExpanded: function (event, node) {
            $('#expandible-output').prepend('<p>' + node.text + ' was expanded</p>');
          }
        });

        var findExpandibleNodess = function() {
          return $expandibleTree.treeview('search', [ $('#input-expand-node').val(), { ignoreCase: false, exactMatch: false } ]);
        };
        var expandibleNodes = findExpandibleNodess();

        // Expand/collapse/toggle nodes
        $('#input-expand-node').on('keyup', function (e) {
          expandibleNodes = findExpandibleNodess();
          $('.expand-node').prop('disabled', !(expandibleNodes.length >= 1));
        });

        $('#btn-expand-node.expand-node').on('click', function (e) {
          var levels = $('#select-expand-node-levels').val();
          $expandibleTree.treeview('expandNode', [ expandibleNodes, { levels: levels, silent: $('#chk-expand-silent').is(':checked') }]);
        });

        $('#btn-collapse-node.expand-node').on('click', function (e) {
          $expandibleTree.treeview('collapseNode', [ expandibleNodes, { silent: $('#chk-expand-silent').is(':checked') }]);
        });

        $('#btn-toggle-expanded.expand-node').on('click', function (e) {
          $expandibleTree.treeview('toggleNodeExpanded', [ expandibleNodes, { silent: $('#chk-expand-silent').is(':checked') }]);
        });

        // Expand/collapse all
        $('#btn-expand-all').on('click', function (e) {
          var levels = $('#select-expand-all-levels').val();
          $expandibleTree.treeview('expandAll', { levels: levels, silent: $('#chk-expand-silent').is(':checked') });
        });

        $('#btn-collapse-all').on('click', function (e) {
          $expandibleTree.treeview('collapseAll', { silent: $('#chk-expand-silent').is(':checked') });
        });



        var $checkableTree = $('#treeview-checkable').treeview({
          data: defaultData,
          showIcon: false,
          showCheckbox: true,
          onNodeChecked: function(event, node) {
            $('#checkable-output').prepend('<p>' + node.text + ' was checked</p>');
          },
          onNodeUnchecked: function (event, node) {
            $('#checkable-output').prepend('<p>' + node.text + ' was unchecked</p>');
          }
        });

        var findCheckableNodess = function() {
          return $checkableTree.treeview('search', [ $('#input-check-node').val(), { ignoreCase: false, exactMatch: false } ]);
        };
        var checkableNodes = findCheckableNodess();

        // Check/uncheck/toggle nodes
        $('#input-check-node').on('keyup', function (e) {
          checkableNodes = findCheckableNodess();
          $('.check-node').prop('disabled', !(checkableNodes.length >= 1));
        });

        $('#btn-check-node.check-node').on('click', function (e) {
          $checkableTree.treeview('checkNode', [ checkableNodes, { silent: $('#chk-check-silent').is(':checked') }]);
        });

        $('#btn-uncheck-node.check-node').on('click', function (e) {
          $checkableTree.treeview('uncheckNode', [ checkableNodes, { silent: $('#chk-check-silent').is(':checked') }]);
        });

        $('#btn-toggle-checked.check-node').on('click', function (e) {
          $checkableTree.treeview('toggleNodeChecked', [ checkableNodes, { silent: $('#chk-check-silent').is(':checked') }]);
        });

        // Check/uncheck all
        $('#btn-check-all').on('click', function (e) {
          $checkableTree.treeview('checkAll', { silent: $('#chk-check-silent').is(':checked') });
        });

        $('#btn-uncheck-all').on('click', function (e) {
          $checkableTree.treeview('uncheckAll', { silent: $('#chk-check-silent').is(':checked') });
        });



        var $disabledTree = $('#treeview-disabled').treeview({
          data: defaultData,
          onNodeDisabled: function(event, node) {
            $('#disabled-output').prepend('<p>' + node.text + ' was disabled</p>');
          },
          onNodeEnabled: function (event, node) {
            $('#disabled-output').prepend('<p>' + node.text + ' was enabled</p>');
          },
          onNodeCollapsed: function(event, node) {
            $('#disabled-output').prepend('<p>' + node.text + ' was collapsed</p>');
          },
          onNodeUnchecked: function (event, node) {
            $('#disabled-output').prepend('<p>' + node.text + ' was unchecked</p>');
          },
          onNodeUnselected: function (event, node) {
            $('#disabled-output').prepend('<p>' + node.text + ' was unselected</p>');
          }
        });

        var findDisabledNodes = function() {
          return $disabledTree.treeview('search', [ $('#input-disable-node').val(), { ignoreCase: false, exactMatch: false } ]);
        };
        var disabledNodes = findDisabledNodes();

        // Expand/collapse/toggle nodes
        $('#input-disable-node').on('keyup', function (e) {
          disabledNodes = findDisabledNodes();
          $('.disable-node').prop('disabled', !(disabledNodes.length >= 1));
        });

        $('#btn-disable-node.disable-node').on('click', function (e) {
          $disabledTree.treeview('disableNode', [ disabledNodes, { silent: $('#chk-disable-silent').is(':checked') }]);
        });

        $('#btn-enable-node.disable-node').on('click', function (e) {
          $disabledTree.treeview('enableNode', [ disabledNodes, { silent: $('#chk-disable-silent').is(':checked') }]);
        });

        $('#btn-toggle-disabled.disable-node').on('click', function (e) {
          $disabledTree.treeview('toggleNodeDisabled', [ disabledNodes, { silent: $('#chk-disable-silent').is(':checked') }]);
        });

        // Expand/collapse all
        $('#btn-disable-all').on('click', function (e) {
          $disabledTree.treeview('disableAll', { silent: $('#chk-disable-silent').is(':checked') });
        });

        $('#btn-enable-all').on('click', function (e) {
          $disabledTree.treeview('enableAll', { silent: $('#chk-disable-silent').is(':checked') });
        });



        var $tree = $('#treeview12').treeview({
          data: json
        });
  		});