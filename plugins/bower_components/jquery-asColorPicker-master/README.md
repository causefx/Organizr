# jQuery asColorPicker

The powerful jQuery plugin that for color picker. 
Download: <a href="https://github.com/amazingSurge/jquery-asColorPicker/archive/master.zip">jquery-asColorPicker-master.zip</a>

***

## Features

* **beautiful skin** — we provide some beautiful skins, it also support custom skin.
* **support all color format** — hex rgb raba hsl hsla. 
* **UX optimize** — we do a lot work to improve UX.
* **keyboard support** — we have carefully designed for keyboard support.

## Dependencies
* <a href="http://jquery.com/" target="_blank">jQuery 1.83+</a>
* <a href="https://github.com/amazingSurge/jquery-asColor" target="_blank">jquery-asColor.js</a>
* <a href="https://github.com/amazingSurge/jquery-asGradient" target="_blank">jquery-asGradient.js</a>

## Usage

Import this libraries:
* jQuery
* jquery-asColor.js
* jquery-asGradient.js
* jquery-asColorPicker.min.js

And CSS:
* asColorPicker.css 

Create base html element:
```html
    <div class="example">
        <input type="text" class="color" /> 
    </div>
```

Initialize tabs:
```javascript
$(".color").asColorPicker();
```

Or initialize tabs with custom settings:
```javascript
$(".color").asColorPicker({
	hideInput: false,
});
```

## Settings

```javascript
{   

    // Optional property, Set a namespace for css class
    namespace: 'asColorPicker',
    
    //Optional property, choose the loaded skin
    skin: null,

    //Optional property, if 'none',we can close at once needn't to give time to render css3 transition
    readonly: false,

    //Optional property, if true , it will remove trigger components, and show color panel on the page when page loaded.
    flat: true,

    //Optional property, if true, open keyboard function, note you need load jquery-asColorPicker-keyboard.js file first 
    keyboard: false,

    //Optional property, trigger when color change 
    onChange: function() {},

    //Optional property, trigger when open asColorPicker pancel, flat type will never trigger this event
    onShow: function() {},

    //Optional property, trigger when close asColorPicker pancel, flat type will never trigger this event
    onClose: function() {},

    //Optional property, trigger when init
    onInit: function() {},

    //Optional property, trigger when init, it will trigger after init event
    onReady: function() {},

    //Optional property, trigger when a color is applied
    onApply: function() {},
}
```

## Public methods

jquery asColorPicker has different methods , we can use it as below :
```javascript
// show asColorPicker panel
$(".asColorPicker").asColorPicker("show");

// close asColorPicker panel
$(".asColorPicker").asColorPicker("close");

// apply selected color
$(".asColorPicker").asColorPicker("apply");

// cancel selceted color
$(".asColorPicker").asColorPicker("cancel");

// set asColorPicker to specified color
$(".asColorPicker").asColorPicker("set", '#fff');

// get selected color
$("asColorPicker").asColorPicker("get");

// enable asColorPicker
$("asColorPicker").asColorPicker("enable");

// disable asColorPicker
$("asColorPicker").asColorPicker("disable");

// destroy asColorPicker
$("asColorPicker").asColorPicker("destroy");

```

## Event

* <code>asColorPicker::show</code>: trigger when show asColorPicker pancel, flat type will never trigger this event
* <code>asColorPicker::close</code>: trigger when close asColorPicker pancel, flat type will never trigger this event
* <code>asColorPicker::apply</code>: trigger when a color is applied
* <code>asColorPicker::init</code>: trigger when init
* <code>asColorPicker::ready</code>: trigger after init event
* <code>asColorPicker::change</code>: trigger when color change

how to use event:
```javascript
$(document).on('asColorPicker::init', function(event,instance) {
    // instance means current asColorPicker instance 
    // some stuff
});
```

## Author
[amazingSurge](http://amazingSurge.com)

## License
jQuery-asColorPicker plugin is released under the <a href="https://github.com/amazingSurge/jquery-asColorPicker/blob/master/LICENCE.GPL" target="_blank">GPL licence</a>.