#[jquery-wizard](https://github.com/amazingSurge/jquery-wizard) ![bower][bower-image] [![devDependency status][devdeps-image]][devdeps-link]

`jquery-wizard` is a lightweight jquery plugin for creating step-by-step wizards.

##Getting jquery-wizard

###Download

Get the latest build, ready to go:

 * [Development](https://raw.githubusercontent.com/amazingSurge/jquery-wizard/master/dist/jquery-wizard.js) - unminified
 * [Production](https://raw.githubusercontent.com/amazingSurge/jquery-wizard/master/dist/jquery-wizard.min.js) - minified

###Build From Source

If you want build from source:

    git clone git@github.com:amazingSurge/jquery-wizard.git
    cd jquery-wizard
    sudo npm install
    grunt

Done!

###Install From Bower

    bower install jquery-wizard.js

## Usage

### Include Files
```html
<link rel="stylesheet" href="wizard.css">

<script src="jquery.min.js"></script>
<script src="jquery-wizard.min.js"></script>
```

### Html Structure
```html
<div class="wizard">
    <ul class="wizard-steps" role="tablist">
        <li class="active" role="tab">
            Step 1
        </li>
        <li role="tab">
            Step 2
        </li>
        <li role="tab">
            Step 3
        </li>
    </ul>
    <div class="wizard-content">
        <div class="wizard-pane active" role="tabpanel">Step Content 1</div>
        <div class="wizard-pane" role="tabpanel">Step Content 2</div>
        <div class="wizard-pane" role="tabpanel">Step Content 3</div>
    </div>
</div>
```

### Javascript
```html
<scritp>
(function(){
    $('.wizard').wizard({
        onFinish: function(){
            // do something
        }
    });
})();
</scritp>
```

## Options
```javascript
    $('.wizard').wizard({
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

        // state classes
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

        // callbacks
        onInit: function(){},
        onNext: function(from, to){},
        onBack: function(from, to){},
        onReset: function(){},

        onBeforeShow: function(from, to){},
        onAfterShow: function(step){},
        onBeforeHide: function(step){},
        onAfterHide: function(step){},
        onBeforeLoad: function(step){},
        onAfterLoad: function(step){},

        onBeforeChange: function(from, to){},
        onAfterChange: function(from, to){},

        onStateChange: function(step, enter, state){},

        onFinish: function(){}
    });
```

## Bugs and feature requests

Anyone and everyone is welcome to contribute. Please take a moment to
review the [guidelines for contributing](CONTRIBUTING.md). Make sure you're using the latest version of jquery-wizard before submitting an issue.

* [Bug reports](CONTRIBUTING.md#bug-reports)
* [Feature requests](CONTRIBUTING.md#feature-requests)

## Copyright and license

Copyright (C) 2015 amazingSurge.

Licensed under [the GPL license](LICENSE-GPL).

[bower-image]: https://img.shields.io/bower/v/jquery-wizard.js.svg?style=flat
[bower-link]: https://david-dm.org/amazingSurge/jquery-wizard.js/dev-status.svg

[devdeps-image]: https://img.shields.io/david/dev/amazingSurge/jquery-wizard.svg?style=flat
[devdeps-link]: https://david-dm.org/amazingSurge/jquery-wizard#info=devDependencies
