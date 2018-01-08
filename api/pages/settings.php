<?php
if(file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
$pageSettings = '
<script>

    (function() {
        updateCheck();
        [].slice.call(document.querySelectorAll(\'.sttabs\')).forEach(function(el) {
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



    <!-- Tabstyle start -->
    <section class="">
        <div class="sttabs tabs-style-flip">
            <nav>
                <ul>
                    <li onclick="changeSettingsMenu(\'Settings::Tab Editor\')"><a href="#settings-main-tab-editor" class="sticon ti-layout-tab-v"><span lang="en">Tab Editor</span></a></li>
                    <li onclick="changeSettingsMenu(\'Settings::Customize\')"><a href="#settings-main-customize" class="sticon ti-paint-bucket"><span lang="en">Customize</span></a></li>
                    <li onclick="changeSettingsMenu(\'Settings::User Management\')"><a href="#settings-main-user-management" class="sticon ti-user"><span lang="en">User Management</span></a></li>
					<li onclick="changeSettingsMenu(\'Settings::Plugins\')"><a href="#settings-main-plugins" class="sticon ti-plug"><span lang="en">Plugins</span></a></li>
                    <li onclick="changeSettingsMenu(\'Settings::System Settings\')"><a href="#settings-main-system-settings" class="sticon ti-settings"><span lang="en">System Settings</span></a></li>
                </ul>
            </nav>
            <div class="content-wrap">
                <section id="settings-main-tab-editor">
                    <ul class="nav customtab2 nav-tabs" role="tablist">
                        <li onclick="changeSettingsMenu(\'Settings::Tab Editor::Tabs\');loadTabEditor();" role="presentation" class=""><a href="#settings-tab-editor-tabs" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-layout-tab-v"></i></span><span class="hidden-xs" lang="en"> Tabs</span></a>
                        </li>
                        <li onclick="changeSettingsMenu(\'Settings::Tab Editor::Categories\');loadCategoryEditor();" role="presentation" class=""><a href="#settings-tab-editor-categories" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-layout-list-thumb"></i></span><span class="hidden-xs" lang="en"> Categories</span></a>
                        </li>
                    </ul>
                    <!-- Tab panes -->
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane fade" id="settings-tab-editor-tabs">
                            <h2 lang="en">Loading...</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="settings-tab-editor-categories">
                            <h2 lang="en">Loading...</h2>
                        </div>
                    </div>
                </section>
                <section id="settings-main-customize">
                    <h2 lang="en">Customize Organizr</h2>
                </section>
                <section id="settings-main-user-management">
                    <ul class="nav customtab2 nav-tabs" role="tablist">
                        <li onclick="changeSettingsMenu(\'Settings::User Management::Manage Users\');loadUserManagement();" role="presentation" class=""><a href="#settings-user-manage-users" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-id-badge"></i></span><span class="hidden-xs" lang="en"> Manage Users</span></a>
                        </li>
                        <li onclick="changeSettingsMenu(\'Settings::User Management::Manage Groups\');loadGroupManagement();" role="presentation" class=""><a href="#settings-user-manage-groups" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-briefcase"></i></span><span class="hidden-xs" lang="en"> Manage Groups</span></a>
                        </li>
                    </ul>
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
                    </div>
                </section>
                <section id="settings-main-plugins">
                    <h2 lang="en">Plugins</h2>
                </section>
                <section id="settings-main-system-settings">
                    <ul class="nav customtab2 nav-tabs" role="tablist">
                        <li onclick="changeSettingsMenu(\'Settings::System Settings::About\')" role="presentation" class="active"><a href="#settings-settings-about" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true"><span class="visible-xs"><i class="ti-info-alt"></i></span><span class="hidden-xs" lang="en"> About</span></a>
                        </li>
                        <li onclick="changeSettingsMenu(\'Settings::System Settings::Main\')" role="presentation" class=""><a href="#settings-settings-main" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-settings"></i></span><span class="hidden-xs" lang="en"> Main</span></a>
                        </li>
                        <li onclick="changeSettingsMenu(\'Settings::System Settings::Logs\');loadLogs();" role="presentation" class=""><a href="#settings-settings-logs" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-receipt"></i></span><span class="hidden-xs" lang="en"> Logs</span></a>
                        </li>
                        <li onclick="changeSettingsMenu(\'Settings::System Settings::Updates\')" role="presentation" class=""><a href="#settings-settings-updates" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-package"></i></span> <span class="hidden-xs" lang="en">Updates</span></a>
                        </li>
                        <li onclick="changeSettingsMenu(\'Settings::System Settings::Donate\')" role="presentation" class=""><a href="#settings-settings-donate" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-money"></i></span> <span class="hidden-xs" lang="en">Donate</span></a>
                        </li>
                    </ul>
                    <!-- Tab panes -->
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane fade" id="settings-settings-main">
                            <h2 lang="en">Main Settings</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="settings-settings-logs">
                            <h2 lang="en">Loading...</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div role="tabpanel" class="tab-pane fade active in" id="settings-settings-about">
							<div class="row">
								<div class="col-lg-6 col-sm-12 col-md-6">
									<div class="panel bg-theme-dark">
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
											<li><a href="https://organizr.us" target="_blank"><i class="mdi mdi-web mdi-24px"></i></a></li>
											<li><a href="https://reddit.com/r/organizr" target="_blank"><i class="mdi mdi-reddit mdi-24px"></i></a></li>
											<li><a href="https://organizr.us/discord" target="_blank"><i class="mdi mdi-discord mdi-24px"></i></a></li>
											<li><a href="https://github.com/causefx/organizr" target="_blank"><i class="mdi mdi-github-box mdi-24px"></i></a></li>
										</ul>
									</div>
								</div>
                                <div class="col-lg-6 col-sm-12 col-md-6">
                                    <div class="white-box bg-theme-dark">
                                        <h3 class="box-title" lang="en">Information</h3>
                                        <ul class="feeds">
                                            <li><div class="bg-info"><i class="mdi mdi-webpack mdi-24px text-white"></i></div><span class="text-muted" lang="en">Organizr Version</span> '.$GLOBALS['installedVersion'].'</li>
                                            <li><div class="bg-info"><i class="mdi mdi-github-box mdi-24px text-white"></i></div><span class="text-muted" lang="en">Organizr Branch</span> '.$GLOBALS['branch'].'</li>
                                            <li><div class="bg-info"><i class="mdi mdi-database mdi-24px text-white"></i></div><span class="text-muted" lang="en">Database Location</span> '.$GLOBALS['dbLocation'].$GLOBALS['dbName'].'</li>
                                            <hr class="m-t-10">
                                            <li><div class="bg-info"><i class="mdi mdi-language-php mdi-24px text-white"></i></div><span class="text-muted" lang="en">PHP Version</span> '.phpversion().'</li>
                                            <li><div class="bg-info"><i class="mdi mdi-package-variant-closed mdi-24px text-white"></i></div><span class="text-muted" lang="en">Webserver Version</span> '.$_SERVER['SERVER_SOFTWARE'].'</li>
                                            <hr class="m-t-10">
                                            <li><div class="bg-info"><i class="mdi mdi-account-card-details mdi-24px text-white"></i></div><span class="text-muted" lang="en">License</span> '.ucwords($GLOBALS['license']).'</li>

                                        </ul>
                                    </div>
                                </div>
							</div>
                            <div class="clearfix"></div>
                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="settings-settings-donate">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="white-box bg-theme-dark">
                                        <h1 class="m-t-0"><i class="fa fa-cc-visa text-info"></i></h1>
                                        <h2>**** **** **** 2150</h2> <span class="pull-right">Expiry date: 10/16</span> <span class="font-500">Johnathan Doe</span> </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="white-box">
                                        <h1 class="m-t-0"><i class="fa fa-cc-mastercard text-danger"></i></h1>
                                        <h2>**** **** **** 2150</h2> <span class="pull-right">Expiry date: 10/16</span> <span class="font-500">Johnathan Doe</span> </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="white-box">
                                        <h1 class="m-t-0"><i class="fa fa-cc-discover text-success"></i></h1>
                                        <h2>**** **** **** 2150</h2> <span class="pull-right">Expiry date: 10/16</span> <span class="font-500">Johnathan Doe</span> </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-12">
                                    <div class="white-box">
                                        <h1 class="m-t-0"><i class="fa fa-cc-amex text-warning"></i></h1>
                                        <h2>**** **** **** 2150</h2> <span class="pull-right">Expiry date: 10/16</span> <span class="font-500">Johnathan Doe</span> </div>
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
';
}
