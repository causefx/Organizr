$(function() {

    var Grid = jsGrid.Grid;

    module("common field config", {
        setup: function() {
            this.isFieldExcluded = function(FieldClass) {
                return FieldClass === jsGrid.ControlField;
            };
        }
    });

    test("filtering=false prevents rendering filter template", function() {
        var isFieldExcluded = this.isFieldExcluded;

        $.each(jsGrid.fields, function(name, FieldClass) {
            if(isFieldExcluded(FieldClass))
                return;

            var field = new FieldClass({ filtering: false });

            equal(field.filterTemplate(), "", "empty filter template for field " + name);
        });
    });

    test("inserting=false prevents rendering insert template", function() {
        var isFieldExcluded = this.isFieldExcluded;

        $.each(jsGrid.fields, function(name, FieldClass) {
            if(isFieldExcluded(FieldClass))
                return;

            var field = new FieldClass({ inserting: false });

            equal(field.insertTemplate(), "", "empty insert template for field " + name);
        });
    });

    test("editing=false renders itemTemplate", function() {
        var isFieldExcluded = this.isFieldExcluded;

        $.each(jsGrid.fields, function(name, FieldClass) {
            if(isFieldExcluded(FieldClass))
                return;

            var field = new FieldClass({ editing: false });

            var editTemplate = field.editTemplate("test");
            var itemTemplate = field.itemTemplate("test");

            var editTemplateContent = editTemplate instanceof jQuery ? editTemplate[0].outerHTML : editTemplate;
            var itemTemplateContent = itemTemplate instanceof jQuery ? itemTemplate[0].outerHTML : itemTemplate;

            equal(editTemplateContent, itemTemplateContent, "item template is rendered instead of edit template for field " + name);
        });
    });

    module("jsGrid.field");

    test("basic", function() {
        var customSortingFunc = function() {
                return 1;
            },
            field = new jsGrid.Field({
                name: "testField",
                title: "testTitle",
                sorter: customSortingFunc
            });

        equal(field.headerTemplate(), "testTitle");
        equal(field.itemTemplate("testValue"), "testValue");
        equal(field.filterTemplate(), "");
        equal(field.insertTemplate(), "");
        equal(field.editTemplate("testValue"), "testValue");
        strictEqual(field.filterValue(), "");
        strictEqual(field.insertValue(), "");
        strictEqual(field.editValue(), "testValue");
        strictEqual(field.sortingFunc, customSortingFunc);
    });


    module("jsGrid.field.text");

    test("basic", function() {
        var field = new jsGrid.TextField({ name: "testField" });

        equal(field.itemTemplate("testValue"), "testValue");
        equal(field.filterTemplate()[0].tagName.toLowerCase(), "input");
        equal(field.insertTemplate()[0].tagName.toLowerCase(), "input");
        equal(field.editTemplate("testEditValue")[0].tagName.toLowerCase(), "input");
        strictEqual(field.filterValue(), "");
        strictEqual(field.insertValue(), "");
        strictEqual(field.editValue(), "testEditValue");
    });

    test("set default field options with setDefaults", function() {
        jsGrid.setDefaults("text", {
            defaultOption: "test"
        });

        var $element = $("#jsGrid").jsGrid({
            fields: [{ type: "text" }]
        });

        equal($element.jsGrid("option", "fields")[0].defaultOption, "test", "default field option set");
    });


    module("jsGrid.field.number");

    test("basic", function() {
        var field = new jsGrid.NumberField({ name: "testField" });

        equal(field.itemTemplate(5), "5");
        equal(field.filterTemplate()[0].tagName.toLowerCase(), "input");
        equal(field.insertTemplate()[0].tagName.toLowerCase(), "input");
        equal(field.editTemplate(6)[0].tagName.toLowerCase(), "input");
        strictEqual(field.filterValue(), 0);
        strictEqual(field.insertValue(), 0);
        strictEqual(field.editValue(), 6);
    });


    module("jsGrid.field.textArea");

    test("basic", function() {
        var field = new jsGrid.TextAreaField({ name: "testField" });

        equal(field.itemTemplate("testValue"), "testValue");
        equal(field.filterTemplate()[0].tagName.toLowerCase(), "input");
        equal(field.insertTemplate()[0].tagName.toLowerCase(), "textarea");
        equal(field.editTemplate("testEditValue")[0].tagName.toLowerCase(), "textarea");
        strictEqual(field.insertValue(), "");
        strictEqual(field.editValue(), "testEditValue");
    });


    module("jsGrid.field.checkbox");

    test("basic", function() {
        var field = new jsGrid.CheckboxField({ name: "testField" }),
            itemTemplate,
            filterTemplate,
            insertTemplate,
            editTemplate;

        itemTemplate = field.itemTemplate("testValue");
        equal(itemTemplate[0].tagName.toLowerCase(), "input");
        equal(itemTemplate.attr("type"), "checkbox");
        equal(itemTemplate.attr("disabled"), "disabled");

        filterTemplate = field.filterTemplate();
        equal(filterTemplate[0].tagName.toLowerCase(), "input");
        equal(filterTemplate.attr("type"), "checkbox");
        equal(filterTemplate.prop("indeterminate"), true);

        insertTemplate = field.insertTemplate();
        equal(insertTemplate[0].tagName.toLowerCase(), "input");
        equal(insertTemplate.attr("type"), "checkbox");

        editTemplate = field.editTemplate(true);
        equal(editTemplate[0].tagName.toLowerCase(), "input");
        equal(editTemplate.attr("type"), "checkbox");
        equal(editTemplate.is(":checked"), true);

        strictEqual(field.filterValue(), undefined);
        strictEqual(field.insertValue(), false);
        strictEqual(field.editValue(), true);
    });


    module("jsGrid.field.select");

    test("basic", function() {
        var field,
            filterTemplate,
            insertTemplate,
            editTemplate;

        field = new jsGrid.SelectField({
            name: "testField",
            items: ["test1", "test2", "test3"],
            selectedIndex: 1
        });

        equal(field.itemTemplate(1), "test2");

        filterTemplate = field.filterTemplate();
        equal(filterTemplate[0].tagName.toLowerCase(), "select");
        equal(filterTemplate.children().length, 3);

        insertTemplate = field.insertTemplate();
        equal(insertTemplate[0].tagName.toLowerCase(), "select");
        equal(insertTemplate.children().length, 3);

        editTemplate = field.editTemplate(2);
        equal(editTemplate[0].tagName.toLowerCase(), "select");
        equal(editTemplate.find("option:selected").length, 1);
        ok(editTemplate.children().eq(2).is(":selected"));

        strictEqual(field.filterValue(), 1);
        strictEqual(field.insertValue(), 1);
        strictEqual(field.editValue(), 2);
    });

    test("items as array of integers", function() {
        var field,
            filterTemplate,
            insertTemplate,
            editTemplate;

        field = new jsGrid.SelectField({
            name: "testField",
            items: [0, 10, 20],
            selectedIndex: 0
        });

        strictEqual(field.itemTemplate(0), 0);

        filterTemplate = field.filterTemplate();
        equal(filterTemplate[0].tagName.toLowerCase(), "select");
        equal(filterTemplate.children().length, 3);

        insertTemplate = field.insertTemplate();
        equal(insertTemplate[0].tagName.toLowerCase(), "select");
        equal(insertTemplate.children().length, 3);

        editTemplate = field.editTemplate(1);
        equal(editTemplate[0].tagName.toLowerCase(), "select");
        equal(editTemplate.find("option:selected").length, 1);
        ok(editTemplate.children().eq(1).is(":selected"));

        strictEqual(field.filterValue(), 0);
        strictEqual(field.insertValue(), 0);
        strictEqual(field.editValue(), 1);
    });

    test("string value type", function() {
        var field = new jsGrid.SelectField({
            name: "testField",
            items: [
                { text: "test1", value: "1" },
                { text: "test2", value: "2" },
                { text: "test3", value: "3" }
            ],
            textField: "text",
            valueField: "value",
            valueType: "string",
            selectedIndex: 1
        });

        field.filterTemplate();
        strictEqual(field.filterValue(), "2");

        field.editTemplate("2");
        strictEqual(field.editValue(), "2");

        field.insertTemplate();
        strictEqual(field.insertValue(), "2");
    });

    test("value type auto-defined", function() {
        var field = new jsGrid.SelectField({
            name: "testField",
            items: [
                { text: "test1", value: "1" },
                { text: "test2", value: "2" },
                { text: "test3", value: "3" }
            ],
            textField: "text",
            valueField: "value",
            selectedIndex: 1
        });

        strictEqual(field.sorter, "string", "sorter set according to value type");

        field.filterTemplate();
        strictEqual(field.filterValue(), "2");

        field.editTemplate("2");
        strictEqual(field.editValue(), "2");

        field.insertTemplate();
        strictEqual(field.insertValue(), "2");
    });

    test("object items", function() {
        var field = new jsGrid.SelectField({
            name: "testField",
            items: [
                { text: "test1", value: 1 },
                { text: "test2", value: 2 },
                { text: "test3", value: 3 }
            ]
        });

        strictEqual(field.itemTemplate(1), field.items[1]);

        field.textField = "text";
        strictEqual(field.itemTemplate(1), "test2");

        field.textField = "";
        field.valueField = "value";
        strictEqual(field.itemTemplate(1), field.items[0]);
        ok(field.editTemplate(2));
        strictEqual(field.editValue(), 2);

        field.textField = "text";
        strictEqual(field.itemTemplate(1), "test1");
    });


    module("jsGrid.field.control");

    test("basic", function() {
        var field,
            itemTemplate,
            headerTemplate,
            filterTemplate,
            insertTemplate,
            editTemplate;

        field = new jsGrid.ControlField();
        field._grid = {
            filtering: true,
            inserting: true,
            option: $.noop
        };

        itemTemplate = field.itemTemplate("any_value");
        equal(itemTemplate.filter("." + field.editButtonClass).length, 1);
        equal(itemTemplate.filter("." + field.deleteButtonClass).length, 1);

        headerTemplate = field.headerTemplate();
        equal(headerTemplate.filter("." + field.insertModeButtonClass).length, 1);

        var $modeSwitchButton = headerTemplate.filter("." + field.modeButtonClass);
        $modeSwitchButton.trigger("click");

        equal(headerTemplate.filter("." + field.searchModeButtonClass).length, 1);

        filterTemplate = field.filterTemplate();
        equal(filterTemplate.filter("." + field.searchButtonClass).length, 1);
        equal(filterTemplate.filter("." + field.clearFilterButtonClass).length, 1);

        insertTemplate = field.insertTemplate();
        equal(insertTemplate.filter("." + field.insertButtonClass).length, 1);

        editTemplate = field.editTemplate("any_value");
        equal(editTemplate.filter("." + field.updateButtonClass).length, 1);
        equal(editTemplate.filter("." + field.cancelEditButtonClass).length, 1);

        strictEqual(field.filterValue(), "");
        strictEqual(field.insertValue(), "");
        strictEqual(field.editValue(), "");
    });

    test("switchMode button should consider filtering=false", function() {
        var optionArgs = {};

        var field = new jsGrid.ControlField();
        field._grid = {
            filtering: false,
            inserting: true,
            option: function(name, value) {
                optionArgs = {
                    name: name,
                    value: value
                };
            }
        };

        var headerTemplate = field.headerTemplate();
        equal(headerTemplate.filter("." + field.insertModeButtonClass).length, 1, "inserting switch button rendered");

        var $modeSwitchButton = headerTemplate.filter("." + field.modeButtonClass);

        $modeSwitchButton.trigger("click");
        ok($modeSwitchButton.hasClass(field.modeOnButtonClass), "on class is attached");
        equal(headerTemplate.filter("." + field.insertModeButtonClass).length, 1, "insert button rendered");
        equal(headerTemplate.filter("." + field.searchModeButtonClass).length, 0, "search button not rendered");
        deepEqual(optionArgs, { name: "inserting", value: true }, "turn on grid inserting mode");

        $modeSwitchButton.trigger("click");
        ok(!$modeSwitchButton.hasClass(field.modeOnButtonClass), "on class is detached");
        deepEqual(optionArgs, { name: "inserting", value: false }, "turn off grid inserting mode");
    });

    test("switchMode button should consider inserting=false", function() {
        var optionArgs = {};

        var field = new jsGrid.ControlField();
        field._grid = {
            filtering: true,
            inserting: false,
            option: function(name, value) {
                optionArgs = {
                    name: name,
                    value: value
                };
            }
        };

        var headerTemplate = field.headerTemplate();
        equal(headerTemplate.filter("." + field.searchModeButtonClass).length, 1, "filtering switch button rendered");

        var $modeSwitchButton = headerTemplate.filter("." + field.modeButtonClass);

        $modeSwitchButton.trigger("click");
        ok(!$modeSwitchButton.hasClass(field.modeOnButtonClass), "on class is detached");
        equal(headerTemplate.filter("." + field.searchModeButtonClass).length, 1, "search button rendered");
        equal(headerTemplate.filter("." + field.insertModeButtonClass).length, 0, "insert button not rendered");
        deepEqual(optionArgs, { name: "filtering", value: false }, "turn off grid filtering mode");

        $modeSwitchButton.trigger("click");
        ok($modeSwitchButton.hasClass(field.modeOnButtonClass), "on class is attached");
        deepEqual(optionArgs, { name: "filtering", value: true }, "turn on grid filtering mode");
    });

    test("switchMode is not rendered if inserting=false and filtering=false", function() {
        var optionArgs = {};

        var field = new jsGrid.ControlField();
        field._grid = {
            filtering: false,
            inserting: false
        };

        var headerTemplate = field.headerTemplate();
        strictEqual(headerTemplate, "", "empty header");
    });

});
