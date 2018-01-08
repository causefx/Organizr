(function(jsGrid, $, undefined) {

    function LoadIndicator(config) {
        this._init(config);
    }

    LoadIndicator.prototype = {

        container: "body",
        message: "Loading...",
        shading: true,

        zIndex: 1000,
        shaderClass: "jsgrid-load-shader",
        loadPanelClass: "jsgrid-load-panel",

        _init: function(config) {
            $.extend(true, this, config);

            this._initContainer();
            this._initShader();
            this._initLoadPanel();
        },

        _initContainer: function() {
            this._container = $(this.container);
        },

        _initShader: function() {
            if(!this.shading)
                return;

            this._shader = $("<div>").addClass(this.shaderClass)
                .hide()
                .css({
                    position: "absolute",
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    zIndex: this.zIndex
                })
                .appendTo(this._container);
        },

        _initLoadPanel: function() {
            this._loadPanel = $("<div>").addClass(this.loadPanelClass)
                .text(this.message)
                .hide()
                .css({
                    position: "absolute",
                    top: "50%",
                    left: "50%",
                    zIndex: this.zIndex
                })
                .appendTo(this._container);
        },

        show: function() {
            var $loadPanel = this._loadPanel.show();

            var actualWidth = $loadPanel.outerWidth();
            var actualHeight = $loadPanel.outerHeight();

            $loadPanel.css({
                marginTop: -actualHeight / 2,
                marginLeft: -actualWidth / 2
            });

            this._shader.show();
        },

        hide: function() {
            this._loadPanel.hide();
            this._shader.hide();
        }

    };

    jsGrid.LoadIndicator = LoadIndicator;

}(jsGrid, jQuery));
