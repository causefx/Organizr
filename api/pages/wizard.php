<?php
$pageWizard = '
<script>
    (function() {
        $(\'#adminValidator\').wizard({
            onInit: function() {
                $(\'#validation\').formValidation({
                    framework: \'bootstrap\',
                    fields: {
                        username: {
                            validators: {
                                notEmpty: {
                                    message: \'The username is required\'
                                },
                                stringLength: {
                                    min: 3,
                                    max: 30,
                                    message: \'The username must be more than 2 and less than 30 characters long\'
                                },
                                regexp: {
                                    regexp: /^[a-zA-Z0-9_\.]+$/,
                                    message: \'The username can only consist of alphabetical, number, dot and underscore\'
                                }
                            }
                        },
                        license: {
                            validators: {
                                regexp: {
                                    regexp: /^[a-zA-Z0-9_\.]+$/,
                                    message: \'Please choose a license\'
                                }
                            }
                        },
                        email: {
                            validators: {
                                notEmpty: {
                                    message: \'The email address is required\'
                                },
                                emailAddress: {
                                    message: \'The input is not a valid email address\'
                                }
                            }
                        },
                        hashKey: {
                            validators: {
                                notEmpty: {
                                    message: \'The hash key is required\'
                                },
                                stringLength: {
                                    min: 3,
                                    max: 30,
                                    message: \'The hash key must be more than 2 and less than 30 characters long\'
                                }
                            }
                        },
                        location: {
                            validators: {
                                notEmpty: {
                                    message: \'The database location is required\'
                                }
                            }
                        },
                        dbName: {
                            validators: {
                                notEmpty: {
                                    message: \'The Database Name is required\'
                                },
                                stringLength: {
                                    min: 2,
                                    max: 30,
                                    message: \'The Database Name must be more than 1 and less than 30 characters long\'
                                },
                                regexp: {
                                    regexp: /^[a-zA-Z0-9_\.]+$/,
                                    message: \'The Database Name can only consist of alphabetical, number, dot and underscore\'
                                }
                            }
                        },
                        api: {
                            validators: {
                                notEmpty: {
                                    message: \'The API Key is required\'
                                },
                                stringLength: {
                                    min: 20,
                                    max: 20,
                                    message: \'The API Key must be 20 characters long\'
                                }
                            }
                        },
                        registrationPassword: {
                            validators: {
                                notEmpty: {
                                    message: \'The registration password is required\'
                                }
                            }
                        },
                        password: {
                            validators: {
                                notEmpty: {
                                    message: \'The password is required\'
                                },
                                different: {
                                    field: \'username\',
                                    message: \'The password cannot be the same as username\'
                                }
                            }
                        }
                    }
                });
            },
            validator: function() {
                var fv = $(\'#validation\').data(\'formValidation\');
                var $this = $(this);
                // Validate the container
                fv.validateContainer($this);
                var isValidStep = fv.isValidContainer($this);
                if (isValidStep === false || isValidStep === null) {
                    return false;
                }
                return true;
            },
            onFinish: function() {
                //$(\'#validation\').submit();
                var post = $( \'#validation\' ).serializeArray();
                console.log( post );
                organizrAPI(\'POST\',\'api/?v1/wizard_config\',post).success(function(data) {
            		var html = JSON.parse(data);
                    if(html.data == true){
                        location.reload();
                    }else if(html.data == \'token\'){
                        message("",window.lang.translate(\'Could not create Token\'),activeInfo.settings.notifications.position,"#FFF","error","3500");
						console.error(\'Organizr Function: Could not create Token\');
					}else if(html.data == \'db\'){
						message("",window.lang.translate(\'Could not create DB - check permissions\'),activeInfo.settings.notifications.position,"#FFF","error","3500");
						console.error(\'Organizr Function: Could not create DB - check permissions\');
					}else if(html.data == \'admin\'){
						message("",window.lang.translate(\'Could not create admin acct\'),activeInfo.settings.notifications.position,"#FFF","error","3500");
						console.error(\'Organizr Function: Could not create admin acct\');
					}else if(html.data == \'config\'){
						message("",window.lang.translate(\'Could not create config files - check permissions\'),activeInfo.settings.notifications.position,"#FFF","error","3500");
						console.error(\'Organizr Function: Could not create config files - check permissions\');
					}else{
						message("",window.lang.translate(\'Sign-up Error Occurred\'),activeInfo.settings.notifications.position,"#FFF","error","3500");
                        console.error(\'Organizr Function: Sign-up Error Occurred\');
                    }
            	}).fail(function(xhr) {
            		console.error("Organizr Function: Connection Failed");
            	});
            }
        });
        generateAPI();
        $( ".wizardInput" ).focusout(function() {
            var value = $(this).val();
            var name = $(this).attr(\'name\');
            if (typeof value !== \'undefined\' && typeof name !== \'undefined\') {
                $(\'#verify-\'+name).text(value);
            }
        });
    })();
</script>
<div class="container-fluid">
    <div class="row bg-title">
        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
            <h4 class="page-title">Organizr Setup Wizard</h4>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!--.row-->
    <div class="row">
        <div class="col-sm-12">
            <div class="white-box">
                <h3 class="box-title m-b-0" lang="en">Admin Creation</h3>
                <div class="wizard" id="adminValidator">
                    <ul class="wizard-steps" role="tablist">
                        <li class="active" role="tab">
                            <h4><span><i class="ti-direction"></i></span><item lang="en">Install Type</item></h4>
                        </li>
                        <li role="tab">
                            <h4><span><i class="ti-user"></i></span><item lang="en">Admin Info</item></h4>
                        </li>
                        <li role="tab">
                            <h4><span><i class="ti-key"></i></span><item lang="en">Security</item></h4>
                        </li>
                        <li role="tab">
                            <h4><span><i class="ti-server"></i></span><item lang="en">Database</item></h4>
                        </li>
                        <li role="tab">
                            <h4><span><i class="ti-check"></i></span><item lang="en">Verify</item></h4>
                        </li>
                    </ul>
                    <form class="form-horizontal" id="validation" name="validation" onsubmit="return false;">
                        <div class="wizard-content">
                            <div class="wizard-pane active" role="tabpanel">
	                            <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <i class="ti-alert fa-fw"></i> <span lang="en">Notice</span>
                                        <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="panel-dismiss"><i class="ti-close"></i></a> </div>
                                    </div>
                                    <div class="panel-wrapper collapse in" aria-expanded="true">
                                        <div class="panel-body">
                                            <p lang="en">Personal has everything unlocked - no restrictions</p>
                                            <p lang="en">Business has Media items hidden [Plex, Emby etc...]</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="license" lang="en">Install Type</label>
                                    <div class="input-group">
                                        <div class="input-group-addon"><i class="ti-direction"></i></div>
                                        <select name="license" class="form-control wizardInput" id="form-license">
                                            <option lang="en">Choose License</option>
                                            <option lang="en" value="personal">Personal</option>
                                            <option lang="en" value="business">Business</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="wizard-pane" role="tabpanel">
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <i class="ti-alert fa-fw"></i> <span lang="en">Notice</span>
                                        <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="panel-dismiss"><i class="ti-close"></i></a> </div>
                                    </div>
                                    <div class="panel-wrapper collapse in" aria-expanded="true">
                                        <div class="panel-body">
                                            <p lang="en">If using Plex or Emby - It is suggested that you use the username and email of the Admin account.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="username" lang="en">Username</label>
                                    <div class="input-group">
                                        <div class="input-group-addon"><i class="ti-user"></i></div>
                                        <input type="text" class="form-control wizardInput" name="username" id="form-username">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="email" lang="en">Email</label>
                                    <div class="input-group">
                                        <div class="input-group-addon"><i class="ti-email"></i></div>
                                        <input type="text" class="form-control wizardInput" name="email" id="form-email">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="passwrod" lang="en">Password</label>
                                    <div class="input-group">
                                        <div class="input-group-addon"><i class="ti-lock"></i></div>
                                        <input type="password" class="form-control wizardInput" name="password" id="form-password">
                                    </div>
                                </div>
                            </div>
                            <div class="wizard-pane" role="tabpanel">
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <i class="ti-alert fa-fw"></i> <span lang="en">Notice</span>
                                        <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="panel-dismiss"><i class="ti-close"></i></a> </div>
                                    </div>
                                    <div class="panel-wrapper collapse in" aria-expanded="true">
                                        <div class="panel-body">
                                            <p lang="en">The Hash Key will be used to decrypt all passwords etc... on the server. {User-Generated]</p>
                                            <p lang="en">The Registration Password will lockout the registration field with this password. {User-Generated]</p>
                                            <p lang="en">The API Key will be used for all calls to organizr for the UI. [Auto-Generated]</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="key" lang="en">Hash Key</label>
                                    <div class="input-group">
                                        <div class="input-group-addon"><i class="ti-key"></i></div>
                                        <input type="password" class="form-control wizardInput" name="hashKey" id="form-hashKey">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="key" lang="en">Registration Password</label>
                                    <div class="input-group">
                                        <div class="input-group-addon"><i class="ti-key"></i></div>
                                        <input type="password" class="form-control wizardInput" name="registrationPassword" id="form-registrationPassword">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="key" lang="en">API Key</label>
                                    <div class="input-group">
                                        <div class="input-group-addon"><i class="ti-key"></i></div>
                                        <input type="password" class="form-control wizardInput disabled" name="api" id="form-api">
                                    </div>
                                </div>
                            </div>
                            <div class="wizard-pane" role="tabpanel">
                                <div class="panel panel-danger">
                                    <div class="panel-heading">
                                        <i class="ti-alert fa-fw"></i> <span lang="en">Attention</span>
                                        <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="panel-dismiss"><i class="ti-close"></i></a> </div>
                                    </div>
                                    <div class="panel-wrapper collapse in" aria-expanded="true">
                                        <div class="panel-body">
                                            <p lang="en">The Database will contain sensitive information.  Please place in directory outside of root Web Directory.</p>
                                            <p lang="en">Suggested Directory: <code>' . dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'db</code> <a class="btn default btn-outline clipboard p-a-5" data-clipboard-text="' . dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'db" href="javascript:void(0);"><i class="ti-clipboard"></i></a></p>
                                            <p lang="en">Current Directory: <code>' . dirname(__DIR__, 2) . '</code> <a class="btn default btn-outline clipboard p-a-5" data-clipboard-text="' . dirname(__DIR__, 2) . '" href="javascript:void(0);"><i class="ti-clipboard"></i></a></p>
                                            <p lang="en">Parent Directory: <code>' . dirname(__DIR__, 3) . '</code> <a class="btn default btn-outline clipboard p-a-5" data-clipboard-text="' . dirname(__DIR__, 3) . '" href="javascript:void(0);"><i class="ti-clipboard"></i></a></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="dbName" lang="en">Database Name</label>
                                    <div class="input-group">
                                        <div class="input-group-addon"><i class="ti-server"></i></div>
                                        <input type="text" class="form-control wizardInput" name="dbName" id="form-dbName" placeholder="orgDBname">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="location" lang="en">Database Location</label>
                                    <div class="input-group">
                                        <div class="input-group-addon"><i class="ti-server"></i></div>
                                        <input type="text" class="form-control wizardInput" name="location" id="form-location" placeholder="Enter path or copy from above">
                                        <span class="input-group-btn"><button class="btn btn-info testPath" lang="en" type="button">Test / Create Path</button></span>
                                    </div>
                                </div>
                            </div>
                            <div class="wizard-pane" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label col-md-3" lang="en">License:</label>
                                            <div class="col-md-9">
                                                <p class="form-control-static" id="verify-license"></p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3" lang="en">Username:</label>
                                            <div class="col-md-9">
                                                <p class="form-control-static" id="verify-username"></p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3" lang="en">Email:</label>
                                            <div class="col-md-9">
                                                <p class="form-control-static" id="verify-email"></p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3" lang="en">Password:</label>
                                            <div class="col-md-9">
                                                <p class="form-control-static">
                                                    <a class="mytooltip" href="javascript:void(0)"> <span lang="en">Hover to show </span><span class="tooltip-content5"><span class="tooltip-text3"><span class="tooltip-inner2" id="verify-password"></span></span></span></a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label col-md-3" lang="en">Hash Key:</label>
                                            <div class="col-md-9">
                                                <p class="form-control-static">
                                                    <a class="mytooltip" href="javascript:void(0)"> <span lang="en">Hover to show </span><span class="tooltip-content5"><span class="tooltip-text3"><span class="tooltip-inner2" id="verify-hashKey">pass</span></span></span></a>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3" lang="en">Registration Password:</label>
                                            <div class="col-md-9">
                                                <p class="form-control-static">
                                                    <a class="mytooltip" href="javascript:void(0)"> <span lang="en">Hover to show </span><span class="tooltip-content5"><span class="tooltip-text3"><span class="tooltip-inner2" id="verify-registrationPassword">pass</span></span></span></a>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3" lang="en">API Key:</label>
                                            <div class="col-md-9">
                                                <p class="form-control-static">
                                                    <a class="mytooltip" href="javascript:void(0)"> <span lang="en">Hover to show </span><span class="tooltip-content5"><span class="tooltip-text3"><span class="tooltip-inner2" id="verify-api">pass</span></span></span></a>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3" lang="en">Database Location:</label>
                                            <div class="col-md-9">
                                                <p class="form-control-static" id="verify-location">  </p>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3" lang="en">Database Name:</label>
                                            <div class="col-md-9">
                                                <p class="form-control-static" id="verify-dbName">  </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--/row-->
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
    <!--./row-->
</div>
<!-- /.container-fluid -->
';
