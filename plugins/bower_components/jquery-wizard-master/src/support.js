var Support = (function() {
    var style = $('<support>').get(0).style,
        prefixes = ['webkit', 'Moz', 'O', 'ms'],
        events = {
            transition: {
                end: {
                    WebkitTransition: 'webkitTransitionEnd',
                    MozTransition: 'transitionend',
                    OTransition: 'oTransitionEnd',
                    transition: 'transitionend'
                }
            }
        },
        tests = {
            csstransitions: function() {
                return !!test('transition');
            }
        };

    function test(property, prefixed) {
        var result = false,
            upper = property.charAt(0).toUpperCase() + property.slice(1);

        if (style[property] !== undefined) {
            result = property;
        }
        if (!result) {
            $.each(prefixes, function(i, prefix) {
                if (style[prefix + upper] !== undefined) {
                    result = '-' + prefix.toLowerCase() + '-' + upper;
                    return false;
                }
            });
        }

        if (prefixed) {
            return result;
        }
        if (result) {
            return true;
        } else {
            return false;
        }
    }

    function prefixed(property) {
        return test(property, true);
    }
    var support = {};
    if (tests.csstransitions()) {
        /* jshint -W053 */
        support.transition = new String(prefixed('transition'))
        support.transition.end = events.transition.end[support.transition];
    }

    return support;
})();

