<?php
$GLOBALS['organizrPages'][] = 'settings_user_manage_users';
function get_page_settings_user_manage_users($Organizr)
{
	if (!$Organizr) {
		$Organizr = new Organizr();
	}
	if ((!$Organizr->hasDB())) {
		return false;
	}
	if (!$Organizr->qualifyRequest(1, true)) {
		return false;
	}
	return '
<script>
	$(document).ready(function($) {
        
    }), jsGrid.setDefaults({
        tableClass: "jsgrid-table table table-striped table-hover"
    }), jsGrid.setDefaults("text", {
        _createTextBox: function() {
            return $("<input>").attr("type", "text").attr("class", "form-control input-md")
        }
    }), jsGrid.setDefaults("number", {
        _createTextBox: function() {
            return $("<input>").attr("type", "number").attr("class", "form-control input-md")
        }
    }), jsGrid.setDefaults("textarea", {
        _createTextBox: function() {
            return $("<input>").attr("type", "textarea").attr("class", "form-control")
        }
    }), jsGrid.setDefaults("control", {
        _createGridButton: function(cls, tooltip, clickHandler) {
            var grid = this._grid;
            return $("<button>").addClass(this.buttonClass).addClass(cls).attr({
                type: "button",
                title: tooltip
            }).on("click", function(e) {
                clickHandler(grid, e)
            })
        }
    }), jsGrid.setDefaults("select", {
        _createSelect: function() {
            var $result = $("<select>").attr("class", "form-control input-md"),
                valueField = this.valueField,
                textField = this.textField,
                selectedIndex = this.selectedIndex;
            return $.each(this.items, function(index, item) {
                var value = valueField ? item[valueField] : index,
                    text = textField ? item[textField] : item,
                    $option = $("<option>").attr("value", value).text(text).appendTo($result);
                $option.prop("selected", selectedIndex === index)
            }), $result
        }
    });
	$(function() {
		pageLength = 10;
		function onPageSelect(newPageLength) {
            pageLength = newPageLength;
            $("#jsGrid-Users").jsGrid("changePageSize", pageLength);
        }
        $("#pageLength").on("change", function() {
            onPageSelect(this.value);
		});
	    $("#jsGrid-Users").jsGrid({
	        height: "auto",
	        width: "100%",
	 		loadIndication: true,
		    loadIndicationDelay: 50000,
		    loadMessage: "Please, wait...",
		    noDataContent: "Loading... or Not found",
		    loadShading: true,
	        filtering: false,
	        editing: true,
	        sorting: true,
	        paging: true,
	        autoload: true,
	        selecting: true,
	 		confirmDeleting: false,
	        pageSize: pageLength,
	        changePageSize: function (pageSize) {
                var $this = this;
                let totalUsers = $this.data.length;
                let totalPages = Math.ceil(totalUsers / pageSize);
                if($this.pageIndex > totalPages){
                    $("#jsGrid-Users").jsGrid("openPage", 1);
                }
                $this.pageSize = pageLength;
                $this.refresh();
            },
	        pageButtonCount: 5,
        	pagerFormat: "&nbsp;&nbsp; {first} {prev} {pages} {next} {last} &nbsp;&nbsp;",
	        controller: {
	            loadData: function() {
	                let d = $.Deferred();
	                $.ajax({
	                    url: "api/v2/users?includeGroups",
	                    dataType: "json"
	                }).done(function(response) {
	                	let groupObj = response.response.data.groups;
	                	$("#jsGrid-Users").jsGrid("fieldOption", "group_id", "items", groupObj);
	                    d.resolve(response.response.data.users);
	                });
	                return d.promise();
	            }
	        },
	 
	        fields: [
	        	{ name: "image", title: "Avatar", type: "text", width: 45, css: "text-center hidden-xs", filtering: false, sorting:false, validate: "required",
	                itemTemplate: function(value) {
	                    return \'<img alt="user-img" class="img-circle" src="\'+value+\'" width="45">\'; }
	            },
	            { name: "username", type: "text", title: "Username", validate: "required", width: 150},
	            { name: "email", type: "text", title: "Email", validate: "required", width: 200},
	            { name: "register_date", type: "text", title: "Date Registered",editing: false, css: "hidden-xs",
	            	itemTemplate: function(value) {
	                    return moment(value).format(\'ll\') + \' \' + moment(value).format(\'LT\') },
	            },
	            { name: "group_id", type: "select", title: "Group", validate: "required",
	            	items: [],
				    valueField: "group_id",
				    textField: "group"
	            },
	            { name: "locked", title: "Locked", type: "select", width: 45, validate: "required",
	            	itemTemplate: function(value) {
	                    return (value == 0 || value == null || value == "" || value == " ") ? "No" : "Yes"; },
	                items: [
	                	{ Name: "No", Id: 0 },
         				{ Name: "Yes", Id: 1 },
	                ],
				    valueField: "Id",
    				textField: "Name"
    				
	            },
	            { name: "password", type: "text", title: "Password", css: "text-center", filtering: false, sorting:false,
	                itemTemplate: function(value) {
	                    return "<i class=\"mdi mdi-account-key\"></i>"; },
	                
	            	editTemplate: function(item, value) {
	            	var $result = jsGrid.fields.text.prototype.editTemplate.apply(this, arguments);
	            	$result.attr("placeholder", "Enter new password");
	            	this.editControl[0].value = "";
	                return $result; },
	            },
	            { type: "control", modeSwitchButton: false, editButton: false, title: "Action",
		             headerTemplate: function() {
	                    return "Action";
	                }
	             }
	        ],
		    onItemDeleting: function(args) {
		        if(args.item.protected) {
		            args.cancel = true;
		        }
		        args.cancel = true;
		        let id = args.item.id;
		        swal({
			        title: window.lang.translate("Delete ")+args.item.username+"?",
			        icon: "warning",
			        buttons: {
			            cancel: window.lang.translate("No"),
			            confirm: window.lang.translate("Yes"),
			        },
			        dangerMode: true,
			        className: "bg-org",
			        confirmButtonColor: "#DD6B55"
			    }).then(function(willDelete) {
			        if (willDelete) {
				        organizrAPI2("DELETE","api/v2/users/" + id, null,true).success(function(data) {
					        $("#jsGrid-Users").jsGrid("render");
				        	message("User Deleted","",activeInfo.settings.notifications.position,"#FFF","success","5000");
				        }).fail(function(xhr) {
					        message("User Deleted Error", xhr.responseJSON.response.message, activeInfo.settings.notifications.position, "#FFF", "error", "10000");
					        console.error("Organizr Function: API Connection Failed");
				        });
					}
				});
		    },
		    onItemUpdating: function(args) {
		        if(typeof args.item.id == "undefined"){
		        	args.cancel = true;
		            alert("Could not get ID");
		        }
		        let diff = objDiff(args.previousItem,args.item);
		        if(typeof diff.password !== "undefined"){
		            if(diff.password === ""){
		                delete diff["password"];
		            }
		        }
		        let id = args.item.id;
		        organizrAPI2("PUT","api/v2/users/" + id, diff,true).success(function(data) {
					try {
						let response = data.response;
						$("#jsGrid-Users").jsGrid("render");
						message("User Updated",response.message,activeInfo.settings.notifications.position,"#FFF","success","5000");
					}catch(e) {
						console.log(e + " error: " + data);
						orgErrorAlert("<h4>" + e + "</h4>" + formatDebug(data));
						return false;
					}
				
				}).fail(function(xhr) {
					message("User Error", xhr.responseJSON.response.message, activeInfo.settings.notifications.position,"#FFF","error","10000");
					console.error("Organizr Function: API Connection Failed");
				});
		    },
		    
		    onRefreshed: function(){
		    
				$(".jsgrid-pager").addClass( "pull-right" );
				$(".jsgrid-pager").find(".jsgrid-pager-page a").addClass( "btn btn-info" );
				$(".jsgrid-pager").find(".jsgrid-pager-nav-button a").addClass( "btn btn-info" );
				$(".jsgrid-pager").find(".jsgrid-pager-current-page").addClass( "btn btn-primary m-r-5" );
				let nav = $(".jsgrid-pager").find(".jsgrid-pager-nav-button");
				$.each(nav, function(i,v) {
					if(v.innerText === "..."){
						$(this).addClass("hidden");
					}
				})
			}
	    });
	    
	});
</script>
<div class="panel bg-org panel-info">
    <div class="panel-heading">
        <span lang="en">MANAGE USERS</span>
        <button type="button" class="btn btn-info btn-circle pull-right popup-with-form" href="#new-user-form" data-effect="mfp-3d-unfold"><i class="fa fa-plus"></i> </button>
        <div id="pageDiv" class="hidden-xs">
			<div class="item-pager-panel pull-right m-r-10">
			        <select id="pageLength" class="form-control">
			            <option>5</option>
			            <option selected="">10</option>
			            <option>15</option>
			            <option>30</option>
			            <option>60</option>
			            <option>180</option>
			        </select>
			</div>
		</div>
    </div>
    <div id="jsGrid-Users" class=""></div>
	<div class="clearfix"></div>
    <div class="table-responsive hidden">
        <table class="table table-hover manage-u-table">
            <thead>
                <tr>
                    <th width="70" class="text-center">#</th>
                    <th lang="en">NAME & EMAIL</th>
                    <th lang="en">ADDED</th>
                    <th lang="en">GROUP</th>
                    <th lang="en">EDIT</th>
                    <th lang="en">EMAIL</th>
                    <th lang="en">DELETE</th>
                </tr>
            </thead>
            <tbody id="manageUserTable"></tbody>
        </table>
    </div>
</div>
<form id="new-user-form" class="mfp-hide white-popup-block mfp-with-anim">
    <h1 lang="en">Add New User</h1>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="new-user-form-inputUsername" lang="en">Username</label>
            <input type="text" class="form-control" id="new-user-form-inputUsername" name="username" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="new-user-form-inputEmail" lang="en">Email</label>
            <input type="email" class="form-control" id="new-user-form-inputEmail" name="email"  required="">
        </div>
        <div class="form-group">
            <label class="control-label" for="new-user-form-inputPassword" lang="en">Password</label>
            <input type="password" class="form-control" id="new-user-form-inputPassword" name="password"  required="">
        </div>
    </fieldset>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none addNewUser" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Add User</span></button>
    <div class="clearfix"></div>
</form>
<form id="edit-user-form" class="mfp-hide white-popup-block mfp-with-anim">
    <input type="hidden" name="id" value="">
    <h1 lang="en">Edit User</h1>
    <fieldset style="border:0;">
        <div class="form-group">
            <label class="control-label" for="edit-user-form-inputUsername" lang="en">Username</label>
            <input type="text" class="form-control" id="edit-user-form-inputUsername" name="username" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="edit-user-form-inputEmail" lang="en">Email</label>
            <input type="text" class="form-control" id="edit-user-form-inputEmail" name="email" required="" autofocus>
        </div>
        <div class="form-group">
            <label class="control-label" for="edit-user-form-inputPassword" lang="en">Password</label>
            <input type="password" class="form-control" id="edit-user-form-inputPassword" name="password"  required="">
        </div>
        <div class="form-group">
            <label class="control-label" for="edit-user-form-inputPassword2" lang="en">Password Again</label>
            <input type="password" class="form-control" id="edit-user-form-inputPassword2" name="password2"  required="">
        </div>
    </fieldset>
    <button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none editUserAdmin" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Edit User</span></button>
    <div class="clearfix"></div>
</form>
';
}
