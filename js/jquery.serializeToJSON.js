/** 
 * serializeToJSON jQuery plugin
 * https://github.com/raphaelm22/jquery.serializeToJSON
 * @version: v1.2.2 (November, 2017)
 * @author: Raphael Nunes
 *
 * Created by Raphael Nunes on 2015-08-28.
 *
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 */


(function($) {
    "use strict";

    $.fn.serializeToJSON = function(options) {

		var f = {
			settings: $.extend(true, {}, $.fn.serializeToJSON.defaults, options),

			getValue: function($input) {
				var value = $input.val();

			    if ($input.is(":radio")) {
			        value = $input.filter(":checked").val() || null;
			    }

			    if ($input.is(":checkbox")) {
			        value = $($input).prop('checked');
			    }

				if (this.settings.parseBooleans) {
					var boolValue = (value + "").toLowerCase();
					if (boolValue === "true" || boolValue === "false") {
						value = boolValue === "true";
					}
				}

				var floatCondition = this.settings.parseFloat.condition;
				if (floatCondition !== undefined && (
				    (typeof(floatCondition) === "string"   && $input.is(floatCondition)) ||
				    (typeof(floatCondition) === "function" && floatCondition($input)))) {

					value = this.settings.parseFloat.getInputValue($input);
					value = Number(value);
					
                    if (this.settings.parseFloat.nanToZero && isNaN(value)){
                        value = 0;
                    }                   
                }

				return value;
			},

			createProperty: function(o, value, names, $input) {
				var navObj = o;

				for (var i = 0; i < names.length; i++) {
					var currentName = names[i];

					if (i === names.length - 1) {								
						var isSelectMultiple = $input.is("select") && $input.prop("multiple");
						
						if (isSelectMultiple && value !== null){
							navObj[currentName] = new Array();
							
							if (Array.isArray(value)){
								$(value).each(function() {
									navObj[currentName].push(this);
								});
							}
							else{
								navObj[currentName].push(value);
							}
						} else {
							navObj[currentName] = value;
						}
					} else {
						var arrayKey = /\[\w+\]/g.exec(currentName);
						var isArray = arrayKey != null && arrayKey.length > 0;

						if (isArray) {
							currentName = currentName.substr(0, currentName.indexOf("["));

							if (this.settings.associativeArrays) {
								if (!navObj.hasOwnProperty(currentName)) {
									navObj[currentName] = {};
								}
							} else {
								if (!Array.isArray(navObj[currentName])) {
									navObj[currentName] = new Array();
								}
							}

							navObj = navObj[currentName];

							var keyName = arrayKey[0].replace(/[\[\]]/g, "");
							currentName = keyName;
						}

						if (!navObj.hasOwnProperty(currentName)) {
							navObj[currentName] = {};
						}

						navObj = navObj[currentName];
					}
				}
			},
			
			includeUncheckValues: function(selector, formAsArray){
				$(":radio", selector).each(function(){
					var isUncheckRadio = $("input[name='" + this.name + "']:radio:checked").length === 0;
					if (isUncheckRadio)
					{
						formAsArray.push({
							name: this.name,
							value: null
						});
					}
				});
				
				$("select[multiple]", selector).each(function(){					
					if ($(this).val() === null){
						formAsArray.push({
							name: this.name,
							value: null
						});
					}
				});
			},

			serializer: function(selector) {
				var self = this;
				
				var formAsArray = $(selector).serializeArray();
				this.includeUncheckValues(selector, formAsArray);

				var serializedObject = {}
				
				$.each(formAsArray, function(i, item) {
					var $input = $(":input[name='" + item.name + "']", selector);
					
					var value = self.getValue($input);
					var names = item.name.split(".");					

					self.createProperty(serializedObject, value, names, $input);
				});

				return serializedObject;
			}
		};

		return f.serializer(this);
    };
	
	$.fn.serializeToJSON.defaults = {
        associativeArrays: true,
        parseBooleans: true,
		parseFloat: {
			condition: undefined,
			nanToZero: true,
			getInputValue: function($input){
				return $input.val().split(",").join("");
			}
		}
    };

})(jQuery);
