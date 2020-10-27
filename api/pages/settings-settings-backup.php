<?php
$GLOBALS['organizrPages'][] = 'settings_settings_backup';
function get_page_settings_settings_backup($Organizr)
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
		getOrganizrBackups();
    </script>
 
    <div class="white-box bg-org">
		<div class="col-md-3 col-sm-4 col-xs-6 pull-right">
			<button onclick="createOrganizrBackup()" class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right" type="button"><span class="btn-label"><i class="fa ti-export"></i></span><span lang="en">Create Backup</span></button>
		</div>
		<h3 class="box-title" lang="en">Backup Organizr</h3>
		<div class="row sales-report">
			<div class="col-md-6 col-sm-6 col-xs-6">
				<h2 id="backup-total-files"><i class="fa fa-spin fa-spinner"></i></h2>
				<p lang="en">Files</p>
			</div>
			<div class="col-md-6 col-sm-6 col-xs-6 ">
				<h1 class="text-right text-info m-t-20" id="backup-total-size"><i class="fa fa-spin fa-spinner"></i></h1>
			</div>
		</div>
		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>#</th>
						<th lang="en">Name</th>
						<th lang="en">Version</th>
						<th lang="en">Size</th>
						<th lang="en">Date</th>
						<th lang="en">Action</th>
					</tr>
				</thead>
				<tbody id="backup-file-list">
					<tr>
						<td class="text-center" colspan="6"><i class="fa fa-spin fa-spinner"></i></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
    <!-- /.container-fluid -->
    ';
}
