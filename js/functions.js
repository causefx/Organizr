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
var OAuthLoginNeeded = false;
var directToHash = false;
var pingOrg = false;
var checkCommitLoadStatus = false;
var timeouts = {};
var increment = 0;
var tabInformation = {};
var tabActionsList = [];
tabActionsList['refresh'] = [];
tabActionsList['close'] = [];
var customHTMLEditorObject = [];
$.xhrPool = [];
// Add new jquery serializeObject function
$.fn.serializeObject = function()
{
	var o = {};
	var a = this.serializeArray();
	$.each(a, function() {
		if (o[this.name] !== undefined) {
			if (!o[this.name].push) {
				o[this.name] = [o[this.name]];
			}
			o[this.name].push(this.value || '');
		} else {
			o[this.name] = this.value || '';
		}
	});
	return o;
};
// Start Organizr
$(document).ready(function () {
    if(getCookie('organizrOAuth')){
        OAuthLoginNeeded = true
    }
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
function orgDebug() {
    let cmd = $('#debug-input').val();
    let result = '';
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
function getDepth(object) {
	var level = 1;
	for(var key in object) {
		if (!object.hasOwnProperty(key)) continue;

		if(typeof object[key] == 'object'){
			var depth = getDepth(object[key]) + 1;
			level = Math.max(depth, level);
		}
	}
	return level;
}
function jsonToHTML(json){
	var html = '';
	$.each(json, function(i,v) {

		if(typeof v === 'object'){
			html += '<p class="tab0">' + i + ':</p>';
			$.each(v, function(index,value) {
				if(typeof value === 'object'){
					html += '<p class="tab1">' + index + ':</p>';
					html += jsonToHTML2(value);
				}else{
					html += '<p class="tab1">' + index + ': ' + value + '</p>';
				}

			});
		}else{
			html += '<p class="tab0">' + i + ': ' + v + '</p>';
		}
	});
	return html;
}
function jsonToHTML2(json){
	var html = '';
	$.each(json, function(i,v) {

		if(typeof v === 'object'){
			html += '<p class="tab2">' + i + ':</p>';
			$.each(v, function(index,value) {
				if(typeof value === 'object'){
					html += '<p class="tab3">' + index + ':</p>';
					html += jsonToHTML3(value);
				}else{
					html += '<p class="tab3">' + index + ': ' + value + '</p>';
				}

			});
		}else{
			html += '<p class="tab2">' + i + ': ' + v + '</p>';
		}
	});
	return html;
}
function jsonToHTML3(json){
	var html = '';
	$.each(json, function(i,v) {

		if(typeof v === 'object'){
			html += '<p class="tab4">' + i + ':</p>';
			$.each(v, function(index,value) {
				if(typeof value === 'object'){
					html += '<p class="tab5">' + index + ':</p>';
					html += jsonToHTML2(value);
				}else{
					html += '<p class="tab5">' + index + ': ' + value + '</p>';
				}

			});
		}else{
			html += '<p class="tab4">' + i + ': ' + v + '</p>';
		}
	});
	return html;
}


function copyDebug(){
    var pre = $('#debugPreInfo').find('.whitebox').text();
    var debug = $('#debugResults').find('.whitebox').text();
    clipboard(true, pre + debug);
    console.log(pre + debug);
}
function formatDebug(result){
    var formatted = '';
    switch (typeof result) {
        case 'object':
            formatted = jsonToHTML(result);
            break;
        default:
            formatted = result;

    }
    return '<pre class="whitebox bg-org text-success default-scroller">' + formatted + '</pre>';
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
    let preNumber = line.match(/\S*\#(.*)/g);
    if(preNumber !== null){
        preNumber = preNumber.toString();
	    let numberSplit = preNumber.split('#');
	    let issueType = numberSplit[0].replace('(', '').replace(')', '');
	    let issueNumber = numberSplit[1].replace('(', '').replace(')', '');
	    let issueWord = issueType.toLowerCase() == 'fr' ? '<i class="icon-arrow-up-circle"></i> feature' : '<i class="fa fa-github"></i> issue';
	    let colorType = issueType.toLowerCase() == 'fr' ? 'label-info' : 'label-primary';
	    let issueLink = issueType.toLowerCase() == 'fr' ? 'https://feature.organizr.app/posts/' + issueNumber : 'https://github.com/causefx/Organizr/issues/' + issueNumber;
        issueLink = '<span class="label upgrade-label text-uppercase ' + colorType + ' label-rounded font-12 pull-right"><a class="text-white text-uppercase" href="' + issueLink + '" target="_blank">' + issueWord + '</a></span>';
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
	organizrConsole('JSON Function','Copied JSON Strings to clipboard');
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
    if(tab == 'Organizr-Support' || tab == 'Organizr-Docs' || tab == 'Feature-Request'){
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
	                    organizrConsole('Tab Function','Auto Closing tab: '+tab);
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
	                organizrConsole('Tab Function','Auto Reloading tab: '+tab);
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
    if(typeof activeInfo !== 'undefined'){
	    if(activeInfo.settings.lockout.enabled && activeInfo.settings.user.oAuthLogin !== true){
		    if (idleTime > activeInfo.settings.lockout.timer && $('#lockScreen').length !== 1) {
			    if(activeInfo.user.groupID <= activeInfo.settings.lockout.minGroup && activeInfo.user.groupID >= activeInfo.settings.lockout.maxGroup){
				    lock();
			    }
		    }
	    }
    }
}
function ajaxblocker(element = null, action = 'out', message = 'Loading...', background = '#707cd2', border = '#5761a9', colorText = '#fff'){
	switch (action) {
		case 'in':
		case 'fadein':
			$(element).block({
				message: '<p style="margin:0;padding:8px;font-size:24px;" lang="en">'+message+'</p>',
				css: {
					color: colorText,
					border: '1px solid ' + border,
					backgroundColor: background
				}
			});
			break;
		case 'out':
		case 'fadeout':
			$(element).unblock();
			break;
		default:
			$(element).unblock();
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
			directToHash = true;
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
   var vars = query.split("&");
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
// What the hell is this?  I don't remember this lol
function noTabs(arrayItems){
	if (arrayItems.data.user.loggedin === true) {
		organizrAPI2('GET','api/v2/page/tabs').success(function(data) {
			try {
				var json = data.response;
				organizrConsole('Organizr Function','No tabs available');
				$(json.data).appendTo($('.organizr-area'));
				$('.organizr-area').removeClass('hidden');
				$("#preloader").fadeOut();
			}catch(e) {
				organizrCatchError(e,data);
			}
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Error');
		});
	}else {
		$('.show-login').trigger('click');
	}
}
function formatImage (icon) {
    if (!icon.id || icon.text == 'Select or type Image') {
        return icon.text;
    }
    var baseUrl = "/user/pages/images/flags";
    var $icon = $(
        '<span><img src="' + icon.id + '" class="img-chooser" /> ' + icon.text + '</span>'
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
	organizrAPI2('GET','api/v2/logout').success(function(data) {
        try {
            var html = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }

            local('set','message','Goodbye|Logout Successful|success');
            history.replaceState(null, null, ' ');
			location.reload();

	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Logout Failed');
	});
}
function reloadOrganizr(){
	location.reload();
}
function hideFrames(split = null){
	let extra = split ? '-right' : '';
	$(".iFrame-listing"+extra+" div[class^='frame-container']").addClass("hidden").removeClass('show');
    $(".internal-listing"+extra+" div[class^='internal-container']").addClass("hidden").removeClass('show');
    $(".plugin-listing"+extra+" div[class^='plugin-container']").addClass("hidden").removeClass('show');
}
function closeSideMenu(){
	$('.content-wrapper').removeClass('show-sidebar');
}
function removeMenuActive(){
	$("#side-menu a").removeClass('active');
}
function swapDisplay(type, split){
	let extra = split ? '-right' : '';
	switch (type) {
		case 'internal':
		    $('body').removeClass('fix-header');
			$('.iFrame-listing' + extra).addClass('hidden').removeClass('show');
			$('.internal-listing' + extra).addClass('show').removeClass('hidden');
			$('.login-area').addClass('hidden').removeClass('show');
			$('.plugin-listing' + extra).addClass('hidden').removeClass('show');
			//$('body').removeClass('fix-header');
			if(split){
				$('#page-wrapper').addClass('split');
				$('#page-wrapper-right').removeClass('hidden');
			}
			break;
		case 'iframe':
		    $('body').addClass('fix-header');
			$('.iFrame-listing' + extra).addClass('show').removeClass('hidden');
			$('.internal-listing' + extra).addClass('hidden').removeClass('show');
			$('.login-area').addClass('hidden').removeClass('show');
			$('.plugin-listing' + extra).addClass('hidden').removeClass('show');
			//$('body').addClass('fix-header');
			if(split){
				$('#page-wrapper').addClass('split');
				$('#page-wrapper-right').removeClass('hidden');
			}
			break;
		case 'login':
		    $('body').removeClass('fix-header');
			$('.iFrame-listing' + extra).addClass('hidden').removeClass('show');
			$('.internal-listing' + extra).addClass('hidden').removeClass('show');
			$('.login-area').addClass('show').removeClass('hidden');
			$('.plugin-listing' + extra).addClass('hidden').removeClass('show');
			if(activeInfo.settings.misc.minimalLoginScreen == true){
                $('.sidebar').addClass('hidden');
                $('.navbar').addClass('hidden');
                $('#page-wrapper').addClass('hidden');
            }
			if(split){
				$('#page-wrapper').addClass('split');
				$('#page-wrapper-right').removeClass('hidden');
			}
			break;
        case 'plugin':
            $('.iFrame-listing' + extra).addClass('hidden').removeClass('show');
            $('.internal-listing' + extra).addClass('hidden').removeClass('show');
            $('.login-area').addClass('hidden').removeClass('show');
            $('.plugin-listing' + extra).addClass('show').removeClass('hidden');
	        if(split){
		        $('#page-wrapper').addClass('split');
		        $('#page-wrapper-right').removeClass('hidden');
	        }
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
function editPageTitle(title){
    document.title =  title + ' - ' + activeInfo.appearance.title;
}
function switchTab(tab, type, split = null){
	if(activeInfo.settings.misc.collapseSideMenuOnClick){
		if(!$('.navbar ').hasClass('sidebar-hidden')){
			toggleSideMenu();
		}
	}
	let extra = split ? 'right-' : '';
	// need to rework for split
    if(type !== 2){
        hideFrames(split);
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
			swapDisplay('internal', split);
			var newTab = $('#internal-'+extra+tab);
			var tabURL = newTab.attr('data-url');
			$('#menu-'+cleanClass(tab)).find('a').addClass("active");
            editPageTitle(tab);
			if(newTab.hasClass('loaded')){
				organizrConsole('Tab Function','Switching to tab: '+tab);
				newTab.addClass("show").removeClass('hidden');
                setTabInfo(cleanClass(tab),'active',true);
			}else{
				//$("#preloader").fadeIn();
				organizrConsole('Tab Function','Loading new tab for: '+tab);
				$('#menu-'+tab+' a').children().addClass('tabLoaded');
				newTab.addClass("show loaded").removeClass('hidden');
				loadInternal(tabURL,cleanClass(tab), split);
                setTabInfo(cleanClass(tab),'active',true);
                setTabInfo(cleanClass(tab),'loaded',true);
				//$("#preloader").fadeOut();
			}
			break;
		case 1:
		case '1':
		case 'iframe':
			swapDisplay('iframe', split);
			var newTab = $('#container-'+extra+tab);
			var tabURL = newTab.attr('data-url');
			$('#menu-'+cleanClass(tab)).find('a').addClass("active");
            editPageTitle(tab);
			if(newTab.hasClass('loaded')){
				organizrConsole('Tab Function','Switching to tab: '+tab);
				newTab.addClass("show").removeClass('hidden');
                setTabInfo(cleanClass(tab),'active',true);
			}else{
				$("#preloader").fadeIn();
				organizrConsole('Tab Function','Loading new tab for: '+tab);
				$('#menu-'+tab+' a').children().addClass('tabLoaded');
				newTab.addClass("show loaded").removeClass('hidden');
				$(buildFrame(tab,tabURL, extra)).appendTo(newTab);
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
			organizrConsole('Tab Function','Action not set', 'error');
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
			organizrConsole('Tab Function','Creating New Window for tab: '+tab);
			var url = $('#menu-'+cleanClass(tab)).attr('data-url');
			window.open(url, '_blank');
			break;
		default:
			organizrConsole('Tab Function','Action not set', 'error');
	}
}
function closeTab(tab){
    tab = cleanClass(tab);
    // check if current tab?
    if($('.active-tab-'+tab).length > 0){
        closeCurrentTab(event);
    }else{
        if($('.frame-'+tab).hasClass('loaded')){
            var type = $('#menu-'+tab).attr('type');
           switch (type) {
               case 0:
               case '0':
               case 'internal':
                   // quick check if homepage
                   if($('#menu-'+tab).attr('data-url') == 'api/v2/page/homepage'){
	                   organizrConsole('Organizr Function','Clearing All Homepage AJAX calls');
                       clearAJAX('homepage');
	                   $.xhrPool.abortAll();
                   }
	               organizrConsole('Tab Function','Closing tab: '+tab);
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
	               organizrConsole('Tab Function','Closing tab: '+tab);
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
	               organizrConsole('Tab Function','Action not set', 'error');
           }
        }
    }
}
function reloadTab(tab, type){
	$("#preloader").fadeIn();
	organizrConsole('Tab Function','Reloading tab: '+tab);
	switch (type) {
		case 0:
		case '0':
		case 'internal':
			if($('#menu-'+cleanClass(tab)).attr('data-url') == 'api/v2/page/homepage'){
				organizrConsole('Organizr Function','Clearing All Homepage AJAX calls');
				clearAJAX('homepage');
				$.xhrPool.abortAll();
			}
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
			organizrConsole('Tab Function','Action not set', 'error');
	}
	$("#preloader").fadeOut();
}
function reloadCurrentTab(){
	//$("#preloader").fadeIn();
	organizrConsole('Tab Function','Reloading Current tab');
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
			if($(activeInternal).attr('data-url') == 'api/v2/page/homepage'){
				organizrConsole('Organizr Function','Clearing All Homepage AJAX calls');
				clearAJAX('homepage');
				$.xhrPool.abortAll();
			}
			$(activeInternal).html('');
			loadInternal(activeInternal.attr('data-url'),activeInternal.attr('data-name'));
			break;
		case 1:
		case '1':
		case 'iframe':
			var activeFrame = $('.iFrame-listing').find('.show').children('iframe');
			if(RegExp('^\/.*').test(activeFrame.attr('src'))) {
				activeFrame.attr('src', activeFrame[0].contentWindow.location.pathname);
			} else {
				activeFrame.attr('src', activeFrame.attr('src'));
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
			console.error('Tab Function: Action not set');
	}
	//$("#preloader").fadeOut();
}
function loadNextTab(){
	var next = $('#page-wrapper').find('.loaded').attr('data-name');
	if (typeof next !== 'undefined') {
		var type = $('#page-wrapper').find('.loaded').attr('data-type');
        var parent = $('#menu-'+next).parent();
        if(parent.hasClass('in') === false && parent.hasClass('nav-second-level')){
            parent.parent().find('a').first().trigger('click')
        }
		switchTab(next,type);
	}else{
		organizrConsole('Tab Function','No Available Tab to open', 'error');
	}
}
function closeCurrentTab(event){
	let extra = '';
	let split = '';
	if(typeof event !== 'undefined'){
		if(event.ctrlKey && event.altKey && !event.shiftKey){
			extra = '-right';
			split = true;
		}
	}

	var iframe = $('.iFrame-listing'+extra).find('.show');
	var internal = $('.internal-listing'+extra).find('.show');
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
			var tab = $('.internal-listing'+extra).find('.show').attr('data-name');
            // quick check if homepage
            if($('#menu-'+cleanClass(tab)).attr('data-url') == 'api/v2/page/homepage'){
	            organizrConsole('Organizr Function','Clearing All Homepage AJAX calls');
                clearAJAX('homepage');
	            $.xhrPool.abortAll();
            }
			organizrConsole('Organizr Function','Closing tab: '+tab);
			$('#internal'+extra+'-'+cleanClass(tab)).html('');
			$('#menu-'+cleanClass(tab)+' a').removeClass("active");
			$('#menu-'+tab+' a').children().removeClass('tabLoaded');
			$('#internal'+extra+'-'+cleanClass(tab)).removeClass("loaded show");
			$('#menu-'+cleanClass(tab)).removeClass("active");
            setTabInfo(cleanClass(tab),'loaded',false);
            setTabInfo(cleanClass(tab),'active',false);
			loadNextTab();
			break;
		case 1:
		case '1':
		case 'iframe':
			var tab = $('.iFrame-listing'+extra).find('.show').children('iframe').attr('data-name');
			console.log(tab);
			organizrConsole('Organizr Function','Closing tab: '+tab);
			$('#menu-'+cleanClass(tab)+' a').removeClass("active");
			$('#menu-'+tab+' a').children().removeClass('tabLoaded');
			$('#container'+extra+'-'+cleanClass(tab)).removeClass("loaded show").addClass("hidden");
			$('#frame'+extra+'-'+cleanClass(tab)).remove();
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
			organizrConsole('Tab Function','No Available Tab to open', 'error');
	}
}
function tabActions(event,name, type){
	if(event.which == 3){
		return false;
	}
	if((event.ctrlKey && !event.shiftKey && !event.altKey)  || event.which == 2){
		popTab(cleanClass(name), type);
	}else if(event.altKey && !event.shiftKey && !event.ctrlKey){
        closeTab(name);
	}else if(event.shiftKey && !event.ctrlKey && !event.altKey){
		reloadTab(cleanClass(name), type);
	}else if(event.ctrlKey && event.shiftKey && !event.altKey){
		organizrConsole('Tab Function','Action not defined yet', 'info');
    }else if(event.ctrlKey && event.altKey && !event.shiftKey){
		organizrConsole('Tab Function','Action not defined yet', 'info');
		switchTab(cleanClass(name), type, true);
	}else if(event.shiftKey && event.altKey && !event.ctrlKey){
		organizrConsole('Tab Function','Action not defined yet', 'info');
	}else{
		switchTab(cleanClass(name), type);
		if(type !== 2){
			$('.splash-screen').removeClass('in').addClass('hidden');
		}
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
            <div class="panel-heading bg-org" id="`+id+`-heading" role="tab"> <a class="panel-title `+collapsed+`" data-toggle="collapse" href="#`+id+`-collapse" data-parent="#`+mainId+`" aria-expanded="false" aria-controls="`+id+`-collapse"> <span lang="en">`+v.title+`</span> </a> </div>
            <div class="panel-collapse `+collapse+`" id="`+id+`-collapse" aria-labelledby="`+id+`-heading" role="tabpanel">
                <div class="panel-body" lang="en"> `+v.body+` </div>
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
    var label = (item.label) ? ' data-label="'+item.label+'"' : '';
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
	var helpInfo = (item.help) ? '<div class="collapse" id="help-info-'+item.name+'"><blockquote lang="en">'+item.help+'</blockquote></div>' : '';
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
			return smallLabel+'<input data-changed="false" lang="en" type="text" class="form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+label+attr+' autocomplete="new-password" />';
			break;
        case 'number':
            return smallLabel+'<input data-changed="false" lang="en" type="number" class="form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+label+attr+' autocomplete="new-password" />';
            break;
		case 'textbox':
			return smallLabel+'<textarea data-changed="false" class="form-control'+extraClass+'"'+placeholder+id+name+disabled+type+label+attr+' autocomplete="new-password">'+textarea+'</textarea>';
			break;
		case 'password':
			return smallLabel+pwgMgr+'<input data-changed="false" lang="en" type="password" class="form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+label+attr+' autocomplete="new-password" />';
			break;
		case 'password-alt':
			return smallLabel+'<div class="input-group">'+pwgMgr+'<input data-changed="false" lang="en" type="password" class="password-alt form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+label+attr+' autocomplete="new-password" /><span class="input-group-btn"> <button class="btn btn-default showPassword" type="button"><i class="fa fa-eye passwordToggle"></i></button></span></div>';
			break;
		case 'password-alt-copy':
			return smallLabel+'<div class="input-group">'+pwgMgr+'<input data-changed="false" lang="en" type="password" class="password-alt form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+label+attr+' autocomplete="new-password" /><span class="input-group-btn"> <button class="btn btn-primary clipboard" type="button" data-clipboard-text="'+item.value+'"><i class="fa icon-docs"></i></button></span><span class="input-group-btn"> <button class="btn btn-inverse showPassword" type="button"><i class="fa fa-eye passwordToggle"></i></button></span></div>';
			break;
		case 'hidden':
			return '<input data-changed="false" lang="en" type="hidden" class="form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+label+attr+' />';
			break;
		case 'select':
			return smallLabel+'<select class="form-control'+extraClass+'"'+placeholder+value+id+name+disabled+type+label+attr+'>'+selectOptions(item.options, item.value)+'</select>';
			break;
		case 'select2':
            var select2ID = (item.id) ? '#'+item.id : '.'+item.name;
            let settings = (item.settings) ? item.settings : '{}';
            return smallLabel+'<select class="m-b-10 '+extraClass+'"'+placeholder+value+id+name+disabled+type+label+attr+' multiple="multiple" data-placeholder="">'+selectOptions(item.options, item.value)+'</select><script>$("'+select2ID+'").select2('+settings+').on("select2:unselecting", function() { $(this).data("unselecting", true); }).on("select2:opening", function(e) { if ($(this).data("unselecting")) { $(this).removeData("unselecting");  e.preventDefault(); } });</script>';
			break;
		case 'switch':
		case 'checkbox':
			return smallLabel+'<input data-changed="false" type="checkbox" class="js-switch'+extraClass+'" data-size="small" data-color="#99d683" data-secondary-color="#f96262"'+name+value+tof(item.value,'c')+id+disabled+type+label+attr+' /><input data-changed="false" type="hidden"'+name+'value="false">';
			break;
		case 'button':
			return smallLabel+'<button class="btn btn-sm btn-success btn-rounded waves-effect waves-light b-none'+extraClass+'" '+href+attr+' type="button"><span class="btn-label"><i class="'+icon+'"></i></span><span lang="en">'+text+'</span></button>';
			break;
		case 'blank':
			return '';
			break;
		case 'accordion':
			return '<div class="panel-group'+extraClass+'"'+placeholder+value+id+name+disabled+type+label+attr+'  aria-multiselectable="true" role="tablist">'+accordionOptions(item.options, item.id)+'</div>';
			break;
		case 'html':
			return item.html;
            break;
        case 'arrayMultiple':
            return '<span class="text-danger">BuildFormItem Class not setup...';
            break;
		case 'cron':
			return `${smallLabel}<div class="input-group"><input data-changed="false" class="form-control ${extraClass}" ${placeholder} ${value} ${id} ${name} ${disabled} ${type} ${label} ${attr} autocomplete="new-password"><span class="input-group-btn"><button class="btn btn-info test-cron" type="button"><i class="fa fa-flask"></i></button></span></div>`;
			break;
		default:
			return '<span class="text-danger">BuildFormItem Class not setup...';
	}
}
function checkCronFile(){
	$('.cron-results-container').removeClass('hidden');
	organizrAPI2('GET','api/v2/test/cron').success(function(data) {
		try {
			$('.cron-results').text('Cron file is setup correctly');
		}catch(e) {
			$('.cron-results').text('Unknown error');
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		$('.cron-results').text('Cron file is not setup or is setup incorrectly');
		OrganizrApiError(xhr);
	});
}
function buildPluginsItem(array, type = 'enabled'){
	var activePlugins = '';
	var inactivePlugins = '';
	$.each(array, function(i,v) {
		var settingsPage = (v.settings == true && type == 'enabled') ? `
		<!-- Plugin Settings Page -->
		<form id="`+v.idPrefix+`-settings-page" class="mfp-hide white-popup mfp-with-anim addFormTick col-md-10 col-md-offset-1" autocomplete="off">
            <div class="panel bg-org panel-info">
                <div class="panel-heading">
                    <span lang="en">`+v.name+` Settings</span>
                    <button type="button" class="btn bg-org btn-circle close-popup pull-right"><i class="fa fa-times"></i> </button>
                    <button id="`+v.idPrefix+`-settings-page-save" onclick="submitSettingsForm('`+v.idPrefix+`-settings-page')" class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right hidden animated loop-animation rubberBand m-r-20" type="button"><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save</span></button>
                </div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="bg-org">
                        <fieldset id="`+v.idPrefix+`-settings-items" style="border:0;" class=""><h2>Loading...</h2></fieldset>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
		</form>
		` : '';
		var href = (v.settings == true) ? '#'+v.idPrefix+'-settings-page' : 'javascript:void(0);';
		if(v.enabled == true){
			var activeToggle = `<li><a class="btn default btn-outline disablePlugin" href="javascript:void(0);" data-plugin-name="`+v.name+`" data-config-prefix="`+v.configPrefix+`" data-config-name="`+v.configPrefix+`-enabled"><i class="ti-power-off fa-2x"></i></a></li>`;
			var settings = `<li><a class="btn default btn-outline popup-with-form" href="`+href+`" data-effect="mfp-3d-unfold"data-plugin-name="`+v.name+`" id="`+v.idPrefix+`-settings-button" data-config-prefix="`+v.configPrefix+`" data-api="${v.api}" data-settings="${v.settings}" data-bind="${v.bind}"><i class="ti-panel fa-2x"></i></a></li>`;
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
								${settings} ${activeToggle}
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
	return (type === 'enabled') ? `
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
	<div class="clearfix"></div>` : `	
	<div class="panel bg-org panel-info">
		<div class="panel-heading">
			<span lang="en">Inactive Plugins</span>
		</div>
		<div class="panel-wrapper collapse in" aria-expanded="true">
			<div class="panel-body bg-org">
				<div class="row el-element-overlay m-b-40">`+inactivePlugins+`</div>
			</div>
		</div>
	</div>`;
}
function buildPluginsItemOld(array){
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
                    <div class="bg-org">
                        <fieldset id="`+v.idPrefix+`-settings-items" style="border:0;" class=""><h2>Loading...</h2></fieldset>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
		</form>
		` : '';
		var href = (v.settings == true) ? '#'+v.idPrefix+'-settings-page' : 'javascript:void(0);';
		if(v.enabled == true){
			var activeToggle = `<li><a class="btn default btn-outline disablePlugin" href="javascript:void(0);" data-plugin-name="`+v.name+`" data-config-prefix="`+v.configPrefix+`" data-config-name="`+v.configPrefix+`-enabled"><i class="ti-power-off fa-2x"></i></a></li>`;
			var settings = `<li><a class="btn default btn-outline popup-with-form" href="`+href+`" data-effect="mfp-3d-unfold"data-plugin-name="`+v.name+`" id="`+v.idPrefix+`-settings-button" data-config-prefix="`+v.configPrefix+`" data-api="${v.api}" data-settings="${v.settings}" data-bind="${v.bind}"><i class="ti-panel fa-2x"></i></a></li>`;
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
								${settings} ${activeToggle}
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
	<select class="form-control settings-dropdown-box plugin-menu w-100 visible-xs">
		<option value="#settings-plugins-active-anchor" lang="en">Active</option>
		<option value="#settings-plugins-inactive-anchor" lang="en">Inactive</option>
		<option value="#settings-plugins-marketplace-anchor" lang="en">Marketplace</option>
	</select>
	<ul class="nav customtab2 nav-tabs nav-non-mobile hidden-xs" data-dropdown="plugin-menu" role="tablist">
		<li onclick="changeSettingsMenu('Settings::Plugins::Active')" role="presentation" class="active"><a id="settings-plugins-active-anchor" href="#settings-plugins-active" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-file"></i></span><span class="hidden-xs" lang="en">Active</span></a>
		</li>
		<li onclick="changeSettingsMenu('Settings::Plugins::Inactive')" role="presentation" class=""><a id="settings-plugins-inactive-anchor" href="#settings-plugins-inactive" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-zip"></i></span><span class="hidden-xs" lang="en">Inactive</span></a>
		</li>
		<li onclick="changeSettingsMenu('Settings::Plugins::Marketplace');loadMarketplace('plugins');" role="presentation" class=""><a id="settings-plugins-marketplace-anchor" href="#settings-plugins-marketplace" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-shopping-cart-full"></i></span><span class="hidden-xs" lang="en">Marketplace</span></a>
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
	        organizrCatchError(e,data);
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
	    OrganizrApiError(xhr);
    });
}
function loadPluginMarketplace(){
	$('#managePluginTable').html('<td class="text-center" colspan="12"><i class="fa fa-spin fa-spinner"></i></td>');
	organizrAPI2('GET','api/v2/plugins/marketplace').success(function(data) {
		try {
			let response = data.response;
			loadMarketplacePluginsItems(response.data);
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Copy JSON Failed');
	});
}
function loadMarketplacePluginsItems(plugins){
    var pluginList = '';
    $.each(plugins, function(i,v) {
        if(v.icon == null || v.icon == ''){ v.icon = 'test.png'; }
        var installButton = (v.status == 'Update Available') ? 'fa fa-download' : 'fa fa-plus';
        var removeButton = (v.status == 'Not Installed') ? 'disabled' : '';
        v.name = i;
        pluginList += `
            <tr class="pluginManagement" data-name="${i}" data-version="${v.version}" data-repo="${v.repo}">
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
                <td lang="en">`+v.status+`</td>
                <td style="text-align:center"><button type="button" onclick='aboutPlugin(`+JSON.stringify(v)+`);' class="btn btn-success btn-outline btn-circle btn-lg popup-with-form" href="#about-plugin-form" data-effect="mfp-3d-unfold"><i class="fa fa-info"></i></button></td>
                <td style="text-align:center"><button type="button" onclick='installPlugin("`+cleanClass(i)+`");' class="btn btn-info btn-outline btn-circle btn-lg"><i class="`+installButton+`"></i></button></td>
                <td style="text-align:center"><button type="button" onclick='removePlugin("`+cleanClass(i)+`");' class="btn btn-danger btn-outline btn-circle btn-lg" `+removeButton+`><i class="fa fa-trash"></i></button></td>
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
            <tr class="themeManagement" data-name="${i}" data-version="${v.version}">
                <td class="text-center el-element-overlay">
                    <div class="el-card-item p-0">
                        <div class="el-card-avatar el-overlay-1 m-0">
                            <img alt="user-img" src="${v.icon}" width="45">
                        </div>
                    </div>
                </td>
                <td>${i}
                    <br><span class="text-muted">${v.version}</span>
                    <br><span class="text-muted">${v.author}</span>
                </td>
                <td>${v.category}</td>
                <td lang="en">${v.status}</td>
                <td style="text-align:center"><button type="button" onclick='aboutTheme(${JSON.stringify(v)});' class="btn btn-success btn-outline btn-circle btn-lg popup-with-form" href="#about-theme-form" data-effect="mfp-3d-unfold"><i class="fa fa-info"></i></button></td>
                <td style="text-align:center"><button type="button" onclick='installTheme("${cleanClass(i)}");themeAnalytics("${v.name}");' class="btn btn-info btn-outline btn-circle btn-lg"><i class="${installButton}"></i></button></td>
                <td style="text-align:center"><button type="button" onclick='removeTheme("${cleanClass(i)}");' class="btn btn-danger btn-outline btn-circle btn-lg" ${removeButton}><i class="fa fa-trash"></i></button></td>
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
    message('Removing Plugin',plugin,activeInfo.settings.notifications.position,"#FFF","success","5000");
	organizrAPI2('DELETE','api/v2/plugins/manage/' + plugin, {}).success(function(data) {
		try {
			let html = data.response;
			loadPluginMarketplace();
			message(plugin+' Removed','',activeInfo.settings.notifications.position,"#FFF","success","5000");
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Removal Failed');
	});
}
function removeTheme(theme=null){
	if(theme == null){
		return false;
	}
	message('Removing Theme',theme,activeInfo.settings.notifications.position,"#FFF","success","5000");
	organizrAPI2('DELETE','api/v2/themes/manage/' + theme, {}).success(function(data) {
		try {
			var html = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		activeInfo.settings.misc.installedThemes = (html.data == null) ? '' : html.data;
		loadMarketplace('themes');
		message(theme+' Removed','',activeInfo.settings.notifications.position,"#FFF","success","5000");

	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Removal Failed');
	});
}
function installPlugin(plugin=null){
    if(plugin == null){
        return false;
    }
    message('Installing Plugin',plugin,activeInfo.settings.notifications.position,"#FFF","success","5000");
	organizrAPI2('POST','api/v2/plugins/manage/' + plugin, {}).success(function(data) {
		try {
			var html = data.response;
			loadPluginMarketplace();
			message(plugin+' Installed','',activeInfo.settings.notifications.position,"#FFF","success","5000");
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Install Failed');
	});
}
function installTheme(theme=null){
    if(theme == null){
        return false;
    }
    message('Installing Theme',theme,activeInfo.settings.notifications.position,"#FFF","success","5000");
    organizrAPI2('POST','api/v2/themes/manage/' + theme, {}).success(function(data) {
        try {
            var html = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
        activeInfo.settings.misc.installedThemes = html.data;
        loadMarketplace('themes');
        message(theme+' Installed','',activeInfo.settings.notifications.position,"#FFF","success","5000");

    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'Install Failed');
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
function copyHomepageJSON(item){
	organizrAPI2('GET','api/v2/settings/homepage/'+item+'/debug').success(function(data) {
		try {
			let response = data.response;
			let debug = response.data;
			clipboard(true, JSON.stringify(debug,null,'\t'));
			message("",window.lang.translate('Copied JSON to clipboard'),activeInfo.settings.notifications.position,"#FFF","success","5000");
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Copy JSON Failed');
	});
}
function homepageItemFormHTML(v){
	let docs = (typeof v.docs == 'undefined') ? '' : `<small class="pull-right m-r-5"><a data-toggle="tooltip" title="Go to Support Doc" data-placement="bottom" class="btn btn-circle btn-primary waves-effect waves-light" href="${v.docs}" target="_blank"> <i class="fa-fw fa fa-question-circle"></i></a></small>`;
	let debug = (typeof v.debug == 'undefined') ? false : true;
	debug = (debug === true) ? (v.debug) : false;
	debug = (debug === true) ? `<small class="pull-right m-r-5"><a data-toggle="tooltip" title="Copy JSON Settings" data-placement="bottom" href="javascript:copyHomepageJSON('${v.name}')" class="btn btn-circle btn-info waves-effect waves-light copyHomepageJSON"> <i class="fa-fw ti-clipboard"></i></a></small>` : '';
	return `
	<a id="editHomepageItemCall" href="#editHomepageItemDiv" class="hidden">homepage item</a>
	<form id="homepage-`+v.name+`-form" class="white-popup mfp-with-anim homepageForm addFormTick">
		<fieldset style="border:0;" class="col-md-10 col-md-offset-1">
            <div class="panel bg-org panel-info">
                <div class="panel-heading">
                    <span class="" lang="en">`+v.name+`</span>
                    <button data-toggle="tooltip" title="Close" data-placement="bottom"  type="button" class="btn btn-default btn-circle close-popup pull-right close-editHomepageItemDiv"><i class="fa fa-times"></i> </button>
                    ${docs}${debug}
                    <button data-toggle="tooltip" title="Reset" data-placement="bottom" id="homepage-`+v.name+`-form-reset" onclick="editHomepageItem('`+v.name+`', true)" class="btn btn-inverse btn-circle waves-effect waves-light pull-right hidden m-r-5" type="button"><span class=""><i class="fa fa-undo"></i></span></button>
                    <button data-toggle="tooltip" title="Save" data-placement="bottom" id="homepage-`+v.name+`-form-save" onclick="submitSettingsForm('homepage-`+v.name+`-form', true)" class="btn btn-success btn-circle waves-effect waves-light pull-right hidden animated loop-animation rubberBand m-r-5" type="button"><span class=""><i class="fa fa-save"></i></span></button>
                </div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="bg-org">
                        `+buildFormGroup(v.settings)+`
                    </div>
                </div>
            </div>
		</fieldset>
		<div class="clearfix"></div>
	</form>
	`;
}
function clearHomepageOriginal(){
	$('#editHomepageItem').html('');
}
function completeHomepageLoad(item, data){
	/*
	if(item == 'CustomHTML'){
		let iteration = 0;
		$.each(data.settings, function(i,customItem) {
			let iterationString = (parseInt(iteration, 10) + 101).toString().substr(1);
			let customEditor = 'customHTML'+iterationString+'Editor';
			let customTextarea = 'customHTML'+iterationString+'Textarea';
			let HTMLMode = ace.require("ace/mode/html").Mode;
			customHTMLEditorObject[iterationString] = ace.edit(customEditor);
			customHTMLEditorObject[iterationString].session.setMode(new HTMLMode());
			customHTMLEditorObject[iterationString].setTheme("ace/theme/idle_fingers");
			customHTMLEditorObject[iterationString].setShowPrintMargin(false);
			customHTMLEditorObject[iterationString].session.on('change', function(delta) {
				$('.' + customTextarea).val(customHTMLEditorObject[iterationString].getValue());
				//$('#homepage-CustomHTML-form-save').removeClass('hidden');
			});
			iteration++;
		});
	}
	*/
	pageLoad();
}
function editHomepageItem(item, reload = false){
	ajaxloader('.editHomepageItemBox-' + item, 'in');
	organizrAPI2('GET','api/v2/settings/homepage/'+item).success(function(data) {
		try {
			let response = data.response;
			let html = homepageItemFormHTML(response.data);
			$('#editHomepageItem').html(html);
			if(reload){
				ajaxloader('.editHomepageItemBox-' + item);
				return false;
			}
			/*$("#editHomepageItemCall").animatedModal({
				top: '40px',
				left: '0px',
				color: '#000000eb',
				animatedIn: 'bounceInUp',
				animatedOut: 'bounceOutDown',
				position: 'fixed',
				afterClose: function() {
					$('body, html').css({'overflow':'hidden'});
				}
			});*/
			new Custombox.modal({
				content: {
					effect:"slidetogether",
					animateFrom:"bottom",
					animateTo:"bottom",
					target: '#editHomepageItemDiv',
					width: '100%',
					delay: 0,
					fullscreen: true,
					clone: false,
					onComplete: completeHomepageLoad(item, response.data),
					onClose: clearHomepageOriginal
				},loader:{active:true}
			}).open();
			//$('#editHomepageItemCall').click();

		}catch(e) {
			organizrCatchError(e,data);
		}
		ajaxloader('.editHomepageItemBox-' + item);
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Edit Homepage Failed');
		ajaxloader('.editHomepageItemBox-' + item);
	});
}
function buildHomepageItem(array){
	var listing = '';
	if (Array.isArray(array)) {
		$.each(array, function(i,v) {
			if(v.enabled){
				listing += `
				<div class="col-lg-2 col-md-2 col-sm-6 col-xs-6">
					<div class="white-box bg-org m-0">
						<div class="el-card-item p-0 editHomepageItemBox-`+v.name+`">
							<div class="el-card-avatar el-overlay-1">
								<a onclick="editHomepageItem('`+v.name+`')"><img class="lazyload tabImages mouse" data-src="`+v.image+`"></a>
							</div>
							<div class="el-card-content">
								<h3 class="box-title elip">`+v.name+`</h3>
								<small class="elip text-uppercase elip">`+v.category+`</small><br>
							</div>
						</div>
					</div>
				</div>
				`;
			}
		});
	}
	return listing;
}
function buildPluginsOLD(){
	organizrAPI2('GET','api/v2/plugins').success(function(data) {
        try {
            var response = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
		$('#main-plugin-area').html(buildPluginsItemOLD(response.data));
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function buildPlugins(status = 'enabled'){
	organizrAPI2('GET','api/v2/plugins/' + status).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		$('#'+status+'-plugin-area').html(buildPluginsItem(response.data, status));
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function buildHomepage(){
	organizrAPI2('GET','api/v2/settings/homepage').success(function(data) {
        try {
            var response = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
		$('#settings-homepage-list').html(buildHomepageItem(response.data));
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function buildFormGroup(array){
    var mainCount = 0;
	var group = '<div class="tab-content w-100">';
	var uList = '<div class="vtabs customvtab"><ul class="nav tabs-vertical" role="tablist">';
	$.each(array, function(i,v) {
        mainCount++;
		var count = 0;
		var total = v.length;
		var active = (mainCount == 1) ? 'active' : '';
		var customID = createRandomString(10);
		if(i == 'custom'){
			group += v;
		}else{
		    uList += `<li role="presentation" class="`+active+`"><a href="#`+customID+cleanClass(i)+`" aria-controls="`+i+`" role="tab" data-toggle="tab" aria-expanded="false"><span lang="en">`+i+`</span></a></li>`;
			group += `
				<!-- FORM GROUP -->
				<div role="tabpanel" class="tab-pane fade in `+active+`" id="`+customID+cleanClass(i)+`">
			`;
			$.each(v, function(i,v) {
				var override = '6';
				if(typeof v.override !== 'undefined'){
					override = v.override;
				}
                var arrayMultiple = false;
                if(typeof v.type !== 'undefined'){
                    if(v.type == 'arrayMultiple'){
                        arrayMultiple = true;
                    }
                }
				count++;
                if (count % 2 !== 0) {
                    group += '<div class="row start">';
                }
                var helpID = '#help-info-'+v.name;
                var helpTip = (v.help) ? '<sup><a class="help-tip" data-toggle="collapse" href="'+helpID+'" aria-expanded="true"><i class="m-l-5 fa fa-question-circle text-info" title="Help" data-toggle="tooltip"></i></a></sup>' : '';
                var builtItems = '';
                if(arrayMultiple == true){
                    $.each(v.value, function(index,value){
                        if (typeof value === 'object'){
                            builtItems += '<div class="row m-b-40">';
                            $.each(value, function(number,formItem) {
                            	let clearfix = (formItem.type == 'blank') ? '<div class="clearfix"></div>' : '';
                                builtItems += `
                                    <!-- INPUT BOX  Yes Multiple -->
                                    <div class="col-md-6 p-b-10">
                                        <div class="form-group">
                                            <label class="control-label col-md-12"><span lang="en">${formItem.label}</span>${helpTip}</label>
                                            <div class="col-md-12">${buildFormItem(formItem)}</div> <!-- end div -->
                                        </div>
                                    </div>
                                    ${clearfix}
                                    <!--/ INPUT BOX -->
                                `;
                            });
                            builtItems += '</div>';
                        }else{
                            builtItems += buildFormItem(value);
                        }
                    });

                }else{
                    builtItems = `
					<!-- INPUT BOX  no Multiple-->
					<div class="col-md-`+override+` p-b-10">
						<div class="form-group">
							<label class="control-label col-md-12"><span lang="en">${v.label}</span>${helpTip}</label>
							<div class="col-md-12">
								${buildFormItem(v)}
							</div>
						</div>
					</div>
					<!--/ INPUT BOX -->
				`;
                }
                group += builtItems;
                if (count % 2 == 0 || count == total) {
                    group += '</div><!--end-->';
                }
            });
			group += '</div>';
		}
	});
	return uList+'</ul>'+group+'</div>';
}
function createImageSwal(attr){
	let title = attr.attr('data-title');
	let fullPath = attr.attr('data-image-path');
	let clipboardText = attr.attr('data-clipboard-text');
	let name = attr.attr('data-image-name');
	let extension = attr.attr('data-image-name-ext');
	let div = `
		<div class="panel panel-default">
            <div class="panel-heading"><h1><img class="center" src="`+fullPath+`" style="height: 50px; width: 50px">`+title+`</h1></div>
            <div class="panel-wrapper collapse in">
                <div class="panel-body">
                	<h5 lang="en">Choose action:</h5>
					<div class="button-box">
                        <button class="btn btn-info waves-effect waves-light clipboard" type="button" data-clipboard-text="`+clipboardText+`"><span class="btn-label"><i class="ti-clipboard"></i></span><span lang="en">Copy to Clipboard</span></button>
                        <button class="btn btn-danger waves-effect waves-light deleteImage" type="button" data-image-path="`+fullPath+`" data-image-name="`+name+`" data-image-name-ext="`+extension+`"><span class="btn-label"><i class="fa fa-trash"></i></span><span lang="en">Delete</span></button>                        
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
function buildImageManagerViewItem(array){
	var imageListing = '';
	if (Array.isArray(array)) {
		$.each(array, function(i,v) {
			var filepath = v.split("/");
			var name = filepath[3].split(".");
			var clipboardText = v.replace(/ /g,"%20");
			imageListing += `
			<a class="imageManagerItem" href="javascript:void(0);" data-toggle="lightbox" data-gallery="multiimages" data-title="`+name[0]+`" data-clipboard-text="`+clipboardText+`" data-image-path="`+v+`" data-image-name="`+name[0]+`" data-image-name-ext="`+filepath[3]+`"><img data-src="`+v+`" alt="tabImage" class="all studio lazyload" /> </a>
			`;
		});
	}
	return imageListing;
}
function buildImageManagerView(){
	organizrAPI2('GET','api/v2/image').success(function(data) {
        try {
            let response = data.response;
	        $('.settings-image-manager-list').html(buildImageManagerViewItem(response.data));
	        $container = $("#gallery-content-center");
	        try{
	        	if(typeof $container.isotope == 'undefined'){
			        $container.isotope({itemSelector : "img"});
		        }else{
			        $container.isotope({itemSelector : "img"});
		        }
	        }catch{
		        $container.isotope('destroy');
		        $container.isotope({itemSelector : "img"});
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function buildPluginsSettings(){
	organizrAPI2('GET','api/v2/settings/plugin').success(function(data) {
		try {
			let response = data.response;
			$('#plugin-settings-form').html(buildFormGroup(response.data));
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function buildCustomizeAppearance(){
	organizrAPI2('GET','api/v2/settings/appearance').success(function(data) {
        try {
            var response = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
		$('#customize-appearance-form').html(buildFormGroup(response.data));
		$("input.pick-a-color-custom-options").ColorPickerSliders({
			placement: 'bottom',
			color: '#987654',
			hsvpanel: true,
			previewformat: 'hex',
		});
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function buildSSO(){
	organizrAPI2('GET','api/v2/settings/sso').success(function(data) {
        try {
            var response = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
		$('#sso-form').html(buildFormGroup(response.data));
    }).fail(function (xhr) {
		console.error("Organizr Function: API Connection Failed");
	});
}
function buildSettingsMain(){
	organizrAPI2('GET','api/v2/settings/main').success(function(data) {
        try {
            var response = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
		$('#settings-main-form').html(buildFormGroup(response.data));
		changeAuth();
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function buildUserManagement(){
	organizrAPI2('GET','api/v2/users?includeGroups').success(function(data) {
        try {
            var response = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
		$('#manageUserTable').html(buildUserManagementItem(response.data));
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function buildGroupManagement(){
	organizrAPI2('GET','api/v2/groups?includeUsers').success(function(data) {
        try {
            var response = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
		$('#manageGroupTable').html(buildGroupManagementItem(response.data));
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function buildTabEditor(){
	organizrAPI2('GET','api/v2/tabs').success(function(data) {
        try {
            var response = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
		$('#tabEditorTable').html(buildTabEditorItem(response.data));
        checkTabHomepageItems();
		addTabSortable();
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function addTabSortable(){
	let el = document.getElementById('tabEditorTable');
	let tabSorter = new Sortable(el, {
		handle: ".sort-tabs-handle",
		ghostClass: "sortable-ghost",
		multiDrag: true,
		selectedClass: "multi-selected",
		onUpdate: function (evt) {
			$('input.order').each(function(idx) {
			$(this).val(idx + 1);
		});
	newTabsGlobal = $("#submit-tabs-form").serializeToJSON();
	$('.saveTabOrderButton').removeClass('hidden');
},
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
function checkTabHomepageItemList(name, url, urlLocal, id, check, tab) {
	// might use this later
	if (name.includes(check) || url.includes(check) || urlLocal.includes(check)) {
		addEditHomepageItem(id, tab);
	}
}
function checkTabHomepageItem(id, name, url, urlLocal){
    name = name.toLowerCase();
    url = url.toLowerCase();
    urlLocal = urlLocal.toLowerCase();
    try {
        let urlObject = (new URL(url));
        if(urlObject.pathname !== '/' && urlObject !== '#'){
            url = urlObject.pathname;
        }
    } catch {
        url = url;
    }
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
    }else if(name.includes('jdownloader') || url.includes('jdownloader') || urlLocal.includes('jdownloader') || name.includes('rsscrawler') || url.includes('rsscrawler') || urlLocal.includes('rsscrawler')){
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
    }else if(name.includes('utorrent') || url.includes('utorrent') || urlLocal.includes('utorrent')){
        addEditHomepageItem(id,'utorrent');
    }else if(name.includes('deluge') || url.includes('deluge') || urlLocal.includes('deluge')){
        addEditHomepageItem(id,'Deluge');
    }else if(name.includes('ombi') || url.includes('ombi') || urlLocal.includes('ombi')){
        addEditHomepageItem(id,'Ombi');
    }else if(name.includes('healthcheck') || url.includes('healthcheck') || urlLocal.includes('healthcheck')){
        addEditHomepageItem(id,'HealthChecks');
    }else if(name.includes('jackett') || url.includes('jackett') || urlLocal.includes('jackett')){
	    addEditHomepageItem(id,'Jackett');
    }else if(name.includes('unifi') || url.includes('unifi') || urlLocal.includes('unifi')){
	    addEditHomepageItem(id,'Unifi');
    }else if(name.includes('tautulli') || url.includes('tautulli') || urlLocal.includes('tautulli')){
	    addEditHomepageItem(id,'Tautulli');
    }
}
function addEditHomepageItem(id, type){
    let html = '<i class="ti-home"></i>';
    $('#'+id).html(html);
    $('#'+id).attr('onclick', 'editHomepageItem("'+type+'")');
    return false;
}
function buildCategoryEditor(){
	organizrAPI2('GET','api/v2/tabs').success(function(data) {
        try {
            var response = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
		$('#categoryEditorTable').html(buildCategoryEditorItem(response.data));
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
/* END ORGANIZR API FUNCTIONS */
function buildLanguage(replace=false,newLang=null){
	var languageItems = '';
	var currentLanguage = (getCookie('organizrLanguage')) ? getCookie('organizrLanguage') : window.lang.currentLang;
	var newLangCode = '';
	$.each(languageList, function(i,v) {
	    if(newLang === v.language){
            newLangCode = v.code;
        }
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
			<ul class="dropdown-menu mailbox animated bounceInDown language-box">
				<li>
					<div class="drop-title" lang="en">Choose Language</div>
				</li>
				<li>
					<div class="message-center default-scrollbar">${languageItems}</div>
				</li>
			</ul>
			<!-- /.dropdown-messages -->
		</li>
	`;
	if(replace == true){
	    setLangCookie(newLangCode);
		$('#languageDropdown').replaceWith(lang);
		message("",window.lang.translate('Changed Language To')+": "+newLang,activeInfo.settings.notifications.position,"#FFF","success","3500");
	}else if(replace == 'wizard'){
		$(lang).appendTo('.navbar-right');
	}else{
		return lang;
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
		return false;
	}
	if(username !== '' && email !== '' && passwordMatch == true){
		var post = {
			username:username,
			email:email
		};
		if(password1 !== ''){
			post['password'] = password1
		}
		ajaxloader(".content-wrap","in");
		organizrAPI2('PUT','api/v2/users/' + activeInfo.user.userID,post).success(function(data) {
            try {
                var response = data.response;
	            $.magnificPopup.close();
	            messageSingle('',window.lang.translate('User Info Updated'),activeInfo.settings.notifications.position,'#FFF','success','5000');
            }catch(e) {
	            organizrCatchError(e,data);
            }
			ajaxloader();
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Update User');
			ajaxloader();
		});

	}
}
function twoFA(action, type, secret = null){
    switch(action){
        case 'activate':
            organizrAPI2('POST','api/v2/2fa/' + type,{}).success(function(data) {
                try {
                    var html = data.response;
                }catch(e) {
	                organizrCatchError(e,data);
                }
                let div = `
				<div class="panel panel-default">
                    <div class="panel-heading">Enable 2FA: `+html.data.type+`</div>
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body">
                            <p class="twofa-modal-image"><img class="center" src="`+html.data.url+`"></p>
                            <h5 class="twofa-modal-secret text-center">`+html.data.secret+`</h5>
	                        <div class="form-group m-t-10">
	                            <div class="input-group" style="width: 100%;">
	                                <div class="input-group-addon hidden-xs"><i class="ti-lock"></i></div>
	                                <input type="text" class="form-control tfa-input" id="twofa-verify" placeholder="Code" autocomplete="off" autocorrect="off" autocapitalize="off" maxlength="6" spellcheck="false" autofocus="" required="">
	                            </div>
	                            <br>
	                            <button class="btn btn-block btn-info" onclick="twoFA('verify','google');">Verify</button>
	
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
            }).fail(function(xhr) {
	            OrganizrApiError(xhr, '2FA');
            });
            break;
        case 'deactivate':
            organizrAPI2('DELETE','api/v2/2fa').success(function(data) {
                try {
	                message('2FA Removed','',activeInfo.settings.notifications.position,'#FFF','success','5000');
	                $('.2fa-list').replaceWith(buildTwoFA('internal'));
                }catch(e) {
	                organizrCatchError(e,data);
                }
            }).fail(function(xhr) {
	            OrganizrApiError(xhr, '2FA');
            });
            break;
        case 'verify':
            var secret = $('.twofa-modal-secret').text();
            var code = $('#twofa-verify').val();
            if(type !== '' && secret !== '' && code !== ''){
                organizrAPI2('POST','api/v2/2fa',{type:type, secret:secret, code:code}).success(function(data) {
                    try {
                        var html = data.response;
	                    message('2FA Success','Input Code Validated! Saving...',activeInfo.settings.notifications.position,"#FFF","success","5000");
	                    swal.close();
	                    twoFA('save', type, secret);
                    }catch(e) {
	                    organizrCatchError(e,data);
                    }
                }).fail(function(xhr) {
	                OrganizrApiError(xhr, '2FA');
                });
            }else{
                message('2FA Failed','Input Code',activeInfo.settings.notifications.position,"#FFF","warning","5000");
            }
            break;
        case 'save':
            organizrAPI2('PUT','api/v2/2fa',{type:type, secret:secret}).success(function(data) {
                try {
                    var html = data.response;
	                message('2FA Success','2FA Saved',activeInfo.settings.notifications.position,"#FFF","success","5000");
	                $('.2fa-list').replaceWith(buildTwoFA(type));
                }catch(e) {
	                organizrCatchError(e,data);
                }
            }).fail(function(xhr) {
	            OrganizrApiError(xhr, '2FA');
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
function scrapeCall(){
    // Define the URL to scrape [only supports GET at the moment
    var url = 'https://api.github.com/users/causefx/repos';
    // Define callbacks variable first
    var callbacks = $.Callbacks();
    // Add functions that will deal with the data
    callbacks.add( scrapeFunction );
    // Call the API function to scrape the page you want [types = 'json' or 'html']
    scrapeAPI(url, callbacks, 'json');
}
function scrapeFunction(data){
    // Here you would do whatever you like
    if(data.data.result == 'Success'){
        console.log('Success!!!');
    }
    console.log('data:')
    console.log(data);
}
function scrapeAPI(url, callbacks = null, type = null){
    if (typeof url === 'undefined'){
        console.log('error');
        return false;
    }
    organizrAPI2('POST','api/v2/homepage/scrape',{url:url, type:type}).success(function(data) {
        try {
            let response = data.response;
	        if(response){
		        if(callbacks){ callbacks.fire(response); }
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'Scrape');
    });
}
function revokeToken(id){
    organizrAPI2('DELETE','api/v2/token/' + id,{}).success(function(data) {
        try {
	        $('#token-'+id).fadeOut();
	        message(window.lang.translate('Removed Token'),"",activeInfo.settings.notifications.position,"#FFF","success","3500");
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
        ajaxloader();
	    OrganizrApiError(xhr, 'Revoke Token');
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
                    <button class="btn btn-danger waves-effect waves-light" type="button" onclick="revokeToken('`+v.id+`');"><i class="fa fa-ban"></i></button>
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
                                        <th lang="en">Token</th>
                                        <th lang="en">Created</th>
                                        <th lang="en">Expires</th>
                                        <th lang="en">Browser</th>
                                        <th lang="en">IP</th>
                                        <th lang="en">Action</th>
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
                                <p><a href="https://app.plex.tv/auth#?resetPassword" target="_blank" lang="en">Change Password on Plex Website</a></p>
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
                                                            <div class="col-md-6 userManagementPassword">
                                                                <div class="form-group">
                                                                    <label class="control-label" lang="en">Password</label>
                                                                    <input type="password" id="accountPassword1" class="form-control"></div>
                                                            </div>
                                                            <div class="col-md-6 userManagementPassword">
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
	$('body').attr('data-active-user-group-name',user.data.user.group);
	$('body').attr('data-active-user-group-id',user.data.user.groupID);
	var sideMenu = '';
	var menuList = '<li class="hidden-xs" onclick="toggleFullScreen();"><a class="waves-effect waves-light"> <i class="ti-fullscreen fullscreen-icon"></i></a></li>';
	var showDebug = (activeInfo.settings.misc.debugArea) ? '<li><a href="javascript:void(0)" onclick="toggleDebug();"><i class="mdi mdi-bug fa-fw"></i> <span lang="en">Debug Area</span></a></li>' : '';
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
					<li class="divider" role="separator"></li>
					<li class="append-menu"><a class="inline-popups" href="#account-area" data-effect="mfp-zoom-out"><i class="ti-settings fa-fw"></i> <span lang="en">Account Settings</span></a></li>
					<li class="divider" role="separator"></li>
					<li><a href="javascript:void(0)" onclick="lock();"><i class="ti-lock fa-fw"></i> <span lang="en">Lock Screen</span></a></li>
					${showDebug}
					<li><a href="javascript:void(0)" onclick="logout();"><i class="fa fa-sign-out fa-fw"></i> <span lang="en">Logout</span></a></li>
				</ul><!-- /.dropdown-user -->
			</li><!-- /.dropdown -->
		`;
		sideMenu += `
		<li class="user-pro">
			<a href="#" class="waves-effect">
				<img src="`+user.data.user.image+`" alt="user-img" class="img-circle">
				<span class="hide-menu">`+user.data.user.username+`<span class="fa arrow"></span></span>
			</a>
			<ul class="nav nav-second-level collapse" aria-expanded="false" style="height: 0px;">
				<li class="append-menu"><a class="inline-popups" href="#account-area" data-effect="mfp-zoom-out"><i class="ti-settings fa-fw"></i> <span lang="en">Account Settings</span></a></li>
				<li><a href="javascript:void(0)" onclick="lock();"><i class="ti-lock fa-fw"></i> <span lang="en">Lock Screen</span></a></li>
				${showDebug}
				<li><a href="javascript:void(0)" onclick="logout();"><i class="fa fa-sign-out fa-fw"></i> <span lang="en">Logout</span></a></li>
			</ul>
		</li>
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
		sideMenu += `
		<li class="user-pro">
			<a href="#" class="waves-effect">
				<img src="`+user.data.user.image+`" alt="user-img" class="img-circle">
				<span class="hide-menu">`+user.data.user.username+`<span class="fa arrow"></span></span>
			</a>
			<ul class="nav nav-second-level collapse" aria-expanded="false" style="height: 0px;">
				<li class="append-menu"><a href="javascript:void(0)" class="show-login"><i class="fa fa-sign-in fa-fw"></i> <span lang="en">Login/Register</span></a></li>
			</ul>
		</li>
		`;
	}
	$(menuList).appendTo('.navbar-right').html;
	//$(sideMenu).appendTo('#side-menu').html;
	//message("",window.lang.translate('Welcome')+" "+user.data.user.username,activeInfo.settings.notifications.position,"#FFF","success","3500");
	console.info("%c "+window.lang.translate('Welcome')+" %c ".concat(user.data.user.username, " "), "color: white; background: #AD80FD; font-weight: 700;", "color: #AD80FD; background: white; font-weight: 700;");
}
function menuExtras(active){
	let adminMenu = '<li class="devider"></li>';
	let extraOrganizrLinks = [
		{
			'type':2,
			'group_id':1,
			'name':'Github Repo',
			'url':'https://github.com/causefx/organizr',
			'icon':'fontawesome::github',
			'active':activeInfo.settings.menuLink.githubMenuLink
		},
		{
			'type':1,
			'group_id':1,
			'name':'Organizr Support',
			'url':'https://organizr.app/support',
			'icon':'fontawesome::life-ring',
			'active':activeInfo.settings.menuLink.organizrSupportMenuLink
		},
		{
			'type':2,
			'group_id':1,
			'name':'Organizr Docs',
			'url':'https://docs.organizr.app',
			'icon':'simpleline::docs',
			'active':activeInfo.settings.menuLink.organizrDocsMenuLink
		},
		{
			'type':1,
			'group_id':1,
			'name':'Feature Request',
			'url':'https://feature.organizr.app',
			'icon':'simpleline::arrow-up-circle',
			'active':activeInfo.settings.menuLink.organizrFeatureRequestLink
		}
	];
	$.each(extraOrganizrLinks, function(i,v) {
		if(v.type == 1){
			let frame = buildFrameContainer(v.name,v.url,v.type);
			$(frame).appendTo($('.iFrame-listing'));
		}
		adminMenu += (activeInfo.user.groupID <= v.group_id && v.active) ? buildMenuList(v.name,v.url,v.type,v.icon) : '';
	});
	if(active === true){
		return (activeInfo.settings.menuLink.organizrSignoutMenuLink) ? `
			<li class="devider"></li>
			<li id="sign-out"><a class="waves-effect" onclick="logout();"><i class="fa fa-sign-out fa-fw"></i> <span class="hide-menu" lang="en">Logout</span></a></li>
		` + adminMenu : '' + adminMenu;
	}else{
		return (activeInfo.settings.menuLink.organizrSignoutMenuLink) ? `
			<li class="devider"></li>
			<li id="menu-login"><a class="waves-effect show-login" href="javascript:void(0)"><i class="mdi mdi-login fa-fw"></i> <span class="hide-menu" lang="en">Login/Register</span></a></li>
		` : '';
	}
}
function categoryProcess(arrayItems){
	var menuList = '';
	let categoryIn = activeInfo.settings.misc.expandCategoriesByDefault ? 'in' : '';
	let categoryActive = activeInfo.settings.misc.expandCategoriesByDefault ? 'active' : '';
	let categoryExpanded = activeInfo.settings.misc.expandCategoriesByDefault ? 'true' : 'false';
	if (Array.isArray(arrayItems['data']['categories']) && Array.isArray(arrayItems['data']['tabs'])) {
		$.each(arrayItems['data']['categories'], function(i,v) {
			if(v.count !== 0 && v.category_id !== 0){
				menuList += `
					<li class="allGroupsList `+categoryActive+`" data-group-name="`+cleanClass(v.category)+`">
						<a class="waves-effect" href="javascript:void(0)">`+iconPrefix(v.image)+`<span class="hide-menu">`+v.category+` <span class="fa arrow"></span> <span class="label label-rouded label-inverse pull-right">`+v.count+`</span></span><div class="menu-category-ping" data-good="0" data-bad="0"></div></a>
						<ul class="nav nav-second-level category-`+v.category_id+` collapse `+categoryIn+`" aria-expanded="`+categoryExpanded+`"></ul>
					</li>
				`;
			}
		});
		$(menuList).appendTo($('#side-menu'));
	}
}
function buildFrame(name,url, split = null){
	let extra = split ? 'right-' : '';
    var sandbox = activeInfo.settings.misc.sandbox;
    sandbox = sandbox.replace(/,/gi, ' ');
    sandbox = (sandbox) ? ' sandbox="' + sandbox + '"' : '';
	return `
		<iframe allow="clipboard-read; clipboard-write" allowfullscreen="true" frameborder="0" id="frame-`+extra+cleanClass(name)+`" data-name="`+cleanClass(name)+`" `+sandbox+` scrolling="auto" src="`+url+`" class="iframe"></iframe>
	`;
}
function buildFrameContainer(name,url,type, split = null){
	let extra = split ? 'right-' : '';
	return `<div id="container-`+extra+cleanClass(name)+`" data-type="`+type+`" class="frame-container frame-`+cleanClass(name)+` hidden" data-url="`+url+`" data-name="`+cleanClass(name)+`"></div>`;
}
function buildInternalContainer(name,url,type, split = null){
	let extra = split ? 'right-' : '';
	return `<div id="internal-`+extra+cleanClass(name)+`" data-type="`+type+`" class="internal-container frame-`+cleanClass(name)+` hidden" data-url="`+url+`" data-name="`+cleanClass(name)+`"></div>`;
}
function buildMenuList(name,url,type,icon,ping=null,category_id = null,group_id = null){
    var ping = (ping !== null) ? `<small class="menu-`+cleanClass(ping)+`-ping-ms hidden-xs label label-rouded label-inverse pull-right pingTime hidden">
</small><div class="menu-`+cleanClass(ping)+`-ping" data-tab-name="`+name+`" data-previous-state=""></div>` : '';
	return `<li class="allTabsList" id="menu-`+cleanClass(name)+`" data-tab-name="`+cleanClass(name)+`" type="`+type+`" data-group-id="`+group_id+`" data-category-id="`+category_id+`" data-url="`+url+`"><a class="waves-effect"  href="javascript:void(0)" onclick="tabActions(event,'`+cleanClass(name)+`',`+type+`);" onauxclick="tabActions(event,'`+cleanClass(name)+`',`+type+`);">`+iconPrefix(icon)+`<span class="hide-menu elip sidebar-tabName">`+name+`</span>`+ping+`</a></li>`;
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
                var menuList = buildMenuList(v.name,v.access_url,v.type,v.image,v.ping_url, v.category_id, v.group_id);
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
				switch (v.type) {
					case 0:
					case '0':
					case 'internal':
						internalList = buildInternalContainer(v.name,v.access_url,v.type);
						$(internalList).appendTo($('.internal-listing'));
						internalList = buildInternalContainer(v.name,v.access_url,v.type, true);
						$(internalList).appendTo($('.internal-listing-right'));
                        if(v.preload){
                            var newTab = $('#internal-'+cleanClass(v.name));
	                        organizrConsole('Tab Function','Preloading new tab for: '+cleanClass(v.name));
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
	                    iFrameList = buildFrameContainer(v.name,v.access_url,v.type, true);
	                    $(iFrameList).appendTo($('.iFrame-listing-right'));
                        if(v.preload){
                            var newTab = $('#container-'+cleanClass(v.name));
                            var tabURL = newTab.attr('data-url');
	                        organizrConsole('Tab Function','Preloading new tab for: '+cleanClass(v.name));
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
						organizrConsole('Tab Function','Action not set', 'error');
				}
			}
		});
		$('#side-menu').metisMenu({ toggle: activeInfo.settings.misc.autoCollapseCategories });
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
	organizrAPI2('GET', 'api/v2/page/login').success(function(data) {
        try {
            var response = data.response;
	        organizrConsole('Organizr Function','Opening Login Page');
	        $('.login-area').html(response.data);
        }catch(e) {
	        organizrCatchError(e,data);
        }
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Login Error');
	});
	$("#preloader").fadeOut();
}
function buildLockscreen(){
	$("#preloader").fadeIn();
	closeSideMenu();
	organizrAPI2('GET', 'api/v2/page/lockscreen').success(function(data) {
        try {
            var response = data.response;
	        organizrConsole('Organizr Function','Adding Lockscreen');
	        $(response.data).appendTo($('body'));
        }catch(e) {
	        organizrCatchError(e,data);
        }
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
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
                <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 col-xl-2 mouse hvr-grow m-b-20" id="menu-${cleanClass(v.name)}" type="${v.type}" data-url="${v.access_url}" onclick="tabActions(event,'${cleanClass(v.name)}',${v.type});">
                    <div class="homepage-drag fc-event bg-org lazyload" ${dataSrc}>
                        ${nonImage}
                        <span class="homepage-text">&nbsp; ${v.name}</span>
                    </div>
                </div>
                `;
            }
        });
    }
    return (splashList !== '') ? splashList : false;
}
function buildSplashScreen(json){
	let hiddenSplash = (directToHash) ? 'hidden' : 'in';
    var items = buildSplashScreenItem(json);
    var menu = '<li ><a href="javascript:void(0)" onclick="$(\'.splash-screen\').removeClass(\'hidden\').addClass(\'in\')"><i class="ti-layout-grid2 fa-fw"></i> <span lang="en">Splash Page</span></a></li>';
    if(items){
        closeSideMenu();
	    organizrConsole('Organizr Function','Adding Splash Screen');
        var splash = `
        <section id="splashScreen" class="lock-screen splash-screen default-scroller fade ${hiddenSplash}">
            <div class="row p-20 flexbox">`+items+`</div>
            <div class="row p-20 p-t-0 flexbox">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 mouse hvr-wobble-bottom bottom-close-splash" onclick="$('.splash-screen').addClass('hidden').removeClass('in')">
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
			'type':'Organizr'
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
	disabled = (disabled == 'disabled' && typeID !== 0) ? null : disabled;
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
		var deleteDisabled = v.url.indexOf('/page/settings') > 0 ? 'disabled' : 'deleteTab';
		var buttonDisabled = v.url.indexOf('/page/settings') > 0 ? 'disabled' : '';
        var typeDisabled = v.url.indexOf('/v2/page/') > 0 ? 'disabled' : '';
		tabList += `
		<tr class="tabEditor" data-order="`+v.order+`" data-original-order="`+v.order+`" data-id="`+v.id+`" data-group-id="`+v.group_id+`" data-category-id="`+v.category_id+`" data-name="`+v.name+`" data-url="`+v.url+`" data-local-url="`+v.url_local+`" data-ping-url="`+v.ping_url+`" data-image="`+v.image+`" data-tab-action-type="`+v.timeout+`" data-tab-action-time="`+v.timeout_ms+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].id" value="`+v.id+`">
			<input type="hidden" class="form-control order" name="tab[`+v.id+`].order" value="`+v.order+`">
			<input type="hidden" class="form-control" name="tab[`+v.id+`].originalOrder" value="`+v.order+`">
			<td class="mouse-grab sort-tabs-handle">
				<i class="icon-options-vertical m-r-5"></i> 
				<!-- May use later on
				<div class="btn-group dropside visible-xs">
					<button aria-expanded="false" data-toggle="dropdown" class="btn btn-default btn-outline dropdown-toggle waves-effect waves-light" type="button"> <i class="icon-options-vertical m-r-5"></i> <span class="caret"></span></button>
					<ul role="menu" class="dropdown-menu">
						<li><a href="#"><i class="fa fa-angle-double-up"></i></a></li>
						<li><a href="#"><i class="fa fa-angle-up"></i></a></li>
						<li><a href="#"><i class="fa fa-angle-double-down"></i></a></li>
						<li><a href="#"><i class="fa fa-angle-down"></i></a></li>
					</ul>
				</div>
				-->
			</td>
			<td style="text-align:center" class="text-center el-element-overlay">
				<div class="el-card-item p-0">
					<div class="el-card-avatar el-overlay-1 m-0">
						<div class="tabEditorIcon">`+iconPrefix(v.image)+`</div>
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
			<td style="text-align:center"><button type="button" class="btn btn-info btn-outline btn-circle btn-lg m-r-5 editTabButton popup-with-form" onclick="editTabForm('`+v.id+`')" href="#edit-tab-form" data-effect="mfp-3d-unfold"><i class="ti-pencil-alt"></i></button></td>
			<td style="text-align:center"><button type="button" class="btn btn-danger btn-outline btn-circle btn-lg m-r-5 `+deleteDisabled+`"><i class="ti-trash"></i></button></td>
		</tr>
		`;
	});
	return tabList;
}
function editTabForm(id){
	organizrAPI2('GET','api/v2/tabs/' + id,true).success(function(data) {
		try {
			let response = data.response;
			$('.tabIconImageList').val(null).trigger('change');
			$('.tabIconIconList').val(null).trigger('change');
			$('#edit-tab-form [name=name]').val(response.data.name);
			$('#originalTabName').html(response.data.name);
			$('#edit-tab-form [name=url]').val(response.data.url);
			$('#edit-tab-form [name=url_local]').val(response.data.url_local);
			$('#edit-tab-form [name=ping_url]').val(response.data.ping_url);
			$('#edit-tab-form [name=image]').val(response.data.image);
			$('#edit-tab-form [name=id]').val(response.data.id);
			$('#edit-tab-form [name=timeout_ms]').val(convertMsToMinutes(response.data.timeout_ms));
			$('#edit-tab-form [name=timeout]').val(response.data.timeout);
			if( response.data.url.indexOf('/?v') > 0){
				$('#edit-tab-form [name=url]').prop('disabled', 'true');
			}else{
				$('#edit-tab-form [name=url]').prop('disabled', null);
			}
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Tab Error');
	});
}
function getSubmitSettingsFormValueSingle(form, index, value){
    var values = {};
    if(value !== '#987654' && index.includes('disable-pwd-mgr') == false) {
        var input = $("#" + form + " [name='" + index + "']");
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
        values = {name: index, value: value, type: dataType};
        return values;
    }
    return false;
}
function getSubmitSettingsFormValueObject(form, index, value){
    var values = [];
    $.each(value, function(i,v) {
        var objectList = [];
        var object = [];
        $.each(v, function(key,val) {
            if(val !== '#987654' && key.includes('disable-pwd-mgr') == false) {
                var input = $("#" + form + " [name='" + index + "["+i+"]."+key+"']");
                var dataType = input.attr('data-type');
                var dataLabel = input.attr('data-label');
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
                var newKey = index + '[' + i + '].' + key;
                object.push({type: dataType, name: newKey, label: dataLabel, value: value});
            }
        })
        values.push(object);
    });
    values = {name: index, value: values, type: 'array'};
    return values;
}
function submitSettingsForm(form, homepageItem = false){
    var list = $( "#"+form ).serializeToJSON();
    var size = 0;
    var submit = {};
    $.each(list, function(i,v) {
        var values = false;
        if(typeof v === 'object' && typeof v.length === 'undefined'){
            values = getSubmitSettingsFormValueObject(form, i, v)
        }else{
            values = getSubmitSettingsFormValueSingle(form, i, v)
        }
        size++;
        if(values){
	        submit[i] = values.value;
        }
    });
	var callbacks = $.Callbacks();
	// Custom Callbacks
	switch(form){
		case 'customize-appearance-form':
			break;
		default:
	}
	if(size > 0){
		organizrAPI2('PUT','api/v2/config', submit,true).success(function(data) {
			try {
				var response = data.response;
			}catch(e) {
				organizrCatchError(e,data);
			}
			if(callbacks){ callbacks.fire(); }
			if(homepageItem) {
				let html = `
		        <div class="panel panel-default">
                    <div class="panel-heading">${response.message}</div>
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body">
                            <div class="overlay-box">
                                <div class="user-content">
                                    <h4 lang="en">Close Homepage Settings?</h4>
                                    <div class="button-box">
				                        <button class="btn btn-info waves-effect waves-light" type="button" onclick="swal.close();Custombox.modal.close()"><span class="btn-label"><i class="ti-check"></i></span>Yes</button>
				                        <button class="btn btn-danger waves-effect waves-light" type="button" onclick="swal.close()"><span class="btn-label"><i class="ti-close"></i></span>No</button>                        
				                    </div>
				                    <p class="close-homepage-timer">Auto Closing in 5 seconds...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
		    `;
				swal({
					content: createElementFromHTML(html),
					buttons: false,
					className: 'bg-org',
					timer: 5000
				})
				textTimer(5,'.close-homepage-timer', 'Seconds remaining: ', 'Closing...');
			}else{
				message('Updated Items',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
			}
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Update Error');
		});
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
function textTimer(seconds,el,preText,postText){
	var seconds_left = seconds;
	var interval = setInterval(function() {
		$(el).html(preText + ' ' + --seconds_left)
		if (seconds_left <= 0)
		{
			$(el).html(postText)
			clearInterval(interval);
		}
	}, 1000);
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
    var callbacks = $.Callbacks();
	if(size > 0){
		organizrAPI2('PUT','api/v2/config', submit,true).success(function(data) {
			try {
				var response = data.response;
				$('#submitHomepageOrder-save').addClass('hidden');
			}catch(e) {
				organizrCatchError(e,data);
			}
			message('Updated Homepage Order',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
			if(callbacks){ callbacks.fire(); }
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'Update Error');
		});
	}else{
	    console.log('add error');
	}
}
function submitTabOrder(newTabs){
	var data = [];
	var process = false;
	$.each(newTabs.tab, function(i,v) {
		if(v.originalOrder == v.order){
			delete newTabs.tab[i];
		}else{
			let temp = {
				"order":v.order,
				"id":v.id
			}
			data.push(temp);
			process = true;
		}
	})
	if(!process){
		message('Tab Order Warning','Order was not changed - Submission not needed',activeInfo.settings.notifications.position,"#FFF","warning","5000");
		$('.saveTabOrderButton').addClass('hidden');
		return false;
	}
	var callbacks = $.Callbacks();
	callbacks.add( buildTabEditor );
	organizrAPI2('PUT','api/v2/tabs',data,true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Tab Order Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
		$('.saveTabOrderButton').addClass('hidden');
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Update Error');
	});
}
function submitCategoryOrder(){
	var data = [];
	var categories = $( "#submit-categories-form" ).serializeToJSON();
	var callbacks = $.Callbacks();
	callbacks.add( buildCategoryEditor );
	$.each(categories.category, function(i,v) {
		if(v.originalOrder == v.order){
			delete categories.category[i];
		}else{
			let temp = {
				"order":v.order,
				"id":v.id
			}
			data.push(temp);
		}
	})
	organizrAPI2('PUT','api/v2/categories',data,true).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		message('Category Order Updated',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
		$('.saveTabOrderButton').addClass('hidden');
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Update Error');
	});
}
function buildTR(array,type,badge){
	var listing = '';
	var arrayItems = array.split("|");
	if(hasValue(arrayItems) === true){
		$.each(arrayItems, function(i,v) {
			listing += `
			<tr>
				<td  width="70"><span class="label label-`+badge+`"><span lang="en">`+type+`</span></span></td>
				<td class="text-capitalize">`+updateIssueLink(v)+`</td>
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
		let tableClass = x == 0 ? '' : 'hidden';
		let divClassPadding = x == 0 ? '' : 'p-b-0';
		let divClassMargin = x == 0 ? '' : 'm-b-0';
		let toggleButtonText = x == 0 ? 'Less' : 'More';
		let toggleButtonIcon = x == 0 ? 'up' : 'down';
		let divStatus = x == 0 ? 'opened' : 'closed';
		versions += `
		<div class="white-box bg-org ${divClassPadding} update-main-div-${x}" data-status="${divStatus}">
			<div class="col-md-3 col-sm-4 col-xs-6 pull-right">`+button+`</div>
			<h3 class="box-title ${divClassMargin} update-box-title-${x}">`+i+`</h3>
			<div class="row sales-report">
				<div class="col-md-12 col-sm-12 col-xs-12">
					<div class="pull-left">
						<span class="tooltip-info" data-toggle="tooltip" data-placement="right" title="" data-original-title="`+moment(v.date).format('LL')+`">`+moment.utc(v.date, "YYYY-MM-DD hh:mm[Z]").local().fromNow()+`</span>
						<p class="text-info p-0">`+v.title+`</p>
					</div>
					<button class="btn btn-sm btn-primary btn-rounded waves-effect waves-light pull-right" onclick="toggleGithubVersion(${x})" type="button"><span class="btn-label"><i class="fa fa-long-arrow-${toggleButtonIcon} toggleButtonIcon-${x}"></i></span><span lang="en" class="toggleButton-${x}">${toggleButtonText}</span></button>
				</div>
			</div>
			<div class="table-responsive ${tableClass} update-table-${x}">
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
function toggleGithubVersion(id){
	let status = $('.update-main-div-' + id).attr('data-status');
	if(status == 'opened'){
		$('.update-main-div-' + id).attr('data-status', 'closed');
		$('.update-main-div-' + id).addClass('p-b-0');
		$('.update-box-title-' + id).addClass('m-b-0');
		$('.update-table-' + id).addClass('hidden');
		$('.toggleButton-' + id).text('More');
		$('.toggleButtonIcon-' + id).removeClass('fa-long-arrow-up').addClass('fa-long-arrow-down');
	}else{
		$('.update-main-div-' + id).attr('data-status', 'opened');
		$('.update-main-div-' + id).removeClass('p-b-0');
		$('.update-box-title-' + id).removeClass('m-b-0');
		$('.update-table-' + id).removeClass('hidden');
		$('.toggleButton-' + id).text('Less');
		$('.toggleButtonIcon-' + id).addClass('fa-long-arrow-up').removeClass('fa-long-arrow-down');

	}
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
	        organizrCatchError(e,data);
        }
		for (var a in reverseObject(response)){
			var latest = a;
			break;
		}
		if(latest !== currentVersion) {
			organizrConsole('Update Function','Update to ' + latest + ' is available', 'warning');
            if (activeInfo.settings.misc.docker === false) {
                messageSingle(window.lang.translate('Update Available'), latest + ' ' + window.lang.translate('is available, goto') + ' <a href="javascript:void(0)" onclick="tabActions(event,\'Settings\',0);clickPath(\'update\')"><span lang="en">Update Tab</span></a>', activeInfo.settings.notifications.position, '#FFF', 'update', '60000');
            }
        }else{
			organizrConsole('Update Function','Already running latest version: ' + latest, 'info');
		}
		$('#githubVersions').html(buildVersion(reverseObject(response)));
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function ignoreNewsId(id){
	organizrAPI2('POST','api/v2/news/' + id,{}).success(function(data) {
		try {
			let response = data.response;
			message('News Item','Item now ignored',activeInfo.settings.notifications.position,"#FFF","success","5000");
			$('.newsItem-' + id).remove();
			$('.newsHeart-' + id).remove();
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'News');
	});
}
function newsLoad(){
    newsJSON().success(function(data) {
        try {
            var response = JSON.parse(data);
            var items = [];
            var limit = 5;
            var count = 0;
	        organizrAPI2('get','api/v2/news').success(function(data) {
		        try {
			        let ignoredIds = data.response.data;
			        ignoredIds = ignoredIds == null ? [] : ignoredIds;
			        $.each(response, function(i,v) {
				        count++;
				        let ignore = ignoredIds.includes(v.id);
				        let alertDefined = (typeof v.important !== 'undefined' && v.important !== false);
				        let alert = (alertDefined && ignore == false) ? `<span class="animated loop-animation flash text-danger mouse newsItem-${v.id}" onclick="ignoreNewsId('${v.id}')">&nbsp; <i class="ti-alert"></i>&nbsp; Important Message - Click me to Ignore</span>` : '';
				        let heartBeat = (alertDefined && ignore == false) ? `<div class="notify pull-left newsHeart-${v.id}"><span class="heartbit"></span><span class="point"></span></div>` : '';
				        let newBody = `
			                <h5 class="pull-left"><i class="ti-calendar"></i>&nbsp;`+moment(v.date).format('LLL')+ alert +`</h5>
			                <h5 class="pull-right">`+v.author+`</h5>
			                <div class="clearfix"></div>
			                `+((v.subTitle) ? '<h5>' + v.subTitle +'</h5>' : '' )+`
			                <p>`+v.body+`</p>
			                `;
				        if(count <= limit){
					        items[i] = {
						        title:v.title + heartBeat,
						        body:newBody
					        }
				        }
			        });
			        var body = buildAccordion(items, true);
			        $('#organizrNewsPanel').html(body);
		        }catch(e) {
			        organizrCatchError(e,data);
		        }
	        }).fail(function(xhr) {
		        OrganizrApiError(xhr, 'News');
	        });

        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr);
    });
}
function checkPluginUpdates(){
	if(!activeInfo.user.loggedin || activeInfo.user.groupID > 1){
		return false;
	}
	organizrAPI2('get','api/v2/plugins/marketplace').success(function(data) {
		try {
			let update = false;
			let pluginsNeedingUpdate = [];
			let plugins = data.response.data;
			$.each(plugins, function(i,v) {
				if(v.needs_update){
					update = true;
					pluginsNeedingUpdate.push(i);
				}
			});
			if(update){
				pluginsNeedingUpdate = '[' + pluginsNeedingUpdate.join(', ') + ']';
				messageSingle(window.lang.translate('Update Available'), '<a href="javascript:void(0)" onclick="shortcut(\'plugin-marketplace\');"><span lang="en">The following plugin(s) need updates</span></a>: ' + pluginsNeedingUpdate, activeInfo.settings.notifications.position, '#FFF', 'update', '600000');
			}
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'News');
	});
}
function checkCommitLoad(){
    if(activeInfo.settings.misc.docker && activeInfo.settings.misc.githubCommit !== 'n/a' && activeInfo.settings.misc.githubCommit !== null) {
	    if(checkCommitLoadStatus == false) {
		    checkCommitLoadStatus = true;
		    getLatestCommitJSON().success(function (data) {
			    try {
				    var latest = data.sha.toString().trim();
				    var current = activeInfo.settings.misc.githubCommit.toString().trim();
				    var link = 'https://github.com/causefx/Organizr/compare/' + current + '...' + latest;
				    if (latest !== current) {
					    messageSingle(window.lang.translate('Update Available'), ' <a href="' + link + '" target="_blank"><span lang="en">Compare Difference</span></a> <span lang="en">or</span> <a href="javascript:void(0)" onclick="updateNow()"><span lang="en">Update Now</span></a>', activeInfo.settings.notifications.position, '#FFF', 'update', '600000');
				    } else {
					    organizrConsole('Update Function', 'Organizr Docker - Up to date');
				    }
			    } catch (e) {
				    organizrCatchError(e, data);
			    }
			    checkCommitLoadStatus = false;
		    }).fail(function (xhr) {
			    console.error("Organizr Function: Github Connection Failed");
			    checkCommitLoadStatus = false;
		    });
	    }
    }
}
function sponsorLoad(){
    sponsorsJSON().success(function(data) {
        try {
            var response = JSON.parse(data);
        }catch(e) {
	        organizrCatchError(e,data);
        }
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
	    OrganizrApiError(xhr);
    });
}
function backersLoad(){
	organizrAPI2('GET','api/v2/sponsors/all').success(function(data) {
		try {
			let json = data.response;
			$('#backersList').html(buildBackers(json.data));
			$('.backers-items').owlCarousel({
				nav:false,
				autoplay:true,
				dots:false,
				margin:10,
				autoWidth:true,
				items:4
			});
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function buildBackers(array){
	let backers = '';
	$.each(array, function(i,v) {
		if(v.type == 'USER' && v.role == 'BACKER' && v.isActive){
			v.name = v.name ? v.name : 'User';
			v.image = v.image ? v.image : 'plugins/images/default_user.png';
			backers += `
		        <!-- /.usercard -->
		        <div class="item lazyload recent-sponsor imageSource"  data-src="${v.image}">
		            <span class="elip recent-title">${v.name}</span>
		        </div>
		        <!-- /.usercard-->
		    `;
		}
	});
	backers += `
        <!-- /.usercard -->
        <div class="item lazyload recent-sponsor mouse imageSource mouse" onclick="window.open('https://opencollective.com/organizr', '_blank')" data-src="plugins/images/sponsor-open-collective.png">
            <span class="elip recent-title" lang="en">You</span>
        </div>
        <!-- /.usercard-->
    `;
	return backers;
}
function sponsorDetails(id){
	sponsorsJSON().success(function(data) {
		try {
			let response = JSON.parse(data);
			let coupon = (response[id].coupon == null) ? false : true;
			let couponAbout = (response[id].coupon_about == null) ? false : true;
			let extraInfo = (coupon && couponAbout) ? `
				<hr/>
		        <h3>Coupon Code:</h3>
		        <p><span class="label label-rouded label-info pull-right">${response[id].coupon}</span>
		        <span class=" pull-left">${response[id].coupon_about}</span></p>
		    ` : '';
			let html = `
		        <div class="panel panel-default">
                    <div class="panel-heading">${response[id].company_name}</div>
                    <div class="panel-wrapper collapse in">
                        <div class="panel-body">
                            <div class="overlay-box">
                                <div class="user-content">
                                    <a href="javascript:void(0)"><img src="${response[id].logo}" class="thumb-lg img-circle" alt="img"></a>
                                    <h4 class="text-white">${response[id].company_name}</h4>
                                    <h5 class="text-white"><a href="${response[id].website}" target="_blank">Website</a></h5>
                                </div>
                            </div>
                            <hr/>
                            <div class="text-left">${response[id].about} ${extraInfo}</div>
                        </div>
                    </div>
                </div>
		    `;
			swal({
				content: createElementFromHTML(html),
				buttons: false,
				className: 'bg-org'
			})

		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
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
                                        <div class="mail-content">
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
        var sponsorAboutModal = (v.about) ? 'onclick="sponsorDetails(\''+i+'\');sponsorAnalytics(\''+v.company_name+'\');"' : 'onclick="window.open(\''+ v.website +'\', \'_blank\');sponsorAnalytics(\''+v.company_name+'\');"';
        sponsors += `
            <!-- /.usercard -->
            <div class="item lazyload recent-sponsor mouse imageSource mouse" ${sponsorAboutModal} data-src="${v.logo}" data-id="${i}">
                <span class="elip recent-title">${v.company_name}</span>
                ${hasCoupon}
            </div>
            <!-- /.usercard-->
        `;
    });
    sponsors += `
        <!-- /.usercard -->
        <div class="item lazyload recent-sponsor mouse imageSource mouse" onclick="window.open('https://www.patreon.com/bePatron?c=1320444&rid=2874514', '_blank')" data-src="plugins/images/sponsor-patreon.png">
            <span class="elip recent-title" lang="en">Patreon Sponsor</span>
        </div>
        <div class="item lazyload recent-sponsor mouse imageSource mouse" onclick="window.open('https://opencollective.com/organizr', '_blank')" data-src="plugins/images/sponsor-open-collective.png">
            <span class="elip recent-title" lang="en">OpenCollective Sponsor</span>
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
                let result = $.parseJSON(xhr.responseText);
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
                let result = $.parseJSON(xhr.responseText);
            }
        }
    });
}
function getOrganizrBackups(){
	organizrAPI2('GET','api/v2/backup').success(function(data) {
		try {
			let json = data.response;
			$('#backup-file-list').html(buildOrganizrBackups(json.data));
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function createOrganizrBackup(){
	$('#settings-settings-backup').block({
		message: '<p style="margin:0;padding:8px;font-size:24px;" lang="en">Backing up...</p>',
		css: {
			color: '#fff',
			border: '1px solid #5761a9',
			backgroundColor: '#707cd2'
		}
	});
	organizrAPI2('POST','api/v2/backup',{}).success(function(data) {
		try {
			let response = data.response;
			if(response){
				getOrganizrBackups();
			}
		}catch(e) {
			organizrCatchError(e,data);
		}
		$('#settings-settings-backup').unblock();
	}).fail(function(xhr) {
		$('#settings-settings-backup').unblock();
		OrganizrApiError(xhr, 'Backup Error');
	});
}
function buildOrganizrBackups(array){
	let list =  '';
	if(array.total_files > 0) {
		$.each(array.files, function (i, v) {
			i++;
			let pattern = /\[[^\]]*\]/mg;
			let version = (typeof v.name.match(pattern)[1] !== 'undefined') ?  v.name.match(pattern)[1] : 'N/A';
			list += `
			<tr>
				<td>${i}</td>
				<td class="txt-oflo">${v.name}</td>
				<td><span class="label label-primary label-rouded">${version}</span> </td>
				<td class="txt-oflo">${v.size}</td>
				<td><span class="text-info tooltip-info" data-toggle="tooltip" data-placement="right" title="" data-original-title="${moment(v.date).format('LLL')}">${moment.utc(v.date, "YYYY-MM-DD hh:mm[Z]").local().fromNow()}</span></td>
				<td><span class="text-primary"><a href="api/v2/backup/${v.name}"><i class="fa fa-download download-backup" data-file="${v.name}"></i></a> | <a href="javascript:void(0)"><i class="fa fa-trash-o delete-backup" data-file="${v.name}"></i></a></span></td>
			</tr>
			`;
		});
	}else{
		list = '<tr><td class="text-center" colspan="6">No Backups made yet</td></tr>';
	}
	$('#backup-total-files').html(array.total_files);
	$('#backup-total-size').html(array.total_size);
	return list;
}
function updateBar(){
	return `
	<div class="white-box m-0">
        <div class="row">
            <div class="col-lg-12">
                <h3 id="update-title" class="box-title pull-left"></h3><h3 id="update-time" class="box-title pull-right hidden"><span id="update-seconds"></span>&nbsp;<span lang="en">Seconds</span></h3>
				<div class="clearfix"></div>
                <div class="progress progress-lg">
                    <div id="update-bar" class="progress-bar progress-bar-primary progress-bar-striped active" style="width: 0%;" role="progressbar">0%</div>
                </div>
            </div>
            <h6>If error occurs - Use Esc key to close modal</h6>
        </div>
    </div>
	`;
}
function showUpdateBar(){
	swal({
		content: createElementFromHTML(updateBar()),
		buttons: false,
		className: 'bg-org',
		closeOnClickOutside: false,
	})
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
	    showUpdateBar();
        updateUpdateBar('Starting Download','20%');
        messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'),window.lang.translate('Starting Update Process'),activeInfo.settings.notifications.position,'#FFF','success','60000');
        organizrAPI2('GET','api/v2/update/docker').success(function(data) {
            updateUpdateBar('Restarting Organizr in', '100%', true);
            messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'),'Update complete',activeInfo.settings.notifications.position,'#FFF','success','60000');
        }).fail(function(xhr) {
	        OrganizrApiError(xhr, 'Update Error');
        });
    }
}
function windowsUpdate(){
    if(activeInfo.serverOS == 'win'){
	    showUpdateBar();
        updateUpdateBar('Starting Download','20%');
        messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'),window.lang.translate('Starting Update Process'),activeInfo.settings.notifications.position,'#FFF','success','60000');
        organizrAPI2('GET','api/v2/update/windows').success(function(data) {
            updateUpdateBar('Restarting Organizr in', '100%', true);
            messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'),'Update complete',activeInfo.settings.notifications.position,'#FFF','success','60000');
        }).fail(function(xhr) {
	        OrganizrApiError(xhr, 'Update Error');
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
	organizrConsole('Update Function','Starting Update Process');
	showUpdateBar();
	updateUpdateBar('Starting Download','5%');
	messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'),window.lang.translate('Starting Update Process'),activeInfo.settings.notifications.position,'#FFF','success','60000');
	organizrAPI2('GET','api/v2/update/download/'+ activeInfo.branch).success(function(data) {
        updateUpdateBar('Starting Unzip', '50%');
        messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'), window.lang.translate('Update File Downloaded'), activeInfo.settings.notifications.position, '#FFF', 'success', '60000');
		organizrAPI2('GET','api/v2/update/unzip/'+ activeInfo.branch).success(function(data) {
            updateUpdateBar('Starting Copy', '70%');
            messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'), window.lang.translate('Update File Unzipped'), activeInfo.settings.notifications.position, '#FFF', 'success', '60000');
			organizrAPI2('GET','api/v2/update/move/'+ activeInfo.branch).success(function(data) {
                updateUpdateBar('Starting Cleanup', '90%');
                messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'), window.lang.translate('Update Files Copied'), activeInfo.settings.notifications.position, '#FFF', 'success', '60000');
				organizrAPI2('GET','api/v2/update/cleanup/'+ activeInfo.branch).success(function(data) {
                    updateUpdateBar('Restarting Organizr in', '100%', true);
                    messageSingle(window.lang.translate('[DO NOT CLOSE WINDOW]'), window.lang.translate('Update Cleanup Finished'), activeInfo.settings.notifications.position, '#FFF', 'success', '60000');
                }).fail(function (xhr) {
					OrganizrApiError(xhr, 'Update Error');
                });
            }).fail(function (xhr) {
				OrganizrApiError(xhr, 'Update Error');
            });
        }).fail(function (xhr) {
			OrganizrApiError(xhr, 'Update Error');
        });
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Update Error');
	});
}
function settingsAPI2(post, callbacks=null, asyncValue=true){
	organizrAPI2('POST',post.api,post.data,asyncValue).success(function(data) {
		try {
			var response = JSON.parse(data);
		}catch(e) {
			organizrCatchError(e,data);
		}
		message(post.messageTitle,post.messageBody,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		console.error(post.error);
	});
}
$.xhrPool.abortAll = function(url) {
	$(this).each(function(i, jqXHR) { //  cycle through list of recorded connection
		if (!url || url === jqXHR.requestURL) {
			organizrConsole('Organizr API Abort',jqXHR.requestURL,'info');
			jqXHR.abort(); //  aborts connection
			$.xhrPool.splice(i, 1); //  removes from list by index
		}
	});
};
$.ajaxPrefilter(function(options, originalOptions, jqXHR) {
	//organizrConsole('Organizr API Function',options.url,'info');
	jqXHR.requestURL = options.url;
});
function organizrAPI2(type,path,data=null,asyncValue=true){
	$.xhrPool.abortAll(path);
	var timeout = 10000;
	switch(path){
		case 'api/v2/update/windows':
		case 'api/v2/update/docker':
		case 'api/v2/login':
			timeout = 240000;
			break;
		default:
			timeout = 60000;
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
					$.xhrPool.push(request);
				},
				complete: function(jqXHR) {
					var i = $.xhrPool.indexOf(jqXHR); //  get index for current connection completed
					if (i > -1) $.xhrPool.splice(i, 1); //  removes from list by index
				},
				timeout: timeout,
			});
		case 'delete':
		case 'DELETE':
		case 'd':
			return $.ajax({
				url:path,
				method:"DELETE",
				beforeSend: function(request) {
					request.setRequestHeader("Token", activeInfo.token);
					request.setRequestHeader("formKey", local('g','formKey'));
					$.xhrPool.push(request);
				},
				complete: function(jqXHR) {
					var i = $.xhrPool.indexOf(jqXHR); //  get index for current connection completed
					if (i > -1) $.xhrPool.splice(i, 1); //  removes from list by index
				},
				timeout: timeout,
			});
		case 'post':
		case 'POST':
		case 'p':
			data.formKey = local('g','formKey');
			return $.ajax({
				url:path,
				method:"POST",
				async: asyncValue,
				beforeSend: function(request) {
					request.setRequestHeader("Token", activeInfo.token);
					request.setRequestHeader("formKey", local('g','formKey'));
					$.xhrPool.push(request);
				},
				complete: function(jqXHR) {
					var i = $.xhrPool.indexOf(jqXHR); //  get index for current connection completed
					if (i > -1) $.xhrPool.splice(i, 1); //  removes from list by index
				},
				data:data
			});
		case 'put':
		case 'PUT':
			data.formKey = local('g','formKey');
			return $.ajax({
				url:path,
				method:"PUT",
				async: asyncValue,
				beforeSend: function(request) {
					request.setRequestHeader("Token", activeInfo.token);
					request.setRequestHeader("formKey", local('g','formKey'));
					$.xhrPool.push(request);
				},
				complete: function(jqXHR) {
					var i = $.xhrPool.indexOf(jqXHR); //  get index for current connection completed
					if (i > -1) $.xhrPool.splice(i, 1); //  removes from list by index
				},
				data:JSON.stringify(data),
				contentType: "application/json"
			});
		default:
			console.warn('Organizr API: Method Not Supported');
	}
}
function loadSettingsPage2(api,element,organizrFn){
    $(element).html('<h2 class="col-lg-12 text-center well"><i class="fa fa-spin fa-refresh"></i><br> Loading</h2>');
	organizrAPI2('get',api).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		organizrConsole('Organizr Function','Loading '+organizrFn);
		$(element).html(response.data);
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function loadInternal(url,tabName, split = null){
	let extra = split ? 'right-' : '';
	organizrAPI2('get',url).success(function(data) {
		try {
			var html = data.response;
			$('#internal-'+extra+tabName).html(html.data);
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function loadInternalOriginal(url,tabName){
	organizrAPI('get',url).success(function(data) {
		try {
			var html = JSON.parse(data);
		}catch(e) {
			organizrCatchError(e,data);
		}
		$('#internal-'+tabName).html(html.data);
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function loadSettingsPage(api,element,organizrFn){
	organizrAPI('get',api).success(function(data) {
		try {
			var response = JSON.parse(data);
		}catch(e) {
			organizrCatchError(e,data);
		}
		organizrConsole('Organizr Function','Loading '+organizrFn);
		$(element).html(response.data);
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function settingsAPI(post, callbacks=null, asyncValue=true){
	organizrAPI('POST',post.api,post,asyncValue).success(function(data) {
		try {
			var response = JSON.parse(data);
		}catch(e) {
			organizrCatchError(e,data);
		}
		message(post.messageTitle,post.messageBody,activeInfo.settings.notifications.position,"#FFF","success","5000");
		if(callbacks){ callbacks.fire(); }
	}).fail(function(xhr) {
		console.error(post.error);
	});
}
function organizrAPI(type,path,data=null,asyncValue=true){
	var timeout = 10000;
    switch(path){
        case 'api/?v1/windows/update':
        case 'api/?v1/docker/update':
            timeout = 120000;
            break;
        default:
            timeout = 60000;
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
				async: asyncValue,
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
        url: "js/icons.json",
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
	organizrAPI2('GET','api/v2/page/wizard').success(function(data) {
        try {
            var json = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
		organizrConsole('Organizr Function','Starting Install Wizard');
		$(json.data).appendTo($('.organizr-area'));
		$('.organizr-area').removeClass('hidden');
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Wiizard Error');
	});
	$("#preloader").fadeOut();
}
function buildDependencyCheck(orgdata){
	organizrAPI2('GET', 'api/v2/page/dependencies').success(function(data) {
        try {
            var json = data.response;
        }catch(e) {
	        organizrCatchError(e,data);
        }
		organizrConsole('Organizr Function','Starting Dependencies Check');
		$(json.data).appendTo($('.organizr-area'));
		$('.organizr-area').removeClass('hidden');
		$(buildBrowserInfo()).appendTo($('#browser-info'));
		$('#web-folder').html(buildWebFolder(orgdata));
		$('#php-version-check').html(buildPHPCheck(orgdata));
		$(buildDependencyInfo(orgdata)).appendTo($('#depenency-info'));
	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'Dependency Error');
	});
	$("#preloader").fadeOut();
}
function buildDependencyInfo(arrayItems){
	let listing = '';
	$.each(arrayItems.data.status.dependenciesActive, function(i,v) {
			listing += '<li class="depenency-item" data-name="'+v+'"><a href="javascript:void(0)"><i class="fa fa-check text-success"></i> '+v+'</a></li>';
		});
	$.each(arrayItems.data.status.dependenciesInactive, function(i,v) {
		listing += '<li class="depenency-item" data-name="'+v+'"><a href="javascript:void(0)"><i class="fa fa-close text-danger"><div class="notify"><span class="heartbit depend-heartbit"></span></div></i> '+v+'</a></li>';
	});

	let className = (arrayItems.data.status.dependenciesInactive.length !== 0) ? 'bg-danger text-warning' : 'bg-primary';
	let icon = (arrayItems.data.status.dependenciesInactive.length !== 0) ? 'fa fa-exclamation-triangle' : 'fa fa-check-circle';//dependency-dependencies-check-listing-header
	let header = (arrayItems.data.status.dependenciesInactive.length !== 0) ? 'panel-danger' : 'panel-info';
	let listingIcon = (arrayItems.data.status.dependenciesInactive.length !== 0) ? 'ti-alert' : 'ti-check-box';
	let listingText = (arrayItems.data.status.dependenciesInactive.length !== 0) ? 'Dependencies Missing' : 'Dependencies OK';

	$('.dependency-dependencies-check-listing-header').removeClass('panel-danger').addClass(header);
	$('.dependency-dependencies-check-listing i').first().removeClass('ti-alert').addClass(listingIcon);
	$('.dependency-dependencies-check-listing span').text(listingText);
	$('.dependency-dependencies-check').removeClass('bg-warning').addClass(className);
	$('.dependency-dependencies-check i').removeClass('fa fa-spin fa-spinner').addClass(icon);
	return listing;
}
function buildWebFolder(arrayItems){
	let writable = 'Not Writable - Please fix permissions';
	let className = 'bg-danger text-warning';
	let icon = 'fa fa-exclamation-triangle';
	if(arrayItems.data.status.writable == 'yes'){
		writable = 'Writable - All Good';
		className = 'bg-primary';
		icon = 'fa fa-check-circle';
	}
	$('.dependency-permissions-check').removeClass('bg-warning').addClass(className);
	$('.dependency-permissions-check i').removeClass('fa fa-spin fa-spinner').addClass(icon);
	$('#web-folder').addClass(className);
	return writable;
}
function buildPHPCheck(arrayItems){
	let phpTest = 'Upgrade PHP Version to 7.2+';
	let className = 'bg-danger text-warning';
	let icon = 'fa fa-exclamation-triangle';
	if(arrayItems.data.status.minVersion == 'yes'){
		phpTest = 'PHP Version Approved';
		className = 'bg-primary';
		icon = 'fa fa-check-circle';
	}
	$('.dependency-phpversion-check').removeClass('bg-warning').addClass(className);
	$('.dependency-phpversion-check i').removeClass('fa fa-spin fa-spinner').addClass(icon);
	$('#php-version-check').addClass(className);
	$('#php-version-check-user').html('<span lang="en">Webserver User</span>: ' + arrayItems.data.status.php_user)
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
function logIcon(type, label = false){
	type = type.toLowerCase();
	let info = {"color" : "info", "icon": "fa fa-check"};
	switch (type) {
		case "success":
			info.color = 'info';
			info.icon = 'fa fa-check';
			break;
		case "info":
			info.color = 'info';
			info.icon = 'mdi mdi-information';
			break;
		case "notice":
			info.color = 'inverse';
			info.icon = 'mdi mdi-information-variant';
			break;
		case "debug":
			info.color = 'primary';
			info.icon = 'mdi mdi-code-tags-check';
			break;
		case "warning":
			info.color = 'warning';
			info.icon = 'mdi mdi-alert-box';
			break;
		case "error":
			info.color = 'danger';
			info.icon = 'mdi mdi-alert-outline';
			break;
		case "critical":
			info.color = 'danger';
			info.icon = 'mdi mdi-alert';
			break;
		case "alert":
			info.color = 'danger';
			info.icon = 'mdi mdi-alert-octagon';
			break;
		case "emergency":
			info.color = 'danger';
			info.icon = 'mdi mdi-alert-octagram';
			break;
		default:
			info = {"color" : "info", "icon": "fa fa-check"};
			break;
	}
	if(label){
		return '<span class="label label-'+info.color+' log-label"> <i class="fa '+info.icon+' m-l-5 fa-fw"></i>&nbsp; <span lang="en" class="text-uppercase">'+type+'</span></span>';
	}else{
		return '<button class="btn btn-xs btn-'+info.color+' log-label no-mouse" type="button"><span class="btn-label pull-left"><i class="'+info.icon+' fa-fw"></i></span><span class="text-uppercase" lang="en">'+type+'</span></button>';
	}
}
function toggleKillOrganizrLiveUpdate(interval = 5000){
	if($('.organizr-log-live-update').hasClass('kill-organizr-log')){
		clearTimeout(timeouts['organizr-log']);
		$('.organizr-log-live-update i').toggleClass('fa-dot-circle-o animated loop-animation swing');
		$('.organizr-log-live-update').toggleClass('kill-organizr-log');
	}else{
		$('.organizr-log-live-update').toggleClass('kill-organizr-log');
		organizrLogLiveUpdate(interval);
	}
}
function organizrLogLiveUpdate(interval = 5000){
	var timeout = interval;
	let timeoutTitle = 'organizr-log';
	$('.organizr-log-live-update i').toggleClass('fa-dot-circle-o animated loop-animation swing');
	organizrLogTable.ajax.reload(null, false);
	setTimeout(function(){ if($('.organizr-log-live-update').hasClass('kill-organizr-log')){ $('.organizr-log-live-update i').toggleClass('fa-dot-circle-o animated loop-animation swing'); } }, interval - 500);
	if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
	timeouts[timeoutTitle] = setTimeout(function(){ organizrLogLiveUpdate(timeout); }, timeout);
	delete timeout;
}
function radioLoop(element){
	$('[type=radio][id!="'+element.id+'"]').each(function() { this.checked=false });
}
function loadAppearance(appearance){
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
			.label-info,
			.tabs-style-iconbox nav ul li.tab-current a,
			.swapLog.active {
			    background-color: `+appearance.accentColor+` !important;
			}
			.panel-blue .panel-heading, .panel-info .panel-heading {
			    border-color: `+appearance.accentColor+`;
			}
			.tabs-style-iconbox nav ul li.tab-current a::after {
				border-top-color: `+appearance.accentColor+`;
			}
			.customvtab .tabs-vertical li.active a,
			.customvtab .tabs-vertical li.active a:focus,
			.customvtab .tabs-vertical li.active a:hover {
				border-right: 2px solid `+appearance.accentColor+`;
			}
			.text-info,
			.btn-link, a {
			    color: `+appearance.accentColor+`;
			}
		`;
	}
	if(appearance.accentTextColor !== ''){
		cssSettings += `
			.bg-info,
			.progress-bar,
			.panel-default .panel-heading,
			.mailbox-widget .customtab li.active a, .mailbox-widget .customtab li.active, .mailbox-widget .customtab li.active a:focus,
			.mailbox-widget .customtab li a,
			.tabs-style-iconbox nav ul li.tab-current a
			.swapLog.active {
				color: `+appearance.accentTextColor+`;
			}
		`;
	}
	if(appearance.buttonColor !== ''){
		cssSettings += `
			.btn-info, .btn-info.disabled,
			.btn,
			.paginate_button.current,
			.paginate_button:hover {
				background: `+appearance.buttonColor+` !important;
				border: 1px solid `+appearance.buttonColor+` !important;
			}
		`;
	}
	if(appearance.buttonTextColor !== ''){
		cssSettings += `
			.btn-info, .btn-info.disabled,
			.btn
			.paginate_button.current
			.paginate_button:hover {
				color: `+appearance.buttonTextColor+` !important;
			}
		`;
	}
	if(appearance.loginWallpaper !== ''){
		cssSettings += `
		    .login-register {
			    background: url(`+randomCSV(appearance.loginWallpaper)+`) center center/cover no-repeat!important;
			    height: 100%;
			    position: fixed;
		    }
			.lock-screen {
				background: url(`+randomCSV(appearance.loginWallpaper)+`) center center/cover no-repeat!important;
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
	if(activeInfo['settings']['misc']['autoExpandNavBar'] == false){
		cssSettings += `
			@media only screen and (min-width: 768px) {
				.sidebar:hover .hide-menu {
					display: none;
				}
				.sidebar:hover .sidebar-head,
				.sidebar:hover {
					width: 60px;
				}
				.sidebar:hover .nav-second-level li a {
					padding-left: 15px;
				}
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
function resetCustomColors(){
	let colors = ['headerColor','headerTextColor','sidebarColor','sidebarTextColor','accentColor','accentTextColor','buttonColor','buttonTextColor'];
	$.each(colors, function(i,v) {
		$('#customize-appearance-form [name='+v+']').val('').trigger('change');
	});
	messageSingle(window.lang.translate('Colors Reverted'),window.lang.translate('Please Save'),activeInfo.settings.notifications.position,'#FFF','success','10000');
}
function randomCSV(values){
    if(typeof values == 'string'){
        if(values.includes(',')){
            var csv = values.split(',');
            var luckyNumber = Math.floor(Math.random() * csv.length);
            return csv[luckyNumber];
        }else{
            return values;
        }
    }
    return false;
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

	if($.urlParam('error') !== null && !isNaN(Number($.urlParam('error')))){
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
            window.location.href = decodeURIComponent(decodeURI(redirect));
        }
    }
}
function changeTheme(theme){
	//$("#preloader").fadeIn();
	$('#theme').attr({
        href: 'css/themes/' + theme + '.css?v='+activeInfo.version
    });
	//$("#preloader").fadeOut();
	console.info("%c Theme %c ".concat(theme, " "), "color: white; background: #AD80FD; font-weight: 700;", "color: #AD80FD; background: white; font-weight: 700;");
}
function changeStyle(style){
	//$("#preloader").fadeIn();
	$('#style').attr({
        href: 'css/' + style + '.min.css?v='+activeInfo.version
    });
	//$("#preloader").fadeOut();
	console.info("%c Style %c ".concat(style, " "), "color: white; background: #AD80FD; font-weight: 700;", "color: #AD80FD; background: white; font-weight: 700;");
}
function setSSO(){
	$.each(activeInfo.sso, function(i,v) {
		if(v !== false){
			local('set', i, v);
		}else{
			local('r', i);
		}
	});
	// other items to remove
	$.each(localStorage, function(i,v) {
		if(typeof v == 'string'){
			if(i.startsWith('user-')){
				if(typeof activeInfo.sso[i] == 'undefined'){
					local('r', i);
				}
			}
		}
	});
}
function buildStreamItem(array,source){
	var cards = '';
	var count = 0;
	var total = array.length;
    var sourceIcon = (source === 'jellyfin') ? 'fish' : source;
    var streamDetails = {
        direct: 0,
        transcode: 0
    };
    var bandwidthDetails = {
        wan: 0,
        lan: 0
    };
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
            streamDetails['direct'] = streamDetails['direct'] + 1;
		}else{
			var userStream = v.userStream.stream;
			var userVideo = v.userStream.videoDecision+' ('+v.userStream.sourceVideoCodec+' <i class="mdi mdi-ray-start-arrow"></i> '+v.userStream.videoCodec+' '+v.userStream.videoResolution+')';
			var userAudio = v.userStream.audioDecision+' ('+v.userStream.sourceAudioCodec+' <i class="mdi mdi-ray-start-arrow"></i> '+v.userStream.audioCodec+')';
            streamDetails['transcode'] = streamDetails['transcode'] + 1;

		}
		var streamInfo = '';
		streamInfo += `<div class="text-muted m-t-20 text-uppercase"><span class="text-uppercase"><i class="mdi mdi-play-circle-outline"></i> Stream: `+userStream+`</span></div>`;
		streamInfo += (v.userStream.videoResolution) ? `<div class="text-muted m-t-20 text-uppercase"><span class="text-uppercase"><i class="mdi mdi-video"></i> Video: `+userVideo+`</span></div>` : '';
		streamInfo += `<div class="text-muted m-t-20 text-uppercase"><span class="text-uppercase"><i class="mdi mdi-speaker"></i> Audio: `+userAudio+`</span></div>`;
		v.session = v.session.replace(/[\W_]+/g,"-");
        bandwidthDetails[v.bandwidthType] = bandwidthDetails[v.bandwidthType] + parseFloat(v.bandwidth);
		cards += `
		<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-xs-12 nowPlayingItem">
			<div class="white-box">
				<div class="el-card-item p-b-10">
					<div class="el-card-avatar el-overlay-1 m-b-0">`+bg+`<img class="imageSource" style="width:`+width+`%;margin-left: auto;margin-right: auto;" src="`+v.nowPlayingImageURL+`">
						<div class="el-overlay">
							<ul class="el-info p-t-20 m-t-20">
								<li><a class="btn b-none inline-popups" href="#`+v.session+`" data-effect="mfp-zoom-out"><i class="mdi mdi-server-network mdi-24px"></i></a></li>
								<li><a class="btn b-none metadata-get" data-source="`+source+`" data-key="`+v.metadataKey+`" data-uid="`+v.uid+`"><i class="mdi mdi-information mdi-24px"></i></a></li>
								<li><a class="btn b-none openTab" data-tab-name="`+v.tabName+`" data-type="`+v.type+`" data-open-tab="`+v.openTab+`" data-url="`+v.address+`" href="javascript:void(0);"><i class=" mdi mdi-`+sourceIcon+` mdi-24px"></i></a></li>
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
						<small class="pull-left p-l-10 w-50 elip"><span class="pull-left"><i class="`+icon+` fa-fw text-info"></i>`+v.nowPlayingBottom+`</span></small>
						<small class="pull-right p-r-10 w-50"><span class="pull-right"><span class="">`+v.user+` <i class="icon-user"></i></span></span></small>
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
    cards += buildStreamTooltip(bandwidthDetails, streamDetails, source);
	return cards;
}
function buildStreamTooltip(bandwidth, streams, type){
    var html = '';
    var streamText = 'Streams: ';
    var bandwidthText = ' | Bandwidth: ';
    var bandwidthTotal = parseFloat(bandwidth['wan']) + parseFloat(bandwidth['lan']);
    if(type !== 'plex'){
        bandwidthText += (parseFloat(bandwidth['wan']) / 1000).toFixed(1) + ' Mbps';
    }else{
        bandwidthText += (parseFloat(bandwidthTotal) / 1000).toFixed(1) + ' Mbps';
        if(bandwidth['wan'] !== 0){
            bandwidthText += ' | WAN: ' + (parseFloat(bandwidth['wan']) / 1000).toFixed(1) + ' Mbps';
        }
        if(bandwidth['lan'] !== 0){
            bandwidthText += ' | LAN: ' + (parseFloat(bandwidth['lan']) / 1000).toFixed(1) + ' Mbps';
        }

    }
    var spacer = '';
    if(streams['direct'] !== 0){
        streamText += streams['direct']  + ' Direct Play(s)';
        spacer = ' & '
    }
    if(streams['transcode'] !== 0){
        streamText += spacer + streams['transcode']  + ' Transcode(s)';
    }
    html += '<span class="label label-info m-l-20 mouse" title="" data-toggle="tooltip" data-original-title="'+ streamText + bandwidthText +'" data-placement="bottom"><i class="fa fa-info"></i></span>';
    return `
    <script>$('.streamDetails-`+type+`').html('`+html+`');$('[data-toggle="tooltip"]').tooltip();</script>
    `;
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
				extra = `<li><a class="mouse" onclick="requestActions('`+id+`', 'deny', '`+type+`');" lang="en">Deny</a></li>`;
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
	return (action) ? `<li><a class="mouse" onclick="requestActions('`+id+`', '`+action+`', '`+type+`');" lang="en">`+text+`</a></li>`+extra : '';
}
function buildRequestItem(array, extra=null){
	var items = '';
	let service = activeInfo.settings.homepage.requests.service;
	$.each(array, function(i,v) {
			if(extra == null){
                var approveID = (v.type == 'tv' && service === 'ombi') ? v.id : v.request_id;
                var iconType = (v.type == 'tv') ? 'fa-tv ' : 'fa-film';
				var badge = '';
				var badge2 = '';
				var bg = (v.background.includes('.')) ? v.background : 'plugins/images/cache/no-np.png';
				v.user = (activeInfo.settings.homepage.ombi.alias && service === 'ombi') || (activeInfo.settings.homepage.overseerr.enabled && service === 'overseerr') ? v.userAlias : v.user;
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
						<li><a class="mouse" onclick="requestActions('`+v.request_id+`', 'delete', '`+v.type+`');" lang="en">Delete</a></li>
                    </ul>
                </div>`;
				adminFunctions = (activeInfo.user.groupID <= 1) ? adminFunctions : '';
				var user = (activeInfo.user.groupID <= 1) ? '<span lang="en">Requested By:</span> '+v.user : '';
				var user2 = (activeInfo.user.groupID <= 1) ? '<br>'+v.user : '';
				var divId = (v.type == 'movie') ? v.request_id : v.id;
				items += `
				<div class="item lazyload recent-poster request-item request-`+v.type+` `+className+` request-`+divId+`-div mouse" data-target="request-`+v.id+`" data-src="`+v.poster+`">
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
	var originalType = type;
    //type = (type === 'emby' && activeInfo.settings.homepage.media.jellyfin) ? 'jellyfin' : type;
	return (streams) ? `
	<div id="`+type+`Streams">
		<div class="el-element-overlay row">
		    <div class="col-md-12">
		        <h4 class="pull-left homepage-element-title"><span lang="en">Active</span> `+toUpper(type)+` <span lang="en">Streams</span> : </h4><h4 class="pull-left">&nbsp;<span class="label label-info m-l-20 checkbox-circle mouse" onclick="homepageStream('`+originalType+`')">`+streams+`</span><span class="streamDetails-`+type+`"></span></h4>
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
	var refreshType = type;
	//type = (type === 'emby' && activeInfo.settings.homepage.media.jellyfin) ? 'jellyfin' : type;
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
			<h4 class="pull-left homepage-element-title"><span class="mouse" onclick="homepageRecent('`+type+`')" lang="en">Recently Added</span> : </h4><h4 class="pull-left">&nbsp;</h4>
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
	var playlist = (typeof array.content !== 'undefined') ? Object.keys(array.content).length : false;
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
			<h4 class="pull-left homepage-element-title"><span onclick="homepagePlaylist('`+type+`')" class="`+type+`-playlistTitle mouse">`+first+`</span> : </h4><h4 class="pull-left">&nbsp;</h4>
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
			<span class="pull-left m-t-5 mouse homepage-element-title" onclick="homepagePlaylist('`+type+`')"><img class="lazyload homepageImageTitle" data-src="plugins/images/tabs/`+type+`.png"> &nbsp; <span class="`+type+`-playlistTitle">`+first+`</span></span>
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
function buildRequest(service, div, array){
	var requests = (typeof array.content !== 'undefined');
	var dropdown = '';
	var headerAlt = '';
	var header = '';
	var requestButton = (activeInfo['settings']['homepage'][service]['enabled'] === true) ? `<button href="#new-request" id="newRequestButton" class="btn btn-info waves-effect waves-light inline-popups" data-effect="mfp-zoom-out"><i class="fa fa-search m-l-5"></i></button>` : '';
	if(requests){
		var builtDropdown = `
		<button type="button" class="btn btn-info waves-effect hidden-xs" onclick="owlChange('request-items-${service}','previous');"><i class="fa fa-chevron-left"></i></button>
		<button type="button" class="btn btn-info waves-effect hidden-xs" onclick="owlChange('request-items-${service}','next');"><i class="fa fa-chevron-right"></i></button>
		<button aria-expanded="false" data-toggle="dropdown" class="btn btn-info dropdown-toggle waves-effect waves-light" type="button">
			<i class="fa fa-filter m-r-5"></i><span class="caret"></span>
		</button>
		`+requestButton+`
		<div role="menu" class="dropdown-menu request-filter">
			<div class="checkbox checkbox-success m-l-20 checkbox-circle">
				<input id="request-filter-available-${service}" data-filter="request-available" class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-available-${service}"> <span lang="en">Available</span> </label>
			</div>
			<div class="checkbox checkbox-danger m-l-20 checkbox-circle">
				<input id="request-filter-unavailable-${service}" data-filter="request-unavailable"  class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-unavailable-${service}"> <span lang="en">Unavailable</span> </label>
			</div>
			<div class="checkbox checkbox-info m-l-20 checkbox-circle">
				<input id="request-filter-approved-${service}" data-filter="request-approved" class="filter-request-input" type="checkbox"  checked="">
				<label for="request-filter-approved-${service}"> <span lang="en">Approved</span> </label>
			</div>
			<div class="checkbox checkbox-warning m-l-20 checkbox-circle">
				<input id="request-filter-unapproved-${service}" data-filter="request-unapproved" class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-unapproved-${service}"> <span lang="en">Unapproved</span> </label>
			</div>
			<div class="checkbox checkbox-purple m-l-20 checkbox-circle">
				<input id="request-filter-denied-${service}" data-filter="request-denied" class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-denied-${service}"> <span lang="en">Denied</span> </label>
			</div>
			<div class="checkbox checkbox-inverse m-l-20 checkbox-circle">
				<input id="request-filter-movie-${service}" data-filter="request-movie" class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-movie-${service}"> <span lang="en">Movie</span> </label>
			</div>
			<div class="checkbox checkbox-inverse m-l-20 checkbox-circle">
				<input id="request-filter-tv-${service}" data-filter="request-tv" class="filter-request-input" type="checkbox" checked="">
				<label for="request-filter-tv-${service}"> <span lang="en">TV</span> </label>
			</div>
		</div>

		`;
	}
	if(activeInfo.settings.homepage.options.alternateHomepageHeaders){
		var headerAlt = `
		<div class="col-md-12">
			<h4 class="pull-left homepage-element-title"><span class="mouse" onclick="homepageRequests('${service}')" lang="en">Requests</span> : </h4><h4 class="pull-left">&nbsp;</h4>
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
			<span class="pull-left m-t-5 mouse homepage-element-title" onclick="homepageRequests('${service}')"><img class="lazyload homepageImageTitle" data-src="plugins/images/tabs/`+service+`.png"> &nbsp; <span lang="en">Requests</span></span>
			<div class="btn-group pull-right">
					`+builtDropdown+`
			</div>
			<div class="clearfix"></div>
		</div>
		`;
	}
	return (requests) ? `
	<div id="${service}-requests" class="row">
		`+headerAlt+`
        <div class="col-lg-12">
            <div class="panel panel-default">
				`+header+`
                <div class="panel-wrapper p-b-0 collapse in">
				<div class="owl-carousel owl-theme request-items-`+service+`">
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
						<input id="request-input" lang="en" placeholder="Request a Show or Movie" type="text" class="form-control inline-focus">
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
    let previousEnabled = (pagePrevious !== 0);
    let nextEnabled = (pageNext <= totalPages);
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
        let previousOnclick = previousEnabled ? `onclick="requestList('${list}', '${media_type}', '${pagePrevious}')";` : ``;
        let nextOnclick = nextEnabled ? `onclick="requestList('${list}', '${media_type}', '${pageNext}')";` : ``;
		next = `
		<div class="clearfix"></div>
		<div class="button-box text-center p-b-0">
            <ul class="pagination m-b-0">
                <li class="`+previousHidden+`"> <a href="javascript:void(0)" ${previousOnclick}><i class="fa fa-angle-left"></i></a> </li>
                `+pageList+`
                <li class="`+nextHidden+`"> <a href="javascript:void(0)" ${nextOnclick}><i class="fa fa-angle-right"></i></a> </li>
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
function buildRequestOverseerrSeasons(array){
	var hasSeasons = (typeof array.data.seasons !== 'undefined');
	if(hasSeasons){
		let seasons = array.data.seasons;
		let id = array.data.id;
		let SeasonItems = '';
		$.each(seasons, function(i,v) {
			if(v.seasonNumber !== 0) {
				SeasonItems += `
					<tr>
						<td><input type="checkbox" name="overseerr-season-${v.seasonNumber}" class="js-switch overseerr-season" data-seasonNumber="${v.seasonNumber}" data-color="#6164c1" data-size="small" /></td>
						<td>${v.name}</td>
						<td>${v.episodeCount}</td>
					</tr>
				`;
			}
		});
		let html = `
			<div class="panel">
				<div class="bg-org2">
					<div class="panel-heading">Choose Seasons</div>
					<div class="panel-wrapper collapse in text-left">
						<div class="table-responsive">
							<table class="table color-bordered-table primary-bordered-table">
								<thead>
									<tr>
										<th width="20"><input type="checkbox" class="js-switch select-all-overseerr-seasons" data-color="#6164c1" data-size="small" /></th>
										<th lang="en">Season</th>
										<th lang="en"># Of Episodes</th>
									</tr>
								</thead>
								<tbody>${SeasonItems}</tbody>
							</table>
						</div>
						<div class="pull-right p-b-20">
							<button class="fcbtn btn btn-info btn-outline btn-1c" lang="en" onclick="swal.close();">Cancel</button>
							<button class="fcbtn btn btn-success btn-outline btn-1c submit-overseerr-seasons" lang="en" data-seasons="[]" data-id="${id}" disabled onclick="processOverseerrSeasons(this)">Request Seasons</button>
						</div>
					</div>
				</div>
			</div>
			`;
		swal({
			content: createElementFromHTML(html),
			button: null,
			className: 'bg-org',
			dangerMode: false
		});
	}
}

function processOverseerrSeasons(el){
	let seasons = $(el).attr('data-seasons');
	let id = $(el).attr('data-id');
	overseerrActions(id,'add','tv', seasons);
}
function processRequest(id,type){
	let service = activeInfo.settings.homepage.requests.service;
	switch (service) {
		case 'ombi':
			requestActions(id,'add',type);
			return false;
		case 'overseerr':
			if(type  === 'tv' && activeInfo.settings.homepage.overseerr.userSelectTv === true){
				organizrAPI2('GET','api/v2/homepage/overseerr/metadata/' + type + '/' + id).success(function(data) {
					try {
						let response = data.response;
						buildRequestOverseerrSeasons(response);
					}catch(e) {
						organizrCatchError(e,data);
					}
				}).fail(function(xhr) {
					OrganizrApiError(xhr, 'Overseerr Error');
				});
			}else{
				requestActions(id,'add',type);
			}
			return false;
		default:
			organizrConsole('Request Function','Service for Processing not setup', 'error');
			return false;
	}
}
function requestActions(id = null, action = null, type = null, extra = null){
	let service = activeInfo.settings.homepage.requests.service;
	switch (service) {
		case 'ombi':
			ombiActions(id,action,type,extra);
			break;
		case 'overseerr':
			overseerrActions(id,action,type,extra);
			break;
		default:
			organizrConsole('Request Function','Service for Request not setup', 'error');
			return false;
	}
}
//Overseerr Actions
function overseerrActions(id, action, type = null, extra = null){
	ajaxloader('.request-' + id + '-div', 'in');
	ajaxloader('.preloader-' + id, 'in');
	//$.magnificPopup.close();
	messageSingle(window.lang.translate('Submitting Action to Overseerr'),'',activeInfo.settings.notifications.position,"#FFF",'success',"10000");
	switch (action){
		case 'add':
			let seasons = (extra !== null) ? '/' + extra : '';
			var method = 'POST';
			var apiUrl = 'api/v2/homepage/overseerr/requests/'+type+'/' + id + seasons;
			var data = {};
			break;
		case 'available':
		case 'pending':
		case 'unavailable':
		case 'approve':
			var method = 'POST';
			var apiUrl = 'api/v2/homepage/overseerr/requests/'+type+'/' + id + '/' + action;
			var data = {};
			break;
		case 'deny':
			var method = 'PUT';
			var apiUrl = 'api/v2/homepage/overseerr/requests/'+type+'/' + id + '/' + action;
			var data = {};
			break;
		case 'delete':
			var method = 'DELETE';
			var apiUrl = 'api/v2/homepage/overseerr/requests/'+type+'/' + id;
			var data = {};
			break;
		default:
			return false;
	}
	organizrAPI2(method,apiUrl,data).success(function(data) {
		try {
			let response = data.response;
			if(action == 'add'){
				addTempRequest();
				setTimeout(function(){
						ajaxloader();
					}, 2000
				);
			}
			messageSingle(response.message,'',activeInfo.settings.notifications.position,"#FFF","success","5000");
			homepageRequests('overseerr');
			cleanCloseSwal();
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		ajaxloader();
		OrganizrApiError(xhr, 'Overseerr Error');
	});
}
//Ombi actions
function ombiActions(id, action, type, extra = null){
	var msg = (activeInfo.user.groupID <= 1) ? '<a href="https://github.com/tidusjar/Ombi/issues/2176" target="_blank">Not Org Fault - Ask Ombi</a>' : 'Connection Error to Request Server';
	ajaxloader('.request-' + id + '-div', 'in');
	ajaxloader('.preloader-' + id, 'in');
    //$.magnificPopup.close();
    messageSingle(window.lang.translate('Submitting Action to Ombi'),'',activeInfo.settings.notifications.position,"#FFF",'success',"10000");
    switch (action){
	    case 'add':
	    	var method = 'POST';
	    	var apiUrl = 'api/v2/homepage/ombi/requests/'+type+'/' + id;
	    	var data = {};
	    	break;
	    case 'available':
	    case 'unavailable':
	    case 'approve':
		    var method = 'POST';
		    var apiUrl = 'api/v2/homepage/ombi/requests/'+type+'/' + id + '/' + action;
		    var data = {};
		    break;
	    case 'deny':
		    var method = 'PUT';
		    var apiUrl = 'api/v2/homepage/ombi/requests/'+type+'/' + id + '/' + action;
		    var data = {};
		    break;
	    case 'delete':
		    var method = 'DELETE';
		    var apiUrl = 'api/v2/homepage/ombi/requests/'+type+'/' + id;
		    var data = {};
		    break;
	    default:
	    	return false;
    }
	organizrAPI2(method,apiUrl,data).success(function(data) {
        try {
            let response = data.response;
	        if(action == 'add'){
		        addTempRequest();
	        }
	        messageSingle(response.message,'',activeInfo.settings.notifications.position,"#FFF","success","5000");
	        homepageRequests('ombi');
	        ajaxloader();
        }catch(e) {
	        organizrCatchError(e,data);
        }
	}).fail(function(xhr) {
		ajaxloader();
		OrganizrApiError(xhr, 'Ombi Error');
	});
}

function addTempRequest(){
	let service = activeInfo.settings.homepage.requests.service;
	let html = `
	<div class="item lazyload recent-poster request-item request-adding  mouse" data-src="">
		<div class="outside-request-div">
			<div class="inside-over-request-div bg-danger"></div>
			<div class="inside-request-div bg-info"></div>
		</div>
		<div class="hover-homepage-item"></div>
		<span class="elip request-title-tv"><i class="fa fa-tv"></i></span>
		<span class="elip recent-title">Adding Request</span>
	</div>
	`;
	$('.request-items-' + service).trigger('add.owl', [html, 0]).trigger('refresh.owl');
	setTimeout(function(){
		ajaxloader('.request-adding', 'in');
		}, 100
	);
}
function cleanCloseSwal(){
	let state = swal.getState().isOpen;
	if(state === true){
		swal.close();
	}
}
function doneTyping () {
	let title = $('#request-input').val();
	if(title == ''){
		return false;
	}
	var page = ($('#request-page').val()) ? $('#request-page').val() : 1;
	if(typeof searchTerm !== 'undefined'){
		if(searchTerm !== $('#request-input').val()){
			page = 1;
		}
	}
	ajaxloader('.search-div', 'in');
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
		OrganizrApiError(xhr, 'TMDB Error');
		ajaxloader();
	});
}
function requestList(list, type, page=1) {
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
		OrganizrApiError(xhr, 'TMDB Error');
		ajaxloader();
	});
}
function buildDownloaderItem(array, source, type='none'){
    var queue = '';
    var count = 0;
    var history = '';
	switch (source) {
        case 'jdownloader':
            if(array.content === false){
                queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
                break;
            }

            if(array.content.queueItems.length == 0 && array.content.grabberItems.length == 0 && array.content.encryptedItems.length == 0 && array.content.offlineItems.length == 0){
                queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
            }else{
                if(array.content.$status[0] == 'RUNNING') {
                    queue += `
                        <tr><td>
                            <a href="#" onclick="return false;"><span class="downloader mouse" data-source="jdownloader" data-action="pause" data-target="main"><i class="fa fa-pause"></i></span></a>
                            <a href="#" onclick="return false;"><span class="downloader mouse" data-source="jdownloader" data-action="stop" data-target="main"><i class="fa fa-stop"></i></span></a>
                        </td></tr>
                        `;
                }else if(array.content.$status[0] == 'PAUSE'){
                    queue += `<tr><td><a href="#" onclick="return false;"><span class="downloader mouse" data-source="jdownloader" data-action="resume" data-target="main"><i class="fa fa-fast-forward"></i></span></a></td></tr>`;
                }else{
                    queue += `<tr><td><a href="#" onclick="return false;"><span class="downloader mouse" data-source="jdownloader" data-action="start" data-target="main"><i class="fa fa-play"></i></span></a></td></tr>`;
                }
                if(array.content.$status[1]) {
                    queue += `<tr><td><a href="#" onclick="return false;"><span class="downloader mouse" data-source="jdownloader" data-action="update" data-target="main"><i class="fa fa-globe"></i></span></a></td></tr>`;
                }
            }
            $.each(array.content.queueItems, function(i,v) {
                count = count + 1;
                if(v.speed == null){
                    v.speed = 'Stopped';
                }
                if(v.eta == null){
                    if(v.percentage == '100'){
                        v.speed = 'Completed';
                        v.eta = '--';
                    }else{
                        v.eta = '--';
                    }
                }
                if(v.enabled == null){
                    v.speed = 'Disabled';
                }
                queue += `
                <tr>
                    <td class="max-texts">`+v.name+`</td>
                    <td>`+v.speed+`</td>
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
            $.each(array.content.grabberItems, function(i,v) {
                count = count + 1;
                queue += `
                <tr>
                    <td class="max-texts">`+v.name+`</td>
                    <td>Online</td>
                    <td class="hidden-xs"> -- </td>
                    <td class="hidden-xs"> -- </td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: 0%;" role="progressbar">0%</div>
                        </div>
                    </td>
                </tr>
                `;
            });
            $.each(array.content.encryptedItems, function(i,v) {
                count = count + 1;
                queue += `
                <tr>
                    <td class="max-texts">`+v.name+`</td>
                    <td>Encrypted</td>
                    <td class="hidden-xs"> -- </td>
                    <td class="hidden-xs"> -- </td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: 0%;" role="progressbar">0%</div>
                        </div>
                    </td>
                </tr>
                `;
            });
            $.each(array.content.offlineItems, function(i,v) {
                count = count + 1;
                queue += `
                <tr>
                    <td class="max-texts">`+v.name+`</td>
                    <td>Offline</td>
                    <td class="hidden-xs"> -- </td>
                    <td class="hidden-xs"> -- </td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: 0%;" role="progressbar">0%</div>
                        </div>
                    </td>
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
                var state = `<a href="#" onclick="return false;"><span class="downloader mouse" data-source="sabnzbd" data-action="resume" data-target="main"><i class="fa fa-play"></i></span></a>`;
                var active = 'grayscale';
            }else{
                var state = `<a href="#" onclick="return false;"><span class="downloader mouse" data-source="sabnzbd" data-action="pause" data-target="main"><i class="fa fa-pause"></i></span></a>`;
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
                    <td class="hidden-xs sabnzbd-`+cleanClass(v.status)+`">`+v.status+`</td>
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
                    <td class="hidden-xs sabnzbd-`+cleanClass(v.status)+`">`+v.status+`</td>
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
                    <td class="hidden-xs nzbget-`+cleanClass(v.Status)+`">`+v.Status+`</td>
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
                    <td class="hidden-xs nzbget-`+cleanClass(v.Status)+`">`+v.Status+`</td>
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
            if(array.content.queueItems == 0){
                queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
            }
            $.each(array.content.queueItems, function(i,v) {
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
                    <td class="hidden-xs transmission-`+cleanClass(status)+`">`+status+`</td>
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
                    <td class="hidden-xs rtorrent-`+cleanClass(v.status)+`">`+v.status+`</td>
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
        case 'utorrent':
            if(array.content === false){
                queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
                break;
            }
            if(array.content.queueItems == 0){
                queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
            }
            $.each(array.content.queueItems, function(i,v) {
		count = count + 1;
                var upload = v.upSpeed !== '' ? humanFileSize(v.upSpeed,false) : "0 B";
                var download = v.downSpeed !== '' ? humanFileSize(v.downSpeed,false) : "0 B";
		var size = v.Size !== '' ? humanFileSize(v.Size,false) : "0 B";
                queue += `
                <tr>
                    <td class="max-texts"><span class="tooltip-info" data-toggle="tooltip" data-placement="right" title="">`+v.Name+`</span></td>
		    <td class="hidden-xs utorrent-`+cleanClass(v.Status)+`">`+v.Status+`</td>
                    <td class="hidden-xs"><span class="label label-info">`+v.Labels+`</span></td>
		    <td class="hidden-xs"><span class="tooltip-info" data-toggle="tooltip" data-placement="right" title="" data-original-title="`+download+`"><i class="fa fa-download"></i>&nbsp;`+download+`</span></td>
                    <td class="hidden-xs"><span class="tooltip-info" data-toggle="tooltip" data-placement="right" title="" data-original-title="`+upload+`"><i class="fa fa-upload"></i>&nbsp;`+upload+`</span></td>
		    <td class="hidden-xs">`+size+`</td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: `+v.Percent+`;" role="progressbar">`+v.Percent+`</div>
                        </div>
                    </td>
                </tr>
                `;
            });
            break;
		case 'sonarr':
			if(array.content === false){
				queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
				break;
			}
			if(array.content.queueItems == 0){
				queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
				break;
			}
			if(array.content.queueItems.records == 0){
				queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
				break;
			}
            let sonarrQueueSet = (typeof array.content.queueItems.records == 'undefined') ? array.content.queueItems : array.content.queueItems.records;
			$.each(sonarrQueueSet, function(i,v) {
				count = count + 1;
				var percent = Math.floor(((v.size - v.sizeleft) / v.size) * 100);
				percent = (isNaN(percent)) ? '0' : percent;
				var size = v.size != -1 ? humanFileSize(v.size,false) : "?";
                v.name = (typeof v.series == 'undefined') ? v.title : v.series.title;
				queue += `
                <tr>
                    <td class="">`+v.name+`</td>
                    <td class="">S`+pad(v.episode.seasonNumber,2)+`E`+pad(v.episode.episodeNumber,2)+`</td>
                    <td class="max-texts">`+v.episode.title+`</td>
                    <td class="hidden-xs sonarr-`+cleanClass(v.status)+`">`+v.status+`</td>
                    <td class="hidden-xs">`+size+`</td>
                    <td class="hidden-xs"><span class="label label-info">`+v.protocol+`</span></td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: `+percent+`%;" role="progressbar">`+percent+`%</div>
                        </div>
                    </td>
                </tr>
                `;
			});
			break;
		case 'radarr':
			if(array.content === false){
				queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
				break;
			}
			if(array.content.queueItems == 0){
				queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
				break;
			}
			if(array.content.queueItems.records == 0){
				queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
				break;
			}
			let queueSet = (typeof array.content.queueItems.records == 'undefined') ? array.content.queueItems : array.content.queueItems.records;
			$.each(queueSet, function(i,v) {
				count = count + 1;
				var percent = Math.floor(((v.size - v.sizeleft) / v.size) * 100);
				percent = (isNaN(percent)) ? '0' : percent;
				var size = v.size != -1 ? humanFileSize(v.size, false) : "?";
				v.name = (typeof v.movie == 'undefined') ? v.title : v.movie.title;
				queue += `
                <tr>
                    <td class="max-texts">${v.name}</td>
                    <td class="hidden-xs sonarr-${cleanClass(v.status)}">${v.status}</td>
                    <td class="hidden-xs">${size}</td>
                    <td class="hidden-xs"><span class="label label-info">${v.protocol}</span></td>
                    <td class="text-right">
                        <div class="progress progress-lg m-b-0">
                            <div class="progress-bar progress-bar-info" style="width: ${percent}%;" role="progressbar">${percent}%</div>
                        </div>
                    </td>
                </tr>
                `;

			});
			if(queue == ''){
				queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
			}
			break;
		case 'qBittorrent':
		    if(array.content === false){
                queue = '<tr><td class="max-texts" lang="en">Connection Error to ' + source + '</td></tr>';
                break;
            }
            if(array.content.queueItems == 0){
                queue = '<tr><td class="max-texts" lang="en">Nothing in queue</td></tr>';
            }
            $.each(array.content.queueItems, function(i,v) {
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
                    <td class="hidden-xs qbit-`+cleanClass(status)+`">`+status+`</td>
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
                    <td class="hidden-xs deluge-`+cleanClass(v.state)+`">`+v.state+`</td>
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
            var queue = true;
            var history = false;
            queueButton = 'REFRESH';
            break;
        case 'sabnzbd':
        case 'nzbget':
            var queue = true;
            var history = true;
            break;
        case 'transmission':
        case 'qBittorrent':
        case 'deluge':
	case 'utorrent':
            var queue = true;
            break;
        case 'rTorrent':
	    case 'sonarr':
	    case 'radarr':
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
			<div class="inbox-center table-responsive">
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
			<div class="inbox-center table-responsive">
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
            var queue = true;
            var history = false;
            queueButton = 'REFRESH';
            break;
        case 'sabnzbd':
        case 'nzbget':
            var queue = true;
            var history = true;
            break;
        case 'utorrent':
            var queue = true;
            break;
        case 'transmission':
        case 'qBittorrent':
        case 'deluge':
        case 'rTorrent':
	    case 'sonarr':
	    case 'radarr':
            var queue = true;
            var history = false;
            queueButton = 'REFRESH';
            break;
        default:
            var queue = false;
            var history = false;

    }
    var mainMenu = `<ul class="nav customtab nav-tabs combinedMenuList" role="tablist">`;
    var addToMainMenu = `<li role="presentation" class="`+active+`"><a onclick="homepageDownloader('`+source+`')" href="#combined-`+source+`" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true"><span class=""><img src="./plugins/images/tabs/`+source+`.png" class="homepageImageTitle"><span class="badge bg-org downloaderCount" id="count-`+source+`"><i class="fa fa-spinner fa-spin"></i></span></span></a></li>`;
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
			<div class="inbox-center table-responsive">
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
			<div class="inbox-center table-responsive">
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
    var sourceIcon = (source === 'jellyfin') ? 'fish' : source;
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
						<button class="btn waves-effect waves-light openTab bg-`+source+`" type="button" data-tab-name="`+cleanClass(v.tabName)+`" data-type="`+v.type+`" data-open-tab="`+v.openTab+`" data-url="`+v.address+`" href="javascript:void(0);"> <i class="fa mdi mdi-`+sourceIcon+` fa-2x"></i> </button>
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
	<div id="allHealthChecks" class="m-b-30">
		<div class="el-element-overlay row">
		    <div class="col-md-12">
		        <h4 class="pull-left homepage-element-title"><span lang="en">Health Checks</span> : </h4><h4 class="pull-left">&nbsp;<span class="label label-info m-l-20 checkbox-circle good-health-checks mouse" onclick="homepageHealthChecks()">`+checks+`</span></h4>
		        <hr class="hidden-xs">
		    </div>
			<div class="clearfix"></div>
		    <!-- .cards -->
		    <div class="healthCheckCards">
			    `+buildHealthChecksItem(array)+`
			</div>
		    <!-- /.cards-->
		</div>
	</div>
	<div class="clearfix"></div>
	` : '';
}
function buildPihole(array){
    if(array === false){ return ''; }
    var html = `
    <div id="allPihole">
        <div class="el-element-overlay row">`;
    if(array['options']['title']) {
        html += `
            <div class="col-md-12">
                <h4 class="pull-left homepage-element-title"><span lang="en">Pi-hole</span> : </h4><h4 class="pull-left">&nbsp;</h4>
                <hr class="hidden-xs ml-2">
            </div>
            <div class="clearfix"></div>
        `;
    }
    html += `
		    <div class="piholeCards col-sm-12 my-3">
			    `+buildPiholeItem(array)+`
			</div>
		</div>
	</div>
    `;
    return (array) ? html : '';
}
function buildUnifi(array){
    if(array === false){ return ''; }
    var items = (typeof array.content.unifi.data !== 'undefined') ? array.content.unifi.data.length : false;
    return (items) ? `
	<div id="allUnifi">
		<div class="row">
		    <div class="col-md-12">
		        <h4 class="pull-left homepage-element-title"><span lang="en">UniFi</span> : </h4><h4 class="pull-left">&nbsp;</h4>
		        <hr class="hidden-xs">
		    </div>
			<div class="clearfix"></div>
		    <!-- .cards -->
		    <div class="unifiCards">
		        `+buildUnifiItem(array.content.unifi.data)+`
			</div>
		    <!-- /.cards-->
		</div>
	</div>
	<div class="clearfix"></div>
	` : '';
}
function buildUnifiItem(array){
    var items = '';
    $.each(array, function(i,v) {
        var name = (typeof v.subsystem !== 'undefined') ? v.subsystem : '';
        var stats = {};
        var panelColor = '';
        var proceed = (v.status == 'ok');
        switch (name) {
            case 'wlan':
                panelColor = 'info';
                stats['clients'] = v.num_user;
                stats['tx'] = v['tx_bytes-r'];
                stats['rx'] = v['rx_bytes-r'];
                break;
            case 'wan':
                panelColor = 'success';
                stats['IP'] = v.wan_ip;
                stats['tx'] = v['tx_bytes-r'];
                stats['rx'] = v['rx_bytes-r'];
                break;
            case 'lan':
                panelColor = 'primary';
                stats['clients'] = v.num_user;
                stats['tx'] = v['tx_bytes-r'];
                stats['rx'] = v['rx_bytes-r'];
                break;
            case 'www':
                panelColor = 'warning';
                stats['drops'] = v.drops;
                stats['latency'] = v.latency;
                stats['uptime'] = v.uptime;
                stats['tx'] = v['tx_bytes-r'];
                stats['rx'] = v['rx_bytes-r'];
                break;
            case 'vpn':
                panelColor = 'inverse';
                stats['clients'] = v.remote_user_num_active;
                stats['tx'] = v.remote_user_tx_bytes;
                stats['rx'] = v.remote_user_rx_bytes;
                break;
            default:
        }
        var statItems = '';
        if(proceed) {
            $.each(stats, function (istat, vstat) {
                statItems += `
                    <div class="stat-item">
                        <h6 class="text-uppercase">${istat}</h6>
                        <b>${vstat}</b>
                    </div>
                    `;
            });
            items += `
                <div class="col-lg-4 col-md-6 col-center">
                    <div class="panel panel-${panelColor}">
                        <div class="panel-heading"> <span class="text-uppercase">${name}</span>
                            <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a></div>
                        </div>
                        <div class="panel-wrapper collapse in" aria-expanded="true">
                            <div class="panel-body">
                               ${statItems}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    });
    return items;
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
    $.each(array.content.checks, function(i,v) {
        var hasIcon = healthCheckIcon(v.tags);
        v.name = (v.name) ? v.name : 'New Item';
	    v.desc = (array.options.desc && v.desc) ? '<h5>Notes: '+v.desc+'</h5>' : '';
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
    	var tagPrimaryElem = '', tagSecondaryElem = '';
        if (array.options.tags && v.tags){
            v.tags = v.tags.split(' ');
	        $.each(v.tags, function(key,value) {
		        if(isURL(value)){
			        v.tags = arrayRemove(v.tags , value);
		        }
	        });
            tagPrimaryElem = '<span class="pull-right mt-3 mr-2"><span class="label text-uppercase bg-'+statusColor.replace('animated-3 loop-animation flash','')+' label-rounded font-12">'+v.tags[0]+'</span></span>';
            tagSecondaryElem = '<h5>Tags: ';
            tagSecondaryElem += v.tags.map(t => { return t }).join(', ');
            tagSecondaryElem += '</h5>'
        }
        checks += `
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-xs-12">
                <div class="card bg-inverse text-white mb-3 showMoreHealth mouse" data-id="`+i+`">
                    <div class="card-body bg-org-alt pt-1 pb-1">
                        <div class="d-flex no-block align-items-center">
                            <div class="left-health bg-`+statusColor+`"></div>
                            <div class="ml-1 w-100">
                                <span class="pull-right mt-3 mb-2"><i class="`+statusIcon+` font-20"></i></span>
				`+tagPrimaryElem+`
                                <h3 class="d-flex no-block align-items-center mt-2 mb-2">`+hasIcon+v.name+`</h3>
                                <div class="clearfix"></div>
                                <div class="d-none showMoreHealthDiv-`+i+`"><h5>Last: `+lastPing+`</h5><h5>Next: `+nextPing+`</h5>`+v.desc+tagSecondaryElem+`</div>
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
function isURL(str) {
	const pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
		'((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
		'((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
		'(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
		'(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
		'(\\#[-a-z\\d_]*)?$','i'); // fragment locator
	return !!pattern.test(str);
}
function arrayRemove(arr, value) {

	return arr.filter(function(ele){
		return ele != value;
	});
}
function buildPiholeItem(array){
    var stats = `
    <style>
    .bg-green {
        background-color: #00a65a !important;
    }
    
    .bg-aqua {
        background-color: #00c0ef!important;
    }
    
    .bg-yellow {
        background-color: #f39c12!important;
    }
    
    .bg-red {
        background-color: #dd4b39!important;
    }
    
    .pihole-stat {
        color: #fff !important;
    }
    
    .pihole-stat .card-body h3 {
        font-size: 38px;
        font-weight: 700;
    }

    .pihole-stat .card-body i {
        font-size: 5em;
        float: right;
        color: #ffffff6b;
    }

    .inline-block {
        display: inline-block;
    }
    </style>
    `;
    var length = Object.keys(array['data']).length;
    var combine = array['options']['combine'];
    var totalQueries = function(data) {
        var card = `
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="card text-white mb-3 pihole-stat bg-green">
                <div class="card-body">
                    <div class="inline-block">
                        <p class="d-inline mr-1">Total queries</p>`;
        for(var key in data) {
            var e = data[key];
            if(typeof e['FTLnotrunning'] == 'undefined'){
	            if(length > 1 && !combine) {
		            card += `<p class="d-inline text-muted">(`+key+`)</p>`;
	            }
	            card += `<h3 data-toggle="tooltip" data-placement="right" title="`+key+`">`+e['dns_queries_today'].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")+`</h3>`;
            }

        };
        card += `
                    </div>
                    <i class="fa fa-globe inline-block" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        `
        return card;
    };
    var totalBlocked = function(data) {
        var card = `
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="card bg-inverse text-white mb-3 pihole-stat bg-aqua">
                <div class="card-body">
                    <div class="inline-block">
                        <p class="d-inline mr-1">Queries Blocked</p>`;
        for(var key in data) {
            var e = data[key];
	        if(typeof e['FTLnotrunning'] == 'undefined') {
		        if (length > 1 && !combine) {
			        card += `<p class="d-inline text-muted">(${key})</p>`;
		        }
		        card += `<h3 data-toggle="tooltip" data-placement="right" title="${key}">${e['ads_blocked_today'].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</h3>`;
	        }
        };
        card += `
                    </div>
                    <i class="fa fa-hand-paper-o inline-block" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        `
        return card;
    };
    var percentBlocked = function(data) {
        var card = `
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="card bg-inverse text-white mb-3 pihole-stat bg-yellow">
                <div class="card-body">
                    <div class="inline-block">
                        <p class="d-inline mr-1">Percent Blocked</p>`;
        for(var key in data) {
            var e = data[key];
	        if(typeof e['FTLnotrunning'] == 'undefined') {
		        if (length > 1 && !combine) {
			        card += `<p class="d-inline text-muted">(${key})</p>`;
		        }
		        card += `<h3 data-toggle="tooltip" data-placement="right" title="${key}">${e['ads_percentage_today'].toFixed(1)}%</h3>`
	        }
        };
        card += `
                    </div>
                    <i class="fa fa-pie-chart inline-block" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        `
        return card;
    };
    var domainsBlocked = function(data) {
        var card = `
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="card bg-inverse text-white mb-3 pihole-stat bg-red">
                <div class="card-body">
                    <div class="inline-block">
                        <p class="d-inline mr-1">Domains on Blocklist</p>`;
        for(var key in data) {
            var e = data[key];
	        if(typeof e['FTLnotrunning'] == 'undefined') {
		        if (length > 1 && !combine) {
			        card += `<p class="d-inline text-muted">(${key})</p>`;
		        }
		        card += `<h3 data-toggle="tooltip" data-placement="right" title="${key}">${e['domains_being_blocked'].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</h3>`;
	        }
        };
        card += `
                    </div>
                    <i class="fa fa-list inline-block" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        `
        return card;
    };
    if(combine) {
        stats += '<div class="row">'
        stats += totalQueries(array['data']);
        stats += totalBlocked(array['data']);
        stats += percentBlocked(array['data']);
        stats += domainsBlocked(array['data']);
        stats += '</div>';
    } else {
        for(var key in array['data']) {
            var data = array['data'][key];
            obj = {};
            obj[key] = data;
            stats += '<div class="row">'
            stats += totalQueries(obj);
            stats += totalBlocked(obj);
            stats += percentBlocked(obj);
            stats += domainsBlocked(obj);
            stats += '</div>';
        };
    }
    return stats;
}
function homepagePihole(timeout){
    var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepagePiholeRefresh;
    organizrAPI2('GET','api/v2/homepage/pihole/stats').success(function(data) {
        try {
            let response = data.response;
	        document.getElementById('homepageOrderPihole').innerHTML = '';
	        if(response.data !== null){
		        buildPihole(response.data)
		        $('#homepageOrderPihole').html(buildPihole(response.data));
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr);
    });
    let timeoutTitle = 'PiHole-Homepage';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ homepagePihole(timeout); }, timeout);
    delete timeout;
}
function homepageHealthChecks(tags, timeout){
    tags = (typeof tags !== 'undefined') ? tags : activeInfo.settings.homepage.options.healthChecksTags;
    if(tags == ''){
	    var apiUrl = 'api/v2/homepage/healthchecks';
    }else{
	    var apiUrl = 'api/v2/homepage/healthchecks/' + tags;
    }
    timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageHealthChecksRefresh;
    organizrAPI2('GET',apiUrl).success(function(data) {
        try {
            var response = data.response;
	        document.getElementById('homepageOrderhealthchecks').innerHTML = '';
	        if(response.data !== null){
		        $('#homepageOrderhealthchecks').html(buildHealthChecks(response.data));
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr);
    });
    let timeoutTitle = 'HealthChecks-Homepage';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ homepageHealthChecks(tags,timeout); }, timeout);
    delete timeout;
}
function homepageUnifi(timeout){
    var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageUnifiRefresh;
    organizrAPI2('GET','api/v2/homepage/unifi/data').success(function(data) {
        try {
            let response = data.response;
	        document.getElementById('homepageOrderunifi').innerHTML = '';
	        if(response.data !== null){
		        $('#homepageOrderunifi').html(buildUnifi(response.data));
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }

    }).fail(function(xhr) {
	    OrganizrApiError(xhr);
    });
    var timeoutTitle = 'Unifi-Homepage';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ homepageUnifi(timeout); }, timeout);
    delete timeout;
}
function homepageDownloader(type, timeout){
	var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageDownloadRefresh;
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
		case 'sonarr':
			var action = 'getSonarrQueue';
			break;
		case 'radarr':
			var action = 'getRadarrQueue';
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
                case 'utorrent':
                        var action = 'getutorrent';
                        break;
		default:

	}
	let lowerType = type.toLowerCase();
	organizrAPI2('GET','api/v2/homepage/'+lowerType+'/queue').success(function(data) {
        try {
            let response = data.response;
	        if(response.data !== null){
		        buildDownloaderItem(response.data, type);
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
	let timeoutTitle = type+'-Downloader-Homepage';
	if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
	timeouts[timeoutTitle] = setTimeout(function(){ homepageDownloader(type,timeout); }, timeout);
	delete timeout;
}
function homepageStream(type, timeout){
	var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageStreamRefresh;
	organizrAPI2('GET','api/v2/homepage/'+type+'/streams').success(function(data) {
        try {
            let response = data.response;
	        document.getElementById('homepageOrder'+type+'nowplaying').innerHTML = '';
	        $('#homepageOrder'+type+'nowplaying').html(buildStream(response.data, type));
        }catch(e) {
	        organizrCatchError(e,data);
        }
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
	let timeoutTitle = type+'-Stream-Homepage';
	if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
	timeouts[timeoutTitle] = setTimeout(function(){ homepageStream(type,timeout); }, timeout);
	delete timeout;
}
function homepageRecent(type, timeout){
	var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageRecentRefresh;
	switch (type) {
		case 'plex':
			var action = 'getPlexRecent';
			break;
		case 'emby':
		case 'jellyfin':
			var action = 'getEmbyRecent';
			break;
		default:

	}
	organizrAPI2('GET','api/v2/homepage/'+type+'/recent').success(function(data) {
        try {
	        let response = data.response;
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
        }catch(e) {
	        organizrCatchError(e,data);
        }

	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
	let timeoutTitle = type+'-Recent-Homepage';
	if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
	timeouts[timeoutTitle] = setTimeout(function(){ homepageRecent(type,timeout); }, timeout);
	delete timeout;
}
function homepagePlaylist(type, timeout=30000){
	organizrAPI2('GET','api/v2/homepage/'+type+'/playlists').success(function(data) {
        try {
	        let response = data.response;
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
        }catch(e) {
	        organizrCatchError(e,data);
        }
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function defaultRequestFilter(service){
	switch (service){
		case 'ombi':
			var defaultFilter = {
				"request-filter-approved-ombi" : activeInfo.settings.homepage.ombi.ombiDefaultFilterApproved,
				"request-filter-unapproved-ombi" : activeInfo.settings.homepage.ombi.ombiDefaultFilterUnapproved,
				"request-filter-available-ombi" : activeInfo.settings.homepage.ombi.ombiDefaultFilterAvailable,
				"request-filter-unavailable-ombi" : activeInfo.settings.homepage.ombi.ombiDefaultFilterUnavailable,
				"request-filter-denied-ombi" : activeInfo.settings.homepage.ombi.ombiDefaultFilterDenied
			};
			$.each(defaultFilter, function(i,v) {
				if(v == false){
					$('#'+i).click();
				}
			});
		case 'overseerr':
			var defaultFilter = {
				"request-filter-approved-overseerr" : activeInfo.settings.homepage.overseerr.overseerrDefaultFilterApproved,
				"request-filter-unapproved-overseerr" : activeInfo.settings.homepage.overseerr.overseerrDefaultFilterUnapproved,
				"request-filter-available-overseerr" : activeInfo.settings.homepage.overseerr.overseerrDefaultFilterAvailable,
				"request-filter-unavailable-overseerr" : activeInfo.settings.homepage.overseerr.overseerrDefaultFilterUnavailable,
				"request-filter-denied-overseerr" : activeInfo.settings.homepage.overseerr.overseerrDefaultFilterDenied
			};
			$.each(defaultFilter, function(i,v) {
				if(v == false){
					$('#'+i).click();
				}
			});
	}
}
function homepageRequests(service, timeout){
	switch (service){
		case 'ombi':
			var apiUrl = 'api/v2/homepage/ombi/requests';
			var div = 'homepageOrderombi';
			var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.ombiRefresh;
			break;
		case 'overseerr':
			var apiUrl = 'api/v2/homepage/overseerr/requests';
			var div = 'homepageOrderoverseerr'
			var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.overseerrRefresh;
			break;
		default:
			return false;
	}
	organizrAPI2('GET',apiUrl).success(function(data) {
        try {
            let response = data.response;
	        document.getElementById(div).innerHTML = '';
	        if(response.data.content !== false){
		        $('#' + div).html(buildRequest(service,div, response.data));
	        }
	        $('.request-items-' + service).owlCarousel({
		        nav:false,
		        autoplay:false,
		        dots:false,
		        margin:10,
		        autoWidth:true,
		        items:4
	        })
	        // Default Filter
	        defaultRequestFilter(service);
        }catch(e) {
	        organizrCatchError(e,data);
        }
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
	if(typeof timeouts[service+'-Requests-Homepage'] !== 'undefined'){ clearTimeout(timeouts[service+'-Requests-Homepage']); }
	timeouts[service+'-Requests-Homepage'] = setTimeout(function(){ homepageRequests(service, timeout); }, timeout);
	delete timeout;
}
function testAPIConnection(service, data = ''){
    messageSingle('',' Testing now...',activeInfo.settings.notifications.position,'#FFF','info','10000');
    organizrAPI2('POST','api/v2/test/' + service,data).success(function(data) {
        try {
            let response = data.response;
	        messageSingle('',' API Connection Success',activeInfo.settings.notifications.position,'#FFF','success','10000');
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'API Error');
    });
}
function getUnifiSite(){
    messageSingle('',' Grabbing now...',activeInfo.settings.notifications.position,'#FFF','info','10000');
    organizrAPI2('POST','api/v2/test/unifi/site', {}).success(function(data) {
        try {
            var response = data.response;
	        if(response.data !== false){
		        var sites = '';
		        if(response.data.data){
			        $.each(response.data.data, function(i,v) {
				        sites += '<div class="form-group row"><div class="col-sm-12"><h4 class="mouse" onclick="unifiSiteApply(\''+v.name+'\')">'+v.desc+'</h4></div></div>';
			        });
		        }else{
			        //console.log('no');
		        }
		        var div = `
                <div class="row">
                    <div class="col-12">
                        <div class="card m-b-0">
                            <div class="form-horizontal">
                                <div class="card-body">
                                    <h4 class="card-title" lang="en">Choose Unifi Site</h4>
                                    `+sites+`
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
	        }else{
		        messageSingle('API Connection Failed',response.data,activeInfo.settings.notifications.position,'#FFF','error','10000');
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'API Error');
    });
}
function unifiSiteApply(name){
    $('#homepage-UniFi-form [name=unifiSiteName]').val(name);
    $('#homepage-UniFi-form [name=unifiSiteName]').change();
    swal.close();
    messageSingle('', ' Grabbed Site - Please Save Now',activeInfo.settings.notifications.position,'#FFF','success','10000');
}
function homepageCalendar(timeout){
	var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.calendarRefresh;
    if(activeInfo.settings.homepage.options.alternateHomepageHeaders){
        $('.fc-toolbar').addClass('fc-alternate');
    }
	organizrAPI2('GET','api/v2/homepage/calendar').success(function(data) {
        try {
            let response = data.response;
	        $('#calendar').fullCalendar('removeEvents');
	        $('#calendar').fullCalendar('addEventSource', response.data.events);
	        $('#calendar').fullCalendar('addEventSource', response.data.ical);
	        $('#calendar').fullCalendar('today');
        }catch(e) {
	        organizrCatchError(e,data);
        }
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
	if(typeof timeouts['calendar-Homepage'] !== 'undefined'){ clearTimeout(timeouts['calendar-Homepage']); }
	timeouts['calendar-Homepage'] = setTimeout(function(){ homepageCalendar(timeout); }, timeout);
	delete timeout;
}
function buildTautulliItem(array){
    var cards = ""
    var homestats = array.homestats.data;
    var libstats = array.libstats;
    var options = array.options;
    var friendlyName = array.options.friendlyName;
    var buildLibraries = function(data){
        var libs = data.data;
        var movies = [];
        var tv = [];
        var audio = [];

        libs.forEach(e => {
            switch(e['section_type']) {
                case 'movie':
                    movies.push(e);
                    break;
                case 'show':
                    tv.push(e);
                    break;
                case 'artist':
                    audio.push(e);
                    break;
                default:
                    break;
            }
        });

        movies = movies.sort((a, b) => (parseInt(a['count']) > parseInt(b['count'])) ? -1 : 1);
        tv = tv.sort((a, b) => (parseInt(a['count']) > parseInt(b['count'])) ? -1 : 1);
        audio = audio.sort((a, b) => (parseInt(a['count']) > parseInt(b['count'])) ? -1 : 1);

        var buildCard = function(type, data) {
            var extraField = null;
            var section_name = null;
            if(type == 'movie'){
                extraField = 'Movies';
                section_name = 'Movie Libraries';
            }else if(type == 'show'){
                extraField = 'Shows/Seasons/Episodes';
                section_name = 'TV Show Libraries';
            }else if(type == 'artist'){
                extraField = 'Artists/Albums/Tracks';
                section_name = 'Music Libraries';
            }
            var cardTitle = '<th><span class="pull-left cardTitle">'+section_name.toUpperCase()+'</span><span class="pull-right cardCountType">'+extraField.toUpperCase()+'</th>';
            var card = `
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                <div class="card text-white mb-3 homepage-tautulli-card library-card">
                    <div class="card-body h-100 bg-org-alt">
                        <table class="h-100 w-100">
                            <tr>
                                <td rowspan='2' class="poster-td text-center"><img src="plugins/images/cache/tautulli-`+type+`.svg" class="lib-icon" alt="library icon"></td>
                                ${cardTitle}
                            </tr>
                            <tr>
                                <td>
                                    <div class="scrollable default-scroller">`;
                                    for(var i = 0; i < data.length; i++) {
                                        var rowType = i == 0 ? 'tautulliFirstItem' : i == data.length-1 ? 'tautulliLastItem' : '';
                                        var rowValue = '';
                                        var firstDivCol = '';
                                        var secondDivCol = '';
                                        if(type == 'movie') {
                                            rowValue = data[i]['count'];
                                            firstDivCol = 'col-md-9';
                                            secondDivCol = 'col-md-2';
                                        } else {
                                            rowValue = data[i]['count'] + '<span class="tautulliSeparator"> / </span>' + data[i]['parent_count'] + '<span class="tautulliSeparator"> / </span>' + data[i]['child_count'];
                                            firstDivCol = 'col-md-5';
                                            secondDivCol = 'col-md-6';
                                        }
                                        card += `
                                        <div class="cardListItem elip row w-100 p-r-0 m-0 ${rowType}">
                                            <div class="tautulliRank col-md-1 p-0">${i+1}</div>
                                            <div class="${firstDivCol} p-0 text-left elip"> ${data[i]['section_name']}</div>
                                            <div class="${secondDivCol} cardListCount text-right m-l-10 p-0">${rowValue}</div>
                                        </div>
                                        `;
                                    };

                                    card += `
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>`;
            return card;
        };
        var card = (movies.length > 0) ? buildCard('movie', movies) : '';
        card += (tv.length > 0) ? buildCard('show', tv) : '';
        card += (audio.length > 0) ? buildCard('artist', audio) : '';
        return card;
    };
    var buildStats = function(data, stat, friendlyName = true){
        var card = '';
        data.forEach(e => {
            let classes = '';
            if(e['stat_id'] == stat) {
                if(stat === 'top_platforms') {
                    classes = ' platform-' + e['rows'][0]['platform_name'] + '-rgba';
                } else {
                    classes = ' bg-org-alt';
                }
                card += `
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12">
                    <div class="card text-white mb-3 homepage-tautulli-card">`;
                        if(stat !== 'top_users' && stat !== 'top_platforms') {
                            card += `
                            <div class="bg-img-cont">
                                <img class="bg-img" src="`+e['rows'][0]['art']+`" alt="background art">
                            </div>
                            `;
                        }
                card += `
                        <div class="card-body h-100`+classes+`">
                            <table class="h-100 w-100">
                                <tr>`;
                                    if(stat == 'top_users') {
                                        card += `<td rowspan="2" class="poster-td text-center"><img src="`+e['rows'][0]['user_thumb']+`" class="poster avatar" alt="user avatar"></td>`;
                                    } else if(stat == 'top_platforms') {
                                        card += `<td rowspan="2" class="poster-td text-center"><img src="plugins/images/cache/tautulli-`+e['rows'][0]['platform_name']+`.svg" class="poster" alt="platform icon"></td>`;
                                    } else {
                                        card += `<td rowspan="2" class="poster-td"><img src="`+e['rows'][0]['thumb']+`" class="poster" alt="movie poster"></td>`;
                                    }
                                    var extraField = null;
                                    if(e['stat_title'].includes('Popular')){
                                        extraField = 'users';
                                    }else if(e['stat_title'].includes('Watched')||e['stat_title'].includes('Active')){
                                        extraField = 'plays';
                                    }
                                    var cardTitle = '<th><span class="pull-left cardTitle">'+e['stat_title'].toUpperCase()+'</span><span class="pull-right cardCountType">'+extraField.toUpperCase()+'</th>';
                                    card += cardTitle+`
                                </tr>
                                <tr>
                                    <td><div class="scrollable default-scroller">`;
                                        for(var i = 0; i < e['rows'].length; i++) {
                                            var item = e['rows'][i];
                                            var rowType = i == 0 ? 'tautulliFirstItem' : i == e['rows'].length-1 ? 'tautulliLastItem' : '';
                                            var rowNameValue = '';
                                            var rowValue = '';
                                            if(stat == 'top_users') {
                                                if(friendlyName) {
                                                    rowNameValue = item['friendly_name'];
                                                } else {
                                                    rowNameValue = item['user'];
                                                }
                                                rowValue = item['total_plays'];
                                            } else if(stat == 'top_platforms') {
                                                rowNameValue = item['platform'];
                                                rowValue = item['total_plays'];
                                            } else if(extraField == 'users') {
                                                rowNameValue = item['title'];
                                                rowValue = item['users_watched'];
                                            } else {
                                                rowNameValue = item['title'];
                                                rowValue = item['total_plays'];
                                            }
                                            card += `
                                            <div class="cardListItem elip row w-100 p-r-0 m-0 ${rowType}">
                                                <div class="tautulliRank col-md-1 p-0">${i+1}</div>
                                                <div class="col-md-9 p-0 text-left elip">${rowNameValue}</div>
                                                <div class="col-md-2 cardListCount text-right m-l-10 p-0">${rowValue}</div>
                                            </div>`;
                                        };
                                    card += `
                                    </div></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>`;
            } else {
                return '';
            }
        });
        return card;
    };
    cards += '<div class="row tautulliTop">'
    cards += (options['libraries']) ? buildLibraries(libstats) : '';
    cards += (options['popularMovies']) ? buildStats(homestats, 'popular_movies') : '';
    cards += (options['popularTV']) ? buildStats(homestats, 'popular_tv') : '';
    cards += (options['topMovies']) ? buildStats(homestats, 'top_movies') : '';
    cards += (options['topTV']) ? buildStats(homestats, 'top_tv') : '';
    cards += (options['topUsers']) ? buildStats(homestats, 'top_users', friendlyName) : '';
    cards += (options['topPlatforms']) ? buildStats(homestats, 'top_platforms') : '';
    cards += '</div>';
    cards += '<div class="row tautulliLibraries">'
    cards += '</div>';
    return cards;
}
function buildTautulli(array){
    if(array === false){ return ''; }
    var html = `
    <div id="allTautulli">
		<div class="el-element-overlay row">`
    if(array['options']['title']) {
        html += `
            <div class="col-md-12">
                <h4 class="pull-left homepage-element-title"><span class="mouse" onclick="homepageTautulli()">`+activeInfo.settings.homepage.options.titles.tautulli+`</span> : </h4><h4 class="pull-left">&nbsp;</h4>
                <hr class="hidden-xs ml-2">
            </div>
            <div class="clearfix"></div>
        `;
    }
    html += `
            <div class="tautulliCards col-sm-12 my-3">
                `+buildTautulliItem(array)+`
			</div>
		</div>
	</div>
    `;
    return (array) ? html : '';
}
function homepageTautulli(timeout){
    var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageTautulliRefresh;
    organizrAPI2('GET','api/v2/homepage/tautulli/data').success(function(data) {
        try {
            let response = data.response;
	        document.getElementById('homepageOrdertautulli').innerHTML = '';
	        if(response.data !== null){
		        $('#homepageOrdertautulli').html(buildTautulli(response.data));
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr);
    });
    let timeoutTitle = 'Tautulli-Homepage';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ homepageTautulli(timeout); }, timeout);
    delete timeout;
}
function weatherIcon(code, daytime = true){
    switch (code) {
        case 1:
        case 2:
            return (daytime) ? 'wi-day-sunny' : 'wi-night-clear';
        case 3:
        case 4:
        case 5:
        case 6:
        case 22:
            return (daytime) ? 'wi-day-sunny-overcast' : 'wi-night-alt-partly-cloudy';
        case 7:
        case 8:
        case 9:
            return (daytime) ? 'wi-day-cloudy-high' : 'wi-night-partly-cloudy';
        case 10:
        case 11:
        case 12:
            return (daytime) ? 'wi-day-thunderstorm' : 'wi-night-thunderstorm';
        case 13:
        case 14:
        case 15:
            return (daytime) ? 'wi-day-haze' : 'wi-night-cloudy-windy';
        case 16:
        case 17:
        case 18:
            return (daytime) ? 'wi-day-fog' : 'wi-night-fog';
        case 19:
        case 20:
        case 21:
            return (daytime) ? 'wi-day-cloudy-high' : 'wi-night-cloudy-high';
        case 23:
        case 25:
            return (daytime) ? 'wi-day-rain' : 'wi-night-rain';
        case 24:
        case 26:
            return (daytime) ? 'wi-day-snow' : 'wi-night-snow';
        case 27:
        case 28:
        case 30:
        case 31:
        case 33:
            return (daytime) ? 'wi-day-rain-mix' : 'wi-night-alt-rain-mix';
        case 29:
        case 32:
        case 34:
        case 35:
            return (daytime) ? 'wi-day-snow-thunderstorm' : 'wi-night-alt-snow-thunderstorm';
        default:
            return (daytime) ? 'wi-day-sunny' : 'wi-night-clear';
    }
}
function buildWeatherAndAir(array){
    var returnData = '';
    if (typeof array.content === 'undefined'){ return ''; }
    if(array.content.weather !== false){
        if(array.content.weather.error === null){
            let dates = {};
            $.each(array.content.weather.data, function(i,v) {
                let date = moment(v.datetime).format('YYYY-MM-DD')
                if( typeof dates[date] === 'undefined'){
                    dates[date] = v;
                    dates[date]['temps'] = {
                        'high': v.temperature.value,
                        'low': v.temperature.value
                    }
                }else{
                    if(moment(v.datetime).format('hh:mm a') == '12:00 pm'){
                        dates[date]['icon_code'] = v.icon_code;
                        dates[date]['is_day_time'] = v.is_day_time;
                    }
                    if(v.temperature.value > dates[date]['temps']['high']){
                        dates[date]['temps']['high'] = v.temperature.value;
                    }
                    if(v.temperature.value < dates[date]['temps']['low']){
                        dates[date]['temps']['low'] = v.temperature.value;
                    }
                }
            })
            let weatherItems = '<div class="row">';
            let weatherItemsCount = 0;
            $.each(dates, function(i,v) {
                if(weatherItemsCount === 0){
                    weatherItems += `
                    <div class="col-lg-4 col-sm-12 col-xs-12">
                        <div class="white-box">
                            <h3 class="box-title"><small class="pull-right m-t-10">Feels Like `+Math.round(v.feels_like_temperature.value)+`°</small>`+moment(v.datetime).format('dddd')+`<br/><small class="text-uppercase elip">`+v.weather_text+`</small></h3>
                            <ul class="list-inline two-part" style="margin-top: -13px;">
                                <li><i class="wi `+weatherIcon(v.icon_code, v.is_day_time)+` text-info"></i></li>
                                <li class="text-right"><span class="counter">`+Math.round(v.temperature.value)+`<small><sup>°`+v.temperature.units+`</sup></small></span></li>
                            </ul>
                            <ul class="list-inline m-b-0">
                                <li class="pull-left w-50 hidden-xs"></li>
                                <li class="pull-right" style="width:75px"><small><i class="wi wi-strong-wind m-r-5 text-primary tooltip-primary" data-toggle="tooltip" data-placement="top" title="" data-original-title="Wind"></i>`+Math.round(v.wind.speed.value)+` `+v.wind.speed.units+`</small></li>
                                <li class="pull-right" style="width:75px"><small><i class="wi wi-barometer m-r-5 text-primary tooltip-primary" data-toggle="tooltip" data-placement="top" title="" data-original-title="Pressure"></i>`+Math.round(v.pressure.value)+` `+v.pressure.units+`</small></li>
                                <li class="pull-right" style="width:45px"><small><i class="wi wi-humidity m-r-5 text-primary tooltip-primary" data-toggle="tooltip" data-placement="top" title="" data-original-title="Humidity"></i>`+Math.round(v.relative_humidity)+`</small></li>
                                <li class="pull-right" style="width:45px"><small><i class="wi wi-raindrop m-r-5 text-primary tooltip-primary" data-toggle="tooltip" data-placement="top" title="" data-original-title="Dew Point"></i>`+Math.round(v.dew_point.value)+`°</small></li>
                                <div class="clearfix"></div>
                            </ul>
                        </div>
                    </div>
                    `;
                }else if(weatherItemsCount !== 5){
                    weatherItems += `
                    <div class="col-lg-2 col-sm-3 col-xs-12">
                        <div class="white-box">
                            <h3 class="box-title">`+moment(v.datetime).format('dddd')+`</h3>
                            <ul class="list-inline two-part">
                                <li><i class="wi `+weatherIcon(v.icon_code, v.is_day_time)+` text-info"></i></li>
                                <li class="text-right"><span class="counter">`+Math.round(v.temps.high)+`<small><sup>°`+v.temperature.units+`</sup></small></span></li>
                            </ul>
                            <ul class="list-inline m-b-0">
                                <li class="pull-left w-100"><small class="text-uppercase elip">`+v.weather_text+`</small></li>
                                <div class="clearfix"></div>
                            </ul>
                        </div>
                    </div>
                    `;
                }
                weatherItemsCount ++;
            })
            weatherItems += '</div>';
            returnData += weatherItems;
        }
    }
    if(array.content.air !== false){
        if(array.content.air.error === null) {
            let airItems = '<div class="row">';
            let activeClasses = {
                'poor': '',
                'low': '',
                'moderate': '',
                'good': '',
                'excellent': '',
	            'text': ''
            };
            if(array.content.air.data.indexes.baqi.aqi <= 20){
                activeClasses['poor'] = 'active';
	            activeClasses['text'] = 'text-poor-gradient';
            }else if(array.content.air.data.indexes.baqi.aqi <= 40){
                activeClasses['low'] = 'active';
	            activeClasses['text'] = 'text-low-gradient';
            }else if(array.content.air.data.indexes.baqi.aqi <= 60){
                activeClasses['moderate'] = 'active';
	            activeClasses['text'] = 'text-moderate-gradient';
            }else if(array.content.air.data.indexes.baqi.aqi <= 80){
                activeClasses['good'] = 'active';
	            activeClasses['text'] = 'text-good-gradient';
            }else if(array.content.air.data.indexes.baqi.aqi <= 100){
                activeClasses['excellent'] = 'active';
	            activeClasses['text'] = 'text-excellent-gradient';
            }
            airItems += `
            <div class="col-lg-4 col-sm-12 col-xs-12">
                <div class="white-box text-white">
                    <div class="aqi-scale-component-wrapper">
                        <div class="aqi__header">
                            <div class="aqi__value">
                                <div class="component-wrapper aqi-number ${activeClasses['text']}">${array.content.air.data.indexes.baqi.aqi}</div>
                            </div>
                            <div class="aqi__text"><h2 >AirQuality Index</h2></div>
                        </div>
                        <div class="aqi-scale m-t-40">
                            <div class="category">
                                <div class="chip ${activeClasses['poor']}">
                                    <div class="chip__text text-white">Poor</div>
                                    <div class="chip__bar bg-poor-gradient"></div>
                                </div>
                                <div class="category__min-value text-white">0</div>
                                <div class="category__max-value text-white">20</div>
                            </div>
                            <div class="category">
                                <div class="chip ${activeClasses['low']}">
                                    <div class="chip__text text-white">Low</div>
                                    <div class="chip__bar bg-low-gradient"></div>
                                </div>
                                <div class="category__max-value text-white">40</div>
                            </div>
                            <div class="category">
                                <div class="chip ${activeClasses['moderate']}">
                                    <div class="chip__text text-white">Moderate</div>
                                    <div class="chip__bar bg-moderate-gradient"></div>
                                </div>
                                <div class="category__max-value text-white">60</div>
                            </div>
                            <div class="category">
                                <div class="chip ${activeClasses['good']}">
                                    <div class="chip__text text-white">Good</div>
                                    <div class="chip__bar bg-good-gradient"></div>
                                </div>
                                <div class="category__max-value text-white">80</div>
                            </div>
                            <div class="category">
                                <div class="chip ${activeClasses['excellent']}">
                                    <div class="chip__text text-white">Excellent</div>
                                    <div class="chip__bar bg-excellent-gradient"></div>
                                </div>
                                <div class="category__max-value text-white">100</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            ${buildPollutant(array.content.air.data.pollutants)}
            ${buildHealthRecommendation(array.content.air.data.health_recommendations)}
            `;
            airItems += '</div>';
            returnData += airItems;
        }
    }
    if(array.content.pollen !== false){
        if(array.content.pollen.error === null){
        }
    }
    return returnData;
}
function buildHealthRecommendation(array){
    var healthHeader = '';
    var healthSection = '';
    $.each(array, function(i,v) {
        var title = i.toString().replace('_', ' ').toLowerCase().split(' ').map(word => word.charAt(0).toUpperCase() + word.substring(1)).join(' ')
        switch (i) {
            case 'general_population':
                var icon = 'fa fa-group';
                break;
            case 'elderly':
                var icon = 'ti ti-wheelchair';
                break;
            case 'lung_diseases':
                var icon = 'mdi mdi-spray';
                break;
            case 'heart_diseases':
                var icon = 'mdi mdi-heart-pulse';
                break;
            case 'active':
                var icon = 'mdi mdi-run-fast';
                break;
            case 'pregnant_women':
                var icon = 'mdi mdi-human-pregnant';
                break;
            case 'children':
                var icon = 'fa fa-child';
                break;
            default:
                var icon = '';
        }
        healthHeader += '<li><a href="#section-health-'+i+'" class="sticon '+icon+'"></a></li>';
        healthSection += `
            <section id="section-pollutant-${i}" class="" >
                <h5 class="m-t-0">${title}</h5>
                <span>${v}</span>
            </section>
        `;
    });
var html = `
    <div class="col-lg-4 hidden-xs hidden-sm">
        <div class="white-box text-white p-0">
            <!-- Tabstyle start -->
            <section class="">
                <div class="sttabs sttabs-main-weather-health-div tabs-style-iconbox">
                    <nav>
                        <ul>${healthHeader}</ul>
                    </nav>
                    <div class="content-wrap health-and-pollutant-section default-scroller">${healthSection}</div>
                    <!-- /content -->
                </div>
                <!-- /tabs -->
            </section>
            <!-- Tabstyle start -->
        </div>
    </div>
    <script>
        (function() {
            [].slice.call(document.querySelectorAll('.sttabs-main-weather-health-div')).forEach(function(el) {
                new CBPFWTabs(el);
            });
        })();
    </script>`
    return html;
}
function buildPollutant(array){
    var pollutantHeader = '';
    var pollutantSection = '';
    $.each(array, function(i,v) {
        pollutantHeader += '<li><a href="#section-pollutant-'+i+'" class="sticon"><strong>'+v.display_name+'</strong><br/><small class="elip">'+v.concentration.value+' '+v.concentration.units+'</small></a></li>';
        pollutantSection += `
            <section id="section-pollutant-${i}">
                <h5 class="m-t-0">${v.full_name}</h5>
                <h6>Sources</h6>
                <span>${v.sources_and_effects.sources}</span>
                <hr>
                <h6>Effects</h6>
                <span>${v.sources_and_effects.effects}</span>
            </section>
        `;
    });
    var html = `
    <div class="col-lg-4 hidden-xs hidden-sm">
        <div class="white-box text-white p-0">
            <!-- Tabstyle start -->
            <section class="">
                <div class="sttabs sttabs-main-weather-pollutant-div tabs-style-iconbox">
                    <nav>
                        <ul>${pollutantHeader}</ul>
                    </nav>
                    <div class="content-wrap health-and-pollutant-section default-scroller">${pollutantSection}</div>
                    <!-- /content -->
                </div>
                <!-- /tabs -->
            </section>
            <!-- Tabstyle start -->
        </div>
    </div>
    <script>
        (function() {
            [].slice.call(document.querySelectorAll('.sttabs-main-weather-pollutant-div')).forEach(function(el) {
                new CBPFWTabs(el);
            });
        })();
    </script>`
    return html;
}
function homepageWeatherAndAir(timeout){
    var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageWeatherAndAirRefresh;
    organizrAPI2('GET','api/v2/homepage/weather/data').success(function(data) {
        try {
            let response = data.response;
	        if(response.data !== null){
		        document.getElementById('homepageOrderWeatherAndAir').innerHTML = '';
		        $('#homepageOrderWeatherAndAir').html(buildWeatherAndAir(response.data));
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr);
    });
    let timeoutTitle = 'WeatherAndAir-Homepage';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ homepageWeatherAndAir(timeout); }, timeout);
    delete timeout;
}
function buildMonitorrItem(array){
    var cards = '';
    var options = array['options'];
    var services = array['services'];
    var tabName = '';

    var buildCard = function(name, data) {
        if(data.status == true) {
            var statusColor = 'success'; var imageText = 'fa fa-check-circle text-success'
        } else if (data.status == 'unresponsive') {
            var statusColor = 'warning animated-3 loop-animation flash'; var imageText = 'fa fa-times-circle text-warning'
        } else {
            var statusColor = 'danger animated-3 loop-animation flash'; var imageText = 'fa fa-times-circle text-danger'
        }
        if(typeof data.link !== 'undefined' && data.link.includes('#')) {
            tabName = data.link.substring(data.link.indexOf('#')+1);
            monitorrLink = '<a href="javascript:void(0)" onclick="tabActions(event,\''+tabName+'\',1)">';
        } else if(typeof data.link !== 'undefined') {
            monitorrLink = '<a href="'+data.link+'" target="_blank">'
        }
        if(options['compact']) {
            var card = `
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-xs-12">
                <div class="card bg-inverse text-white mb-3 monitorr-card">
                    <div class="card-body bg-org-alt pt-1 pb-1">
                        <div class="d-flex no-block align-items-center">
                            <div class="left-health bg-`+statusColor+`"></div>
                            <div class="ml-1 w-100">
                                <i class="`+imageText+` font-20 pull-right mt-3 mb-2"></i>
                                `; if (typeof data.link !== 'undefined') { card += monitorrLink; }
                                card += `<h3 class="d-flex no-block align-items-center mt-2 mb-2"><img class="lazyload loginTitle" src="`+data.image+`">&nbsp;`+name+`</h3>
                                `; if (typeof data.link !== 'undefined') { card +=`</a>`; }
                                card += `<div class="clearfix"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        } else {
            var card = `
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
                <div class="card bg-inverse text-white mb-3 monitorr-card">
                    <div class="card-body bg-org-alt text-center">
                        `; if (typeof data.link !== 'undefined') { card +=`<a href="`+data.link+`" target="_blank">`; }
                        card += `<div class="d-block">
                            <h3 class="mt-0 mb-3">`+name+`</h3>
                            <img class="monitorrImage" src="`+data.image+`" alt="service icon">
                        </div>
                        <div class="d-inline-block mt-4 py-2 px-4 badge indicator bg-`+statusColor+`">
                            <p class="mb-0">`; if(data.status == true) { card += 'ONLINE' } else if(data.status == 'unresponsive') { card += 'UNRESPONSIVE' } else { card += 'OFFLINE' } card+=`</p>
                        </div>
                        `; if (typeof data.link !== 'undefined') { card +=`</a>`; }
                        card += `</div>
                </div>
            </div>
            `;
        }
        return card;
    }
    for(var key in services) {
        cards += buildCard(key, services[key]);
    };
    return cards;
}
function buildMonitorr(array){
    if(array === false){ return ''; }
    if(array.error != undefined) {
	    organizrConsole('Monitorr Function',array.error, 'error');
    } else {
        var services = (typeof array.services !== 'undefined') ? Object.keys(array.services).length : false;
        var html = `
        <div id="allMonitorr">
            <div class="el-element-overlay row">`
        if(array['options']['titleToggle']) {
            html += `
                <div class="col-md-12">
                    <h4 class="pull-left homepage-element-title"><span lang="en">`+array['options']['title']+`</span> : </h4><h4 class="pull-left">&nbsp;<span class="label label-info m-l-20 checkbox-circle good-monitorr-services mouse" onclick="homepageMonitorr()">`+services+`</span></h4></h4>
                    <hr class="hidden-xs ml-2">
                </div>
                <div class="clearfix"></div>
            `;
        }
        html += `
                <div class="monitorrCards">
                    `+buildMonitorrItem(array)+`
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        `;
    }
    return (array) ? html : '';
}
function homepageMonitorr(timeout){
    var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepagePiholeRefresh;
    organizrAPI2('GET','api/v2/homepage/monitorr/data').success(function(data) {
        try {
            let response = data.response;
	        document.getElementById('homepageOrderMonitorr').innerHTML = '';
	        if(response.data !== null){
		        buildMonitorr(response.data)
		        $('#homepageOrderMonitorr').html(buildMonitorr(response.data));
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr);
    });
    let timeoutTitle = 'Monitorr-Homepage';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ homepageMonitorr(timeout); }, timeout);
    delete timeout;
}
function homepageSpeedtest(timeout){
    var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageSpeedtestRefresh;
    organizrAPI2('GET','api/v2/homepage/speedtest/data').success(function(data) {
        try {
            let response = data.response;
	        document.getElementById('homepageOrderSpeedtest').innerHTML = '';
	        if(response.data !== null){
		        $('#homepageOrderSpeedtest').html(buildSpeedtest(response.data));
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr);
    });
    let timeoutTitle = 'Speedtest-Homepage';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ homepageSpeedtest(timeout); }, timeout);
	delete timeout;
}
function buildSpeedtest(array){
    if(array === false){ return ''; }
    var html = `
    <style>
    .shadow-sm {
        -webkit-box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075) !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075) !important;
    }
    .speedtest-card {
        background-color: #2d2c2c;
    }
    .speedtest-card .text-success {
        color: #07db71 !important;
    }
    .speedtest-card .text-warning {
        color: #fca503 !important;
    }
    .speedtest-card .text-primary {
        color: #3e95cd !important;
    }
    .speedtest-card span.icon {
        font-size: 2em;
    }
    .speedtest-card h5 {
    }

    .speedtest-card h4,
    .speedtest-card h3 {
        font-weight: 450;
        line-height: 1.2;
    }

    .speedtest-card .text-muted,
    .speedtest-card h5 {
        color: #9e9e9e !important;
    }
    </style>
    `;
    var current = array.data.current;
    var average = array.data.average;
    var maximum = array.data.maximum;
    var minimum = array.data.minimum;
    var options = array.options;

    html += `
    <div id="allSpeedtest">
    `;
    if(options.titleToggle) {
        html += `
        <div class="row">
            <div class="col-sm-12">
                <h4 class="pull-left homepage-element-title"><span lang="en">`+array['options']['title']+` : </h4>
            </div>
        </div>
        `;
    }
    html += `
        <div class="row">
            <div class="my-2 col-lg-4 col-md-4 col-sm-12">
                <div class="card speedtest-card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <h4>Ping</h4>
                            <span class="ti-pulse icon text-success" />
                        </div>
                        <div class="text-truncate">
                            <h3 class="d-inline">`+parseFloat(current.ping).toFixed(1)+`</h3>
                            <p class="d-inline ml-1 text-white">ms (current)</p>
                        </div>`;
    if(average != undefined) {
        html += `
                        <div class="text-truncate text-muted">
                            <h5 class="d-inline">`+parseFloat(average.ping).toFixed(1)+`</h5>
                            <p class="d-inline ml-1">ms (average)</p>
                        </div>
        `;
    }
    if(maximum != undefined) {
        html += `
                        <div class="text-truncate text-muted">
                            <h5 class="d-inline">`+parseFloat(maximum.ping).toFixed(1)+`</h5>
                            <p class="d-inline ml-1">ms (maximum)</p>
                        </div>
        `;
    }
    if(minimum != undefined) {
        html += `
                        <div class="text-truncate text-muted">
                            <h5 class="d-inline">`+parseFloat(minimum.ping).toFixed(1)+`</h5>
                            <p class="d-inline ml-1">ms (minimum)</p>
                        </div>
        `;
    }
    html += `       </div>
                </div>
            </div>
            <div class="my-2 col-lg-4 col-md-4 col-sm-12">
                <div class="card speedtest-card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <h4>Download</h4>
                            <span class="ti-download icon text-warning" />
                        </div>
                        <div class="text-truncate">
                            <h3 class="d-inline">`+parseFloat(current.download).toFixed(1)+`</h3>
                            <p class="d-inline ml-1 text-white">Mbit/s (current)</p>
                        </div>`;
    if(average != undefined) {
            html += `
                        <div class="text-truncate text-muted">
                            <h5 class="d-inline">`+parseFloat(average.download).toFixed(1)+`</h5>
                            <p class="d-inline ml-1">Mbit/s (average)</p>
                        </div>
            `;
        }
    if(maximum != undefined) {
        html += `
                        <div class="text-truncate text-muted">
                            <h5 class="d-inline">`+parseFloat(maximum.download).toFixed(1)+`</h5>
                            <p class="d-inline ml-1">Mbit/s (maximum)</p>
                        </div>
        `;
    }
    if(minimum != undefined) {
        html += `
                        <div class="text-truncate text-muted">
                            <h5 class="d-inline">`+parseFloat(minimum.download).toFixed(1)+`</h5>
                            <p class="d-inline ml-1">Mbit/s (minimum)</p>
                        </div>
        `;
    }
    html += `       </div>
                </div>
            </div>
            <div class="my-2 col-lg-4 col-md-4 col-sm-12">
                <div class="card speedtest-card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <h4>Upload</h4>
                            <span class="ti-upload icon text-primary" />
                        </div>
                        <div class="text-truncate">
                            <h3 class="d-inline">`+parseFloat(current.upload).toFixed(1)+`</h3>
                            <p class="d-inline ml-1 text-white">Mbit/s (current)</p>
                        </div>`;
    if(average != undefined) {
            html += `
                        <div class="text-truncate text-muted">
                            <h5 class="d-inline">`+parseFloat(average.upload).toFixed(1)+`</h5>
                            <p class="d-inline ml-1">Mbit/s (average)</p>
                        </div>
            `;
        }
    if(maximum != undefined) {
        html += `
                        <div class="text-truncate text-muted">
                            <h5 class="d-inline">`+parseFloat(maximum.upload).toFixed(1)+`</h5>
                            <p class="d-inline ml-1">Mbit/s (maximum)</p>
                        </div>
        `;
    }
    if(minimum != undefined) {
        html += `
                        <div class="text-truncate text-muted">
                            <h5 class="d-inline">`+parseFloat(minimum.upload).toFixed(1)+`</h5>
                            <p class="d-inline ml-1">Mbit/s (minimum)</p>
                        </div>
        `;
    }
    html += `       </div>
                </div>
            </div>
        </div>
    </div>
    `;

    return (array) ? html : '';
}
function buildNetdataItem(array){
    var html = `
    <style>
    .all-netdata .easyPieChart-value {
        position: absolute;
        top: 77px;
        width: 100%;
        text-align: center;
        left: 0;
        font-size: 24.4625px;
        font-weight: normal;
    }
    .all-netdata .easyPieChart-title {
        position: absolute;
        width: 100%;
        text-align: center;
        left: 0;
        font-weight: bold;
    }
    .all-netdata .easyPieChart-units {
        position: absolute;
        top: 118px;
        width: 100%;
        text-align: center;
        left: 0;
        font-size: 15px;
        font-weight: normal;
    }

    .all-netdata .gauge-chart .gauge-value {
        position: relative;
        width: 100%;
        text-align: center;
        top: 30px;
        color: #dcdcdc;
        font-weight: bold;
        left: 0;
        font-size: 26px;
    }

    .all-netdata .gauge-chart .gauge-title {
        position: relative;
        width: 100%;
        text-align: center;
        top: -10px;
        //color: #fff;
        font-weight: bold;
        left: 0;
        font-size: 15px;
    }

    .all-netdata .chart-lg .gauge-chart .gauge-value {
        top: 70px;
        font-size: 26px;
    }

    .all-netdata .chart-lg .gauge-chart .gauge-title {
        top: 45px;
        font-size: 15px;
    }

    .all-netdata .chart-md .gauge-chart .gauge-value {
        top: 65px;
        font-size: 26px;
    }

    .all-netdata .chart-md .gauge-chart .gauge-title {
        top: 45px;
        font-size: 15px;
    }

    .all-netdata .chart-sm .gauge-chart .gauge-value {
        top: 65px;
        font-size: 26px;
    }

    .all-netdata .chart-sm .gauge-chart .gauge-title {
        top: 45px;
        font-size: 15px;
    }

    .all-netdata .chart-lg,
    .all-netdata .chart-md,
    .all-netdata .chart-sm {
        display: inline-block;
        margin: 15px;
    }

    .all-netdata .chart-lg,
    .all-netdata .chart-lg .chart {
        height: 180px;
        width: 180px;
    }

    .all-netdata .chart-md,
    .all-netdata .chart-md .chart {
        height: 160px;
        width: 160px;
    }

    .all-netdata .chart-sm,
    .all-netdata .chart-sm .chart {
        height: 140px;
        width: 140px;
    }

    .all-netdata .chart-lg .gauge-chart,
    .all-netdata .gauge-cont.chart-lg {
        //height: 300px;
        width: 300px;
    }

    .all-netdata .chart-md .gauge-chart,
    .all-netdata .gauge-cont.chart-md {
        //height: 275px;
        width: 275px;
    }

    .all-netdata .chart-sm .gauge-chart,
    .all-netdata .gauge-cont.chart-sm {
        //height: 250px;
        width: 250px;
    }

    .all-netdata .chart-lg .easyPieChart-title {
        top: 37px;
        font-size: 15px;
    }

    .all-netdata .chart-md .easyPieChart-title {
        top: 33px;
        font-size: 13.5px;
    }

    .all-netdata .chart-sm .easyPieChart-title {
        top: 30px;
        font-size: 12px;
    }

    .all-netdata .chart-lg .easyPieChart-value {
        top: 75px;
        font-size: 24.4625px;
    }

    .all-netdata .chart-md .easyPieChart-value {
        top: 65px;
        font-size: 24.4625px;
    }

    .all-netdata .chart-sm .easyPieChart-value {
        top: 55px;
        font-size: 24.4625px;
    }

    .all-netdata .chart-lg .easyPieChart-units {
        top: 130px;
        font-size: 15px;
    }

    .all-netdata .chart-md .easyPieChart-units {
        top: 108px;
        font-size: 15px;
    }

    .all-netdata .chart-sm .easyPieChart-units {
        top: 95px;
        font-size: 15px;
    }
    </style>
    `;

    var buildEasyPieChart = function(e,i,size,easySize,display) {
        return `
        <div class="chart-`+size+` my-3 text-center `+display+`">
            <div class="chart" id="easyPieChart`+(i+1)+`" data-percent="`+e.percent+`">
                <span class="easyPieChart-title">`+e.title+`</span>
                <span class="easyPieChart-value" id="easyPieChart`+(i+1)+`Value">`+parseFloat(e.value).toFixed(1)+`</span>
                <span class="easyPieChart-units" id="easyPieChart`+(i+1)+`Units">`+e.units+`</span>
            </div>
        </div>
        <script>
        $(function() {
            var opts = {
                size: `+easySize+`,
                lineWidth: 7,
                scaleColor: false,
                barColor: '#`+e.colour+`',
                trackColor: '#636363',
            };
            if(`+e.percent+` == 0) {
                opts.lineCap = 'butt';
            }
            $('#easyPieChart`+(i+1)+`').easyPieChart(opts);
        });
        </script>
        `;
    }

    var buildGaugeChart = function(e,i,size,easySize,display) {
        switch(size) {
            case 'lg':
                easySize = 300;
                break;
            case 'sm':
                easySize = 275;
                break;
            case 'md':
            default:
                easySize = 250;
                break;
        }
        return `
        <div class="mx-0 gauge-cont chart-`+size+` my-3 text-center `+display+`">
            <div class="gauge-chart text-center">
                <span class="gauge-title d-block" id="gaugeChart`+(i+1)+`Title">`+e.title+`</span>
                <span class="gauge-value d-block" id="gaugeChart`+(i+1)+`Value">`+parseFloat(e.value).toFixed(1)+`</span>
                <canvas id="gaugeChart`+(i+1)+`" style="width: 100%"></canvas>
            </div>
        </div>
        <script>
        $(function() {
            var opts = {
                angle: 0.14, // The span of the gauge arc
                lineWidth: 0.54, // The line thickness
                radiusScale: 1, // Relative radius
                pointer: {
                    length: 0.77, // // Relative to gauge radius
                    strokeWidth: 0.075, // The thickness
                    color: '#A1A1A1' // Fill color
                },
                limitMax: false,     // If false, max value increases automatically if value > maxValue
                limitMin: false,     // If true, the min value of the gauge will be fixed
                colorStart: '#`+e.colour+`',   // Colors
                colorStop: '#`+e.colour+`',    // just experiment with them
                strokeColor: '#636363',  // to see which ones work best for you
                generateGradient: true,
                highDpiSupport: true,     // High resolution support
            
            };
            var target = document.getElementById('gaugeChart`+(i+1)+`'); // your canvas element
            var gauge = new Gauge(target).setOptions(opts); // create sexy gauge!
            gauge.maxValue = `+e.max+`; // set max gauge value
            gauge.setMinValue(0);  // Prefer setter over gauge.minValue = 0
            gauge.animationSpeed = 8; // set animation speed (32 is default value)
            gauge.set(`+e.percent+`); // set actual value
            window.netdata[`+(i+1)+`] = gauge
        });
        </script>
        `;
    }

    array.forEach((e, i) => {
        var size = e.size;
        var easySize;
        if(size == '') {
            size = 'md';
        }
        switch(size) {
            case 'lg':
                easySize = 180;
                break;
            case 'sm':
                easySize = 140;
                break;
            case 'md':
            default:
                easySize = 160;
                break;
        }

        var display = ' ';
        if(e.lg) {
            display += ' d-xl-inline-block d-lg-inline-block';
        } else {
            display += ' d-xl-none d-lg-none d-none';
        }
        if(e.md) {
            display += ' d-md-inline-block';
        } else {
            display += ' d-md-none d-none';
        }
        if(e.sm) {
            display += ' d-sm-inline-block d-xs-inline-block';
        } else {
            display += ' d-sm-none d-xs-none d-none';
        }
        display += ' ';

        if(e.error) {
	        organizrConsole('Netdata Function','(Chart ' + (i+1) + '): ' + e.error, 'error');
        } else if(e.chart == 'easypiechart') {
            html += buildEasyPieChart(e,i,size,easySize,display);
        } else if(e.chart == 'gauge') {
            html += buildGaugeChart(e,i,size,easySize,display);
        }
    });

    return html;
}
function buildNetdata(array){
    var data = array.data;
    if(array === false){ return ''; }
    window.netdata = [];

    var html = `
    <style>
    .clearfix {
        *zoom: 1;
      }
      .all-netdata .clearfix:before,
      .all-netdata .clearfix:after {
        display: table;
        content: "";
      }
      .all-netdata .clearfix:after {
        clear: both;
      }
      
      .all-netdata .easyPieChart {
          position: relative;
          text-align: center;
      }
      
      .all-netdata .easyPieChart canvas {
          position: absolute;
          top: 0;
          left: 0;
      }
      
      .all-netdata .chart {
          float: left;
          //margin: 10px;
      }
      
      .all-netdata .percentage,
      .all-netdata .label {
          text-align: center;
          color: #333;
          font-weight: 100;
          font-size: 1.2em;
          margin-bottom: 0.3em;
      }
      
      .all-netdata .credits {
          padding-top: 0.5em;
          clear: both;
          color: #999;
      }
      
      .all-netdata .credits a {
          color: #333;
      }
      
      .all-netdata .dark {
          background: #333;
      }
      
      .all-netdata .dark .percentage-light,
      .all-netdata .dark .label {
          text-align: center;
          color: #999;
          font-weight: 100;
          font-size: 1.2em;
          margin-bottom: 0.3em;
      }
      
      
      .all-netdata .button {
        -webkit-box-shadow: inset 0 0 1px #000, inset 0 1px 0 1px rgba(255,255,255,0.2), 0 1px 1px -1px rgba(0, 0, 0, .5);
        -moz-box-shadow: inset 0 0 1px #000, inset 0 1px 0 1px rgba(255,255,255,0.2), 0 1px 1px -1px rgba(0, 0, 0, .5);
        box-shadow: inset 0 0 1px #000, inset 0 1px 0 1px rgba(255,255,255,0.2), 0 1px 1px -1px rgba(0, 0, 0, .5);
        -webkit-border-radius: 3px;
        -moz-border-radius: 3px;
        border-radius: 3px;
        padding: 6px 20px;
        font-weight: bold;
        text-transform: uppercase;
        display: block;
        margin: 0 auto 2em;
        max-width: 200px;
        text-align: center;
        background-color: #5c5c5c;
        background-image: -moz-linear-gradient(top, #666666, #4d4d4d);
        background-image: -ms-linear-gradient(top, #666666, #4d4d4d);
        background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#666666), to(#4d4d4d));
        background-image: -webkit-linear-gradient(top, #666666, #4d4d4d);
        background-image: -o-linear-gradient(top, #666666, #4d4d4d);
        background-image: linear-gradient(top, #666666, #4d4d4d);
        background-repeat: repeat-x;
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#666666', endColorstr='#4d4d4d', GradientType=0);
        color: #ffffff;
        text-shadow: 0 1px 1px #333333;
      }
      .all-netdata .button:hover {
        color: #ffffff;
        text-decoration: none;
        background-color: #616161;
        background-image: -moz-linear-gradient(top, #6b6b6b, #525252);
        background-image: -ms-linear-gradient(top, #6b6b6b, #525252);
        background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#6b6b6b), to(#525252));
        background-image: -webkit-linear-gradient(top, #6b6b6b, #525252);
        background-image: -o-linear-gradient(top, #6b6b6b, #525252);
        background-image: linear-gradient(top, #6b6b6b, #525252);
        background-repeat: repeat-x;
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#6b6b6b', endColorstr='#525252', GradientType=0);
      }
      .all-netdata .button:active {
        background-color: #575757;
        background-image: -moz-linear-gradient(top, #616161, #474747);
        background-image: -ms-linear-gradient(top, #616161, #474747);
        background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#616161), to(#474747));
        background-image: -webkit-linear-gradient(top, #616161, #474747);
        background-image: -o-linear-gradient(top, #616161, #474747);
        background-image: linear-gradient(top, #616161, #474747);
        background-repeat: repeat-x;
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#616161', endColorstr='#474747', GradientType=0);
        -webkit-transform: translate(0, 1px);
        -moz-transform: translate(0, 1px);
        -ms-transform: translate(0, 1px);
        -o-transform: translate(0, 1px);
        transform: translate(0, 1px);
      }
      .all-netdata .button:disabled {
        background-color: #dddddd;
        background-image: -moz-linear-gradient(top, #e7e7e7, #cdcdcd);
        background-image: -ms-linear-gradient(top, #e7e7e7, #cdcdcd);
        background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#e7e7e7), to(#cdcdcd));
        background-image: -webkit-linear-gradient(top, #e7e7e7, #cdcdcd);
        background-image: -o-linear-gradient(top, #e7e7e7, #cdcdcd);
        background-image: linear-gradient(top, #e7e7e7, #cdcdcd);
        background-repeat: repeat-x;
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#e7e7e7', endColorstr='#cdcdcd', GradientType=0);
        color: #939393;
        text-shadow: 0 1px 1px #fff;
      }
    </style>
    `;

    html += `
    <div class="row m-b-30">
        
            <div class="d-block text-center all-netdata">
    `;
    html += buildNetdataItem(data);
    html += `
            </div>
        
    </div>`;

    return (array) ? html : '';
}
function homepageNetdata(timeout){
    var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageNetdataRefresh;
    organizrAPI2('GET','api/v2/homepage/netdata/data').success(function(data) {
        try {
            let response = data.response;
	        if(!tryUpdateNetdata(response.data.data)) {
		        document.getElementById('homepageOrderNetdata').innerHTML = '';
		        if(response.data !== null){
			        $('#homepageOrderNetdata').html(buildNetdata(response.data));
		        }
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr);
    });
    var timeoutTitle = 'Netdata-Homepage';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ homepageNetdata(timeout); }, timeout);
    delete timeout;
}
function tryUpdateNetdata(array){
    var existing = false;
    array.forEach((e,i) => {
        var id = i + 1;
        if(e.chart == 'easypiechart') {
            if($('#easyPieChart' + id).length) {
                $('#easyPieChart' + id).data('easyPieChart').update(e.percent);
                $('#easyPieChart' + id + 'Value').html(parseFloat(e.value).toFixed(1));
                existing = true;
            }
        } else if(e.chart == 'gauge') {
            if(window.netdata) {
                if(window.netdata[(i+1)]) {
                    window.netdata[(i+1)].set(e.percent); // set actual value
                    $('#gaugeChart' + (i+1) + 'Value').html(parseFloat(e.value).toFixed(1));
                    existing = true;
                }
            } else {
                existing = false;
            }
        } else {
            existing = false;
        }
    });
    return existing;
}
function homepageJackett(){
	if(activeInfo.settings.homepage.options.alternateHomepageHeaders){
		var header = `
		<div class="col-md-12">
			<h2 class="text-white m-0 pull-left text-uppercase"><img class="lazyload homepageImageTitle" data-src="plugins/images/tabs/jackett.png"> &nbsp; <span lang="en">Jackett</span>&nbsp;</h2>
			<hr class="hidden-xs"><div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
		<script>$('.jackett-panel').removeClass('panel panel-default');</script>
		`;
	}else{
		var header = `
		<div class="panel-heading bg-info p-t-10 p-b-10">
			<span class="pull-left m-t-5 text-white"><img class="lazyload homepageImageTitle" data-src="plugins/images/tabs/jackett.png" > &nbsp; <span lang="en">Jackett</span></span>
			<div class="clearfix"></div>
		</div>
		`;
	}
	let html = `
	<div id="jackettSearch" class="row">
		<div class="col-lg-12">
			<div class="jackett-panel panel panel-default">
				`+header+`
				<div class="panel-wrapper p-b-0 collapse in">
					<div class="white-box">
	                    <h3 class="box-title m-b-0" lang="en">Search</h3>
	                    
	                    <form onsubmit="searchJackett();return false;">
	                        <div class="input-group m-b-30">
	                        	<span class="input-group-btn hidden">
									<button type="button" class="btn waves-effect waves-light btn-primary clearJackett" onclick="clearJackett();"><i class="fa fa-eraser"></i></button>
								</span>
	                            <input id="jackett-search-query" class="form-control" placeholder="Search for..." lang="en">
	                            <span class="input-group-btn">
									<button type="submit" class="btn waves-effect waves-light btn-info"><i class="fa fa-search"></i></button>
								</span>
	                        </div>
	
	                    </form>
	                    
	                    <div class="jackettDataTable hidden">
        					<h3 class="box-title m-b-0" lang="en">Results</h3>
					        <div class="table-responsive">
					            <table id="jackettDataTable" class="table table-striped">
					                <thead>
					                    <tr>
					                        <th lang="en">Date</th>
					                        <th lang="en">Tracker</th>
					                        <th lang="en">Name</th>
					                        <th lang="en">Size</th>
					                        <th lang="en">Files</th>
					                        <th lang="en">Grabs</th>
					                        <th lang="en">Seeds</th>
					                        <th lang="en">Leechers</th>
					                        <th lang="en">Download</th>
					                    </tr>
					                </thead>
					                <tbody></tbody>
					            </table>
					        </div>
    					</div>
	                </div>
					
				</div>
			</div>
		</div>
	</div>
	`;
	$('#homepageOrderJackett').html(html);
}
function clearJackett(){
	$('#jackett-search-query').val('');
	$('.clearJackett').parent().addClass('hidden');
	$('#jackettDataTable').DataTable().destroy();
	$('.jackettDataTable').addClass('hidden');
}
function searchJackett(){
	let query = $('#jackett-search-query').val();
	if(query !== ''){
		$('.jackettDataTable').removeClass('hidden');
		//ajaxloader('#jackettSearch .panel-wrapper', 'in');
		ajaxblocker('.jackett-panel .white-box', 'in', 'Searching...');
	}else{
		return false;
	}
	$.fn.dataTable.ext.errMode = 'none';
	$('#jackettDataTable').DataTable().destroy();
	let preferBlackholeDownload = activeInfo.settings.homepage.jackett.homepageJackettBackholeDownload;
	let jackettTable = $("#jackettDataTable")
		.on( 'error.dt', function ( e, settings, techNote, message ) {
			console.log( 'An error has been reported by DataTables: ', message );
		} )
		.DataTable( {
			"ajax": {
				"url": "api/v2/homepage/jackett/" + query,
				"dataSrc": function ( json ) {
					return json.response.data.content.Results;
				}
			},
			"columns": [
				{ data: 'PublishDate',
					render: function ( data, type, row ) {
						if ( type === 'display' || type === 'filter' ) {
							var m = moment.tz(data, activeInfo.timezone);
							return moment.utc(m, "YYYY-MM-DD hh:mm[Z]").local().fromNow();

						}
						return data;
					}
				},
				{ "data": "Tracker" },
				{ data: 'Title',
					render: function ( data, type, row ) {
						if(row.Details !== null){
							return '<a href="'+row.Details+'" target="_blank">'+data+'</a>';
						}else{
							return data;
						}

					}
				},
				{ data: 'Size',
					render: function ( data, type, row ) {
						if ( type === 'display' || type === 'filter' ) {
							return humanFileSize(data, false);
						}
						return humanFileSize(data, false);
					}
				},
				{ "data": "Files" },
				{ "data": "Grabs" },
				{ "data": "Seeders" },
				{ "data": "Peers" },
				{ data: 'MagnetUri',
					render: function ( data, type, row ) {
						if ( type === 'display' || type === 'filter' ) {
							if(preferBlackholeDownload === true && row.BlackholeLink !== null){
								return '<a onclick="jackettDownload(\''+row.BlackholeLink+'\');return false;" href="#"><i class="fa fa-cloud-download"></i></a>';
							}else if(data !== null){
								return '<a href="'+data+'" target="_blank"><i class="fa fa-magnet"></i></a>';
							}else if(row.Details !== null){
								return '<a href="'+row.Details+'" target="_blank"><i class="fa fa-cloud-download"></i></a>';
							}else if(row.Guid !== null){
								return '<a href="'+row.Guid+'" target="_blank"><i class="fa fa-cloud-download"></i></a>';
							}else if(row.Link !== null){
								return '<a href="'+row.Link+'" target="_blank"><i class="fa fa-download"></i></a>';
							}else{
								return 'No Download Link';
							}
						}
						return data;
					}
				},
			],
			"order": [[ 0, 'desc' ]],
			"initComplete": function(settings, json) {
				//ajaxloader();
				ajaxblocker('.jackett-panel .white-box');
				$('.clearJackett').parent().removeClass('hidden');
			}
		} );

}
function jackettDownload(url) {
	let blackholeLink=url.substring(url.indexOf("/bh/"));
	var post = {
		url: blackholeLink
	};
	organizrAPI2('POST', 'api/v2/homepage/jackett/download/', post, true)
		.success(function() {
			message('Torrent downloaded','',activeInfo.settings.notifications.position,"#FFF","success","5000");
		})
		.fail(function(xhr) {
			OrganizrApiError(xhr, 'Error downloading torrent');
		});
}
function homepageOctoprint(timeout){
    var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageOctoprintRefresh;
    organizrAPI2('GET','api/v2/homepage/octoprint/data').success(function(data) {
        try {
            let response = data.response;
	        document.getElementById('homepageOrderOctoprint').innerHTML = '';
	        if(response.data !== null){
		        $('#homepageOrderOctoprint').html(buildOctoprint(response.data));
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr);
    });
    let timeoutTitle = 'Octoprint-Homepage';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ homepageOctoprint(timeout); }, timeout);
    delete timeout;
}
function buildOctoprint(array){
	var menu = `<ul class="nav customtab nav-tabs pull-right" role="tablist">`;
	var headerAlt = '';
	var header = '';
	var content = '';
	var webcamUrl = '';
	var webcamHtml = '';
	var css = `
	<style>
	.octoprint-webcam {
		max-height: 400px;
		max-width: 100%;
		float: right;
	}
	.octoprint-block {
		margin-left: 0px;
		margin-right: 0px;
	}
	.octoprint-button-spacer {
		padding-right: 46px;
	}
	</style>
	`;
	menu += `
		<li role="presentation" class="active" ><a href="" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true" onclick="homepageOctoprint();"><span class="visible-xs"><i class="ti-download"></i></span><span class="hidden-xs">REFRESH</span></a></li>
		`;
	menu += '</ul>';
	if(activeInfo.settings.homepage.options.alternateHomepageHeaders){
		var headerAlt = `
		<div class="col-md-12">
			<h2 class="text-white m-0 pull-left text-uppercase"><img class="lazyload homepageImageTitle" data-src="plugins/images/tabs/octoprint.png">  &nbsp; </h2>
			`+menu+`
			<hr class="hidden-xs"><div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
		`;
	}else{
		var header = `
		<div class="white-box bg-info m-b-0 p-b-0 p-t-10 mailbox-widget">
			<h2 class="text-white m-0 pull-left text-uppercase"><img class="lazyload homepageImageTitle" data-src="plugins/images/tabs/octoprint.png">  &nbsp; </h2>
			`+menu+`
			<div class="clearfix"></div>
		</div>
		`;
	}
	content = '<p>State: '+array.data.job.state+'</p>';
	if (array.data.job.state == "Printing") {
		content += '<p>File: '+array.data.job.job.file.display+'</p>';
		content += '<p>Progress: '+parseFloat(array.data.job.progress.completion).toFixed(0)+'%</p>';
		content += '<p>Approx. Total Print Time: '+octoprintFormatTime(array.data.job.job.estimatedPrintTime)+'</p>';
		content += '<p>Print Time Left: '+octoprintFormatTime(array.data.job.progress.printTimeLeft)+'</p>';
	}
	if (array.data.settings.webcam.webcamEnabled) {
		webcamUrl = array.data.settings.webcam.streamUrl;
		if (webcamUrl[0] == "/") {
			webcamUrl = array.data.url + webcamUrl;
		}
	}
	if (webcamUrl) {
		var webcamHtml = `<div class="col-lg-4"><img class="octoprint-webcam" src="`+webcamUrl+`"></div>`;
	}
	return css+`
	<div class="row">
		`+headerAlt+`
		<div class="col-lg-12">
			`+header+`
			<div class="row octoprint-block white-box">
				<div class="col-lg-8 text-white">
						<div class="tab-content m-t-0">`+content+`</div>
				</div>
				`+webcamHtml+`
			</div>
		</div>
	</div>
	`;
}
function octoprintFormatTime(seconds) {
	var format = "";
	var days = Math.floor(moment.duration(seconds,'seconds').asDays());
	var hours = Math.floor(moment.duration(seconds,'seconds').asHours());
	var minutes = moment.duration(seconds,'seconds').minutes()
	var seconds = moment.duration(seconds,'seconds').seconds()
	if (days > 0) {
		format += days + " "+octoprintPluralize("day", days)+" ";
	}
	if (hours > 0) {
		format += hours + " "+octoprintPluralize("hour", hours)+" ";
	}
	if (minutes > 0) {
		format += minutes + " "+octoprintPluralize("minute", minutes)+" ";
	}
	if (seconds > 0) {
		format += seconds + " "+octoprintPluralize("second", seconds)+" ";
	}
	return format;
}

function octoprintPluralize(s, n) {
	if (n > 1) {
		return s+"s";
	}
	return s
}
function pad(n, width, z) {
	z = z || '0';
	n = n + '';
	return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
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
        'X-Plex-Model': 'Plex OAuth',
        'X-Plex-Platform': activeInfo.osName,
        'X-Plex-Platform-Version': activeInfo.osVersion,
        'X-Plex-Device': activeInfo.browserName,
        'X-Plex-Device-Name': activeInfo.browserVersion,
        'X-Plex-Device-Screen-Resolution': window.screen.width + 'x' + window.screen.height,
        'X-Plex-Language': 'en'
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
    'Redirecting to the login page...' +
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
function PlexOAuth(success, error, pre, id = null) {
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
        var oauth_params = {
            'clientID': x_plex_headers['X-Plex-Client-Identifier'],
            'context[device][product]': x_plex_headers['X-Plex-Product'],
            'context[device][version]': x_plex_headers['X-Plex-Version'],
            'context[device][platform]': x_plex_headers['X-Plex-Platform'],
            'context[device][platformVersion]': x_plex_headers['X-Plex-Platform-Version'],
            'context[device][device]': x_plex_headers['X-Plex-Device'],
            'context[device][deviceName]': x_plex_headers['X-Plex-Device-Name'],
            'context[device][model]': x_plex_headers['X-Plex-Model'],
            'context[device][screenResolution]': x_plex_headers['X-Plex-Device-Screen-Resolution'],
            'context[device][layout]': 'desktop',
            'code': code
        };
        plex_oauth_window.location = 'https://app.plex.tv/auth/#!?' + encodeData(oauth_params);
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
                            success('plex',data.authToken, id)
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
function openOAuth(provider){
	// will actually fix this later
	closePlexOAuthWindow();
	plex_oauth_window = PopupCenter('', 'OAuth', 600, 700);
	$(plex_oauth_window.document.body).html(plex_oauth_loader);
	plex_oauth_window.location = 'api/v2/oauth/trakt';
}
function encodeData(data) {
    return Object.keys(data).map(function(key) {
        return [key, data[key]].map(encodeURIComponent).join("=");
    }).join("&");
}
function oAuthSuccess(type,token, id = null){
    switch(type) {
        case 'plex':
        	if(id){
		        $(id).val(token);
		        $(id).change();
		        messageSingle('',window.lang.translate('Grabbed Token - Please Save'),activeInfo.settings.notifications.position,'#FFF','success','5000');
	        }else{
		        $('#oAuth-Input').val(token);
		        $('#oAuthType-Input').val(type);
		        $('#login-username-Input').addClass('hidden');
		        $('#login-password-Input').addClass('hidden');
		        $('#oAuth-div').removeClass('hidden');
		        $('.login-button').first().trigger('click');
	        }
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
		url: "api/v2/homepage/youtube/"+searchQuery,
	});
}
function youtubeCheck(title,link){
	youtubeSearch(title).success(function(data) {
        var response = data.response;
		if(response.data){
			inlineLoad();
			var id = response.data.items["0"].id.videoId;
			var div = `
		<div id="player-`+link+`" data-plyr-provider="youtube" data-plyr-embed-id="`+id+`"></div>
		<div class="clearfix"></div>
		`;
			$('.youtube-div').html(div);
			$('.'+link).trigger('click');
			player = new Plyr('#player-'+link);
		}

	}).fail(function(xhr) {
		OrganizrApiError(xhr, 'YouTube API Error');
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
			url = 'https://api.themoviedb.org/4/list/64438?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&language='+activeInfo.language+'&page='+page;
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
function getTmdbImages(id, type) {
	return $.ajax({
		url: `https://api.themoviedb.org/3/${type}/${id}/images?api_key=83cf4ee97bb728eeaf9d4a54e64356a1`,
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
    organizrAPI2('POST','api/v2/users/import/'+type,{type:type}).success(function(data) {
        try {
            var response = data.response;
	        message('User Import',response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
	        $('.importUsersButton').attr('disabled', false);
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'Import Error');
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
	    case 'jellyfin':
		    $('.switchAuth').parent().parent().parent().hide();
		    $('.backendAuth').parent().parent().parent().show();
		    $('.jellyfinAuth').parent().parent().parent().show();
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
function checkLocalForwardStatus(array){
    if(array.settings.login.enableLocalAddressForward == true && typeof array.settings.login.enableLocalAddressForward !== 'undefined'){
        if(array.settings.login.wanDomain !== '' && array.settings.login.localAddress !== ''){
	        organizrConsole('Organizr Function','Local Login Enabled');
	        organizrConsole('Organizr Function','Local Login Testing...');
            let remoteSite = array.settings.login.wanDomain;
            let localSite = array.settings.login.localAddress;
            try {
                let currentURL = decodeURI(window.location.href)
                let currentSite = window.location.host;
                if(activeInfo.settings.user.local && currentSite.indexOf(remoteSite) !== -1 && currentURL.indexOf('override') === -1){
	                organizrConsole('Organizr Function','Local Login Status: Local | Forwarding Now');
                    window.location = localSite;
                }else{
	                organizrConsole('Organizr Function','Local Login Status: Not Local or Override was set - Ignoring Forward Request');
                }
            } catch(e) {
                console.error(e);
            }
        }
    }
}
function forceSearch(term){
    $.magnificPopup.close();
    var tabName = $("li[data-url^='api/v2/page/homepage']").find('span').html();
    if($("li[data-url^='api/v2/page/homepage']").find('i').hasClass('tabLoaded')){
        if($("li[data-url^='api/v2/page/homepage']").find('a').hasClass('active')){
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
        none += (activeInfo.settings.homepage.ombi.enabled == true || activeInfo.settings.homepage.overseerr.enabled == true) ? `<button onclick="forceSearch('`+term+`')" class="btn btn-block btn-info" lang="en">Would you like to Request it?</button>` : '';
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
	//requests setup?
	if(activeInfo.settings.homepage.ombi.enabled == true || activeInfo.settings.homepage.overseerr.enabled == true){
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
	results = '<div class="media-results">' + results + '</div>';
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
function pingUpdateItem(ping){
	if(activeInfo.user.groupID > activeInfo.settings.ping.auth){
		return false;
	}
	organizrAPI2('GET','api/v2/ping/' + ping,).success(function(data) {
		try {
			var response = data.response;
		}catch(e) {
			organizrCatchError(e,data);
		}
		var i = ping;
		var v = response.data;
		var elm = $('.menu-'+cleanClass(i)+'-ping');
		var elmMs = $('.menu-'+cleanClass(i)+'-ping-ms');
		var catElm = elm.parent().parent().parent().parent().children('a').find('.menu-category-ping');
		var error = '<div class="ping"><span class="heartbit"></span><span class="point"></span></div>';
		var success = '';
		var badCount = 0;
		var goodCount = 0;
		var previousState = (elm.attr('data-previous-state') == "") ? '' : elm.attr('data-previous-state');
		var tabName = elm.attr('data-tab-name');
		var status = (v == null) ? 'down' : 'up';
		var ms = (v == null) ? 'down' : v+'ms';
		var sendMessage = (previousState !== status && previousState !== '' && activeInfo.user.groupID <= activeInfo.settings.ping.authMessage) ? true : false;
		var audioDown = (sendMessage) ? new Audio(activeInfo.settings.ping.offlineSound) : '';
		var audioUp = (sendMessage) ? new Audio(activeInfo.settings.ping.onlineSound) : '';
		elm.attr('data-previous-state', status);
		let listing = elm.parent().parent().parent().parent().children('a').find('.menu-category-ping').parent().parent().find('li').find("div[class$='-ping']");
		$.each(listing, function(i,v) {
			let state = $(v).attr('data-previous-state');
			if(state == 'up'){
				goodCount = goodCount + 1
			}else if(state == 'down'){
				badCount = badCount + 1;
			}
		})
		if(catElm.length > 0){
			catElm.attr('data-bad', badCount);
			catElm.attr('data-good', goodCount);
			if(badCount == 0){
				catElm.html(success);
			}
		}
		if(activeInfo.user.groupID <= activeInfo.settings.ping.authMs && activeInfo.settings.ping.ms){ elmMs.removeClass('hidden').html(ms); }
		switch (status){
			case 'down':
				elm.html(error);
				catElm.html(error);
				elm.parent().find('img').addClass('grayscale');
				var msg = (sendMessage) ? message(tabName,'Server Down',activeInfo.settings.notifications.position,'#FFF','error','600000') : '';
				var audio = (sendMessage && activeInfo.settings.ping.statusSounds) ? audioDown.play() : '';
				break;
			default:
				elm.html(success);
				elm.parent().find('img').removeClass('grayscale');
				var msg = (sendMessage) ? message(tabName,'Server Back Online',activeInfo.settings.notifications.position,'#FFF','success','600000') : '';
				var audio = (sendMessage && activeInfo.settings.ping.statusSounds) ? audioUp.play() : '';
		}


	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function pingUpdate(pingList,timeout){
	$.each(pingList, function(i,v) {
		pingUpdateItem(v);
	})
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
function message(heading,text,position,color,icon,timeout, single = false){
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
                    setTimeout(function(){ message(heading,text,position,color,icon,timeout, single); }, 100);
                }
            }
            break;
        default:
            var ready = false;
    }
    if(notificationsReady && ready){
        oldPosition = position;
        position = messagePositions()[position][bb];
	    if(typeof activeInfo === 'undefined'){
            setTimeout(function(){ message(heading,text,oldPosition,color,icon,timeout, single); }, 100);
            return false;
        }
	    if(single){
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
	            organizrConsole('Organizr Function','Message case not setup');
        }

    }else{
        setTimeout(function(){ message(heading,text,position,color,icon,timeout,single); }, 100);
    }

}
function messageSingle(heading,text,position,color,icon,timeout){
	message(heading,text,position,color,icon,timeout, true);
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
    organizrAPI2('POST','api/v2/users/lock','').success(function(data) {
        try {
            let html = data.response;
	        location.reload();
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'Lock Error');
    });
}
function openSettings(){
    var tab = $("li[data-url='api/v2/page/settings']").find('span').text();
    tabActions('click',tab,0);
}
function openHomepage(){
    var tab = $("li[data-url='api/v2/page/homepage']").find('span').text();
    tabActions('click',tab,0);
}
function toggleFullScreenIcon(){
	$('.fullscreen-icon').toggleClass('ti-fullscreen').toggleClass('mdi mdi-fullscreen-exit');
}
function toggleFullScreen() {
	toggleFullScreenIcon();
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
            $('#settings-settings-updates-anchor').trigger('click');
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
    var allTabs = $('.tabEditor');
    var tabList = [];
    $.each(allTabs, function(i,v) {
        tabList[i] = v.getAttribute('data-name').toLowerCase();
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
function getLatestBlackberryThemes() {
	return $.ajax({
		url: 'https://api.github.com/repos/Archmonger/Blackberry-Themes/contents/Themes',
	});
}
function getBlackberryTheme(theme) {
	return $.ajax({
		url: 'https://api.github.com/repos/Archmonger/Blackberry-Themes/contents/Themes/' +  theme + '/Icons',
	});
}
function showBlackberryThemes(target){
	getLatestBlackberryThemes().success(function(data) {
		try {
			let themes = '';
			$.each(data, function(i,v) {
				if(v.name !== 'Beta'){
					themes += `<a href="javascript:selectBlackberryTheme('${v.name}','${target}');" class="list-group-item"><span><img class="themeIcon pull-right" src="https://raw.githubusercontent.com/Archmonger/Blackberry-Themes/master/Themes/${v.name}/Icons/preview.png"></span>${v.name}</a>`;
				}
			});
			themes = `<div class="list-group">${themes}</div>`;
			let html = `
			<div class="panel">
				<div class="bg-org2">
					<div class="panel-heading">Choose a Theme</div>
					<div class="panel-body text-left">${themes}</div>
				</div>
			</div>
			`;
			swal({
				content: createElementFromHTML(html),
				button: 'Close',
				className: 'orgErrorAlert',
				dangerMode: true
			});
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function selectBlackberryTheme(theme, target){
	getBlackberryTheme(theme).success(function(data) {
		try {
			let icons = '';
			$.each(data, function(i,v) {
				v.name = v.name.split('.')[0];
				v.name = cleanClass(v.name);
				icons += `<a href="javascript:swal.close();$('#${target}').val('${v.download_url}')"><img alt="${v.name}" data-toggle="tooltip" data-placement="top" title="" data-original-title="${v.name}"src="${v.download_url}" ></a>`;
			});
			icons = `<div id="gallery-content-center">${icons}</div>`;
			let html = `
			<div class="panel">
				<div class="bg-org2">
					<div class="panel-heading">Choose an Icon</div>
					<div class="panel-body text-left">${icons}</div>
				</div>
			</div>
			`;
			swal({
				content: createElementFromHTML(html),
				buttons: {
					back: {
						text: "Back To Themes",
						value: "back",
						dangerMode: true,
						className: "bg-org-alt"
					}
				},
				className: 'orgErrorAlert',
				dangerMode: true
			})
			.then((value) => {
				switch (value) {
					case "back":
						showBlackberryThemes();
						break;
				}
			});
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function orgErrorAlert(error){
	let showError = false;
	if(typeof activeInfo === 'undefined'){
		showError = true;
	}else{
		if(activeInfo.settings.misc.debugErrors){
			showError = true;
		}
	}
    if(showError) {
	    let div = `
	    <div class="panel">
            <div class="bg-org2">
                <div class="panel-heading">ERROR</div>
                <div class="panel-body text-left">${error}</div>
            </div>
        </div>
	    `;
	    swal({
		    content: createElementFromHTML(div),
		    button: 'OK',
		    className: 'orgErrorAlert',
		    dangerMode: true
	    });
    }
}
function toggleDebug(){
	var div = `
	<div class="white-box m-0">
	    <div class="steamline">
	        <div class="sl-item">
	            <div class="sl-left bg-success"><i class="mdi mdi-code-tags"></i></div>
	            <div class="sl-right">
	                <div class="form-group">
	                    <div id="" class="input-group">
	                        <input id="debug-input" lang="en" placeholder="Input Command" type="text"
	                               class="form-control inline-focus">
	                        <div class="input-group-btn">
	                            <button type="button"
	                                    class="btn waves-effect waves-light btn-info dropdown-toggle"
	                                    data-toggle="dropdown" aria-expanded="false"><span lang="en">Commands</span>
	                                <span class="caret"></span></button>
	                            <ul class="dropdown-menu dropdown-menu-right">
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso');"
	                                       href="javascript:void(0)"
	                                       lang="en">SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.plex');"
	                                       href="javascript:void(0)"
	                                       lang="en">Plex SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.tautulli');"
	                                       href="javascript:void(0)"
	                                       lang="en">Tautulli SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.overseerr');"
	                                       href="javascript:void(0)"
	                                       lang="en">Overseerr SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.petio');"
	                                       href="javascript:void(0)"
	                                       lang="en">Petio SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.ombi');"
	                                       href="javascript:void(0)"
	                                       lang="en">Ombi SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.jellyfin');"
	                                       href="javascript:void(0)"
	                                       lang="en">Jellyfin SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.komga');"
	                                       href="javascript:void(0)"
	                                       lang="en">Komga SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.sso.misc');"
	                                       href="javascript:void(0)"
	                                       lang="en">Misc SSO</a></li>
	                                <li><a onclick="orgDebugList('activeInfo.settings.misc.schema');"
	                                       href="javascript:void(0)"
	                                       lang="en">DB Schema</a></li>
	                            </ul>
	                        </div>
	                    </div>
	                    <div class="clearfix"></div>
	                </div>
	            </div>
	        </div>
	        <div id="debugPreInfoBox" class="sl-item text-left">
	            <div class="sl-left bg-info"><i class="mdi mdi-package-variant-closed"></i></div>
	            <div class="sl-right">
	                <div>
	                    <span lang="en">Organizr Information:</span>&nbsp;
	                </div>
	                <div id="debugPreInfo" class="desc"></div>
	            </div>
	        </div>
	        <div id="debugResultsBox" class="sl-item hidden text-left">
	            <div class="sl-left bg-info"><i class="mdi mdi-receipt"></i></div>
	            <div class="sl-right">
	                <div><span lang="en">Results For cmd:</span>&nbsp;<span class="cmdName"></span>
	                </div>
	                <div id="debugResults" class="desc"></div>
	            </div>
	        </div>
	    </div>
	</div>
	`;
	swal({
		content: createElementFromHTML(div),
		button: "OK",
		className: 'orgErrorAlert',
	});
	getDebugPreInfo();
}
function toggleCalendarFilter(){
	var div = `
	<div id="calendar-filter-modal" class="panel panel-inverse">
        <div class="panel-heading"><span class="text-uppercase" lang="en">Filter Calendar</span></div>
        <div class="panel-wrapper collapse in" aria-expanded="true">
            <div class="panel-body">
	            <div class="row">
                    <div class="col-md-12">
                        <label class="control-label" lang="en">Choose Media Type</label>
                        <select class="form-control form-white" data-placeholder="Choose media type" id="choose-calender-filter">
                            <option value="all" lang="en">All</option>
                            <option value="tv" lang="en">TV</option>
                            <option value="film" lang="en">Movie</option>
                            <option value="music" lang="en">Music</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="control-label" lang="en">Choose Media Status</label>
                        <select class="form-control form-white" data-placeholder="Choose media status" id="choose-calender-filter-status">
                            <option value="all" lang="en">All</option>
                            <option value="text-success" lang="en">Downloaded</option>
                            <option value="text-info" lang="en">Unaired</option>
                            <option value="text-danger" lang="en">Missing</option>
                            <option value="text-primary animated flash" lang="en">Premier</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
	</div>
	`;
	swal({
		content: createElementFromHTML(div),
		className: 'bg-org',
		button: false
	});
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
function addCoordinatesToInput(latitude, longitude){
    $('#homepage-Weather-Air-form [name=homepageWeatherAndAirLatitude]').val(latitude).change();
    $('#homepage-Weather-Air-form [name=homepageWeatherAndAirLongitude]').val(longitude).change();
    swal.close();
    message('Coordinates Added','Please Save',activeInfo.settings.notifications.position,'#FFF','success','10000');
}
function searchCoordinatesAPI(query){
	messageSingle('Submitting Query','',activeInfo.settings.notifications.position,'#FFF','info','5000');
    organizrAPI2('POST','api/v2/homepage/weather/coordinates',{query:query}).success(function(data) {
        try {
            let html = data.response;
	        if(html.data.type == 'FeatureCollection'){
		        var entries = '';
		        $.each(html.data.features, function(i,v) {
			        entries += '<li class="text-left"><i class="fa fa-caret-right text-info"></i><span class="mouse" onclick="addCoordinatesToInput(\''+v.center[1]+'\',\''+v.center[0]+'\')">'+v.place_name+'</span></li>';
		        })
		        var div = `
		        <div class="row">
		            <div class="col-12">
		                <div class="card m-b-0">
		                    <div class="form-horizontal">
		                        <div class="card-body">
		                            <h4 class="card-title" lang="en">Select Place</h4>
		                            <div class="form-group row">
		                                <div class="col-sm-12">
		                                    <ul class="list-icons">
		                                        `+entries+`
		                                    </ul>
		                                </div>
		                            </div>
		                        </div>
		                    </div>
		                </div>
		            </div>
		        </div>
		        `;
		        if(entries !== ''){
			        swal.close();
			        swal({
				        content: createElementFromHTML(div),
				        buttons: false,
				        className: 'bg-org'
			        })
		        }else{
			        message('API Error','No results found...',activeInfo.settings.notifications.position,'#FFF','warning','10000');
		        }

	        }else{
		        message('API Error','',activeInfo.settings.notifications.position,'#FFF','warning','10000');
		        console.error('Organizr Function: API failed');
	        }
        }catch(e) {
	        organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
	    OrganizrApiError(xhr, 'API Error');
    });
}
function showLookupCoordinatesModal(){
    var div = `
    <div class="row">
        <div class="col-12">
            <div class="card m-b-0">
                <div class="form-horizontal">
                    <div class="card-body">
                        <h4 class="card-title" lang="en">Enter City or Address</h4>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <input type="text" class="form-control" id="coordinatesModalCityInput" placeholder="Enter City or Address...">
                            </div>
                        </div>
                        <div class="form-group mb-0 p-r-10 text-right">
                            <button type="submit" onclick="searchCoordinatesAPI($('#coordinatesModalCityInput').val())" class="btn btn-info waves-effect waves-light">Submit</button>
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
                                <button type="submit" onclick="testAPIConnection('ldap/login', {'username':$('#ldapUsernameTest').val(),'password':$('#ldapPasswordTest').val()})" class="btn btn-info waves-effect waves-light">Test Login</button>
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

function showPlexTokenForm(selector = null){
	var div = `
		<form id="get-plex-token-form">
		    <h1 lang="en">Get Plex Token</h1>
		    <div class="panel plexTokenHeader">
		        <div class="panel-heading plexTokenMessage" lang="en">Enter Plex Details</div>
		    </div>
		    <fieldset style="border:0;">
		        <div class="form-group">
		            <label class="control-label" for="plex-token-form-username" lang="en">Plex Username</label>
		            <input type="text" class="form-control" id="plex-token-form-username" name="username" required="" autofocus>
		        </div>
		        <div class="form-group">
		            <label class="control-label" for="plex-token-form-password" lang="en">Plex Password</label>
		            <input type="password" class="form-control" id="plex-token-form-password" name="password"  required="">
		        </div>
		        <div class="form-group">
		            <label class="control-label" for="plex-token-form-tfa" lang="en">Plex 2FA (if applicable)</label>
		            <input type="text" class="form-control" id="plex-token-form-tfa" name="tfa" >
		        </div>
		    </fieldset>
		    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none" onclick="getPlexToken('`+selector+`')" type="button"><span class="btn-label"><i class="fa fa-ticket"></i></span><span lang="en">Grab It</span></button>
		    <div class="clearfix"></div>
		</form>
	`;
	swal({
		content: createElementFromHTML(div),
		buttons: false,
		className: 'bg-org'
	})
}
function getPlexToken(selector) {
	$('.plexTokenMessage').text("Grabbing Token");
	$('.plexTokenHeader').addClass('panel-info').removeClass('panel-warning').removeClass('panel-danger');
	var plex_username = $('#get-plex-token-form [name=username]').val().trim();
	var plex_password = $('#get-plex-token-form [name=password]').val().trim();
	var plex_tfa = $('#get-plex-token-form [name=tfa]').val().trim();
	if ((plex_password !== '') && (plex_password !== '')) {
		$.ajax({
			type: 'POST',
			headers: {
				'X-Plex-Product':'Organizr',
				'X-Plex-Version':'2.0',
				'X-Plex-Client-Identifier':'01010101-10101010'
			},
			url: 'https://plex.tv/users/sign_in.json',
			data: {
				'user[login]': plex_username,
				'user[password]': plex_password + plex_tfa,
				force: true
			},
			cache: false,
			async: true,
			complete: function(xhr, status) {
				var result = $.parseJSON(xhr.responseText);
				if (xhr.status === 201) {
					$('.plexTokenMessage').text(xhr.statusText);
					$('.plexTokenHeader').addClass('panel-success').removeClass('panel-info').removeClass('panel-warning').removeClass('panel-danger');
					$(selector).val(result.user.authToken);
					$(selector).change();
					messageSingle('Token created','Please save...',activeInfo.settings.notifications.position,'#FFF','success','5000');
				} else {
					$('.plexTokenMessage').text(xhr.statusText);
					$('.plexTokenHeader').addClass('panel-danger').removeClass('panel-info').removeClass('panel-warning');
				}
			}
		});
	} else {
		$('.plexTokenMessage').text("Enter Username and Password");
		$('.plexTokenHeader').addClass('panel-warning').removeClass('panel-info').removeClass('panel-danger');
	}
}
function showPlexMachineForm(selector = null){
	var div = `
		<form id="get-plex-machine-form">
		    <h1 lang="en">Get Plex Machine</h1>
		    <div class="panel plexMachineHeader">
		        <div class="panel-heading plexMachineMessage" lang="en">Contacting server...</div>
		    </div>
		    <fieldset style="border:0;">
		        <div class="form-group">
		            <label class="control-label" for="plex-machine-form-machine" lang="en">Plex Machine</label>
		            <div class="plexMachineListing"></div>
		        </div>
		    </fieldset>
		    <div class="clearfix"></div>
		</form>
	`;
	swal({
		content: createElementFromHTML(div),
		buttons: false,
		className: 'bg-org'
	})
	.then(
		organizrAPI2('GET','api/v2/plex/servers?owned').success(function(data) {
			try {
				let response = data.response;
				$('.plexMachineMessage').text('Choose Plex Server');
				$('.plexMachineHeader').addClass('panel-success').removeClass('panel-info').removeClass('panel-warning');
				let machines = '<option lang="en">Choose Plex Machine</option>';
				$.each(response.data, function(i,v) {
					let name = v.name;
					let machine = v.machineIdentifier;
					name = name + ' [' + machine + ']';
					machines += '<option value="'+machine+'">'+name+'</option>';
				})
				let listing = '<select class="form-control" id="plexMachineSelector" data-selector="'+selector+'" data-type="select">'+machines+'</select>';
				$('.plexMachineListing').html(listing);
			}catch(e) {
				organizrCatchError(e,data);
			}
		}).fail(function(xhr) {
			OrganizrApiError(xhr, 'API Error');
			$('.plexMachineMessage').text("Plex Token Needed First");
			$('.plexMachineHeader').addClass('panel-warning').removeClass('panel-info').removeClass('panel-danger');
		})
	);
}
function oAuthLoginNeededCheck() {
    if(OAuthLoginNeeded == false){
        return false;
    }else{
        if(activeInfo.user.loggedin == true){
            return false;
        }
    }
    message('OAuth', ' Proceeding to login', activeInfo.settings.notifications.position, '#FFF', 'info', '10000');
    organizrAPI2('POST', 'api/v2/login', '').success(function (data) {
	    local('set','message','Welcome|Login Successful|success');
	    local('r','loggingIn');
	    location.reload();
    }).fail(function (xhr) {
	    $('div.login-box').unblock({});
	    switch (xhr.status){
		    case 401:
			    if(xhr.responseJSON.response.message == '2FA Code incorrect'){
				    $('div.login-box').unblock({});
				    $('#tfa-div').removeClass('hidden');
				    $('#loginform [name=tfaCode]').focus();
			    }
			    break;
		    case 403:
			    $('div.login-box').block({
				    message: '<h5><i class="fa fa-close"></i> Locked Out!</h4>',
				    css: {
					    color: '#fff',
					    border: '1px solid #e91e63',
					    backgroundColor: '#f44336'
				    }
			    });
			    setTimeout(function(){ local('r','loggingIn'); location.reload() }, 10000);
			    break;
		    case 422:
			    $('div.login-box').unblock({});
			    $('#tfa-div').removeClass('hidden');
			    $('#loginform [name=tfaCode]').focus();
			    break;
		    default:
			    message('Login Error', 'API Connection Failed', activeInfo.settings.notifications.position, '#FFF', 'error', '10000');
			    console.error("Organizr Function: API Connection Failed");
	    }
	    message('Login Error', xhr.responseJSON.response.message, activeInfo.settings.notifications.position, '#FFF', 'warning', '10000');
	    console.error("Organizr Function: " + xhr.responseJSON.response.message);
	    local('r','loggingIn');
    });
}
function ipInfoSpan(ip){
    return '<span class="ipInfo mouse">'+ip+'</span>';
}
function jsFriendlyJSONStringify (s) {
	return JSON.stringify(s).
	replace('\'', "").
	replace(/\u2028/g, '\\u2028').
	replace(/\u2029/g, '\\u2029');
}
function logContext(row){
	let buttons = '';
	buttons += (Object.keys(row).length > 0) ? '<button data-toggle="tooltip" title="" data-original-title="View Details" class="btn btn-xs btn-primary waves-effect waves-light log-details m-r-5" data-trace="'+row.trace_id+'"><i class="mdi mdi-file-find"></i></button>' : '';
	buttons += (Object.keys(row).length > 0) ? '<button data-toggle="tooltip" title="" data-original-title="Copy Log" class="btn btn-xs btn-info waves-effect waves-light log-details m-r-5" data-trace="'+row.trace_id+'" data-clipboard="true"><i class="mdi mdi-content-copy"></i></button>' : '';
	return buttons;
}
function formatLogDetails(details){
	if(!details){
		return false;
	}
	let m = moment.tz(details.datetime + 'Z', activeInfo.timezone);
	details.datetime = moment(m).format('LLL');
	let items = '';
	items += `<li><div class="bg-inverse"><i class="mdi mdi-calendar-text text-white"></i></div> ${details.datetime}<span class="text-muted" lang="en">Date</span></li>`;
	items += `<li><div class="bg-warning"><i class="mdi mdi-robot text-white"></i></div> ${details.trace_id}<span class="text-muted" lang="en">Trace ID</span></li>`;
	items += `<li><div class="bg-primary"><i class="mdi mdi-account-box-outline text-white"></i></div> ${details.username}<span class="text-muted" lang="en">User</span></li>`;
	items += `<li><div class="bg-info"><i class="mdi mdi-function text-white"></i></div> ${details.channel}<span class="text-muted" lang="en">Function</span></li>`;
	items += `<li><div class="bg-plex"><i class="mdi mdi-language-php text-white"></i></div> ${details.file}<code>#L${details.line}</code><span class="text-muted" lang="en">File</span></li>`;
	let items2 = '';
	items2 += (Object.keys(details.context).length > 0) ? `<div class="sl-item"><div class="sl-left bg-inverse"> <i class="mdi mdi-json"></i></div><div class="sl-right"><div class="p-t-10 desc" lang="en">Context</div></div><pre class="m-5 fc-scroller">${JSON.stringify(details.context,null, 5)}</pre></div>` : '';
	items2 += (typeof details.errors !== 'undefined') ? `<div class="sl-item"><div class="sl-left bg-danger"> <i class="mdi mdi-code-braces"></i></div><div class="sl-right"><div class="p-t-10 desc" lang="en">Errors</div></div><pre class="m-5 fc-scroller">${JSON.stringify(details.errors,null, 5)}</pre></div>` : '';
	var div = `
		<div class="col-lg-12">
			<div class="panel panel-default text-left">
				<div class="panel-heading"><i class="mdi mdi-file-find fa-lg fa-2x"></i> <span lang="en">Log Details</span> <span class="pull-right">${logIcon(details.log_level, true)}</span></div>
				<div class="panel-wrapper collapse in">
					<div class="panel-body bg-org">
						<h3>${details.message}</h3>
						<div class="white-box">
							<ul class="feeds">
								${items}
							</ul>
						</div>
						<div class="steamline">
							${items2}
						</div>
					</div>
				</div>
			</div>
		</div>`;
	swal({
		content: createElementFromHTML(div),
		buttons: false,
		className: 'orgAlertTransparent'
	});
	pageLoad();
}
function checkToken(activate = false){
    if(typeof activeInfo !== 'undefined'){
        if(typeof activeInfo.settings.misc.uuid !== 'undefined'){
            var token = getCookie('organizr_token_' + activeInfo.settings.misc.uuid);
            if(token){
                setTimeout(function(){ checkToken(true); }, 5000);
            }else{
                if(activate){
                    local('set','message','Token Expired|You have been logged out|error');
                    location.reload();
                }
            }
        }
    }
}
function objDiff(obj1, obj2) {

	// Make sure an object to compare is provided
	if (!obj2 || Object.prototype.toString.call(obj2) !== '[object Object]') {
		return obj1;
	}

	//
	// Variables
	//

	var diffs = {};
	var key;


	//
	// Methods
	//

	/**
	 * Check if two arrays are equal
	 * @param  {Array}   arr1 The first array
	 * @param  {Array}   arr2 The second array
	 * @return {Boolean}      If true, both arrays are equal
	 */
	var arraysMatch = function (arr1, arr2) {

		// Check if the arrays are the same length
		if (arr1.length !== arr2.length) return false;

		// Check if all items exist and are in the same order
		for (var i = 0; i < arr1.length; i++) {
			if (arr1[i] !== arr2[i]) return false;
		}

		// Otherwise, return true
		return true;

	};

	/**
	 * Compare two items and push non-matches to object
	 * @param  {*}      item1 The first item
	 * @param  {*}      item2 The second item
	 * @param  {String} key   The key in our object
	 */
	var compare = function (item1, item2, key) {

		// Get the object type
		var type1 = Object.prototype.toString.call(item1);
		var type2 = Object.prototype.toString.call(item2);

		// If type2 is undefined it has been removed
		if (type2 === '[object Undefined]') {
			diffs[key] = null;
			return;
		}

		// If items are different types
		if (type1 !== type2) {
			diffs[key] = item2;
			return;
		}

		// If an object, compare recursively
		if (type1 === '[object Object]') {
			var objDiff = diff(item1, item2);
			if (Object.keys(objDiff).length > 1) {
				diffs[key] = objDiff;
			}
			return;
		}

		// If an array, compare
		if (type1 === '[object Array]') {
			if (!arraysMatch(item1, item2)) {
				diffs[key] = item2;
			}
			return;
		}

		// Else if it's a function, convert to a string and compare
		// Otherwise, just compare
		if (type1 === '[object Function]') {
			if (item1.toString() !== item2.toString()) {
				diffs[key] = item2;
			}
		} else {
			if (item1 !== item2 ) {
				diffs[key] = item2;
			}
		}

	};


	//
	// Compare our objects
	//

	// Loop through the first object
	for (key in obj1) {
		if (obj1.hasOwnProperty(key)) {
			compare(obj1[key], obj2[key], key);
		}
	}

	// Loop through the second object and find missing items
	for (key in obj2) {
		if (obj2.hasOwnProperty(key)) {
			if (!obj1[key] && obj1[key] !== obj2[key] ) {
				diffs[key] = obj2[key];
			}
		}
	}

	// Return the object of differences
	return diffs;

}
function organizrConsole(subject,msg,type = 'info'){

	let color;
	switch (type){
		case 'error':
			color = '#ed2e72';
			break;
		case 'warning':
			color = '#272361';
			break;
		default:
			color = '#2cabe3';
			break;

	}

	console.info("%c "+subject+" %c ".concat(msg, " "), "color: white; background: "+color+"; font-weight: 700;", "color: "+color+"; background: white; font-weight: 700;");
}
function organizrCatchError(e,data){
	organizrConsole('Organizr API Function',data,'warning');
	orgErrorAlert('<h4>' + e + '</h4><p><mark lang="en">Trace Log has been outputted to Browser Console</mark></p><h5 lang="en">Output of last API call</h5>' + formatDebug(data));
	console.trace();
	return false;
}
function OrganizrApiError(xhr, secondaryMessage = null){
	let msg = '';
	if(typeof xhr.responseJSON !== 'undefined'){
		msg = xhr.responseJSON.response.message;
	}else if(typeof xhr.statusText !== 'undefined'){
		msg = xhr.statusText;
	}else if(typeof xhr.responseText !== 'undefined'){
		msg = xhr.responseText;
	}else{
		msg = 'Connection Error';
	}
	organizrConsole('Organizr API Function',msg,'error');

	if(msg !== 'abort') {
		if(secondaryMessage){
			messageSingle(secondaryMessage, msg, activeInfo.settings.notifications.position, '#FFF', 'error', '10000');
		}
		console.trace();
	}
	return false;
}
function checkForUpdates(){
	if(activeInfo.user.loggedin && activeInfo.user.groupID <= 1){
		updateCheck();
		checkCommitLoad();
		checkPluginUpdates();
	}
}

function loadJavascript(script = null, defer = false){
	if(script){
		organizrConsole('JS Loader',script);
		organizrConsole('JS Loader','Checking if script is loaded...');
		let loaded = $('script[src="'+script+'"]').length;
		if(!loaded){
			organizrConsole('JS Loader','Script is NOT loaded... Loading now...');
			let head = document.getElementsByTagName('head')[0];
			let scriptEl = document.createElement('script');
			scriptEl.type = 'text/javascript';
			scriptEl.src = script;
			scriptEl.defer = false;
			head.appendChild(scriptEl);
		}else{
			organizrConsole('JS Loader','Script already loaded');
		}
	}
}

function tabShit(){

}

function msToTime(s) {
	let pad = (n, z = 2) => ('00' + n).slice(-z);
	let hours = (pad(s/3.6e6|0) !== '00') ? pad(s/3.6e6|0) + ':' : '';
	let mins = pad((s%3.6e6)/6e4 | 0) + ':';
	let secs = pad((s%6e4)/1000|0);
	let ms = pad(s%1000, 3);
	if(ms >= '500'){ secs = pad(parseFloat(secs) + 1, 2); }
	return hours+mins+secs;
}
function clickSettingsTab(){
	let tabs = $('.allTabsList');
	$.each(tabs, function(i,v) {
		let tab = $(v);
		if(tab.attr('data-url') == 'api/v2/page/settings'){
			tab.find('a').trigger('click');
		}
	});
}
function clickMenuItem(selector){
	if($(selector).length >= 1){
		$(selector).click();
	}else{
		$('body').arrive(selector, {onceOnly: true}, function() {
			$(selector).click();
		});
	}

}
function shortcut(selectors = ''){
	let timeout = 200;
	if(typeof selectors == 'string') {
		if(selectors == ''){
			selectors = [];
		}else{
			switch (selectors){
				case 'plugin-marketplace':
					clickSettingsTab();
					selectors = ['#settings-main-plugins-anchor', '#settings-plugins-marketplace-anchor'];
					break;
				case 'custom-cert':
					clickSettingsTab();
					selectors = ['#settings-main-system-settings-anchor','#settings-settings-main-anchor','a[href$="Certificate"]'];
					break;
				default:
					clickSettingsTab();
					selectors = ['#settings-main-system-settings-anchor'];

			}
		}
	}
	selectors.forEach(function(selector){
		timeout = timeout + 200;
		setTimeout(function(){
			clickMenuItem(selector);
		}, timeout);
	});
}
function getJournalMode(){
	organizrAPI2('GET','api/v2/database/journal').success(function(data) {
		try {
			let response = data.response;
			$('.journal-mode').html(response.data.journal_mode);
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function setJournalMode(mode){
	messageSingle('Setting New Journal Mode','',activeInfo.settings.notifications.position,"#FFF","info","1500");
	organizrAPI2('PUT','api/v2/database/journal/' + mode, {}).success(function(data) {
		try {
			getJournalMode();
			let response = data.response;
			message('Set New Journal Mode',response.data.journal_mode,activeInfo.settings.notifications.position,"#FFF","success","5000");
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
}
function toggleSideMenuClasses(){
	$('#page-wrapper').toggleClass('sidebar-hidden');
	$('.sidebar').toggleClass('sidebar-hidden');
	$('.navbar').toggleClass('sidebar-hidden');
}
function sideMenuCollapsed(){
	if(activeInfo.settings.misc.sideMenuCollapsed){
		toggleSideMenuClasses();
	}
}
function toggleSideMenu(){
	toggleSideMenuClasses();
	$('.sidebar-head .open-close i').first().toggleClass('ti-menu ti-shift-left mouse');
	$('.toggle-side-menu').toggleClass('hidden');
}

function toggleTopBarHamburger(){
	toggleSideMenuClasses();
	$('.sidebar-head .hide-menu.hidden-xs').text('Hide Menu');
	$('.sidebar-head .open-close i').first().toggleClass('ti-menu ti-shift-left mouse');
	$('.toggle-side-menu').toggleClass('hidden');
}
function toggleLogFilter(filter = 'INFO'){
	//choose-organizr-log
	filter = filter.toUpperCase();
	$.each($('.choose-organizr-log').children(), function(i,v) {
		let url = $(v).val();
		let newURL = updateUrlParameter(url,'filter',filter)
		$(v).val(newURL);
	});
	$('.log-filter-text').text(filter);
	$('.log-filter-text').text(filter);
	let currentURL = organizrLogTable.ajax.url();
	let updatedURL = updateUrlParameter(currentURL,'filter',filter);
	organizrLogTable.ajax.url(updatedURL);
	organizrLogTable.clear().draw().ajax.reload(null, false);
}
function updateUrlParameter(uri, key, value) {
	// remove the hash part before operating on the uri
	var i = uri.indexOf('#');
	var hash = i === -1 ? ''  : uri.substr(i);
	uri = i === -1 ? uri : uri.substr(0, i);
	var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
	var separator = uri.indexOf('?') !== -1 ? "&" : "?";
	if (value === null) {
		// remove key-value pair if value is specifically null
		uri = uri.replace(new RegExp("([?&]?)" + key + "=[^&]*", "i"), '');
		if (uri.slice(-1) === '?') {
			uri = uri.slice(0, -1);
		}
		// replace first occurrence of & by ? if no ? is present
		if (uri.indexOf('?') === -1) uri = uri.replace(/&/, '?');
	} else if (uri.match(re)) {
		uri = uri.replace(re, '$1' + key + "=" + value + '$2');
	} else {
		uri = uri + separator + key + "=" + value;
	}
	return uri + hash;
}
function launch(){
	console.info('https://docs.organizr.app/help/faq/migration-guide#version-2-0-greater-than-version-2-1');
	organizrConsole('API V2 API','If you see a 404 Error for api/v2/launch below this line, you have not setup the new location block... See URL above this line', 'error');
	organizrConnect('api/v2/launch').success(function (data) {
        try {
            let json = data.response;
	        if(json.data.user == false){ location.reload(); }
	        currentVersion = json.data.version;
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
		        branch:json.data.branch,
		        sso:json.data.sso,
		        settings:json.data.settings,
		        appearance:json.data.appearance,
		        theme:json.data.theme,
		        style:json.data.style,
		        version:json.data.version
	        };
	        // Add element to signal activeInfo Ready
	        $('#wrapper').after('<div id="activeInfo"></div>');
	        console.info("%c Organizr %c ".concat(currentVersion, " "), "color: white; background: #66D9EF; font-weight: 700; font-size: 24px; font-family: Monospace;", "color: #66D9EF; background: white; font-weight: 700; font-size: 24px; font-family: Monospace;");
	        console.info("%c Status %c ".concat("Starting Up...", " "), "color: white; background: #F92671; font-weight: 700;", "color: #F92671; background: white; font-weight: 700;");
	        //local('set','initial',true);
	        //setTimeout(function(){ local('r','initial'); }, 300);
	        defineNotification();
	        checkMessage();
	        errorPage();
	        uriRedirect();
	        changeStyle(activeInfo.style);
	        changeTheme(activeInfo.theme);
	        setSSO();
	        checkToken();
	        switch (json.data.status.status) {
		        case "wizard":
			        buildWizard();
			        buildLanguage('wizard');
			        break;
		        case "dependencies":
			        buildDependencyCheck(json);
			        break;
		        case "ok":
			        loadAppearance(json.data.appearance);
			        sideMenuCollapsed();
			        if(activeInfo.user.locked == 1){
				        buildLockscreen();
			        }else{
				        userMenu(json);
				        categoryProcess(json);
				        tabProcess(json);
				        buildSplashScreen(json);
				        accountManager(json);
				        organizrSpecialSettings(json.data);
				        getPingList(json);
				        checkLocalForwardStatus(json.data);
				        checkForUpdates();
			        }
			        loadCustomJava(json.data.appearance);
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
	        console.info("%c Organizr %c ".concat("DOM Fully loaded", " "), "color: white; background: #AD80FD; font-weight: 700;", "color: #AD80FD; background: white; font-weight: 700;");
	        oAuthLoginNeededCheck();
        } catch (e) {
            orgErrorCode(data);
            defineNotification();
            message('FATAL ERROR',data,'br','#FFF','error','60000');
            return false;
        }
	}).fail(function(xhr) {
		defineNotification();
		if(xhr.status == 404){
			orgErrorAlert('<h2>Webserver not setup for Organizr v2.1</h2><h4>Please goto <a href="https://docs.organizr.app/help/faq/migration-guide#version-2-0-greater-than-version-2-1">Migration guide to complete the changes...</a></h4><h3>Webserver Error:</h3>' + xhr.responseText);
			message('FATAL ERROR','You need to update webserver location block... check browser console for migration URL','br','#FFF','error','60000');
		}else{
			orgErrorAlert('<h3>Webserver Error:</h3>' + xhr.responseText);
		}
	});
}

function homepageBookmarks(timeout){
    var timeout = (typeof timeout !== 'undefined') ? timeout : activeInfo.settings.homepage.refresh.homepageBookmarksRefresh;
    organizrAPI2('GET','api/v2/plugins/bookmark/page').success(function(data) {
        try {
            let response = data.response;
            document.getElementById('homepageOrderBookmarks').innerHTML = '';
            if(response.data !== null){
                $('#homepageOrderBookmarks').html(buildBookmarks(response.data));
            }
        }catch(e) {
            organizrCatchError(e,data);
        }
    }).fail(function(xhr) {
        OrganizrApiError(xhr);
    });
    let timeoutTitle = 'Bookmarks-Homepage';
    if(typeof timeouts[timeoutTitle] !== 'undefined'){ clearTimeout(timeouts[timeoutTitle]); }
    timeouts[timeoutTitle] = setTimeout(function(){ homepageBookmarks(timeout); }, timeout);
    delete timeout;
}

function buildBookmarks(data){
    var returnData = data;
    return returnData;
}
