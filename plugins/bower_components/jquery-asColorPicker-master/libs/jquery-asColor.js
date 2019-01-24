/*! asColor - v0.2.1 - 2014-08-27
* https://github.com/amazingSurge/asColor
* Copyright (c) 2014 amazingSurge; Licensed GPL */
(function(window, document, $, undefined) {
    'use strict';

    function expandHex(hex) {
        if (!hex) {
            return null;
        }
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        return hex.length === 6 ? hex : null;
    }

    function shrinkHex(hex) {
        if (hex.length === 6 && hex[0] === hex[1] && hex[2] === hex[3] && hex[4] === hex[5]) {
            return hex[0] + hex[2] + hex[4];
        } else {
            return hex;
        }
    }

    function parseIntFromHex(val) {
        return parseInt(val, 16);
    }

    function isPercentage(n) {
        return typeof n === 'string' && n.indexOf('%') != -1;
    }

    function conventPercentageToRgb(n) {
        return parseInt(n.slice(0, -1) * 2.55, 10);
    }

    function convertPercentageToFloat(n) {
        return parseFloat(n.slice(0, -1) / 100, 10);
    }

    function flip(o) {
        var flipped = {};
        for (var i in o) {
            if (o.hasOwnProperty(i)) {
                flipped[o[i]] = i;
            }
        }
        return flipped;
    }

    var CssColorStrings = (function() {
        var CSS_INTEGER = '[-\\+]?\\d+%?';
        var CSS_NUMBER = '[-\\+]?\\d*\\.\\d+%?';
        var CSS_UNIT = '(?:' + CSS_NUMBER + ')|(?:' + CSS_INTEGER + ')';

        var PERMISSIVE_MATCH3 = '[\\s|\\(]+(' + CSS_UNIT + ')[,|\\s]+(' + CSS_UNIT + ')[,|\\s]+(' + CSS_UNIT + ')\\s*\\)';
        var PERMISSIVE_MATCH4 = '[\\s|\\(]+(' + CSS_UNIT + ')[,|\\s]+(' + CSS_UNIT + ')[,|\\s]+(' + CSS_UNIT + ')[,|\\s]+(' + CSS_UNIT + ')\\s*\\)';

        return {
            RGB: {
                match: new RegExp('^rgb' + PERMISSIVE_MATCH3 +'$', 'i'),
                parse: function(result) {
                    return {
                        r: isPercentage(result[1]) ? conventPercentageToRgb(result[1]) : parseInt(result[1], 10),
                        g: isPercentage(result[2]) ? conventPercentageToRgb(result[2]) : parseInt(result[2], 10),
                        b: isPercentage(result[3]) ? conventPercentageToRgb(result[3]) : parseInt(result[3], 10),
                        a: 1
                    };
                },
                to: function(color) {
                    return 'rgb(' + color.r + ', ' + color.g + ', ' + color.b + ')';
                }
            },
            RGBA: {
                match: new RegExp('^rgba' + PERMISSIVE_MATCH4 +'$', 'i'),
                parse: function(result) {
                    return {
                        r: isPercentage(result[1]) ? conventPercentageToRgb(result[1]) : parseInt(result[1], 10),
                        g: isPercentage(result[2]) ? conventPercentageToRgb(result[2]) : parseInt(result[2], 10),
                        b: isPercentage(result[3]) ? conventPercentageToRgb(result[3]) : parseInt(result[3], 10),
                        a: parseFloat(result[4])
                    };
                },
                to: function(color) {
                    return 'rgba(' + color.r + ', ' + color.g + ', ' + color.b + ', ' + color.a + ')';
                }
            },
            HSL: {
                match: new RegExp('^hsl' + PERMISSIVE_MATCH3 +'$', 'i'),
                parse: function(result) {
                    var hsl = {
                        h: ((result[1] % 360) + 360) % 360,
                        s: isPercentage(result[2]) ? convertPercentageToFloat(result[2]) : parseFloat(result[2], 10),
                        l: isPercentage(result[3]) ? convertPercentageToFloat(result[3]) : parseFloat(result[3], 10),
                        a: 1
                    };

                    return AsColor.HSLToRGB(hsl);
                },
                to: function(color) {
                    var hsl = AsColor.RGBToHSL(color);
                    return 'hsl(' + parseInt(hsl.h, 10) + ', ' + Math.round(hsl.s * 100) + '%, ' + Math.round(hsl.l * 100) + '%)';
                }
            },
            HSLA: {
                match: new RegExp('^hsla' + PERMISSIVE_MATCH4 +'$', 'i'),
                parse: function(result) {
                    var hsla = {
                        h: ((result[1] % 360) + 360) % 360,
                        s: isPercentage(result[2]) ? convertPercentageToFloat(result[2]) : parseFloat(result[2], 10),
                        l: isPercentage(result[3]) ? convertPercentageToFloat(result[3]) : parseFloat(result[3], 10),
                        a: parseFloat(result[4])
                    };

                    return AsColor.HSLToRGB(hsla);
                },
                to: function(color) {
                    var hsl = AsColor.RGBToHSL(color);
                    return 'hsla(' + parseInt(hsl.h, 10) + ', ' + Math.round(hsl.s * 100) + '%, ' + Math.round(hsl.l * 100) + '%, ' + color.a + ')';
                }
            },
            HEX: {
                match: /^#([a-f0-9]{6}|[a-f0-9]{3})$/i,
                parse: function(result) {
                    var hex = result[1], rgb = AsColor.HEXtoRGB(hex);
                    return {
                        r: rgb.r,
                        g: rgb.g,
                        b: rgb.b,
                        a: 1
                    };
                },
                to: function(color, instance) {
                    var hex = [color.r.toString(16), color.g.toString(16), color.b.toString(16)];
                    $.each(hex, function(nr, val) {
                        if (val.length === 1) {
                            hex[nr] = '0' + val;
                        }
                    });
                    hex = hex.join('');
                    if (instance) {
                        if (instance.options.hexUseName) {
                            var hasName = AsColor.hasNAME(color);
                            if (hasName) {
                                return hasName;
                            }
                        }
                        if (instance.options.shortenHex) {
                            hex = shrinkHex(hex);
                        }
                    }
                    return '#' + hex;
                }
            },
            TRANSPARENT: {
                match: /^transparent$/i,
                parse: function() {
                    return {
                        r: 0,
                        g: 0,
                        b: 0,
                        a: 0
                    };
                },
                to: function() {
                    return 'transparent';
                }
            },
            NAME: {
                match: /^\w+$/i,
                parse: function(result) {
                    var rgb = AsColor.NAMEtoRGB(result[0]);
                    if(rgb) {
                        return {
                            r: rgb.r,
                            g: rgb.g,
                            b: rgb.b,
                            a: 1
                        };
                    }
                },
                to: function(color, instance) {
                    return AsColor.RGBtoNAME(color, instance ? instance.options.nameDegradation : undefined);
                }
            }
        };
    })();

    var AsColor = $.asColor = function(string, options) {
        if (typeof string === 'object' && typeof options === 'undefined') {
            options = string;
            string = undefined;
        }
        if(typeof options === 'string'){
            options = {
                format: options
            };
        }
        this.options = $.extend(true, {}, AsColor.defaults, options);
        this.value = {
            r: 0,
            g: 0,
            b: 0,
            h: 0,
            s: 0,
            v: 0,
            a: 1
        };
        this._format = false;
        this._matchFormat = 'HEX';
        this._valid = true;

        this.init(string);
    };

    AsColor.prototype = {
        constructor: AsColor,
        init: function(string) {
            this.format(this.options.format);         
            this.fromString(string);
        },
        isValid: function() {
            return this._valid;
        },
        val: function(value) {
            if (typeof value === 'undefined') {
                return this.toString();
            } else {
                this.fromString(value);
                return this;
            }
        },
        alpha: function(value) {
            if (typeof value === 'undefined' || isNaN(value)) {
                return this.value.a;
            } else {
                value = parseFloat(value);

                if (value > 1) {
                    value = 1;
                } else if (value < 0) {
                    value = 0;
                }
                this.value.a = value;
            }
        },
        matchString: function(string){
            return AsColor.matchString(string);
        },
        fromString: function(string, updateFormat) {
            if (typeof string === 'string') {
                string = $.trim(string);
                var matched = null,
                    rgb;
                this._valid = false;
                for (var i in CssColorStrings) {
                    if ((matched = CssColorStrings[i].match.exec(string)) != null) {
                        rgb = CssColorStrings[i].parse(matched);

                        if (rgb) {
                            this.set(rgb);
                            if(i === 'TRANSPARENT'){
                                i = 'HEX';
                            }
                            this._matchFormat = i;
                            if (updateFormat === true) {
                                this.format(i);
                            }
                            break;
                        }
                    }
                }
            } else if (typeof string === 'object') {
                this.set(string);
            }
        },
        format: function(format) {
            if (typeof format === 'string' && (format = format.toUpperCase()) && typeof CssColorStrings[format] !== 'undefined') {
                if (format !== 'TRANSPARENT') {
                    this._format = format;
                } else {
                    this._format = 'HEX';
                }
            } else if(format === false) {
                this._format = false;
            } else {
                if(this._format === false){
                    return this._matchFormat;
                } else {
                    return this._format;
                }
            }
        },
        toRGBA: function() {
            return CssColorStrings.RGBA.to(this.value, this);
        },
        toRGB: function() {
            return CssColorStrings.RGB.to(this.value, this);
        },
        toHSLA: function() {
            return CssColorStrings.HSLA.to(this.value, this);
        },
        toHSL: function() {
            return CssColorStrings.HSL.to(this.value, this);
        },
        toHEX: function() {
            return CssColorStrings.HEX.to(this.value, this);
        },
        toNAME: function() {
            return CssColorStrings.NAME.to(this.value, this);
        },
        to: function(format) {
            if (typeof format === 'string' && (format = format.toUpperCase()) && typeof CssColorStrings[format] !== 'undefined') {
                return CssColorStrings[format].to(this.value, this);
            }
            return this.toString();
        },
        toString: function() {
            var value = this.value;
            if (!this._valid) {
                value = this.options.invalidValue;

                if (typeof value === 'string') {
                    return value;
                }
            }

            if (value.a === 0 && this.options.zeroAlphaAsTransparent) {
                return CssColorStrings.TRANSPARENT.to(value, this);
            }

            var format;
            if(this._format === false){
                format = this._matchFormat;
            } else {
                format = this._format;
            }

            if (this.options.reduceAlpha && value.a === 1) {
                switch (format) {
                    case 'RGBA':
                        format = 'RGB';
                        break;
                    case 'HSLA':
                        format = 'HSL';
                        break;
                }
            }

            if (value.a !== 1 && format!=='RGBA' && format !=='HSLA' && this.options.alphaConvert){
                if(typeof this.options.alphaConvert === 'string'){
                    format = this.options.alphaConvert;
                }
                if(typeof this.options.alphaConvert[format] !== 'undefined'){
                    format = this.options.alphaConvert[format];
                }
            }
            return CssColorStrings[format].to(value, this);
        },
        get: function() {
            return this.value;
        },
        set: function(color) {
            this._valid = true;
            var fromRgb = 0,
                fromHsv = 0,
                hsv,
                rgb;

            for (var i in color) {
                if ('hsv'.indexOf(i) !== -1) {
                    fromHsv++;
                    this.value[i] = color[i];
                } else if ('rgb'.indexOf(i) !== -1) {
                    fromRgb++;
                    this.value[i] = color[i];
                } else if (i === 'a') {
                    this.value.a = color.a;
                }
            }
            if (fromRgb > fromHsv) {
                hsv = AsColor.RGBtoHSV(this.value);
                if (this.value.r === 0 && this.value.g === 0 && this.value.b === 0) {
                    // this.value.h = color.h;
                } else {
                    this.value.h = hsv.h;
                }

                this.value.s = hsv.s;
                this.value.v = hsv.v;
            } else if (fromHsv > fromRgb) {
                rgb = AsColor.HSVtoRGB(this.value);
                this.value.r = rgb.r;
                this.value.g = rgb.g;
                this.value.b = rgb.b;
            }
        }
    };
    AsColor.HSLToRGB = function(hsl) {
        var h = hsl.h / 360,
            s = hsl.s,
            l = hsl.l,
            m1, m2, rgb;
        if (l <= 0.5) {
            m2 = l * (s + 1);
        } else {
            m2 = l + s - (l * s);
        }
        m1 = l * 2 - m2;
        rgb = {
            r: AsColor.hueToRGB(m1, m2, h + 1 / 3),
            g: AsColor.hueToRGB(m1, m2, h),
            b: AsColor.hueToRGB(m1, m2, h - 1 / 3)
        };
        if (typeof hsl.a !== 'undefined') {
            rgb.a = hsl.a;
        }
        if (hsl.l === 0) {
            rgb.h = hsl.h;
        }
        return rgb;
    };
    AsColor.hueToRGB = function(m1, m2, h) {
        var v;
        if (h < 0) {
            h = h + 1;
        } else if (h > 1) {
            h = h - 1;
        }
        if ((h * 6) < 1) {
            v = m1 + (m2 - m1) * h * 6;
        } else if ((h * 2) < 1) {
            v = m2;
        } else if ((h * 3) < 2) {
            v = m1 + (m2 - m1) * (2 / 3 - h) * 6;
        } else {
            v = m1;
        }
        return Math.round(v * 255);
    };
    AsColor.RGBToHSL = function(rgb) {
        var r = rgb.r / 255,
            g = rgb.g / 255,
            b = rgb.b / 255,
            min = Math.min(r, g, b),
            max = Math.max(r, g, b),
            diff = max - min,
            add = max + min,
            l = add * 0.5,
            h, s;

        if (min === max) {
            h = 0;
        } else if (r === max) {
            h = (60 * (g - b) / diff) + 360;
        } else if (g === max) {
            h = (60 * (b - r) / diff) + 120;
        } else {
            h = (60 * (r - g) / diff) + 240;
        }
        if (diff === 0) {
            s = 0;
        } else if (l <= 0.5) {
            s = diff / add;
        } else {
            s = diff / (2 - add);
        }

        return {
            h: Math.round(h) % 360,
            s: s,
            l: l
        };
    };
    AsColor.RGBToHEX = function(rgb) {
        return CssColorStrings.HEX.to(rgb);
    };
    AsColor.HSLToHEX = function(hsl) {
        var rgb = AsColor.HSLToRGB(hsl);
        return CssColorStrings.HEX.to(rgb);
    };
    AsColor.HSVtoHEX = function(hsv) {
        var rgb = AsColor.HSVtoRGB(hsv);
        return CssColorStrings.HEX.to(rgb);
    };
    AsColor.RGBtoHSV = function(rgb) {
        var r = rgb.r / 255,
            g = rgb.g / 255,
            b = rgb.b / 255,
            max = Math.max(r, g, b),
            min = Math.min(r, g, b),
            h, s, v = max,
            diff = max - min;
        s = (max === 0) ? 0 : diff / max;
        if (max === min) {
            h = 0;
        } else {
            switch (max) {
                case r:
                    h = (g - b) / diff + (g < b ? 6 : 0);
                    break;
                case g:
                    h = (b - r) / diff + 2;
                    break;
                case b:
                    h = (r - g) / diff + 4;
                    break;
            }
            h /= 6;
        }

        return {
            h: Math.round(h * 360),
            s: s,
            v: v
        };
    };
    AsColor.HSVtoRGB = function(hsv) {
        var r, g, b, h = (hsv.h % 360) / 60,
            s = hsv.s,
            v = hsv.v,
            c = v * s,
            x = c * (1 - Math.abs(h % 2 - 1));

        r = g = b = v - c;
        h = ~~h;

        r += [c, x, 0, 0, x, c][h];
        g += [x, c, c, x, 0, 0][h];
        b += [0, 0, x, c, c, x][h];

        return {
            r: Math.round(r * 255),
            g: Math.round(g * 255),
            b: Math.round(b * 255)
        };
    };
    AsColor.HEXtoRGB = function(hex) {
        if (hex.indexOf('#') === 0) {
            hex = hex.substr(1);
        }
        if (hex.length === 3) {
            hex = expandHex(hex);
        }
        return {
            r: parseIntFromHex(hex.substr(0, 2)),
            g: parseIntFromHex(hex.substr(2, 2)),
            b: parseIntFromHex(hex.substr(4, 2))
        };
    };
    AsColor.isNAME = function(string) {
        if (AsColor.names.hasOwnProperty(string)) {
            return true;
        } else {
            return false;
        }
    };
    AsColor.NAMEtoHEX = function(name) {
        if (AsColor.names.hasOwnProperty(name)) {
            return '#' + AsColor.names[name];
        }
    };
    AsColor.NAMEtoRGB = function(name) {
        var hex = AsColor.NAMEtoHEX(name);
        if (hex) {
            return AsColor.HEXtoRGB(hex);
        }
    };
    AsColor.hasNAME = function(rgb) {
        var hex = AsColor.RGBToHEX(rgb);

        if (hex.indexOf('#') === 0) {
            hex = hex.substr(1);
        }
        hex = shrinkHex(hex);

        if (AsColor.hexNames.hasOwnProperty(hex)) {
            return AsColor.hexNames[hex];
        } else {
            return false;
        }
    },
    AsColor.RGBtoNAME = function(rgb, degradation) {
        var hasName = AsColor.hasNAME(rgb);
        if (hasName) {
            return hasName;
        } else {
            if (typeof degradation === 'undefined') {
                degradation = AsColor.defaults.nameDegradation;
            }
            return CssColorStrings[degradation.toUpperCase()].to(rgb);
        }
    };

    AsColor.matchString = function(string){
        if (typeof string === 'string') {
            string = $.trim(string);
            var matched = null,
                rgb;
            for (var i in CssColorStrings) {
                if ((matched = CssColorStrings[i].match.exec(string)) != null) {
                    rgb = CssColorStrings[i].parse(matched);

                    if (rgb) {
                        return true;
                    }
                }
            }
        }
        return false;
    };
    AsColor.defaults = {
        format: false,
        shortenHex: false,
        hexUseName: false,
        reduceAlpha: false,
        alphaConvert: { // or false will disable convert
            'RGB': 'RGBA',
            'HSL': 'HSLA',
            'HEX': 'RGBA',
            'NAME': 'RGBA',
        },
        nameDegradation: 'HEX',
        invalidValue: '',
        zeroAlphaAsTransparent: true
    };
    AsColor.names = {
        aliceblue: 'f0f8ff',
        antiquewhite: 'faebd7',
        aqua: '0ff',
        aquamarine: '7fffd4',
        azure: 'f0ffff',
        beige: 'f5f5dc',
        bisque: 'ffe4c4',
        black: '000',
        blanchedalmond: 'ffebcd',
        blue: '00f',
        blueviolet: '8a2be2',
        brown: 'a52a2a',
        burlywood: 'deb887',
        burntsienna: 'ea7e5d',
        cadetblue: '5f9ea0',
        chartreuse: '7fff00',
        chocolate: 'd2691e',
        coral: 'ff7f50',
        cornflowerblue: '6495ed',
        cornsilk: 'fff8dc',
        crimson: 'dc143c',
        cyan: '0ff',
        darkblue: '00008b',
        darkcyan: '008b8b',
        darkgoldenrod: 'b8860b',
        darkgray: 'a9a9a9',
        darkgreen: '006400',
        darkgrey: 'a9a9a9',
        darkkhaki: 'bdb76b',
        darkmagenta: '8b008b',
        darkolivegreen: '556b2f',
        darkorange: 'ff8c00',
        darkorchid: '9932cc',
        darkred: '8b0000',
        darksalmon: 'e9967a',
        darkseagreen: '8fbc8f',
        darkslateblue: '483d8b',
        darkslategray: '2f4f4f',
        darkslategrey: '2f4f4f',
        darkturquoise: '00ced1',
        darkviolet: '9400d3',
        deeppink: 'ff1493',
        deepskyblue: '00bfff',
        dimgray: '696969',
        dimgrey: '696969',
        dodgerblue: '1e90ff',
        firebrick: 'b22222',
        floralwhite: 'fffaf0',
        forestgreen: '228b22',
        fuchsia: 'f0f',
        gainsboro: 'dcdcdc',
        ghostwhite: 'f8f8ff',
        gold: 'ffd700',
        goldenrod: 'daa520',
        gray: '808080',
        green: '008000',
        greenyellow: 'adff2f',
        grey: '808080',
        honeydew: 'f0fff0',
        hotpink: 'ff69b4',
        indianred: 'cd5c5c',
        indigo: '4b0082',
        ivory: 'fffff0',
        khaki: 'f0e68c',
        lavender: 'e6e6fa',
        lavenderblush: 'fff0f5',
        lawngreen: '7cfc00',
        lemonchiffon: 'fffacd',
        lightblue: 'add8e6',
        lightcoral: 'f08080',
        lightcyan: 'e0ffff',
        lightgoldenrodyellow: 'fafad2',
        lightgray: 'd3d3d3',
        lightgreen: '90ee90',
        lightgrey: 'd3d3d3',
        lightpink: 'ffb6c1',
        lightsalmon: 'ffa07a',
        lightseagreen: '20b2aa',
        lightskyblue: '87cefa',
        lightslategray: '789',
        lightslategrey: '789',
        lightsteelblue: 'b0c4de',
        lightyellow: 'ffffe0',
        lime: '0f0',
        limegreen: '32cd32',
        linen: 'faf0e6',
        magenta: 'f0f',
        maroon: '800000',
        mediumaquamarine: '66cdaa',
        mediumblue: '0000cd',
        mediumorchid: 'ba55d3',
        mediumpurple: '9370db',
        mediumseagreen: '3cb371',
        mediumslateblue: '7b68ee',
        mediumspringgreen: '00fa9a',
        mediumturquoise: '48d1cc',
        mediumvioletred: 'c71585',
        midnightblue: '191970',
        mintcream: 'f5fffa',
        mistyrose: 'ffe4e1',
        moccasin: 'ffe4b5',
        navajowhite: 'ffdead',
        navy: '000080',
        oldlace: 'fdf5e6',
        olive: '808000',
        olivedrab: '6b8e23',
        orange: 'ffa500',
        orangered: 'ff4500',
        orchid: 'da70d6',
        palegoldenrod: 'eee8aa',
        palegreen: '98fb98',
        paleturquoise: 'afeeee',
        palevioletred: 'db7093',
        papayawhip: 'ffefd5',
        peachpuff: 'ffdab9',
        peru: 'cd853f',
        pink: 'ffc0cb',
        plum: 'dda0dd',
        powderblue: 'b0e0e6',
        purple: '800080',
        red: 'f00',
        rosybrown: 'bc8f8f',
        royalblue: '4169e1',
        saddlebrown: '8b4513',
        salmon: 'fa8072',
        sandybrown: 'f4a460',
        seagreen: '2e8b57',
        seashell: 'fff5ee',
        sienna: 'a0522d',
        silver: 'c0c0c0',
        skyblue: '87ceeb',
        slateblue: '6a5acd',
        slategray: '708090',
        slategrey: '708090',
        snow: 'fffafa',
        springgreen: '00ff7f',
        steelblue: '4682b4',
        tan: 'd2b48c',
        teal: '008080',
        thistle: 'd8bfd8',
        tomato: 'ff6347',
        turquoise: '40e0d0',
        violet: 'ee82ee',
        wheat: 'f5deb3',
        white: 'fff',
        whitesmoke: 'f5f5f5',
        yellow: 'ff0',
        yellowgreen: '9acd32'
    };
    AsColor.hexNames = flip(AsColor.names);
}(window, document, jQuery));
