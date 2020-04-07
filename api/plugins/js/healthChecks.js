/* PHP MAILER JS FILE */
/*
$(document).on('click', '#PHPMAILER-settings-button', function() {
	var post = {
        plugin:'PHPMailer/settings/get', // used for switch case in your API call
        api:'api/?v1/plugin', // API Endpoint will always be this for custom plugin API calls
        name:$(this).attr('data-plugin-name'),
        configName:$(this).attr('data-config-name'),
        messageTitle:'', // Send succees message title (top line)
        messageBody:'Disabled '+$(this).attr('data-plugin-name'), // Send succees message body (bottom line)
        error:'Organizr Function: API Connection Failed' // conole error message
    };
	var callbacks = $.Callbacks(); // init callbacks var
    //callbacks.add(  ); // add function to callback to be fired after API call
    //settingsAPI(post,callbacks); // exec API call
    //ajaxloader(".content-wrap","in");
    //setTimeout(function(){ buildPlugins();ajaxloader(); }, 3000);
});
*/

// FUNCTIONS

// EVENTS and LISTENERS

// CHANGE CUSTOMIZE Options
//
$(document).on('click', '#HEALTHCHECKS-settings-button', function() {
    var post = {
        plugin:'HealthChecks/settings/get', // used for switch case in your API call
    };
    ajaxloader(".content-wrap","in");
    organizrAPI('POST','api/?v1/plugin',post).success(function(data) {
        var response = JSON.parse(data);
        $('#HEALTHCHECKS-settings-items').html(buildFormGroup(response.data));
        var elAddButtonStart = $('#HEALTHCHECKS-settings-page [id*="Services"] .row.start');
        var testone = $('#HEALTHCHECKS-settings-page [id*="Services"] .row.m-b-40').first('span')
        var testtwo = $('#HEALTHCHECKS-settings-page [id*="Services"] .row.m-b-40 span')
        $(elAddButtonStart).after('<div class="row"><button type="button" class="btn btn-info pull-right m-r-20 addNewHCService" ><i class="fa fa-plus"></i> Add New Service</button></div>');
        $.each(testtwo, function(key,val) {
            var el = $(val);
            var text = el.text();
            if(text === 'Service Name'){
                $(this).after('&nbsp;<div class="pull-right text-danger removeHCService mouse"><i class="fa fa-close text-danger"></i></div>');
            }
        })

    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    ajaxloader();
});
$(document).on('click', '.addNewHCService', function() {
    var lastEl = $('#HEALTHCHECKS-settings-page [name*="HEALTHCHECKS-all-items"]').last().attr('name');
    var newNum = 0;
    if(typeof lastEl !== 'undefined'){
        lastEl = Number($('#HEALTHCHECKS-settings-page [name*="HEALTHCHECKS-all-items"]').last().attr('name').replace(/\D/g, ''));
        newNum = lastEl + 1;
    }
    var copyEl = '' +
        '<div class="row m-b-40">\n' +
        '\t<!-- INPUT BOX  Yes Multiple -->\n' +
        '\t<div class="col-md-6 p-b-10">\n' +
        '\t\t<div class="form-group">\n' +
        '\t\t\t<label class="control-label col-md-12"><span lang="en">Service Name</span>&nbsp;<div class="pull-right text-danger removeHCService mouse"><i class="fa fa-close text-danger"></i></div></label>\n' +
        '\t\t\t<div class="col-md-12"> <input data-changed="false" lang="en" type="text" class="form-control" value="" name="HEALTHCHECKS-all-items[999999].name" data-type="input" data-label="Service Name" autocomplete="new-password"> </div> <!-- end div -->\n' +
        '\t\t</div>\n' +
        '\t</div>\n' +
        '\t<!--/ INPUT BOX -->\n' +
        '\n' +
        '\t<!-- INPUT BOX  Yes Multiple -->\n' +
        '\t<div class="col-md-6 p-b-10">\n' +
        '\t\t<div class="form-group">\n' +
        '\t\t\t<label class="control-label col-md-12"><span lang="en">UUID</span></label>\n' +
        '\t\t\t<div class="col-md-12"> <input data-changed="false" lang="en" type="text" class="form-control" value="" name="HEALTHCHECKS-all-items[999999].uuid" data-type="input" data-label="UUID" autocomplete="new-password"> </div> <!-- end div -->\n' +
        '\t\t</div>\n' +
        '\t</div>\n' +
        '\t<!--/ INPUT BOX -->\n' +
        '\n' +
        '\t<!-- INPUT BOX  Yes Multiple -->\n' +
        '\t<div class="col-md-6 p-b-10">\n' +
        '\t\t<div class="form-group">\n' +
        '\t\t\t<label class="control-label col-md-12"><span lang="en">External URL</span></label>\n' +
        '\t\t\t<div class="col-md-12"> <input data-changed="false" lang="en" type="text" class="form-control" value="" name="HEALTHCHECKS-all-items[999999].external" data-type="input" data-label="External URL" autocomplete="new-password"> </div> <!-- end div -->\n' +
        '\t\t</div>\n' +
        '\t</div>\n' +
        '\t<!--/ INPUT BOX -->\n' +
        '\n' +
        '\t<!-- INPUT BOX  Yes Multiple -->\n' +
        '\t<div class="col-md-6 p-b-10">\n' +
        '\t\t<div class="form-group">\n' +
        '\t\t\t<label class="control-label col-md-12"><span lang="en">Internal URL</span></label>\n' +
        '\t\t\t<div class="col-md-12"> <input data-changed="false" lang="en" type="text" class="form-control" value="" name="HEALTHCHECKS-all-items[999999].internal" data-type="input" data-label="Internal URL" autocomplete="new-password"> </div> <!-- end div -->\n' +
        '\t\t</div>\n' +
        '\t</div>\n' +
        '\t<!--/ INPUT BOX -->\n' +
        '\n' +
        '\t<!-- INPUT BOX  Yes Multiple -->\n' +
        '\t<div class="col-md-6 p-b-10">\n' +
        '\t\t<div class="form-group">\n' +
        '\t\t\t<label class="control-label col-md-12"><span lang="en">Enabled</span></label>\n' +
        '\t\t\t<div class="col-md-12"> <input data-changed="false" type="checkbox" class="js-switch" data-size="small" data-color="#99d683" data-secondary-color="#f96262" name="HEALTHCHECKS-all-items[999999].enabled" value="" checked="" data-type="switch" data-label="Enabled"><input data-changed="false" type="hidden" name="HEALTHCHECKS-all-items[999999].enabled" value=""> </div> <!-- end div -->\n' +
        '\t\t</div>\n' +
        '\t</div>\n' +
        '\t<!--/ INPUT BOX -->\n' +
        '</div>'
//smallLabel+'<input data-changed="false" type="checkbox" class="js-switch'+extraClass+'" data-size="small" data-color="#99d683" data-secondary-color="#f96262"'+name+value+tof(item.value,'c')+id+disabled+type+label+attr+' /><input data-changed="false" type="hidden"'+name+'value="false">';
    var elAddButtonStart = $('#HEALTHCHECKS-settings-page [id*="Services"] .row.start');
    var copiedEl = $(copyEl).clone();
    copiedEl.find("input").each(function() {
        var currentName = $(this).attr("name");
        var newName = currentName.replace('999999', newNum);
        $(this).attr("name", newName);
        $(this).attr("value", "");
    });
    $(copiedEl).appendTo(elAddButtonStart);
    $(function () {
        // Switchery
        var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
        $('.js-switch').each(function() {
            if ($(this).attr('data-switchery') !== 'true'){
                new Switchery($(this)[0], $(this).data());
            }
        });
    });

});

$(document).on('click', '.removeHCService', function() {
    $(this).closest('.row').remove();
    $('#HEALTHCHECKS-settings-page-save').removeClass('hidden');
});