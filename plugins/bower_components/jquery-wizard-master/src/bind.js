$(document).on('click', '[data-wizard]', function(e){
    var href;
    var $this = $(this);
    var $target = $($this.attr('data-target') || (href = $this.attr('href')) && href.replace(/.*(?=#[^\s]+$)/, ''));

    var wizard = $target.data('wizard');

    if(!wizard){
        return;
    }

    var method = $this.data('wizard');

    if(/^(back|next|first|finish|reset)$/.test(method)){
        wizard[method]();
    }

    e.preventDefault();
});
