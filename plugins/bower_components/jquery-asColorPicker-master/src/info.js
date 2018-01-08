// info

(function($) {
    "use strict";

    $.asColorPicker.registerComponent('info', function() {
        return {
            color: ['white', 'black', 'transparent'],
            init: function(api) {
                var template = '<ul class="' + api.namespace + '-info">' + '<li><label>R:<input type="text" data-type="r"/></label></li>' + '<li><label>G:<input type="text" data-type="g"/></label></li>' + '<li><label>B:<input type="text" data-type="b"/></label></li>' + '<li><label>A:<input type="text" data-type="a"/></label></li>' + '</ul>';
                this.$info = $(template).appendTo(api.$dropdown);
                this.$r = this.$info.find('[data-type="r"]');
                this.$g = this.$info.find('[data-type="g"]');
                this.$b = this.$info.find('[data-type="b"]');
                this.$a = this.$info.find('[data-type="a"]');

                this.$info.delegate('input', 'keyup update change', function(e) {
                    var val;
                    var type = $(e.target).data('type');
                    switch (type) {
                        case 'r':
                        case 'g':
                        case 'b':
                            val = parseInt(this.value, 10);
                            if (val > 255) {
                                val = 255;
                            } else if (val < 0) {
                                val = 0;
                            }
                            break;
                        case 'a':
                            val = parseFloat(this.value, 10);
                            if (val > 1) {
                                val = 1;
                            } else if (val < 0) {
                                val = 0;
                            }
                            break;
                    }
                    if (isNaN(val)) {
                        val = 0;
                    }
                    var color = {};
                    color[type] = val;
                    api.set(color);
                });

                var self = this;
                api.$element.on('asColorPicker::update asColorPicker::setup', function(e, color) {
                    self.update(color);
                });
            },
            update: function(color) {
                this.$r.val(color.value.r);
                this.$g.val(color.value.g);
                this.$b.val(color.value.b);
                this.$a.val(color.value.a);
            }
        };
    });
})(jQuery);
