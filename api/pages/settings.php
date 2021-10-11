<?php
$GLOBALS['organizrPages'][] = 'settings';
function get_page_settings($Organizr)
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
	$Organizr->setLoggerChannel('Organizr');
	$Organizr->logger->info('Accessed admin settings page');
	$systemMenus = $Organizr->systemMenuLists();
	return $Organizr->pluginFiles('js', true) . $Organizr->loadJavascriptFile('js/Sortable.min.js') . '
<script>
	(function() {
		updateCheck();
		authDebugCheck();
		sponsorLoad();
		newsLoad();
		checkCommitLoad();
		backersLoad();
		[].slice.call(document.querySelectorAll(\'.sttabs-main-settings-div\')).forEach(function(el) {
			new CBPFWTabs(el);
		});
	})();
</script>
<div class="container-fluid">
	<div class="row bg-title">
		<div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
			<h4 class="page-title" lang="en">Organizr Settings</h4>
		</div>
		<div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
			<ol id="settingsBreadcrumb" class="breadcrumb">
				<li lang="en">Settings</li>
				<li lang="en">Tab Editor</li>
			</ol>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<!--.row-->
	<div class="row">
		<!-- Tab style start -->
		<section class="">
			<div class="sttabs sttabs-main-settings-div tabs-style-flip">
				<nav>
					<ul>
						<li onclick="changeSettingsMenu(\'Settings::Tab Editor\')" id="settings-main-tab-editor-anchor"><a href="#settings-main-tab-editor" class="sticon ti-layout-tab-v"><span lang="en">Tab Editor</span></a></li>
						<li onclick="changeSettingsMenu(\'Settings::Customize\')" id="settings-main-customize-anchor"><a href="#settings-main-customize" class="sticon ti-paint-bucket"><span lang="en">Customize</span></a></li>
						<li onclick="changeSettingsMenu(\'Settings::User Management\')" id="settings-main-user-management-anchor"><a href="#settings-main-user-management" class="sticon ti-user"><span lang="en">User Management</span></a></li>
						<li onclick="changeSettingsMenu(\'Settings::Image Manager\');loadSettingsPage2(\'api/v2/page/settings_image_manager\',\'#settings-image-manager-view\',\'Image Viewer\');" id="settings-main-image-manager-anchor"><a href="#settings-main-image-manager" class="sticon ti-image"><span lang="en">Image Manager</span></a></li>
						<li onclick="changeSettingsMenu(\'Settings::Plugins\');loadSettingsPage2(\'api/v2/page/settings_plugins\',\'#settings-main-plugins\',\'Plugins\');" id="settings-main-plugins-anchor"><a href="#settings-main-plugins" class="sticon ti-plug"><span lang="en">Plugins</span></a></li>
						<li onclick="changeSettingsMenu(\'Settings::System Settings\');authDebugCheck();" id="settings-main-system-settings-anchor"><a href="#settings-main-system-settings" class="sticon ti-settings"><span lang="en">System Settings</span></a></li>
					</ul>
				</nav>
				<div class="content-wrap">
					<! -- TAB EDITOR -->
					<section id="settings-main-tab-editor">
						' . $systemMenus['tab_editor'] . '
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane fade" id="settings-tab-editor-tabs">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-tab-editor-categories">
								<h2 lang="en">Loading...</h2>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-tab-editor-homepage">
								<h2 lang="en">Loading...</h2>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-tab-editor-homepage-order">
								<h2 lang="en">Loading...</h2>
							</div>
						</div>
					</section>
					<! -- Customize -->
					<section id="settings-main-customize">
						' . $systemMenus['customize'] . '
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane fade" id="settings-customize-appearance">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-customize-marketplace">
								<div class="panel bg-org panel-info">
									<div class="panel-heading">
										<span lang="en">Theme Marketplace</span>
									</div>
									<div class="panel-wrapper collapse in" aria-expanded="true">
										<div class="table-responsive">
											<table class="table table-hover manage-u-table">
												<thead>
													<tr>
														<th width="70" class="text-center" lang="en">THEME</th>
														<th></th>
														<th lang="en">CATEGORY</th>
														<th lang="en">STATUS</th>
														<th lang="en" style="text-align:center">INFO</th>
														<th lang="en" style="text-align:center">INSTALL</th>
														<th lang="en" style="text-align:center">DELETE</th>
													</tr>
												</thead>
												<tbody id="manageThemeTable"></tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
						</div>
					</section>
					<! -- USER MANAGEMENT -->
					<section id="settings-main-user-management">
						' . $systemMenus['user_management'] . '
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane fade" id="settings-user-manage-users">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-user-manage-groups">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-user-import-users">
								' . $Organizr->importUserButtons() . '
								<div class="clearfix"></div>
							</div>
						</div>
					</section>
					<! -- IMAGE MANAGER -->
					<section id="settings-main-image-manager">
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane fade active in" id="settings-image-manager-view">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
						</div>
					</section>
					<! -- PLUGINS -->
					<section id="settings-main-plugins">
						<h2 lang="en">Plugins</h2>
					</section>
					<! -- SYSTEM SETTINGS -->
					<section id="settings-main-system-settings">
					' . $systemMenus['system_settings'] . '
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane fade" id="settings-settings-main">
								<h2 lang="en">Main Settings</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-settings-sso">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-settings-logs">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-settings-backup">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade active in" id="settings-settings-about">
								<div class="row">
									<div class="col-lg-12">
										<div class="panel panel-default">
											<div class="panel-heading bg-org p-t-10 p-b-10">
												<span class="pull-left m-t-5">
													<img class="lazyload loginTitle" data-src="plugins/images/organizr/logo-no-border.png"> &nbsp;
													<span class="text-uppercase fw300" lang="en">Organizr News</span>
												</span>
												<div class="clearfix"></div>
											</div>
											<div class="panel-wrapper p-b-0 collapse in bg-org">
												<div id="organizrNewsPanel"></div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-lg-6 col-sm-12 col-md-6">
										<div class="panel bg-org">
											<div class="p-30">
												<div class="row">
													<div class="col-xs-12"><img src="plugins/images/organizr/logo-wide.png" alt="organizr" class="img-responsive"></div>
												</div>
											</div>
											<hr class="m-t-10">
											<div class="p-20 text-center">
												<p lang="en">Below you will find all the links for everything that has to do with Organizr</p>
											</div>
											<hr>
											<ul class="dp-table profile-social-icons">
												<li><a href="https://organizr.app" target="_blank"><i class="mdi mdi-web mdi-24px"></i></a></li>
												<li><a href="https://reddit.com/r/organizr" target="_blank"><i class="mdi mdi-reddit mdi-24px"></i></a></li>
												<li><a href="https://organizr.app/discord" target="_blank"><i class="mdi mdi-discord mdi-24px"></i></a></li>
												<li><a href="https://github.com/causefx/organizr" target="_blank"><i class="mdi mdi-github-box mdi-24px"></i></a></li>
											</ul>
											<hr>
											<a href="https://poeditor.com/join/project/T6l68hksTE" target="_blank">
												<div class="white-box bg-org">
													<h4 lang="en">Want to help translate?</h4>
													<p lang="en">Head on over to POEditor and help us translate Organizr into your language</p>
													<p lang="en">I will try and import new strings every Friday</p>
												</div>
											</a>
											
										</div>
									</div>
									<div class="col-lg-6 col-sm-12 col-md-6">
										<div class="white-box bg-org">
											<h3 class="box-title" lang="en">Information</h3>
											<ul class="feeds">
												<li><div class="bg-info"><i class="mdi mdi-webpack mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Organizr Version</span> ' . $Organizr->version . '</li>
												<li><div class="bg-info"><i class="mdi mdi-github-box mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Organizr Branch</span><a href="https://github.com/causefx/Organizr/commits/' . $Organizr->config['branch'] . '" target="_blank"> ' . $Organizr->config['branch'] . '</a></li>
												<li><div class="bg-info"><i class="mdi mdi-database mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Database Location</span> ' . $Organizr->config['dbLocation'] . $Organizr->config['dbName'] . '</li>
												' . $Organizr->settingsDocker() . $Organizr->settingsPathChecks() . '
												<hr class="m-t-10">
												<li><div class="bg-info"><i class="mdi mdi-language-php mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">PHP Version</span> ' . phpversion() . '</li>
												<li><div class="bg-info"><i class="mdi mdi-package-variant-closed mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Webserver Version</span> ' . $_SERVER['SERVER_SOFTWARE'] . '</li>
												<hr class="m-t-10">
												<li><div class="bg-info"><i class="mdi mdi-account-card-details mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">License</span> ' . ucwords($Organizr->config['license']) . '</li>
											</ul>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-lg-12">
										<div class="panel panel-default">
											<div class="panel-heading bg-org p-t-10 p-b-10">
												<span class="pull-left m-t-5"><span lang="en">Sponsors</span></span>
												<div class="clearfix"></div>
											</div>
											<div class="panel-wrapper p-b-0 collapse in bg-org">
												<div id="sponsorList" class="owl-carousel owl-theme sponsor-items"></div>
												<div id="sponsorListModals"></div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-lg-12">
										<div class="panel panel-default">
											<div class="panel-heading bg-org p-t-10 p-b-10">
												<span class="pull-left m-t-5"><span lang="en">Backers</span></span>
												<div class="clearfix"></div>
											</div>
											<div class="panel-wrapper p-b-0 collapse in bg-org">
												<div id="backersList" class="owl-carousel owl-theme backers-items"></div>
											</div>
										</div>
									</div>
								</div>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-settings-donate">
								<div class="col-lg-12">
									<div class="white-box bg-org">
										<ul class="nav nav-tabs tabs customtab">
											<li class="tab active">
												<a href="#donate-github" data-toggle="tab" aria-expanded="true"> <span class=""><i class="fa fa-github text-warning"></i></span> <span class="hidden-xs" lang="en">Github Sponsor</span> </a>
											</li>
											<li class="tab">
												<a href="#donate-paypal" data-toggle="tab" aria-expanded="true"> <span class=""><i class="fa fa-paypal text-info"></i></span> <span class="hidden-xs" lang="en">PayPal</span> </a>
											</li>
											<li class="tab">
												<a href="#donate-square" data-toggle="tab" aria-expanded="false"> <span class=""><i class="fa mdi mdi-square-inc-cash mdi-18px text-success"></i></span> <span class="hidden-xs" lang="en">Square Cash</span> </a>
											</li>
											<li class="tab">
												<a href="#donate-crypto" data-toggle="tab" aria-expanded="false"> <span class=""><i class="fa mdi mdi-coin mdi-18px text-info"></i></span> <span class="hidden-xs" lang="en">Cryptos</span> </a>
											</li>
											<li class="tab">
												<a href="#donate-patreon" data-toggle="tab" aria-expanded="false"> <span class=""><i class="fa mdi mdi-account-multiple mdi-18px text-danger"></i></span> <span class="hidden-xs" lang="en">Patreon</span> </a>
											</li>
											<li class="tab">
												<a href="#donate-open-collective" data-toggle="tab" aria-expanded="false"> <span class=""><i class="fa fa-circle-o-notch text-primary"></i></span> <span class="hidden-xs" lang="en">Open Collective</span> </a>
											</li>
											<li class="tab">
												<a href="#donate-ads" data-toggle="tab" aria-expanded="false"> <span class=""><i class="fa mdi mdi-google mdi-18px text-danger"></i></span> <span class="hidden-xs" lang="en">Google Ads</span> </a>
											</li>
										</ul>
										<div class="tab-content">
											<div class="tab-pane active" id="donate-github">
												<blockquote lang="en">Want to show support on Github?  Sponsor me :)<br/><span lang="en">Please click the button to continue.</span></blockquote>
												<button onclick="window.open(\'https://github.com/sponsors/causefx\', \'_blank\')" class="btn btn-primary btn-rounded waves-effect waves-light" type="button"><span class="btn-label"><i class="fa fa-link"></i></span><span lang="en">Continue To Website</span></button>
											</div>
											<div class="tab-pane" id="donate-paypal">
												<blockquote lang="en">I have chosen to go with PayPal Pools so everyone can see how much people have donated.<br/><span lang="en">Please click the button to continue.</span></blockquote>
												<button onclick="window.open(\'https://paypal.me/pools/c/83JNaMBESR\', \'_blank\')" class="btn btn-primary btn-rounded waves-effect waves-light" type="button"><span class="btn-label"><i class="fa fa-link"></i></span><span lang="en">Continue To Website</span></button>
											</div>
											<div class="tab-pane" id="donate-square">
												<blockquote lang="en">If you use the Square Cash App, you can donate with that if you like.<br/><span lang="en">Please click the button to continue.</span></blockquote>
												<button onclick="window.open(\'https://cash.me/$CauseFX\', \'_blank\')" class="btn btn-primary btn-rounded waves-effect waves-light" type="button"><span class="btn-label"><i class="fa fa-link"></i></span><span lang="en">Continue To Website</span></button>
											</div>
											<div class="tab-pane" id="donate-crypto">
												<blockquote lang="en">Want to donate a small amount of Crypto?.<br/>Please use the QR Code or Wallet ID.</blockquote>
												<div class="col-lg-4 col-xs-12">
													<div class="lazyload qr-code" data-src="plugins/images/Bitcoin_QR_code.png"></div>
													<div class="clearfix"></div>
													<code>18dNtPKgor6pV5DJhYNqFxLJJ2BKugo4K9</code>
												</div>
												<div class="col-lg-4 col-xs-12">
													<div class="lazyload qr-code" data-src="plugins/images/Litecoin_QR_code.png"></div>
													<div class="clearfix"></div>
													<code>LejRxt8huhFGpVrp7TM43VSstrzKGxf8Cj</code>
												</div>
												<div class="col-lg-4 col-xs-12">
													<div class="lazyload qr-code" data-src="plugins/images/Ethereum_QR_code.png"></div>
													<div class="clearfix"></div>
													<code>0x605b678761af62C02Fe0fA86A99053D666dF5d6f</code>
												</div>
												<div class="clearfix"></div>
											</div>
											<div class="tab-pane" id="donate-patreon">
												<blockquote lang="en">Need specialized support or just want to support Organizr?  If so head to Patreon...<br/><span lang="en">Please click the button to continue.</span></blockquote>
												<button onclick="window.open(\'https://www.patreon.com/join/organizr?\', \'_blank\')" class="btn btn-primary btn-rounded waves-effect waves-light" type="button"><span class="btn-label"><i class="fa fa-link"></i></span><span lang="en">Continue To Website</span></button>
											</div>
											<div class="tab-pane" id="donate-open-collective">
												<blockquote lang="en">Need specialized support or just want to support Organizr?  If so head to Open Collective...<br/><span lang="en">Please click the button to continue.</span></blockquote>
												<button onclick="window.open(\'https://opencollective.com/organizr\', \'_blank\')" class="btn btn-primary btn-rounded waves-effect waves-light" type="button"><span class="btn-label"><i class="fa fa-link"></i></span><span lang="en">Continue To Website</span></button>
											</div>
											<div class="tab-pane" id="donate-ads">
												<blockquote lang="en">Money not an option?  No problem.  Show some love to this Google Ad below:</blockquote>
												 <button onclick="window.open(\'https://organizr.app/ads/google.html\', \'_blank\')" class="btn btn-primary btn-rounded waves-effect waves-light" type="button"><span class="btn-label"><i class="fa fa-link"></i></span><span lang="en">Continue To Website</span></button>
											</div>
										</div>
									</div>
								</div>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-settings-updates">
								<div id="githubVersions"></div>
								<div class="clearfix"></div>
							</div>
						</div>
					</section>
				</div>
				<!-- /content -->
			</div>
			<!-- /tabs -->
		</section>
	</div>
	<!--./row-->
</div>
<!-- /.container-fluid -->
<form id="about-theme-form" class="mfp-hide white-popup-block mfp-with-anim">
	<h2 id="about-theme-title">Loading...</h2>
	<div class="clearfix"></div>
	<div id="about-theme-body" class=""></div>
</form>
<div id="editHomepageItemDiv"><div id="editHomepageItem" class=""></div></div>
';
}