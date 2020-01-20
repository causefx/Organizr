(function(jsGrid, $, undefined) {

    function Validation(config) {
        this._init(config);
    }

    Validation.prototype = {

        _init: function(config) {
            $.extend(true, this, config);
        },

        validate: function(args) {
            var errors = [];

            $.each(this._normalizeRules(args.rules), function(_, rule) {
                if(rule.validator(args.value, args.item, rule.param))
                    return;

                var errorMessage = $.isFunction(rule.message) ? rule.message(args.value, args.item) : rule.message;
                errors.push(errorMessage);
            });

            return errors;
        },

        _normalizeRules: function(rules) {
            if(!$.isArray(rules))
                rules = [rules];

            return $.map(rules, $.proxy(function(rule) {
                return this._normalizeRule(rule);
            }, this));
        },

        _normalizeRule: function(rule) {
            if(typeof rule === "string")
                rule = { validator: rule };

            if($.isFunction(rule))
                rule = { validator: rule };

            if($.isPlainObject(rule))
                rule = $.extend({}, rule);
            else
                throw Error("wrong validation config specified");

            if($.isFunction(rule.validator))
                return rule;

            return this._applyNamedValidator(rule, rule.validator);
        },

        _applyNamedValidator: function(rule, validatorName) {
            delete rule.validator;

            var validator = validators[validatorName];
            if(!validator)
                throw Error("unknown validator \"" + validatorName + "\"");

            if($.isFunction(validator)) {
                validator = { validator: validator };
            }

            return $.extend({}, validator, rule);
        }
    };

    jsGrid.Validation = Validation;


    var validators = {
        required: {
            message: "Field is required",
            validator: function(value) {
                return value !== undefined && value !== null && value !== "";
            }
        },

        rangeLength: {
            message: "Field value length is out of the defined range",
            validator: function(value, _, param) {
                return value.length >= param[0] && value.length <= param[1];
            }
        },

        minLength: {
            message: "Field value is too long",
            validator: function(value, _, param) {
                return value.length >= param;
            }
        },

        maxLength: {
            message: "Field value is too short",
            validator: function(value, _, param) {
                return value.length <= param;
            }
        },

        pattern: {
            message: "Field value is not matching the defined pattern",
            validator: function(value, _, param) {
                if(typeof param === "string") {
                    param = new RegExp("^(?:" + param + ")$");
                }
                return param.test(value);
            }
        },

        range: {
            message: "Field value is out of the defined range",
            validator: function(value, _, param) {
                return value >= param[0] && value <= param[1];
            }
        },

        min: {
            message: "Field value is too large",
            validator: function(value, _, param) {
                return value >= param;
            }
        },

        max: {
            message: "Field value is too small",
            validator: function(value, _, param) {
                return value <= param;
            }
        }
    };

    jsGrid.validators = validators;

}(jsGrid, jQuery));
