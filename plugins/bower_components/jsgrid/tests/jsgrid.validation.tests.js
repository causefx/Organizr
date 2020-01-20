$(function() {

    var validators = jsGrid.validators;


    module("validation.validate", {
        setup: function() {
            this.validation = new jsGrid.Validation();
        }
    });

    test("as function", function() {
        var validateFunction = function(value) {
            return value === "test";
        };

        deepEqual(this.validation.validate({
            value: "not_test",
            rules: validateFunction
        }), [undefined]);

        deepEqual(this.validation.validate({
            value: "test",
            rules: validateFunction
        }), []);
    });

    test("as rule config", function() {
        var validateRule = {
            validator: function(value) {
                return value === "test";
            },
            message: "Error"
        };

        deepEqual(this.validation.validate({
            value: "not_test",
            rules: validateRule
        }), ["Error"]);

        deepEqual(this.validation.validate({
            value: "test",
            rules: validateRule
        }), []);
    });

    test("as rule config with param", function() {
        var validateRule = {
            validator: function(value, item, param) {
                return value === param;
            },
            param: "test",
            message: "Error"
        };

        deepEqual(this.validation.validate({
            value: "not_test",
            rules: validateRule
        }), ["Error"]);

        deepEqual(this.validation.validate({
            value: "test",
            rules: validateRule
        }), []);
    });

    test("as array of rules", function() {
        var validateRules = [{
            message: "Error",
            validator: function(value) {
                return value !== "";
            }
        }, {
            validator: function(value) {
                return value === "test";
            }
        }];

        deepEqual(this.validation.validate({
            value: "",
            rules: validateRules
        }), ["Error", undefined]);

        deepEqual(this.validation.validate({
            value: "test",
            rules: validateRules
        }), []);
    });

    test("as string", function() {
        validators.test_validator = function(value) {
            return value === "test";
        };

        deepEqual(this.validation.validate({
            value: "not_test",
            rules: "test_validator"
        }), [undefined]);

        deepEqual(this.validation.validate({
            value: "test",
            rules: "test_validator"
        }), []);

        delete validators.test_validator;
    });

    test("as rule config with validator as string", function() {
        validators.test_validator = function(value) {
            return value === "test";
        };

        var validateRule = {
            validator: "test_validator",
            message: "Error"
        };

        deepEqual(this.validation.validate({
            value: "not_test",
            rules: validateRule
        }), ["Error"]);

        deepEqual(this.validation.validate({
            value: "test",
            rules: validateRule
        }), []);

        delete validators.test_validator;
    });

    test("as array of mixed rules", function() {
        validators.test_validator = function(value) {
            return value === "test";
        };

        var validationRules = [
            "test_validator",
            function(value) {
                return value !== "";
            }, {
                validator: function(value) {
                    return value === "test";
                },
                message: "Error"
            }
        ];

        deepEqual(this.validation.validate({
            value: "",
            rules: validationRules
        }), [undefined, undefined, "Error"]);

        deepEqual(this.validation.validate({
            value: "not_test",
            rules: validationRules
        }), [undefined, "Error"]);

        deepEqual(this.validation.validate({
            value: "test",
            rules: validationRules
        }), []);

        delete validators.test_validator;
    });

    test("as string validator with default error message", function() {
        validators.test_validator = {
            message: function(value) {
                return "Error: " + value;
            },
            validator: function(value) {
                return value === "test";
            }
        };

        var validateRule = {
            validator: "test_validator"
        };

        deepEqual(this.validation.validate({
            value: "not_test",
            rules: validateRule
        }), ["Error: not_test"]);

        deepEqual(this.validation.validate({
            value: "test",
            rules: validateRule
        }), []);

        delete validators.test_validator;
    });

    test("throws exception for unknown validator", function() {
        var validateRule = {
            validator: "unknown_validator"
        };

        var validation = this.validation;

        throws(function() {
            validation.validate({
                value: "test",
                rules: validateRule
            });
        }, /unknown validator "unknown_validator"/, "exception for unknown validator");
    });


    module("validators", {
        setup: function() {
            var validation = new jsGrid.Validation();

            this.testValidator = function(validator, value, param) {
                var result = validation.validate({
                    value: value,
                    rules: { validator: validator, param: param }
                });

                return !result.length;
            }
        }
    });

    test("required", function() {
        equal(this.testValidator("required", ""), false);
        equal(this.testValidator("required", undefined), false);
        equal(this.testValidator("required", null), false);
        equal(this.testValidator("required", 0), true);
        equal(this.testValidator("required", "test"), true);
    });

    test("rangeLength", function() {
        equal(this.testValidator("rangeLength", "123456", [0, 5]), false);
        equal(this.testValidator("rangeLength", "", [1, 5]), false);
        equal(this.testValidator("rangeLength", "123", [0, 5]), true);
        equal(this.testValidator("rangeLength", "", [0, 5]), true);
        equal(this.testValidator("rangeLength", "12345", [0, 5]), true);
    });

    test("minLength", function() {
        equal(this.testValidator("minLength", "123", 5), false);
        equal(this.testValidator("minLength", "12345", 5), true);
        equal(this.testValidator("minLength", "123456", 5), true);
    });

    test("maxLength", function() {
        equal(this.testValidator("maxLength", "123456", 5), false);
        equal(this.testValidator("maxLength", "12345", 5), true);
        equal(this.testValidator("maxLength", "123", 5), true);
    });

    test("pattern", function() {
        equal(this.testValidator("pattern", "_13_", "1?3"), false);
        equal(this.testValidator("pattern", "13", "1?3"), true);
        equal(this.testValidator("pattern", "3", "1?3"), true);
        equal(this.testValidator("pattern", "_13_", /1?3/), true);
    });

    test("range", function() {
        equal(this.testValidator("range", 6, [0, 5]), false);
        equal(this.testValidator("range", 0, [1, 5]), false);
        equal(this.testValidator("range", 3, [0, 5]), true);
        equal(this.testValidator("range", 0, [0, 5]), true);
        equal(this.testValidator("range", 5, [0, 5]), true);
    });

    test("min", function() {
        equal(this.testValidator("min", 3, 5), false);
        equal(this.testValidator("min", 5, 5), true);
        equal(this.testValidator("min", 6, 5), true);
    });

    test("max", function() {
        equal(this.testValidator("max", 6, 5), false);
        equal(this.testValidator("max", 5, 5), true);
        equal(this.testValidator("max", 3, 5), true);
    });

});
