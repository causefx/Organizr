/*! asColorPicker - v0.3.1 - 2014-11-04
* https://github.com/amazingSurge/jquery-asColorPicker
* Copyright (c) 2014 amazingSurge; Licensed GPL */
(function(window, document, $, Color, undefined) {
    "use strict";

    var id = 0;

    function createId(api) {
        api.id = id;
        id++;
    }

    // Constructor
    var AsColorInput = $.asColorPicker = function(element, options) {
        this.element = element;
        this.$element = $(element);

        //flag
        this.opened = false;
        this.firstOpen = true;
        this.disabled = false;
        this.initialed = false;
        this.originValue = this.element.value;
        this.isEmpty = false;

        createId(this);

        this.options = $.extend(true, {}, AsColorInput.defaults, options, this.$element.data());
        this.namespace = this.options.namespace;

        this.classes = {
            wrap: this.namespace + '-wrap',
            dropdown: this.namespace + '-dropdown',
            input: this.namespace + '-input',
            skin: this.namespace + '_' + this.options.skin,
            open: this.namespace + '_open',
            mask: this.namespace + '-mask',
            hideInput: this.namespace + '_hideInput',
            disabled: this.namespace + '_disabled',
            mode: this.namespace + '-mode_' + this.options.mode
        };
        if (this.options.hideInput) {
            this.$element.addClass(this.classes.hideInput);
        }

        this.components = AsColorInput.modes[this.options.mode];
        this._components = $.extend(true, {}, this._components);

        this._trigger('init');
        this.init();
    };

    AsColorInput.prototype = {
        constructor: AsColorInput,
        _components: {},
        init: function() {
            this.color = new Color(this.element.value, this.options.color);

            this._create();

            if (this.options.skin) {
                this.$dropdown.addClass(this.classes.skin);
                this.$element.parent().addClass(this.classes.skin);
            }

            if (this.options.readonly) {
                this.$element.prop('readonly', true);
            }

            this._bindEvent();

            this.initialed = true;
            this._trigger('ready');
        },

        _create: function() {
            var self = this;

            this.$dropdown = $('<div class="' + this.classes.dropdown + '" data-mode="' + this.options.mode + '"></div>');
            this.$element.wrap('<div class="' + this.classes.wrap + '"></div>').addClass(this.classes.input);

            this.$wrap = this.$element.parent();
            this.$body = $('body');

            this.$dropdown.data('asColorPicker', this);

            var component;
            $.each(this.components, function(key, options) {
                if (options === true) {
                    options = {};
                }
                if (self.options[key] !== undefined) {
                    options = $.extend(true, {}, options, self.options[key]);
                }
                if (self._components[key]) {
                    component = self._components[key]();
                    component.init(self, options);
                }
            });

            this._trigger('create');
        },
        _bindEvent: function() {
            var self = this;
            this.$element.on({
                'click.asColorPicker': function() {
                    if (!self.opened) {
                        self.open();
                    }
                    return false;
                },
                'keydown.asColorPicker': function(e) {
                    if (e.keyCode === 9) {
                        self.close();
                    } else if (e.keyCode === 13) {
                        self.val(self.element.value);
                        self.close();
                    }
                },
                'keyup.asColorPicker': function() {
                    if (self.color.matchString(self.element.value)) {
                        self.val(self.element.value);
                    }
                    //self.val(self.$element.val());
                }
            });
        },
        _trigger: function(eventType) {
            var method_arguments = Array.prototype.slice.call(arguments, 1),
                data = [this].concat(method_arguments);

            // event
            this.$element.trigger('asColorPicker::' + eventType, data);

            // callback
            eventType = eventType.replace(/\b\w+\b/g, function(word) {
                return word.substring(0, 1).toUpperCase() + word.substring(1);
            });
            var onFunction = 'on' + eventType;
            if (typeof this.options[onFunction] === 'function') {
                this.options[onFunction].apply(this, method_arguments);
            }
        },
        opacity: function(v) {
            if (v) {
                this.color.alpha(v);
            } else {
                return this.color.alpha();
            }
        },
        position: function() {
            var hidden = !this.$element.is(':visible'),
                offset = hidden ? this.$trigger.offset() : this.$element.offset(),
                height = hidden ? this.$trigger.outerHeight() : this.$element.outerHeight(),
                width = hidden ? this.$trigger.outerWidth() : this.$element.outerWidth() + this.$trigger.outerWidth(),
                picker_width = this.$dropdown.outerWidth(true),
                picker_height = this.$dropdown.outerHeight(true),
                top, left;

            if (picker_height + offset.top > $(window).height() + $(window).scrollTop()) {
                top = offset.top - picker_height;
            } else {
                top = offset.top + height;
            }

            if (picker_width + offset.left > $(window).width() + $(window).scrollLeft()) {
                left = offset.left - picker_width + width;
            } else {
                left = offset.left;
            }

            this.$dropdown.css({
                position: 'absolute',
                top: top,
                left: left
            });
        },
        open: function() {
            if (this.disabled) {
                return;
            }
            this.originValue = this.element.value;

            var self = this;
            if (this.$dropdown[0] !== this.$body.children().last()[0]) {
                this.$dropdown.detach().appendTo(this.$body);
            }

            this.$mask = $('.' + self.classes.mask);
            if (this.$mask.length === 0) {
                this.createMask();
            }

            // ensure the mask is always right before the dropdown
            if (this.$dropdown.prev()[0] !== this.$mask[0]) {
                this.$dropdown.before(this.$mask);
            }

            $("#asColorPicker-dropdown").removeAttr("id");
            this.$dropdown.attr("id", "asColorPicker-dropdown");

            // show the mask
            this.$mask.show();

            this.position();

            $(window).on('resize.asColorPicker', $.proxy(this.position, this));

            this.$dropdown.addClass(this.classes.open);

            this.opened = true;

            if (this.firstOpen) {
                this.firstOpen = false;
                this._trigger('firstOpen');
            }
            this._setup();
            this._trigger('open');
        },
        createMask: function() {
            this.$mask = $(document.createElement("div"));
            this.$mask.attr("class", this.classes.mask);
            this.$mask.hide();
            this.$mask.appendTo(this.$body);

            this.$mask.on("mousedown touchstart click", function(e) {
                var $dropdown = $("#asColorPicker-dropdown"),
                    self;
                if ($dropdown.length > 0) {
                    self = $dropdown.data("asColorPicker");
                    if (self.opened) {
                        if (self.options.hideFireChange) {
                            self.apply();
                        } else {
                            self.cancel();
                        }
                    }

                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        },
        close: function() {
            this.opened = false;
            this.$element.blur();
            this.$mask.hide();

            this.$dropdown.removeClass(this.classes.open);

            $(window).off('resize.asColorPicker');

            this._trigger('close');
        },
        clear: function() {
            this.val('');
        },
        cancel: function() {
            this.close();

            this.set(this.originValue);
        },
        apply: function() {
            this._trigger('apply', this.color);
            this.close();
        },
        val: function(value) {
            if (typeof value === 'undefined') {
                return this.color.toString();
            }

            this.set(value);
        },
        _update: function() {
            this._trigger('update', this.color);
            this._updateInput();
        },
        _updateInput: function() {
            var value = this.color.toString();
            if (this.isEmpty) {
                value = '';
            }
            this._trigger('change', value, this.options.name, 'asColorPicker');
            this.$element.val(value);
        },
        set: function(value) {
            if (value !== '') {
                this.isEmpty = false;
            } else {
                this.isEmpty = true;
            }
            return this._set(value);
        },
        _set: function(value) {
            if (typeof value === 'string') {
                this.color.val(value);
            } else {
                this.color.set(value);
            }

            this._update();
        },
        _setup: function() {
            this._trigger('setup', this.color);
        },
        get: function() {
            return this.color;
        },
        enable: function() {
            this.disabled = false;
            this.$parent.addClass(this.classes.disabled);
            return this;
        },
        disable: function() {
            this.disabled = true;
            this.$parent.removeClass(this.classes.disabled);
            return this;
        },
        destroy: function() {

        }
    };

    AsColorInput.registerComponent = function(component, method) {
        AsColorInput.prototype._components[component] = method;
    };

    AsColorInput.localization = [];

    AsColorInput.defaults = {
        namespace: 'asColorPicker',
        readonly: false,
        skin: null,
        hideInput: false,
        hideFireChange: true,
        keyboard: false,
        color: {
            format: false,
            alphaConvert: { // or false will disable convert
                'RGB': 'RGBA',
                'HSL': 'HSLA',
                'HEX': 'RGBA',
                'NAME': 'RGBA',
            },
            shortenHex: false,
            hexUseName: false,
            reduceAlpha: true,
            nameDegradation: 'HEX',
            invalidValue: '',
            zeroAlphaAsTransparent: true
        },
        mode: 'simple',
        onInit: null,
        onReady: null,
        onChange: null,
        onClose: null,
        onOpen: null,
        onApply: null
    };

    AsColorInput.modes = {
        'simple': {
            trigger: true,
            clear: true,
            saturation: true,
            hue: true,
            alpha: true
        },
        'palettes': {
            trigger: true,
            clear: true,
            palettes: true
        },
        'complex': {
            trigger: true,
            clear: true,
            preview: true,
            palettes: true,
            saturation: true,
            hue: true,
            alpha: true,
            hex: true,
            buttons: true
        },
        'gradient': {
            trigger: true,
            clear: true,
            preview: true,
            palettes: true,
            saturation: true,
            hue: true,
            alpha: true,
            hex: true,
            gradient: true
        }
    };

    // Collection method.
    $.fn.asColorPicker = function(options) {
        if (typeof options === 'string') {
            var method = options;
            var method_arguments = Array.prototype.slice.call(arguments, 1);

            if (/^\_/.test(method)) {
                return false;
            } else if ((/^(get)$/.test(method)) || (method === 'val' && method_arguments.length === 0)) {
                var api = this.first().data('asColorPicker');
                if (api && typeof api[method] === 'function') {
                    return api[method].apply(api, method_arguments);
                }
            } else {
                return this.each(function() {
                    var api = $.data(this, 'asColorPicker');
                    if (api && typeof api[method] === 'function') {
                        api[method].apply(api, method_arguments);
                    }
                });
            }
        } else {
            return this.each(function() {
                if (!$.data(this, 'asColorPicker')) {
                    $.data(this, 'asColorPicker', new AsColorInput(this, options));
                }
            });
        }
    };
}(window, document, jQuery, (function($) {
    if ($.asColor === undefined) {
        // console.info('lost dependency lib of $.asColor , please load it first !');
        return false;
    } else {
        return $.asColor;
    }
}(jQuery))));

// trigger

(function($) {
    "use strict";

    $.asColorPicker.registerComponent('trigger', function() {
        return {
            defaults: {
                template: function(namespace) {
                    return '<div class="' + namespace + '-trigger"><span></span></div>';
                }
            },
            init: function(api, options) {
                this.options = $.extend(this.defaults, options),
                    api.$trigger = $(this.options.template.call(this, api.namespace));
                this.$trigger_inner = api.$trigger.children('span');

                api.$trigger.insertAfter(api.$element);
                api.$trigger.on('click', function() {
                    if (!api.opened) {
                        api.open();
                    } else {
                        api.close();
                    }
                    return false;
                });
                var self = this;
                api.$element.on('asColorPicker::update', function(e, api, color, gradient) {
                    if (typeof gradient === 'undefined') {
                        gradient = false;
                    }
                    self.update(color, gradient);
                });

                this.update(api.color);
            },
            update: function(color, gradient) {
                if (gradient) {
                    this.$trigger_inner.css('background', gradient.toString(true));
                } else {
                    this.$trigger_inner.css('background', color.toRGBA());
                }
            },
            destroy: function(api) {
                api.$trigger.remove();
            }
        };
    });
})(jQuery);

// clear

(function($) {
    "use strict";

    $.asColorPicker.registerComponent('clear', function() {
        return {
            defaults: {
                template: function(namespace) {
                    return '<a href="#" class="' + namespace + '-clear"></a>';
                }
            },
            init: function(api, options) {
                if (api.options.hideInput) {
                    return;
                }
                this.options = $.extend(this.defaults, options);
                this.$clear = $(this.options.template.call(this, api.namespace)).insertAfter(api.$element);

                this.$clear.on('click', function() {
                    api.clear();
                    return false;
                });
            }
        };
    });
})(jQuery);

// keyboard
(function(window, document, $, undefined) {
    "use strict";

    var $doc = $(document);
    var keyboard = {
        keys: {
            'UP': 38,
            'DOWN': 40,
            'LEFT': 37,
            'RIGHT': 39,
            'RETURN': 13,
            'ESCAPE': 27,
            'BACKSPACE': 8,
            'SPACE': 32
        },
        map: {},
        bound: false,
        press: function(e) {
            var key = e.keyCode || e.which;
            if (key in keyboard.map && typeof keyboard.map[key] === 'function') {
                keyboard.map[key](e);
            }
            return false;
        },
        attach: function(map) {
            var key, up;
            for (key in map) {
                if (map.hasOwnProperty(key)) {
                    up = key.toUpperCase();
                    if (up in keyboard.keys) {
                        keyboard.map[keyboard.keys[up]] = map[key];
                    } else {
                        keyboard.map[up] = map[key];
                    }
                }
            }
            if (!keyboard.bound) {
                keyboard.bound = true;
                $doc.bind('keydown', keyboard.press);
            }
        },
        detach: function() {
            keyboard.bound = false;
            keyboard.map = {};
            $doc.unbind('keydown', keyboard.press);
        }
    };
    $doc.on('asColorPicker::init', function(event, instance) {
        if (instance.options.keyboard === true) {
            instance._keyboard = keyboard;
        }
    });
})(window, document, jQuery);

// alpha

(function($) {
    "use strict";

    $.asColorPicker.registerComponent('alpha', function() {
        return {
            size: 150,
            defaults: {
                direction: 'vertical', // horizontal
                template: function(namespace) {
                    return '<div class="' + namespace + '-alpha ' + namespace + '-alpha-' + this.direction + '"><i></i></div>';
                }
            },
            data: {},
            init: function(api, options) {
                var self = this;

                this.options = $.extend(this.defaults, options);
                self.direction = this.options.direction;
                this.api = api;

                this.$alpha = $(this.options.template.call(self, api.namespace)).appendTo(api.$dropdown);
                this.$handle = this.$alpha.find('i');

                api.$element.on('asColorPicker::firstOpen', function() {
                    // init variable
                    if (self.direction === 'vertical') {
                        self.size = self.$alpha.height();
                    } else {
                        self.size = self.$alpha.width();
                    }
                    self.step = self.size / 360;

                    // bind events
                    self.bindEvents();
                    self.keyboard();
                });

                api.$element.on('asColorPicker::update asColorPicker::setup', function(e, api, color) {
                    self.update(color);
                });
            },
            bindEvents: function() {
                var self = this;
                this.$alpha.on('mousedown.asColorPicker', function(e) {
                    var rightclick = (e.which) ? (e.which === 3) : (e.button === 2);
                    if (rightclick) {
                        return false;
                    }
                    $.proxy(self.mousedown, self)(e);
                });
            },
            mousedown: function(e) {
                var offset = this.$alpha.offset();
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
            move: function(position, alpha, update) {
                position = Math.max(0, Math.min(this.size, position));
                this.data.cach = position;
                if (typeof alpha === 'undefined') {
                    alpha = 1 - (position / this.size);
                }
                alpha = Math.max(0, Math.min(1, alpha));
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
                        a: Math.round(alpha * 100) / 100
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

                this.$alpha.attr('tabindex', '0').on('focus', function() {
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
                var position = this.size * (1 - color.value.a);
                this.$alpha.css('backgroundColor', color.toHEX());

                this.move(position, color.value.a, false);
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

// palettes

(function($) {
    "use strict";

    function noop() {
        return;
    }
    if (!window.localStorage) {
        window.localStorage = noop;
    }

    $.asColorPicker.registerComponent('palettes', function() {
        return {
            defaults: {
                template: function(namespace) {
                    return '<ul class="' + namespace + '-palettes"></ul>';
                },
                item: function(namespace, color) {
                    return '<li data-color="' + color + '"><span style="background-color:' + color + '" /></li>';
                },
                colors: ['white', 'black', 'red', 'blue', 'yellow'],
                max: 10,
                localStorage: true
            },
            init: function(api, options) {
                var self = this,
                    colors, asColor = new $.asColor();

                this.options = $.extend(true, {}, this.defaults, options);
                this.colors = [];
                if (this.options.localStorage) {
                    var localKey = api.namespace + '_palettes_' + api.id;
                    colors = this.getLocal(localKey);
                    if (!colors) {
                        colors = this.options.colors;
                        this.setLocal(localKey, colors);
                    }
                } else {
                    colors = this.options.colors;
                }

                for (var i in colors) {
                    this.colors.push(asColor.val(colors[i]).toRGBA());
                }

                var list = '';
                $.each(this.colors, function(i, color) {
                    list += self.options.item(api.namespace, color);
                });

                this.$palettes = $(this.options.template.call(this, api.namespace)).html(list).appendTo(api.$dropdown);

                this.$palettes.delegate('li', 'click', function(e) {
                    var color = $(this).data('color');
                    api.set(color);

                    e.preventDefault();
                    e.stopPropagation();
                });

                api.$element.on('asColorPicker::apply', function(e, api, color) {
                    if (typeof color.toRGBA !== 'function') {
                        color = color.get().color;
                    }

                    var rgba = color.toRGBA();
                    if ($.inArray(rgba, self.colors) === -1) {
                        if (self.colors.length >= self.options.max) {
                            self.colors.shift();
                            self.$palettes.find('li').eq(0).remove();
                        }

                        self.colors.push(rgba);

                        self.$palettes.append(self.options.item(api.namespace, color));

                        if (self.options.localStorage) {
                            self.setLocal(localKey, self.colors);
                        }
                    }
                });
            },
            setLocal: function(key, value) {
                var jsonValue = JSON.stringify(value);

                localStorage[key] = jsonValue;
            },
            getLocal: function(key) {
                var value = localStorage[key];

                return value ? JSON.parse(value) : value;
            }
        };
    });
})(jQuery);

// preview

(function($) {
    "use strict";

    $.asColorPicker.registerComponent('preview', function() {
        return {
            defaults: {
                template: function(namespace) {
                    return '<ul class="' + namespace + '-preview"><li class="' + namespace + '-preview-current"><span /></li><li class="' + namespace + '-preview-previous"><span /></li></ul>';
                }
            },
            init: function(api, options) {
                var self = this;
                this.options = $.extend(this.defaults, options);
                this.$preview = $(this.options.template.call(self, api.namespace)).appendTo(api.$dropdown);
                this.$current = this.$preview.find('.' + api.namespace + '-preview-current span');
                this.$previous = this.$preview.find('.' + api.namespace + '-preview-previous span');

                api.$element.on('asColorPicker::firstOpen', function() {
                    self.$previous.on('click', function() {
                        api.set($(this).data('color'));
                        return false;
                    });
                });

                api.$element.on('asColorPicker::setup', function(e, api, color) {
                    self.updateCurrent(color);
                    self.updatePreview(color);
                });
                api.$element.on('asColorPicker::update', function(e, api, color) {
                    self.updateCurrent(color);
                });
            },
            updateCurrent: function(color) {
                this.$current.css('backgroundColor', color.toRGBA());
            },
            updatePreview: function(color) {
                this.$previous.css('backgroundColor', color.toRGBA());
                this.$previous.data('color', {
                    r: color.value.r,
                    g: color.value.g,
                    b: color.value.b,
                    a: color.value.a
                });
            }
        };
    });
})(jQuery);

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
