var counter = 0;

var Wizard = function(element, options) {
    this.$element = $(element);

    this.options = $.extend(true, {}, Wizard.defaults, options);

    this.$steps = this.$element.find(this.options.step);

    this.id = this.$element.attr('id');
    if(!this.id){
        this.id = 'wizard-' + (++counter);
        this.$element.attr('id', this.id);
    }

    this.initialize();
}
