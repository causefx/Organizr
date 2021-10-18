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
	$logsDropdown = $Organizr->buildLogDropdown();
	$filterDropdown = $Organizr->buildFilterDropdown();
	return '
	<div class="btn-group m-b-20 pull-left">' . $logsDropdown . '</div>
	<button class="btn btn-danger waves-effect waves-light pull-right purgeLog" type="button" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Purge Log"><i class="fa fa-trash"></i></span></button>
	<button onclick="organizrLogTable.clear().draw().ajax.reload(null, false)" class="btn btn-info waves-effect waves-light pull-right reloadLog m-r-5" type="button" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Reload Log"><i class="fa fa-refresh"></i></span></button>
	<button onclick="toggleKillOrganizrLiveUpdate(' . $Organizr->config['logLiveUpdateRefresh'] . ');" class="btn btn-primary waves-effect waves-light pull-right organizr-log-live-update m-r-5" type="button" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Live Update"><i class="fa fa-clock-o"></i></span></button>
	' . $filterDropdown . '
	<div class="clearfix"></div>
	<div class="white-box bg-org logTable orgLogDiv">
		<h3 class="box-title m-b-0" lang="en">Organizr Logs</h3>
		<div class="table-responsive">
			<table id="organizrLogTable" class="table table-striped compact nowrap">
				<thead>
					<tr>
						<th lang="en">Date</th>
						<th lang="en">Severity</th>
						<th lang="en">Function</th>
						<th lang="en">Message</th>
						<th lang="en">IP Address</th>
						<th lang="en">User</th>
						<th></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
	<!-- /.container-fluid -->
	<script>
	clearTimeout(timeouts[\'organizr-log\']);
	$.fn.dataTable.ext.errMode = "none";
	var organizrLogTable = $("#organizrLogTable")
	.on("error.dt", function(e, settings, techNote, message) {
		console.log("An error has been reported by DataTables: ", message);
		organizrLogTable.draw();
	})
	.DataTable({
		"ajax": {
			"url": "api/v2/log/0?filter=NONE&pageSize=1000&offset=0",
			"dataSrc": function(json) {
				return json.response.data.results;
			}
		},
		"deferRender": true,
		"pageLength": ' . (int)$Organizr->config['logPageSize'] . ',
		"columns": [{
			data: "datetime",
			render: function(data, type, row) {
				if (type === "display" || type === "filter") {
					var m = moment.tz(data + "Z", activeInfo.timezone);
					return moment(m).format("LLL");
				}
				return data;
			}
		}, {
			data: "log_level",
			render: function(data, type, row) {
				if (type === "display" || type === "filter") {
					return logIcon(data);
				}
				return logIcon(data);
			}
		}, {
			data: "channel"
		}, {
			data: "message"
		}, {
			data: "remote_ip_address",
			"width": "5%",
			render: function(data, type, row) {
				return ipInfoSpan(data);
			}
		}, {
			"data": "username"
		}, {
			data: "context",
			render: function(data, type, row) {
				return logContext(row);
			},
			orderable: false
		}, ],
		"order": [
			[0, "desc"]
		],
	})
	</script>
	';
}