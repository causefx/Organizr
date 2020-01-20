(function(jsGrid, $, undefined) {

    var TextField = jsGrid.TextField;

    function TextAreaField(config) {
        TextField.call(this, config);
    }

    TextAreaField.prototype = new TextField({

        insertTemplate: function() {
            if(!this.inserting)
                return "";

            return this.insertControl = this._createTextArea();
        },

        editTemplate: function(value) {
            if(!this.editing)
                return this.itemTemplate(value);

            var $result = this.editControl = this._createTextArea();
            $result.val(value);
            return $result;
        },

        _createTextArea: function() {
            return $("<textarea>").prop("readonly", !!this.readOnly);
        }
    });

    jsGrid.fields.textarea = jsGrid.TextAreaField = TextAreaField;

}(jsGrid, jQuery));
