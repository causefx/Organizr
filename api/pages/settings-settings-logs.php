<?php
$GLOBALS['organizrPages'][] = 'settings_settings_logs';
function get_page_settings_settings_logs($Organizr)
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
    $(document).on("click", ".swapLog", function(e) {
    	switch ($(this).attr(\'data-name\')){
    	case \'loginLog\':
    		loginLogTable.ajax.reload(null, false);
    	break;
    	case \'orgLog\':
    		organizrLogTable.ajax.reload(null, false);
    	break;
    	default:
    		//nada
    		//loginLogTable
    	}
        var log = $(this).attr(\'data-name\')+\'Div\';
        $(\'.logTable\').addClass(\'hidden\');
        $(\'.\'+log).addClass(\'show\').removeClass(\'hidden\');
    	$(\'.swapLog\').removeClass(\'active\');
    	$(this).addClass(\'active\');
    });
    </script>
    <div class="btn-group m-b-20 pull-left">
        <button type="button" class="btn btn-default btn-outline waves-effect bg-org swapLog active" data-name="loginLog" data-path="' . $Organizr->organizrLoginLog . '" lang="en">Login Log</button>
        <button type="button" class="btn btn-default btn-outline waves-effect bg-org swapLog" data-name="orgLog" data-path="' . $Organizr->organizrLog . '" lang="en">Organizr Log</button>
    </div>
    <button class="btn btn-danger btn-sm waves-effect waves-light pull-right purgeLog" type="button"><span class="btn-label"><i class="fa fa-trash"></i></span>Purge Log</button>
    <div class="clearfix"></div>
    <div class="white-box bg-org logTable loginLogDiv">
        <h3 class="box-title m-b-0" lang="en">Login Logs</h3>
        <div class="table-responsive">
            <table id="loginLogTable" class="table table-striped">
                <thead>
                    <tr>
                        <th lang="en">Date</th>
                        <th lang="en">Username</th>
                        <th lang="en">IP Address</th>
                        <th lang="en">Type</th>
                    </tr>
                </thead>
    			<tfoot>
                    <tr>
                        <th lang="en">Date</th>
                        <th lang="en">Username</th>
                        <th lang="en">IP Address</th>
                        <th lang="en">Type</th>
                    </tr>
                </tfoot>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="white-box bg-org logTable orgLogDiv hidden">
        <h3 class="box-title m-b-0" lang="en">Organizr Logs</h3>
        <div class="table-responsive">
            <table id="organizrLogTable" class="table table-striped">
                <thead>
                    <tr>
                        <th lang="en">Date</th>
                        <th lang="en">Username</th>
                        <th lang="en">IP Address</th>
                        <th lang="en">Message</th>
                        <th lang="en">Type</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th lang="en">Date</th>
                        <th lang="en">Username</th>
                        <th lang="en">IP Address</th>
                        <th lang="en">Message</th>
                        <th lang="en">Type</th>
                    </tr>
                </tfoot>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <!-- /.container-fluid -->
    <script>
    //$.fn.dataTable.moment(\'DD-MMM-Y HH:mm:ss\');
    $.fn.dataTable.ext.errMode = \'none\';
    var loginLogTable = $("#loginLogTable")
    .on( \'error.dt\', function ( e, settings, techNote, message ) {
        console.log( \'An error has been reported by DataTables: \', message );
        loginLogTable.draw();
    } )
    .DataTable( {
    		"ajax": {
				"url": "api/v2/log/login",
				"dataSrc": function ( json ) {
					return json.response.data;
				}
			},
            "columns": [
                { data: \'utc_date\',
                    render: function ( data, type, row ) {
                        if ( type === \'display\' || type === \'filter\' ) {
                            var m = moment.tz(data, activeInfo.timezone);
                            return moment(m).format(\'LLL\');
                        }
                        return data;
                    }
                },
                { "data": "username" },
                { data: \'ip\',
                    render: function ( data, type, row ) {
                        return ipInfoSpan(data);
                    }
                },
                { data: \'auth_type\',
                    render: function ( data, type, row ) {
                        if ( type === \'display\' || type === \'filter\' ) {
                            return logIcon(data);
                        }
                        return logIcon(data);
                    }
                }
            ],
            "order": [[ 0, \'desc\' ]],
    } );
    var organizrLogTable = $("#organizrLogTable")
    .on( \'error.dt\', function ( e, settings, techNote, message ) {
        console.log( \'An error has been reported by DataTables: \', message );
        organizrLogTable.draw();
    } )
    .DataTable( {
            "ajax": {
				"url": "api/v2/log/organizr",
				"dataSrc": function ( json ) {
					return json.response.data;
				}
			},
                "columns": [
                { data: \'utc_date\',
                    render: function ( data, type, row ) {
                        if ( type === \'display\' || type === \'filter\' ) {
                            var m = moment.tz(data, activeInfo.timezone);
                            return moment(m).format(\'LLL\');
                        }
                    return data;}
                    },
                { "data": "username" },
                { data: \'ip\',
                    render: function ( data, type, row ) {
                        return ipInfoSpan(data);
                    }
                },
                { "data": "message" },
                { data: \'type\',
                    render: function ( data, type, row ) {
                        if ( type === \'display\' || type === \'filter\' ) {
                            return logIcon(data);
                        }
                        return logIcon(data);
                    }
                }
            ],
            "order": [[ 0, \'desc\' ]],
    } );
    </script>
    ';
}
