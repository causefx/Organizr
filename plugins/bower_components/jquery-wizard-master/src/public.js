$.extend(Wizard.prototype, {
    Constructor: Wizard,
    initialize: function(){
        this.steps = [];
        var self = this;

        this.$steps.each(function(index){
            self.steps.push(new Step(this, self, index));
        });

        this._current = 0;
        this.transitioning = null;

        $.each(this.steps, function(i, step){
            step.setup();
        });

        this.setup();

        this.$element.on('click', this.options.step, function(e){
            var index = $(this).data('wizard-index');

            if(!self.get(index).is('disabled')){
                self.goTo(index);
            }

            e.preventDefault();
            e.stopPropagation();
        });

        if(this.options.keyboard){
            $(document).on('keyup', $.proxy(this.keydown, this));
        }

        this.trigger('init');
    },

    setup: function(){
        this.$buttons = $(this.options.templates.buttons.call(this));

        this.updateButtons();

        var buttonsAppendTo = this.options.buttonsAppendTo;
        var $to;
        if(buttonsAppendTo ==='this'){
            $to = this.$element;
        } else if($.isFunction(buttonsAppendTo)){
            $to = buttonsAppendTo.call(this);
        } else {
            $to = this.$element.find(buttonsAppendTo);
        }
        this.$buttons = this.$buttons.appendTo($to);
    },

    updateButtons: function(){
        var classes = this.options.classes.button;
        var $back = this.$buttons.find('[data-wizard="back"]');
        var $next = this.$buttons.find('[data-wizard="next"]');
        var $finish = this.$buttons.find('[data-wizard="finish"]');

        if(this._current === 0){
            $back.addClass(classes.disabled);
        } else {
            $back.removeClass(classes.disabled);
        }

        if(this._current === this.lastIndex()) {
            $next.addClass(classes.hide);
            $finish.removeClass(classes.hide);
        } else {
            $next.removeClass(classes.hide);
            $finish.addClass(classes.hide);
        }
    },

    updateSteps: function(){
        var self = this;

        $.each(this.steps, function(i, step){
            
            if(i > self._current){
                step.leave('error');
                step.leave('active');
                step.leave('done');

                if(!self.options.enableWhenVisited ){
                    step.enter('disabled');
                }
            }
        });
    },

    keydown: function(e) {
        if (/input|textarea/i.test(e.target.tagName)) return;
        switch (e.which) {
            case 37: this.back(); break;
            case 39: this.next(); break;
            default: return;
        }

        e.preventDefault();
    },

    trigger: function(eventType){
        var method_arguments = Array.prototype.slice.call(arguments, 1);
        var data = [this].concat(method_arguments);

        this.$element.trigger('wizard::' + eventType, data);

        // callback
        eventType = eventType.replace(/\b\w+\b/g, function(word) {
            return word.substring(0, 1).toUpperCase() + word.substring(1);
        });

        var onFunction = 'on' + eventType;
        if (typeof this.options[onFunction] === 'function') {
            this.options[onFunction].apply(this, method_arguments);
        }
    },

    get: function(index) {
        if(typeof index === 'string' && index.substring(0, 1) === '#'){
            var id = index.substring(1);
            for(var i in this.steps){
                if(this.steps[i].$pane.attr('id') === id){
                    return this.steps[i];
                }
            }
        }

        if(index < this.length() && this.steps[index]){
            return this.steps[index];
        }

        return null;
    },

    goTo: function(index, callback) {
        if(index === this._current || this.transitioning === true){
            return false;
        }

        var current = this.current();
        var to = this.get(index);

        if(index > this._current){
            if(!current.validate()){
                current.leave('done');
                current.enter('error');

                return -1;
            } else {
                current.leave('error');

                if(index > this._current) {
                    current.enter('done');
                }
            }
        }     

        var self = this;
        var process = function (){
            self.trigger('beforeChange', current, to);
            self.transitioning = true;
            
            current.hide();
            to.show(function(){
                self._current = index;
                self.transitioning = false;
                this.leave('disabled');

                self.updateButtons();
                self.updateSteps();

                if(self.options.autoFocus){
                    var $input = this.$pane.find(':input');
                    if($input.length > 0) {
                        $input.eq(0).focus();
                    } else {
                        this.$pane.focus();
                    }
                }

                if($.isFunction(callback)){
                    callback.call(self);
                }

                self.trigger('afterChange', current, to);
            });
        };

        if(to.loader){
            to.load(function(){
                process();
            });
        } else {
            process();
        }

        return true;
    },

    length: function() {
        return this.steps.length;
    },

    current: function() {
        return this.get(this._current);
    },

    currentIndex: function() {
        return this._current;
    },

    lastIndex: function(){
        return this.length() - 1;
    },

    next: function() {
        if(this._current < this.lastIndex()){
            var from = this._current, to = this._current + 1;

            this.goTo(to, function(){
                this.trigger('next', this.get(from), this.get(to));
            });
        }

        return false;
    },

    back: function() {
        if(this._current > 0) {
            var from = this._current, to = this._current - 1;

            this.goTo(to, function(){
                this.trigger('back', this.get(from), this.get(to));
            });
        }

        return false;
    },

    first: function() {
        return this.goTo(0);
    },

    finish: function() {
        if(this._current === this.lastIndex()){
            var current = this.current();
            if(current.validate()){
                this.trigger('finish');
                current.leave('error');
                current.enter('done');
            } else {
                current.enter('error');
            }
        }
    },

    reset: function() {
        this._current = 0;

        $.each(this.steps, function(i, step){
            step.reset();
        });

        this.trigger('reset');
    }
});
