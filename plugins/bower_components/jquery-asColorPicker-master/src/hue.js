// hue
(function($) {
    "use strict";

    $.asColorPicker.registerComponent('hue', function() {
        return {
            size: 150,
            defaults: {
                direction: 'vertical', // horizontal
                template: function() {
                    var namespace = this.api.namespace;
                    return '<div class="' + namespace + '-hue ' + namespace + '-hue-' + this.direction + '"><i></i></div>';
                }
            },
            data: {},
            init: function(api, options) {
                var self = this;

                this.options = $.extend(this.defaults, options);
                this.direction = this.options.direction;
                this.api = api;

                this.$hue = $(this.options.template.call(self)).appendTo(api.$dropdown);
                this.$handle = this.$hue.find('i');

                api.$element.on('asColorPicker::firstOpen', function() {
                    // init variable
                    if (self.direction === 'vertical') {
                        self.size = self.$hue.height();
                    } else {
                        self.size = self.$hue.width();
                    }
                    self.step = self.size / 360;

                    // bind events
                    self.bindEvents(api);
                    self.keyboard(api);
                });

                api.$element.on('asColorPicker::update asColorPicker::setup', function(e, api, color) {
                    self.update(color);
                });
            },
            bindEvents: function() {
                var self = this;
                this.$hue.on('mousedown.asColorPicker', function(e) {
                    var rightclick = (e.which) ? (e.which === 3) : (e.button === 2);
                    if (rightclick) {
                        return false;
                    }
                    $.proxy(self.mousedown, self)(e);
                });
            },
            mousedown: function(e) {
                var offset = this.$hue.offset();
                if (this.direction === 'vertical') {
                    this.data.startY = e.pageY;
                    this.data.top = e.pageY - offset.top;
                    this.move(this.data.top);
                } else {
                    this.data.startX = e.pageX;
                    this.data.left = e.pageX - offset.left;
                    this.move(this.data.left);
                }

                this.mousemove = function(e) {
                    var position;
                    if (this.direction === 'vertical') {
                        position = this.data.top + (e.pageY || this.data.startY) - this.data.startY;
                    } else {
                        position = this.data.left + (e.pageX || this.data.startX) - this.data.startX;
                    }

                    this.move(position);
                    return false;
                };

                this.mouseup = function() {
                    $(document).off({
                        mousemove: this.mousemove,
                        mouseup: this.mouseup
                    });
                    if (this.direction === 'vertical') {
                        this.data.top = this.data.cach;
                    } else {
                        this.data.left = this.data.cach;
                    }

                    return false;
                };

                $(document).on({
                    mousemove: $.proxy(this.mousemove, this),
                    mouseup: $.proxy(this.mouseup, this)
                });

                return false;
            },
            move: function(position, hub, update) {
                position = Math.max(0, Math.min(this.size, position));
                this.data.cach = position;
                if (typeof hub === 'undefined') {
                    hub = (1 - position / this.size) * 360;
                }
                hub = Math.max(0, Math.min(360, hub));
                if (this.direction === 'vertical') {
                    this.$handle.css({
                        top: position
                    });
                } else {
                    this.$handle.css({
                        left: position
                    });
                }
                if (update !== false) {
                    this.api.set({
                        h: hub
                    });
                }
            },
            moveLeft: function() {
                var step = this.step,
                    data = this.data;
                data.left = Math.max(0, Math.min(this.width, data.left - step));
                this.move(data.left);
            },
            moveRight: function() {
                var step = this.step,
                    data = this.data;
                data.left = Math.max(0, Math.min(this.width, data.left + step));
                this.move(data.left);
            },
            moveUp: function() {
                var step = this.step,
                    data = this.data;
                data.top = Math.max(0, Math.min(this.width, data.top - step));
                this.move(data.top);
            },
            moveDown: function() {
                var step = this.step,
                    data = this.data;
                data.top = Math.max(0, Math.min(this.width, data.top + step));
                this.move(data.top);
            },
            keyboard: function() {
                var keyboard, self = this;
                if (this.api._keyboard) {
                    keyboard = $.extend(true, {}, this.api._keyboard);
                } else {
                    return false;
                }

                this.$hue.attr('tabindex', '0').on('focus', function() {
                    if (this.direction === 'vertical') {
                        keyboard.attach({
                            up: function() {
                                self.moveUp();
                            },
                            down: function() {
                                self.moveDown();
                            }
                        });
                    } else {
                        keyboard.attach({
                            left: function() {
                                self.moveLeft();
                            },
                            right: function() {
                                self.moveRight();
                            }
                        });
                    }
                    return false;
                }).on('blur', function() {
                    keyboard.detach();
                });
            },
            update: function(color) {
                var position = (color.value.h === 0) ? 0 : this.size * (1 - color.value.h / 360);
                this.move(position, color.value.h, false);
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
