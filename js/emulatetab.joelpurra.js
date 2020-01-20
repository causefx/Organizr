/*global document:true, jQuery:true */

// Set up namespace, if needed
var JoelPurra = JoelPurra || {};

(function(document, $, namespace, pluginName) {
    "use strict";

    var eventNamespace = "." + pluginName,

        // TODO: get code for :focusable, :tabbable from jQuery UI?
        focusable = ":input, a[href]",

        // Keep a reference to the last focused element, use as a last resort.
        lastFocusedElement = null,

        // Private methods
        internal = {
            escapeSelectorName: function(str) {
                // Based on http://api.jquery.com/category/selectors/
                // Still untested
                return str.replace(/(!"#$%&'\(\)\*\+,\.\/:;<=>\?@\[\]^`\{\|\}~)/g, "\\\\$1");
            },

            findNextFocusable: function($from, offset) {
                var $focusable = $(focusable)
                    .not(":disabled")
                    .not(":hidden")
                    .not("a[href]:empty");

                if ($from[0].tagName === "INPUT" && $from[0].type === "radio" && $from[0].name !== "") {
                    var name = internal.escapeSelectorName($from[0].name);

                    $focusable = $focusable
                        .not("input[type=radio][name=" + name + "]")
                        .add($from);
                }

                var currentIndex = $focusable.index($from);

                var nextIndex = (currentIndex + offset) % $focusable.length;

                if (nextIndex <= -1) {
                    nextIndex = $focusable.length + nextIndex;
                }

                var $next = $focusable.eq(nextIndex);

                return $next;
            },

            focusInElement: function(event) {
                lastFocusedElement = event.target;
            },

            tryGetElementAsNonEmptyJQueryObject: function(selector) {
                try {
                    var $element = $(selector);

                    if ( !! $element && $element.size() !== 0) {
                        return $element;
                    }
                } catch (e) {
                    // Could not use element. Do nothing.
                }

                return null;
            },

            // Fix for EmulateTab Issue #2
            // https://github.com/joelpurra/emulatetab/issues/2
            // Combined function to get the focused element, trying as long as possible.
            // Extra work done trying to avoid problems with security features around
            // <input type="file" /> in Firefox (tested using 10.0.1).
            // http://stackoverflow.com/questions/9301310/focus-returns-no-element-for-input-type-file-in-firefox
            // Problem: http://jsfiddle.net/joelpurra/bzsv7/
            // Fixed:   http://jsfiddle.net/joelpurra/bzsv7/2/

            getFocusedElement: function() {
                // 1. Try the well-known, recommended method first.
                //
                // 2. Fall back to a fast method that might fail.
                // Known to fail for Firefox (tested using 10.0.1) with
                // Permission denied to access property "nodeType".
                //
                // 3. As a last resort, use the last known focused element.
                // Has not been tested enough to be sure it works as expected
                // in all browsers and scenarios.
                //
                // 4. Empty fallback
                var $focused = internal.tryGetElementAsNonEmptyJQueryObject(":focus") || internal.tryGetElementAsNonEmptyJQueryObject(document.activeElement) || internal.tryGetElementAsNonEmptyJQueryObject(lastFocusedElement) || $();

                return $focused;
            },

            emulateTabbing: function($from, offset) {
                var $next = internal.findNextFocusable($from, offset);

                $next.focus();
            },

            initializeAtLoad: function() {
                // Start listener that keep track of the last focused element.
                $(document)
                    .on("focusin" + eventNamespace, internal.focusInElement);
            }
        },

        plugin = {
            tab: function($from, offset) {
                // Tab from focused element with offset, .tab(-1)
                if ($.isNumeric($from)) {
                    offset = $from;
                    $from = undefined;
                }

                $from = $from || plugin.getFocused();

                offset = offset || +1;

                internal.emulateTabbing($from, offset);
            },

            forwardTab: function($from) {
                return plugin.tab($from, +1);
            },

            reverseTab: function($from) {
                return plugin.tab($from, -1);
            },

            getFocused: function() {
                return internal.getFocusedElement();
            }
        },

        installJQueryExtensions = function() {
            $.extend({
                emulateTab: function($from, offset) {
                    return plugin.tab($from, offset);
                }
            });

            $.fn.extend({
                emulateTab: function(offset) {
                    return plugin.tab(this, offset);
                }
            });
        },

        init = function() {
            namespace[pluginName] = plugin;

            installJQueryExtensions();

            // EmulateTab initializes listener(s) when jQuery is ready
            $(internal.initializeAtLoad);
        };

    init();
}(document, jQuery, JoelPurra, "EmulateTab"));
