// saturation

(function($) {
    "use strict";

    $.asColorPicker.registerComponent('saturation', function() {
        return {
            defaults: {
                template: function(namespace) {
                    return '<div class="' + namespace + '-saturation"><i><b></b></i></div>';
                }
            },
            width: 0,
            height: 0,
            size: 6,
            data: {},
            init: function(api, options) {
                var self = this;
                this.options = $.extend(this.defaults, options),
                    this.api = api;

                //build element and add component to picker
                this.$saturation = $(this.options.template.call(self, api.namespace)).appendTo(api.$dropdown);
                this.$handle = this.$saturation.find('i');

                api.$element.on('asColorPicker::firstOpen', function() {
                    // init variable
                    self.width = self.$saturation.width();
                    self.height = self.$saturation.height();
                    self.step = {
                        left: self.width / 20,
                        top: self.height / 20
                    };
                    self.size = self.$handle.width() / 2;

                    // bind events
                    self.bindEvents();
                    self.keyboard(api);
                });

                api.$element.on('asColorPicker::update asColorPicker::setup', function(e, api, color) {
                    self.update(color);
                });

            },
            bindEvents: function() {
                var self = this;

                this.$saturation.on('mousedown.asColorPicker', function(e) {
                    var rightclick = (e.which) ? (e.which === 3) : (e.button === 2);
                    if (rightclick) {
                        return false;
                    }
                    self.mousedown(e);
                });
            },
            mousedown: function(e) {
                var offset = this.$saturation.offset();

                this.data.startY = e.pageY;
                this.data.startX = e.pageX;
                this.data.top = e.pageY - offset.top;
                this.data.left = e.pageX - offset.left;
                this.data.cach = {};

                this.move(this.data.left, this.data.top);

                this.mousemove = function(e) {
                    var x = this.data.left + (e.pageX || this.data.startX) - this.data.startX;
                    var y = this.data.top + (e.pageY || this.data.startY) - this.data.startY;
                    this.move(x, y);
                    return false;
                };

                this.mouseup = function() {
                    $(document).off({
                        mousemove: this.mousemove,
                        mouseup: this.mouseup
                    });
                    this.data.left = this.data.cach.left;
                    this.data.top = this.data.cach.top;

                    return false;
                };

                $(document).on({
                    mousemove: $.proxy(this.mousemove, this),
                    mouseup: $.proxy(this.mouseup, this)
                });

                return false;
            },
            move: function(x, y, update) {
                y = Math.max(0, Math.min(this.height, y));
                x = Math.max(0, Math.min(this.width, x));

                if (this.data.cach === undefined) {
                    this.data.cach = {};
                }
                this.data.cach.left = x;
                this.data.cach.top = y;

                this.$handle.css({
                    top: y - this.size,
                    left: x - this.size
                });

                if (update !== false) {
                    this.api.set({
                        s: x / this.width,
                        v: 1 - (y / this.height)
                    });
                }
            },
            update: function(color) {
                if (color.value.h === undefined) {
                    color.value.h = 0;
                }
                this.$saturation.css('backgroundColor', $.asColor.HSLToHEX({
                    h: color.value.h,
                    s: 1,
                    l: 0.5
                }));

                var x = color.value.s * this.width;
                var y = (1 - color.value.v) * this.height;

                this.move(x, y, false);
            },
            moveLeft: function() {
                var step = this.step.left,
                    data = this.data;
                data.left = Math.max(0, Math.min(this.width, data.left - step));
                this.move(data.left, data.top);
            },
            moveRight: function() {
                var step = this.step.left,
                    data = this.data;
                data.left = Math.max(0, Math.min(this.width, data.left + step));
                this.move(data.left, data.top);
            },
            moveUp: function() {
                var step = this.step.top,
                    data = this.data;
                data.top = Math.max(0, Math.min(this.width, data.top - step));
                this.move(data.left, data.top);
            },
            moveDown: function() {
                var step = this.step.top,
                    data = this.data;
                data.top = Math.max(0, Math.min(this.width, data.top + step));
                this.move(data.left, data.top);
            },
            keyboard: function() {
                var keyboard, self = this;
                if (this.api._keyboard) {
                    keyboard = $.extend(true, {}, this.api._keyboard);
                } else {
                    return false;
                }

                this.$saturation.attr('tabindex', '0').on('focus', function() {
                    keyboard.attach({
                        left: function() {
                            self.moveLeft();
                        },
                        right: function() {
                            self.moveRight();
                        },
                        up: function() {
                            self.moveUp();
                        },
                        down: function() {
                            self.moveDown();
                        }
                    });
                    return false;
                }).on('blur', function() {
                    keyboard.detach();
                });
            },
            destroy: function() {
                $(document).off({
                    mousemove: this.mousemove,
                    mouseup: this.mouseup
                });
            }
        };
    });
})(jQuery);
