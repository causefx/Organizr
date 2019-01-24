/*
 * jquery-asColorPicker
 * https://github.com/amazingSurge/jquery-asColorPicker
 *
 * Copyright (c) 2014 AmazingSurge
 * Licensed under the GPL license.
 */
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
