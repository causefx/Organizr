(function(jsGrid, $, undefined) {

    var Field = jsGrid.Field;

    function TextField(config) {
        Field.call(this, config);
    }

    TextField.prototype = new Field({

        autosearch: true,
		readOnly: false,

        filterTemplate: function() {
            if(!this.filtering)
                return "";

            var grid = this._grid,
                $result = this.filterControl = this._createTextBox();

            if(this.autosearch) {
                $result.on("keypress", function(e) {
                    if(e.which === 13) {
                        grid.search();
                        e.preventDefault();
                    }
                });
            }

            return $result;
        },

        insertTemplate: function() {
            if(!this.inserting)
                return "";

            return this.insertControl = this._createTextBox();
        },

        editTemplate: function(value) {
            if(!this.editing)
                return this.itemTemplate(value);

            var $result = this.editControl = this._createTextBox();
            $result.val(value);
            return $result;
        },

        filterValue: function() {
            return this.filterControl.val();
        },

        insertValue: function() {
            return this.insertControl.val();
        },

        editValue: function() {
            return this.editControl.val();
        },

        _createTextBox: function() {
            return $("<input>").attr("type", "text")
                .prop("readonly", !!this.readOnly);
        }
    });

    jsGrid.fields.text = jsGrid.TextField = TextField;

}(jsGrid, jQuery));
