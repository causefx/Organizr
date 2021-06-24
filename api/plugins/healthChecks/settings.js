/* HEALTHCHECKS.IO JS FILE */

// FUNCTIONS

// EVENTS and LISTENERS

// CHANGE CUSTOMIZE Options
//
$(document).on('click', '#HEALTHCHECKS-settings-button', function() {
    ajaxloader(".content-wrap","in");
    organizrAPI2('GET','api/v2/plugins/healthchecks/settings').success(function(data) {
        var response = data.response;
        $('#HEALTHCHECKS-settings-items').html(buildFormGroup(response.data));
        var elAddButtonStart = $('#HEALTHCHECKS-settings-page [id*="Services"] .row.start');
        var items = $('#HEALTHCHECKS-settings-page [id*="Services"] .row.m-b-40 span');
        $(elAddButtonStart).after('<div class="row"><button type="button" class="btn btn-info pull-right m-r-20 addNewHCService" ><i class="fa fa-plus"></i> Add New Service</button><button type="button" class="btn btn-primary pull-right m-r-20 importNewHCService" ><i class="fa fa-database"></i> Import Services</button></div>');
        $.each(items, function(key,val) {
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
$(document).on('click', '.importNewHCService', function() {
	messageSingle('',' Grabbing checks...',activeInfo.settings.notifications.position,'#FFF','info','10000');
	var apiUrl = 'api/v2/homepage/healthchecks';
	organizrAPI2('GET',apiUrl).success(function(data) {
		try {
			let response = data.response;
			if(response.data !== null){
				if(response.data === false){ return ''; }
				let checks = (typeof response.data.content.checks !== 'undefined') ? response.data.content.checks : false;
				if(checks){
					let checksAlreadySetup = $('#HEALTHCHECKS-settings-page').serializeToJSON();
					if(typeof checksAlreadySetup['HEALTHCHECKS-all-items'] !== 'undefined'){
						checksAlreadySetup = checksAlreadySetup['HEALTHCHECKS-all-items'];
					}else{
						checksAlreadySetup = null;
					}
					$.each(checks, function(i,v) {
						let alreadySetup = false;
						let uuid = v.ping_url;
						uuid = uuid.match(/([0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12})/g);
						uuid = uuid[0];
						if(uuid){
							if(checksAlreadySetup){
								$.each(checksAlreadySetup, function(index,val) {
									if(val.uuid == uuid){
										alreadySetup = true;
									}
								});
							}
							if(alreadySetup == false){
								$('.addNewHCService').click();
								let nameElement = $('#HEALTHCHECKS-settings-page [name*="HEALTHCHECKS-all-items"]').eq(-6).attr('name');
								let uuidElement = $('#HEALTHCHECKS-settings-page [name*="HEALTHCHECKS-all-items"]').eq(-5).attr('name');
								$('#HEALTHCHECKS-settings-page [name*="'+nameElement+'"]').val(v.name);
								$('#HEALTHCHECKS-settings-page [name*="'+uuidElement+'"]').val(uuid);
								$('#HEALTHCHECKS-settings-page [name*="HEALTHCHECKS-all-items"]').eq(-6).parent().parent().parent().parent().attr('style', 'background: #707cd2; color:white');
							}else{
								console.log(v.name + ' is ALREADY SETUP');
							}
						}
					});
					messageSingle('Import',' Import has completed...',activeInfo.settings.notifications.position,'#FFF','info','10000');
				}else{
					messageSingle('',' No HealthChecks were setup',activeInfo.settings.notifications.position,'#FFF','error','10000');
				}
			}
		}catch(e) {
			organizrCatchError(e,data);
		}
	}).fail(function(xhr) {
		OrganizrApiError(xhr);
	});
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