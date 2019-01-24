// hex

(function($) {
    "use strict";

    $.asColorPicker.registerComponent('hex', function() {
        return {
            init: function(api) {
                var template = '<input type="text" class="' + api.namespace + '-hex" />';
                this.$hex = $(template).appendTo(api.$dropdown);

                this.$hex.on('change', function() {
                    api.set(this.value);
                });

                var self = this;
                api.$element.on('asColorPicker::update asColorPicker::setup', function(e, api, color) {
                    self.update(color);
                });
            },
            update: function(color) {
                this.$hex.val(color.toHEX());
            }
        };
    });
})(jQuery);
