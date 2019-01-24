jQuery Steps Plugin [![Build Status](https://travis-ci.org/rstaib/jquery-steps.svg?branch=master)](https://travis-ci.org/rstaib/jquery-steps) [![Bower version](https://badge.fury.io/bo/jquery.steps.svg)](http://badge.fury.io/bo/jquery.steps) [![NuGet version](https://badge.fury.io/nu/jquery.steps.svg)](http://badge.fury.io/nu/jquery.steps)
============

A powerful jQuery wizard plugin that supports accessibility and HTML5.

## Getting Started

**jQuery Steps** is a lightweight wizard UI component written for **jQuery**.

Everything you need to start is:

1. Include **jQuery** and **jQuery Steps** in your HTML code.
2. Then select an element represents the wizard and call the `steps` method.

```html
<!DOCTYPE html>
<html>
    <head>
        <title>Demo</title>
        <meta charset="utf-8">
        <script src="jquery.js"></script> 
        <script src="jquery.steps.js"></script>
        <link href="jquery.steps.css" rel="stylesheet">
    </head>
    <body>
        <script>
            $("#wizard").steps();
        </script>
        <div id="wizard"></div>
    </body>
</html>
```

> For more information [check the documentation](https://github.com/rstaib/jquery-steps/wiki).

### How to add initial steps?

There are two ways to add steps and their corresponding content.

1. Add HTML code into the representing wizard element.

```html
<div id="wizard">
    <h1>First Step</h1>
    <div>First Content</div>

    <h1>Second Step</h1>
    <div>Second Content</div>
</div>
```

2. Or use the API to add steps dynamically.

```javascript
// Initialize wizard
var wizard = $("#wizard").steps();

// Add step
wizard.steps("add", {
    title: "HTML code", 
    content: "<strong>HTML code</strong>"
});
```

> For more samples [check the demos](https://github.com/rstaib/jquery-steps/wiki#demo).

## Reporting an Issue

Instructions will follow soon!

## Asking questions

I'm always happy to help answer your questions. The best way to get quick answers is to go to [stackoverflow.com](http://stackoverflow.com) and tag your questions always with **jquery-steps**.

## Contributing

Instructions will follow soon!

## License

Copyright (c) 2013 Rafael J. Staib Licensed under the [MIT license](https://github.com/rstaib/jquery-steps/blob/master/LICENSE.txt).
