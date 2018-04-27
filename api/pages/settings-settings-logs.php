<?php
if(file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
    $pageSettingsSettingsLogs = '
    <script>
    $(document).on("click", ".swapLog", function(e) {
        var log = $(this).attr(\'data-name\')+\'Div\';
        $(\'.logTable\').addClass(\'hidden\');
        $(\'.\'+log).addClass(\'show\').removeClass(\'hidden\');
    	$(\'.swapLog\').removeClass(\'active\');
    	$(this).addClass(\'active\');
    });
    </script>
    <div class="btn-group m-b-20 pull-left">
        <button type="button" class="btn btn-default btn-outline waves-effect bg-org swapLog active" data-name="loginLog" data-path="'.$GLOBALS['organizrLoginLog'].'" lang="en">Login Log</button>
        <button type="button" class="btn btn-default btn-outline waves-effect bg-org swapLog" data-name="orgLog" data-path="'.$GLOBALS['organizrLog'].'" lang="en">Organizr Log</button>
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
    $("#loginLogTable").DataTable( {
            "ajax": "api/?v1/login_log",
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
                { "data": "ip" },
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
    $("#organizrLogTable").DataTable( {
            "ajax": "api/?v1/organizr_log",
                "columns": [
                { data: \'utc_date\',
                    render: function ( data, type, row ) {
                        // If display or filter data is requested, format the date
                        if ( type === \'display\' || type === \'filter\' ) {
                            var m = moment.tz(data, activeInfo.timezone);
                            return moment(m).format(\'LLL\');
                        }

                    // Otherwise the data type requested (`type`) is type detection or
                    // sorting data, for which we want to use the integer, so just return
                    // that, unaltered
                    return data;}
                    },
                { "data": "username" },
                { "data": "ip" },
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
