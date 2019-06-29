// Create language switcher instance
var lang = new Lang();
var langStrings = { "token": {} };
loadLanguageList();
var falbackLanguage = (languageList.filter(p => p.code == language(moment.locale(navigator.languages[0]))).length > 0 ? language(moment.locale(navigator.languages[0])) : 'en');
lang.init({
	//defaultLang: 'en',
	currentLang: (getCookie('organizrLanguage')) ? getCookie('organizrLanguage') : falbackLanguage,
	cookie: {
		name: 'organizrLanguage',
		expiry: 365,
		path: '/'
	},
	allowCookieOverride: true
});
var timeouts = {};
var increment = 0;
var tabInformation = {};
var tabActionsList = [];
tabActionsList['refresh'] = [];
tabActionsList['close'] = [];

// Start Organizr
$(document).ready(function () {
    launch();
    local('r','loggingIn');
});
/* NORMAL FUNCTIONS */
function setLangCookie(lang){
    Cookies.set('organizrLanguage',lang, {
        expires: 365,
        path: '/'
    });
}
function toggleDebug(){
    $('.debugModal').modal('show')
}
function highlightObject(json) {
    if (typeof json != 'string') {
        json = JSON.stringify(json, undefined, '\t');
    }
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var cls = 'number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'key';
            } else {
                cls = 'string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'boolean';
        } else if (/null/.test(match)) {
            cls = 'null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}
function orgDebug(cmd) {
    var cmd = $('#debug-input').val();
    var result = '';
    if (cmd !== '') {
        result = eval(cmd);
    }
    if (result !== '') {
        $('#debugResultsBox').removeClass('hidden');
        $('#debugResults').html(formatDebug(result));
        $('.cmdName').text(cmd);
        if(bowser.mobile !== true){
            $('#debugResults > .whitebox').slimScroll();
        }
    } else {

    }
}
function jsonToHTML(json){
    var html = '';
    $.each(json, function(i,v) {

        if(typeof v === 'object'){
            html += i + ': <br/>';
                $.each(v, function(index,value) {
                html += '&nbsp; &nbsp; &nbsp; &nbsp;' + index + ': ' + value + '<br/>';
            });
        }else{
            html += i + ': ' + v + '<br/>';
        }
    });
    return html;
}
function copyDebug(){
    var pre = $('#debugPreInfo').find('.whitebox').text();
    var debug = $('#debugResults').find('.whitebox').text();
    clipboard(true, pre + debug);
    console.log('copied');
    console.log(pre + debug);
}
function formatDebug(result){
    var formatted = '';
    switch (typeof result) {
        case 'object':
            //formatted = highlightObject(result);
            formatted = jsonToHTML(result);
            break;
        default:
            formatted = result;

    }
    return '<pre class="whitebox bg-org text-success">' + formatted + '</pre>';
}
function getDebugPreInfo(){
    var formatted = 'Version: ' + activeInfo.version +
        '<br/>Branch: ' + activeInfo.branch +
        '<br/>Server OS: ' + activeInfo.serverOS +
        '<br/>PHP: ' + activeInfo.phpVersion +
        '<br/>Install Type: ' + ((activeInfo.settings.misc.docker) ? 'Official Docker' : 'Native') +
        '<br/>Auth Type: ' + activeInfo.settings.misc.authType +
        '<br/>Auth Backend: ' + activeInfo.settings.misc.authBackend +
        '<br/>Installed Plugins: ' + activeInfo.settings.misc.installedPlugins +
        '<br/>Installed Themes: ' + activeInfo.settings.misc.installedThemes +
        '<br/>Theme: ' + activeInfo.theme +
        '<br/>Local: ' + activeInfo.settings.user.local +
        '<br/>oAuth: ' + activeInfo.settings.user.oAuthLogin +
        '<br/>Agent: ' + activeInfo.settings.user.agent;
    formatted = '<pre class="whitebox bg-org text-success">' + formatted + '</pre>';
    $('#debugPreInfo').html(formatted);
    if(bowser.mobile !== true){
        $('#debugPreInfo > .whitebox').slimScroll();
    }
}
function orgDebugList(cmd){
    if(cmd !== ''){
        $('#debug-input').val(cmd);
        orgDebug();
    }
}
function updateIssueLink(line){
    var preNumber = line.match(/\((.*?)\)/g);
    if(preNumber !== null){
        preNumber = preNumber.toString();
        var issueNumber = preNumber.substr(2, (preNumber.length - 3));
        var issueLink = 'https://github.com/causefx/Organizr/issues/' + issueNumber;
        issueLink = '<a href="' + issueLink + '" target="_blank">' + preNumber + '</a>';
        return line.replace(preNumber, issueLink);
    }else{
        return line;
    }
}
function clipboard(trigger = true, string = null){
    let clipboard = $('#internal-clipboard');
    if(string){
        clipboard.attr('data-clipboard-text',string );
    }
    if(trigger){
        clipboard.click();
    }
}
function getLangStrings(){
    let strings = JSON.stringify(window.langStrings, null, '\t');
    clipboard(true,strings);
    console.log('Copied JSON Strings to clipboard');
}
function getHiddenProp(){
    var prefixes = ['webkit','moz','ms','o'];
    // if 'hidden' is natively supported just return it
    if ('hidden' in document) return 'hidden';
    // otherwise loop over all the known prefixes until we find one
    for (var i = 0; i < prefixes.length; i++){
        if ((prefixes[i] + 'Hidden') in document)
            return prefixes[i] + 'Hidden';
    }
    // otherwise it's not supported
    return null;
}
function isHidden() {
    var prop = getHiddenProp();
    if (!prop) return false;
    return document[prop];
}
function loadLanguageList(){
	$.each(languageList, function(i,v) {
		lang.dynamic(v.code, 'js/langpack/'+v.filename);
	});
}
function sleep(ms) {
	return new Promise(resolve => setTimeout(resolve, ms));
}
function contains(target, pattern){
    var value = 0;
    pattern.forEach(function(word){
      value = value + target.includes(word);
    });
    return (value === 1)
}
function isNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : event.keyCode;
    if ((charCode < 48 || charCode > 57))
        return false;
    return true;
}
function setTabInfo(tab,action,value){
    if(tab == 'Organizr-Support'){
        return false;
    }
    if(tab !== null && action !== null && value !== null){
        switch(action){
            case 'active':
                $.each(tabInformation, function(i,v) {
                    tabInformation[i]['active'] = false;
                });
                break;
            default:
            //nada
        }
        tabInformation[tab][action] = value;
    }else{
        return false;
    }
}
function tabTimerAction(){
    if(tabActionsList.close.length > 0){
        $.each(tabActionsList.close, function(i,v) {
            var tab = v.tab;
            var minutes = (tabInformation[tab]['tabInfo']['timeout_ms'] / 1000) /60;
            var process = false;
            if(tabInformation[tab]['loaded']){
                if(tabInformation[tab]['active'] && idleTime >= 1){
                    process = true;
                }
                if(tabInformation[tab]['active'] === false){
                    process = true;
                }
                if(process){
                    tabInformation[tab]['increments'] = tabInformation[tab]['increments'] + 1;
                    if(tabInformation[tab]['increments'] >= minutes){
                        tabInformation[tab]['increments'] = 0;
                        console.log('Tab Function: Auto Closing tab: '+tab);
                        closeTab(tab);
                    }
                }

            }
        });
    }
    if(tabActionsList.refresh.length > 0){
        $.each(tabActionsList.refresh, function(i,v) {
            var tab = v.tab;
            var minutes = (tabInformation[tab]['tabInfo']['timeout_ms'] / 1000) /60;
            var process = false;
            if(tabInformation[tab]['loaded']){
                tabInformation[tab]['increments'] = tabInformation[tab]['increments'] + 1;
                if(tabInformation[tab]['increments'] >= minutes){
                    tabInformation[tab]['increments'] = 0;
                    console.log('Tab Function: Auto Reloading tab: '+tab);
                    reloadTab(tab, tabInformation[tab]['tabInfo']['type']);
                }
            }
        });
    }

}
function timerIncrement() {
    increment = increment + 1;
    tabTimerAction();
    //check for cookieExpiry
    if(hasCookie){
        if(getCookie('organizrToken')){
            //do nothing
        }else{
            location.reload();
        }
    }
    idleTime = idleTime + 1;
    if(activeInfo.settings.lockout.enabled && activeInfo.settings.user.oAuthLogin !== true){
        if (idleTime > activeInfo.settings.lockout.timer && $('#lockScreen').length !== 1) {
            if(activeInfo.user.groupID <= activeInfo.settings.lockout.minGroup && activeInfo.user.groupID >= activeInfo.settings.lockout.maxGroup){
                lock();
            }
        }
    }
}
function ajaxloader(element=null, action='out'){
	var loader = `
	<div class="ajaxloader">
		<svg class="circular" viewBox="25 25 50 50">
			<circle class="path" cx="50" cy="50" fill="none" r="20" stroke-miterlimit="10" stroke-width="5"></circle>
		</svg>
	</div>`;
	switch (action) {
		case 'in':
		case 'fadein':
			$(loader).appendTo(element);
			break;
		case 'out':
		case 'fadeout':
			$('.ajaxloader').remove();
			break;
		default:
			$('.ajaxloader').remove();
	}
}
function getDefault(tabName,tabType){
	if(getHash() === false){
		if(tabName !== null && tabType !== null){
			switchTab(tabName,tabType);
		}else{
			$('#side-menu').children().first().children().click()
		}

	}else{
		var hashTab = getHash();
		var hashType = getTabType(hashTab);
		if (typeof hashTab !== 'undefined' && typeof hashType !== 'undefined') {
			switchTab(hashTab,hashType);
		}else{
			console.warn("Tab Function: "+hashTab+" is not a defined tab");
		}
	}
}
function getTabType(tabName){
	var tabType = $('#menu-'+tabName);
	if (typeof tabType !== 'undefined') {
		return tabType.attr('type');
	}else{
		return false;
	}
}
function getHash(){
	if ($(location).attr('hash')){
		return $(location).attr('hash').substr(1);
	}
	return false;
}
function setHash(hash){
	window.location.hash = '#'+hash;
}
function getQueryVariable(variable){
   var query = window.location.search.substring(1);
   console.log(query);
   var vars = query.split("&");
   console.log(vars);
   for (var i=0;i<vars.length;i++) {
       var pair = vars[i].split("=");
       if(pair[0] == variable){return pair[1];}
   }
   return(false);
}
function iconPrefix(source){
	var tabIcon = source.split("::");
	var icons = {
		"materialize":"mdi mdi-",
		"fontawesome":"fa fa-",
		"themify":"ti-",
		"simpleline":"icon-",
        "weathericon":"wi wi-",
        "alphanumeric":"fa-fw",
	};
	if(Array.isArray(tabIcon) && tabIcon.length === 2){
		if(tabIcon[0] !== 'url' && tabIcon[0] !== 'alphanumeric'){
			return '<i class="'+icons[tabIcon[0]]+tabIcon[1]+' fa-fw"></i>';
		}else if(tabIcon[0] == 'alphanumeric'){
            return '<i class="fa-fw">'+tabIcon[1]+'</i>';
        }else{
			return '<img class="fa-fw" src="'+tabIcon[1]+'" alt="tabIcon" />';
		}
	}else{
		return '<img class="fa-fw" src="'+source+'" alt="tabIcon" />';
	}
}
function iconPrefixSplash(source){
    var tabIcon = source.split("::");
    var icons = {
        "materialize":"mdi mdi-",
        "fontawesome":"fa fa-",
        "themify":"ti-",
        "simpleline":"icon-",
        "weathericon":"wi wi-",
        "alphanumeric":"fa-fw",
    };
    if(Array.isArray(tabIcon) && tabIcon.length === 2){
        if(tabIcon[0] !== 'url' && tabIcon[0] !== 'alphanumeric'){
            return '<i class="'+icons[tabIcon[0]]+tabIcon[1]+' fa-fw"></i>';
        }else if(tabIcon[0] == 'alphanumeric'){
            return '<i class="fa-fw">'+tabIcon[1]+'</i>';
        }else{
            return tabIcon[1];
        }
    }else{
        return source;
    }
}
function cleanClass(string){
	return string.replace(/ +/g, "-").replace(/\W+/g, "-");
}
function noTabs(arrayItems){
	if (arrayItems.data.user.loggedin === true) {
		organizrConnect('api/?v1/no_tabs').success(function(data) {
            try {
                var response = JSON.parse(data);
            }catch(e) {
                console.log(e + ' error: ' + data);
                orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                return false;
            }
			console.log("Organizr Function: No Tabs Available");
			$(response.data).appendTo($('.organizr-area'));
		}).fail(function(xhr) {
			console.error("Organizr Function: API Connection Failed");
		});
	}else {
		$('.show-login').trigger('click');
	}
}
function formatImage (icon) {
    if (!icon.id || icon.text == 'Select or type Icon') {
        return icon.text;
    }
    var baseUrl = "/user/pages/images/flags";
    var $icon = $(
        '<span><img src="' + icon.element.value + '" class="img-chooser" /> ' + icon.text + '</span>'
    );
    return $icon;
}
function formatIcon (icon) {
    if (!icon.id || icon.text == 'Select or type Icon') {
        return icon.text;
    }
    var $icon = $(
        '<span>'+iconPrefix(icon.id)+ icon.text + '</span>'
    );
    return $icon;
}
function logout(){
	message('',' Goodbye!',activeInfo.settings.notifications.position,'#FFF','success','10000');
	organizrAPI('GET','api/?v1/logout').success(function(data) {
        try {
            var html = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		if(html.data == true){
            local('set','message','Goodbye|Logout Successful|success');
			location.reload();
		}else{
			message('Logout Error',' An Error Occured',activeInfo.settings.notifications.position,'#FFF','warning','10000');
			console.error('Organizr Function: Logout failed');
		}
	}).fail(function(xhr) {
		console.error("Organizr Function: Logout Failed");
	});
}
function reloadOrganizr(){
	location.reload();
}
function hideFrames(){
	$(".iFrame-listing div[class^='frame-container']").addClass("hidden").removeClass('show');
    $(".internal-listing div[class^='internal-container']").addClass("hidden").removeClass('show');
    $(".plugin-listing div[class^='plugin-container']").addClass("hidden").removeClass('show');
}
function closeSideMenu(){
	$('.content-wrapper').removeClass('show-sidebar');
}
function removeMenuActive(){
	$("#side-menu a").removeClass('active');
}
function swapDisplay(type){
	switch (type) {
		case 'internal':
		    $('body').removeClass('fix-header');
			$('.iFrame-listing').addClass('hidden').removeClass('show');
			$('.internal-listing').addClass('show').removeClass('hidden');
			$('.login-area').addClass('hidden').removeClass('show');
			$('.plugin-listing').addClass('hidden').removeClass('show');
			//$('body').removeClass('fix-header');
			break;
		case 'iframe':
		    $('body').addClass('fix-header');
			$('.iFrame-listing').addClass('show').removeClass('hidden');
			$('.internal-listing').addClass('hidden').removeClass('show');
			$('.login-area').addClass('hidden').removeClass('show');
			$('.plugin-listing').addClass('hidden').removeClass('show');
			//$('body').addClass('fix-header');
			break;
		case 'login':
		    $('body').removeClass('fix-header');
			$('.iFrame-listing').addClass('hidden').removeClass('show');
			$('.internal-listing').addClass('hidden').removeClass('show');
			$('.login-area').addClass('show').removeClass('hidden');
			$('.plugin-listing').addClass('hidden').removeClass('show');
			if(activeInfo.settings.misc.minimalLoginScreen == true){
                $('.sidebar').addClass('hidden');
                $('.navbar').addClass('hidden');
                $('#pagewrapper').addClass('hidden');
            }
			break;
        case 'plugin':
            $('.iFrame-listing').addClass('hidden').removeClass('show');
            $('.internal-listing').addClass('hidden').removeClass('show');
            $('.login-area').addClass('hidden').removeClass('show');
            $('.plugin-listing').addClass('show').removeClass('hidden');
            break;
		default:
	}
}
function toggleParentActive(tab){
	var childTab = $('#menu-'+tab);
	if(childTab.parent().hasClass('nav-second-level')){
		if(!childTab.parent().hasClass('in')){
			childTab.parent().addClass('collapse in');
			childTab.parent().parent().addClass('active');
		}
	}
}
function swapBodyClass(tab){
    var prior = $('body').attr('data-active-tab');
    if(prior !== ''){
        $('body').removeClass('active-tab-'+prior);
    }
    $('body').attr('data-active-tab', tab);
    $('body').addClass('active-tab-'+tab);
}
function switchTab(tab, type){
    if(type !== 2){
        hideFrames();
        closeSideMenu();
        removeMenuActive();
        toggleParentActive(tab);
        swapBodyClass(tab);
    }
    if(type !== 2 && type !== 'plugin'){
        setHash(tab);
    }
	switch (type) {
		case 0:
		case '0':
		case 'internal':
			swapDisplay('internal');
			var newTab = $('#internal-'+tab);
			var tabURL = newTab.attr('data-url');
			$('#menu-'+cleanClass(tab)).find('a').addClass("active");
			if(newTab.hasClass('loaded')){
				console.log('Tab Function: Switching to tab: '+tab);
				newTab.addClass("show").removeClass('hidden');
                setTabInfo(cleanClass(tab),'active',true);
			}else{
				$("#preloader").fadeIn();
				console.log('Tab Function: Loading new tab for: '+tab);
				$('#menu-'+tab+' a').children().addClass('tabLoaded');
				newTab.addClass("show loaded").removeClass('hidden');
				loadInternal(tabURL,cleanClass(tab));
                setTabInfo(cleanClass(tab),'active',true);
                setTabInfo(cleanClass(tab),'loaded',true);
				$("#preloader").fadeOut();
			}
			break;
		case 1:
		case '1':
		case 'iframe':
			swapDisplay('iframe');
			var newTab = $('#container-'+tab);
			var tabURL = newTab.attr('data-url');
			$('#menu-'+cleanClass(tab)).find('a').addClass("active");
			if(newTab.hasClass('loaded')){
				console.log('Tab Function: Switching to tab: '+tab);
				newTab.addClass("show").removeClass('hidden');
                setTabInfo(cleanClass(tab),'active',true);
			}else{
				$("#preloader").fadeIn();
				console.log('Tab Function: Loading new tab for: '+tab);
				$('#menu-'+tab+' a').children().addClass('tabLoaded');
				newTab.addClass("show loaded").removeClass('hidden');
				$(buildFrame(tab,tabURL)).appendTo(newTab);
                setTabInfo(cleanClass(tab),'active',true);
                setTabInfo(cleanClass(tab),'loaded',true);
				$("#preloader").fadeOut();
			}
            $('#frame-'+tab).focus();
			break;
		case 2:
		case 3:
		case '2':
		case '3':
		case '_blank':
		case 'popout':
			popTab(cleanClass(tab), type);
			break;
        case 'plugin':
            swapDisplay('plugin');
            $('#container-plugin-'+tab).addClass("show").removeClass('hidden');
            break;
		default:
			console.error('Tab Function: Action not set');
	}

}
function popTab(tab, type){
	switch (type) {
		case 0:
		case '0':
		case 'internal':
			console.warn('Tab Function: New window not supported for tab: '+tab);
			break;
		case 1:
		case '1':
		case 'iframe':
		case 2:
		case 3:
		case '2':
		case '3':
		case '_blank':
		case 'popout':
			console.log('Tab Function: Creating New Window for tab: '+tab);
			var url = $('#menu-'+cleanClass(tab)).attr('data-url');
			window.open(url, '_blank');
			break;
		default:
			console.error('Tab Function: Action not set');
	}
}
function closeTab(tab){
    tab = cleanClass(tab);
    // check if current tab?
    if($('.active-tab-'+tab).length > 0){
        closeCurrentTab();
    }else{
        if($('.frame-'+tab).hasClass('loaded')){
            var type = $('#menu-'+tab).attr('type');
           switch (type) {
               case 0:
               case '0':
               case 'internal':
                   // quick check if homepage
                   if($('#menu-'+tab).attr('data-url') == 'api/?v1/homepage/page'){
                       console.log('Organizr Function - Clearing All Homepage AJAX calls');
                       clearAJAX('homepage');
                   }
                   console.log('Tab Function: Closing tab: '+tab);
                   $('#internal-'+cleanClass(tab)).html('');
                   $('#menu-'+cleanClass(tab)+' a').removeClass("active");
                   $('#menu-'+tab+' a').children().removeClass('tabLoaded');
                   $('#internal-'+cleanClass(tab)).removeClass("loaded show");
                   $('#menu-'+cleanClass(tab)).removeClass("active");
                   setTabInfo(cleanClass(tab),'loaded',false);
                   break;
               case 1:
               case '1':
               case 'iframe':
                   console.log('Tab Function: Closing tab: '+tab);
                   $('#menu-'+cleanClass(tab)+' a').removeClass("active");
                   $('#menu-'+tab+' a').children().removeClass('tabLoaded');
                   $('#container-'+cleanClass(tab)).removeClass("loaded show");
                   $('#frame-'+cleanClass(tab)).remove();
                   setTabInfo(cleanClass(tab),'loaded',false);
                   break;
               case 2:
               case 3:
               case '2':
               case '3':
               case '_blank':
               case 'popout':

                   break;
               default:
                   console.error('Tab Function: Action not set');
           }
        }
    }
}
function reloadTab(tab, type){
	$("#preloader").fadeIn();
	console.log('Tab Function: Reloading tab: '+tab);
	switch (type) {
		case 0:
		case '0':
		case 'internal':
		    var dataURL = $('.frame-'+cleanClass(tab)).attr('data-url');
		    var dataName = $('.frame-'+cleanClass(tab)).attr('data-name');
            $('#frame-'+cleanClass(tab)).html('');
            loadInternal(dataURL,dataName);
			break;
		case 1:
		case '1':
		case 'iframe':
			$('#frame-'+cleanClass(tab)).attr('src', $('#frame-'+cleanClass(tab)).attr('src'));
			break;
		case 2:
		case 3:
		case '2':
		case '3':
		case '_blank':
		case 'popout':

			break;
		default:
			console.error('Tab Function: Action not set');
	}
	$("#preloader").fadeOut();
}
function reloadCurrentTab(){
	$("#preloader").fadeIn();
	console.log('Tab Function: Reloading Current tab');
	var iframe = $('.iFrame-listing').find('.show');
	var internal = $('.internal-listing').find('.show');
	if(iframe.length > 0){
		var type = 'iframe';
	}else if(internal.length > 0){
		var type = 'internal';
	}else{
		var type = 'not set';
	}
	switch (type) {
		case 0:
		case '0':
		case 'internal':
			var activeInternal = $('.internal-listing').find('.show');
			$(activeInternal).html('');
			loadInternal(activeInternal.attr('data-url'),activeInternal.attr('data-name'));
			break;
		case 1:
		case '1':
		case 'iframe':
			var activeFrame = $('.iFrame-listing').find('.show').children('iframe');
			activeFrame.attr('src', activeFrame.attr('src'));
			break;
		case 2:
		case 3:
		case '2':
		case '3':
		case '_blank':
		case 'popout':

			break;
		default:
			console.error('Tab Function: Action not set');
	}
	$("#preloader").fadeOut();
}
function loadNextTab(){
	var next = $('#page-wrapper').find('.loaded').attr('data-name');
	if (typeof next !== 'undefined') {
		var type = $('#page-wrapper').find('.loaded').attr('data-type');
        var parent = $('#menu-'+next).parent();
        if(parent.hasClass('in') === false){
            parent.parent().find('a').first().trigger('click')
        }
		switchTab(next,type);
	}else{
		console.log("Tab Function: No Available Tab to open");
	}
}
function closeCurrentTab(){
	var iframe = $('.iFrame-listing').find('.show');
	var internal = $('.internal-listing').find('.show');
	if(iframe.length > 0){
		var type = 'iframe';
	}else if(internal.length > 0){
		var type = 'internal';
	}else{
		var type = 'not set';
	}
	switch (type) {
		case 0:
		case '0':
		case 'internal':
			var tab = $('.internal-listing').find('.show').attr('data-name');
            // quick check if homepage
            if($('#menu-'+cleanClass(tab)).attr('data-url') == 'api/?v1/homepage/page'){
                console.log('Organizr Function - Clearing All Homepage AJAX calls');
                clearAJAX('homepage');
            }
			console.log('Tab Function: Closing tab: '+tab);
			$('#internal-'+cleanClass(tab)).html('');
			$('#menu-'+cleanClass(tab)+' a').removeClass("active");
			$('#menu-'+tab+' a').children().removeClass('tabLoaded');
			$('#internal-'+cleanClass(tab)).removeClass("loaded show");
			$('#menu-'+cleanClass(tab)).removeClass("active");
            setTabInfo(cleanClass(tab),'loaded',false);
            setTabInfo(cleanClass(tab),'active',false);
			loadNextTab();
			break;
		case 1:
		case '1':
		case 'iframe':
			var tab = $('.iFrame-listing').find('.show').children('iframe').attr('data-name');
			console.log('Tab Function: Closing tab: '+tab);
			$('#menu-'+cleanClass(tab)+' a').removeClass("active");
			$('#menu-'+tab+' a').children().removeClass('tabLoaded');
			$('#container-'+cleanClass(tab)).removeClass("loaded show");
			$('#frame-'+cleanClass(tab)).remove();
            setTabInfo(cleanClass(tab),'loaded',false);
            setTabInfo(cleanClass(tab),'active',false);
			loadNextTab();
			break;
		case 2:
		case 3:
		case '2':
		case '3':
		case '_blank':
		case 'popout':

			break;
		default:
			console.error('Tab Function: Action not set');
	}
}
function tabActions(event,name, type){
	if(event.ctrlKey && !event.shiftKey && !event.altKey){
		popTab(cleanClass(name), type);
	}else if(event.altKey && !event.shiftKey && !event.ctrlKey){
        closeTab(name);
	}else if(event.shiftKey && !event.ctrlKey && !event.altKey){
		reloadTab(cleanClass(name), type);
	}else if(event.ctrlKey && event.shiftKey && !event.altKey){
        switchTab(cleanClass(name), type);
    }else{
		switchTab(cleanClass(name), type);
        $('.splash-screen').removeClass('in').addClass('hidden');
	}
}
function reverseObject(object) {
    var newObject = {};
    var keys = [];
    for (var key in object) {
        keys.push(key);
    }
    for (var i = keys.length - 1; i >= 0; i--) {
      var value = object[keys[i]];
      newObject[keys[i]]= value;
    }
    return newObject;
}
function hasValue(test){
	if(Array.isArray(test) && test[0] !== ''){
		return true;
	}else{
		return false;
	}
	return false;
}
function arrayContains(needle, arrhaystack){
    return (arrhaystack.indexOf(needle) > -1);
}
/* END NORMAL FUNCTIONS */
/* BUILD FUNCTIONS */
/* END BUILD FUNCTIONS */
/* ORGANIZR API FUNCTIONS */
function selectOptions(options, active){
	var selectOptions = '';
	$.each(options, function(i,v) {
		activeTest = active.split(',');
		if(activeTest.length > 1){
			var selected = (arrayContains(v.value, activeTest)) ? 'selected' : '';
		}else{
			var selected = (active.toString() == v.value) ? 'selected' : '';
		}
		var disabled = (v.disabled) ? ' disabled' : '';
		selectOptions += '<option '+selected+disabled+' value="'+v.value+'">'+v.name+'</option>';
	});
	return selectOptions;
}
function accordionOptions(options, parentID){
	//console.log(options);
	var accordionOptions = '';
	$.each(options, function(i,v) {
		var id = v.id;
		var extraClass = (v.class) ? ' '+v.class : '';
		var header = (v.header) ? ' '+v.header : '';
		if(typeof v.body == 'object'){
			if(typeof v.body.length == 'undefined'){
				var body = buildFormItem(v.body);
			}else{
				var body = '';
				$.each(v.body, function(int,val) {
					body += buildFormItem(val);
				});
			}
		}else{
			var body = v.body;
		}
		accordionOptions += `
		<div class="panel">
			<div class="panel-heading" id="`+id+`-heading" role="tab">
				<a class="panel-title collapsed" data-toggle="collapse" href="#`+id+`-collapse" data-parent="#`+parentID+`" aria-expanded="false" aria-controls="`+id+`-collapse"><span lang="en">`+header+`</span></a>
			</div>
			<div class="panel-collapse collapse" id="`+id+`-collapse" aria-labelledby="`+id+`-heading" role="tabpanel" aria-expanded="false" style="height: 0px;">
				<div class="panel-body">`+body+`</div>
			</div>
		</div>
		`;
	});
	return accordionOptions;
}
function buildAccordion(array, open = false){
    var items = '';
    var mainId = createRandomString(10);
    $.each(array, function(i,v) {
        var collapse = (open && i == 0) ? 'collapse in' : 'collapse';
        var collapsed = (open && i == 0) ? '' : 'collapsed';
        var id = mainId + '-' + i;
        items += `
        <div class="panel">
            <div class="panel-heading bg-org" id="`+id+`-heading" role="tab"> <a class="panel-title `+collapsed+`" data-toggle="collapse" href="#`+id+`-collapse" data-parent="#`+mainId+`" aria-expanded="false" aria-controls="`+id+`-collapse"> `+v.title+` </a> </div>
            <div class="panel-collapse `+collapse+`" id="`+id+`-collapse" aria-labelledby="`+id+`-heading" role="tabpanel">
                <div class="panel-body"> `+v.body+` </div>
            </div>
        </div>
        `;
    });
    return '<div class="panel-group" id="'+mainId+'" aria-multiselectable="true" role="tablist">' + items + '</div>';
}
function buildFormItem(item){
    var placeholder = (item.placeholder) ? ' placeholder="'+item.placeholder+'"' : '';
	var id = (item.id) ? ' id="'+item.id+'"' : '';
	var type = (item.type) ? ' data-type="'+item.type+'"' : '';
	var value = (item.value) ? ' value="'+item.value+'"' : '';
	var textarea = (item.value) ? item.value : '';
	var name = (item.name) ? ' name="'+item.name+'"' : '';
	var extraClass = (item.class) ? ' '+item.class : '';
	var icon = (item.icon) ? ' '+item.icon : '';
	var text = (item.text) ? ' '+item.text : '';
	var attr = (item.attr) ? ' '+item.attr : '';
	var disabled = (item.disabled) ? ' disabled' : '';
	var href = (item.href) ? ' href="'+item.href+'"' : '';
	var pwd1 = createRandomString(6);
	var pwd2 = createRandomString(6);
	var pwd3 = createRandomString(6);
	var helpInfo = (item.help) ? '<div class="collapse" id="help-info-'+item.name+'"><blockquote>'+item.help+'</blockquote></div>' : '';
    var smallLabel = (item.smallLabel) ? '<label><span lang="en">'+item.smallLabel+'</span></label>'+helpInfo : ''+helpInfo;
	var pwgMgr = `
	<input name="disable-pwd-mgr-`+pwd1+`" type="password" id="disable-pwd-mgr-`+pwd1+`" style="display: none;" value="disable-pwd-mgr-`+pwd1+`" />
	<input name="disable-pwd-mgr-`+pwd2+`" type="password" id="disable-pwd-mgr-`+pwd2+`" style="display: none;" value="disable-pwd-mgr-`+pwd2+`" />
	<input name="disable-pwd-mgr-`+pwd3+`" type="password" id="disable-pwd-mgr-`+pwd3+`" style="display: none;" value="disable-pwd-mgr-`+pwd3+`" />
	`;
	//+tof(item.value,'c')+`
	switch (item.type) {
		case 'input':
		case 'text':
			return smallLabel+'<input data-changed="false" lang="en" type="text" class="form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+attr+' autocomplete="new-password" />';
			break;
        case 'number':
            return smallLabel+'<input data-changed="false" lang="en" type="number" class="form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+attr+' autocomplete="new-password" />';
            break;
		case 'textbox':
			return smallLabel+'<textarea data-changed="false" class="form-control'+extraClass+'"'+placeholder+id+name+disabled+type+attr+' autocomplete="new-password">'+textarea+'</textarea>';
			break;
		case 'password':
			return smallLabel+pwgMgr+'<input data-changed="false" lang="en" type="password" class="form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+attr+' autocomplete="new-password" />';
			break;
		case 'password-alt':
			return smallLabel+'<div class="input-group">'+pwgMgr+'<input data-changed="false" lang="en" type="password" class="password-alt form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+attr+' autocomplete="new-password" /><span class="input-group-btn"> <button class="btn btn-default showPassword" type="button"><i class="fa fa-eye passwordToggle"></i></button></span></div>';
			break;
		case 'hidden':
			return '<input data-changed="false" lang="en" type="hidden" class="form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+attr+' />';
			break;
		case 'select':
			return smallLabel+'<select class="form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+attr+'>'+selectOptions(item.options, item.value)+'</select>';
			break;
		case 'select2':
            var select2ID = (item.id) ? '#'+item.id : '.'+item.name;
            return smallLabel+'<select class="m-b-10 '+extraClass+'"'+placeholder+value+id+name+disabled+type+attr+' multiple="multiple" data-placeholder="Choose">'+selectOptions(item.options, item.value)+'</select><script>$("'+select2ID+'").select2();</script>';
			break;
		case 'switch':
		case 'checkbox':
			return smallLabel+'<input data-changed="false" type="checkbox" class="js-switch'+extraClass+'" data-size="small" data-color="#99d683" data-secondary-color="#f96262"'+name+value+tof(item.value,'c')+id+disabled+type+attr+' /><input data-changed="false" type="hidden"'+name+'value="false">';
			break;
		case 'button':
			return smallLabel+'<button class="btn btn-sm btn-success btn-rounded waves-effect waves-light b-none'+extraClass+'" '+href+attr+' type="button"><span class="btn-label"><i class="'+icon+'"></i></span><span lang="en">'+text+'</span></button>';
			break;
		case 'blank':
			return '';
			break;
		case 'accordion':
			return '<div class="panel-group'+extraClass+'"'+placeholder+value+id+name+disabled+type+attr+'  aria-multiselectable="true" role="tablist">'+accordionOptions(item.options, item.id)+'</div>';
			break;
		case 'html':
			return item.html;
			break;
		default:
			return false;
	}
}
function buildPluginsItem(array){
	var activePlugins = '';
	var inactivePlugins = '';
	$.each(array, function(i,v) {
		var settingsPage = (v.settings == true) ? `
		<!-- Plugin Settings Page -->
		<form id="`+v.idPrefix+`-settings-page" class="mfp-hide white-popup mfp-with-anim addFormTick col-md-10 col-md-offset-1" autocomplete="off">                			
            <div class="panel bg-org panel-info">
                <div class="panel-heading">
                    <span lang="en">`+v.name+` Settings</span>
                    <button type="button" class="btn bg-org btn-circle close-popup pull-right"><i class="fa fa-times"></i> </button>
                    <button id="`+v.idPrefix+`-settings-page-save" onclick="submitSettingsForm('`+v.idPrefix+`-settings-page')" class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right hidden animated loop-animation rubberBand m-r-20" type="button"><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save</span></button>
                </div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body bg-org">
                    <fieldset id="`+v.idPrefix+`-settings-items" style="border:0;" class=""></fieldset>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
		</form>
		` : '';
		var href = (v.settings == true) ? '#'+v.idPrefix+'-settings-page' : 'javascript:void(0);';
		if(v.enabled == true){
			var activeToggle = `<li><a class="btn default btn-outline disablePlugin" href="javascript:void(0);" data-plugin-name="`+v.name+`" data-config-prefix="`+v.configPrefix+`" data-config-name="`+v.configPrefix+`-enabled"><i class="ti-power-off fa-2x"></i></a></li>`;
			var settings = `<li><a class="btn default btn-outline popup-with-form" href="`+href+`" data-effect="mfp-3d-unfold"data-plugin-name="`+v.name+`" id="`+v.idPrefix+`-settings-button" data-config-prefix="`+v.configPrefix+`"><i class="ti-panel fa-2x"></i></a></li>`;
		}else{
			var activeToggle = `<li><a class="btn default btn-outline enablePlugin" href="javascript:void(0);" data-plugin-name="`+v.name+`" data-config-prefix="`+v.configPrefix+`" data-config-name="`+v.configPrefix+`-enabled"><i class="ti-plug fa-2x"></i></a></li>`;
			var settings = '';
		}
		var plugin = `
		<div class="col-lg-2 col-md-2 col-sm-4 col-xs-4">
			<div class="white-box m-0">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0"> <img class="lazyload" data-src="`+v.image+`">
						<div class="el-overlay">
							<ul class="el-info">
								`+settings+activeToggle+`
							</ul>
						</div>
					</div>
					<div class="el-card-content">
						<h3 class="box-title elip">`+v.name+`</h3>
						<small class="elip text-uppercase p-b-10">`+v.category+`</small>
					</div>
				</div>
			</div>
		</div>
		`;
		if(v.enabled == true){
			activePlugins += plugin+settingsPage;
		}else{
			inactivePlugins += plugin+settingsPage;
		}
	});
	activePlugins = (activePlugins.length !== 0) ? activePlugins : '<h2 class="text-center" lang="en">Nothing Active</h2>';
	inactivePlugins = (inactivePlugins.length !== 0) ? inactivePlugins : '<h2 class="text-center" lang="en">Everything Active</h2>';
	var panes = `
	<ul class="nav customtab2 nav-tabs" role="tablist">
		<li onclick="changeSettingsMenu('Settings::Plugins::Active')" role="presentation" class="active"><a href="#settings-plugins-active" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-file"></i></span><span class="hidden-xs" lang="en">Active</span></a>
		</li>
		<li onclick="changeSettingsMenu('Settings::Plugins::Inactive')" role="presentation" class=""><a href="#settings-plugins-inactive" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-zip"></i></span><span class="hidden-xs" lang="en">Inactive</span></a>
		</li>
		<li onclick="changeSettingsMenu('Settings::Plugins::Marketplace');loadMarketplace('plugins');" role="presentation" class=""><a href="#settings-plugins-marketplace" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-shopping-cart-full"></i></span><span class="hidden-xs" lang="en">Marketplace</span></a>
		</li>
	</ul>
	<!-- Tab panes -->
	<div class="tab-content">
		<div role="tabpanel" class="tab-pane fade in active" id="settings-plugins-active">
			<div class="panel bg-org panel-info">
				<div class="panel-heading">
					<span lang="en">Active Plugins</span>
				</div>
				<div class="panel-wrapper collapse in" aria-expanded="true">
					<div class="panel-body bg-org">
						<div class="row el-element-overlay m-b-40">`+activePlugins+`</div>
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
		</div>
		<div role="tabpanel" class="tab-pane fade" id="settings-plugins-inactive">
			<div class="panel bg-org panel-info">
				<div class="panel-heading">
					<span lang="en">Inactive Plugins</span>
				</div>
				<div class="panel-wrapper collapse in" aria-expanded="true">
					<div class="panel-body bg-org">
						<div class="row el-element-overlay m-b-40">`+inactivePlugins+`</div>
					</div>
				</div>
			</div>
		</div>
		<div role="tabpanel" class="tab-pane fade" id="settings-plugins-marketplace">
			<div class="panel bg-org panel-info">
				<div class="panel-heading">
					<span lang="en">Plugin Marketplace</span>
				</div>
				<div class="panel-wrapper collapse in" aria-expanded="true">
					<div class="table-responsive">
                        <table class="table table-hover manage-u-table">
                            <thead>
                                <tr>
                                    <th width="70" class="text-center" lang="en">PLUGIN</th>
                                    <th></th>
                                    <th lang="en">CATEGORY</th>
                                    <th lang="en">STATUS</th>
                                    <th lang="en" style="text-align:center">INFO</th>
                                    <th lang="en" style="text-align:center">INSTALL</th>
                                    <th lang="en" style="text-align:center">DELETE</th>
                                </tr>
                            </thead>
                            <tbody id="managePluginTable"></tbody>
                        </table>
                    </div>
				</div>
			</div>
		</div>
	</div>

	`;

	return panes;
}
function loadMarketplace(type){
    marketplaceJSON(type).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        switch (type) {
            case 'plugins':
                loadMarketplacePluginsItems(response);
                break;
            case 'themes':
                loadMarketplaceThemesItems(response);
                break;
            default:
        }
    }).fail(function(xhr) {
        console.error("Organizr Function: Github Connection Failed");
    });
}
function loadMarketplacePluginsItems(plugins){
    var pluginList = '';
    $.each(plugins, function(i,v) {
        if(v.icon == null || v.icon == ''){ v.icon = 'test.png'; }
        v.status = pluginStatus(i,v.version);
        var installButton = (v.status == 'Update Available') ? 'fa fa-download' : 'fa fa-plus';
        var removeButton = (v.status == 'Not Installed') ? 'disabled' : '';
        v.name = i;
        pluginList += `
            <tr class="pluginManagement" data-name="`+i+`" data-version="`+v.version+`">
                <td class="text-center el-element-overlay">
                    <div class="el-card-item p-0">
                        <div class="el-card-avatar el-overlay-1 m-0">
                            <img alt="user-img" src="`+v.icon+`" width="45">
                        </div>
                    </div>
                </td>
                <td>`+i+`
                    <br><span class="text-muted">`+v.version+`</span>
                    <br><span class="text-muted">`+v.author+`</span>
                </td>
                <td>`+v.category+`</td>
                <td>`+v.status+`</td>
                <td style="text-align:center"><button type="button" onclick='aboutPlugin(`+JSON.stringify(v)+`);' class="btn btn-success btn-outline btn-circle btn-lg popup-with-form" href="#about-plugin-form" data-effect="mfp-3d-unfold"><i class="fa fa-info"></i></button></td>
                <td style="text-align:center"><button type="button" onclick='installPlugin(`+JSON.stringify(v)+`);' class="btn btn-info btn-outline btn-circle btn-lg"><i class="`+installButton+`"></i></button></td>
                <td style="text-align:center"><button type="button" onclick='removePlugin(`+JSON.stringify(v)+`);' class="btn btn-danger btn-outline btn-circle btn-lg" `+removeButton+`><i class="fa fa-trash"></i></button></td>
            </tr>
        `;

    });
    $('#managePluginTable').html(pluginList);
}
function loadMarketplaceThemesItems(themes){
    var themeList = '';
    $.each(themes, function(i,v) {
        if(v.icon == null || v.icon == ''){ v.icon = 'test.png'; }
        v.status = themeStatus(i,v.version);
        var installButton = (v.status == 'Update Available') ? 'fa fa-download' : 'fa fa-plus';
        var removeButton = (v.status == 'Not Installed') ? 'disabled' : '';
        v.name = i;
        themeList += `
            <tr class="themeManagement" data-name="`+i+`" data-version="`+v.version+`">
                <td class="text-center el-element-overlay">
                    <div class="el-card-item p-0">
                        <div class="el-card-avatar el-overlay-1 m-0">
                            <img alt="user-img" src="`+v.icon+`" width="45">
                        </div>
                    </div>
                </td>
                <td>`+i+`
                    <br><span class="text-muted">`+v.version+`</span>
                    <br><span class="text-muted">`+v.author+`</span>
                </td>
                <td>`+v.category+`</td>
                <td>`+v.status+`</td>
                <td style="text-align:center"><button type="button" onclick='aboutTheme(`+JSON.stringify(v)+`);' class="btn btn-success btn-outline btn-circle btn-lg popup-with-form" href="#about-theme-form" data-effect="mfp-3d-unfold"><i class="fa fa-info"></i></button></td>
                <td style="text-align:center"><button type="button" onclick='installTheme(`+JSON.stringify(v)+`);themeAnalytics("`+ v.name +`");' class="btn btn-info btn-outline btn-circle btn-lg"><i class="`+installButton+`"></i></button></td>
                <td style="text-align:center"><button type="button" onclick='removeTheme(`+JSON.stringify(v)+`);' class="btn btn-danger btn-outline btn-circle btn-lg" `+removeButton+`><i class="fa fa-trash"></i></button></td>
            </tr>
        `;

    });
    $('#manageThemeTable').html(themeList);
}
function aboutPluginImages(images){
    var imageList = '';
    if(Object.keys(images).length !== 0){
        var imageCount = 0;
        $.each(images, function(i,v) {
            imageCount++;
            var active = (imageCount == 1) ? 'active' : '';
            imageList += `
            <div class="`+active+` item">
                <div class="overlaybg"><img src="`+v+`" /></div>
                <div class="news-content"><span class="label label-info label-rounded">`+i+`</span></div>
            </div>
            `;
        });
    }else{
        imageList += `
            <div class="active item">
                <div class="overlaybg"><img src="https://via.placeholder.com/350x150" /></div>
            </div>
        `;
    }
    return imageList;
}
function aboutPluginFiles(fileList){
    var files = [];
    $.each(fileList, function(i,v) {
        var splitFiles = v.split('|');
        var formattedSplit = [];
        $.each(splitFiles, function(i,v) {
            var arrayFilePush = {
                "text": v
            };
            formattedSplit.push(arrayFilePush);
        });
        var arrayPush = {
            "text": i,
            "nodes": formattedSplit,
        };
        files.push(arrayPush);
    });
    return files;
}
function pluginFileList(fileList,folder,type){
    var files = [];
    $.each(fileList, function(i,v) {
        var splitFiles = v.split('|');
        var formattedSplit = [];
        var prePath = (i.length !== 1) ? i+'/' : i;
        $.each(splitFiles, function(i,v) {
            var arrayPush = {
                "fileName": v,
                "path": prePath,
                "githubPath": 'https://raw.githubusercontent.com/causefx/Organizr/v2-'+type+'/'+folder+prePath+v,
            };
            files.push(arrayPush);
        });
    });
    return files;
}
function aboutTheme(theme){
    var files = aboutPluginFiles(theme.files);
    var imageList = aboutPluginImages(theme.images);
    var homepageLink = (theme.website !== '' || theme.website !== null) ? 'onclick="window.open(\''+theme.website+'\',\'_blank\');"' : ' ';

    var infoBox = `
    <div class="row">
        <div class="col-lg-6 col-sm-12 col-xs-12">
            <div class="row">
                <div class="col-lg-12 col-sm-12 col-xs-12">
                    <div class="white-box p-10" id="aboutThemeScroll">
                        `+theme.description+`
                    </div>
                </div>
                <div class="clearfix">&nbsp;</div>
                <div class="col-lg-4 col-sm-4 col-xs-12">
                    <div class="white-box mouse">
                        <ul class="list-inline two-part text-center m-b-0">
                            <li><i class="icon-envelope-open text-info"></i></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-4 col-xs-12">
                    <div class="white-box mouse" `+homepageLink+`>
                        <ul class="list-inline two-part text-center m-b-0">
                            <li><i class="icon-home text-danger"></i></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-4 col-xs-12">
                    <div class="white-box mouse" onclick="$('.themeFileList').toggleClass('hidden');">
                        <ul class="list-inline two-part text-center m-b-0">
                            <li><i class="icon-folder text-purple"></i></li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-12 col-xs-12 themeFileList hidden">
                    <div id="treeviewTheme" class=""></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-sm-12 col-xs-12">
            <div class="news-slide m-b-15">
                <div class="vcarousel slide">
                    <!-- Carousel items -->
                    <div class="carousel-inner">
                        `+imageList+`
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    $('#about-theme-title').html(theme.name+'&nbsp;<small>'+theme.version+'</small>');
    $('#about-theme-body').html(infoBox);
    $('.vcarousel').carousel({
        interval: 3000
    });
    $('#treeviewTheme').treeview({
        levels: 1,
        expandIcon: 'ti-angle-right',
        onhoverColor: "rgba(0, 0, 0, 0.05)",
        selectedBackColor: "#03a9f3",
        collapseIcon: 'ti-angle-down',
        data: JSON.stringify(files)
    });
    $('#aboutThemeScroll').slimScroll({
        height: '225px'
    });
}
function aboutPlugin(plugin){
    var files = aboutPluginFiles(plugin.files);
    var imageList = aboutPluginImages(plugin.images);
    var homepageLink = (plugin.website !== '' || plugin.website !== null) ? 'onclick="window.open(\''+plugin.website+'\',\'_blank\');"' : ' ';

    var infoBox = `
    <div class="row">
        <div class="col-lg-6 col-sm-12 col-xs-12">
            <div class="row">
                <div class="col-lg-12 col-sm-12 col-xs-12">
                    <div class="white-box p-10" id="aboutPluginScroll">
                        `+plugin.description+`
                    </div>
                </div>
                <div class="clearfix">&nbsp;</div>
                <div class="col-lg-4 col-sm-4 col-xs-12">
                    <div class="white-box mouse">
                        <ul class="list-inline two-part text-center m-b-0">
                            <li><i class="icon-envelope-open text-info"></i></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-4 col-xs-12">
                    <div class="white-box mouse" `+homepageLink+`>
                        <ul class="list-inline two-part text-center m-b-0">
                            <li><i class="icon-home text-danger"></i></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-4 col-xs-12">
                    <div class="white-box mouse" onclick="$('.pluginFileList').toggleClass('hidden');">
                        <ul class="list-inline two-part text-center m-b-0">
                            <li><i class="icon-folder text-purple"></i></li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-12 col-xs-12 pluginFileList hidden">
                    <div id="treeview5" class=""></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-sm-12 col-xs-12">
            <div class="news-slide m-b-15">
                <div class="vcarousel slide">
                    <!-- Carousel items -->
                    <div class="carousel-inner">
                        `+imageList+`
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    $('#about-plugin-title').html(plugin.name+'&nbsp;<small>'+plugin.version+'</small>');
    $('#about-plugin-body').html(infoBox);
    $('.vcarousel').carousel({
        interval: 3000
    });
    $('#treeview5').treeview({
        levels: 1,
        expandIcon: 'ti-angle-right',
        onhoverColor: "rgba(0, 0, 0, 0.05)",
        selectedBackColor: "#03a9f3",
        collapseIcon: 'ti-angle-down',
        data: JSON.stringify(files)
    });
    $('#aboutPluginScroll').slimScroll({
        height: '225px'
    });
}
function removePlugin(plugin=null){
    if(plugin == null){
        return false;
    }
    message('Removing Plugin',plugin.name,activeInfo.settings.notifications.position,"#FFF","success","5000");
    plugin.downloadList = pluginFileList(plugin.files,plugin.github_folder,'plugins');
    organizrAPI('POST','api/?v1/plugin/remove',{plugin:plugin}).success(function(data) {
        try {
            var html = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        if(html.data.substr(0, 7) == 'Success'){
            var newPlugins = html.data.split('!@!');
            activeInfo.settings.misc.installedPlugins = newPlugins[1];
            loadMarketplace('plugins');
            message(plugin.name+' Removed','Please Click Plugins Above to refresh',activeInfo.settings.notifications.position,"#FFF","success","5000");
        }else{
            message('Remove Failed',html.data,activeInfo.settings.notifications.position,"#FFF","warning","10000");
        }
    }).fail(function(xhr) {
        message('Remove Failed',plugin.name,activeInfo.settings.notifications.position,"#FFF","warning","5000");
        console.error("Organizr Function: Connection Failed");
    });
}
function removeTheme(theme=null){
    if(theme == null){
        return false;
    }
    message('Removing Plugin',theme.name,activeInfo.settings.notifications.position,"#FFF","success","5000");
    theme.downloadList = pluginFileList(theme.files,theme.github_folder,'plugins');
    organizrAPI('POST','api/?v1/theme/remove',{theme:theme}).success(function(data) {
        try {
            var html = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        if(html.data.substr(0, 7) == 'Success'){
            var newThemes = html.data.split('!@!');
            activeInfo.settings.misc.installedThemes = newThemes[1];
            loadMarketplace('themes');
            message(theme.name+' Removed','Please Click Customize Above to refresh',activeInfo.settings.notifications.position,"#FFF","success","5000");
        }else{
            message('Remove Failed',html.data,activeInfo.settings.notifications.position,"#FFF","warning","10000");
        }
    }).fail(function(xhr) {
        message('Remove Failed',theme.name,activeInfo.settings.notifications.position,"#FFF","warning","5000");
        console.error("Organizr Function: Connection Failed");
    });
}
function installPlugin(plugin=null){
    if(plugin == null){
        return false;
    }
    message('Installing Plugin',plugin.name,activeInfo.settings.notifications.position,"#FFF","success","5000");
    plugin.downloadList = pluginFileList(plugin.files,plugin.github_folder,'plugins');
    organizrAPI('POST','api/?v1/plugin/install',{plugin:plugin}).success(function(data) {
        try {
            var html = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        if(html.data.substr(0, 7) == 'Success'){
            var newPlugins = html.data.split('!@!');
            activeInfo.settings.misc.installedPlugins = newPlugins[1];
            loadMarketplace('plugins');
            message(plugin.name+' Installed','Please Click Plugins Above to refresh',activeInfo.settings.notifications.position,"#FFF","success","5000");
        }else{
            message('Install Failed',html.data,activeInfo.settings.notifications.position,"#FFF","warning","10000");
        }
    }).fail(function(xhr) {
        message('Install Failed',plugin.name,activeInfo.settings.notifications.position,"#FFF","warning","5000");
        console.error("Organizr Function: Connection Failed");
    });
}
function installTheme(theme=null){
    if(theme == null){
        return false;
    }
    message('Installing Theme',theme.name,activeInfo.settings.notifications.position,"#FFF","success","5000");
    theme.downloadList = pluginFileList(theme.files,theme.github_folder,'themes');
    organizrAPI('POST','api/?v1/theme/install',{theme:theme}).success(function(data) {
        try {
            var html = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        if(html.data.substr(0, 7) == 'Success'){
            var newThemes = html.data.split('!@!');
            activeInfo.settings.misc.installedThemes = newThemes[1];
            loadMarketplace('themes');
            message(theme.name+' Installed','Please Click Customize Above to refresh',activeInfo.settings.notifications.position,"#FFF","success","5000");
        }else{
            message('Install Failed',html.data,activeInfo.settings.notifications.position,"#FFF","warning","10000");
        }
    }).fail(function(xhr) {
        message('Install Failed',theme.name,activeInfo.settings.notifications.position,"#FFF","warning","5000");
        console.error("Organizr Function: Connection Failed");
    });
}
function pluginStatus(name=null,version=null){
    var installedPlugins = [];
    var installedPluginsList = [];
    if(activeInfo.settings.misc.installedPlugins !== ''){
        installedPlugins = activeInfo.settings.misc.installedPlugins.split("|");
        $.each(installedPlugins, function(i,v) {
            var plugin = v.split(":");
            installedPluginsList[plugin[0]] = plugin[1];
        });
        if(typeof installedPluginsList[name] !== 'undefined'){
            if(version !== installedPluginsList[name]){
                return 'Update Available';
            }else{
                return 'Up to date';
            }
        }else{
            return 'Not Installed';
        }
    }else{
        return 'Not Installed';
    }
}
function themeStatus(name=null,version=null){
    var installedThemes = [];
    var installedThemesList = [];
    if(activeInfo.settings.misc.installedThemes !== ''){
        installedThemes = activeInfo.settings.misc.installedThemes.split("|");
        $.each(installedThemes, function(i,v) {
            var theme = v.split(":");
            installedThemesList[theme[0]] = theme[1];
        });
        if(typeof installedThemesList[name] !== 'undefined'){
            if(version !== installedThemesList[name]){
                return 'Update Available';
            }else{
                return 'Up to date';
            }
        }else{
            return 'Not Installed';
        }
    }else{
        return 'Not Installed';
    }
}
function buildHomepageItem(array){
	var listing = '';
	if (Array.isArray(array)) {
		$.each(array, function(i,v) {
			if(v.enabled){
				listing += `
				<div class="col-lg-2 col-md-2 col-sm-4 col-xs-4">
					<div class="white-box bg-org m-0">
						<div class="el-card-item p-0">
							<div class="el-card-avatar el-overlay-1">
								<a class="popup-with-form" href="#homepage-`+v.name+`-form" data-effect="mfp-3d-unfold"><img class="lazyload tabImages" data-src="`+v.image+`"></a>
							</div>
							<div class="el-card-content">
								<h3 class="box-title">`+v.name+`</h3>
								<small class="elip text-uppercase">`+v.category+`</small><br>
							</div>
						</div>
					</div>
				</div>
				<form id="homepage-`+v.name+`-form" class="mfp-hide white-popup mfp-with-anim homepageForm addFormTick">
				    <fieldset style="border:0;" class="col-md-10 col-md-offset-1">
                        <div class="panel bg-org panel-info">
                            <div class="panel-heading">
                                <span lang="en">`+v.name+`</span>
                                <button type="button" class="btn bg-org btn-circle close-popup pull-right"><i class="fa fa-times"></i> </button>
                                <button id="homepage-`+v.name+`-form-save" onclick="submitSettingsForm('homepage-`+v.name+`-form')" class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right hidden animated loop-animation rubberBand m-r-20" type="button"><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save</span></button>
                            </div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body bg-org">
                                    `+buildFormGroup(v.settings)+`
                                </div>
                            </div>
                        </div>
					</fieldset>
				    <div class="clearfix"></div>
				</form>
				`;
			}
		});
	}
	return listing;
}
function buildPlugins(){
	organizrAPI('GET','api/?v1/settings/plugins/list').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		$('#main-plugin-area').html(buildPluginsItem(response.data));
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function buildHomepage(){
	organizrAPI('GET','api/?v1/settings/homepage/list').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		$('#settings-homepage-list').html(buildHomepageItem(response.data));
		customHTMLoneEditor = ace.edit("customHTMLoneEditor");
		var HTMLMode = ace.require("ace/mode/html").Mode;
		customHTMLoneEditor.session.setMode(new HTMLMode());
		customHTMLoneEditor.setTheme("ace/theme/idle_fingers");
		customHTMLoneEditor.setShowPrintMargin(false);
		customHTMLoneEditor.session.on('change', function(delta) {
            $('.customHTMLoneTextarea').val(customHTMLoneEditor.getValue());
            $('#homepage-CustomHTML-1-form-save').removeClass('hidden');
		});
		customHTMLtwoEditor = ace.edit("customHTMLtwoEditor");
		customHTMLtwoEditor.session.setMode(new HTMLMode());
		customHTMLtwoEditor.setTheme("ace/theme/idle_fingers");
		customHTMLtwoEditor.setShowPrintMargin(false);
		customHTMLtwoEditor.session.on('change', function(delta) {
            $('.customHTMLtwoTextarea').val(customHTMLtwoEditor.getValue());
            $('#homepage-CustomHTML-2-form-save').removeClass('hidden');
		});
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function buildFormGroup(array){
    var mainCount = 0;
	var group = '<div class="tab-content">';
	var uList = '<ul class="nav customtab nav-tabs nav-low-margin" role="tablist">';
	$.each(array, function(i,v) {
        mainCount++;
		var count = 0;
		var total = v.length;
		var active = (mainCount == 1) ? 'active' : '';
		var customID = createRandomString(10);
		if(i == 'custom'){
			group += v;
		}else{
		    uList += `<li role="presentation" class="`+active+`"><a href="#`+customID+cleanClass(i)+`" aria-controls="`+i+`" role="tab" data-toggle="tab" aria-expanded="false"><span> `+i+`</span></a></li>`;
			group += `
				<!-- FORM GROUP -->
				<div role="tabpanel" class="tab-pane fade in `+active+`" id="`+customID+cleanClass(i)+`">
			`;
			$.each(v, function(i,v) {
				var override = '6';
				if(typeof v.override !== 'undefined'){
					override = v.override;
				}
				count++;
                if (count % 2 !== 0) {
                    group += '<div class="row start">';
                }
                var helpID = '#help-info-'+v.name;
                var helpTip = (v.help) ? '<sup><a class="help-tip" data-toggle="collapse" href="'+helpID+'" aria-expanded="true"><i class="m-l-5 fa fa-question-circle text-info" title="Help" data-toggle="tooltip"></i></a></sup>' : '';
                group += `
					<!-- INPUT BOX -->
					<div class="col-md-`+override+` p-b-10">
						<div class="form-group">
							<label class="control-label col-md-12"><span lang="en">`+v.label+`</span>`+helpTip+`</label>
							<div class="col-md-12">
								`+buildFormItem(v)+`
							</div>
						</div>
					</div>
					<!--/ INPUT BOX -->
				`;
                if (count % 2 == 0 || count == total) {
                    group += '</div><!--end-->';
                }
            });
			group += '</div>';
		}
	});
	return uList+'</ul>'+group;
}
function buildImageManagerViewItem(array){
	var imageListing = '';
	if (Array.isArray(array)) {
		$.each(array, function(i,v) {
			var filepath = v.split("/");
			var name = filepath[3].split(".");
			var clipboardText = v.replace(/ /g,"%20");
			imageListing += `
			<div class="col-lg-1 col-md-1 col-sm-2 col-xs-4">
				<div class="white-box bg-org m-0">
					<div class="el-card-item p-0">
						<div class="el-card-avatar el-overlay-1"> <img class="lazyload tabImages" data-src="`+v+`" width="22" height="22">
							<div class="el-overlay">
								<ul class="el-info">
									<li><a class="btn default btn-outline clipboard p-a-5" data-clipboard-text="`+clipboardText+`" href="javascript:void(0);"><i class="ti-clipboard"></i></a></li>
									<li><a class="btn default btn-outline deleteImage p-a-5" href="javascript:void(0);" data-image-path="`+v+`" data-image-name="`+name[0]+`"><i class="icon-trash"></i></a></li>
								</ul>
							</div>
						</div>
						<div class="el-card-content">
							<small class="elip text-uppercase">`+name[0]+`</small><br>
						</div>
					</div>
				</div>
			</div>
			`;
		});
	}
	return imageListing;
}
function buildImageManagerView(){
	organizrAPI('GET','api/?v1/image/list').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		$('#settings-image-manager-list').html(buildImageManagerViewItem(response.data));
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function buildCustomizeAppearance(){
	organizrAPI('GET','api/?v1/customize/appearance').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		$('#customize-appearance-form').html(buildFormGroup(response.data));
		cssEditor = ace.edit("customCSSEditor");
		var CssMode = ace.require("ace/mode/css").Mode;
		cssEditor.session.setMode(new CssMode());
		cssEditor.setTheme("ace/theme/idle_fingers");
		cssEditor.setShowPrintMargin(false);
		cssEditor.session.on('change', function(delta) {
            $('.cssTextarea').val(cssEditor.getValue());
            $('#customize-appearance-form-save').removeClass('hidden');
		});
        cssThemeEditor = ace.edit("customThemeCSSEditor");
        var CssThemeMode = ace.require("ace/mode/css").Mode;
        cssThemeEditor.session.setMode(new CssThemeMode());
        cssThemeEditor.setTheme("ace/theme/idle_fingers");
        cssThemeEditor.setShowPrintMargin(false);
        cssThemeEditor.session.on('change', function(delta) {
            $('.cssThemeTextarea').val(cssThemeEditor.getValue());
            $('#customize-appearance-form-save').removeClass('hidden');
        });
        javaEditor = ace.edit("customJavaEditor");
        var JavaMode = ace.require("ace/mode/javascript").Mode;
        javaEditor.session.setMode(new JavaMode());
        javaEditor.setTheme("ace/theme/idle_fingers");
        javaEditor.setShowPrintMargin(false);
        javaEditor.session.on('change', function(delta) {
            $('.javaTextarea').val(javaEditor.getValue());
            $('#customize-appearance-form-save').removeClass('hidden');
        });
        javaThemeEditor = ace.edit("customThemeJavaEditor");
        var JavaThemeMode = ace.require("ace/mode/javascript").Mode;
        javaThemeEditor.session.setMode(new JavaThemeMode());
        javaThemeEditor.setTheme("ace/theme/idle_fingers");
        javaThemeEditor.setShowPrintMargin(false);
        javaThemeEditor.session.on('change', function(delta) {
            $('.javaThemeTextarea').val(javaThemeEditor.getValue());
            $('#customize-appearance-form-save').removeClass('hidden');
        });
		$("input.pick-a-color").ColorPickerSliders({
			placement: 'bottom',
			color: '#987654',
			hsvpanel: true,
			previewformat: 'hex',
		});
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function buildSSO(){
	organizrAPI('GET','api/?v1/sso').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		$('#sso-form').html(buildFormGroup(response.data));
    }).fail(function (xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function buildSettingsMain(){
	organizrAPI('GET','api/?v1/settings/main').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		$('#settings-main-form').html(buildFormGroup(response.data));
		changeAuth();
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function buildUserManagement(){
	organizrAPI('GET','api/?v1/user/list').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		$('#manageUserTable').html(buildUserManagementItem(response.data));
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function buildGroupManagement(){
	organizrAPI('GET','api/?v1/user/list').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		$('#manageGroupTable').html(buildGroupManagementItem(response.data));
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function buildTabEditor(){
	organizrAPI('GET','api/?v1/tab/list').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		$('#tabEditorTable').html(buildTabEditorItem(response.data));
        loadSettingsPage('api/?v1/settings/tab/editor/homepage','#settings-tab-editor-homepage','Homepage Items');
        setTimeout(function(){ sortHomepageItemHrefs() }, 1000);
        setTimeout(function(){ checkTabHomepageItems(); }, 1500);


	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function checkTabHomepageItems(){
    var tabList = $('.checkTabHomepageItem');
    $.each(tabList, function(i,v) {
        var el = $(v);
        var id = el.attr('id');
        var name = el.attr('data-name');
        var url = el.attr('data-url');
        var urlLocal = el.attr('data-url-local');
        checkTabHomepageItem(id, name, url, urlLocal);
    });
}
function sortHomepageItemHrefs(){
    var hrefList = $('.popup-with-form');
    window.hrefList = new Array();
    $.each(hrefList, function(i,v) {
        var el = $(v);
        var href = el.attr('href');
        if(href.includes('#homepage-')){
            var splitHref = href.split("-");
            window.hrefList[splitHref[1]] = i;
        }
    });
}
function checkTabHomepageItem(id, name, url, urlLocal){
    name = name.toLowerCase();
    url = url.toLowerCase();
    urlLocal = urlLocal.toLowerCase();
    if(name.includes('sonarr') || url.includes('sonarr') || urlLocal.includes('sonarr')){
        addEditHomepageItem(id,'Sonarr');
    }else if(name.includes('radarr') || url.includes('radarr') || urlLocal.includes('radarr')){
        addEditHomepageItem(id,'Radarr');
    }else if(name.includes('lidarr') || url.includes('lidarr') || urlLocal.includes('lidarr')){
        addEditHomepageItem(id,'Lidarr');
    }else if(name.includes('couchpotato') || url.includes('couchpotato') || urlLocal.includes('couchpotato')){
        addEditHomepageItem(id,'CouchPotato');
    }else if(name.includes('sick') || url.includes('sick') || urlLocal.includes('sick')){
        addEditHomepageItem(id,'SickRage');
    }else if((name.includes('plex') || url.includes('plex') || urlLocal.includes('plex')) && !name.includes('plexpy')){
        addEditHomepageItem(id,'Plex');
    }else if(name.includes('emby') || url.includes('emby') || urlLocal.includes('emby')){
        addEditHomepageItem(id,'Emby');
    }else if(name.includes('jdownloader') || url.includes('jdownloader') || urlLocal.includes('jdownloader')){
        addEditHomepageItem(id,'jDownloader');
    }else if(name.includes('sab') || url.includes('sab') || urlLocal.includes('sab')){
        addEditHomepageItem(id,'SabNZBD');
    }else if(name.includes('nzbget') || url.includes('nzbget') || urlLocal.includes('nzbget')){
        addEditHomepageItem(id,'NZBGet');
    }else if(name.includes('transmission') || url.includes('transmission') || urlLocal.includes('transmission')){
        addEditHomepageItem(id,'Transmission');
    }else if(name.includes('qbit') || url.includes('qbit') || urlLocal.includes('qbit')){
        addEditHomepageItem(id,'qBittorrent');
    }else if(name.includes('rtorrent') || url.includes('rtorrent') || urlLocal.includes('rtorrent')){
        addEditHomepageItem(id,'rTorrent');
    }else if(name.includes('deluge') || url.includes('deluge') || urlLocal.includes('deluge')){
        addEditHomepageItem(id,'Deluge');
    }else if(name.includes('ombi') || url.includes('ombi') || urlLocal.includes('ombi')){
        addEditHomepageItem(id,'Ombi');
    }else if(name.includes('healthcheck') || url.includes('healthcheck') || urlLocal.includes('healthcheck')){
        addEditHomepageItem(id,'HealthChecks');
    }
}
function addEditHomepageItem(id, type){
    var html = '';
    var process = false;
    if(type in window.hrefList){
        html = '<i class="ti-home"></i>';
        process = true;
    }
    if(html !== ''){
        $('#'+id).html(html);
    }
    if(process){
        $('#'+id).attr('onclick', "$('.popup-with-form').magnificPopup('open',"+window.hrefList[type]+")");
    }
    return false;
}
function buildCategoryEditor(){
	organizrAPI('GET','api/?v1/tab/list').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		$('#categoryEditorTable').html(buildCategoryEditorItem(response.data));
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function settingsAPI(post, callbacks=null){
	organizrAPI('POST',post.api,post).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		//console.log(response);
		message(post.messageTitle,post.messageBody,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		console.error(post.error);
	});
}
/* END ORGANIZR API FUNCTIONS */
function buildLanguage(replace=false,newLang=null){
	var languageItems = '';
	var currentLanguage = (getCookie('organizrLanguage')) ? getCookie('organizrLanguage') : window.lang.currentLang;
	$.each(languageList, function(i,v) {
		var active = (v.code == currentLanguage) ? '' : '';
		languageItems += `
			<a onclick="window.lang.change('`+v.code+`');buildLanguage(true,'`+v.language+`')" href="javascript:void(0);" class="`+active+`">
				<div class="mail-content"><h5 class="m-0">`+v.language+`</h5><span class="mail-desc" lang="en">`+active+`</span></div>
			</a>
		`;
	});
	var lang = `
		<li class="dropdown" id="languageDropdown">
			<a class="dropdown-toggle waves-effect waves-light" data-toggle="dropdown" href="#" aria-expanded="false"> <i class="fa fa-language"></i><span></span></a>
			<ul class="dropdown-menu mailbox animated bounceInDown">
				<li>
					<div class="drop-title" lang="en">Choose Language</div>
				</li>
				<li>
					<div class="message-center" data-simplebar>`+languageItems+`</div>
				</li>
			</ul>
			<!-- /.dropdown-messages -->
		</li>
	`;
	if(replace == true){
	    setLangCookie(newLang);
		$('#languageDropdown').replaceWith(lang);
		message("",window.lang.translate('Changed Language To')+": "+newLang,activeInfo.settings.notifications.position,"#FFF","success","3500");
	}else if(replace == 'wizard'){
		$(lang).appendTo('.navbar-right');
	}else{
		return lang;
	}
}
function removeFile(path,name){
	if(path !== '' && name !== ''){
		var post = {
			path:path,
			name:name
		};
		ajaxloader(".content-wrap","in");
		organizrAPI('POST','api/?v1/remove/file',post).success(function(data) {
            try {
                var response = JSON.parse(data);
            }catch(e) {
                console.log(e + ' error: ' + data);
                orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                return false;
            }
			if(response.data == true){
				messageSingle('',window.lang.translate('Removed File')+' - '+name,activeInfo.settings.notifications.position,'#FFF','success','5000');
			}else{
				messageSingle('','File Removal Error',activeInfo.settings.notifications.position,'#FFF','error','5000');
			}
		}).fail(function(xhr) {
			console.error("Organizr Function: API Connection Failed");
		});
		ajaxloader();
	}
}
function updateUserInformation(){
	var passwordMatch = true;
	var username = $('#accountUsername').val();
	var email = $('#accountEmail').val();
	var password1 = $('#accountPassword1').val();
	var password2 = $('#accountPassword2').val();
	if(password1 != password2){
		passwordMatch = false;
		messageSingle('','Passwords do not match',activeInfo.settings.notifications.position,'#FFF','error','5000');
	}
	if(username !== '' && email !== '' && passwordMatch == true){
		var post = {
			username:username,
			email:email,
			password:password1
		};
		ajaxloader(".content-wrap","in");
		organizrAPI('POST','api/?v1/manage/user',post).success(function(data) {
            try {
                var response = JSON.parse(data);
            }catch(e) {
                console.log(e + ' error: ' + data);
                orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                return false;
            }
			if(response.data == true){
				$.magnificPopup.close();
				messageSingle('',window.lang.translate('User Info Updated'),activeInfo.settings.notifications.position,'#FFF','success','5000');
			}else{
				messageSingle('',response.data,activeInfo.settings.notifications.position,'#FFF','error','5000');
			}
		}).fail(function(xhr) {
			console.error("Organizr Function: API Connection Failed");
		});
		ajaxloader();
	}
}
function twoFA(action, type, secret = null){
    switch(action){
        case 'activate':
            organizrAPI('POST','api/?v1/2fa/create',{type:type}).success(function(data) {
                try {
                    var html = JSON.parse(data);
                }catch(e) {
                    console.log(e + ' error: ' + data);
                    orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                    return false;
                }
                $('.twofa-modal-title').html(html.data.type);
                $('.twofa-modal-image').html('<img class="center" src="'+html.data.url+'">');
                $('.twofa-modal-secret').html(html.data.secret);
                $('#twofa-modal').modal('show');
            }).fail(function(xhr) {
                console.error("Organizr Function: Connection Failed");
            });
            break;
        case 'deactivate':
            organizrAPI('GET','api/?v1/2fa/remove').success(function(data) {
                try {
                    var html = JSON.parse(data);
                }catch(e) {
                    console.log(e + ' error: ' + data);
                    orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                    return false;
                }
                $('.2fa-list').replaceWith(buildTwoFA('internal'));
            }).fail(function(xhr) {
                console.error("Organizr Function: Connection Failed");
            });
            break;
        case 'verify':
            var secret = $('.twofa-modal-secret').text();
            var code = $('#twofa-verify').val();
            if(type !== '' && secret !== '' && code !== ''){
                organizrAPI('POST','api/?v1/2fa/verify',{type:type, secret:secret, code:code}).success(function(data) {
                    try {
                        var html = JSON.parse(data);
                    }catch(e) {
                        console.log(e + ' error: ' + data);
                        orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                        return false;
                    }
                    if(html.data == true){
                        message('2FA Success','Input Code Validated! Saving...',activeInfo.settings.notifications.position,"#FFF","success","5000");
                        $('#twofa-modal').modal('hide');
                        twoFA('save', type, secret);
                    }else{
                        message('2FA Failed','Code Incorrect',activeInfo.settings.notifications.position,"#FFF","warning","5000");
                    }
                }).fail(function(xhr) {
                    console.error("Organizr Function: Connection Failed");
                });
            }else{
                message('2FA Failed','Input Code',activeInfo.settings.notifications.position,"#FFF","warning","5000");
            }
            break;
        case 'save':
            organizrAPI('POST','api/?v1/2fa/save',{type:type, secret:secret}).success(function(data) {
                try {
                    var html = JSON.parse(data);
                }catch(e) {
                    console.log(e + ' error: ' + data);
                    orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                    return false;
                }
                //console.log(html);
                if(html.data == true){
                    message('2FA Success','2FA Saved',activeInfo.settings.notifications.position,"#FFF","success","5000");
                    $('.2fa-list').replaceWith(buildTwoFA(type));
                }else{
                    message('2FA Failed','2FA Error!',activeInfo.settings.notifications.position,"#FFF","warning","5000");
                }
            }).fail(function(xhr) {
                console.error("Organizr Function: Connection Failed");
            });
            break;
    }
}
function buildTwoFA(current){
    switch(current){
        case 'internal':
            var option = `
                <div class="col-lg-3 col-sm-6 row-in-br">
                    <ul class="col-in">
                        <li>
                            <span class="circle circle-md bg-info"><i class="mdi mdi-webpack mdi-24px"></i></span>
                        </li>
                        <li class="col-middle">
                            <h5>Organizr Authenticator</h5>
                            <h5><span lang="en">Current</span></h5>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-3 col-sm-6 row-in-br">
                    <ul class="col-in">
                        <li>
                            <span class="circle circle-md bg-info"><i class="fa fa-google"></i></span>
                        </li>
                        <li class="col-middle">
                            <h5>Google Authenticator</h5>
                            <h5><a href="javascript:void(0)" onclick="twoFA('activate','google');"><span lang="en">Activate</span></a></h5>
                        </li>
                    </ul>
                </div>
            `;
            break;
        case 'google':
            var option = `
                <div class="col-lg-3 col-sm-6 row-in-br">
                    <ul class="col-in">
                        <li>
                            <span class="circle circle-md bg-info"><i class="fa fa-google"></i></span>
                        </li>
                        <li class="col-middle">
                            <h5>Google Authenticator</h5>
                            <h5><a href="javascript:void(0)" onclick="twoFA('deactivate','google');"><span lang="en">Deactivate</span></a></h5>
                        </li>
                    </ul>
                </div>
            `;
            break;
        default:
            break;
    }
    var element = `
    <div class="white-box 2fa-list">
        <div class="row row-in">
            `+option+`
        </div>
    </div>
    `;








    return element;
}
function revokeToken(token,id){
    organizrAPI('POST','api/?v1/token/revoke',{token:token}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        if(response.data == true){
            $('#token-'+id).fadeOut();
            message(window.lang.translate('Removed Token'),"",activeInfo.settings.notifications.position,"#FFF","success","3500");
        }else{
            message(window.lang.translate('Error: Removing Token'),"",activeInfo.settings.notifications.position,"#FFF","error","3500");
        }
    }).fail(function(xhr) {
        ajaxloader();
        console.error("Organizr Function: API Connection Failed");
    });
}
function buildActiveTokens(array) {
    var parser = new UAParser();
    var tokens = '';
    $.each(array, function(i,v) {
        parser.setUA(v.browser);
        var result = parser.getResult();
        var className = (activeInfo.user.token == v.token) ? 'bg-success text-inverse' : '';
        var extraText = (activeInfo.user.token == v.token) ? '<span class="tooltip-info" data-toggle="tooltip" data-placement="right" title="" data-original-title="Current Token">...'+v.token.substr(-10, 10)+'</span>' : v.token.substr(-10, 10);
        tokens += `
            <tr id="token-`+v.id+`" class="`+className+`">
                <td>`+v.id+`</td>
                <td>`+extraText+`</td>
                <td>`+moment(v.created).format('LLL')+`</td>
                <td>`+moment(v.expires).format('LLL')+`</td>
                <td><a data-toggle="collapse" href="#token-`+v.id+`-info" aria-expanded="false" href="javascript:void(0)">`+(result.browser.name)+`</a>
                    <div id="token-`+v.id+`-info" class="table-responsive collapse">
                        <table class="table color-bordered-table purple-bordered-table">
                            <tbody class="bg-org">
                                <tr>
                                    <td>Browser</td>
                                    <td>`+result.browser.name+`</td>
                                </tr>
                                <tr>
                                    <td>Version</td>
                                    <td>`+result.browser.version+`</td>
                                </tr>
                                <tr>
                                    <td>OS</td>
                                    <td>`+result.os.name+`</td>
                                </tr>
                                <tr>
                                    <td>Version</td>
                                    <td>`+result.os.version+`</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
</td>
                <td>`+(v.ip)+`</td>
                <td>
                    <button class="btn btn-danger waves-effect waves-light" type="button" onclick="revokeToken('`+v.token+`', '`+v.id+`');"><i class="fa fa-ban"></i></button>
                </td>
            </tr>
        `;
    });
    return `
        <div class="col-lg-12">
            <div class="panel panel-info">
                <div class="panel-heading"> <span lang="en">Active Tokens</span>
                    <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-plus"></i></a> </div>
                </div>
                <div class="panel-wrapper collapse" aria-expanded="true">
                    <div class="panel-body bg-org p-0">
                        <div class="table-responsive">
                            <table class="table color-table info-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Token</th>
                                        <th>Created</th>
                                        <th>Expires</th>
                                        <th>Browser</th>
                                        <th>IP</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    `+tokens+`
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}
function accountManager(user){
    var passwordMessage = '';
    switch(activeInfo.settings.misc.authBackend){
        case 'plex':
            passwordMessage = `
                <div class="col-lg-12">
                    <div class="panel panel-info">
                        <div class="panel-heading"> <span lang="en">Password Notice</span>
                            <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-plus"></i></a> </div>
                        </div>
                        <div class="panel-wrapper collapse" aria-expanded="true">
                            <div class="panel-body bg-org">
                                <p lang="en">If you signed in with a Plex Acct... Please use the following link to change your password there:</p><br>
                                <p><a href="https://app.plex.tv/auth#?resetPassword" target="_blank">Change Password on Plex Website</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            break;
        case 'emby':
            passwordMessage = `
                <div class="col-lg-12">
                    <div class="panel panel-info">
                        <div class="panel-heading"> <span lang="en">Password Notice</span>
                            <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="panel-dismiss"><i class="ti-close"></i></a> </div>
                        </div>
                        <div class="panel-wrapper collapse in" aria-expanded="true">
                            <div class="panel-body bg-org">
                                <p lang="en">If you signed in with a Emby Acct... Please use the following link to change your password there:</p><br>
                                <p><a href="https://emby.media/community/index.php?app=core&module=global&section=lostpass" target="_blank">Change Password on Emby Website</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            break;
        default:
            passwordMessage = '';
            break;
    }
	if (user.data.user.loggedin === true) {
	    var twoFADisable = (buildTwoFA(user.data.user.authService) == 'internal') ? '' : 'disabled';
	    var activeTokens = buildActiveTokens(user.data.user.tokenList);
		var accountDiv = `
        <!-- 2fa modal content -->
        <div id="twofa-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="twofa-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h4 class="modal-title" id="twofa-modal-label">Enable 2FA</h4> </div>
                    <div class="modal-body">
                        <h4 class="twofa-modal-title text-center text-uppercase"></h4>
                        <p class="twofa-modal-image"></p>
                        <h5 class="twofa-modal-secret text-center"></h5>
                        <div class="form-group m-t-10">
                            <div class="input-group" style="width: 100%;">
                                <div class="input-group-addon hidden-xs"><i class="ti-lock"></i></div>
                                <input type="text" class="form-control tfa-input" id="twofa-verify" placeholder="Code" autocomplete="off" autocorrect="off" autocapitalize="off" maxlength="6" spellcheck="false" autofocus="" required="">
                            </div>
                            <br>
                            <button class="btn btn-block btn-info" onclick="twoFA('verify','google');">Verify</button>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-info waves-effect" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->
		<div id="account-area" class="white-popup mfp-with-anim mfp-hide">
			<div class="col-md-10 col-md-offset-1">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-info m-0">
							<div class="panel-heading">
								<span lang="en">Account Information</span>
								<div class="btn-group pull-right">
									<button class="btn btn-info waves-effect waves-light" type="button" onclick="updateUserInformation();">
										<i class="fa fa-save"></i>
									</button>
								</div>
							</div>
							<div class="panel-wrapper collapse in main-email-panel" aria-expanded="true">
								<div class="panel-body">
									<div class="form-body">
									    `+buildTwoFA(user.data.user.authService)+`
										<div class="row">
                                            <div class="col-lg-12">
                                                <div class="panel panel-info">
                                                    <div class="panel-heading"> <span lang="en">User Information</span>
                                                        <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-plus"></i></a> </div>
                                                    </div>
                                                    <div class="panel-wrapper collapse" aria-expanded="true">
                                                        <div class="panel-body bg-org p-0 p-t-10">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="control-label" lang="en">Username</label>
                                                                    <input `+twoFADisable+` type="text" id="accountUsername" class="form-control" value="`+activeInfo.user.username+`"></div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="control-label" lang="en">Email</label>
                                                                    <input `+twoFADisable+` type="text" id="accountEmail" class="form-control" value="`+activeInfo.user.email+`"></div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="control-label" lang="en">Password</label>
                                                                    <input type="password" id="accountPassword1" class="form-control"></div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label class="control-label" lang="en">Verify Password</label>
                                                                    <input type="password" id="accountPassword2" class="form-control"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
										</div>
										<!--/row-->
										<div class="row">
											`+activeTokens+passwordMessage+`
										</div>
										<!--/row-->
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		`;
		$('.organizr-area').after(accountDiv);
		pageLoad();
	}
}
function userMenu(user){
	var menuList = '<li class="hidden-xs" onclick="toggleFullScreen();"><a class="waves-effect waves-light"> <i class="ti-fullscreen fullscreen-icon"></i></a></li>';
	var showDebug = (activeInfo.settings.misc.debugArea) ? '<li><a href="javascript:void(0)" onclick="toggleDebug();getDebugPreInfo();"><i class="mdi mdi-bug fa-fw"></i> <span lang="en">Debug Area</span></a></li>' : '';
	menuList += buildLanguage();
	if (user.data.user.loggedin === true) {
		menuList += `
			<li class="dropdown">
				<a class="dropdown-toggle profile-pic" data-toggle="dropdown" href="javascript:void(0)"><img alt="" class="img-circle profile-image" src="`+user.data.user.image+`" width="36"><b class="hidden-xs">`+user.data.user.username+`</b><span class="caret"></span></a>
				<ul class="dropdown-menu dropdown-user animated flipInY">
					<li>
						<div class="dw-user-box">
							<div class="u-img"><img alt="user" src="`+user.data.user.image+`"></div>
							<div class="u-text"><h4>`+user.data.user.username+`</h4><p class="text-muted">`+user.data.user.email+`</p><p class="text-muted">`+user.data.user.group+`</p></div>
						</div>
					</li>
					<!--<li class="divider" role="separator"></li>
					<li><a href="javascript:void(0)"><i class="ti-user fa-fw"></i> <span lang="en">My Profile</span></a></li>
					<li><a href="javascript:void(0)"><i class="ti-email fa-fw"></i> <span lang="en">Inbox</span></a></li>-->
					<li class="divider" role="separator"></li>
					<li class="append-menu"><a class="inline-popups" href="#account-area" data-effect="mfp-zoom-out"><i class="ti-settings fa-fw"></i> <span lang="en">Account Settings</span></a></li>
					<li class="divider" role="separator"></li>
					<li><a href="javascript:void(0)" onclick="lock();"><i class="ti-lock fa-fw"></i> <span lang="en">Lock Screen</span></a></li>
					` + showDebug + `
					<li><a href="javascript:void(0)" onclick="logout();"><i class="fa fa-sign-out fa-fw"></i> <span lang="en">Logout</span></a></li>
				</ul><!-- /.dropdown-user -->
			</li><!-- /.dropdown -->
		`;
	}else{
		menuList += `
			<li class="dropdown">
					<a class="dropdown-toggle profile-pic" data-toggle="dropdown" href="javascript:void(0)"><img alt="" class="img-circle profile-image" src="`+user.data.user.image+`" width="36"><b class="hidden-xs">`+user.data.user.username+`</b><span class="caret"></span></a>
					<ul class="dropdown-menu dropdown-user animated flipInY">
						<li>
							<div class="dw-user-box">
								<div class="u-img"><img alt="user" src="`+user.data.user.image+`"></div>
								<div class="u-text"><h4>`+user.data.user.username+`</h4></div>
							</div>
						</li>
						<li class="divider" role="separator"></li>
						<li class="append-menu"><a href="javascript:void(0)" class="show-login"><i class="fa fa-sign-in fa-fw"></i> <span lang="en">Login/Register</span></a></li>
					</ul><!-- /.dropdown-user -->
				</li><!-- /.dropdown -->
		`;
	}
	$(menuList).appendTo('.navbar-right').html;
	//message("",window.lang.translate('Welcome')+" "+user.data.user.username,activeInfo.settings.notifications.position,"#FFF","success","3500");
	console.log(window.lang.translate('Welcome')+" "+user.data.user.username);
}
function menuExtras(active){
    var supportFrame = buildFrameContainer('Organizr Support','https://organizr.app/support',1);
    var adminMenu = (activeInfo.user.groupID <= 1) ? buildMenuList('Organizr Support','https://organizr.app/support',1,'fontawesome::life-ring'): '';
    $(supportFrame).appendTo($('.iFrame-listing'));
	if(active === true){
		return `
			<li class="devider"></li>
			<li id="sign-out"><a class="waves-effect" onclick="logout();"><i class="fa fa-sign-out fa-fw"></i> <span class="hide-menu" lang="en">Logout</span></a></li>
			<li class="devider"></li>
			<li id="github"><a href="https://github.com/causefx/organizr" target="_blank" class="waves-effect"><i class="fa fa-github fa-fw text-success"></i> <span class="hide-menu">GitHub</span></a></li>
		`+adminMenu;
	}else{
		return `
			<li class="devider"></li>
			<li id="menu-login"><a class="waves-effect show-login" href="javascript:void(0)"><i class="mdi mdi-login fa-fw"></i> <span class="hide-menu" lang="en">Login/Register</span></a></li>
		`;
	}
}
function categoryProcess(arrayItems){
	var menuList = '';
	if (Array.isArray(arrayItems['data']['categories']) && Array.isArray(arrayItems['data']['tabs'])) {
		$.each(arrayItems['data']['categories'], function(i,v) {
			if(v.count !== 0 && v.category_id !== 0){
				menuList += `
					<li>
						<a class="waves-effect" href="javascript:void(0)">`+iconPrefix(v.image)+`<span class="hide-menu">`+v.category+` <span class="fa arrow"></span> <span class="label label-rouded label-inverse pull-right">`+v.count+`</span></span><div class="menu-category-ping" data-good="0" data-bad="0"></div></a>
						<ul class="nav nav-second-level category-`+v.category_id+` collapse"></ul>
					</li>
				`;
			}
		});
		$(menuList).appendTo($('#side-menu'));
	}
}
function buildFrame(name,url){
    var sandbox = activeInfo.settings.misc.sandbox;
    sandbox = sandbox.replace(/,/gi, ' ');
    sandbox = (sandbox) ? ' sandbox="' + sandbox + '"' : '';
	return `
		<iframe allowfullscreen="true" frameborder="0" id="frame-`+cleanClass(name)+`" data-name="`+cleanClass(name)+`" `+sandbox+` scrolling="auto" src="`+url+`" class="iframe"></iframe>
	`;
}
function buildFrameContainer(name,url,type){
	return `<div id="container-`+cleanClass(name)+`" data-type="`+type+`" class="frame-container frame-`+cleanClass(name)+` hidden" data-url="`+url+`" data-name="`+cleanClass(name)+`"></div>`;
}
function buildInternalContainer(name,url,type){
	return `<div id="internal-`+cleanClass(name)+`" data-type="`+type+`" class="internal-container frame-`+cleanClass(name)+` hidden" data-url="`+url+`" data-name="`+cleanClass(name)+`"></div>`;
}
function buildMenuList(name,url,type,icon,ping=null){
    var ping = (ping !== null) ? `<small class="menu-`+cleanClass(ping)+`-ping-ms hidden-xs label label-rouded label-inverse pull-right pingTime hidden">
</small><div class="menu-`+cleanClass(ping)+`-ping" data-tab-name="`+name+`" data-previous-state=""></div>` : '';
	return `<li class="allTabsList" id="menu-`+cleanClass(name)+`" data-tab-name="`+cleanClass(name)+`" type="`+type+`" data-url="`+url+`"><a class="waves-effect"  onclick="tabActions(event,'`+cleanClass(name)+`',`+type+`);">`+iconPrefix(icon)+`<span class="hide-menu elip sidebar-tabName">`+name+`</span>`+ping+`</a></li>`;
}
function tabProcess(arrayItems) {
	var iFrameList = '';
	var internalList = '';
	var defaultTabName = null;
	var defaultTabType = null;
	if (Array.isArray(arrayItems['data']['tabs']) && arrayItems['data']['tabs'].length > 0) {
		$.each(arrayItems['data']['tabs'], function(i,v) {
			if(v.enabled === 1 && v.access_url){
                tabInformation[cleanClass(v.name)] = {"active":false,"loaded":false,"increments":0,"tabInfo":v};
                switch(v.timeout){
                    case 1:
                    case '1':
                        tabActionsList['close'].push({"tab":cleanClass(v.name),"action_ms":v.timeout_ms});
                        break;
                    case 2:
                    case '2':
                        tabActionsList['refresh'].push({"tab":cleanClass(v.name),"action_ms":v.timeout_ms});
                        break;
                    default:
                        //nada
                }
                if(v.default === 1){
                    defaultTabName = cleanClass(v.name);
                    defaultTabType = v.type;
                }
                var menuList = buildMenuList(v.name,v.access_url,v.type,v.image,v.ping_url);
                if(v.category_id === 0){
                    if(activeInfo.settings.misc.unsortedTabs === 'top'){
                        $(menuList).prependTo($('#side-menu'));
                    }else if(activeInfo.settings.misc.unsortedTabs === 'bottom') {
                        $(menuList).appendTo($('#side-menu'));
                    }
                }else{
                    if(activeInfo.settings.misc.unsortedTabs === 'top'){
                        $(menuList).prependTo($('.category-'+v.category_id));
                    }else if(activeInfo.settings.misc.unsortedTabs === 'bottom') {
                        $(menuList).appendTo($('.category-'+v.category_id));
                    }
                }
                $('#side-menu').metisMenu({ toggle: false });
				switch (v.type) {
					case 0:
					case '0':
					case 'internal':
						internalList = buildInternalContainer(v.name,v.access_url,v.type);
						$(internalList).appendTo($('.internal-listing'));
                        if(v.preload){
                            var newTab = $('#internal-'+cleanClass(v.name));
                            console.log('Tab Function: Preloading new tab for: '+cleanClass(v.name));
                            $('#menu-'+cleanClass(v.name)+' a').children().addClass('tabLoaded');
                            newTab.addClass("loaded");
                            loadInternal(v.access_url,cleanClass(v.name));
                        }
						break;
					case 1:
					case '1':
                    case 'iframe':
						iFrameList = buildFrameContainer(v.name,v.access_url,v.type);
						$(iFrameList).appendTo($('.iFrame-listing'));
                        if(v.preload){
                            var newTab = $('#container-'+cleanClass(v.name));
                            var tabURL = newTab.attr('data-url');
                            console.log('Tab Function: Preloading new tab for: '+cleanClass(v.name));
                            $('#menu-'+cleanClass(v.name)+' a').children().addClass('tabLoaded');
                            newTab.addClass("loaded");
                            $(buildFrame(cleanClass(v.name),tabURL)).appendTo(newTab);
                        }
						break;
					case 2:
					case 3:
					case '2':
					case '3':
					case '_blank':
					case 'popout':
						break;
					default:
						console.error('Tab Process: Action not set');
				}
			}
		});
		getDefault(defaultTabName,defaultTabType);
	}else{
		noTabs(arrayItems);
	}
	$(menuExtras(arrayItems.data.user.loggedin)).appendTo($('#side-menu'));
}
function buildLogin(){
	swapDisplay('login');
	closeSideMenu();
	removeMenuActive();
	$('#menu-login a').addClass('active');
	organizrConnect('api/?v1/login_page').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		console.log("Organizr Function: Opening Login Page");
		$('.login-area').html(response.data);
	}).fail(function(xhr) {
		console.error("Organizr Function: Login Connection Failed");
	});
	$("#preloader").fadeOut();
}
function buildLockscreen(){
	$("#preloader").fadeIn();
	closeSideMenu();
	organizrConnect('api/?v1/lockscreen').success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		console.log("Organizr Function: Adding Lockscreen");
		$(response.data).appendTo($('body'));
	}).fail(function(xhr) {
		console.error("Organizr Function: Lockscreen Connection Failed");
	});
	$("#preloader").fadeOut();
}
function buildSplashScreenItem(arrayItems){
    var splashList = '';
    if (Array.isArray(arrayItems['data']['tabs']) && arrayItems['data']['tabs'].length > 0) {
        arrayItems['data']['tabs'].sort((a, b) => parseFloat(a.order) - parseFloat(b.order));
        $.each(arrayItems['data']['tabs'], function(i,v) {
            if(v.enabled === 1 && v.splash === 1 && v.access_url){
                var image = iconPrefixSplash(v.image);
                if(image.indexOf('.') !== -1){
                    var dataSrc = 'data-src="'+iconPrefixSplash(v.image)+'"';
                    var nonImage = '';
                }else{
                    var dataSrc = '';
                    var nonImage = '<span class="text-uppercase badge bg-org splash-badge">'+image+'</span>';
                }
                splashList += `
                <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 col-xl-2 mouse hvr-grow m-b-20" id="menu-`+cleanClass(v.name)+`" type="`+v.type+`" data-url="`+v.access_url+`" onclick="tabActions(event,'`+cleanClass(v.name)+`',`+v.type+`);">
                    <div class="homepage-drag fc-event bg-org lazyload"  `+ dataSrc +`>
                        `+nonImage+`
                        <span class="homepage-text">&nbsp; `+v.name+`</span>
                    </div>
                </div>
                `;
            }
        });
    }
    return (splashList !== '') ? splashList : false;
}
function buildSplashScreen(json){
    var items = buildSplashScreenItem(json);
    var menu = '<li ><a href="javascript:void(0)" onclick="$(\'.splash-screen\').removeClass(\'hidden\').addClass(\'in\')"><i class="ti-layout-grid2 fa-fw"></i> <span lang="en">Splash Page</span></a></li>';
    if(items){
        closeSideMenu();
        console.log("Organizr Function: Adding Splash Screen");
        var splash = `
        <section id="splashScreen" class="lock-screen splash-screen fade in">
            <div class="row p-20 flexbox">`+items+`</div>
            <div class="row p-20 p-t-0 flexbox">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 mouse hvr-wobble-bottom" onclick="$('.splash-screen').addClass('hidden').removeClass('in')">
                    <div class="homepage-drag fc-event bg-danger lazyload"  data-src="">
                        <span class="homepage-text">&nbsp; Close Splash</span>
                    </div>
                </div>
            </div>
        </section>
        `;
        $(splash).appendTo($('body'));
        $('.append-menu').after(menu);
    }
}
function buildUserGroupSelect(array, userID, groupID){
	var groupSelect = '';
	var selected = '';
	var disabled = '';
	if(groupID == 0  && userID == 1){
		disabled = 'disabled';
	}
	$.each(array, function(i,v) {
		selected = '';
		if(v.group_id == groupID){
			selected = 'selected';
		}
		var selectDisable = (v.group_id == 0 || v.group_id == 999) ? 'disabled' : '';
		groupSelect += '<option '+selected+' '+selectDisable+' value="'+v.group_id+'">'+v.group+'</option>';
	});
	return '<td><select name="userGroupSelect" class="form-control userGroupSelect" '+disabled+'>'+groupSelect+'</select></td>';
}
function buildTabGroupSelect(array, tabID, groupID){
	var groupSelect = '';
	var selected = '';
	$.each(array, function(i,v) {
		selected = '';
		if(v.group_id == groupID){
			selected = 'selected';
		}
		groupSelect += '<option '+selected+' value="'+v.group_id+'">'+v.group+'</option>';
	});
	return '<td><select name="tab['+tabID+'].group_id" class="form-control tabGroupSelect">'+groupSelect+'</select></td>';
}
function buildTabTypeSelect(tabID, typeID, disabled){
	var array = [
		{
			'type_id':0,
			'type':'Internal'
		},
		{
			'type_id':1,
			'type':'iFrame'
		},
		{
			'type_id':2,
			'type':'New Window'
		}
    ];
	var typeSelect = '';
	var selected = '';
	$.each(array, function(i,v) {
		selected = '';
		if(v.type_id == typeID){
			selected = 'selected';
		}
        var disabledAttr = (disabled === 'disabled' && v.type !== 'Internal') ? 'disabled' : '';
		typeSelect += '<option '+selected+' value="'+v.type_id+'" '+disabledAttr+'>'+v.type+'</option>';
	});
	return '<td><select name="tab['+tabID+'].type" class="form-control tabTypeSelect">'+typeSelect+'</select></td>';
}
function buildTabCategorySelect(array,tabID, categoryID){
	var categorySelect = '';
	var selected = '';
	$.each(array, function(i,v) {
		selected = '';
		if(v.category_id == categoryID){
			selected = 'selected';
		}
		categorySelect += '<option '+selected+' value="'+v.category_id+'">'+v.category+'</option>';
	});
	return '<td><select name="tab['+tabID+'].category_id" class="form-control tabCategorySelect">'+categorySelect+'</select></td>';
}
function buildUserManagementItem(array){
	var userList = '';
	$.each(array.users, function(i,v) {
		var disabledDelete = (v.group_id == 999 || v.group_id == 0) ? 'disabled' : 'deleteUser';
		userList += `
		<tr class="userManagement" data-id="`+v.id+`" data-username="`+v.username+`" data-group="`+v.group+`" data-email="`+v.email+`">
			<td class="text-center el-element-overlay">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0">
						<img alt="user-img" class="img-circle" src="`+v.image+`" width="45">
						<div class="el-overlay">
							<ul class="el-info">
								`+v.id+`
							</ul>
						</div>
					</div>
				</div>
			</td>
			<td>`+v.username+`
				<br/><span class="text-muted">`+v.email+`</span></td>
			<td>`+moment(v.register_date).format('ll')+`
				<br/><span class="text-muted">`+moment(v.register_date).format('LT')+`</span></td>
			`+buildUserGroupSelect(array.groups,v.id,v.group_id)+`
			<td><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-5 editUserButton popup-with-form" href="#edit-user-form" data-effect="mfp-3d-unfold"><i class="ti-pencil-alt"></i></button></td>
			<td><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-20 emailUser"><i class="ti-email"></i></button></td>
			<td><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5 `+disabledDelete+`"><i class="ti-trash"></i></button></td>
		</tr>
		`;
	});
	return userList;
}
function buildGroupManagementItem(array){
	var userList = '';
	$.each(array.groups, function(i,v) {
		var userCount = array.users.reduce(function (n, group) {
		    return n + (group.group_id == v.group_id);
		}, 0);
		var disabledDefault = (v.group_id == 0 || v.group_id == 999) ? 'disabled' : '';
		var disabledDelete = (userCount > 0 || v.default == 1 || v.group_id == 999 || v.group_id <= 1) ? 'disabled' : '';
		var defaultIcon = (v.default == 1) ? 'icon-user-following' : 'icon-user-follow';
		var defaultColor = (v.default == 1) ? 'btn-info disabled' : 'btn-warning';
		userList += `
		<tr class="userManagement" data-id="`+v.id+`" data-group-id="`+v.group_id+`" data-group="`+v.group+`" data-default="`+tof(v.default)+`" data-image="`+v.image+`" data-user-count="`+userCount+`">
			<td class="text-center el-element-overlay">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0">
						<div class="tabEditorIcon">`+iconPrefix(v.image)+`</div>
						<div class="el-overlay">
							<ul class="el-info">
								`+v.group_id+`
							</ul>
						</div>
					</div>
				</div>
			</td>
			<td>`+v.group+`</td>
			<td>`+userCount+`</td>
			<td><button type="button" class="btn `+defaultColor+` btn-outline btn-circle btn-lg m-r-5 changeDefaultGroup" `+disabledDefault+`><i class="`+defaultIcon+`"></i></button></td>
			<td><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-5 editGroupButton popup-with-form" href="#edit-group-form" data-effect="mfp-3d-unfold"><i class="ti-pencil-alt"></i></button></td>
			<td><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5 deleteUserGroup" `+disabledDelete+`><i class="ti-trash"></i></button></td>
		</tr>
		`;
	});
	return userList;
}
function buildCategoryEditorItem(array){
	var categoryList = '';
	$.each(array.categories, function(i,v) {
		var tabCount = array.tabs.reduce(function (n, category) {
		    return n + (category.category_id == v.category_id);
		}, 0);
		var disabledDefault = (v.default == 1) ? 'disabled' : '';
		var disabledDelete = (tabCount > 0 || v.default == 1 || v.category_id == 0) ? 'disabled' : '';
		var defaultIcon = (v.default == 1) ? 'icon-user-following' : 'icon-user-follow';
		var defaultColor = (v.default == 1) ? 'btn-info disabled' : 'btn-warning';
		categoryList += `
		<tr class="categoryEditor" data-id="`+v.id+`" data-order="`+v.order+`" data-category-id="`+v.category_id+`" data-name="`+v.category+`" data-default="`+tof(v.default)+`" data-image="`+v.image+`" data-tab-count="`+tabCount+`">
			<input type="hidden" class="form-control order" name="category[`+v.id+`].order" value="`+v.order+`">
			<input type="hidden" class="form-control" name="category[`+v.id+`].originalOrder" value="`+v.order+`">
			<input type="hidden" class="form-control" name="category[`+v.id+`].name" value="`+v.category+`">
			<input type="hidden" class="form-control" name="category[`+v.id+`].id" value="`+v.id+`">
			<td class="text-center el-element-overlay">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0">
						<div class="tabEditorIcon">`+iconPrefix(v.image)+`</div>
						<div class="el-overlay bg-org">
							<ul class="el-info">
								<i class="fa fa-bars"></i>
							</ul>
						</div>
					</div>
				</div>
			</td>
			<td>`+v.category+`</td>
			<td style="text-align:center">`+tabCount+`</td>
			<td style="text-align:center"><button type="button" class="btn `+defaultColor+` btn-outline btn-circle btn-lg m-r-5 changeDefaultCategory" `+disabledDefault+`><i class="`+defaultIcon+`"></i></button></td>
			<td style="text-align:center"><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-5 editCategoryButton popup-with-form" href="#edit-category-form" data-effect="mfp-3d-unfold"><i class="ti-pencil-alt"></i></button></td>
			<td style="text-align:center"><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5 deleteCategory" `+disabledDelete+`><i class="ti-trash"></i></button></td>
		</tr>
		`;
	});
	return categoryList;
}
function buildTabEditorItem(array){
	var tabList = '';
	$.each(array.tabs, function(i,v) {
		var deleteDisabled = v.url.indexOf('/settings/') > 0 ? 'disabled' : 'deleteTab';
		var buttonDisabled = v.url.indexOf('/settings/') > 0 ? 'disabled' : '';
        var typeDisabled = v.url.indexOf('/?v1/') > 0 ? 'disabled' : '';
		tabList += `
		<tr class="tabEditor" data-order="`+v.order+`" data-id="`+v.id+`" data-group-id="`+v.group_id+`" data-category-id="`+v.category_id+`" data-name="`+v.name+`" data-url="`+v.url+`" data-local-url="`+v.url_local+`" data-ping-url="`+v.ping_url+`" data-image="`+v.image+`" data-tab-action-type="`+v.timeout+`" data-tab-action-time="`+v.timeout_ms+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].id" value="`+v.id+`">
			<input type="hidden" class="form-control order" name="tab[`+v.id+`].order" value="`+v.order+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].originalOrder" value="`+v.order+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].url_local" value="`+v.url_local+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].name" value="`+v.name+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].url" value="`+v.url+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].ping_url" value="`+v.ping_url+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].image" value="`+v.image+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].timeout" value="`+v.timeout+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].timeout_ms" value="`+v.timeout_ms+`">
			<td style="text-align:center" class="text-center el-element-overlay">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0">
						<div class="tabEditorIcon">`+iconPrefix(v.image)+`</div>
						<div class="el-overlay bg-org">
							<ul class="el-info">
								<i class="fa fa-bars"></i>
							</ul>
						</div>
					</div>
				</div>
			</td>
			<td><span class="tooltip-info" data-toggle="tooltip" data-placement="right" title="" data-original-title="`+v.url+`">`+v.name+`</span><span id="checkTabHomepageItem-`+v.id+`" data-url="`+v.url+`" data-url-local="`+v.url_local+`" data-name="`+v.name+`" class="checkTabHomepageItem mouse label label-rouded label-inverse pull-right"></span></td>
			`+buildTabCategorySelect(array.categories,v.id, v.category_id)+`
			`+buildTabGroupSelect(array.groups,v.id, v.group_id)+`
			`+buildTabTypeSelect(v.id, v.type, typeDisabled)+`
			<td style="text-align:center"><div class="radio radio-purple"><input onclick="radioLoop(this);" type="radio" class="defaultSwitch" id="tab[`+v.id+`].default" name="tab[`+v.id+`].default" value="true" `+tof(v.default,'c')+`><label for="tab[`+v.id+`].default"></label></div></td>

			<td style="text-align:center"><input `+buttonDisabled+` type="checkbox" class="js-switch enabledSwitch `+buttonDisabled+`" data-size="small" data-color="#99d683" data-secondary-color="#f96262" name="tab[`+v.id+`].enabled" value="true" `+tof(v.enabled,'c')+`/><input type="hidden" class="form-control" name="tab[`+v.id+`].enabled" value="false"></td>
			<td style="text-align:center"><input type="checkbox" class="js-switch splashSwitch" data-size="small" data-color="#99d683" data-secondary-color="#f96262" name="tab[`+v.id+`].splash" value="true" `+tof(v.splash,'c')+`/><input type="hidden" class="form-control" name="tab[`+v.id+`].splash" value="false"></td>
			<td style="text-align:center"><input type="checkbox" class="js-switch pingSwitch" data-size="small" data-color="#99d683" data-secondary-color="#f96262" name="tab[`+v.id+`].ping" value="true" `+tof(v.ping,'c')+`/><input type="hidden" class="form-control" name="tab[`+v.id+`].ping" value="false"></td>
			<td style="text-align:center"><input type="checkbox" class="js-switch preloadSwitch" data-size="small" data-color="#99d683" data-secondary-color="#f96262" name="tab[`+v.id+`].preload" value="true" `+tof(v.preload,'c')+`/><input type="hidden" class="form-control" name="tab[`+v.id+`].preload" value="false"></td>
			<td style="text-align:center"><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-5 editTabButton popup-with-form" href="#edit-tab-form" data-effect="mfp-3d-unfold"><i class="ti-pencil-alt"></i></button></td>
			<td style="text-align:center"><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5 `+deleteDisabled+`"><i class="ti-trash"></i></button></td>
		</tr>
		`;
	});
	return tabList;
}
function submitSettingsForm(form){
    var list = $( "#"+form ).serializeToJSON();
    var size = 0;
    var submit = {};
    $.each(list, function(i,v) {
        if(v !== '#987654' && i.includes('disable-pwd-mgr') == false){
            size++;
            var input = $( "#"+form+" [name='"+i+"']" );
            var dataType = input.attr('data-type');
            switch (dataType) {
                case 'switch':
                case 'checkbox':
                    var value = input.prop("checked") ? true : false;
                    break;
				case 'select2':
                    var value = (input.val() !== null) ? input.val().toString() : '';
                    break;
                default:
                    var value = input.val();
            }

            submit[i] = {name: i , value: value, type: dataType};
        }
    });
    var post = {
        api:'api/?v1/update/config/multiple/form',
        payload:submit,
        messageTitle:'',
        messageBody:'Updated Items',
        error:'Organizr Function: API Connection Failed'
    };
    var callbacks = $.Callbacks();
    // Custom Callbacks
    switch(form){
        case 'customize-appearance-form':
            //callbacks.add( buildCustomizeAppearance );
            break;
        default:

    }
    if(size > 0){
        //console.log(submit);
        settingsAPI(post,callbacks);
        $("#"+form+" :input").each(function(){
            var input = $(this);
            input.closest('.form-group').removeClass('has-success').removeClass('has-error');
        });
        $('#'+form+'-save').addClass('hidden');
    }else{
        $("#"+form+" :input").each(function(){
            var input = $(this);
            input.closest('.form-group').removeClass('has-success').addClass('has-error');
        });
    }
}
function submitHomepageOrder(){
	var list = $( "#homepage-values" ).serializeToJSON();
	var size = 0;
	var submit = {};
	$.each(list, function(i,v) {
		if(v !== ''){
			size++;
			submit[i] = v;
		}
	});
    var post = {
        api:'api/?v1/update/config/multiple',
        payload:submit,
        messageTitle:'',
        messageBody:'Updated Homepage Order',
        error:'Organizr Function: API Connection Failed'
    };
    var callbacks = $.Callbacks();
    //callbacks.add( buildCustomizeAppearance );
	if(size > 0){
		settingsAPI(post,callbacks);
        $('#submitHomepageOrder-save').addClass('hidden');
	}else{
	    console.log('add error');
	}
}
function submitTabOrder(newTabs){
	var post = {
		action:'changeOrder',
		api:'api/?v1/settings/tab/editor/tabs',
		tabs:newTabs,
		messageTitle:'',
		messageBody:window.lang.translate('Tab Order Saved'),
		error:'Organizr Function: API Connection Failed'
	};
	var callbacks = $.Callbacks();
    callbacks.add( buildTabEditor );
	settingsAPI(post,callbacks);
}
function submitCategoryOrder(){
	var post = {
		action:'changeOrder',
		api:'api/?v1/settings/tab/editor/categories',
		categories:$( "#submit-categories-form" ).serializeToJSON(),
		messageTitle:'',
		messageBody:window.lang.translate('Category Order Saved'),
		error:'Organizr Function: API Connection Failed'
	};
	var callbacks = $.Callbacks();
    callbacks.add( buildCategoryEditor );
	settingsAPI(post,callbacks);
}
function buildTR(array,type,badge){
	var listing = '';
	var arrayItems = array.split("|");
	if(hasValue(arrayItems) === true){
		$.each(arrayItems, function(i,v) {
			listing += `
			<tr>
				<td  width="70"><span class="label label-`+badge+`"><span lang="en">`+type+`</span></span></td>
				<td>`+updateIssueLink(v)+`</td>
			</tr>
			`;
		});
		return listing;
	}
	return ' ';
}
function buildVersion(array){
	var x = 0;
	var versions = '<div class="col-md-3 col-sm-4 col-xs-6 m-b-10 pull-right"><button onclick="manualUpdateCheck()" class="btn btn-sm btn-primary btn-rounded waves-effect waves-light pull-right row b-none buttonManualUpdateCheck" type="button"><span class="btn-label"><i class="fa fa-globe"></i></span><span lang="en">Check For Updates</span></button></div><div class="clearfix"></div>';
	var listing = '';
	var currentV = currentVersion;
	var installed = '';
	var spanClass = '';
	var button = '';
	$.each(array, function(i,v) {
		listing += buildTR(v.new,'NEW','info');
		listing += buildTR(v.fixed,'FIXED','success');
		listing += buildTR(v.notes,'NOTE','warning');
		if(currentV === i){
			button = '<button class="btn btn-sm btn-success btn-rounded waves-effect waves-light disabled pull-right row b-none" type="button"><span class="btn-label"><i class="fa fa-check"></i></span><span lang="en">Installed</span></button>';
		}else if (x === 0){
			button = '<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none" type="button" onclick="updateNow();"><span class="btn-label"><i class="fa fa-download"></i></span><span lang="en">Install Update</span></button>';
		}
		versions += `
		<div class="white-box bg-org">
			<div class="col-md-3 col-sm-4 col-xs-6 pull-right">`+button+`</div>
			<h3 class="box-title">`+i+`</h3>
			<div class="row sales-report">
				<div class="col-md-12 col-sm-12 col-xs-12">

						<span class="tooltip-info" data-toggle="tooltip" data-placement="right" title="" data-original-title="`+moment(v.date).format('LL')+`">`+moment.utc(v.date, "YYYY-MM-DD hh:mm[Z]").local().fromNow()+`</span>

					<p class="text-info p-0">`+v.title+`</p>
				</div>
			</div>
			<div class="table-responsive">
				<table class="table inverse-bordered-table">
					<tbody>
						`+listing+`
					</tbody>
				</table>
			</div>
		</div>
		`;
		listing = '';
		button = '';
		x++;
	});
	return versions;
}
function loadInternal(url,tabName){
	organizrAPI('get',url).success(function(data) {
        try {
            var html = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		$('#internal-'+tabName).html(html.data);
	}).fail(function(xhr) {
		console.error("Organizr Function: Connection Failed");
	});
}
function loadSettingsPage(api,element,organizrFn){
	organizrAPI('get',api).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		console.log('Organizr Function: Loading '+organizrFn);
		$(element).html(response.data);
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function manualUpdateCheck(){
    $('.buttonManualUpdateCheck').addClass('disabled');
    $('.buttonManualUpdateCheck i').removeClass('fa-globe').addClass('fa-refresh fa-spin');
    setTimeout(function(){ updateCheck(); checkCommitLoad(); }, 1000);
    setTimeout(function(){
        $('.buttonManualUpdateCheck').removeClass('disabled');
        $('.buttonManualUpdateCheck i').removeClass('fa-refresh fa-spin fa-globe').addClass('fa-check');
     }, 1500);
    return true;
}
function updateCheck(){
	githubVersions().success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		for (var a in reverseObject(response)){
			var latest = a;
			break;
		}
		if(latest !== currentVersion) {
            console.log('Update Function: Update to ' + latest + ' is available');
            if (activeInfo.settings.misc.docker === false) {
                messageSingle(window.lang.translate('Update Available'), latest + ' ' + window.lang.translate('is available, goto') + ' <a href="javascript:void(0)" onclick="tabActions(event,\'Settings\',0);clickPath(\'update\')"><span lang="en">Update Tab</span></a>', activeInfo.settings.notifications.position, '#FFF', 'update', '60000');
            }
        }
		$('#githubVersions').html(buildVersion(reverseObject(response)));
	}).fail(function(xhr) {
		console.error("Organizr Function: Github Connection Failed");
	});
}
function newsLoad(){
    newsJSON().success(function(data) {
        try {
            var response = JSON.parse(data);
            var items = [];
            $.each(response, function(i,v) {
                var newBody = `
                <h5 class="pull-left">`+moment(v.date).format('LLL')+`</h5>
                <h5 class="pull-right">`+v.author+`</h5>
                <div class="clearfix"></div>
                `+((v.subTitle) ? '<h5>' + v.subTitle + '</h5>' : '' )+`
                <p>`+v.body+`</p>
                `;
                items[i] = {
                    title:v.title,
                    body:newBody
                }
            });
            var body = buildAccordion(items, true);
            $('#organizrNewsPanel').html(body);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
    }).fail(function(xhr) {
        console.error("Organizr Function: Github Connection Failed");
    });
}
function checkCommitLoad(){
    if(activeInfo.settings.misc.docker && activeInfo.settings.misc.githubCommit !== 'n/a') {
        getLatestCommitJSON().success(function (data) {
            try {
                var latest = data.sha.toString().trim();
                var current = activeInfo.settings.misc.githubCommit.toString().trim();
                var link = 'https://github.com/causefx/Organizr/compare/'+current+'...'+latest;
                if(latest !== current) {
                    messageSingle(window.lang.translate('Update Available'),' <a href="'+link+'" target="_blank"><span lang="en">Compare Difference</span></a> <span lang="en">or</span> <a href="javascript:void(0)" onclick="updateNow()"><span lang="en">Update Now</span></a>', activeInfo.settings.notifications.position, '#FFF', 'update', '600000');
                }else{
                    console.log('Organizr Docker - Up to date');
                }
            } catch (e) {
                console.log(e + ' error: ' + data);
                orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                return false;
            }
        }).fail(function (xhr) {
            console.error("Organizr Function: Github Connection Failed");
        });
    }
}
function sponsorLoad(){
    sponsorsJSON().success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        /*for (var a in reverseObject(json)){
            var latest = a;
            break;
        }
        if(latest !== currentVersion){
            console.log('Update Function: Update to '+latest+' is available');
            message(window.lang.translate('Update Available'),latest+' '+window.lang.translate('is available, goto')+' <a href="javascript:void(0)" onclick="tabActions(event,\'Settings\',0);$(\'#update-button\').click()"><span lang="en">Update Tab</span></a>',activeInfo.settings.notifications.position,'#FFF','update','60000');
        }*/
        $('#sponsorList').html(buildSponsor(response));
        $('#sponsorListModals').html(buildSponsorModal(response));
        $('.sponsor-items').owlCarousel({
            nav:false,
            autoplay:true,
            dots:false,
            margin:10,
            autoWidth:true,
            items:4
        });
    }).fail(function(xhr) {
        console.error("Organizr Function: Github Connection Failed");
    });
}
function sponsorAbout(id,array){
    var coupon = (array.coupon == null) ? false : true;
    var couponAbout = (array.coupon_about == null) ? false : true;
    var extraInfo = (coupon && couponAbout) ? `
        <h3>Coupon Code:</h3>
        <p><span class="label label-rouded label-info pull-right">`+array.coupon+`</span>
        <span class=" pull-left">`+array.coupon_about+`</span></p>
    ` : '';
    return `
        <!--  modal content -->
        <div id="sponsor-`+id+`-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel-`+id+`" aria-hidden="true" style="display: none;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h4 class="modal-title" id="mySmallModalLabel-`+id+`">`+array.company_name+`</h4> </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="comment-center p-t-10">
                                    <div class="comment-body b-none">
                                        <div class="user-img"> <img src="`+array.logo+`" alt="user" class="img-circle"> </div>
                                        <div class="mail-contnet">
                                            <h5><a href="`+array.website+`" target="_blank">`+array.company_name+`</a></h5> 
                                            `+array.about+extraInfo+`
                                         </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->
    `;
}
function buildSponsor(array){
    var sponsors = '';
    $.each(array, function(i,v) {
        var hasCoupon = '';
        if(v.about){
            if(v.coupon){
                hasCoupon = `
                    <span class="text-center has-coupon-text">Has Coupon</span>
                    <span class="text-center has-coupon"><i class="fa fa-ticket" aria-hidden="true"></i></span>
                `;
            }
        }
        var sponsorAboutModal = (v.about) ? 'data-toggle="modal" data-target="#sponsor-'+i+'-modal" onclick="sponsorAnalytics(\''+v.company_name+'\');"' : 'onclick="window.open(\''+ v.website +'\', \'_blank\');sponsorAnalytics(\''+v.company_name+'\');"';
        sponsors += `
            <!-- /.usercard -->
            <div class="item lazyload recent-sponsor mouse imageSource mouse" `+sponsorAboutModal+` data-src="`+v.logo+`">
                <span class="elip recent-title">`+v.company_name+`</span>
                `+ hasCoupon +`
            </div>
            <!-- /.usercard-->
        `;
    });
    sponsors += `
        <!-- /.usercard -->
        <div class="item lazyload recent-sponsor mouse imageSource mouse" onclick="window.open('https://www.patreon.com/bePatron?c=1320444&rid=2874514', '_blank')" data-src="plugins/images/sponsor.png">
            <span class="elip recent-title" lang="en">Become Sponsor</span>
        </div>
        <!-- /.usercard-->
    `;
    return sponsors;
}
function buildSponsorModal(array){
    var sponsors = '';
    $.each(array, function(i,v) {
        var sponsorAboutModal = (v.about) ? sponsorAbout(i,v) : '';
        sponsors += sponsorAboutModal;

    });
    return sponsors;
}
function sponsorAnalytics(sponsor_name){
    var uuid = activeInfo.settings.misc.uuid;
    $.ajax({
        type: 'POST',
        url: 'https://api.organizr.app/',
        data: {
            'sponsor_name': sponsor_name,
            'user_uuid': uuid,
            'cmd': 'sponsor'
        },
        cache: false,
        async: true,
        complete: function(xhr, status) {
            if (xhr.status === 200) {
                var result = $.parseJSON(xhr.responseText);
                console.log(result.response.message);
            }
        }
    });
}
function themeAnalytics(theme_name){
    var uuid = activeInfo.settings.misc.uuid;
    $.ajax({
        type: 'POST',
        url: 'https://api.organizr.app/',
        data: {
            'theme_name': theme_name,
            'user_uuid': uuid,
            'cmd': 'theme'
        },
        cache: false,
        async: true,
        complete: function(xhr, status) {
            if (xhr.status === 200) {
                var result = $.parseJSON(xhr.responseText);
                console.log(result.response.message);
            }
        }
    });
}
function updateBar(){
	return `
	<div class="white-box m-0">
        <div class="row">
            <div class="col-lg-12 p-r-40">
                <h3 id="update-title" class="box-title pull-left"></h3><h3 id="update-time" class="box-title pull-right hidden"><span id="update-seconds"></span>&nbsp;<span lang="en">Seconds</span></h3>
				<div class="clearfix"></div>
                <div class="progress progress-lg">
                    <div id="update-bar" class="progress-bar progress-bar-primary progress-bar-striped active" style="width: 0%;" role="progressbar">0%</div>
                </div>
            </div>
        </div>
    </div>
	`;
}
function updateUpdateBar(title,percent,update=false){
	$('#update-title').text(title);
	$('#update-bar').text(percent);
	$('#update-bar').css('width',percent);
	if(update){
		$('#update-time').removeClass('hidden');
		countdown(10);
	}
}
function countdown(remaining) {
    if(remaining === 0){
		local('set','message','Organizr Update|Update Successful|update');
        location.reload(true);
	}
	$('#update-seconds').text(remaining);
    setTimeout(function(){ countdown(remaining - 1); }, 1000);
}
function dockerUpdate(){
    if(activeInfo.settings.misc.docker){
        $(updateBar()).appendTo('.organizr-area');
        updateUpdateBar('Starting Download','20%');
        messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'),window.lang.translate('Starting Update Process'),activeInfo.settings.notifications.position,'#FFF','success','60000');
        organizrAPI('GET','api/?v1/docker/update').success(function(data) {
            try {
                var json = JSON.parse(data);
            }catch(e) {
                console.log(e + ' error: ' + data);
                orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                return false;
            }
            updateUpdateBar('Restarting Organizr in', '100%', true);
            messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'),json.data,activeInfo.settings.notifications.position,'#FFF','success','60000');
        }).fail(function(xhr) {
            console.error("Organizr Function: Reboot Failed");
        });
    }
}
function windowsUpdate(){
    if(activeInfo.serverOS == 'win'){
        $(updateBar()).appendTo('.organizr-area');
        updateUpdateBar('Starting Download','20%');
        messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'),window.lang.translate('Starting Update Process'),activeInfo.settings.notifications.position,'#FFF','success','60000');
        organizrAPI('GET','api/?v1/windows/update').success(function(data) {
            try {
                var json = JSON.parse(data);
            }catch(e) {
                console.log(e + ' error: ' + data);
                orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                return false;
            }
            updateUpdateBar('Restarting Organizr in', '100%', true);
            messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'),json.data,activeInfo.settings.notifications.position,'#FFF','success','60000');
        }).fail(function(xhr) {
            console.error("Organizr Function: Reboot Failed");
        });
    }
}
function updateNow(){
    clearAJAX();
    if(activeInfo.settings.misc.docker){
        dockerUpdate();
        return false;
    }
    if(activeInfo.serverOS === 'win'){
        windowsUpdate();
        return false;
    }
	console.log('Organizr Function: Starting Update Process');
	$(updateBar()).appendTo('.organizr-area');
	updateUpdateBar('Starting Download','5%');
	messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'),window.lang.translate('Starting Update Process'),activeInfo.settings.notifications.position,'#FFF','success','60000');
	organizrAPI('POST','api/?v1/update', {branch:activeInfo.branch,stage:1}).success(function(data) {
        try {
            var json = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		if(json.data == true) {
            updateUpdateBar('Starting Unzip', '50%');
            messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'), window.lang.translate('Update File Downloaded'), activeInfo.settings.notifications.position, '#FFF', 'success', '60000');
            organizrAPI('POST', 'api/?v1/update', {branch: activeInfo.branch, stage: 2}).success(function (data) {
                try {
                    var json = JSON.parse(data);
                }catch(e) {
                    console.log(e + ' error: ' + data);
                    orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                    return false;
                }
                if (json.data == true) {
                    updateUpdateBar('Starting Copy', '70%');
                    messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'), window.lang.translate('Update File Unzipped'), activeInfo.settings.notifications.position, '#FFF', 'success', '60000');
                    organizrAPI('POST', 'api/?v1/update', {
                        branch: activeInfo.branch,
                        stage: 3
                    }).success(function (data) {
                        try {
                            var json = JSON.parse(data);
                        }catch(e) {
                            console.log(e + ' error: ' + data);
                            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                            return false;
                        }
                        if (json.data == true) {
                            updateUpdateBar('Starting Cleanup', '90%');
                            messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'), window.lang.translate('Update Files Copied'), activeInfo.settings.notifications.position, '#FFF', 'success', '60000');
                            organizrAPI('POST', 'api/?v1/update', {
                                branch: activeInfo.branch,
                                stage: 4
                            }).success(function (data) {
                                try {
                                    var json = JSON.parse(data);
                                }catch(e) {
                                    console.log(e + ' error: ' + data);
                                    orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
                                    return false;
                                }
                                if (json.data == true) {
                                    updateUpdateBar('Restarting Organizr in', '100%', true);
                                    messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'), window.lang.translate('Update Cleanup Finished'), activeInfo.settings.notifications.position, '#FFF', 'success', '60000');
                                } else {
                                    message('', window.lang.translate('Update Cleanup Failed'), activeInfo.settings.notifications.position, '#FFF', 'error', '10000');
                                }
                            }).fail(function (xhr) {
                                console.error("Organizr Function: API Connection Failed");
                            });
                        } else {
                            message('', window.lang.translate('Update File Copy Failed'), activeInfo.settings.notifications.position, '#FFF', 'error', '10000');
                        }
                    }).fail(function (xhr) {
                        console.error("Organizr Function: API Connection Failed");
                    });
                } else {
                    message('', window.lang.translate('Update File Unzip Failed'), activeInfo.settings.notifications.position, '#FFF', 'error', '10000');
                }
            }).fail(function (xhr) {
                console.error("Organizr Function: API Connection Failed");
            });
        }else if(json.data == 'permissions'){
            message('',window.lang.translate('Organizr does not have permissions to download the update'),activeInfo.settings.notifications.position,'#FFF','error','10000');
		}else{
			message('',window.lang.translate('Update File Download Failed'),activeInfo.settings.notifications.position,'#FFF','error','10000');
		}
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function organizrAPI(type,path,data=null){
	var timeout = 10000;
    switch(path){
        case 'api/?v1/windows/update':
            timeout = 120000;
            break;
        default:
            timeout = 10000;
    }
	switch (type) {
		case 'get':
		case 'GET':
		case 'g':
			return $.ajax({
				url:path,
				method:"GET",
				beforeSend: function(request) {
                    request.setRequestHeader("Token", activeInfo.token);
                    request.setRequestHeader("formKey", local('g','formKey'));
				},
				timeout: timeout,
			});
			break;
		case 'post':
		case 'POST':
		case 'p':
		    data.formKey = local('g','formKey');
			return $.ajax({
				url:path,
				method:"POST",
				beforeSend: function(request) {
					request.setRequestHeader("Token", activeInfo.token);
                    request.setRequestHeader("formKey", local('g','formKey'));
				},
				data:{
					data: data,
				}
			});
		default:
		console.warn('Organizr API: Method Not Supported');
	}
}
function githubVersions() {
	return $.ajax({
		url: "https://raw.githubusercontent.com/causefx/Organizr/"+activeInfo.branch+"/js/version.json",
	});
}
function sponsorsJSON() {
    return $.ajax({
        url: "https://raw.githubusercontent.com/causefx/Organizr/v2-develop/js/sponsors.json",
    });
}
function newsJSON() {
    return $.ajax({
        url: "https://raw.githubusercontent.com/causefx/Organizr/v2-develop/js/news.json",
    });
}
function getLatestCommitJSON() {
    return $.ajax({
        url: "https://api.github.com/repos/causefx/Organizr/commits/"+activeInfo.branch,
    });
}
function marketplaceJSON(type) {
    return $.ajax({
        url: "https://raw.githubusercontent.com/causefx/Organizr/v2-"+type+"/"+type+".json",
    });
}
function allIcons() {
    return $.ajax({
        url: "/js/icons.json",
    });
}
function organizrConnect(path){
	return $.ajax({
		url: path,
	});
}
function changeSettingsMenu(path){
	var menuItems = path.split("::");
	var menu = '';
	if(Array.isArray(menuItems)){
		$.each(menuItems, function(i,v) {
			menu += '<li><a lang="en">'+v+'</a></li>';
		});
	}
	$('#settingsBreadcrumb').html(menu);
}
function buildWizard(){
	organizrConnect('api/?v1/wizard_page').success(function(data) {
        try {
            var json = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		console.log("Organizr Function: Starting Install Wizard");
		$(json.data).appendTo($('.organizr-area'));
	}).fail(function(xhr) {
		console.error("Organizr Function: Wizard Connection Failed");
	});
	$("#preloader").fadeOut();
}
function buildDependencyCheck(orgdata){
	organizrConnect('api/?v1/dependencies_page').success(function(data) {
        try {
            var json = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		console.log("Organizr Function: Starting Dependencies Check");
		$(json.data).appendTo($('.organizr-area'));
		$(buildBrowserInfo()).appendTo($('#browser-info'));
		$('#web-folder').html(buildWebFolder(orgdata));
		$('#php-version-check').html(buildPHPCheck(orgdata));
		$(buildDependencyInfo(orgdata)).appendTo($('#depenency-info'));
	}).fail(function(xhr) {
		console.error("Organizr Function: Dependencies Connection Failed");
	});
	$("#preloader").fadeOut();
}
function buildDependencyInfo(arrayItems){
	var listing = '';
	$.each(arrayItems.data.status.dependenciesActive, function(i,v) {
			listing += '<li class="depenency-item" data-name="'+v+'"><a href="javascript:void(0)"><i class="fa fa-check text-success"></i> '+v+'</a></li>';
		});
	$.each(arrayItems.data.status.dependenciesInactive, function(i,v) {
		listing += '<li class="depenency-item" data-name="'+v+'"><a href="javascript:void(0)"><i class="fa fa-close text-danger"><div class="notify"><span class="heartbit"></span></div></i> '+v+'</a></li>';
	});
	return listing;
}
function buildWebFolder(arrayItems){
	var writable = (arrayItems.data.status.writable == 'yes') ? 'Writable - All Good' : 'Not Writable - Please fix permissions';
	var className = (writable == 'Writable - All Good') ? 'bg-primary' : 'bg-danger text-warning';
	$('#web-folder').addClass(className);
	return writable;
}
function buildPHPCheck(arrayItems){
	var phpTest = (arrayItems.data.status.minVersion == 'yes') ? 'PHP Version Approved' : 'Upgrade PHP Version to 7.0';
	var className = (arrayItems.data.status.minVersion == 'yes') ? 'bg-primary' : 'bg-danger text-warning';
	$('#php-version-check').addClass(className);
	return phpTest;
}
function buildBrowserInfo(){
	var listing = '';
	$.each(activeInfo, function(i,v) {
		listing += `
		<tr>
			<td>`+i+`</td>
			<td>`+tof(v)+`</td>
		</tr>
		`;
	});
	return `
	<table class="table table-hover">
		<tbody>
			`+listing+`
		</tbody>
	</table>
	`;
}
function tof(string,type){
	var result;
	if (typeof string == 'undefined' || string == 'false' || string == false || string == null || string == 0 || string == 'off' || string == 'no') {
		result = "0";
	}else if (string == 'true' || string == true || string == 1 || string == 'on' || string == 'yes') {
		result = "1";
	}
	switch (type) {
		case 'bool':
		case 'b':
			return (result == "0") ? (false) : ((result == "1") ? (true) : (string));
			break;
		case 'switch':
		case 's':
			return (result == "0") ? ('off') : ((result == "1") ? ('on') : (string));
			break;
		case 'checkbox':
		case 'c':
			return (result == "0") ? ('') : ((result == "1") ? ('checked') : (string));
			break;
		case 'integer':
		case 'number':
		case 'i':
		case 'n':
			return (result == "0") ? (0) : ((result == "1") ? (1) : (string));
			break;
		case 'question':
		case 'q':
			return (result == "0") ? ('yes') : ((result == "1") ? ('no') : (string));
			break;
		case 'string':
			return string.toString();
			break;
		default:
			return (result == "0") ? ("false") : ((result == "1") ? ("true") : (string));
	}
}
function createRandomString( length ) {
	var str = "";
	for ( ; str.length < length; str += Math.random().toString( 36 ).substr( 2 ) );
	return str.substr( 0, length );
}
function generateAPI(){
	var string = createRandomString(20);
	$('#form-api').focus();
	$('#form-api').val(string);
	$('#form-api').focusout();
	$('#verify-api').text(string);
	$('#form-username').focus();
}
function getCookie(cname) {
	var name = cname + "=";
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(';');
	for(var i = 0; i <ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}
function localStorageSupport() {
    return (('localStorage' in window) && window['localStorage'] !== null)
}
function local(type,key,value=null){
	if (localStorageSupport) {
		switch (type) {
			case 'set':
			case 's':
				localStorage.setItem(key,value);
				break;
			case 'get':
			case 'g':
				return localStorage.getItem(key);
				break;
			case 'remove':
			case 'r':
				localStorage.removeItem(key);
				break;
			default:
			console.warn('Organizr Function: localStorage action not defined');
		}
	}
}
function language(language){
	var language = language.split("-");
	return language[0];
}
function logIcon(type){
	switch (type) {
		case "success":
			return '<i class="fa fa-check text-success"></i><span class="hidden">Success</span>';
			break;
		case "warning":
			return '<i class="fa fa-exclamation-triangle text-warning"></i><span class="hidden">Warning</span>';
			break;
		case "error":
			return '<i class="fa fa-close text-danger"></i><span class="hidden">Error</span>';
			break;
		default:
			return '<i class="fa fa-exclamation-triangle text-warning"></i><span class="hidden">Warning</span>';
	}
}
function radioLoop(element){
	$('[type=radio][id!="'+element.id+'"]').each(function() { this.checked=false });
}
function loadAppearance(appearance){
	//console.log(appearance);
	var cssSettings = '';
	document.title = appearance.title;
	if(appearance.useLogo === false){
		$('#main-logo').html(appearance.title);
		$('#side-logo').html(appearance.title);
	}else{
		$('#main-logo').html('<img alt="home" class="dark-logo" src="'+appearance.logo+'">');
		$('#side-logo').html('<img alt="home" class="dark-logo-side" src="'+appearance.logo+'">');
	}
	if(appearance.headerColor !== ''){
		cssSettings += `
		    .navbar-header{
			    background: `+appearance.headerColor+`;
		    }
		`;
	}
	if(appearance.headerTextColor !== ''){
		cssSettings += `
		    .navbar-top-links > li > a {
			    color: `+appearance.headerTextColor+`;
		    }
		`;
	}
	if(appearance.sidebarColor !== ''){
		cssSettings += `
		    .sidebar, .sidebar .sidebar-head{
			    background: `+appearance.sidebarColor+`;
		    }
		`;
	}
	if(appearance.sidebarTextColor !== ''){
		cssSettings += `
		    #side-menu li a,
			.sidebar .sidebar-head h3,
			#side-menu > li > a.active, #side-menu > li > ul > li > a.active
			{
			    color: `+appearance.sidebarTextColor+`;
		    }
		`;
	}
	if(appearance.accentColor !== ''){
		cssSettings += `
			.bg-info,
			.fc-toolbar,
			.progress-bar-info,
			.label-info {
			    background-color: `+appearance.accentColor+` !important;
			}
			.panel-blue .panel-heading, .panel-info .panel-heading {
			    border-color: `+appearance.accentColor+`;
			}

			.text-info,
			.btn-link, a {
			    color: `+appearance.accentColor+`;
			}
		`;
	}
	if(appearance.accentTextColor !== ''){
		cssSettings += `
			.progress-bar,
			.panel-default .panel-heading,
			.mailbox-widget .customtab li.active a, .mailbox-widget .customtab li.active, .mailbox-widget .customtab li.active a:focus,
			.mailbox-widget .customtab li a {
				color: `+appearance.accentTextColor+`;
			}
		`;
	}
	if(appearance.buttonColor !== ''){
		cssSettings += `
			.btn-info, .btn-info.disabled {
				background: `+appearance.buttonColor+` !important;
				border: 1px solid `+appearance.buttonColor+` !important;
			}
		`;
	}
	if(appearance.buttonTextColor !== ''){
		cssSettings += `
			.btn-info, .btn-info.disabled {
				color: `+appearance.buttonTextColor+` !important;
			}
		`;
	}
	if(appearance.loginWallpaper !== ''){
		cssSettings += `
		    .login-register {
			    background: url(`+appearance.loginWallpaper+`) center center/cover no-repeat!important;
			    height: 100%;
			    position: fixed;
		    }
			.lock-screen {
				background: url(`+appearance.loginWallpaper+`) center center/cover no-repeat!important;
			    height: 100%;
			    position: fixed;
			    z-index: 1001;
			    top: 0;
			    width: 100%;
			    -webkit-user-select: none;
			    -moz-user-select: none;
			    -ms-user-select: none;
			    -o-user-select: none;
			    user-select: none;
			}
		`;
	}
	if(cssSettings !== ''){
		$('#user-appearance').html(cssSettings);
	}
    if(appearance.customThemeCss !== ''){
        $('#custom-theme-css').html(appearance.customThemeCss);
    }
    if(appearance.customCss !== ''){
        $('#custom-css').html(appearance.customCss);
    }
}
function loadCustomJava(appearance){
    if(appearance.customThemeJava !== ''){
        $('#custom-theme-javascript').html(appearance.customThemeJava);
    }
    if(appearance.customJava !== ''){
        $('#custom-javascript').html(appearance.customJava);
    }
}
function clearForm(form){
	$(form+" input[type=text]").each(function() {
        $(this).val('');
    });
    $(form+" input[type=password]").each(function() {
        $(this).val('');
    });
}
function checkMessage(){
	var check = (local('get','message')) ? local('get','message') : false;
	if(check){
		local('remove', 'message');
		var message = check.split('|');
		messageSingle(window.lang.translate(message[0]),window.lang.translate(message[1]),activeInfo.settings.notifications.position,'#FFF',message[2],'10000');
	}
}
function setError(error){
	local('set','error',error);
	var url = window.location.href.split('?')[0];
	url = url.split('#')[0];
	window.location.href = url+'?error';
}
function buildErrorPage(error){
	var description = '';
	var message = '';
	var color = '';
	switch (error) {
		case '401':
			description = 'Unauthorized';
			message = 'Look, you dont belong here';
			color = 'danger';
			break;
		case '404':
			description = 'Not Found';
			message = 'I think I lost it...';
			color = 'primary';
			break;
		default:
			description = 'Something happened';
			message = 'But I dont know what';
			color = 'muted';
	}
	return `
	<div class="error-box">
		<div class="error-body text-center">
			<h1 class="text-`+color+`">`+error+`</h1>
			<h3 class="text-uppercase">`+description+`</h3>
			<p class="text-muted m-t-30 m-b-30" lang="en">`+message+`</p>
			<a href="javascript:void(0);" class="btn btn-`+color+` btn-rounded waves-effect waves-light m-b-40 closeErrorPage animated tada loop-animation" lang="en">OK</a>
		</div>
	</div>
	`;
}
$.urlParam = function(name){
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results==null){
       return null;
    }
    else{
       return decodeURI(results[1]) || 0;
    }
};
function errorPage(error=null,uri=null){
	if(error){
		local('set','error',error);
	}
    if(uri){
        local('set','uri',uri);
    }
	//var urlParams = new URLSearchParams(window.location.search);
	if($.urlParam('error') !== null){
		local('set','error',$.urlParam('error'));
	}
    if($.urlParam('return') !== null && activeInfo.user.loggedin !== true){
        local('set','uri',$.urlParam('return'));
    }
	if ( window.location !== window.parent.location ) {
        var count = 0;
        for (var k in window.parent.location) {
            if (window.parent.location.hasOwnProperty(k)) {
                ++count;
            }
        }
        if(count == 0 || count == 'undefined'){
            return false;
        }
		var iframeError = local('get', 'error');
		parent.errorPage(iframeError);
        local('remove', 'uri');
		$('html').html('');
	  	return false;
	}
	if(local('get', 'error')){
		//show error page
		$('.error-page').html(buildErrorPage(local('get', 'error')));
		$('.error-page').fadeIn();
		local('remove', 'error');
		window.history.pushState({}, document.title, "./" );
	}

}
function uriRedirect(uri=null){
    if(uri){
        local('set','uri',uri);
    }
    if(activeInfo.user.loggedin === true && activeInfo.user.locked !== 1){
        var redirect = local('get', 'uri');
        local('remove', 'uri');
        if(redirect !== null){
            window.location.href = redirect;
        }
    }
}
function changeTheme(theme){
	//$("#preloader").fadeIn();
	$('#theme').attr({
        href: 'css/themes/' + theme + '.css?v='+activeInfo.version
    });
	//$("#preloader").fadeOut();
	console.log('Theme: '+theme);
}
function changeStyle(style){
	//$("#preloader").fadeIn();
	$('#style').attr({
        href: 'css/' + style + '.css?v='+activeInfo.version
    });
	//$("#preloader").fadeOut();
	console.log('Style: '+style);
}
function setSSO(){
	$.each(activeInfo.sso, function(i,v) {
		if(v !== false){
			local('set', i, v);
		}else{
		    local('r', i);
        }
	});
}
function buildStreamItem(array,source){
	var cards = '';
	var count = 0;
	var total = array.length;
	cards += '<div class="flexbox">';
	$.each(array, function(i,v) {
		var icon = '';
		var width = 100;
		var bg = '';
		count++;
        v.nowPlayingImageURL = (v.useImage) ? v.useImage : v.nowPlayingImageURL;
		switch (v.type) {
			case 'music':
				icon = 'icon-music-tone-alt';
				width = (v.nowPlayingImageURL !== 'plugins/images/cache/no-np.png') ? 56 : 100;
				bg = (v.nowPlayingImageURL !== 'plugins/images/cache/no-np.png') ? `
				<img class="imageSource imageSourceLeft" src="`+v.nowPlayingImageURL+`">
				<img class="imageSource imageSourceRight" src="`+v.nowPlayingImageURL+`">
				` : '';
				break;
			case 'movie':
				icon = 'icon-film';
				break;
			case 'tv':
				icon = 'icon-screen-desktop';
				break;
			case 'video':
				icon = 'icon-screen-film';
				break;
			default:

		}
		var userThumb = (v.userThumb) ? '<img src="'+v.userThumb+'" class="nowPlayingUserThumb" alt="User">' : '';
		if(v.sessionType == 'Direct Playing'){
			var userStream = 'Direct Play';
			var userVideo = 'Direct Play';
			var userAudio = 'Direct Play';
		}else{
			var userStream = v.userStream.stream;
			var userVideo = v.userStream.videoDecision+' ('+v.userStream.sourceVideoCodec+' <i class="mdi mdi-ray-start-arrow"></i> '+v.userStream.videoCodec+' '+v.userStream.videoResolution+')';
			var userAudio = v.userStream.audioDecision+' ('+v.userStream.sourceAudioCodec+' <i class="mdi mdi-ray-start-arrow"></i> '+v.userStream.audioCodec+')';

		}
		var streamInfo = '';
		streamInfo += `<div class="text-muted m-t-20 text-uppercase"><span class="text-uppercase"><i class="mdi mdi-play-circle-outline"></i> Stream: `+userStream+`</span></div>`;
		streamInfo += (v.userStream.videoResolution) ? `<div class="text-muted m-t-20 text-uppercase"><span class="text-uppercase"><i class="mdi mdi-video"></i> Video: `+userVideo+`</span></div>` : '';
		streamInfo += `<div class="text-muted m-t-20 text-uppercase"><span class="text-uppercase"><i class="mdi mdi-speaker"></i> Audio: `+userAudio+`</span></div>`;
		v.session = v.session.replace(/[\W_]+/g,"-");
		cards += `
		<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-xs-12 nowPlayingItem">
			<div class="white-box">
				<div class="el-card-item p-b-10">
					<div class="el-card-avatar el-overlay-1 m-b-0">`+bg+`<img class="imageSource" style="width:`+width+`%;margin-left: auto;margin-right: auto;" src="`+v.nowPlayingImageURL+`">
						<div class="el-overlay">
							<ul class="el-info p-t-20 m-t-20">
								<li><a class="btn b-none inline-popups" href="#`+v.session+`" data-effect="mfp-zoom-out"><i class="mdi mdi-server-network mdi-24px"></i></a></li>
								<li><a class="btn b-none metadata-get" data-source="`+source+`" data-key="`+v.metadataKey+`" data-uid="`+v.uid+`"><i class="mdi mdi-information mdi-24px"></i></a></li>
								<li><a class="btn b-none openTab" data-tab-name="`+v.tabName+`" data-type="`+v.type+`" data-open-tab="`+v.openTab+`" data-url="`+v.address+`" href="javascript:void(0);"><i class=" mdi mdi-`+source+` mdi-24px"></i></a></li>
								<li><a class="btn b-none refreshImage" data-type="nowPlaying" data-image="`+v.nowPlayingOriginalImage+`" href="javascript:void(0);"><i class="mdi mdi-refresh mdi-24px"></i></a></li>
								<a class="inline-popups `+v.uid+` hidden" href="#`+v.uid+`-metadata-div" data-effect="mfp-zoom-out"></a>
							</ul>
						</div>
					</div>
					<div class="el-card-content">
						<div class="progress">
							<div class="progress-bar progress-bar-info" style="width: `+v.watched+`%;" role="progressbar"><span class="hidden">`+v.watched+`%</span></div>
							<div class="progress-bar progress-bar-inverse" style="width: `+v.transcoded+`%;" role="progressbar"></div>
						</div>
						<h3 class="box-title pull-left p-l-10 elip" style="width:90%">`+v.nowPlayingTitle+`</h3>
						<h3 class="box-title pull-right vertical-middle" style="width:10%"><i class="icon-control-`+v.state+` fa-fw text-info" style=""></i></h3>
						<div class="clearfix"></div>
						<small class="pull-left p-l-10"><i class="`+icon+` fa-fw text-info"></i>`+v.nowPlayingBottom+`</small>
						<small class="pull-right p-r-10">`+v.user+` <i class="icon-user"></i></small>
						<br>
					</div>
				</div>
			</div>
		</div>
		<div id="`+v.session+`" class="white-popup mfp-with-anim mfp-hide">
			<div class="col-md-6 col-md-offset-3">
				<div class="white-box m-b-0 bg-info">
					<h3 class="text-white box-title m-b-0">`+v.sessionType+`<span class="pull-right"><i class="mdi mdi-network-upload"></i> `+v.bandwidth+` kbps <button type="button" class="btn bg-org btn-circle close-popup m-l-10"><i class="fa fa-times"></i> </button></span></h3>
				</div>
				<div class="white-box">
					<div class="row">
						<div class="p-l-20 p-r-20">
							<div class="pull-left">
								<span class="text-uppercase"><i class="mdi mdi-`+v.bandwidthType+`"></i> `+v.bandwidthType+`</span>
								<span class="text-uppercase"><i class="mdi mdi-account-network"></i> `+v.userAddress+`</span>
								`+streamInfo+`
								<div class="text-muted m-t-20 text-uppercase"><span class="text-uppercase"><i class="mdi mdi-`+source+`"></i> Product: `+v.userStream.product+`</span></div>
								<div class="text-muted m-t-20 text-uppercase"><span class="text-uppercase"><i class="mdi mdi-laptop-mac"></i> Device: `+v.userStream.device+`</span></div>
							</div>
							<div data-label="`+v.watched+`%" class="css-bar css-bar-`+Math.ceil(v.watched/5)*5+` css-bar-lg m-b-0  css-bar-info pull-right">`+userThumb+`</div>
						</div>
					</div>
				</div>
            </div>
		</div>
		<div id="`+v.uid+`-metadata-div" class="white-popup mfp-with-anim mfp-hide">
	        <div class="col-md-8 col-md-offset-2 `+v.uid+`-metadata-info"></div>
	    </div>
		`;

	});
	cards += '</div><!--end-->';
	return cards;
}
function buildRecentItem(array, type, extra=null){
	var items = '';
	$.each(array, function(i,v) {
		if(extra == null){
			var className = '';
			var extraImg = '';
			switch (v.type) {
				case 'music':
					className = 'recent-cover recent-item recent-music';
					extraImg = '<img src="'+v.imageURL+'" class="imageSourceAlt imageSourceTop recent-cover"><img src="'+v.imageURL+'" class="imageSourceAlt imageSourceBottom recent-cover">';
					break;
				case 'movie':
					className = 'recent-poster recent-item recent-movie';
					break;
				case 'tv':
					className = 'recent-poster recent-item recent-tv';
					break;
				case 'video':
					className = 'recent-poster recent-item recent-video';
					break;
				default:

			}
			items += `
			<div class="item lazyload `+className+` metadata-get mouse imageSource" data-source="`+type+`" data-key="`+v.metadataKey+`" data-uid="`+v.uid+`" data-src="`+v.imageURL+`">
				`+extraImg+`
				<div class="hover-homepage-item">
				    <span class="elip request-title-movie">
					    <a class="text-white refreshImage" data-type="recent-item" data-image="`+v.originalImage+`" href="javascript:void(0);"><i class="mdi mdi-refresh mdi-24px"></i></a>
					</span>
				</div>
				<span class="elip recent-title">`+v.title+`<br/>`+v.secondaryTitle+`</span>
				<div id="`+v.uid+`-metadata-div" class="white-popup mfp-with-anim mfp-hide">
			        <div class="col-md-8 col-md-offset-2 `+v.uid+`-metadata-info"></div>
			    </div>
			</div>
			`;
		}else{
			items += `
			<a class="inline-popups `+v.uid+` hidden" href="#`+v.uid+`-metadata-div" data-effect="mfp-zoom-out"></a>
			`;
		}

	});
	return items;
}
function buildPlaylistItem(array, type, extra=null){
	var items = '';
	$.each(array, function(i,v) {
		if(i !== 'title'){
			if(extra == null){
				items += `
				<div class="item lazyload recent-poster metadata-get mouse imageSource" data-source="`+type+`" data-key="`+v.metadataKey+`" data-uid="`+v.uid+`" data-src="`+v.imageURL+`">
					<div class="hover-homepage-item">
					    <span class="elip request-title-movie">
						    <a class="text-white refreshImage" data-type="recent-item" data-image="`+v.originalImage+`" href="javascript:void(0);"><i class="mdi mdi-refresh mdi-24px"></i></a>
						</span>
					</div>
					<span class="elip recent-title">`+v.title+`</span>
					<div id="`+v.uid+`-metadata-div" class="white-popup mfp-with-anim mfp-hide">
				        <div class="col-md-8 col-md-offset-2 `+v.uid+`-metadata-info"></div>
				    </div>
				</div>
				`;
			}else{
				items += `
				<a class="inline-popups `+v.uid+` hidden" href="#`+v.uid+`-metadata-div" data-effect="mfp-zoom-out"></a>
				`;
			}
		}
	});
	return items;
}
function buildRequestAdminMenuItem(value,category,id,type){
	var action = '';
	var text = '';
	var extra = '';
	switch (category) {
		case 'approved':
			if(value){
				//nada
			}else{
				action = 'approve';
				text = 'Approve';
				extra = `<li><a class="mouse" onclick="ombiActions('`+id+`', 'deny', '`+type+`');" lang="en">Deny</a></li>`;
			}
			break;
		case 'available':
			if(value){
				action = 'unavailable';
				text = 'Mark as Unavailable';
			}else{
				action = 'available';
				text = 'Mark as Available';
			}
			break;
		default:

	}
	return (action) ? `<li><a class="mouse" onclick="ombiActions('`+id+`', '`+action+`', '`+type+`');" lang="en">`+text+`</a></li>`+extra : '';
}
function buildRequestItem(array, extra=null){
	var items = '';
	$.each(array, function(i,v) {
			if(extra == null){
                var approveID = (v.type == 'tv') ? v.id : v.request_id;
                var iconType = (v.type == 'tv') ? 'fa-tv ' : 'fa-film';
				var badge = '';
				var badge2 = '';
				var bg = (v.background.includes('.')) ? v.background : 'plugins/images/cache/no-np.png';
				v.user = (activeInfo.settings.homepage.ombi.alias) ? v.userAlias : v.user;
				//Set Status
				var status = (v.approved) ? '<span class="badge bg-org m-r-10" lang="en">Approved</span>' : '<span class="badge bg-danger m-r-10" lang="en">Unapproved</span>';
				status += (v.available) ? '<span class="badge bg-org m-r-10" lang="en">Available</span>' : '<span class="badge bg-danger m-r-10" lang="en">Unavailable</span>';
				status += (v.denied) ? '<span class="badge bg-danger m-r-10" lang="en">Denied</span>' : '';
				//Set Class
				var className = (v.approved) ? 'request-approved' : 'request-unapproved';
				className += (v.available) ? ' request-available' : ' request-unavailable';
				className += (v.denied) ? ' request-denied' : ' request-notdenied';
				//Set badge
				badge = (v.approved) ? 'bg-info' : 'bg-warning';
				badge = (v.denied) ? 'bg-danger' : badge;
				badge2 = (v.available) ? 'bg-success' : 'bg-danger';
				//Is Admin?
				var adminFunctions = `<div class="btn-group m-r-10">
                    <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-outline dropdown-toggle waves-effect waves-light" type="button"> <i class="fa fa-ellipsis-v m-r-5"></i> <span class="caret"></span></button>
                    <ul role="menu" class="dropdown-menu">
						<li><h5 class="text-center" lang="en">Request Options</h5></li>
						<li class="divider"></li>
						`+buildRequestAdminMenuItem(v.approved, 'approved',approveID,v.type)+`
						`+buildRequestAdminMenuItem(v.available, 'available',approveID,v.type)+`
						<li><a class="mouse" onclick="ombiActions('`+v.request_id+`', 'delete', '`+v.type+`');" lang="en">Delete</a></li>
                    </ul>
                </div>`;
				adminFunctions = (activeInfo.user.groupID <= 1) ? adminFunctions : '';
				var user = (activeInfo.user.groupID <= 1) ? '<span lang="en">Requested By:</span> '+v.user : '';
				var user2 = (activeInfo.user.groupID <= 1) ? '<br>'+v.user : '';
				items += `
				<div class="item lazyload recent-poster request-item request-`+v.type+` `+className+` mouse" data-target="request-`+v.id+`" data-src="`+v.poster+`">
					<div class="outside-request-div">
						<div class="inside-over-request-div `+badge2+`"></div>
						<div class="inside-request-div `+badge+`"></div>
					</div>
					<div class="hover-homepage-item"></div>
					<span class="elip request-title-`+v.type+`"><i class="fa `+iconType+`"></i></span>
					<span class="elip recent-title">`+v.title+user2+`</span>
					<div id="request-`+v.id+`" class="white-popup mfp-with-anim mfp-hide">
						<div class="col-md-8 col-md-offset-2">
							<div class="white-box m-b-0">
								<div class="user-bg lazyload" data-src="`+bg+`">
									<div class="col-xs-2 p-10">`+adminFunctions+`</div>
									<div class="col-xs-10">
										<h2 class="m-b-0 font-medium pull-right text-right">
											`+v.title+`<button type="button" class="btn bg-org btn-circle close-popup m-l-10"><i class="fa fa-times"></i> </button><br>
											<small class="m-t-0 text-white">`+user+`</small><br>
											`+buildYoutubeLink(v.title+' '+v.type)+`
										</h2>
									</div>
									<div class="genre-list p-10">`+status+`</div>
								</div>
							</div>
							<div class="panel panel-info p-b-0 p-t-0">
								<div class="panel-body p-b-0 p-t-0 m-b-0">
									<div class="p-20 text-center">
										<p class="">`+v.overview+`</p>
									</div>
									<div class="row">
										<div class="col-lg-12">
											<div class="owl-carousel owl-theme metadata-actors p-b-10"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				`;
			}else{
				items += `
				<a class="inline-popups hidden" id="link-request-`+v.id+`" href="#request-`+v.id+`" data-effect="mfp-zoom-out" ></a>
				`;
			}
	});
	return ((items !== '') || extra == null) ? items : '<h2 class="text-center">No items</h2>';
}
function buildStream(array, type){
	var streams = (typeof array.content !== 'undefined') ? array.content.length : false;
	return (streams) ? `
	<div id="`+type+`Streams">
		<div class="el-element-overlay row">
		    <div class="col-md-12">
		        <h4 class="pull-left"><span lang="en">Active</span> `+toUpper(type)+` <span lang="en">Streams</span>: </h4><h4 class="pull-left">&nbsp;<span class="label label-info m-l-20 checkbox-circle mouse" onclick="homepageStream('`+type+`')">`+streams+`</span></h4>
		        <hr class="hidden-xs">
		    </div>
			<div class="clearfix"></div>
		    <!-- .cards -->
			`+buildStreamItem(array.content, type)+`
		    <!-- /.cards-->
		</div>
	</div>
	<div class="clearfix"></div>
	` : '';
}
function buildRecent(array, type){
	var recent = (typeof array.content !== 'undefined') ? true : false;
    array.content = (recent) ? Object.values(array.content) : false;
    var movie = (recent) ? (array.content.filter(p => p.type == "movie").length > 0 ? true : false) : false;
	var tv = (recent) ? (array.content.filter(p => p.type == "tv").length > 0 ? true : false) : false;
	var video = (recent) ? (array.content.filter(p => p.type == "video").length > 0 ? true : false) : false;
	var music = (recent) ? (array.content.filter(p => p.type == "music").length > 0 ? true : false) : false;
	var dropdown = '';
	var header = '';
	var headerAlt = '';
	dropdown += (recent && movie) ? `<li><a data-filter="recent-movie" server-filter="`+type+`" href="javascript:void(0);">Movies</a></li>` : '';
	dropdown += (recent && tv) ? `<li><a data-filter="recent-tv" server-filter="`+type+`" href="javascript:void(0);">Shows</a></li>` : '';
	dropdown += (recent && video) ? `<li><a data-filter="recent-video" server-filter="`+type+`" href="javascript:void(0);">Videos</a></li>` : '';
	dropdown += (recent && music) ? `<li><a data-filter="recent-music" server-filter="`+type+`" href="javascript:void(0);">Music</a></li>` : '';
	var dropdownMenu = `
	<div class="btn-group pull-right">
		<button type="button" class="btn btn-info waves-effect hidden-xs" onclick="owlChange('`+type+`-recent','previous');"><i class="fa fa-chevron-left"></i></button>
		<button type="button" class="btn btn-info waves-effect hidden-xs" onclick="owlChange('`+type+`-recent','next');"><i class="fa fa-chevron-right"></i></button>
		<button aria-expanded="false" data-toggle="dropdown" class="btn btn-info dropdown-toggle waves-effect waves-light" type="button">
			<i class="fa fa-filter m-r-5"></i><span class="caret"></span>
		</button>
		<ul role="menu" class="dropdown-menu recent-filter">
			<li><a data-filter="all" server-filter="`+type+`" href="javascript:void(0);">All</a></li>
			<li class="divider"></li>
			`+dropdown+`
		</ul>
	</div>`;
	if(activeInfo.settings.homepage.options.alternateHomepageHeaders){
		var headerAlt = `
		<div class="col-md-12">
			<h4 class="pull-left"><span class="mouse" onclick="homepageRecent('`+type+`')" lang="en">Recently Added</span></h4>
			`+dropdownMenu+`
			<hr class="hidden-xs"><div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
		`;
	}else{
		var header = `
		<div class="panel-heading bg-info p-t-10 p-b-10">
			<span onclick="homepageRecent('`+type+`')" class="pull-left m-t-5 mouse"><img class="lazyload homepageImageTitle" data-src="plugins/images/tabs/`+type+`.png"> &nbsp; <span lang="en">Recently Added</span></span>
			`+dropdownMenu+`
			<div class="clearfix"></div>
		</div>
		`;
	}
	return (recent) ? `
	<div id="`+type+`Recent" class="row">
		`+headerAlt+`
        <div class="col-lg-12">
            <div class="panel panel-default">
				`+header+`
                <div class="panel-wrapper p-b-0 collapse in">
					<div class="`+type+`-recent-hidden hidden"></div>
                    <div class="owl-carousel owl-theme recent-items `+type+`-recent">
						`+buildRecentItem(array.content, type)+`
                    </div>
					`+buildRecentItem(array.content, type, true)+`
                </div>
            </div>
        </div>
    </div>
	` : '';
}
function owlChange(elm,action){
	switch (action){
		case 'next':
            $('.'+elm).trigger('next.owl');
			break;
		case 'previous':
            $('.'+elm).trigger('prev.owl');
			break;
		default:
			return false;
	}
	return false;
}
function cleanPlaylistTitle(string){
	var test = string.split('.');
	if(test.length > 1){
		if(!isNaN(test[0])){
			return test[1];
		}
	}
	return string;
}
function buildPlaylist(array, type){
	var playlist = (typeof array.content !== 'undefined') ? true : false;
	var dropdown = '';
	var first = '';
    var firstButton = '';
	var hidden = '';
	var count = 0;
	var items = '';
	var header = '';
	var headerAlt = '';
	if(playlist){
		$.each(array.content, function(i,v) {
			v.title = cleanPlaylistTitle(v.title);
			count ++;
			first = (count == 1) ? v.title : first;
            firstButton = (count == 1) ? i+'-playlist' : firstButton;
			hidden = (count == 1) ? '' : ' owl-hidden hidden';
			dropdown += `<li><a data-filter="`+i+`" server-filter="`+type+`" data-title="`+encodeURI(v.title)+`" href="javascript:void(0);">`+v.title+`</a></li>`;

			items += `
			<div class="owl-carousel owl-theme playlist-items `+type+`-playlist `+hidden+` `+i+`-playlist">
				`+buildPlaylistItem(v, type)+`
			</div>
			`+buildPlaylistItem(v, type, true)+`
			`;
		});
		var builtDropdown = `
		<button type="button" class="btn btn-info waves-effect hidden-xs playlist-previous" onclick="owlChange('`+firstButton+`','previous');"><i class="fa fa-chevron-left"></i></button>
		<button type="button" class="btn btn-info waves-effect hidden-xs playlist-next" onclick="owlChange('`+firstButton+`','next');"><i class="fa fa-chevron-right"></i></button>
		<button aria-expanded="false" data-toggle="dropdown" class="btn btn-info dropdown-toggle waves-effect waves-light" type="button">
			<i class="mdi mdi-playlist-play m-r-5 fa-lg"></i><span class="caret"></span>
		</button>
		<ul role="menu" class="dropdown-menu playlist-filter">
			`+dropdown+`
		</ul>
		`;
	}
	if(activeInfo.settings.homepage.options.alternateHomepageHeaders){
		var headerAlt = `
		<div class="col-md-12">
			<h4 class="pull-left"><span onclick="homepagePlaylist('`+type+`')" class="`+type+`-playlistTitle mouse">`+first+`</span></h4>
			<div class="btn-group pull-right">
				`+builtDropdown+`
			</div>
			<hr class="hidden-xs"><div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
		`;
	}else{
		var header = `
		<div class="panel-heading bg-info p-t-10 p-b-10">
			<span class="pull-left m-t-5 mouse" onclick="homepagePlaylist('`+type+`')"><img class="lazyload homepageImageTitle" data-src="plugins/images/tabs/`+type+`.png"> &nbsp; <span class="`+type+`-playlistTitle">`+first+`</span></span>
			<div class="btn-group pull-right">
					`+builtDropdown+`
			</div>
			<div class="clearfix"></div>
		</div>
		`;
	}
	return (playlist) ? `
	<div id="`+type+`Playlist" class="row">
		`+headerAlt+`
        <div class="col-lg-12">
            <div class="panel panel-default">
                `+header+`
                <div class="panel-wrapper p-b-0 collapse in">
                    `+items+`
                </div>
            </div>
        </div>
    </div>
	` : '';
}
function buildRequest(array){
	var requests = (typeof array.content !== 'undefined') ? true : false;
	var dropdown = '';
	var headerAlt = '';
	var header = '';
	var ombiButton = (activeInfo.settings.homepage.ombi.enabled == true) ? `<button href="#new-request" id="newRequestButton" class="btn btn-info waves-effect waves-light inline-popups" data-effect="mfp-zoom-out"><i class="fa fa-search m-l-5"></i></button>` : '';
	if(requests){
		var builtDropdown = `
		<button type="button" class="btn btn-info waves-effect hidden-xs" onclick="owlChange('request-items','previous');"><i class="fa fa-chevron-left"></i></button>
		<button type="button" class="btn btn-info waves-effect hidden-xs" onclick="owlChange('request-items','next');"><i class="fa fa-chevron-right"></i></button>
		<button aria-expanded="false" data-toggle="dropdown" class="btn btn-info dropdown-toggle waves-effect waves-light" type="button">
			<i class="fa fa-filter m-r-5"></i><span class="caret"></span>
		</button>
		`+ombiButton+`
		<div role="menu" class="dropdown-menu request-filter">
			<div class="checkbox checkbox-success m-l-20 checkbox-circle">
				<input id="request-filter-available" data-filter="request-available" class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-available"> <span lang="en">Available</span> </label>
			</div>
			<div class="checkbox checkbox-danger m-l-20 checkbox-circle">
				<input id="request-filter-unavailable" data-filter="request-unavailable"  class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-unavailable"> <span lang="en">Unavailable</span> </label>
			</div>
			<div class="checkbox checkbox-info m-l-20 checkbox-circle">
				<input id="request-filter-approved" data-filter="request-approved" class="filter-request-input" type="checkbox"  checked="">
				<label for="request-filter-approved"> <span lang="en">Approved</span> </label>
			</div>
			<div class="checkbox checkbox-warning m-l-20 checkbox-circle">
				<input id="request-filter-unapproved" data-filter="request-unapproved" class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-unapproved"> <span lang="en">Unapproved</span> </label>
			</div>
			<div class="checkbox checkbox-purple m-l-20 checkbox-circle">
				<input id="request-filter-denied" data-filter="request-denied" class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-denied"> <span lang="en">Denied</span> </label>
			</div>
			<div class="checkbox checkbox-inverse m-l-20 checkbox-circle">
				<input id="request-filter-movie" data-filter="request-movie" class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-movie"> <span lang="en">Movie</span> </label>
			</div>
			<div class="checkbox checkbox-inverse m-l-20 checkbox-circle">
				<input id="request-filter-tv" data-filter="request-tv" class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-tv"> <span lang="en">TV</span> </label>
			</div>
		</div>

		`;
	}
	if(activeInfo.settings.homepage.options.alternateHomepageHeaders){
		var headerAlt = `
		<div class="col-md-12">
			<h4 class="pull-left"><span class="mouse" onclick="homepageRequests()" lang="en">Requests</span></h4>
			<div class="btn-group pull-right">
				`+builtDropdown+`
			</div>
			<hr class="hidden-xs"><div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
		`;
	}else{
		var header = `
		<div class="panel-heading bg-info p-t-10 p-b-10">
			<span class="pull-left m-t-5 mouse" onclick="homepageRequests()"><img class="lazyload homepageImageTitle" data-src="plugins/images/tabs/ombi.png"> &nbsp; Requests</span>
			<div class="btn-group pull-right">
					`+builtDropdown+`
			</div>
			<div class="clearfix"></div>
		</div>
		`;
	}
	return (requests) ? `
	<div id="ombi-requests" class="row">
		`+headerAlt+`
        <div class="col-lg-12">
            <div class="panel panel-default">
				`+header+`
                <div class="panel-wrapper p-b-0 collapse in">
				<div class="owl-carousel owl-theme request-items">
					`+buildRequestItem(array.content)+`
				</div>
				`+buildRequestItem(array.content, true)+`
                </div>
            </div>
        </div>
    </div>
	<div id="new-request" class="white-popup mfp-with-anim mfp-hide">
		<div class="col-md-8 col-md-offset-2">
			<div class="white-box m-b-0 search-div resultBox-outside">
				<div class="form-group m-b-0">
					<div id="request-input-div" class="input-group">
						<input id="request-input" lang="en" placeholder="Request Show or Movie" type="text" class="form-control inline-focus">
                        <input id="request-page" type="hidden" class="form-control">
                        <div class="input-group-btn">
                            <button type="button" class="btn waves-effect waves-light btn-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span lang="en">Suggestions</span> <span class="caret"></span></button>
                            <ul class="dropdown-menu dropdown-menu-right">
								<li><a onclick="requestList('org-mod', 'movie');" href="javascript:void(0)" lang="en">Organizr Mod Picks</a></li>
								<li><a onclick="requestList('theatre-movie', 'movie');" href="javascript:void(0)" lang="en">In Theatres</a></li>
								<li><a onclick="requestList('top-movie', 'movie');" href="javascript:void(0)" lang="en">Top Movies</a></li>
								<li><a onclick="requestList('pop-movie', 'movie');" href="javascript:void(0)" lang="en">Popular Movies</a></li>
								<li><a onclick="requestList('up-movie', 'movie');" href="javascript:void(0)" lang="en">Upcoming Movies</a></li>
								<li><a onclick="requestList('top-tv', 'tv');" href="javascript:void(0)" lang="en">Top TV</a></li>
								<li><a onclick="requestList('pop-tv', 'tv');" href="javascript:void(0)" lang="en">Popular TV</a></li>
                                <li><a onclick="requestList('today-tv', 'tv');" href="javascript:void(0)" lang="en">Airs Today TV</a></li>
                            </ul>
                        </div>
                    </div>
					<div class="clearfix"></div>
				</div>
				<div id="request-results" class="row el-element-overlay resultBox-inside"></div>
			</div>
		</div>
	</div>
	` : '';
}
function pagination(c, m) {
    var current = c,
        last = m,
        delta = 2,
        left = current - delta,
        right = current + delta + 1,
        range = [],
        rangeWithDots = [],
        l;

    for (let i = 1; i <= last; i++) {
        if (i == 1 || i == last || i >= left && i < right) {
            range.push(i);
        }
    }

    for (let i of range) {
        if (l) {
            if (i - l === 2) {
                rangeWithDots.push(l + 1);
            } else if (i - l !== 1) {
                rangeWithDots.push('...');
            }
        }
        rangeWithDots.push(i);
        l = i;
    }

    return rangeWithDots;
}
function buildRequestResult(array,media_type=null,list=null,page=null,search=false){
	var comments = (typeof array.comments !== 'undefined') ? true : false;
	var comment = '';
	var results = ``;
	var buttons = ``;
	var next = ``;
	var tv = 0;
	var movie = 0;
	var total = 0;
	var totalPages = array.total_pages;
    var currentPage = (page * 1);
    var pagePrevious = ((page * 1) - 1);
    var pageNext = ((page * 1) + 1);
    var pageFirst = 1;
    var pageLast = totalPages;
    var previousHidden = (currentPage == 1) ? 'disabled' : '';
    var nextHidden = (currentPage == totalPages) ? 'disabled' : '';
    var pageList = '';
	if(array.results.length == 0){
		return '<h2 class="text-center" lang="en">No Results</h2>';
	}
	$.each(array.results, function(i,v) {
		media_type = (v.media_type) ? v.media_type : media_type;
		if(media_type == 'tv' || media_type == 'movie'){
			total = total + 1;
			tv = (media_type == 'tv') ? tv + 1 : tv;
			movie = (media_type == 'movie') ? movie + 1 : movie;
			var bg = (v.poster_path !== null) ? `https://image.tmdb.org/t/p/w300/`+v.poster_path : 'plugins/images/cache/no-list.png';
			var top = (v.title) ? v.title : (v.original_title) ? v.original_title : (v.original_name) ? v.original_name : '';
			var bottom = (v.release_date) ? v.release_date : (v.first_air_date) ? v.first_air_date : '';
			if(comments){
				if(array.comments[media_type+':'+v.id] !== null){
					comment = array.comments[media_type+':'+v.id];
				}
			}
			results += `
			<div class="col-lg-3 col-md-4 col-sm-6 col-xs-12 m-t-20 request-result-item request-result-`+media_type+`">
	            <div class="white-box m-b-10">
	                <div class="el-card-item p-b-0">
	                    <div class="el-card-avatar el-overlay-1 m-b-5 preloader-`+v.id+`"> <img class="lazyload resultImages" data-src="`+bg+`">
	                        <div class="el-overlay">
								<span class="text-info p-a-5 font-normal">`+comment+`</span>
	                            <ul class="el-info">
	                                <li><a class="btn default btn-outline" href="javascript:void(0);" onclick="processRequest('`+v.id+`','`+media_type+`');"><i class="icon-link"></i>&nbsp; <span lang="en">Request</span></a></li>
	                                <li><a class="btn default btn-outline" href="https://www.themoviedb.org/`+media_type+`/`+v.id+`" target="_blank"><i class="icon-info"></i></a></li>
	                            </ul>
	                        </div>
	                    </div>
	                    <div class="el-card-content bg-org">
	                        <h3 class="box-title elip">`+top+`</h3> <small>`+bottom+`</small>
	                        <br>
						</div>
	                </div>
	            </div>
	        </div>
			`;
		}
		comment = '';
	});
	if((list) && (page) && (search == false)){
        $.each(pagination(currentPage, totalPages), function(key,value) {
            var activePage = (currentPage == value) ? 'active' : '';
            var disabled = (value == '...') ? 'disabled' : '';
            var pageLink = (value == '...') ? '' : `onclick="requestList('`+list+`', '`+media_type+`', '`+value+`');"`;
            pageList += '<li class="'+activePage+disabled+'"> <a '+pageLink+' href="javascript:void(0)">'+value+'</a> </li>'
        });

		next = `
		<div class="clearfix"></div>
		<div class="button-box text-center p-b-0">
            <ul class="pagination m-b-0">
                <li class="`+previousHidden+`"> <a href="javascript:void(0)" onclick="requestList('`+list+`', '`+media_type+`', '`+pagePrevious+`');"><i class="fa fa-angle-left"></i></a> </li>
 
                `+pageList+`
                <li class="`+nextHidden+`"> <a href="javascript:void(0)" onclick="requestList('`+list+`', '`+media_type+`', '`+pageNext+`');"><i class="fa fa-angle-right"></i></a> </li>
            </ul>
        </div>
		`;
	}
	if((list) && (page) && (search == true)){
        $.each(pagination(currentPage, totalPages), function(key,value) {
            var activePage = (currentPage == value) ? 'active' : '';
            var disabled = (value == '...') ? 'disabled' : '';
            var pageLink = (value == '...') ? '' : `onclick="$('#request-page').val(`+value+`);doneTyping();"`;
            pageList += '<li class="'+activePage+disabled+'"> <a '+pageLink+' href="javascript:void(0)">'+value+'</a> </li>'
        });
        next = `
		<div class="clearfix"></div>
		<div class="button-box text-center p-b-0">
            <ul class="pagination m-b-0">
                <li class="`+previousHidden+`"> <a href="javascript:void(0)" onclick="$('#request-page').val(`+pagePrevious+`);doneTyping();"><i class="fa fa-angle-left"></i></a> </li>
                `+pageList+`
                <li class="`+nextHidden+`"> <a href="javascript:void(0)" onclick="$('#request-page').val(`+pageNext+`);doneTyping();"><i class="fa fa-angle-right"></i></a> </li>
            </ul>
        </div>
		`;
	}
	var buttons = `
	<div class="button-box p-20 text-center p-b-0">
		<button class="btn btn-inverse waves-effect waves-light filter-request-result" data-filter="request-result-all"><span>`+total+`</span> <i class="fa fa-th-large m-l-5 fa-fw"></i></button>
		<button class="btn btn-primary waves-effect waves-light filter-request-result" data-filter="request-result-movie"><span>`+movie+`</span> <i class="fa fa-film m-l-5 fa-fw"></i></button>
        <button class="btn btn-info waves-effect waves-light filter-request-result" data-filter="request-result-tv"><span>`+tv+`</span> <i class="fa fa-tv m-l-5 fa-fw"></i></button>
    </div>
	`;
	return buttons+next+results+next;
}
function processRequest(id,type){
	if(type == 'tv'){
		requestNewID(id).success(function(data) {
			var newID = data.tvdb_id;
			ombiActions(newID,'add',type);
		}).fail(function(xhr) {
			console.error("Organizr Function: TMDB Connection Failed");
		});
	}else{
		ombiActions(id,'add',type);
	}
}
//Ombi actions
function ombiActions(id,action,type){
	//console.log(id,action,type);
	var msg = (activeInfo.user.groupID <= 1) ? '<a href="https://github.com/tidusjar/Ombi/issues/2176" target="_blank">Not Org Fault - Ask Ombi</a>' : 'Connection Error to Request Server';
	ajaxloader('.preloader-'+id,'in');
    ajaxloader('.mfp-content .white-popup .col-md-8 .white-box .user-bg','in');
	organizrAPI('POST','api/?v1/ombi',{id:id, action:action, type:type}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		//console.log(response.data);
		if(response.data !== false){
            if(action == 'delete'){
                homepageRequests();
                $.magnificPopup.close();
                message(window.lang.translate('Deleted Request Item'),'',activeInfo.settings.notifications.position,"#FFF",'success',"3500");
                return true;
            }
            try {
                var responseData = JSON.parse(response.data.bd);
            }catch(e) {
                console.log(e + ' error: ' + response.data.bd);
                orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(response.data.bd));
                return false;
            }
            console.log(responseData);
            var responseMessage = (responseData.isError == true) ? responseData.errorMessage : 'Success';
            var responseType = (responseData.isError == true) ? 'error' : 'success';
			homepageRequests();
			if(action !== 'add'){
				$.magnificPopup.close();
				message(window.lang.translate('Updated Request Item'),responseMessage,activeInfo.settings.notifications.position,"#FFF",responseType,"3500");
			}else{
				ajaxloader();
				message(window.lang.translate('Added Request Item'),responseMessage,activeInfo.settings.notifications.position,"#FFF",responseType,"3500");
			}
		}else{
			ajaxloader();
			message("",msg,activeInfo.settings.notifications.position,"#FFF","error","3500");
		}
	}).fail(function(xhr) {
		ajaxloader();
		console.error("Organizr Function: API Connection Failed");
	});
}
function doneTyping () {
	var page = ($('#request-page').val()) ? $('#request-page').val() : 1;
	if(typeof searchTerm !== 'undefined'){
		if(searchTerm !== $('#request-input').val()){
			page = 1;
		}
	}
	ajaxloader('.search-div', 'in');
	var title = $('#request-input').val();
	searchTerm = title;
	$('#request-page').val(page);
	requestSearch(title, page).success(function(data) {
		$('#request-results').html(buildRequestResult(data,'',title,page,true));
        if(bowser.mobile !== true){
            $('.resultBox-inside').slimScroll({
                height: '100%',
                position: 'right',
                size: "5px",
                color: '#dcdcdc'
            });
        }
		$('.mfp-wrap').animate({
			scrollTop:  '0'
		}, 500);
		ajaxloader();
	}).fail(function(xhr) {
		console.error("Organizr Function: TMDB Connection Failed");
		ajaxloader();
	});
}
function requestList (list, type, page=1) {
	ajaxloader('.search-div', 'in');
	requestSearchList(list,page).success(function(data) {
		if(typeof data.results !== 'undefined'){
			var results = data.results;
		}else if(typeof data.items !== 'undefined'){
			var results = data.items;
		}
		$('#request-results').html(buildRequestResult(data, type, list, page));
        if(bowser.mobile !== true){
            $('.resultBox-inside').slimScroll({
                height: '100%',
                position: 'right',
                size: "5px",
                color: '#dcdcdc'
            });
        }
		$('.mfp-wrap').animate({
			scrollTop: '0'
		}, 500);
		ajaxloader();
	}).fail(function(xhr) {
		console.error("Organizr Function: TMDB Connection Failed");
		ajaxloader();
	});
}
function buildDownloaderItem(array, source, type='none'){
    //console.log(array);
    var queue = '';
    var history = '';
    var count = 0;
	switch (source) {
        case 'jdownloader':
            if(array.content === false){
                queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
                break;
            }

            /*
            if(array.content.$status[0] != 'RUNNING'){
                var state = `<a href="#"><span class="downloader mouse" data-source="jdownloader" data-action="resume" data-target="main"><i class="fa fa-play"></i></span></a>`;
                var active = 'grayscale';
            }else{
                var state = `<a href="#"><span class="downloader mouse" data-source="jdownloader" data-action="pause" data-target="main"><i class="fa fa-pause"></i></span></a>`;
                var active = '';
            }
            $('.jdownloader-downloader-action').html(state);
            */

            if(array.content.queueItems.length == 0){
                queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
            }
            $.each(array.content.queueItems, function(i,v) {
                count = count + 1;
                if(v.speed == null){
                    if(v.percentage == '100'){
                        v.speed = '--';
                    }else{
                        v.speed = 'Stopped';
                    }
                }
                if(v.eta == null){
                    if(v.percentage == '100'){
                        v.eta = 'Complete';
                    }else{
                        v.eta = '--';
                    }
                }
                queue += `
                <tr>
                    <td class="max-texts">`+v.name+`</td>
                    <td class="hidden-xs">`+v.speed+`</td>
                    <td class="hidden-xs" alt="`+v.done+`">`+v.size+`</td>
                    <td class="hidden-xs">`+v.eta+`</td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: `+v.percentage+`%;" role="progressbar">`+v.percentage+`%</div>
                        </div>
                    </td>
                </tr>
                `;
            });
            if(array.content.grabberItems.length == 0){
                history = '<tr><td class="max-texts" lang="en">Nothing in Linkgrabbber</td></tr>';
            }
            $.each(array.content.grabberItems, function(i,v) {
                history += `
                <tr>
                    <td class="max-texts">`+ v.name+`</td>
                </tr>
                `;
            });
            break;
		case 'sabnzbd':
            if(array.content === false){
                queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
                break;
            }
            if(array.content.queueItems.queue.paused){
                var state = `<a href="#"><span class="downloader mouse" data-source="sabnzbd" data-action="resume" data-target="main"><i class="fa fa-play"></i></span></a>`;
                var active = 'grayscale';
            }else{
                var state = `<a href="#"><span class="downloader mouse" data-source="sabnzbd" data-action="pause" data-target="main"><i class="fa fa-pause"></i></span></a>`;
                var active = '';
            }
            $('.sabnzbd-downloader-action').html(state);

            if(array.content.queueItems.queue.slots.length == 0){
                queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
            }
            $.each(array.content.queueItems.queue.slots, function(i,v) {
                count = count + 1;
                var action = (v.status == "Downloading") ? 'pause' : 'resume';
                var actionIcon = (v.status == "Downloading") ? 'pause' : 'play';
                queue += `
                <tr>
                    <td class="max-texts">`+v.filename+`</td>
                    <td class="hidden-xs">`+v.status+`</td>
                    <td class="downloader mouse" data-target="`+v.nzo_id+`" data-source="sabnzbd" data-action="`+action+`"><i class="fa fa-`+actionIcon+`"></i></td>
                    <td class="hidden-xs"><span class="label label-info">`+v.cat+`</span></td>
                    <td class="hidden-xs">`+v.size+`</td>
                    <td class="hidden-xs" alt="`+v.eta+`">`+v.timeleft+`</td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: `+v.percentage+`%;" role="progressbar">`+v.percentage+`%</div>
                        </div>
                    </td>
                </tr>
                `;
            });
            if(array.content.historyItems.history.slots.length == 0){
                history = '<tr><td class="max-texts" lang="en">Nothing in history</td></tr>';
            }
            $.each(array.content.historyItems.history.slots, function(i,v) {
                history += `
                <tr>
                    <td class="max-texts">`+v.name+`</td>
                    <td class="hidden-xs">`+v.status+`</td>
                    <td class="hidden-xs"><span class="label label-info">`+v.category+`</span></td>
                    <td class="hidden-xs">`+v.size+`</td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: 100%;" role="progressbar">100%</div>
                        </div>
                    </td>
                </tr>
                `;
            });
			break;
		case 'nzbget':
            if(array.content === false){
                queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
                break;
            }
            if(array.content.queueItems.result.length == 0){
                queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
            }
            $.each(array.content.queueItems.result, function(i,v) {
                count = count + 1;
                var action = (v.Status == "Downloading") ? 'pause' : 'resume';
                var actionIcon = (v.Status == "Downloading") ? 'pause' : 'play';
                var percent = Math.floor((v.FileSizeMB - v.RemainingSizeMB) * 100 / v.FileSizeMB);
                var size = v.FileSizeMB * 1000000;
                v.Category = (v.Category !== '') ? v.Category : 'Not Set';
                queue += `
                <tr>
                    <td class="max-texts">`+v.NZBName+`</td>
                    <td class="hidden-xs">`+v.Status+`</td>
                    <!--<td class="downloader mouse" data-target="`+v.NZBID+`" data-source="sabnzbd" data-action="`+action+`"><i class="fa fa-`+actionIcon+`"></i></td>-->
                    <td class="hidden-xs"><span class="label label-info">`+v.Category+`</span></td>
                    <td class="hidden-xs">`+humanFileSize(size,true)+`</td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: `+percent+`%;" role="progressbar">`+percent+`%</div>
                        </div>
                    </td>
                </tr>
                `;
            });
            if(array.content.historyItems.result.length == 0){
                history = '<tr><td class="max-texts" lang="en">Nothing in history</td></tr>';
            }
            $.each(array.content.historyItems.result, function(i,v) {
                v.Category = (v.Category !== '') ? v.Category : 'Not Set';
                var size = v.FileSizeMB * 1000000;
                history += `
                <tr>
                    <td class="max-texts">`+v.NZBName+`</td>
                    <td class="hidden-xs">`+v.Status+`</td>
                    <td class="hidden-xs"><span class="label label-info">`+v.Category+`</span></td>
                    <td class="hidden-xs">`+humanFileSize(size,true)+`</td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: 100%;" role="progressbar">100%</div>
                        </div>
                    </td>
                </tr>
                `;
            });
			break;
		case 'transmission':
            if(array.content === false){
                queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
                break;
            }
            if(array.content.queueItems.arguments.torrents == 0){
                queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
            }
            $.each(array.content.queueItems.arguments.torrents, function(i,v) {
                count = count + 1;
                switch (v.status) {
                    case 7:
                    case '7':
                        var status = 'No Peers';
                        break;
                    case 6:
                    case '6':
                        var status = 'Seeding';
                        break;
                    case 5:
                    case '5':
                        var status = 'Seeding Queued';
                        break;
                    case 4:
                    case '4':
                        var status = 'Downloading';
                        break;
                    case 3:
                    case '3':
                        var status = 'Queued';
                        break;
                    case 2:
                    case '2':
                        var status = 'Checking Files';
                        break;
                    case 1:
                    case '1':
                        var status = 'File Check Queued';
                        break;
                    case 0:
                    case '0':
                        var status = 'Complete';
                        break;
                    default:
                        var status = 'Complete';
                }
                var percent = Math.floor(v.percentDone * 100);
                v.Category = (v.Category !== '') ? v.Category : 'Not Set';
                queue += `
                <tr>
                    <td class="max-texts">`+v.name+`</td>
                    <td class="hidden-xs">`+status+`</td>
                    <td class="hidden-xs">`+v.downloadDir+`</td>
                    <td class="hidden-xs">`+humanFileSize(v.totalSize,true)+`</td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: `+percent+`%;" role="progressbar">`+percent+`%</div>
                        </div>
                    </td>
                </tr>
                `;
            });
			break;
        case 'rTorrent':
            if(array.content === false){
                queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
                break;
            }
            if(array.content.queueItems == 0){
                queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
            }
            //console.log(array);
            $.each(array.content.queueItems, function(i,v) {
                count = count + 1;
                var percent = Math.floor((v.downloaded / v.size) * 100);
                var size = v.size != -1 ? humanFileSize(v.size,false) : "?";
                var upload = v.seed !== '' ? humanFileSize(v.seed,true) : "0 B";
                var download = v.leech !== '' ? humanFileSize(v.leech,true) : "0 B";
                var upTotal = v.upTotal !== '' ? humanFileSize(v.upTotal,false) : "0 B";
                var downTotal = v.downTotal !== '' ? humanFileSize(v.downTotal,false) : "0 B";
                var date = new Date(0);
                date.setUTCSeconds(v.date);
                date = moment(date).format('LLL');
                queue += `
                <tr>
                    <td class="max-texts"><span class="tooltip-info" data-toggle="tooltip" data-placement="right" title="" data-original-title="`+date+`">`+v.name+`</span></td>
                    <td class="hidden-xs">`+v.status+`</td>
                    <td class="hidden-xs"><span class="tooltip-info" data-toggle="tooltip" data-placement="right" title="" data-original-title="`+downTotal+`"><i class="fa fa-download"></i>&nbsp;`+download+`</span></td>
                    <td class="hidden-xs"><span class="tooltip-info" data-toggle="tooltip" data-placement="right" title="" data-original-title="`+upTotal+`"><i class="fa fa-upload"></i>&nbsp;`+upload+`</span></td>
                    <td class="hidden-xs">`+size+`</td>
                    <td class="hidden-xs"><span class="label label-info">`+v.label+`</span></td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: `+percent+`%;" role="progressbar">`+percent+`%</div>
                        </div>
                    </td>
                </tr>
                `;
            });
            break;
		case 'qBittorrent':
		    if(array.content === false){
                queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
                break;
            }
            if(array.content.queueItems.arguments.torrents == 0){
                queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
            }
            $.each(array.content.queueItems.arguments.torrents, function(i,v) {
                count = count + 1;
                switch (v.state) {
                    case 'stalledDL':
                        var status = 'No Peers';
                        break;
                    case 'metaDL':
                        var status = 'Getting Metadata';
                        break;
                    case 'uploading':
                        var status = 'Seeding';
                        break;
                    case 'queuedUP':
                        var status = 'Seeding Queued';
                        break;
                    case 'downloading':
                        var status = 'Downloading';
                        break;
                    case 'queuedDL':
                        var status = 'Queued';
                        break;
                    case 'checkingDL':
                    case 'checkingUP':
                        var status = 'Checking Files';
                        break;
                    case 'pausedDL':
                        var status = 'Paused';
                        break;
                    case 'pausedUP':
                        var status = 'Complete';
                        break;
                    default:
                        var status = 'Complete';
                }
                var percent = Math.floor(v.progress * 100);
                var size = v.total_size != -1 ? humanFileSize(v.total_size,true) : "?";
                queue += `
                <tr>
                    <td class="max-texts">`+v.name+`</td>
                    <td class="hidden-xs">`+status+`</td>
                    <td class="hidden-xs">`+v.save_path+`</td>
                    <td class="hidden-xs">`+size+`</td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: `+percent+`%;" role="progressbar">`+percent+`%</div>
                        </div>
                    </td>
                </tr>
                `;
            });
			break;
		case 'deluge':
            if(array.content === false){
                queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
                break;
            }
            if(array.content.queueItems.length == 0){
                queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
            }
            $.each(array.content.queueItems, function(i,v) {
                count = count + 1;
                var percent = Math.floor(v.progress);
                var size = v.total_size != -1 ? humanFileSize(v.total_size,true) : "?";
                var upload = v.upload_payload_rate != -1 ? humanFileSize(v.upload_payload_rate,true) : "?";
                var download = v.download_payload_rate != -1 ? humanFileSize(v.download_payload_rate,true) : "?";
                var action = (v.Status == "Downloading") ? 'pause' : 'resume';
                var actionIcon = (v.Status == "Downloading") ? 'pause' : 'play';
                queue += `
                <tr>
                    <td class="max-texts">`+v.name+`</td>
                    <td class="hidden-xs">`+v.state+`</td>
                    <td class="hidden-xs">`+size+`</td>
                    <td class="hidden-xs"><i class="fa fa-download"></i>&nbsp;`+download+`</td>
                    <td class="hidden-xs"><i class="fa fa-upload"></i>&nbsp;`+upload+`</td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: `+percent+`%;" role="progressbar">`+percent+`%</div>
                        </div>
                    </td>
                </tr>
                `;
            });
			break;
		default:
			return false;
	}
    if(queue !== ''){
        $('.'+source+'-queue').html(queue);
    }
    if(history !== ''){
        $('.'+source+'-history').html(history);
    }
    $('#count-'+source).html(count);
}
function buildDownloader(source){
    var queueButton = 'QUEUE';
    var historyButton = 'HISTORY';
    switch (source) {
        case 'jdownloader':
        case 'sabnzbd':
        case 'nzbget':
            var queue = true;
            var history = true;
            break;
        case 'transmission':
        case 'qBittorrent':
        case 'deluge':
        case 'rTorrent':
            var queue = true;
            var history = false;
            queueButton = 'REFRESH';
            break;
        default:
            var queue = false;
            var history = false;

    }
	var menu = `<ul class="nav customtab nav-tabs pull-right" role="tablist">`;
	var listing = '';
	var state = '';
	var active = '';
	var headerAlt = '';
	var header = '';
	//console.log(array);
	//console.log(queueItems);
	//console.log(historyItems);
	//console.log(downloader);
	if(queue){
		menu += `
			<li role="presentation" class="active" onclick="homepageDownloader('`+source+`')"><a href="#`+source+`-queue" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true"><span class="visible-xs"><i class="ti-download"></i></span><span class="hidden-xs">`+queueButton+`</span></a></li>
			`;
		listing += `
		<div role="tabpanel" class="tab-pane fade active in" id="`+source+`-queue">
			<div class="inbox-center table-responsive" data-simplebar>
				<table class="table table-hover">
					<tbody class="`+source+`-queue"></tbody>
				</table>
			</div>
			<div class="clearfix"></div>
		</div>
		`;
	}
	if(history){
		menu += `
		<li role="presentation" class=""><a href="#`+source+`-history" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-time"></i></span> <span class="hidden-xs">`+historyButton+`</span></a></li>
		`;
		listing += `
		<div role="tabpanel" class="tab-pane fade" id="`+source+`-history">
			<div class="inbox-center table-responsive" data-simplebar>
				<table class="table table-hover">
					<tbody class="`+source+`-history"></tbody>
				</table>
			</div>
			<div class="clearfix"></div>
		</div>
		`;
	}
	menu += '</ul>';
	if(activeInfo.settings.homepage.options.alternateHomepageHeaders){
		var headerAlt = `
		<div class="col-md-12">
			<h2 class="text-white m-0 pull-left text-uppercase"><img class="lazyload homepageImageTitle `+active+`" data-src="plugins/images/tabs/`+source+`.png">  &nbsp; `+state+`</h2>
			`+menu+`
			<hr class="hidden-xs"><div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
		`;
	}else{
		var header = `
		<div class="white-box bg-info m-b-0 p-b-0 p-t-10 mailbox-widget">
			<h2 class="text-white m-0 pull-left text-uppercase"><img class="lazyload homepageImageTitle `+active+`" data-src="plugins/images/tabs/`+source+`.png">  &nbsp; `+state+`</h2>
			`+menu+`
			<div class="clearfix"></div>
		</div>
		`;
	}
	return `
	<div class="row">
		`+headerAlt+`
		<div class="col-lg-12">
	        `+header+`
	        <div class="white-box p-0">
	            <div class="tab-content m-t-0">`+listing+`</div>
	        </div>
		</div>
	</div>
	`;
}
function buildDownloaderCombined(source){
    var first = ($('.combinedDownloadRow').length == 0) ? true : false;
    var active = (first) ? 'active' : '';
    var queueButton = 'QUEUE';
    var historyButton = 'HISTORY';
    switch (source) {
        case 'jdownloader':
        case 'sabnzbd':
        case 'nzbget':
            var queue = true;
            var history = true;
            break;
        case 'transmission':
        case 'qBittorrent':
        case 'deluge':
        case 'rTorrent':
            var queue = true;
            var history = false;
            queueButton = 'REFRESH';
            break;
        default:
            var queue = false;
            var history = false;

    }
    var mainMenu = `<ul class="nav customtab nav-tabs combinedMenuList" role="tablist">`;
    var addToMainMenu = `<li role="presentation" class="`+active+`"><a onclick="homepageDownloader('`+source+`')" href="#combined-`+source+`" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true"><span class=""><img src="./plugins/images/tabs/`+source+`.png" class="homepageImageTitle"><span class="badge bg-org downloaderCount" id="count-`+source+`"></span> </span></a></li>`;
    var listing = '';
    var headerAlt = '';
    var header = '';
    var menu = `<ul class="nav customtab nav-tabs m-t-5" role="tablist">`;
    if(queue){
        menu += `
			<li role="presentation" class="active" onclick="homepageDownloader('`+source+`')"><a href="#`+source+`-queue" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true"><span class="visible-xs"><i class="ti-download"></i></span><span class="hidden-xs">`+queueButton+`</span></a></li>
			`;
        listing += `
		<div role="tabpanel" class="tab-pane fade active in" id="`+source+`-queue">
			<div class="inbox-center table-responsive" data-simplebar>
				<table class="table table-hover">
					<tbody class="`+source+`-queue"></tbody>
				</table>
			</div>
			<div class="clearfix"></div>
		</div>
		`;
    }
    if(history){
        menu += `
		<li role="presentation" class=""><a href="#`+source+`-history" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-time"></i></span> <span class="hidden-xs">`+historyButton+`</span></a></li>
		`;
        listing += `
		<div role="tabpanel" class="tab-pane fade" id="`+source+`-history">
			<div class="inbox-center table-responsive" data-simplebar>
				<table class="table table-hover">
					<tbody class="`+source+`-history"></tbody>
				</table>
			</div>
			<div class="clearfix"></div>
		</div>
		`;
    }
    menu += '<li class="'+source+'-downloader-action"></li></ul><div class="clearfix"></div>';
    menu = ((queue) && (history)) ? menu : '';
    var listingMain = '<div role="tabpanel" class="tab-pane fade '+active+' in" id="combined-'+source+'">'+menu+'<div class="tab-content m-t-0 listingSingle">'+listing+'</div></div>';
    mainMenu += (first) ? addToMainMenu + '</ul>' : '';
    if(first){
        if(activeInfo.settings.homepage.options.alternateHomepageHeaders){
            var headerAlt = `
            <div class="col-md-12">
                `+mainMenu+`
                <div class="clearfix"></div>
            </div>
            <div class="clearfix"></div>
            `;
        }else{
            var header = `
            <div class="white-box bg-info m-b-0 p-b-0 p-10 mailbox-widget">
                `+mainMenu+`
                <div class="clearfix"></div>
            </div>
            `;
        }
        var built = `
        <div class="row combinedDownloadRow">
            `+headerAlt+`
            <div class="col-lg-12">
                `+header+`
                <div class="white-box p-0">
                    <div class="tab-content m-t-0 listingMain">`+listingMain+`</div>
                </div>
            </div>
        </div>
        `;
        $('#homepageOrderdownloader').html(built);
    }else{
        $(addToMainMenu).appendTo('.combinedMenuList');
        $(listingMain).appendTo('.listingMain');
    }
}
function buildMetadata(array, source){
	var metadata = '';
	var genres = '';
	var actors = '';
	var rating = '<div class="col-xs-2 p-10"></div>';
	$.each(array.content, function(i,v) {
		var hasActor = (typeof v.metadata.actors !== 'string') ? true : false;
		var hasGenre = (typeof v.metadata.genres !== 'string') ? true : false;
		if(hasActor){
			$.each(v.metadata.actors, function(i,v) {
				actors += '<div class="item lazyload recent-poster" data-src="'+(v.thumb.replace("http://", "https://"))+'" alt="'+v.name+'" ><span class="elip recent-title p-a-5">'+v.name+'<br><small class="font-light">'+v.role+'</small></span></div>';
			});
		}
		if(hasGenre){
			$.each(v.metadata.genres, function(i,v) {
				genres += '<span class="badge bg-org m-r-10">'+v+'</span>';
			});
		}
		if(v.metadata.rating){
			var ratingRound = Math.ceil(v.metadata.rating)*10;
			rating = `<div class="col-xs-2 p-10"><div data-label="`+v.metadata.rating *10+`%" class="css-bar css-bar-`+Math.ceil(ratingRound/5)*5+` css-bar-sm m-b-0  css-bar-info"><img src="plugins/images/rotten.png" class="nowPlayingUserThumb" alt="User"></div></div>`;
		}
		var seconds = v.metadata.duration / 1000 ; // or "2000"
        seconds = parseInt(seconds); //because moment js dont know to handle number in string format
		var format =  Math.floor(moment.duration(seconds,'seconds').asHours()) + ':' + moment.duration(seconds,'seconds').minutes() + ':' + moment.duration(seconds,'seconds').seconds();
		metadata = `
		<div class="white-box m-b-0">
			<div class="user-bg lazyload" data-src="`+v.nowPlayingImageURL+`">
				`+rating+`
				<div class="col-xs-10">
	                <h2 class="m-b-0 font-medium pull-right text-right">
						`+v.title+`<button type="button" class="btn bg-org btn-circle close-popup m-l-10"><i class="fa fa-times"></i> </button><br>
						<small class="m-t-0 text-white">`+v.metadata.tagline+`</small><br>
						<button class="btn waves-effect waves-light openTab bg-`+source+`" type="button" data-tab-name="`+cleanClass(v.tabName)+`" data-type="`+v.type+`" data-open-tab="`+v.openTab+`" data-url="`+v.address+`" href="javascript:void(0);"> <i class="fa mdi mdi-`+source+` fa-2x"></i> </button>
						`+buildYoutubeLink(v.title+' '+v.metadata.year+' '+v.type)+`
					</h2>
	            </div>
				<div class="genre-list p-10">`+genres+`</div>
			</div>
		</div>
		<div class="panel panel-info p-b-0 p-t-0">
            <div class="panel-body p-b-0 p-t-0 m-b-0">
				<div class="p-20 text-center">
					<p class="">`+v.metadata.summary+`</p>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="owl-carousel owl-theme metadata-actors p-b-10">`+actors+`</div>
					</div>
				</div>
            </div>
        </div>

		`;
	});
	return metadata;
}
function buildYoutubeLink(title){
	if(title){
		var str = createRandomString(10);
		return `
		<button class="btn btn-youtube waves-effect waves-light" type="button" onclick="youtubeCheck('`+escape(title)+`','`+str+`')"> <i class="fa fa-youtube-play fa-2x"></i> </button>
		<a class="hidden inline-popups `+str+`" href="#open-youtube" data-effect="mfp-zoom-out"></a>
		`;
	}
}
function buildCalendarMetadata(array){
	var metadata = '';
	var genres = '';
	var actors = '';
	var rating = '<div class="col-xs-2 p-10"></div>';
		var hasGenre = (typeof array.genres !== 'string') ? true : false;
		if(hasGenre){
			$.each(array.genres, function(i,v) {
				genres += '<span class="badge bg-org m-r-10">'+v+'</span>';
			});
		}
		if(array.ratings){
			var ratingRound = Math.ceil(array.ratings)*10;
			rating = `<div class="col-xs-2 p-10"><div data-label="`+array.ratings *10+`%" class="css-bar css-bar-`+Math.ceil(ratingRound/5)*5+` css-bar-sm m-b-0  css-bar-info"><img src="plugins/images/rotten.png" class="nowPlayingUserThumb" alt="User"></div></div>`;
		}
		var seconds = array.runtime / 1000 ; // or "2000"
    seconds = parseInt(seconds); //because moment js dont know to handle number in string format
		var format =  Math.floor(moment.duration(seconds,'seconds').asHours()) + ':' + moment.duration(seconds,'seconds').minutes() + ':' + moment.duration(seconds,'seconds').seconds();
		metadata = `
		<div class="white-box m-b-0">
			<div class="user-bg lazyload" data-src="`+array.image+`">
				`+rating+`
				<div class="col-xs-10">
	                <h2 class="m-b-0 font-medium pull-right text-right">
						`+array.topTitle+`<button type="button" class="btn bg-org btn-circle close-popup m-l-10"><i class="fa fa-times"></i> </button><br>
						<small class="m-t-0 text-white">`+array.bottomTitle+`</small><br>
						`+buildYoutubeLink(array.topTitle)+`
					</h2>
	            </div>
				<div class="genre-list p-10">`+genres+`</div>
			</div>
		</div>
		<div class="panel panel-info p-b-0 p-t-0">
            <div class="panel-body p-b-0 p-t-0 m-b-0">
				<div class="p-20 text-center">
					<p class="">`+array.overview+`</p>
				</div>
            </div>
        </div>

		`;
	return metadata;
}
function buildHealthChecks(array){
    if(array === false){ return ''; }
    var checks = (typeof array.content.checks !== 'undefined') ? array.content.checks.length : false;
    return (checks) ? `
	<div id="allHealthChecks">
		<div class="el-element-overlay row">
		    <div class="col-md-12">
		        <h4 class="pull-left"><span lang="en">Health Checks</span> : </h4><h4 class="pull-left">&nbsp;<span class="label label-info m-l-20 checkbox-circle good-health-checks mouse">`+checks+`</span></h4>
		        <hr class="hidden-xs">
		    </div>
			<div class="clearfix"></div>
		    <!-- .cards -->
		    <div class="healthCheckCards">
			    `+buildHealthChecksItem(array.content.checks)+`
			</div>
		    <!-- /.cards-->
		</div>
	</div>
	<div class="clearfix"></div>
	` : '';
}
function healthCheckIcon(tags){
    var allTags = tags.split(' ');
    var useIcon = '';
    $.each(allTags, function(i,v) {
        //check for image
        var file =  v.substring(v.lastIndexOf('.')+1, v.length).toLowerCase() || v.toLowerCase();
        switch (file) {
            case 'png':
            case 'jpg':
            case 'jpeg':
            case 'gif':
                useIcon = '<img class="lazyload loginTitle" data-src="'+v+'">&nbsp;';
                break;
            default:
        }
    });
    return useIcon;
}
function buildHealthChecksItem(array){
    var checks = '';
    $.each(array, function(i,v) {
        var hasIcon = healthCheckIcon(v.tags);
        v.name = (v.name) ? v.name : 'New Item';
        switch(v.status){
            case 'up':
                var statusColor = 'success';
                var statusIcon = 'ti-link text-success';
                var nextPing = moment.utc(v.next_ping, "YYYY-MM-DD hh:mm[Z]").local().fromNow();
                var lastPing = moment.utc(v.last_ping, "YYYY-MM-DD hh:mm[Z]").local().fromNow();
                break;
            case 'down':
                var statusColor = 'danger animated-3 loop-animation flash';
                var statusIcon = 'ti-unlink text-danger';
                var nextPing = 'Service Down';
                var lastPing = moment.utc(v.last_ping, "YYYY-MM-DD hh:mm[Z]").local().fromNow();
                break;
            case 'new':
                var statusColor = 'info';
                var statusIcon = 'ti-time text-info';
                var nextPing = 'Waiting...';
                var lastPing = 'n/a';
                break;
            case 'grace':
                var statusColor = 'warning';
                var statusIcon = 'ti-alert text-warning';
                var nextPing = moment.utc(v.next_ping, "YYYY-MM-DD hh:mm[Z]").local().fromNow();
                var lastPing = 'Missed';
                break;
            case 'paused':
                var statusColor = 'primary';
                var statusIcon = 'ti-control-pause text-primary';
                var nextPing = 'Paused';
                var lastPing = moment.utc(v.last_ping, "YYYY-MM-DD hh:mm[Z]").local().fromNow();
                break;
            default:
                var statusColor = 'warning';
                var statusIcon = 'ti-timer text-warning';
                var nextPing = 'Waiting...';
                var lastPing = 'n/a';
        }
        checks += `
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-xs-12">
                <div class="card bg-inverse text-white mb-3 showMoreHealth mouse" data-id="`+i+`">
                    <div class="card-body bg-org-alt pt-1 pb-1">
                        <div class="d-flex no-block align-items-center">
                            <div class="left-health bg-`+statusColor+`"></div>
                            <div class="ml-1 w-100">
                                <i class="`+statusIcon+` font-20 pull-right mt-3 mb-2"></i>
                                <h3 class="d-flex no-block align-items-center mt-2 mb-2">`+hasIcon+v.name+`</h3>
                                <div class="clearfix"></div>
                                <div class="d-none showMoreHealthDiv-`+i+`"><h5>Last: `+lastPing+`</h5><h5>Next: `+nextPing+`</h5></div>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `
    });
    return checks;
}
function homepageHealthChecks(tags, timeout){
    var tags = (typeof tags !== 'undefined') ? tags : activeInfo.settings.homepage.options.healthChecksTags;
    var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageHealthChecksRefresh;
    organizrAPI('POST','api/?v1/homepage/connect',{action:'getHealthChecks',tags:tags}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        document.getElementById('homepageOrderhealthchecks').innerHTML = '';
        if(response.data !== null){
            $('#homepageOrderhealthchecks').html(buildHealthChecks(response.data));
        }
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    var timeoutTitle = 'HealthChecks-Homepage';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ homepageHealthChecks(tags,timeout); }, timeout);
}
function homepageDownloader(type, timeout){
	var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageDownloadRefresh;
	//if(isHidden()){ return; }
	switch (type) {
        case 'jdownloader':
            var action = 'getJdownloader';
            break;
		case 'sabnzbd':
			var action = 'getSabnzbd';
			break;
		case 'nzbget':
			var action = 'getNzbget';
			break;
		case 'transmission':
			var action = 'getTransmission';
			break;
		case 'qBittorrent':
			var action = 'getqBittorrent';
			break;
		case 'deluge':
			var action = 'getDeluge';
			break;
        case 'rTorrent':
            var action = 'getrTorrent';
            break;
		default:

	}
	organizrAPI('POST','api/?v1/homepage/connect',{action:action}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		//document.getElementById('homepageOrder'+type).innerHTML = '';
		if(response.data !== null){
			buildDownloaderItem(response.data, type);
		}
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
	var timeoutTitle = type+'-Downloader-Homepage';
	if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
	timeouts[timeoutTitle] = setTimeout(function(){ homepageDownloader(type,timeout); }, timeout);
}
function homepageStream(type, timeout){
	var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageStreamRefresh;
	switch (type) {
		case 'plex':
			var action = 'getPlexStreams';
			break;
		case 'emby':
			var action = 'getEmbyStreams';
			break;
		default:

	}
	organizrAPI('POST','api/?v1/homepage/connect',{action:action}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		document.getElementById('homepageOrder'+type+'nowplaying').innerHTML = '';
		$('#homepageOrder'+type+'nowplaying').html(buildStream(response.data, type));
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
	var timeoutTitle = type+'-Stream-Homepage';
	if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
	timeouts[timeoutTitle] = setTimeout(function(){ homepageStream(type,timeout); }, timeout);
}
function homepageRecent(type, timeout){
	var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageRecentRefresh;
	//if(isHidden()){ return; }
	switch (type) {
		case 'plex':
			var action = 'getPlexRecent';
			break;
		case 'emby':
			var action = 'getEmbyRecent';
			break;
		default:

	}
	organizrAPI('POST','api/?v1/homepage/connect',{action:action}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		document.getElementById('homepageOrder'+type+'recent').innerHTML = '';
		$('#homepageOrder'+type+'recent').html(buildRecent(response.data, type));
		$('.recent-items').owlCarousel({
    	    nav:false,
    		autoplay:false,
            dots:false,
			margin:10,
		    autoWidth:true,
		    items:4
    	})
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
	var timeoutTitle = type+'-Recent-Homepage';
	if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
	timeouts[timeoutTitle] = setTimeout(function(){ homepageRecent(type,timeout); }, timeout);
}
function homepagePlaylist(type, timeout=30000){
	//if(isHidden()){ return; }
	switch (type) {
		case 'plex':
			var action = 'getPlexPlaylists';
			break;
		default:

	}
	organizrAPI('POST','api/?v1/homepage/connect',{action:action}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		document.getElementById('homepageOrder'+type+'playlist').innerHTML = '';
		$('#homepageOrder'+type+'playlist').html(buildPlaylist(response.data, type));
		$('.playlist-items').owlCarousel({
			nav:false,
			autoplay:false,
			dots:false,
			margin:10,
			autoWidth:true,
			items:4
    	})
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function homepageRequests(timeout){
	var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.ombiRefresh;
	organizrAPI('POST','api/?v1/homepage/connect',{action:'getRequests'}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
		document.getElementById('homepageOrderombi').innerHTML = '';
		if(response.data.content !== false){
			$('#homepageOrderombi').html(buildRequest(response.data));
		}
		$('.request-items').owlCarousel({
			nav:false,
			autoplay:false,
			dots:false,
			margin:10,
			autoWidth:true,
			items:4
    	})
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
	if(typeof timeouts['ombi-Homepage'] !== 'undefined'){ clearTimeout(timeouts['ombi-Homepage']); }
	timeouts['ombi-Homepage'] = setTimeout(function(){ homepageRequests(timeout); }, timeout);
}
function testAPIConnection(service, data = ''){
    messageSingle('',' Testing now...',activeInfo.settings.notifications.position,'#FFF','info','10000');
    organizrAPI('POST','api/?v1/test/api/connection',{action:service, data:data}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        if(response.data == true){
            messageSingle('',' API Connection Success',activeInfo.settings.notifications.position,'#FFF','success','10000');
        }else{
            messageSingle('API Connection Failed',response.data,activeInfo.settings.notifications.position,'#FFF','error','10000');
        }
        console.log(response);
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
        message('',' Organizr Error',activeInfo.settings.notifications.position,'#FFF','error','10000');
    });
}
function homepageCalendar(timeout){
	var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.calendarRefresh;
    if(activeInfo.settings.homepage.options.alternateHomepageHeaders){
        $('.fc-toolbar').addClass('fc-alternate');
    }
	organizrAPI('POST','api/?v1/homepage/connect',{action:'getCalendar'}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        $('#calendar').fullCalendar('removeEvents');
        $('#calendar').fullCalendar('addEventSource', response.data.events);
        $('#calendar').fullCalendar('addEventSource', response.data.ical);
        $('#calendar').fullCalendar('today');
		response = '';
	}).fail(function(xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
	if(typeof timeouts['calendar-Homepage'] !== 'undefined'){ clearTimeout(timeouts['calendar-Homepage']); }
	timeouts['calendar-Homepage'] = setTimeout(function(){ homepageCalendar(timeout); }, timeout);
}
// Thanks Swifty!
function PopupCenter(url, title, w, h) {
    // Fixes dual-screen position                         Most browsers      Firefox
    var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
    var dualScreenTop = window.screenTop != undefined ? window.screenTop : window.screenY;
    var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
    var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
    var left = ((width / 2) - (w / 2)) + dualScreenLeft;
    var top = ((height / 2) - (h / 2)) + dualScreenTop;
    var newWindow = window.open(url, title, 'scrollbars=yes, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
    // Puts focus on the newWindow
    if (window.focus) {
        newWindow.focus();
    }
    return newWindow;
}
function getPlexHeaders(){
    return {
        'Accept': 'application/json',
        'X-Plex-Product': activeInfo.appearance.title,
        'X-Plex-Version': '2.0',
        'X-Plex-Client-Identifier': activeInfo.settings.misc.uuid,
    };
}
var plex_oauth_window = null;
const plex_oauth_loader = '<style>' +
    '.login-loader-container {' +
    'font-family: "Open Sans", Arial, sans-serif;' +
    'position: absolute;' +
    'top: 0;' +
    'right: 0;' +
    'bottom: 0;' +
    'left: 0;' +
    '}' +
    '.login-loader-message {' +
    'color: #282A2D;' +
    'text-align: center;' +
    'position: absolute;' +
    'left: 50%;' +
    'top: 25%;' +
    'transform: translate(-50%, -50%);' +
    '}' +
    '.login-loader {' +
    'border: 5px solid #ccc;' +
    '-webkit-animation: spin 1s linear infinite;' +
    'animation: spin 1s linear infinite;' +
    'border-top: 5px solid #282A2D;' +
    'border-radius: 50%;' +
    'width: 50px;' +
    'height: 50px;' +
    'position: relative;' +
    'left: calc(50% - 25px);' +
    '}' +
    '@keyframes spin {' +
    '0% { transform: rotate(0deg); }' +
    '100% { transform: rotate(360deg); }' +
    '}' +
    '</style>' +
    '<div class="login-loader-container">' +
    '<div class="login-loader-message">' +
    '<div class="login-loader"></div>' +
    '<br>' +
    'Redirecting to the Plex login page...' +
    '</div>' +
    '</div>';
function closePlexOAuthWindow() {
    if (plex_oauth_window) {
        plex_oauth_window.close();
    }
}
getPlexOAuthPin = function () {
    var x_plex_headers = getPlexHeaders();
    var deferred = $.Deferred();
    $.ajax({
        url: 'https://plex.tv/api/v2/pins?strong=true',
        type: 'POST',
        headers: x_plex_headers,
        success: function(data) {
            deferred.resolve({pin: data.id, code: data.code});
        },
        error: function() {
            closePlexOAuthWindow();
            deferred.reject();
        }
    });
    return deferred;
};
var polling = null;
function PlexOAuth(success, error, pre) {
    if (typeof pre === "function") {
        pre()
    }
    closePlexOAuthWindow();
    plex_oauth_window = PopupCenter('', 'Plex-OAuth', 600, 700);
    $(plex_oauth_window.document.body).html(plex_oauth_loader);
    getPlexOAuthPin().then(function (data) {
        var x_plex_headers = getPlexHeaders();
        const pin = data.pin;
        const code = data.code;
        plex_oauth_window.location = 'https://app.plex.tv/auth/#!?clientID=' + x_plex_headers['X-Plex-Client-Identifier'] + '&code=' + code;
        polling = pin;
        (function poll() {
            $.ajax({
                url: 'https://plex.tv/api/v2/pins/' + pin,
                type: 'GET',
                headers: x_plex_headers,
                success: function (data) {
                    if (data.authToken){
                        closePlexOAuthWindow();
                        if (typeof success === "function") {
                            success('plex',data.authToken)
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus !== "timeout") {
                        closePlexOAuthWindow();
                        if (typeof error === "function") {
                            error()
                        }
                    }
                },
                complete: function () {
                    if (!plex_oauth_window.closed && polling === pin){
                        setTimeout(function() {poll()}, 1000);
                    }
                },
                timeout: 10000
            });
        })();
    }, function () {
        closePlexOAuthWindow();
        if (typeof error === "function") {
            error()
        }
    });
}
function oAuthSuccess(type,token){
    switch(type) {
        case 'plex':
            $('#oAuth-Input').val(token);
            $('#oAuthType-Input').val(type);
            $('#login-username-Input').addClass('hidden');
            $('#login-password-Input').addClass('hidden');
            $('#oAuth-div').removeClass('hidden');
            $('.login-button').first().trigger('click');
            break;
        default:
            break;
    }
}
function oAuthError(){
    messageSingle('',window.lang.translate('Error Connecting to oAuth Provider'),activeInfo.settings.notifications.position,'#FFF','error','5000');
}
function oAuthStart(type){
    switch(type){
        case 'plex':
            PlexOAuth(oAuthSuccess,oAuthError);
            break;
        default:
            break;
    }
}
function clearAJAX(id='all'){
	if(id == 'all'){
		$.each(timeouts, function(i,v) {
			clearTimeout(timeouts[i]);
		});
	}else if(id == 'homepage'){
        $.each(timeouts, function(i,v) {
            if(i.indexOf('-Homepage') > 0 ){
                clearTimeout(timeouts[i]);
            }
        })
    }else{
		clearTimeout(timeouts[id]);
	}
}
//Generate API
function generateCode() {
    var code = "";
    var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 20; i++)
        code += possible.charAt(Math.floor(Math.random() * possible.length));
    return code;
}
// uppercase word
function toUpper(str) {
	return str
	    .toLowerCase()
	    .split(' ')
	    .map(function(word) {
	        return word[0].toUpperCase() + word.substr(1);
	    })
	    .join(' ');
}
// human filesize
function humanFileSize(bytes, si) {
    var thresh = si ? 1000 : 1024;
    if(Math.abs(bytes) < thresh) {
        return bytes + ' B';
    }
    var units = si
        ? ['kB','MB','GB','TB','PB','EB','ZB','YB']
        : ['KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB'];
    var u = -1;
    do {
        bytes /= thresh;
        ++u;
    } while(Math.abs(bytes) >= thresh && u < units.length - 1);
    return bytes.toFixed(1)+' '+units[u];
}
//youtube search
function youtubeSearch(searchQuery) {
	return $.ajax({
		url: "https://www.googleapis.com/youtube/v3/search?part=snippet&q="+searchQuery+"+official+trailer&part=snippet&maxResults=1&type=video&videoDuration=short&key=AIzaSyD-8SHutB60GCcSM8q_Fle38rJUV7ujd8k",
	});
}
function youtubeCheck(title,link){
	youtubeSearch(title).success(function(data) {
		inlineLoad();
		var id = data.items["0"].id.videoId;
		var div = `
		<div id="player-`+link+`" data-plyr-provider="youtube" data-plyr-embed-id="`+id+`"></div>
		<div class="clearfix"></div>
		`;
		$('.youtube-div').html(div);
		$('.'+link).trigger('click');
		player = new Plyr('#player-'+link);
	}).fail(function(xhr) {
		console.error("Organizr Function: YouTube Connection Failed");
	});
}
//request search
function requestSearch(title,page=1) {
	return $.ajax({
		url: "https://api.themoviedb.org/3/search/multi?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&language="+activeInfo.language+"&query="+title+"&page="+page+"&include_adult=false",
	});
}
function requestSearchList(list,page=1) {
	var url = '';
	switch (list) {
		case 'top-movie':
			url = 'https://api.themoviedb.org/3/movie/top_rated?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&language='+activeInfo.language+'&region=US&page='+page;
			break;
		case 'pop-movie':
			url = 'https://api.themoviedb.org/3/movie/popular?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&language='+activeInfo.language+'&region=US&page='+page;
			break;
		case 'up-movie':
			url = 'https://api.themoviedb.org/3/movie/upcoming?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&language='+activeInfo.language+'&region=US&page='+page;
			break;
		case 'theatre-movie':
			url = 'https://api.themoviedb.org/3/movie/now_playing?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&language='+activeInfo.language+'&region=US&page='+page;
			break;
		case 'top-tv':
			url = 'https://api.themoviedb.org/3/tv/top_rated?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&language='+activeInfo.language+'&region=US&page='+page;
			break;
		case 'pop-tv':
			url = 'https://api.themoviedb.org/3/tv/popular?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&language='+activeInfo.language+'&region=US&page='+page;
			break;
		case 'today-tv':
			url = 'https://api.themoviedb.org/3/tv/airing_today?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&language='+activeInfo.language+'&region=US&page='+page;
			break;
		case 'org-mod':
			url = 'https://api.themoviedb.org/4/list/64438?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&language='+activeInfo.language+'&region=US&page='+page;
			break;
		default:

	}
	return $.ajax({
		url: url,
	});
}
function requestNewID(id) {
	return $.ajax({
		url: "https://api.themoviedb.org/3/tv/"+id+"/external_ids?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&language=en-US",
	});
}
function inlineLoad(){
	$('.inline-popups').magnificPopup({
	  removalDelay: 500, //delay removal by X to allow out-animation
	  closeOnBgClick: true,
	  //closeOnContentClick: true,
	  callbacks: {
		beforeOpen: function() {
		   this.st.mainClass = this.st.el.attr('data-effect');
		   this.st.focus = '#request-input';
	   },
	   close: function() {
		  if(typeof player !== 'undefined'){
              console.log('STOP STOP STOP');
			  player.destroy();
		  }
		}
	  },
	  midClick: true // allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source.
	});
}
//Import Users
function importUsers(type){
    $('.importUsersButton').attr('disabled', true);
    messageSingle('',window.lang.translate('Importing Users'),activeInfo.settings.notifications.position,'#FFF','success','5000');
    organizrAPI('POST','api/?v1/import/users',{type:type}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        if(response.data !== false){
            messageSingle('',window.lang.translate('Imported [' + response.data + '] Users'),activeInfo.settings.notifications.position,'#FFF','success','5000');
            $('.importUsersButton').attr('disabled', false);
        }else{
            messageSingle('','Imported Users Error',activeInfo.settings.notifications.position,'#FFF','error','5000');
        }
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
}
//Settings change auth
function changeAuth(){
    var type = $('#authSelect').val();
    var service = $('#authBackendSelect').val();
    switch (service) {
        case 'plex':
            $('.switchAuth').parent().parent().parent().hide();
            $('.backendAuth').parent().parent().parent().show();
            $('.plexAuth').parent().parent().parent().show();
            break;
        case 'emby_local':
        case 'emby_connect':
        case 'emby_all':
            $('.switchAuth').parent().parent().parent().hide();
            $('.backendAuth').parent().parent().parent().show();
            $('.embyAuth').parent().parent().parent().show();
            break;
        case 'ftp':
            $('.switchAuth').parent().parent().parent().hide();
            $('.backendAuth').parent().parent().parent().show();
            $('.ftpAuth').parent().parent().parent().show();
            break;
        case 'ldap':
            $('.switchAuth').parent().parent().parent().hide();
            $('.backendAuth').parent().parent().parent().show();
            $('.ldapAuth').parent().parent().parent().show();
            break;
        default:
            $('.switchAuth').parent().parent().parent().hide();
            $('.backendAuth').parent().parent().parent().show();
    }
    if(type == 'internal') { $('.switchAuth').parent().parent().parent().hide(); }
}
function organizrSpecialSettings(array){
	//media search
	if(array.settings.homepage.search.enabled == true && typeof array.settings.homepage.search.type !== 'undefined'){
		var htmlDOM = `
		<li class=""><a class="waves-effect waves-light inline-popups" href="#mediaSearch-area" data-effect="mfp-zoom-out"> <i class="ti-search"></i></a></li>
		`;
		var searchBoxResults = `
		<div id="mediaSearch-area" class="white-popup mfp-with-anim mfp-hide">
			<div class="col-md-8 col-md-offset-2">
				<div class="white-box m-b-0 resultBox-outside">
					<div class="form-group m-b-0">

							<input id="mediaSearchQuery" data-server="`+array.settings.homepage.search.type+`" lang="en" placeholder="Search My Media" type="text" class="form-control inline-focus">

						<div class="clearfix"></div>
					</div>
					<div class="row el-element-overlay mediaSearch-div resultBox-inside"></div>
				</div>
			</div>
		</div>
		`;
		$(htmlDOM).prependTo('.navbar-right');
		$(searchBoxResults).appendTo($('.organizr-area'));
	}
}
function forceSearch(term){
    $.magnificPopup.close();
    var tabName = $("li[data-url^='api/?v1/homepage/page']").find('span').html();
    if($("li[data-url^='api/?v1/homepage/page']").find('i').hasClass('tabLoaded')){
        console.log('yup');
        if($("li[data-url^='api/?v1/homepage/page']").find('a').hasClass('active')){
            setTimeout(
                function(){
                    $('#newRequestButton').trigger('click');
                    $('#request-input').val(term);
                    doneTyping();
                },
            1000);
        }else{
            tabActions('click',tabName,0);
            setTimeout(
                function(){


                    $('#newRequestButton').trigger('click');
                    $('#request-input').val(term);
                    doneTyping();
                },
            1000);
        }
    }else{
        tabActions('click',tabName,0);
        setTimeout(
            function(){


                $('#newRequestButton').trigger('click');
                $('#request-input').val(term);
                doneTyping();
            },
        3000);
    }
}
function splitPoster(str){
	var words = str.split(' ');
	var newWord = '';
	$.each(words, function(i,v) {
		newWord += v+'<br/>';
	});
	return newWord;
}
function buildMediaResults(array,source,term){
    if(array.content.length == 0){
		var none = '<h2 class="text-center" lang="en">No Results for:</h2><h3 class="text-center" lang="en">'+term+'</h3>';
        none += (activeInfo.settings.homepage.ombi.enabled == true) ? `<button onclick="forceSearch('`+term+`')" class="btn btn-block btn-info" lang="en">Would you like to Request it?</button>` : '';
        return none;
	}
    var results = '';
    var tv = 0;
    var movie = 0;
    var music = 0;
    var total = 0;
	$.each(array.content, function(i,v) {

        total = total + 1;
        tv = (v.type == 'tv') ? tv + 1 : tv;
        movie = (v.type == 'movie') ? movie + 1 : movie;
        music = (v.type == 'music') ? music + 1 : music;
        var bg = v.imageURL;
        var top = v.title;
        var bottom = v.metadata.originallyAvailableAt;
        results += `
        <div id="`+v.uid+`-metadata-div" class="white-popup mfp-with-anim mfp-hide">
            <div class="col-md-8 col-md-offset-2 `+v.uid+`-metadata-info"></div>
        </div>
        <a class="inline-popups `+v.uid+` hidden" href="#`+v.uid+`-metadata-div" data-effect="mfp-zoom-out"></a>

        <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12 m-t-20 request-result-item request-result-`+v.type+` metadata-get mouse" data-source="`+source+`" data-key="`+v.metadataKey+`" data-uid="`+v.uid+`">
            <div class="white-box m-b-10">
                <div class="el-card-item p-b-0">
                    <div class="el-card-avatar el-overlay-1 m-b-5"> <img class="lazyload resultImages" data-src="`+bg+`"></div>
                    <div class="el-card-content bg-org">
                        <h3 class="box-title elip">`+top+`</h3> <small>`+bottom+`</small>
                        <br>
                    </div>
                </div>
            </div>
        </div>
        `;

    });
	//ombi setup?
	if(activeInfo.settings.homepage.ombi.enabled == true){
		results += `
		<div class="col-lg-3 col-md-4 col-sm-6 col-xs-12 m-t-20 request-result-item request-result-movie mouse"  onclick="forceSearch('`+term+`')">
			<div class="white-box m-b-10">
				<div class="el-card-item p-b-0">
					<div class="el-card-avatar el-overlay-1 m-b-5"> <img class="lazyload resultImages mouse" data-src="plugins/images/cache/no-request.png">
						<div class="customPoster">
							<a href="javascript:void(0);">`+splitPoster(term)+`</a>
						</div>
					</div>
					<div class="el-card-content bg-org">
						<h3 class="box-title elip">`+term+`</h3> <small lang="en">Request Me!</small>
						<br>
					</div>
				</div>
			</div>
		</div>
		`;
	}
    var buttons = `
    <div class="button-box p-20 text-center p-b-0">
        <button class="btn btn-inverse waves-effect waves-light filter-request-result" data-filter="request-result-all"><span>`+total+`</span> <i class="fa fa-th-large m-l-5 fa-fw"></i></button>
        <button class="btn btn-primary waves-effect waves-light filter-request-result" data-filter="request-result-movie"><span>`+movie+`</span> <i class="fa fa-film m-l-5 fa-fw"></i></button>
        <button class="btn btn-info waves-effect waves-light filter-request-result" data-filter="request-result-tv"><span>`+tv+`</span> <i class="fa fa-tv m-l-5 fa-fw"></i></button>
        <button class="btn btn-info waves-effect waves-light filter-request-result" data-filter="request-result-music"><span>`+music+`</span> <i class="fa fa-music m-l-5 fa-fw"></i></button>
    </div>
    `;
    return buttons+results;
}
function getPingList(arrayItems){
    var pingList = [];
    var timeout = (activeInfo.user.groupID <= 1) ? activeInfo.settings.homepage.refresh.adminPingRefresh : activeInfo.settings.homepage.refresh.otherPingRefresh;
    if (Array.isArray(arrayItems['data']['tabs']) && arrayItems['data']['tabs'].length > 0) {
        $.each(arrayItems['data']['tabs'], function(i,v) {
            if(v.ping && v.ping_url !== null){
                pingList.push(v.ping_url);
            }
        });
    }
    return (pingList.length > 0) ? pingUpdate(pingList,timeout): false;
}
function pingUpdate(pingList,timeout){
    organizrAPI('POST','api/?v1/ping/list',{pingList:pingList}).success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        if (response.data !== false || response.data !== null) {
            $('.menu-category-ping').each(function( index ) {
                $(this).attr('data-good','0');
                $(this).attr('data-bad','0');
            });
            $.each(response.data, function(i,v) {
                var elm = $('.menu-'+cleanClass(i)+'-ping');
                var elmMs = $('.menu-'+cleanClass(i)+'-ping-ms');
                var catElm = elm.parent().parent().parent().parent().children('a').find('.menu-category-ping');
                var error = '<div class="ping"><span class="heartbit"></span><span class="point"></span></div>';
                var success = '';
                var badCount = (catElm.length !== 0) ? parseInt(catElm.attr('data-bad')) : 0;
                var goodCount = (catElm.length !== 0) ? parseInt(catElm.attr('data-good')) : 0;
                var previousState = (elm.attr('data-previous-state') == "") ? '' : elm.attr('data-previous-state');
                var tabName = elm.attr('data-tab-name');
                var status = (v == false) ? 'down' : 'up';
                var ms = (v == false) ? 'down' : v+'ms';
                var sendMessage = (previousState !== status && previousState !== '' && activeInfo.user.groupID <= activeInfo.settings.ping.authMessage) ? true : false;
                var audioDown = (sendMessage) ? new Audio(activeInfo.settings.ping.offlineSound) : '';
                var audioUp = (sendMessage) ? new Audio(activeInfo.settings.ping.onlineSound) : '';
                elm.attr('data-previous-state', status);
                if(activeInfo.user.groupID <= activeInfo.settings.ping.authMs && activeInfo.settings.ping.ms){ elmMs.removeClass('hidden').html(ms); }
                switch (status){
                    case 'down':
                        if(catElm.length > 0){ badCount = badCount + 1; catElm.attr('data-bad', badCount); }
                        elm.html(error);
                        catElm.html(error);
                        elm.parent().find('img').addClass('grayscale');
                        var msg = (sendMessage) ? message(tabName,'Server Down',activeInfo.settings.notifications.position,'#FFF','error','600000') : '';
                        var audio = (sendMessage && activeInfo.settings.ping.statusSounds) ? audioDown.play() : '';
                        break;
                    default:
                        if(catElm.length > 0){ goodCount = goodCount + 1; catElm.attr('data-good', goodCount); if(badCount == 0){ catElm.html(success); } }
                        elm.html(success);
                        elm.parent().find('img').removeClass('grayscale');
                        var msg = (sendMessage) ? message(tabName,'Server Back Online',activeInfo.settings.notifications.position,'#FFF','success','600000') : '';
                        var audio = (sendMessage && activeInfo.settings.ping.statusSounds) ? audioUp.play() : '';
                }
            });
        }
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    var timeoutTitle = 'ping';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ pingUpdate(pingList,timeout); }, timeout);
}
function include(filename) {
    var type = filename.split('.').pop();
    switch (type){
        case 'js':
            var body = document.getElementsByTagName('body')[0];
            var script = document.createElement('script');
            script.src = filename;
            script.type = 'text/javascript';
            body.appendChild(script);
            break;
        case 'css':
            var head = document.getElementById('style');
            var script = document.createElement('link');
            script.href = filename;
            script.type = 'text/css';
            script.rel = 'stylesheet';
            head.appendChild(script);
            break;
        default:
            return false;
    }
    return false;
}
function defineNotification(){
    var bb = (typeof activeInfo !== 'undefined') ? activeInfo.settings.notifications.backbone : 'izi';
    switch(bb){
        case 'toastr':
            include('plugins/bower_components/toast-master/css/jquery.toast.css');
            include('plugins/bower_components/toast-master/js/jquery.toast.js');
            window.notificationFunction = '$.toast';
            break;
        case 'izi':
            include('plugins/bower_components/iziToast/css/iziToast.min.css');
            include('plugins/bower_components/iziToast/js/iziToast.min.js');
            window.notificationFunction = 'iziToast';
            break;
        case 'alertify':
            include('plugins/bower_components/alertify/alertify.min.css');
            include('plugins/bower_components/alertify/default.min.css');
            include('plugins/bower_components/alertify/alertify.min.js');
            window.notificationFunction = 'alertify';
            break;
        case 'noty':
            include('plugins/bower_components/noty/noty.min.js');
            include('plugins/bower_components/noty/mo.min.js');
            include('plugins/bower_components/noty/noty.css');
            include('plugins/bower_components/noty/mint.css');
            window.notificationFunction = 'Noty';
            break;
        default:
            return false
    }
    window.notificationsReady = true;
}
function messagePositions(){
    return {
        "br":{
            "toastr":"bottom-right",
            "alertify":"bottom-right",
            "izi":"bottomRight",
            "noty":"bottomRight",
        },
        "bl":{
            "toastr":"bottom-left",
            "alertify":"bottom-left",
            "izi":"bottomLeft",
            "noty":"bottomLeft",
        },
        "bc":{
            "toastr":"bottom-center",
            "alertify":"bottom-center",
            "izi":"bottomCenter",
            "noty":"bottomCenter",
        },
        "tr":{
            "toastr":"top-right",
            "alertify":"top-right",
            "izi":"topRight",
            "noty":"topRight",
        },
        "tl":{
            "toastr":"top-left",
            "alertify":"top-left",
            "izi":"topLeft",
            "noty":"topLeft",
        },
        "tc":{
            "toastr":"top-center",
            "alertify":"top-center",
            "izi":"topCenter",
            "noty":"topCenter",
        },
        "c":{
            "toastr":"center",
            "alertify":"bottom-center",
            "izi":"center",
            "noty":"center",
        }
    };
}
function message(heading,text,position,color,icon,timeout){
    var bb = (typeof activeInfo !== 'undefined') ? activeInfo.settings.notifications.backbone : 'izi';
    switch (bb) {
        case 'toastr':

            var ready = (eval( notificationFunction) !== undefined) ? true :false;
            break;
        case 'izi':
        case 'alertify':
        case 'noty':
            try {
                var ready = (typeof eval(notificationFunction) !== undefined) ? true :false;
            } catch (e) {
                if (e instanceof SyntaxError) {
                    setTimeout(function(){ message(heading,text,position,color,icon,timeout); }, 100);
                }
            }
            break;
        default:
            var ready = false;
    }
    if(notificationsReady && ready){
        oldPosition = position;
        position = messagePositions()[position][bb];
        if(local('g','initial')){
            setTimeout(function(){ message(heading,text,oldPosition,color,icon,timeout); }, 100);
            return false;
        }
        switch (bb) {
            case 'toastr':
                $.toast({
                    heading: heading,
                    text: text,
                    position: position,
                    loaderBg: color,
                    icon: icon,
                    hideAfter: timeout,
                    stack: 6,
                    showHideTransition: 'slide',
                });
                break;
            case 'izi':
                switch (icon){
                    case 'success':
                        var msg = {
                            icon: 'mdi mdi-check-circle-outline',
                        };
                        break;
                    case 'info':
                        var msg ={
                            icon: 'mdi mdi-information-outline',
                        };
                        break;
                    case 'error':
                        var msg ={
                            icon: 'mdi mdi-close-circle-outline',
                        };
                        break;
                    case 'warning':
                        var msg ={
                            icon: 'mdi mdi-alert-circle-outline',
                        };
                        break;
                    case 'update':
                        var msg ={
                            icon: 'mdi mdi-webpack',
                        };
                        break;
                    default:
                        var msg ={
                            icon: 'mdi mdi-alert-circle-outline',
                        };
                }
                iziToast.show({
                    close: true,
                    progressBar: true,
                    progressBarEasing: 'ease',
                    class: icon+'-notify',
                    title: heading,
                    message: text,
                    position: position,
                    timeout: timeout,
                    layout: 2,
                    transitionIn: 'flipInX',
                    transitionOut: 'flipOutX',
                    balloon: false,
                    icon: msg['icon'],
                });
                break;
            case 'alertify':
                var msgFull = (heading !== '') ? heading + '<br/>' + text : text;
                timeout = timeout / 1000;
                alertify.set('notifier','position', position);
                alertify.notify(msgFull, icon+'-alertify', timeout);
                break;
            case 'noty':
                if(typeof mojs == 'undefined'){
                    setTimeout(function(){ message(heading,text,oldPosition,color,icon,timeout); }, 100);
                    return false;
                }
                var msgFull = (heading !== '') ? heading + '<br/>' + text : text;
                new Noty({
                    type: icon + '-noty',
                    layout: position,
                    text: msgFull,
                    progressBar: true,
                    timeout: timeout,
                    animation: {
                        open: function (promise) {
                            var n = this;
                            var Timeline = new mojs.Timeline();
                            var body = new mojs.Html({
                                el: n.barDom,
                                x: {500: 0, delay: 0, duration: 500, easing: 'elastic.out'},
                                isForce3d: true,
                                onComplete: function () {
                                    promise(function (resolve) {
                                        resolve();
                                    })
                                }
                            });

                            var parent = new mojs.Shape({
                                parent: n.barDom,
                                width: 200,
                                height: n.barDom.getBoundingClientRect().height,
                                radius: 0,
                                x: {[150]: -150},
                                duration: 1.2 * 500,
                                isShowStart: true
                            });

                            n.barDom.style['overflow'] = 'visible';
                            parent.el.style['overflow'] = 'hidden';

                            var burst = new mojs.Burst({
                                parent: parent.el,
                                count: 10,
                                top: n.barDom.getBoundingClientRect().height + 75,
                                degree: 90,
                                radius: 75,
                                angle: {[-90]: 40},
                                children: {
                                    fill: '#EBD761',
                                    delay: 'stagger(500, -50)',
                                    radius: 'rand(8, 25)',
                                    direction: -1,
                                    isSwirl: true
                                }
                            });

                            var fadeBurst = new mojs.Burst({
                                parent: parent.el,
                                count: 2,
                                degree: 0,
                                angle: 75,
                                radius: {0: 100},
                                top: '90%',
                                children: {
                                    fill: '#EBD761',
                                    pathScale: [.65, 1],
                                    radius: 'rand(12, 15)',
                                    direction: [-1, 1],
                                    delay: .8 * 500,
                                    isSwirl: true
                                }
                            });

                            Timeline.add(body, burst, fadeBurst, parent);
                            Timeline.play();
                        },
                        close: function (promise) {
                            var n = this;
                            new mojs.Html({
                                el: n.barDom,
                                x: {0: 500, delay: 10, duration: 500, easing: 'cubic.out'},
                                skewY: {0: 10, delay: 10, duration: 500, easing: 'cubic.out'},
                                isForce3d: true,
                                onComplete: function () {
                                    promise(function (resolve) {
                                        resolve();
                                    })
                                }
                            }).play();
                        }
                    }
                }).show();
                break;
            default:
                console.log('msg not setup')
        }

    }else{
        setTimeout(function(){ message(heading,text,position,color,icon,timeout); }, 100);
    }

}
function messageSingle(heading,text,position,color,icon,timeout){
    var bb = activeInfo.settings.notifications.backbone;
    switch (bb) {
        case 'toastr':
            var ready = (eval( notificationFunction) !== undefined) ? true :false;
            break;
        case 'izi':
        case 'alertify':
        case 'noty':
            try {
                var ready = (typeof eval(notificationFunction) !== undefined) ? true :false;
            } catch (e) {
                if (e instanceof SyntaxError) {
                    setTimeout(function(){ messageSingle(heading,text,position,color,icon,timeout); }, 100);
                }
            }
            break;
        default:
            var ready = false;
    }
    if(notificationsReady && ready){
        switch (bb) {
            case 'toastr':
                $.toast().reset('all');
                break;
            case 'izi':
                iziToast.destroy();
                break;
            case 'alertify':
                alertify.dismissAll();
                break;
            case 'noty':
                Noty.closeAll();
                break;
            default:
                return false;
        }
        message(heading,text,position,color,icon,timeout);

    }else{
        setTimeout(function(){ messageSingle(heading,text,position,color,icon,timeout); }, 100);
    }
}
function blockDev(e) {
    var evtobj = window.event ? event : e;
    if (evtobj.keyCode == 73 && evtobj.shiftKey && evtobj.ctrlKey){
        evtobj.preventDefault();
    }
}
function authDebugCheck(){
    if(activeInfo.settings.misc.authDebug == true){
        message('REMINDER','Auth Debug is still enabled',activeInfo.settings.notifications.position,'#FFF','warning','20000');
    }
}
function lock(){
    if(activeInfo.settings.user.oAuthLogin == true){
        message('Lock Disabled','Lock function disabled if logged in via oAuth',activeInfo.settings.notifications.position,'#FFF','warning','5000');
        return false;
    }
    organizrAPI('POST','api/?v1/lock','').success(function(data) {
        try {
            var html = JSON.parse(data);
        }catch(e) {
            console.log(e + ' error: ' + data);
            orgErrorAlert('<h4>' + e + '</h4>' + formatDebug(data));
            return false;
        }
        console.log(html);
        if(html.data == true){
            location.reload();
        }else{
            message('Login Error',html.data,activeInfo.settings.notifications.position,'#FFF','warning','10000');
            console.error('Organizr Function: Login failed');
        }
    }).fail(function(xhr) {
        console.error("Organizr Function: Login Failed");
    });
}
function openSettings(){
    var tab = $("li[data-url='api/?v1/settings/page']").find('span').text();
    tabActions('click',tab,0);
}
function openHomepage(){
    var tab = $("li[data-url='api/?v1/homepage/page']").find('span').text();
    tabActions('click',tab,0);
}
function toggleFullScreen() {
    $('.fullscreen-icon').toggleClass('ti-fullscreen').toggleClass('mdi mdi-fullscreen-exit');
    if (!document.fullscreenElement &&    // alternative standard method
        !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement ) {  // current working methods
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
        } else if (document.documentElement.msRequestFullscreen) {
            document.documentElement.msRequestFullscreen();
        } else if (document.documentElement.mozRequestFullScreen) {
            document.documentElement.mozRequestFullScreen();
        } else if (document.documentElement.webkitRequestFullscreen) {
            document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        }
    }
}
function orgErrorCode(code){
    switch (code) {
        case 'upgrading':
            window.location.href = './plugins/static/upgrade.html';
        default:

    }
}
function clickPath(type,path=null){
    switch(type){
        case 'c':
        case 'custom':
            if(path !== null){
                if(typeof path == 'object'){
                    $.each(path, function(i,v) {
                        $(v).trigger('click');
                    });
                }else{
                    $(path).trigger('click');
                }
            }else{
                return null;
            }
            break;
        case 'update':
            $('#settings-main-system-settings-anchor').trigger('click');
            $('#update-button').trigger('click');
            break;
        case 'sso':
            $('#settings-main-system-settings-anchor').trigger('click');
            $('#settings-settings-sso-anchor').trigger('click');
            break;
        default:
            return null;
    }
}
function toggleWritableFolders(){
    $('.folders-writable').toggleClass('hidden');
}
function getAllTabNames(){
    var allTabs = $('.allTabsList');
    var tabList = [];
    $.each(allTabs, function(i,v) {
        tabList[i] = v.getAttribute('data-tab-name').toLowerCase();
    });
    return tabList;
}
function checkIfTabNameExists(tabName){
    if (getAllTabNames().indexOf(tabName.toLowerCase()) == -1) {
        return false;
    }else{
        return true;
    }
}
function orgErrorAlert(error){
    if(activeInfo.settings.misc.debugErrors) {
        $('#main-org-error-container').addClass('show');
        $('#main-org-error').html(error);
    }
}
function closeOrgError(){
    $('#main-org-error-container').removeClass('show');
    $('#main-org-error').html('');
}
function isJSON(data) {
    if (typeof data != 'string'){
        data = JSON.stringify(data);
    }
    try {
        JSON.parse(data);
        return true;
    } catch (e) {
        return false;
    }
}
function createElementFromHTML(htmlString) {
    var div = document.createElement('div');
    div.innerHTML = htmlString.trim();
    return div.firstChild;
}
function showLDAPLoginTest(){
    var div = `
        <div class="row">
            <div class="col-12">
                <div class="card m-b-0">
                    <div class="form-horizontal">
                        <div class="card-body">
                            <h4 class="card-title" lang="en">LDAP User Info</h4>
                            <div class="form-group row">
                                <div class="col-sm-12">
                                    <input type="text" class="form-control" id="ldapUsernameTest" placeholder="Username">
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-sm-12">
                                    <input type="password" class="form-control" id="ldapPasswordTest" placeholder="Password">
                                </div>
                            </div>
                            <div class="form-group mb-0 p-r-10 text-right">
                                <button type="submit" onclick="testAPIConnection('ldap_login', {'username':$('#ldapUsernameTest').val(),'password':$('#ldapPasswordTest').val()})" class="btn btn-info waves-effect waves-light">Test Login</button>
                            </div>
                        </div>				
                    </div>
                </div>
            </div>
        </div>
    `;
    swal({
        content: createElementFromHTML(div),
        buttons: false,
        className: 'bg-org'
    })
}
function launch(){
	organizrConnect('api/?v1/launch_organizr').success(function (data) {
        try {
            var json = JSON.parse(data);
        } catch (e) {
            orgErrorCode(data);
            defineNotification();
            message('FATAL ERROR',data,'br','#FFF','error','60000');
            return false;
        }
		if(json.data.user == false){ location.reload(); }
		currentVersion = json.data.status.version;
		activeInfo = {
			timezone:Intl.DateTimeFormat().resolvedOptions().timeZone,
			offest:new Date().getTimezoneOffset(),
			language:language(moment.locale(navigator.languages[0])),
			browserVersion:bowser.name,
			browserName:bowser.version,
			mobile:bowser.mobile,
			tablet:bowser.tablet,
			osName:bowser.osname,
			osVersion:bowser.osversion,
			serverOS:json.data.status.os,
			phpVersion:json.data.status.php,
			token:json.data.user.token,
			user:json.data.user,
			plugins:json.data.plugins,
			branch:json.branch,
			sso:json.sso,
			settings:json.settings,
            appearance:json.appearance,
			theme:json.theme,
			style:json.style,
			version:json.version
		};
		console.log("%cOrganizr","color: #66D9EF; font-size: 24px; font-family: Monospace;");
		console.log("%cVersion: "+currentVersion,"color: #AD80FD; font-size: 12px; font-family: Monospace;");
		console.log("%cStarting Up...","color: #F92671; font-size: 12px; font-family: Monospace;");
        local('set','initial',true);
        setTimeout(function(){ local('r','initial'); }, 3000);
		defineNotification();
		checkMessage();
		errorPage();
		uriRedirect();
		changeStyle(activeInfo.style);
		changeTheme(activeInfo.theme);
		setSSO();
		switch (json.data.status.status) {
			case "wizard":
				buildWizard();
				buildLanguage('wizard');
				break;
			case "dependencies":
				buildDependencyCheck(json);
				break;
			case "ok":
				loadAppearance(json.appearance);
                if(activeInfo.user.locked == 1){
                    buildLockscreen();
                }else{
                    userMenu(json);
                    categoryProcess(json);
                    tabProcess(json);
                    buildSplashScreen(json);
                    accountManager(json);
                    organizrSpecialSettings(json);
                    getPingList(json);
                }
                loadCustomJava(json.appearance);
                if(getCookie('lockout')){
                    $('.show-login').click();
                    setTimeout(function(){
                        $('div.login-box').block({
                            message: '<h5><i class="fa fa-close"></i> Locked Out!</h4>',
                            css: {
                                color: '#fff',
                                border: '1px solid #e91e63',
                                backgroundColor: '#f44336'
                            }
                        });
                    }, 1000);
                    setTimeout(function(){ location.reload() }, 60000);
                }
				break;
			default:
				console.error('Organizr Function: Action not set or defined');
		}
		console.log('Organizr DOM Fully loaded');
	});
}
