Wizard.defaults = {
    step: '.wizard-steps > li',

    getPane: function(index, step){
        return this.$element.find('.wizard-content').children().eq(index);
    },

    buttonsAppendTo: 'this',
    templates: {
        buttons: function(){
            var options = this.options;
            return '<div class="wizard-buttons">'+
                '<a class="wizard-back" href="#'+this.id+'" data-wizard="back" role="button">'+options.buttonLabels.back+'</a>' +
                '<a class="wizard-next" href="#'+this.id+'" data-wizard="next" role="button">'+options.buttonLabels.next+'</a>' +
                '<a class="wizard-finish" href="#'+this.id+'" data-wizard="finish" role="button">'+options.buttonLabels.finish+'</a>' +
            '</div>';
        }
    },

    classes: {
        step: {
            done: 'done',
            error: 'error',
            active: 'current',
            disabled: 'disabled',
            activing: 'activing',
            loading: 'loading'
        },

        pane: {
            active: 'active',
            activing: 'activing'
        },

        button: {
            hide: 'hide',
            disabled: 'disabled'
        }
    },

    autoFocus: true,
    keyboard: true,

    enableWhenVisited: false,

    buttonLabels: {
        next: 'Next',
        back: 'Back',
        finish: 'Finish'
    },

    loading: {
        show: function(step) {},
        hide: function(step) {},
        fail: function(step) {}
    },

    cacheContent: false,

    validator: function(step){
        return true;
    },

    onInit: null,
    onNext: null,
    onBack: null,
    onReset: null,

    onBeforeShow: null,
    onAfterShow: null,
    onBeforeHide: null,
    onAfterHide: null,
    onBeforeLoad: null,
    onAfterLoad: null,

    onBeforeChange: null,
    onAfterChange: null,

    onStateChange: null,

    onFinish: null
};
