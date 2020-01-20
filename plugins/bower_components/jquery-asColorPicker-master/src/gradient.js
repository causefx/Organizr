// gradient

(function($, asGradient) {
    $.asColorPicker.registerComponent('gradient', function() {
        return {
            defaults: {
                switchable: true,
                switchText: 'Gradient',
                cancelText: 'Cancel',
                settings: {
                    forceStandard: true,
                    angleUseKeyword: true,
                    emptyString: '',
                    degradationFormat: false,
                    cleanPosition: false,
                    forceColorFormat: 'rgb', // rgb, rgba, hsl, hsla, hex
                },
                template: function() {
                    var namespace = this.api.namespace;
                    var control = '<div class="' + namespace + '-gradient-control">';
                    if (this.options.switchable) {
                        control += '<a href="#" class="' + namespace + '-gradient-switch">' + this.options.switchText + '</a>';
                    }
                    control += '<a href="#" class="' + namespace + '-gradient-cancel">' + this.options.cancelText + '</a>' +
                        '</div>';

                    return control +
                        '<div class="' + namespace + '-gradient">' +
                        '<div class="' + namespace + '-gradient-preview">' +
                        '<ul class="' + namespace + '-gradient-markers"></ul>' +
                        '</div>' +
                        '<div class="' + namespace + '-gradient-wheel">' +
                        '<i></i>' +
                        '</div>' +
                        '<input class="' + namespace + '-gradient-angle" type="text" value="" size="3" />' +
                        '</div>';
                }
            },
            init: function(api, options) {
                var self = this;

                api.$element.on('asColorPicker::ready', function(event, instance) {
                    if (instance.options.mode !== 'gradient') {
                        return;
                    }

                    self.defaults.settings.color = api.options.color;
                    options = $.extend(true, self.defaults, options);

                    api.gradient = new Gradient(api, options);
                });
            }
        };
    });

    function conventToPercentage(n) {
        if (n < 0) {
            n = 0;
        } else if (n > 1) {
            n = 1;
        }
        return n * 100 + '%';
    }

    var Gradient = function(api, options) {
        this.api = api;
        this.options = options;
        this.classes = {
            enable: api.namespace + '-gradient_enable',
            marker: api.namespace + '-gradient-marker',
            active: api.namespace + '-gradient-marker_active',
            focus: api.namespace + '-gradient_focus'
        };
        this.isEnabled = false;
        this.initialized = false;
        this.current = null;
        this.value = new asGradient(this.options.settings);
        this.$doc = $(document);

        var self = this;
        $.extend(self, {
            init: function() {
                self.$wrap = $(self.options.template.call(self)).appendTo(api.$dropdown);

                self.$gradient = self.$wrap.filter('.' + api.namespace + '-gradient');

                this.angle.init();
                this.preview.init();
                this.markers.init();
                this.wheel.init();

                this.bind();

                if (self.options.switchable === false) {
                    self.enable();
                } else {
                    if (this.value.matchString(api.element.value)) {
                        self.enable();
                    }
                }
                this.initialized = true;
            },
            bind: function() {
                var namespace = api.namespace;

                self.$gradient.on('update', function() {
                    var current = self.value.getById(self.current);

                    if (current) {
                        api._trigger('update', current.color, self.value);
                    }

                    if (api.element.value !== self.value.toString()) {
                        api._updateInput();
                    }
                });

                // self.$gradient.on('add', function(e, data) {
                //     if (data.stop) {
                //         self.active(data.stop.id);
                //         api._trigger('update', data.stop.color, self.value);
                //         api._updateInput();
                //     }
                // });

                if (self.options.switchable) {
                    self.$wrap.on('click', '.' + namespace + '-gradient-switch', function() {
                        if (self.isEnabled) {
                            self.disable();
                        } else {
                            self.enable();
                        }

                        return false;
                    });
                }

                self.$wrap.on('click', '.' + namespace + '-gradient-cancel', function() {
                    if (self.options.switchable === false || asGradient.matchString(api.originValue)) {
                        self.overrideCore();
                    }

                    api.cancel();

                    return false;
                });
            },
            overrideCore: function() {
                api.set = function(value) {
                    if (value !== '') {
                        api.isEmpty = false;
                    } else {
                        api.isEmpty = true;
                    }
                    if (typeof value === 'string') {
                        if (self.options.switchable === false || asGradient.matchString(value)) {
                            if (self.isEnabled) {
                                self.val(value);
                                api.color = self.value;
                                self.$gradient.trigger('update', self.value.value);
                            } else {
                                self.enable(value);
                            }
                        } else {
                            self.disable();
                            api.val(value);
                        }
                    } else {
                        var current = self.value.getById(self.current);

                        if (current) {
                            current.color.val(value)
                            api._trigger('update', current.color, self.value);
                        }

                        self.$gradient.trigger('update', {
                            id: self.current,
                            stop: current
                        });
                    }
                };

                api._setup = function() {
                    var current = self.value.getById(self.current);

                    api._trigger('setup', current.color);
                };
            },
            revertCore: function() {
                api.set = $.proxy(api._set, api);
                api._setup = function() {
                    api._trigger('setup', api.color);
                };
            },
            preview: {
                init: function() {
                    var that = this;
                    self.$preview = self.$gradient.find('.' + api.namespace + '-gradient-preview');

                    self.$gradient.on('add del update empty', function() {
                        that.render();
                    });
                },
                render: function() {
                    self.$preview.css({
                        'background-image': self.value.toStringWithAngle('to right', true),
                    });
                    self.$preview.css({
                        'background-image': self.value.toStringWithAngle('to right'),
                    });
                }
            },
            markers: {
                width: 160,
                init: function() {
                    self.$markers = self.$gradient.find('.' + api.namespace + '-gradient-markers').attr('tabindex', 0);
                    var that = this;

                    self.$gradient.on('add', function(e, data) {
                        that.add(data.stop);
                    });

                    self.$gradient.on('active', function(e, data) {
                        that.active(data.id);
                    });

                    self.$gradient.on('del', function(e, data) {
                        that.del(data.id);
                    });

                    self.$gradient.on('update', function(e, data) {
                        if (data.stop) {
                            that.update(data.stop.id, data.stop.color);
                        }
                    });

                    self.$gradient.on('empty', function() {
                        that.empty();
                    });

                    self.$markers.on('mousedown.asColorPicker', function(e) {
                        var rightclick = (e.which) ? (e.which === 3) : (e.button === 2);
                        if (rightclick) {
                            return false;
                        }

                        var position = parseFloat((e.pageX - self.$markers.offset().left) / self.markers.width, 10);
                        self.add('#fff', position);
                        return false;
                    });

                    self.$markers.on('mousedown.asColorPicker', 'li', function(e) {
                        var rightclick = (e.which) ? (e.which === 3) : (e.button === 2);
                        if (rightclick) {
                            return false;
                        }
                        that.mousedown(this, e);
                        return false;
                    });

                    self.$doc.on('keydown.asColorPicker', function(e) {
                        if (self.api.opened && self.$markers.is('.' + self.classes.focus)) {

                            var key = e.keyCode || e.which;
                            if (key === 46 || key === 8) {
                                if (self.value.length <= 2) {
                                    return false;
                                }

                                self.del(self.current);

                                return false;
                            }
                        }
                    });

                    self.$markers.on('focus.asColorPicker', function() {
                        self.$markers.addClass(self.classes.focus);
                    }).on('blur.asColorPicker', function() {
                        self.$markers.removeClass(self.classes.focus);
                    });

                    self.$markers.on('click', 'li', function() {
                        var id = $(this).data('id');
                        self.active(id);
                    });
                },
                getMarker: function(id) {
                    return self.$markers.find('[data-id="' + id + '"]');
                },
                update: function(id, color) {
                    var $marker = this.getMarker(id);
                    $marker.find('span').css('background-color', color.toHEX());
                    $marker.find('i').css('background-color', color.toHEX());
                },
                add: function(stop) {
                    $('<li data-id="' + stop.id + '" style="left:' + conventToPercentage(stop.position) + '" class="' + self.classes.marker + '"><span style="background-color: ' + stop.color.toHEX() + '"></span><i style="background-color: ' + stop.color.toHEX() + '"></i></li>').appendTo(self.$markers);
                },
                empty: function() {
                    self.$markers.html('');
                },
                del: function(id) {
                    var $marker = this.getMarker(id);
                    var $to = $marker.prev();
                    if ($to.length === 0) {
                        $to = $marker.next();
                    }

                    self.active($to.data('id'));
                    $marker.remove();
                },
                active: function(id) {
                    self.$markers.children().removeClass(self.classes.active);

                    var $marker = this.getMarker(id);
                    $marker.addClass(self.classes.active);

                    self.$markers.focus();
                    // self.api._trigger('apply', self.value.getById(id).color);
                },
                mousedown: function(marker, e) {
                    var that = this,
                        id = $(marker).data('id'),
                        first = $(marker).position().left,
                        start = e.pageX,
                        end;

                    this.mousemove = function(e) {
                        end = e.pageX || start;
                        var position = (first + end - start) / this.width;
                        that.move(marker, position, id);
                        return false;
                    };

                    this.mouseup = function() {
                        $(document).off({
                            mousemove: this.mousemove,
                            mouseup: this.mouseup
                        });

                        return false;
                    };

                    self.$doc.on({
                        mousemove: $.proxy(this.mousemove, this),
                        mouseup: $.proxy(this.mouseup, this)
                    });
                    self.active(id);
                    return false;
                },
                move: function(marker, position, id) {
                    self.api.isEmpty = false;
                    position = Math.max(0, Math.min(1, position));
                    $(marker).css({
                        left: conventToPercentage(position)
                    });
                    if (!id) {
                        id = $(marker).data('id');
                    }

                    self.value.getById(id).setPosition(position);

                    self.$gradient.trigger('update', {
                        id: $(marker).data('id'),
                        position: position
                    });
                },
            },
            wheel: {
                init: function() {
                    var that = this;
                    self.$wheel = self.$gradient.find('.' + api.namespace + '-gradient-wheel');
                    self.$pointer = self.$wheel.find('i');

                    self.$gradient.on('update', function(e, data) {
                        if (typeof data.angle !== 'undefined') {
                            that.position(data.angle);
                        }
                    });

                    self.$wheel.on('mousedown.asColorPicker', function(e) {
                        var rightclick = (e.which) ? (e.which === 3) : (e.button === 2);
                        if (rightclick) {
                            return false;
                        }
                        that.mousedown(e, self);
                        return false;
                    });
                },
                mousedown: function(e, self) {
                    var offset = self.$wheel.offset();
                    var r = self.$wheel.width() / 2;
                    var startX = offset.left + r;
                    var startY = offset.top + r;
                    var $doc = self.$doc;
                    var that = this;

                    this.r = r;

                    this.wheelMove = function(e) {
                        var x = e.pageX - startX;
                        var y = startY - e.pageY;

                        var position = that.getPosition(x, y);
                        var angle = that.calAngle(position.x, position.y);
                        self.api.isEmpty = false;
                        self.setAngle(angle);
                    };
                    this.wheelMouseup = function() {
                        $doc.off({
                            mousemove: this.wheelMove,
                            mouseup: this.wheelMouseup
                        });
                        return false;
                    };
                    $doc.on({
                        mousemove: $.proxy(this.wheelMove, this),
                        mouseup: $.proxy(this.wheelMouseup, this)
                    });

                    this.wheelMove(e);
                },
                getPosition: function(a, b) {
                    var r = this.r;
                    var x = a / Math.sqrt(a * a + b * b) * r;
                    var y = b / Math.sqrt(a * a + b * b) * r;
                    return {
                        x: x,
                        y: y
                    };
                },
                calAngle: function(x, y) {
                    var deg = Math.round(Math.atan(Math.abs(x / y)) * (180 / Math.PI));
                    if (x < 0 && y > 0) {
                        return 360 - deg;
                    }
                    if (x < 0 && y <= 0) {
                        return deg + 180;
                    }
                    if (x >= 0 && y <= 0) {
                        return 180 - deg;
                    }
                    if (x >= 0 && y > 0) {
                        return deg;
                    }
                },
                set: function(value) {
                    self.value.angle(value);
                    self.$gradient.trigger('update', {
                        angle: value
                    });
                },
                position: function(angle) {
                    var r = this.r || self.$wheel.width() / 2;
                    var pos = this.calPointer(angle, r);
                    self.$pointer.css({
                        left: pos.x,
                        top: pos.y
                    });
                },
                calPointer: function(angle, r) {
                    var x = Math.sin(angle * Math.PI / 180) * r;
                    var y = Math.cos(angle * Math.PI / 180) * r;
                    return {
                        x: r + x,
                        y: r - y
                    };
                }
            },
            angle: {
                init: function() {
                    self.$angle = self.$gradient.find('.' + api.namespace + '-gradient-angle');

                    self.$angle.on('blur.asColorPicker', function() {
                        self.setAngle(this.value);
                        return false;
                    }).on('keydown.asColorPicker', function(e) {
                        var key = e.keyCode || e.which;
                        if (key === 13) {
                            self.api.isEmpty = false;
                            $(this).blur();
                            return false;
                        }
                    });

                    self.$gradient.on('update', function(e, data) {
                        if (typeof data.angle !== 'undefined') {
                            self.$angle.val(data.angle);
                        }
                    });
                },
                set: function(value) {
                    self.value.angle(value);
                    self.$gradient.trigger('update', {
                        angle: value
                    });
                }
            }
        });

        this.init();
    };

    Gradient.prototype = {
        constructor: Gradient,

        enable: function(value) {
            if (this.isEnabled === true) {
                return;
            }
            this.isEnabled = true;
            this.overrideCore();



            this.$gradient.addClass(this.classes.enable);
            this.markers.width = this.$markers.width();

            if (typeof value === 'undefined') {
                value = this.api.element.value;
            }

            if (value !== '') {
                this.api.isEmpty = false;
            } else {
                this.api.isEmpty = true;
            }

            if (!asGradient.matchString(value) && this._last) {
                this.value = this._last;
            } else {
                this.val(value);
            }
            this.api.color = this.value;

            this.$gradient.trigger('update', this.value.value);

            if (this.api.opened) {
                this.api.position();
            }
        },
        val: function(string) {
            if (string !== '' && this.value.toString() === string) {
                return;
            }
            this.empty();
            this.value.val(string);
            this.value.reorder();

            if (this.value.length < 2) {
                var fill = string;

                if (!$.asColor.matchString(string)) {
                    fill = 'rgba(0,0,0,1)';
                }

                if (this.value.length === 0) {
                    this.value.append(fill, 0);
                }
                if (this.value.length === 1) {
                    this.value.append(fill, 1);
                }
            }

            var stop;
            for (var i = 0; i < this.value.length; i++) {
                stop = this.value.get(i);
                if (stop) {
                    this.$gradient.trigger('add', {
                        stop: stop
                    });
                }
            }

            this.active(stop.id);
        },
        disable: function() {
            if (this.isEnabled === false) {
                return;
            }
            this.isEnabled = false;
            this.revertCore();

            this.$gradient.removeClass(this.classes.enable);
            this._last = this.value;
            this.api.color = this.api.color.getCurrent().color;
            this.api.set(this.api.color.value);

            if (this.api.opened) {
                this.api.position();
            }
        },
        active: function(id) {
            if (this.current !== id) {
                this.current = id;
                this.value.setCurrentById(id);

                this.$gradient.trigger('active', {
                    id: id
                });
            }
        },
        empty: function() {
            this.value.empty();
            this.$gradient.trigger('empty');
        },
        add: function(color, position) {
            var stop = this.value.insert(color, position);
            this.api.isEmpty = false;
            this.value.reorder();

            this.$gradient.trigger('add', {
                stop: stop
            });

            this.active(stop.id);

            this.$gradient.trigger('update', {
                stop: stop
            });
            return stop;
        },
        del: function(id) {
            if (this.value.length <= 2) {
                return;
            }
            this.value.removeById(id);
            this.value.reorder();
            this.$gradient.trigger('del', {
                id: id
            });

            this.$gradient.trigger('update', {});
        },
        setAngle: function(value) {
            this.value.angle(value);
            this.$gradient.trigger('update', {
                angle: value
            });
        }
    };
})(jQuery, (function($) {
    if ($.asGradient === undefined) {
        // console.info('lost dependency lib of $.asGradient , please load it first !');
        return false;
    } else {
        return $.asGradient;
    }
}(jQuery)));
