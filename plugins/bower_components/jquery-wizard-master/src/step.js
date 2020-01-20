// Step
function Step() {
    return this.initialize.apply(this, Array.prototype.slice.call(arguments));
}

$.extend(Step.prototype, {
    TRANSITION_DURATION: 200,
    initialize: function(element, wizard, index) {
        this.$element = $(element);
        this.wizard = wizard;

        this.events = {};
        this.loader = null;
        this.loaded = false;

        this.validator = this.wizard.options.validator;

        this.states = {
            done: false,
            error: false,
            active: false,
            disabled: false,
            activing: false
        };

        this.index = index;
        this.$element.data('wizard-index', index);


        this.$pane = this.getPaneFromTarget();

        if(!this.$pane){
            this.$pane = this.wizard.options.getPane.call(this.wizard, index, element);
        }

        this.setValidatorFromData();
        this.setLoaderFromData();
    },

    getPaneFromTarget: function(){
        var selector = this.$element.data('target');

        if (!selector) {
            selector = this.$element.attr('href');
            selector = selector && selector.replace(/.*(?=#[^\s]*$)/, '');
        }

        if(selector) {
            return $(selector);
        } else {
            return null;
        }
    },

    setup: function() {
        var current = this.wizard.currentIndex();
        if(this.index === current){
            this.enter('active');

            if(this.loader){
                this.load();
            }
        } else if (this.index > current){
            this.enter('disabled');
        }

        this.$element.attr('aria-expanded', this.is('active'));
        this.$pane.attr('aria-expanded', this.is('active'));

        var classes = this.wizard.options.classes;
        if(this.is('active')){
            this.$pane.addClass(classes.pane.active);
        } else {
            this.$pane.removeClass(classes.pane.active);
        }
    },

    show: function(callback) {
        if(this.is('activing') || this.is('active')) {
            return;
        }

        this.trigger('beforeShow');
        this.enter('activing');

        var classes = this.wizard.options.classes;

        this.$element
            .attr('aria-expanded', true);

        this.$pane
            .addClass(classes.pane.activing)
            .addClass(classes.pane.active)
            .attr('aria-expanded', true);

        var complete = function () {
            this.$pane
                .removeClass(classes.pane.activing)

            this.leave('activing');
            this.enter('active');
            this.trigger('afterShow');

            if($.isFunction(callback)){
                callback.call(this);
            }
        }

        if (!Support.transition) {
            return complete.call(this);
        }

        this.$pane.one(Support.transition.end, $.proxy(complete, this));

        emulateTransitionEnd(this.$pane, this.TRANSITION_DURATION);
    },

    hide: function(callback) {
        if(this.is('activing') || !this.is('active')) {
            return;
        }

        this.trigger('beforeHide');
        this.enter('activing');

        var classes = this.wizard.options.classes;

        this.$element
            .attr('aria-expanded', false);

        this.$pane
            .addClass(classes.pane.activing)
            .removeClass(classes.pane.active)
            .attr('aria-expanded', false);

        var complete = function () {
            this.$pane
                .removeClass(classes.pane.activing);

            this.leave('activing');
            this.leave('active');
            this.trigger('afterHide');

            if($.isFunction(callback)){
                callback.call(this);
            }
        }

        if (!Support.transition) {
            return complete.call(this);
        }

        this.$pane.one(Support.transition.end, $.proxy(complete, this));

        emulateTransitionEnd(this.$pane, this.TRANSITION_DURATION);
    },

    empty: function() {
        this.$pane.empty();
    },

    load: function(callback) {
        var self = this;
        var loader = this.loader;

        if($.isFunction(loader)){
            loader = loader.call(this.wizard, this);
        }

        if(this.wizard.options.cacheContent && this.loaded){
            if($.isFunction(callback)){
                callback.call(this);
            }
            return true;
        }

        this.trigger('beforeLoad');
        this.enter('loading');

        function setContent(content) {
            self.$pane.html(content);

            self.leave('loading');
            self.loaded = true;
            self.trigger('afterLoad');

            if($.isFunction(callback)){
                callback.call(self);
            }
        }

        if (typeof loader === 'string') {
            setContent(loader);
        } else if (typeof loader === 'object' && loader.hasOwnProperty('url')) {
            self.wizard.options.loading.show.call(self.wizard, self);

            $.ajax(loader.url, loader.settings || {}).done(function(data) {
                setContent(data);

                self.wizard.options.loading.hide.call(self.wizard, self);
            }).fail(function(){
                self.wizard.options.loading.fail.call(self.wizard, self);
            });
        } else {
            setContent('');
        }
    },

    trigger: function(event) {
        var method_arguments = Array.prototype.slice.call(arguments, 1);

        if($.isArray(this.events[event])){
            for(var i in this.events[event]){
                this.events[event][i].apply(this, method_arguments);
            }
        }

        this.wizard.trigger.apply(this.wizard, [event, this].concat(method_arguments));
    },

    enter: function(state) {
        this.states[state] = true;

        var classes = this.wizard.options.classes;
        this.$element.addClass(classes.step[state]);

        this.trigger('stateChange', true, state);
    },

    leave: function(state) {
        if(this.states[state]){
            this.states[state] = false;

            var classes = this.wizard.options.classes;
            this.$element.removeClass(classes.step[state]);

            this.trigger('stateChange', false, state);
        }
    },

    setValidatorFromData: function(){
        var validator = this.$pane.data('validator');
        if(validator && $.isFunction(window[validator])){
            this.validator = window[validator];
        }
    },

    setLoaderFromData: function(){
        var loader = this.$pane.data('loader');

        if(loader){
            if($.isFunction(window[loader])){
                this.loader = window[loader];
            }
        } else {
            var url = this.$pane.data('loader-url');
            if(url){
                this.loader = {
                    url: url,
                    settings: this.$pane.data('settings') || {}
                }
            }
        }
    },

    /*
     * Public methods below
     */
    active: function(){
        return this.wizard.goTo(this.index);
    },

    on: function(event, handler){
        if($.isFunction(handler)){
            if($.isArray(this.events[event])){
                this.events[event].push(handler);
            } else {
                this.events[event] = [handler];
            }
        }

        return this;
    },

    off: function(event, handler){
        if($.isFunction(handler) && $.isArray(this.events[event])){
            $.each(this.events[event], function(i, f){
                if(f === handler) {
                    delete this.events[event][i];
                    return false;
                }
            });
        }

        return this;
    },

    is: function(state) {
        return this.states[state] && this.states[state] === true;
    },

    reset: function(){
        for(var state in this.states){
            this.leave(state);
        }
        this.setup();

        return this;
    },

    setLoader: function(loader){
        this.loader = loader;

        if(this.is('active')){
            this.load();
        }

        return this;
    },

    setValidator: function(validator) {
        if($.isFunction(validator)){
            this.validator = validator;
        }

        return this;
    },

    validate: function() {
        return this.validator.call(this.$pane.get(0), this);
    }
});
