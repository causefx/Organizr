// buttons
(function($) {
    "use strict";

    $.asColorPicker.registerComponent('buttons', function() {
        return {
            defaults: {
                apply: false,
                cancel: true,
                applyText: 'apply',
                cancelText: 'cancel',
                template: function(namespace) {
                    return '<div class="' + namespace + '-buttons"></div>';
                },
                applyTemplate: function(namespace) {
                    return '<a href="#" alt="' + this.options.applyText + '" class="' + namespace + '-buttons-apply">' + this.options.applyText + '</a>';
                },
                cancelTemplate: function(namespace) {
                    return '<a href="#" alt="' + this.options.cancelText + '" class="' + namespace + '-buttons-apply">' + this.options.cancelText + '</a>';
                }
            },
            init: function(api, options) {
                var self = this;

                this.options = $.extend(this.defaults, options);
                this.$buttons = $(this.options.template.call(this, api.namespace)).appendTo(api.$dropdown);

                api.$element.on('asColorPicker::firstOpen', function() {
                    if (self.options.apply) {
                        self.$apply = $(self.options.applyTemplate.call(self, api.namespace)).appendTo(self.$buttons).on('click', function() {
                            api.apply();
                            return false;
                        });
                    }

                    if (self.options.cancel) {
                        self.$cancel = $(self.options.cancelTemplate.call(self, api.namespace)).appendTo(self.$buttons).on('click', function() {
                            api.cancel();
                            return false;
                        });
                    }
                });
            }
        };
    });
})(jQuery);
