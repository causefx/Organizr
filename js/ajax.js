function ajax_request(method, action, data, callback, options) {
	var ajax_response_action = function(data, req_code) {
		if (typeof options === 'object' && options.replacecallback === true && typeof callback === 'function') {
			callback(data, req_code);
		} else {
			// Use just JSON data
			data = data.responseJSON;
			
			// Check if data object is valid
			if (typeof data === 'object') {				
				// Check if we are doing notifications
				if (typeof data.notify === 'object') {
					if (typeof parent.notify === 'function') {
						var notifyString = (data.notify.html?data.notify.html:'Ajax Complete!;')
						var notifyIcon = (data.notify.icon?data.notify.icon:'bullhorn;')
						var notifyType = (data.notify.type?data.notify.type:'success;')
						var notifyLength = (data.notify.length?data.notify.length:4000)
						var notifyLayout = (data.notify.layout?data.notify.layout:'bar')
						var notifyEffect = (data.notify.effect?data.notify.effect:'slidetop')
						
						parent.notify(notifyString, notifyIcon, notifyType, notifyLength, notifyLayout, notifyEffect);
					} else {
						console.log(data.notify);
					}
				}
				
				// Show Apply
				if (data.show_apply === true) {
					$('#apply').show();
				}
				
				// Callback
				if (typeof data.callback === 'string') {
					eval(data.callback);
				}
				
				// Internal Process Function
				var scopefunctions = function(scopeObj, data) {
					
					// Reload?
					if (data.reload === true) {
						console.log(scopeObj.location.href );
						scopeObj.location.href = scopeObj.location.href;
						scopeObj.location.reload(true);
					}
					
					// Navigate?
					if (typeof data.goto === 'string') {
						if (!/^http(s)?:\/\//i.test(data.goto)) {
							if (/^\//.test(data.goto)) {
								data.goto = scopeObj.location.origin + data.goto;
							} else {
								var currentLoc = scopeObj.location.pathname.split('/');
								currentLoc.splice(-1);
								data.goto = currentLoc.join('/') + '/' + data.goto;
							}
						}
						scopeObj.location.href = data.goto;
					}
					
					// Replace body with
					if (typeof data.content === 'string') {
						scopeObj.document.getElementsByTagName('body').innerHTML = data.content;
					}
				}
				
				// Tab Scope
				if (typeof data.tab === 'object') {
					scopefunctions(window, data.tab);
				}
				
				// Page Functions
				if (typeof data.parent === 'object') {
					scopefunctions(window.parent, data.parent);
				}
			} else {
				// Dunno what this is
				console.log(data);
			}
			
			// Custom Callback (in addition to default actions);
			if (typeof callback !== 'undefined') {
				if (typeof callback === 'function') {
					callback(data, req_code);
				} else {
					console.log('Specified callback is not valid');
					console.log(callback);
				}
			}
		}
	};
	
	// Data must be an object
	if (typeof data !== 'object') { data = {}; }
	var ajax_settings = {
		async: true,
		accepts: 'application/json',
		cache: false,
		complete: ajax_response_action,
		contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
		crossDomain: false,
		data: data,
		dataType: 'json',
		error: function (xhr, req_code, error) {
			console.log(xhr);
			console.log(req_code);
			console.log(error);
		},
		headers: {
			action: action,
		},
		method: method,
	};
	
	var result = $.ajax('ajax.php?a='+action, ajax_settings);
	
	console.log(result);
	
	return result;
}