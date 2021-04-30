<?php
$GLOBALS['organizrPages'][] = 'dependencies';
function get_page_dependencies($Organizr)
{
	if (!$Organizr) {
		$Organizr = new Organizr();
	}
	return '
<script>
</script>
<div class="container-fluid">
	<div class="row bg-title">
		<div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
			<h4 class="page-title" lang="en">Organizr Dependency Check</h4>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<!--.row-->
	<div class="row">
		<div class="col-sm-12">
			<div class="white-box">
				<div class="row row-in">
					<div class="col-lg-4 col-sm-6 row-in-br">
						<ul class="col-in">
							<li>
								<span class="circle circle-md bg-warning dependency-dependencies-check"><i class="fa fa-spin fa-spinner"></i></span>
							</li>
							<li class="col-last">
								<h3 class="counter text-right m-t-15" lang="en">Dependencies</h3>
							</li>
							
						</ul>
					</div>
					<div class="col-lg-4 col-sm-6 row-in-br  b-r-none">
						<ul class="col-in">
							<li>
								<span class="circle circle-md bg-warning dependency-phpversion-check"><i class="fa fa-spin fa-spinner"></i></span>
							</li>
							<li class="col-last">
								<h3 class="counter text-right m-t-15" lang="en">PHP Version</h3>
							</li>
							
						</ul>
					</div>
					
					<div class="col-lg-4 col-sm-6  b-0">
						<ul class="col-in">
							<li>
								<span class="circle circle-md bg-warning dependency-permissions-check"><i class="fa fa-spin fa-spinner"></i></span>
							</li>
							<li class="col-last">
								<h3 class="counter text-right m-t-15" lang="en">Permissions</h3>
							</li>
							
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-4 col-sm-6">
			<div class="panel panel-danger dependency-dependencies-check-listing-header">
				<div class="panel-heading dependency-dependencies-check-listing"> <i class="ti-alert fa-fw"></i> <span lang="en">Dependencies Missing</span>
					<div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a></div>
				</div>
				<div class="panel-wrapper collapse in" aria-expanded="true">
					<div class="panel-body">
						<ul class="common-list" id="depenency-info"></ul>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-4 col-sm-6">
			<div class="panel panel-info">
				<div class="panel-heading"> <i class="ti-alert fa-fw"></i> <span lang="en">PHP Version Check</span>
					<div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a></div>
				</div>
				<div class="panel-wrapper collapse in" aria-expanded="true">
					<table class="table table-hover">
						<tbody>
							<tr>
								<td id="php-version-check" lang="en">Loading...</td>
							</tr>
							<tr>
								<td id="php-version-check-user" lang="en">Loading...</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-lg-4 col-sm-6">
			<div class="panel panel-info">
				<div class="panel-heading"> <i class="ti-alert fa-fw"></i> <span lang="en">Web Folder</span>
					<div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a></div>
				</div>
				<div class="panel-wrapper collapse in" aria-expanded="true">
					<table class="table table-hover">
						<tbody>
							<tr>
								<td>' . dirname(__DIR__, 2) . '</td>
							</tr>
							<tr>
								<td id="web-folder" lang="en">Loading...</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-lg-12">
			<div class="panel panel-info">
				<div class="panel-heading"> <i class="ti-alert fa-fw"></i> <span lang="en">Browser Information</span>
					<div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-plus"></i></a></div>
				</div>
				<div class="panel-wrapper collapse" id="browser-info" aria-expanded="false"></div>
			</div>
		</div>

	</div>
	<!--./row-->
</div>
<!-- /.container-fluid -->
';
}