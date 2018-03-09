<?php
// Include functions if not already included
require_once('functions.php');

// Upgrade environment
upgradeCheck();

// Lazyload settings
$databaseConfig = configLazy('config/config.php');

// Load USER
require_once("user.php");
$USER = new User("registration_callback");
$group = $USER->role;

// Create Database Connection
$file_db = new PDO('sqlite:'.DATABASE_LOCATION.'users.db');
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Some PHP config stuff
ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);

// Confirm Access
qualifyUser('admin', true);

// Load User List
$gotUsers = $file_db->query('SELECT * FROM users');

// Load Invite List
$gotInvites = $file_db->query('SELECT * FROM invites');

// Load Colours/Appearance
foreach(loadAppearance() as $key => $value) {
	$$key = $value;
}

// Slimbar
if(SLIMBAR == "true") {
	$slimBar = "30";
	$userSize = "25";
} else {
	$slimBar = "56";
	$userSize = "40";
}
//Theme Info
$themeName = (!empty(INSTALLEDTHEME) ? explode("-", INSTALLEDTHEME)[0] : null);
$themeVersion = (!empty(INSTALLEDTHEME) ? explode("-", INSTALLEDTHEME)[1] : null);

?>

<!DOCTYPE html>

<html lang="en" class="no-js">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />
        <title>Settings</title>
        <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="bower_components/mdi/css/materialdesignicons.min.css">
        <link rel="stylesheet" href="bower_components/metisMenu/dist/metisMenu.min.css">
        <link rel="stylesheet" href="bower_components/Waves/dist/waves.min.css">
        <link rel="stylesheet" href="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css">
        <link rel="stylesheet" href="js/selects/cs-select.css">
        <link rel="stylesheet" href="js/selects/cs-skin-elastic.css">
        <link rel="stylesheet" href="bower_components/iconpick/dist/css/fontawesome-iconpicker.min.css">
        <link rel="stylesheet" href="bower_components/google-material-color/dist/palette.css">
        <link rel="stylesheet" href="bower_components/sweetalert/dist/sweetalert.css">
        <link rel="stylesheet" href="bower_components/smoke/dist/css/smoke.min.css">
        <link rel="stylesheet" href="bower_components/animate.css/animate.min.css">
        <link rel="stylesheet" href="bower_components/DataTables/media/css/jquery.dataTables.css">
        <link rel="stylesheet" href="bower_components/datatables-tabletools/css/dataTables.tableTools.css">
        <link rel="stylesheet" href="bower_components/numbered/jquery.numberedtextarea.css">
        <link rel="stylesheet" href="css/style.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" href="bower_components/summernote/dist/summernote.css">
        <link rel="stylesheet" href="css/jquery.filer.css">
	    <link rel="stylesheet" href="css/jquery.filer-dragdropbox-theme.css">
        <link rel="stylesheet" href="bower_components/morris.js/morris.css">
		<link rel="stylesheet" href="css/settings.css?v=<?php echo INSTALLEDVERSION; ?>">
        <!--[if lt IE 9]>
        <script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
        <script src="bower_components/respondJs/dest/respond.min.js"></script>
        <![endif]-->
        <!--Scripts-->
		<script src="js/menu/modernizr.custom.js"></script>
        <script src="js/sha1.js"></script>
        <script src="js/user.js"></script>
        <script src="bower_components/jquery/dist/jquery.min.js"></script>
        <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
        <script src="bower_components/metisMenu/dist/metisMenu.min.js"></script>
        <script src="bower_components/Waves/dist/waves.min.js"></script>
        <script src="bower_components/moment/min/moment.min.js"></script>
        <script src="bower_components/jquery.nicescroll/jquery.nicescroll.min.js?v=<?php echo INSTALLEDVERSION; ?>"></script>
        <script src="bower_components/slimScroll/jquery.slimscroll.min.js"></script>
        <script src="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.js"></script>
        <script src="bower_components/cta/dist/cta.min.js"></script>
        <!--Menu-->
        <script src="js/menu/classie.js"></script>
        <script src="bower_components/iconpick/dist/js/fontawesome-iconpicker.js"></script>
        <!--Selects-->
        <script src="js/selects/selectFx.js"></script>
        <script src="js/jscolor.js"></script>
        <script src="bower_components/sweetalert/dist/sweetalert.min.js"></script>
        <script src="bower_components/smoke/dist/js/smoke.min.js"></script>
        <script src="bower_components/numbered/jquery.numberedtextarea.js"></script>
        <!--Other-->
        <script src="js/ajax.js?v=<?php echo INSTALLEDVERSION; ?>"></script>
        <script src="bower_components/raphael/raphael-min.js"></script>
        <script src="bower_components/morris.js/morris.min.js"></script>
        <!--Notification-->
        <script src="js/notifications/notificationFx.js"></script>

        <script src="js/jqueri_ui_custom/jquery-ui.min.js"></script>
        <script src="js/jquery.filer.min.js" type="text/javascript"></script>
        <script src="js/custom.js?v=<?php echo INSTALLEDVERSION; ?>" type="text/javascript"></script>
        <script src="js/jquery.mousewheel.min.js" type="text/javascript"></script>
        <!--Data Tables-->
        <script src="bower_components/DataTables/media/js/jquery.dataTables.js"></script>
        <script src="bower_components/datatables.net-responsive/js/dataTables.responsive.js"></script>
        <script src="bower_components/datatables-tabletools/js/dataTables.tableTools.js"></script>
         <!--Summernote-->
        <script src="bower_components/summernote/dist/summernote.min.js"></script>
		<!--Other-->
		<script src="js/lazyload.min.js"></script>
		<script>
			function addTab() {
				var idNumber = Math.round(Math.random() * 999999999) + 1000000000;
				var $element = $('#tab-new').clone();
				$element.css('display','block');
				$element.attr('id', $element.attr('id').replace('new',idNumber));
				$element.find('[value=new]').attr('value', idNumber).val(idNumber);
				$element.find('[id][name]').each(function () {
					this.id = this.id.replace('new',idNumber);
					this.name = this.name.replace('new',idNumber);
				});
				$element.find('[for]').each(function () {
					$(this).attr('for',$(this).attr('for').replace('new',idNumber));
				});
				$element.appendTo('#submitTabs ul');
				$element.find('.iconpickeradd').iconpicker({placement: 'right', hideOnSelect: false, collision: true});
				$(".iconpicker-items").niceScroll({
					railpadding: {top:0,right:0,left:0,bottom:0},
					scrollspeed: 30,
	                mousescrollstep: 60,
	                grabcursorenabled: false
	            });
                $('.tab-box').scrollTop($('.tab-box')[0].scrollHeight);
			}
			function submitTabs(form) {
				var formData = {};
				var ids = [];
				$.each($(form).serializeArray(), function(i,v) {
					var regmatch = /(\w+)\[((?:new-)?\d+)\]/i.exec(v.name);
					if (regmatch) {
						if (ids.indexOf(regmatch[2]) == -1) {
							ids.push(regmatch[2]);
							if (typeof formData['order'] !== 'object') { formData['order'] = {}; }
							formData['order'][regmatch[2]] = ids.length;
						}
						if (typeof formData[regmatch[1]] !== 'object') { formData[regmatch[1]] = {}; }
						formData[regmatch[1]][regmatch[2]] = v.value;
					} else {
						console.log(regmatch);
					}
				});
				console.log(formData);
				ajax_request('POST', 'submit-tabs', formData);
				return false;
			}
		</script>
        <style>
            body{
                background: #273238;
            }
			.tabs-with-bg .dropdown-menu a:hover {
				color: black !important;
			}
			.shadow {
				-webkit-filter: drop-shadow(0px 0px 0px black);
				filter: drop-shadow(0px 0px 0px black);
			}
			.faded {
				opacity: .5;
			}
            .save-btn-form {
                position: absolute;
                top: 15px;
                right: 60px;
            }
            @media screen and (min-width: 737px){
                .save-btn-form {
                    position: relative;
                    top: 15px;
                    right: 10px;
                    float: right;
                }
            }
            .darkBold {
                color: black;
                font-weight: 500;
            }
			@-webkit-keyframes fadeIn {
				from { opacity: 0; }
				to { opacity: 1; }
			}
			@keyframes fadeIn {
				from { opacity: 0; }
				to { opacity: 1; }
			}
			button.settingsMenu:hover {
				width: 250px !important;
				z-index: 10000;
				opacity: 1 !important;
			}
			button.settingsMenu:hover p{
				display: block !important;
				-webkit-animation: fadeIn 1s;
				animation: fadeIn 1s;
				opacity: 1 !important;
			}
			button.settingsMenuActive {
				margin-left: 0px !important;
				opacity: 1 !important;
			}
			button.settingsMenuInactive {
				opacity: .5;
			}
            .loop-animation {
                animation-iteration-count: infinite;
                -webkit-animation-iteration-count: infinite;
                -moz-animation-iteration-count: infinite;
                -o-animation-iteration-count: infinite;
            }
            @media screen and (max-width:737px){
                .email-body{width: 100%; overflow: auto;}
                .email-content, .email-new {
                    -webkit-overflow-scrolling: touch;
                    -webkit-transform: translateZ(0);
                    overflow: scroll;
                    position: fixed;
                    height:100% !important;
                    margin-top:0;
                }.email-content .email-header, .email-new .email-header{
                    padding: 10px 30px;
                    z-index: 1000;
                }
            }@media screen and (min-width:737px){
                .email-body{width: 100%}
                .email-content .close-button, .email-content .email-actions, .email-new .close-button, .email-new .email-actions {
                    position: relative;
                    top: 15px;
                    right: 0px;
                    float: right;
                }.email-inner-section {
                    margin-top: 50px;
                }.email-content, .email-new {
                    overflow: auto;
                    margin-top: 0;
                    height: 100%;
                    position: fixed;
                    max-width: 100%;
                    width: calc(100% - 50px) !important;
                    right: calc(-100% - 50px);
                }.email-content .email-header, .email-new .email-header{
                    position: fixed;
                    padding: 10px 30px;
                    width: calc(100% - 50px) !important;
                    z-index: 1000;
                }
            }ul.inbox-nav.nav {
                background: white;
                padding: 5px;
                border-radius: 5px;
            }.profile-usermenu ul li.active a {
                border-left: 3px solid <?=$activetabBG;?> !important;
                padding-left: 12px;
            }.profile-usermenu ul li a:hover {
                background: <?=$hoverbg;?> !important;
                color: <?=$hovertext;?> !important;
                cursor: pointer;
            }input.form-control.material.icp-auto.iconpicker-element.iconpicker-input {
                display: none;
            }input.form-control.iconpicker-search {
                color: black;
            }.key {
                font-family:Tahoma, sans-serif;
                border-style:solid;
                border-color:#D5D6AD #C1C1A8 #CDCBA5 #E7E5C5;
                border-width:2px 3px 8px 3px;
                background:#D6D4B4;
                display:inline-block;
                border-radius:5px;
                margin:3px;
                text-align:center;
            }.form-control.material {
                background-image: -webkit-gradient(linear, left top, left bottom, from(<?=$topbartext;?>), to(<?=$topbartext;?>)), -webkit-gradient(linear, left top, left bottom, from(#d2d2d2), to(#d2d2d2));
                background-image: -webkit-linear-gradient(<?=$topbartext;?>, <?=$topbartext;?>), -webkit-linear-gradient(#d2d2d2, #d2d2d2);
                background-image: linear-gradient(<?=$topbartext;?>, <?=$topbartext;?>), linear-gradient(#d2d2d2, #d2d2d2);
            }.key span {
                background:#ECEECA;
                color:#5D5E4F;
                display:block;
                font-size:12px;
                padding:0 2px;
                border-radius:3px;
                width:14px;
                height:18px;
                line-height:18px;
                text-align:center;
                font-weight:bold;
                letter-spacing:1px;
                text-transform:uppercase;
            }.key.wide span {
                width:auto;
                padding:0 12px;
            }.dragging{
                border: 2px solid;
            }.todo .action-btns a span {
                color: #76828e !important;
            }.todo li:nth-child(even) {
                background: #FFFFFF !important;
            }.themeImage {
                position: fixed;
                left: 160px;
                top: 0px;
                height: 400px;
            }.chooseTheme a span {
                position:absolute; display:none; z-index:99;
            }.chooseTheme a:hover span {
                display:block;
            }ul.nav.nav-tabs.apps {
                border: solid;
                border-top: 0;
                border-left: 0;
                border-right: 0;
                border-radius: 0;
            }li.apps.active {
                border: solid;
                border-bottom: 0;
                border-radius: 5px;
                top: 3px;
			}<?php customCSS(); ?>
        </style>
    </head>
    <body id="body-settings" class="scroller-body group-<?php echo $group;?>" style="padding: 0; overflow: hidden">
        <div id="main-wrapper" class="main-wrapper">
            <!--Content-->
            <div id="content"  style="margin:0 10px; overflow:hidden">
				<!-- Update -->
				<div id="updateStatus" class="row" style="display: none;z-index: 10000;position: relative;">
        			<div class="col-lg-2">
          				<div class="content-box box-shadow animated rubberBand">
            				<div class="table-responsive">
              					<table class="table table-striped progress-widget zero-m">
                					<thead class="yellow-bg"><tr><th>Updating</th></tr></thead>
                					<tbody >
										<tr>
											<td>
												<div class="progress">
													<div id="updateStatusBar" class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
													</div>
												</div>
											</td>
                  						</tr>
									</tbody>
              					</table>
            				</div>
						</div>
        			</div>
				</div>
				<!-- Check Frame Modal -->
                <div class="modal fade checkFrame" tabindex="-1" role="dialog">
                	<div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Check iFrame Compatability</h4>
                            </div>
                            <form id="urlTestForm" onsubmit="return false;">
                                <div class="modal-body">
									<?php echo translate("TEST_URL"); ?>
                                    <input type="text" class="form-control material" name="url-test" placeholder="<?php echo translate("URL"); ?>" autocorrect="off" autocapitalize="off" value="">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default waves" data-dismiss="modal">Close</button>
                                    <button id="urlTestForm_submit" class="btn btn-primary waves" data-dismiss="modal">Check Frame URL</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <br/>
                <div id="versionCheck"></div>
                <div class="row">
					<?php
					if($userDevice !== "phone"){
						echo '<div class="col-xs-1" style="width: 60px">';
						echo '
						<button id="apply" type="submit" style="display:none;border-radius: 0px !important; -webkit-border-radius: 20px !important;margin-bottom: -20px;margin-left: 5px;z-index:10000;" class="btn btn-success btn-icon waves waves-circle waves-effect waves-float settingsMenu animated tada">
						<i class="fa fa-retweet fa-fw pull-left" style="padding-left: 12px;"></i>
						<p class="" style="text-align: center;direction: rtl;display:none;"><strong>'.$language->translate("APPLY_CHANGES").'</strong></p>
					</button>
						';
					}else{
						echo '<div class="col-sm-2">';
						echo '<button id="apply" style="width: 100%; display: none;" class="btn waves btn-success btn-sm text-uppercase waves-effect waves-float animated tada" type="submit">'.$language->translate("APPLY_CHANGES").'</button>';
					}
					$buildMenu = array(
						array(
							'id' => 'open-tabs',
							'box' => 'tab-box',
							'name' => 'Edit Tabs',
							'icon_1' => 'view-list',
							'icon_2' => 'th-list',
							'color' => 'red-orange',
							'color2' => 'palette-Red-A700 bg',
							'padding' => '2',
						),
						array(
							'id' => 'open-colors',
							'box' => 'color-box',
							'name' => 'Edit Colors',
							'icon_1' => 'format-paint',
							'icon_2' => 'paint-brush',
							'color' => 'red',
							'color2' => 'palette-Indigo-A700 bg',
							'padding' => '2',
						),
						array(
							'id' => 'open-users',
							'box' => 'users-box',
							'name' => 'Manage Users',
							'icon_1' => 'account-multiple',
							'icon_2' => 'user',
							'color' => 'green',
							'color2' => 'palette-Blue-Grey-700 bg',
							'padding' => '2',
					    ),
					    array(
							'id' => 'open-email',
							'box' => 'email-box',
							'name' => 'Email Users',
							'icon_1' => 'email',
							'icon_2' => 'envelope',
							'color' => 'yellow',
							'color2' => 'palette-Pink-A700 bg',
							'padding' => '2',
						),
						array(
							'id' => 'open-logs',
							'box' => 'logs-box',
							'name' => 'View Logs',
							'icon_1' => 'file-document-box',
							'icon_2' => 'list-alt',
							'color' => 'blue',
							'color2' => 'palette-Teal-A700 bg',
							'padding' => '2',
						),
						array(
							'id' => 'open-homepage',
							'box' => 'homepage-box',
							'name' => 'Edit Homepage',
							'icon_1' => 'television-guide',
							'icon_2' => 'home',
							'color' => 'yellow',
							'color2' => 'palette-Deep-Orange-A400 bg',
							'padding' => '2',
					    ),
						array(
							'id' => 'open-invites',
							'box' => 'invites-box',
							'name' => 'Plex Invites',
							'icon_1' => 'account-multiple-plus',
							'icon_2' => 'user-plus',
							'color' => 'light-blue',
							'color2' => 'palette-Amber-A700 bg',
							'padding' => '2',
						),
						array(
							'id' => 'open-advanced',
							'box' => 'advanced-box',
							'name' => 'Advanced',
							'icon_1' => 'settings',
							'icon_2' => 'cog',
							'color' => 'gray',
							'color2' => 'palette-Grey-600 bg',
							'padding' => '2',
						),array(
							'id' => 'open-info',
							'box' => 'info-box',
							'name' => 'About',
							'icon_1' => 'information',
							'icon_2' => 'info-circle',
							'color' => 'orange',
							'color2' => 'palette-Light-Blue-A700 bg',
							'padding' => '2',
						),array(
							'id' => 'open-help',
							'box' => 'help-box',
							'name' => 'Help & Chat',
							'icon_1' => 'help-circle',
							'icon_2' => 'question-circle',
							'color' => 'orange',
							'color2' => 'palette-Light-Blue-900 bg',
							'padding' => '2',
						),array(
							'id' => 'open-donate',
							'box' => 'donate-box',
							'name' => 'Donate',
							'icon_1' => 'cash-usd',
							'icon_2' => 'money',
							'color' => 'red',
							'color2' => 'palette-Green-A700 bg',
							'padding' => '2',
						),
					);
					if($userDevice !== "phone"){ echo "<br><br><br>".buildMenu($buildMenu); }else{ echo buildMenuPhone($buildMenu); }?>
                    </div>
					<?php if($userDevice !== "phone"){?>
                    <div class="col-lg-10" style="position: absolute;top: 50%;left: 10%;width: 80%;">
						<h1 style="font-size: 50px" class="text-center">ORGANIZR <i class="fa fa-heart fa-1x red loop-animation animated pulse" aria-hidden="true"></i> YOU</h1>
                    </div>
					<?php } ?>
                </div>
                <div class="email-content tab-box white-bg">
                    <div class="email-body">
                        <div class="email-header gray-bg">
                            <button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
                            <button type="button" class="btn waves btn-labeled btn-success btn btn-sm text-uppercase waves-effect waves-float save-btn-form submitTabBtn">
								<span class="btn-label"><i class="fa fa-floppy-o"></i></span><?php echo translate('SAVE_TABS'); ?>
							</button>
                            <h1>Edit Tabs</h1>
                        </div>
                        <div class="email-inner small-box">
                            <div class="email-inner-section">
                                <div class="small-box todo-list fade in" id="tab-tabs">
									<form id="submitTabs" onsubmit="submitTabs(this); return false;">
										<div class="sort-todo">
											<button id="newtab" type="button" class="btn waves btn-labeled btn-success btn-sm text-uppercase waves-effect waves-float" onclick="addTab()">
												<span class="btn-label"><i class="fa fa-plus"></i></span><?php echo translate("NEW_TAB");?>
											</button>
											<button id="iconHide" type="button" class="btn waves btn-labeled btn-warning btn-sm text-uppercase waves-effect waves-float">
												<span class="btn-label"><i class="fa fa-upload"></i></span><?php echo $language->translate("UPLOAD_ICONS");?>
											</button>
											<button id="iconAll" type="button" class="btn waves btn-labeled btn-info btn-sm text-uppercase waves-effect waves-float">
												<span class="btn-label"><i class="fa fa-picture-o"></i></span><?php echo $language->translate("VIEW_ICONS");?>
											</button>
           									<button id="checkFrame" data-toggle="modal" data-target=".checkFrame" type="button" class="btn waves btn-labeled btn-gray btn-sm text-uppercase waves-effect waves-float">
												<span class="btn-label"><i class="fa fa-check"></i></span><?php echo $language->translate("CHECK_FRAME");?>
											</button>
                                            <button id="toggleAllExtra" type="button" class="btn waves btn-labeled btn-info btn-sm text-uppercase waves-effect waves-float indigo-bg">
												<span class="btn-label"><i class="fa fa-toggle-off"></i></span><span class="btn-text"><?php echo $language->translate("TOGGLE_ALL");?></span>
											</button>
										</div>
										<input type="file" name="files[]" id="uploadIcons" multiple="multiple">
										<div id="viewAllIcons" style="display: none;">
											<h4><strong><?php echo $language->translate("ALL_ICONS");?></strong> [<?php echo $language->translate("CLICK_ICON");?>]</h4>
											<div class="row">
												<textarea id="copyTarget" class="hideCopy" style="left: -9999px; top: 0; position: absolute;"></textarea>
												<?php echo loadIcons();?>
											</div>
										</div>
										<div class="panel">
                                            <div class="panel-body todo">
                                                <ul class="list-group ui-sortable">
													<?php
													foreach($file_db->query('SELECT * FROM tabs ORDER BY `order` asc') as $key => $row) {
														if (!isset($row['id'])) { $row['id'] = $key + 1; }
														echo printTabRow($row);
													}
													?>
                                                </ul>
                                            </div>
										</div>
									</form>
									<?php echo printTabRow(false); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="email-content color-box white-bg">
<?php
// Build Colour Settings
echo buildSettings(
	array(
		'title' => 'Appearance Settings',
		'id' => 'appearance_settings',
		'submitAction' => 'update-appearance',
		'tabs' => array(
			array(
				'title' => 'Colours',
				'id' => 'theme_colours',
				'image' => 'images/paint.png',
				'fields' => array(
					array(
						array(
							'type' => 'button',
							'labelTranslate' => 'COLOR_TEMPLATE',
							'icon' => 'css3',
							'id' => 'themeSelector',
							'buttonType' => 'dark',
							'buttonDrop' => '
							<ul class="dropdown-menu gray-bg">
								<li class="chooseTheme" id="plexTheme" style="border: 1px #FFFFFF; border-style: groove; background: #000000; border-radius: 5px; margin: 5px;"><a style="color: #E49F0C !important;" href="#">Plex<span><img class="themeImage" src="images/themes/plex.png"></span></a></li>
								<li class="chooseTheme" id="newPlexTheme" style="border: 1px #E5A00D; border-style: groove; background: #282A2D; border-radius: 5px; margin: 5px;"><a style="color: #E5A00D !important;" href="#">New Plex<span><img class="themeImage" src="images/themes/newplex.png"></span></a></li>
								<li class="chooseTheme" id="embyTheme" style="border: 1px #FFFFFF; border-style: groove; background: #212121; border-radius: 5px; margin: 5px;"><a style="color: #52B54B !important;" href="#">Emby<span><img class="themeImage" src="images/themes/emby.png"></span></a></li>
								<li class="chooseTheme" id="bookTheme" style="border: 1px #FFFFFF; border-style: groove; background: #3B5998; border-radius: 5px; margin: 5px;"><a style="color: #FFFFFF !important;" href="#">Facebook<span><img class="themeImage" src="images/themes/facebook.png"></span></a></li>
								<li class="chooseTheme" id="spaTheme" style="border: 1px #66BBAE; border-style: groove; background: #66BBAE; border-radius: 5px; margin: 5px;"><a style="color: #5B391E !important;" href="#">Spa<span><img class="themeImage" src="images/themes/spa.png"></span></a></li>
								<li class="chooseTheme" id="darklyTheme" style="border: 1px #464545; border-style: groove; background: #375A7F; border-radius: 5px; margin: 5px;"><a style="color: #FFFFFF !important;" href="#">Darkly<span><img class="themeImage" src="images/themes/darkly.png"></span></a></li>
								<li class="chooseTheme" id="slateTheme" style="border: 1px #58C0DE; border-style: groove; background: #272B30; border-radius: 5px; margin: 5px;"><a style="color: #C8C8C8 !important;" href="#">Slate<span><img class="themeImage" src="images/themes/slate.png"></span></a></li>
								<li class="chooseTheme" id="monokaiTheme" style="border: 1px #AD80FD; border-style: groove; background: #333333; border-radius: 5px; margin: 5px;"><a style="color: #66D9EF !important;" href="#">Monokai<span><img class="themeImage" src="images/themes/monokai.png"></span></a></li>
								<li class="chooseTheme" id="thejokerTheme" style="border: 1px #CCC6CC; border-style: groove; background: #000000; border-radius: 5px; margin: 5px;"><a style="color: #CCCCCC !important;" href="#">The Joker<span><img class="themeImage" src="images/themes/joker.png"></span></a></li>
								<li class="chooseTheme" id="redTheme" style="border: 1px #eb6363; border-style: groove; background: #eb6363; border-radius: 5px; margin: 5px;"><a style="color: #FFFFFF !important;" href="#">Original Red<span><img class="themeImage" src="images/themes/original.png"></span></a></li>
							</ul>
							',
                        ),
                        array(
							'type' => 'button',
							'labelTranslate' => 'CHOOSE_THEME',
							'icon' => 'birthday-cake',
							'id' => 'layerCake',
							'buttonType' => 'dark',
							'buttonDrop' => '
							<ul class="dropdown-menu">
								<li class="dropdown-header">Choose a Theme Below</li>
								<li id="open-themes" box="themes-box" onclick"" data-toggle="tooltip" data-placement="top" title="" data-original-title="Custom Themes Created by The Community"><a onclick="" href="#">Themes</a></li>
								<li id="layerCakeDefault" data-toggle="tooltip" data-placement="top" title="" data-original-title="A 7 color theme based on Organizr"><a onclick="layerCake(\'Basic\',\'layerCake\');$(\'#customCSS_id\').attr(\'data-changed\', \'true\');" href="#">LAYER#CAKE Basic</a></li>
								<li id="layerCakeCustom" data-toggle="tooltip" data-placement="top" title="" data-original-title="A 32 color theme based on Organizr"><a onclick="layerCake(\'Advanced\',\'layerCake\');$(\'#customCSS_id\').attr(\'data-changed\', \'true\');" href="#">LAYER#CAKE Advanced</a></li>
							</ul>
							',
						),
					),
					array(
						'type' => 'header',
						'labelTranslate' => 'TITLE',
					),
					array(
						array(
							'type' => 'text',
							'format' => 'colour',
							'labelTranslate' => 'TITLE',
							'name' => 'title',
							'id' => 'title',
							'value' => $title,
						),
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'TITLE_TEXT',
							'name' => 'topbartext',
							'id' => 'topbartext',
							'value' => $topbartext,
						),
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'LOADING_COLOR',
							'name' => 'loading',
							'id' => 'loading',
							'value' => $loading,
						),
					),
					array(
						'type' => 'header',
						'labelTranslate' => 'NAVIGATION_BARS',
					),
					array(
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'TOP_BAR',
							'name' => 'topbar',
							'id' => 'topbar',
							'value' => $topbar,
						),
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'BOTTOM_BAR',
							'name' => 'bottombar',
							'id' => 'bottombar',
							'value' => $bottombar,
						),
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'SIDE_BAR',
							'name' => 'sidebar',
							'id' => 'sidebar',
							'value' => $sidebar,
						),
					),
					array(
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'HOVER_BG',
							'name' => 'hoverbg',
							'id' => 'hoverbg',
							'value' => $hoverbg,
						),
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'HOVER_TEXT',
							'name' => 'hovertext',
							'id' => 'hovertext',
							'value' => $hovertext,
						),
					),
					array(
						'type' => 'header',
						'labelTranslate' => 'ACTIVE_TAB',
					),
					array(
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'ACTIVE_TAB_BG',
							'name' => 'activetabBG',
							'id' => 'activetabBG',
							'value' => $activetabBG,
						),
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'ACTIVE_TAB_ICON',
							'name' => 'activetabicon',
							'id' => 'activetabicon',
							'value' => $activetabicon,
						),
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'ACTIVE_TAB_TEXT',
							'name' => 'activetabtext',
							'id' => 'activetabtext',
							'value' => $activetabtext,
						),
					),
					array(
						'type' => 'header',
						'labelTranslate' => 'INACTIVE_TAB',
					),
					array(
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'INACTIVE_ICON',
							'name' => 'inactiveicon',
							'id' => 'inactiveicon',
							'value' => $inactiveicon,
						),
						array(
							'type' => 'text',
							'format' => 'colour',
							'class' => 'jscolor {hash:true}',
							'labelTranslate' => 'INACTIVE_TEXT',
							'name' => 'inactivetext',
							'id' => 'inactivetext',
							'value' => $inactivetext,
						),
					),
				),
			),
			array(
				'title' => 'Custom CSS',
				'id' => 'theme_css',
				'image' => 'images/css.png',
				'fields' => array(
					array(
						'type' => 'header',
						'label' => 'Custom CSS',
                    ),
                    array(
                        'type' => 'header',
                        'class' => '',
						'label' => (empty(INSTALLEDTHEME)?'<span class="themeHeader">Installed Theme: No Theme Installed</span>':'<span class="themeHeader">Installed Theme: '.INSTALLEDTHEME.'</span><button id="clearTheme" type="button" class="btn waves pull-right btn-labeled btn-sm btn-danger text-uppercase waves-effect waves-float"><span class="btn-label"><i class="fa fa-trash"></i></span> Clear Theme</button>'),
					),
					array(
						'type' => 'textarea',
						'name' => 'customCSS',
						'value' => (file_exists('./custom.css')?file_get_contents('./custom.css'):''),
						'rows' => 25,
						'style' => 'background: #000; color: #FFF;',
					),
				),
			),
		),
	)
);
?>
                </div>
                <div class="email-content homepage-box white-bg">
<?php
// Qualify most typical hostnames prior to form submission
$urlPattern = '([hH][tT][tT][pP][sS]?):\/\/([\w\.\-]{1,250})(?::(\d{1,5}))?((?:\/[^?.\s]+))?';

// Once configurable user groups is added change to select-multi to allow specific group selection
$userSelectType = 'select';
$userTypes = array(
	'None' => 'false',
	'User' => 'user|admin',
	'Admin' => 'admin',
);
$branchTypes = array(
	'Master' => 'master',
	'Develop' => 'develop',
	'Pre-Develop' => 'cero-dev',
);
$refreshSeconds = array(
	'1 sec' => '1000',
	'5 secs' => '5000',
	'10 secs' => '10000',
	'15 secs' => '15000',
	'30 secs' => '30000',
	'1 min' => '60000',
	'1.5 mins' => '90000',
	'2 mins' => '120000',
	'5 mins' => '300000',
	'10 mins' => '600000',
	'15 mins' => '900000',
	'30 mins' => '1800000',
	'45 mins' => '2700000',
	'1 hour' => '3600000',
);

// Build Homepage Settings
echo buildSettings(
	array(
		'title' => 'Homepage Settings',
		'id' => 'homepage_settings',
		'onready' => '',
		'tabs' => array(
			array(
				'title' => 'General',
				'id' => 'home_general',
				'image' => 'images/gear.png',
				'fields' => array(
					array(
						'type' => 'custom',
						'html' => '<h2>To Enable, please add new tab with url of homepage.php</h2>',
					),
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_HOMEPAGE',
						'name' => 'homePageAuthNeeded',
						'value' => HOMEPAGEAUTHNEEDED,
						'options' => $userTypes,
					),
					/*
					array(
						'type' => 'custom',
						'labelTranslate' => 'SHOW_HOMEPAGE',
						'html' => 'homePageAuthNeeded',
						'name' => 'homePagelayout',
						'value' => '',
					),
					*/
				),
			),
			array(
				'title' => 'Plex',
				'id' => 'plex',
				'image' => 'images/plex.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'plexHomeAuth',
						'value' => PLEXHOMEAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:32400',
						'labelTranslate' => 'PLEX_URL',
						'assist' => 'http://hostname:32400',
						'name' => 'plexURL',
						'pattern' => $urlPattern,
						'value' => PLEXURL,
					),
					array(
						array(
						'type' => 'text',
						'placeholder' => randString(20),
						'labelTranslate' => 'PLEX_TOKEN',
						'name' => 'plexToken',
						'pattern' => '[a-zA-Z0-9]{20}',
						'value' => PLEXTOKEN,
						),
						array(
							'type' => 'custom',
							'html' => '<button id="openPlexModal" type="button" class="btn waves btn-labeled btn-success btn-sm text-uppercase waves-effect waves-float"> <span class="btn-label"><i class="fa fa-ticket"></i></span>'.translate("GET_PLEX_TOKEN").'</button>',
						),
					),
     				array(
						array(
						'type' => 'text',
						'placeholder' => "",
						'labelTranslate' => 'RECENT_ITEMS_LIMIT',
						'name' => 'plexRecentItems',
						'pattern' => '[0-9]+',
						'value' => PLEXRECENTITEMS,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'RECENT_REFRESH',
							'name' => 'recentRefresh',
							'value' => RECENTREFRESH,
							'options' => $refreshSeconds,
						),
					),
					array(
						'type' => 'text',
						'placeholder' => "Name of Plex Tab i.e. Plex",
						'labelTranslate' => 'PLEX_TAB_NAME',
						'name' => 'plexTabName',
						'value' => PLEXTABNAME,
					),
					array(
						'type' => 'text',
						'placeholder' => "URL For Plex Links",
						'labelTranslate' => 'PLEX_TAB_URL',
						'name' => 'plexTabURL',
						'value' => PLEXTABURL,
					),
					array(
      					array(
							'type' => 'checkbox',
							'labelTranslate' => 'ALLOW_SEARCH',
							'name' => 'plexSearch',
							'value' => PLEXSEARCH,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'SHOW_ON_HOMEPAGE',
							'name' => 'plexHomeAuth',
							'value' => PLEXSEARCHAUTH,
							'options' => $userTypes,
						),
					),
					array(
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'RECENT_MOVIES',
							'name' => 'plexRecentMovie',
							'value' => PLEXRECENTMOVIE,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'SHOW_ON_HOMEPAGE',
							'name' => 'plexRecentMovieAuth',
							'value' => PLEXRECENTMOVIEAUTH,
							'options' => $userTypes,
						),
					),
					array(
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'RECENT_TV',
							'name' => 'plexRecentTV',
							'value' => PLEXRECENTTV,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'SHOW_ON_HOMEPAGE',
							'name' => 'plexRecentTVAuth',
							'value' => PLEXRECENTTVAUTH,
							'options' => $userTypes,
						),
					),
					array(
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'RECENT_MUSIC',
							'name' => 'plexRecentMusic',
							'value' => PLEXRECENTMUSIC,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'SHOW_ON_HOMEPAGE',
							'name' => 'plexRecentMusicAuth',
							'value' => PLEXRECENTMUSICAUTH,
							'options' => $userTypes,
						),
					),
					array(
                        array(
							'type' => 'checkbox',
							'labelTranslate' => 'PLAYLISTS',
							'name' => 'plexPlaylists',
							'value' => PLEXPLAYLISTS,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'SHOW_ON_HOMEPAGE',
							'name' => 'plexPlaylistsAuth',
							'value' => PLEXPLAYLISTSAUTH,
							'options' => $userTypes,
						),
					),
					array(
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'PLAYING_NOW',
							'name' => 'plexPlayingNow',
							'value' => PLEXPLAYINGNOW,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'SHOW_ON_HOMEPAGE',
							'name' => 'plexPlayingNowAuth',
							'value' => PLEXPLAYINGNOWAUTH,
							'options' => $userTypes,
						),
					),
					array(
      					array(
							'type' => 'checkbox',
							'labelTranslate' => 'SHOW_NAMES',
							'name' => 'plexShowNames',
							'value' => PLEXSHOWNAMES,
						),
					),
				),
			),
			array(
				'title' => 'Emby',
				'id' => 'emby',
				'image' => 'images/emby.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'embyHomeAuth',
						'value' => EMBYHOMEAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:8096/emby',
						'labelTranslate' => 'EMBY_URL',
						'assist' => 'http://hostname:8096 | https://hostname/emby | http://hostname:8096/emby',
						'name' => 'embyURL',
						'pattern' => $urlPattern,
						'value' => EMBYURL,
					),
					array(
						'type' => 'text',
						'placeholder' => randString(32),
						'labelTranslate' => 'EMBY_TOKEN',
						'name' => 'embyToken',
						'pattern' => '[a-zA-Z0-9]{32}',
						'value' => EMBYTOKEN,
					),
					array(
	     				array(
							'type' => 'text',
							'placeholder' => "",
							'labelTranslate' => 'RECENT_ITEMS_LIMIT',
							'name' => 'embyRecentItems',
							'pattern' => '[0-9]+',
							'value' => EMBYRECENTITEMS,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'RECENT_REFRESH',
							'name' => 'recentRefresh',
							'value' => RECENTREFRESH,
							'options' => $refreshSeconds,
						),
					),
					array(
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'RECENT_MOVIES',
							'name' => 'embyRecentMovie',
							'value' => EMBYRECENTMOVIE,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'SHOW_ON_HOMEPAGE',
							'name' => 'embyRecentMovieAuth',
							'value' => EMBYRECENTMOVIEAUTH,
							'options' => $userTypes,
						),
					),
					array(
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'RECENT_TV',
							'name' => 'embyRecentTV',
							'value' => EMBYRECENTTV,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'SHOW_ON_HOMEPAGE',
							'name' => 'embyRecentTVAuth',
							'value' => EMBYRECENTTVAUTH,
							'options' => $userTypes,
						),
					),
					array(
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'RECENT_MUSIC',
							'name' => 'embyRecentMusic',
							'value' => EMBYRECENTMUSIC,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'SHOW_ON_HOMEPAGE',
							'name' => 'embyRecentMusicAuth',
							'value' => EMBYRECENTMUSICAUTH,
							'options' => $userTypes,
						),
					),
					array(
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'PLAYING_NOW',
							'name' => 'embyPlayingNow',
							'value' => EMBYPLAYINGNOW,
						),
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'SHOW_ON_HOMEPAGE',
							'name' => 'embyPlayingNowAuth',
							'value' => EMBYPLAYINGNOWAUTH,
							'options' => $userTypes,
						),
					),
					array(
      					array(
							'type' => 'checkbox',
							'labelTranslate' => 'SHOW_NAMES',
							'name' => 'embyShowNames',
							'value' => EMBYSHOWNAMES,
						),
					),
				),
			),
			array(
				'title' => 'Sonarr',
				'id' => 'sonarr',
				'image' => 'images/sonarr.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'sonarrHomeAuth',
						'value' => SONARRHOMEAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:8989',
						'labelTranslate' => 'SONARR_URL',
						'assist' => 'http://hostname:8989 | hostname/sonarr | http://hostname:8989/sonarr',
						'name' => 'sonarrURL',
						'pattern' => $urlPattern,
						'value' => SONARRURL,
					),
					array(
						'type' => 'text',
						'placeholder' => randString(32),
						'labelTranslate' => 'SONARR_KEY',
						'name' => 'sonarrKey',
						'pattern' => '[a-zA-Z0-9]{32}',
						'value' => SONARRKEY,
					),
          array(
						'type' => 'checkbox',
						'labelTranslate' => 'SONARR_UNMONITORED',
						'name' => 'sonarrUnmonitored',
						'value' => SONARRUNMONITORED,
					),
				),
			),
			array(
				'title' => 'Radarr',
				'id' => 'radarr',
				'image' => 'images/radarr.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'radarrHomeAuth',
						'value' => RADARRHOMEAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:7878',
						'labelTranslate' => 'RADARR_URL',
						'assist' => 'http://hostname:7878 | hostname/radarr | http://hostname:7878/radarr',
						'name' => 'radarrURL',
						'pattern' => $urlPattern,
						'value' => RADARRURL,
					),
					array(
						'type' => 'text',
						'placeholder' => randString(32),
						'labelTranslate' => 'RADARR_KEY',
						'name' => 'radarrKey',
						'pattern' => '[a-zA-Z0-9]{32}',
						'value' => RADARRKEY,
					),
				),
            ),
            array(
				'title' => 'CouchPotato',
				'id' => 'couchpotato',
				'image' => 'images/couchpotato.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'couchHomeAuth',
						'value' => COUCHHOMEAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:8181',
						'labelTranslate' => 'COUCH_URL',
						'assist' => 'http://hostname:8181',
						'name' => 'couchURL',
						'pattern' => $urlPattern,
						'value' => COUCHURL,
					),
					array(
						'type' => 'text',
						'placeholder' => randString(32),
						'labelTranslate' => 'COUCH_KEY',
						'name' => 'couchAPI',
						'value' => COUCHAPI,
					),
				),
			),
			array(
				'title' => 'Sickbeard/Sickrage',
				'id' => 'sick',
				'image' => 'images/sickrage.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'sickrageHomeAuth',
						'value' => SICKRAGEHOMEAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:8081/sick',
						'labelTranslate' => 'SICK_URL',
						'assist' => 'http://hostname:8081 | hostname/sick | http://hostname:8081/sick',
						'name' => 'sickrageURL',
						'pattern' => $urlPattern,
						'value' => SICKRAGEURL,
					),
					array(
						'type' => 'text',
						'placeholder' => randString(32),
						'labelTranslate' => 'SICK_KEY',
						'name' => 'sickrageKey',
						'value' => SICKRAGEKEY,
					),
				),
			),
			array(
				'title' => 'Headphones',
				'id' => 'headphones',
				'image' => 'images/headphones.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'headphonesHomeAuth',
						'value' => HEADPHONESHOMEAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:8181',
						'labelTranslate' => 'HEADPHONES_URL',
						'assist' => 'http://hostname:8181',
						'name' => 'headphonesURL',
						'pattern' => $urlPattern,
						'value' => HEADPHONESURL,
					),
					array(
						'type' => 'text',
						'placeholder' => randString(32),
						'labelTranslate' => 'HEADPHONES_KEY',
						'name' => 'headphonesKey',
						'value' => HEADPHONESKEY,
					),
				),
			),
			array(
				'title' => 'Sabnzbd',
				'id' => 'sabnzbd',
				'image' => 'images/sabnzbd.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'sabnzbdHomeAuth',
						'value' => SABNZBDHOMEAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:8080/sabnzbd',
						'labelTranslate' => 'SABNZBD_URL',
						'assist' => 'http://hostname:8080 | http://hostname/sabnzbd | http://hostname:8080/sabnzbd',
						'name' => 'sabnzbdURL',
						'pattern' => $urlPattern,
						'value' => SABNZBDURL,
					),
					array(
						'type' => 'text',
						'placeholder' => randString(32),
						'labelTranslate' => 'SABNZBD_KEY',
						'name' => 'sabnzbdKey',
						'value' => SABNZBDKEY,
					),
                    array(
						'type' => $userSelectType,
						'labelTranslate' => 'DOWNLOAD_REFRESH',
						'name' => 'downloadRefresh',
						'value' => DOWNLOADREFRESH,
						'options' => $refreshSeconds,
					),
                    array(
						'type' => $userSelectType,
						'labelTranslate' => 'HISTORY_REFRESH',
						'name' => 'historyRefresh',
						'value' => HISTORYREFRESH,
						'options' => $refreshSeconds,
					),
				),
			),
			array(
				'title' => 'nzbGET',
				'id' => 'nzbget',
				'image' => 'images/nzbget.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'nzbgetHomeAuth',
						'value' => NZBGETHOMEAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:6789',
						'labelTranslate' => 'NZBGET_URL',
						'assist' => 'http://hostname:6789',
						'name' => 'nzbgetURL',
						'pattern' => $urlPattern,
						'value' => NZBGETURL,
					),
					array(
						'type' => 'text',
						'labelTranslate' => 'USERNAME',
						'name' => 'nzbgetUsername',
						'value' => NZBGETUSERNAME,
					),
					array(
						'type' => 'password',
						'labelTranslate' => 'PASSWORD',
						'name' => 'nzbgetPassword',
						'value' => (empty(NZBGETPASSWORD)?'':randString(20)),
                        'autocomplete' => 'new-password',
					),
                    array(
						'type' => $userSelectType,
						'labelTranslate' => 'DOWNLOAD_REFRESH',
						'name' => 'downloadRefresh',
						'value' => DOWNLOADREFRESH,
						'options' => $refreshSeconds,
					),
                    array(
						'type' => $userSelectType,
						'labelTranslate' => 'HISTORY_REFRESH',
						'name' => 'historyRefresh',
						'value' => HISTORYREFRESH,
						'options' => $refreshSeconds,
					),
				),
			),
			array(
				'title' => 'Transmission',
				'id' => 'transmission',
				'image' => 'images/transmission.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'transmissionHomeAuth',
						'value' => TRANSMISSIONHOMEAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:6789',
						'labelTranslate' => 'TRANSMISSION_URL',
						'assist' => 'http://hostname:6789',
						'name' => 'transmissionURL',
						'pattern' => $urlPattern,
						'value' => TRANSMISSIONURL,
					),
					array(
						'type' => 'text',
						'labelTranslate' => 'USERNAME',
						'name' => 'transmissionUsername',
						'value' => TRANSMISSIONUSERNAME,
					),
					array(
						'type' => 'password',
						'labelTranslate' => 'PASSWORD',
						'name' => 'transmissionPassword',
						'value' => (empty(TRANSMISSIONPASSWORD)?'':randString(20)),
                        'autocomplete' => 'new-password',
					),
                    array(
						'type' => $userSelectType,
						'labelTranslate' => 'DOWNLOAD_REFRESH',
						'name' => 'downloadRefresh',
						'value' => DOWNLOADREFRESH,
						'options' => $refreshSeconds,
					),
				),
			),
			array(
				'title' => 'Calendar',
				'id' => 'calendar',
				'image' => 'images/calendar.png',
				'fields' => array(
					array(
						'type' => 'select',
						'labelTranslate' => 'CALENDAR_START_DAY',
						'name' => 'calendarStart',
						'value' => CALENDARSTART,
						'options' => array(
							explode('|', translate('DAYS'))[0] => '0',
							explode('|', translate('DAYS'))[1] => '1',
							explode('|', translate('DAYS'))[2] => '2',
							explode('|', translate('DAYS'))[3] => '3',
							explode('|', translate('DAYS'))[4] => '4',
							explode('|', translate('DAYS'))[5] => '5',
							explode('|', translate('DAYS'))[6] => '6',
						),
					),
					array(
						'type' => 'select',
						'labelTranslate' => 'DEFAULT',
						'name' => 'calendarView',
						'value' => CALENDARVIEW,
						'options' => array(
							translate('MONTH') => 'month',
							translate('DAY') => 'basicDay',
							translate('WEEK') => 'basicWeek',
						),
					),
					array(
						'type' => 'select',
						'labelTranslate' => 'CALTIMEFORMAT',
						'name' => 'calTimeFormat',
						'value' => CALTIMEFORMAT,
						'options' => array(
							'6p' => 'h(:mm)t',
							'6:00p' => 'h:mmt',
							'6:00' => 'h:mm',
							'18' => 'H(:mm)',
							'18:00' => 'H:mm',
						),
					),
					array(
						'type' => 'number',
						'placeholder' => '10',
						'labelTranslate' => 'CALENDAR_START_DATE',
						'name' => 'calendarStartDay',
						'pattern' => '[1-9][0-9]+',
						'value' => CALENDARSTARTDAY,
					),
					array(
						'type' => 'number',
						'placeholder' => '10',
						'labelTranslate' => 'CALENDAR_END_DATE',
						'name' => 'calendarEndDay',
						'pattern' => '[1-9][0-9]+',
						'value' => CALENDARENDDAY,
                    ),
                    array(
						'type' => $userSelectType,
						'labelTranslate' => 'CALENDAR_REFRESH',
						'name' => 'calendarRefresh',
						'value' => CALENDARREFRESH,
						'options' => $refreshSeconds,
					),
				),
			),
   			array(
				'title' => 'Notice',
				'id' => 'notice',
				'image' => 'images/pin.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'homepageNoticeAuth',
						'value' => HOMEPAGENOTICEAUTH,
						'options' => $userTypes,
					),
     				array(
						'type' => $userSelectType,
						'labelTranslate' => 'NOTICE_LAYOUT',
						'name' => 'homepageNoticeLayout',
						'value' => HOMEPAGENOTICELAYOUT,
						'options' => array(
							'Elegant' => 'elegant',
							'Basic' => 'basic',
							'Jumbotron' => 'jumbotron',
						),
					),
     				array(
						'type' => $userSelectType,
						'labelTranslate' => 'NOTICE_COLOR',
						'name' => 'homepageNoticeType',
						'value' => HOMEPAGENOTICETYPE,
						'options' => array(
							'Green' => 'success',
							'Blue' => 'primary',
							'Gray' => 'gray',
							'Red' => 'danger',
							'Yellow' => 'warning',
							'Light Blue' => 'info',
						),
					),
     				array(
						'type' => 'text',
						'labelTranslate' => 'NOTICE_TITLE',
						'name' => 'homepageNoticeTitle',
						'value' => HOMEPAGENOTICETITLE,
					),
					/*array(
						'type' => 'textarea',
						'labelTranslate' => 'NOTICE_MESSAGE',
						'name' => 'homepageNoticeMessage',
						'value' => HOMEPAGENOTICEMESSAGE,
      					'rows' => 5,
						'class' => 'material no-code',
					),*/
        			array(
						'type' => 'custom',
		 				'labelTranslate' => 'NOTICE_MESSAGE',
						'html' => '<div class="summernote" name="homepageNoticeMessage">'.HOMEPAGENOTICEMESSAGE.'</div>',
					),
                    array(
						'type' => 'custom',
						'html' => '<h2>Not Logged In/Guest Notice</h2>',
					),
                    array(
						'type' => $userSelectType,
						'labelTranslate' => 'NOTICE_LAYOUT',
						'name' => 'homepageNoticeLayoutGuest',
						'value' => HOMEPAGENOTICELAYOUTGUEST,
						'options' => array(
							'Elegant' => 'elegant',
							'Basic' => 'basic',
							'Jumbotron' => 'jumbotron',
						),
					),
     				array(
						'type' => $userSelectType,
						'labelTranslate' => 'NOTICE_COLOR',
						'name' => 'homepageNoticeTypeGuest',
						'value' => HOMEPAGENOTICETYPEGUEST,
						'options' => array(
							'Green' => 'success',
							'Blue' => 'primary',
							'Gray' => 'gray',
							'Red' => 'danger',
							'Yellow' => 'warning',
							'Light Blue' => 'info',
						),
					),
     				array(
						'type' => 'text',
						'labelTranslate' => 'NOTICE_TITLE',
						'name' => 'homepageNoticeTitleGuest',
						'value' => HOMEPAGENOTICETITLEGUEST,
					),
        			array(
						'type' => 'custom',
		 				'labelTranslate' => 'NOTICE_MESSAGE',
						'html' => '<div class="summernote" name="homepageNoticeMessageGuest">'.HOMEPAGENOTICEMESSAGEGUEST.'</div>',
					),
				),
			),
			array(
				'title' => 'Ombi',
				'id' => 'ombiSettings',
				'image' => 'images/ombi.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'ombiAuth',
						'value' => OMBIAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:5000',
						'labelTranslate' => 'OMBI_URL',
						'assist' => 'http://hostname:5000 | http://hostname/ombi | http://hostname:5000/ombi',
						'name' => 'ombiURL',
						'pattern' => $urlPattern,
						'value' => OMBIURL,
					),
					array(
						'type' => 'text',
						'placeholder' => randString(32),
						'labelTranslate' => 'OMBI_KEY',
						'name' => 'ombiKey',
						'value' => OMBIKEY,
					),
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'REQUEST_REFRESH',
						'name' => 'requestRefresh',
						'value' => REQUESTREFRESH,
						'options' => $refreshSeconds,
					),
                    array(
						'type' => 'checkbox',
						'labelTranslate' => 'REQUESTED_ONLY',
						'name' => 'requestedUserOnly',
						'value' => REQUESTEDUSERONLY,
					),
					array(
						'type' => 'custom',
						'html' => '<h2>Requires Ombi V3.0.2165 & Above</h2>',
					),
				),
			),
			array(
				'title' => 'Speed Test',
				'id' => 'speedTestSettings',
				'image' => 'images/settings/full-color/png/64px/speedometer.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'speedtestAuth',
						'value' => SPEEDTESTAUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'checkbox',
						'labelTranslate' => 'SPEED_TEST',
						'name' => 'speedTest',
						'value' => SPEEDTEST,
					),
					array(
						'type' => 'custom',
						'html' => '<button id="open-speedtest" box="speed-box" type="button" class="btn waves btn-labeled btn-success btn-sm text-uppercase waves-effect waves-float"><span class="btn-label"><i class="fa fa-star"></i></span> History</button>',
						'name' => 'speed_test_history',
						'value' => '',
					),
				),
			),
			array(
				'title' => 'Custom HTML 1',
				'id' => 'customhtml1',
				'image' => 'images/html.png',
				'fields' => array(
					array(
						'type' => $userSelectType,
						'labelTranslate' => 'SHOW_ON_HOMEPAGE',
						'name' => 'homepageCustomHTML1Auth',
						'value' => HOMEPAGECUSTOMHTML1AUTH,
						'options' => $userTypes,
					),
					array(
						'type' => 'textarea',
						'labelTranslate' => 'CUSTOMHTML',
						'name' => 'homepageCustomHTML1',
						'value' => HOMEPAGECUSTOMHTML1,
						'rows' => 15,
						'style' => 'background: #000; color: #FFF;',
					),
				),
			),
			array(
				'title' => 'Homepage Arrangement',
				'id' => 'homepageArrangement',
				'image' => 'images/settings/full-color/png/64px/news.png',
				'fields' => array(
					array(
						'type' => 'custom',
						'html' => buildHomepageSettings(),
					),
				),
			),
		),
	)
);
?>
                </div>
                <div class="email-content advanced-box white-bg">
<?php
$backendOptions = array();
foreach (array_filter(get_defined_functions()['user'],function($v) { return strpos($v, 'plugin_auth_') === 0; }) as $value) {
	$name = str_replace('plugin_auth_','',$value);
	if (strpos($name, 'disabled') === false) {
		$backendOptions[ucwords(str_replace('_',' ',$name))] = $name;
	} else {
		$backendOptions[$value()] = array(
			'value' => randString(),
			'disabled' => true,
		);
	}
}
ksort($backendOptions);
$emailTemplates = array(
	array(
		'type' => 'inputbox',
		'name' => 'emailTempateLogo',
		'value' => emailTempateLogo,
	),
	array(
		'type' => 'template',
		'title' => 'Password Reset',
        'variables' => array('{user}','{domain}','{fullDomain}','{password}'),
        'subject' => emailTemplateResetPasswordSubject,
		'body' => emailTemplateResetPassword,
        'template' => 'emailTemplateResetPassword',
	),
	array(
		'type' => 'template',
		'title' => 'New Registration',
        'variables' => array('{user}','{domain}','{fullDomain}'),
        'subject' => emailTemplateRegisterUserSubject,
		'body' => emailTemplateRegisterUser,
        'template' => 'emailTemplateRegisterUser',
	),
    array(
		'type' => 'template',
		'title' => 'Invite User',
        'variables' => array('{user}','{domain}','{fullDomain}','{inviteCode}'),
        'subject' => emailTemplateInviteUserSubject,
		'body' => emailTemplateInviteUser,
        'template' => 'emailTemplateInviteUser',
	),
	array(
		'type' => 'templateCustom',
		'title' => emailTemplateCustomOneName,
        'variables' => array('{domain}','{fullDomain}'),
        'subject' => emailTemplateCustomOneSubject,
		'body' => emailTemplateCustomOne,
        'template' => 'emailTemplateCustomOne',
	),
	array(
		'type' => 'templateCustom',
		'title' => emailTemplateCustomTwoName,
        'variables' => array('{domain}','{fullDomain}'),
        'subject' => emailTemplateCustomTwoSubject,
		'body' => emailTemplateCustomTwo,
        'template' => 'emailTemplateCustomTwo',
	),
	array(
		'type' => 'templateCustom',
		'title' => emailTemplateCustomThreeName,
        'variables' => array('{domain}','{fullDomain}'),
        'subject' => emailTemplateCustomThreeSubject,
		'body' => emailTemplateCustomThree,
        'template' => 'emailTemplateCustomThree',
	),
	array(
		'type' => 'templateCustom',
		'title' => emailTemplateCustomFourName,
        'variables' => array('{domain}','{fullDomain}'),
        'subject' => emailTemplateCustomFourSubject,
		'body' => emailTemplateCustomFour,
        'template' => 'emailTemplateCustomFour',
	),
);
echo buildSettings(
	array(
		'title' => 'Advanced Settings',
		'id' => 'advanced_settings',
		'onready' => '$(\'#authType_id\').trigger(\'change\')',
		'tabs' => array(
			array(
				'title' => 'Backend Authentication',
				'id' => 'be_auth',
				'image' => 'images/security.png',
				'fields' => array(
					array(
						'type' => 'select',
						'labelTranslate' => 'AUTHTYPE',
						'name' => 'authType',
						'value' => AUTHTYPE,
						'onchange' => 'if (this.value == \'internal\') { $(\'.be-auth, #authBackend_id, #authBackendCreate_id\').parent().hide(); } else { $(\'#authBackend_id, #authBackendCreate_id\').trigger(\'change\').parent().show(); }if (this.value == \'external\') { alert(\'ATTENTION! Before using this option, Make sure that the ADMIN account that you setup matches at least one username on your external backend.  Otherwide you will lose Admin functionality.  If something messes up, edit config/config.php and change authType to either internal or both.\') } ',
						'options' => array(
							'Organizr' => 'internal',
							(AUTHBACKEND) ? 'Organizr & '.ucwords(AUTHBACKEND) : 'Organizr & Backend' => 'both',
                            (AUTHBACKEND) ? ucwords(AUTHBACKEND)." Only" : "Backend Only" => 'external',
						),
					),
					array(
						'type' => 'select',
						'labelTranslate' => 'AUTHBACKEND',
						'name' => 'authBackend',
						'onchange' => '$(\'.be-auth\').each(function() { $(this).parent().hide(); }); $(\'.be-auth-\'+this.value).each(function() { $(this).parent().show(); });',
						'value' => AUTHBACKEND,
						'options' => $backendOptions,
					),
					array(
						'type' => 'select',
						'labelTranslate' => 'AUTHBACKENDCREATE',
						'name' => 'authBackendCreate',
						'value' => AUTHBACKENDCREATE,
						'options' => array(
							translate('YES_CREATE') => 'true',
							translate('NO_CREATE') => 'false',
						),
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:8181',
						'labelTranslate' => 'AUTHBACKENDHOST',
						'assist' => 'http(s)://hostname:8181 | Ldap(s)://localhost:389 | ftp(s)://localhost:21',
						'name' => 'authBackendHost',
						'class' => 'be-auth be-auth-ftp be-auth-ldap',
						'pattern' => '((?:[hH][tT][tT][pP]|[lL][dD][aA][pP]|[fF][tT][pP])[sS]?):\/\/([\w\.]{1,250})(?::(\d{1,5}))?((?:\/[^?.\s]+))?',
						'value' => AUTHBACKENDHOST,
					),
					array(
						'type' => 'text',
						'placeholder' => 'domain',
						'labelTranslate' => 'AUTHBACKENDDOMAIN',
						'name' => 'authBackendDomain',
						'class' => 'be-auth be-auth-ldap',
						'value' => AUTHBACKENDDOMAIN,
                    ),
                    array(
						'type' => 'text',
						'placeholder' => 'domain & format',
						'labelTranslate' => 'AUTHBACKENDDOMAINFORMAT',
						'name' => 'authBackendDomainFormat',
						'class' => 'be-auth be-auth-ldap',
						'value' => AUTHBACKENDDOMAINFORMAT,
					),
					array(
						'type' => 'text',
						'placeholder' => 'http://hostname:8096/emby',
						'labelTranslate' => 'EMBY_URL',
						'assist' => 'http://hostname:8096 | https://hostname/emby | http://hostname:8096/emby',
						'class' => 'be-auth be-auth-emby_local be-auth-emby_all be-auth-emby_connect',
						'name' => 'embyURL',
						'pattern' => $urlPattern,
						'value' => EMBYURL,
					),
					array(
						'type' => 'text',
						'placeholder' => randString(32),
						'labelTranslate' => 'EMBY_TOKEN',
						'name' => 'embyToken',
						'class' => 'be-auth be-auth-emby_all be-auth-emby_connect',
						'pattern' => '[a-zA-Z0-9]{32}',
						'value' => EMBYTOKEN,
					),
					array(
						'type' => 'text',
						'labelTranslate' => 'PLEX_USERNAME',
						'name' => 'plexUsername',
						'class' => 'be-auth be-auth-plex',
						'value' => PLEXUSERNAME,
					),
					array(
						'type' => 'password',
						'labelTranslate' => 'PLEX_PASSWORD',
						'name' => 'plexPassword',
						'class' => 'be-auth be-auth-plex',
						'value' => (empty(PLEXPASSWORD)?'':randString(20)),
						'autocomplete' => 'new-password',
					),
                    array(
						'type' => 'text',
						'labelTranslate' => 'ORGANIZR_API_KEY',
						'name' => 'organizrAPI',
						'value' => ORGANIZRAPI,
					),
                    array(
                        'type' => 'button',
                        'id' => 'generateAPI',
                        'labelTranslate' => 'GENERATE_API_KEY',
                        'icon' => 'key',
                        'onclick' => 'var code = generateCode(); $(\'#organizrAPI_id\').val(code); $(\'#organizrAPI_id\').attr(\'data-changed\', \'true\');',
                    ),
				),
			),
			array(
				'title' => 'Super Advanced',
				'id' => 'super_advanced',
				'image' => 'images/gear.png',
				'fields' => array(
					array(
						'type' => 'text',
						'placeholder' => '/home/www-data/',
						'labelTranslate' => 'DATABASE_PATH',
						'name' => 'database_Location',
						'value' => DATABASE_LOCATION,
					),
					array(
						'type' => 'select',
						'labelTranslate' => 'SET_TIMEZONE',
						'name' => 'timezone',
						'value' => TIMEZONE,
						'options' => timezoneOptions(),
					),
					array(
						'type' => 'text',
						'labelTranslate' => 'REGISTER_PASSWORD',
						'name' => 'registerPassword',
						'value' => REGISTERPASSWORD,
					),
					array(
						'type' => 'text',
						'labelTranslate' => 'COOKIE_DOMAIN',
						'name' => 'domain',
						'value' => DOMAIN,
					),
					array(
						'type' => 'password',
						'labelTranslate' => 'COOKIE_PASSWORD',
						'name' => 'cookiePassword',
						'value' => (empty(COOKIEPASSWORD)?'':randString(20)),
                        'autocomplete' => 'new-password',
					),
                    array(
						'type' => 'text',
						'labelTranslate' => 'IPINFO_TOKEN',
						'name' => 'ipInfoToken',
						'value' => IPINFOTOKEN,
					),
					array(
						'type' => 'select',
						'labelTranslate' => 'GIT_BRANCH',
						'placeholder' => 'Default: \'master\' - Development: \'develop\' OR \'cero-dev\'',
						'id' => 'git_branch_id',
						'name' => 'git_branch',
						'value' => GIT_BRANCH,
                        'options' => $branchTypes,
					),
					array(
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'GIT_CHECK',
							'name' => 'git_check',
							'value' => GIT_CHECK,
						),
						array(
							'type' => 'button',
							'id' => 'gitForceInstall',
                            'style' => (extension_loaded("ZIP")) ? "" : "display : none",
							'labelTranslate' => 'GIT_FORCE',
							'icon' => 'gear',
							'onclick' => 'if ($(\'#git_branch_id[data-changed]\').length) { alert(\'Branch was altered, save settings first!\') } else { if (confirm(\''.translate('GIT_FORCE_CONFIRM').'\')) { performUpdate(); ajax_request(\'POST\', \'forceBranchInstall\'); } }',
						),
					),
				),
			),
			array(
				'title' => 'Mail Settings',
				'id' => 'mail_settings',
				'image' => 'images/mail.png',
				'fields' => array(
					array(
						'type' => 'text',
						'placeholder' => 'mail.provider.com',
						'labelTranslate' => 'SMTP_HOST',
						'name' => 'smtpHost',
						'pattern' => '([\w\.\-]{1,250})',
						'value' => SMTPHOST,
					),
					array(
						'type' => 'number',
						'placeholder' => 'port # i.e. 465',
						'labelTranslate' => 'SMTP_HOST_PORT',
						'name' => 'smtpHostPort',
						'value' => SMTPHOSTPORT,
					),
					array(
						'type' => 'text',
						'labelTranslate' => 'SMTP_HOST_USERNAME',
						'name' => 'smtpHostUsername',
						'value' => SMTPHOSTUSERNAME,
					),
					array(
						'type' => 'password',
						'labelTranslate' => 'SMTP_HOST_PASSWORD',
						'name' => 'smtpHostPassword',
						'value' => (empty(SMTPHOSTPASSWORD)?'':randString(20)),
                        'autocomplete' => 'new-password',
					),
					array(
						'type' => 'text',
						'labelTranslate' => 'SMTP_HOST_SENDER_NAME',
						'name' => 'smtpHostSenderName',
						'value' => SMTPHOSTSENDERNAME,
					),
					array(
						'type' => 'text',
						'labelTranslate' => 'SMTP_HOST_SENDER_EMAIL',
						'name' => 'smtpHostSenderEmail',
						'value' => SMTPHOSTSENDEREMAIL,
					),
                    array(
						'type' => 'select',
						'labelTranslate' => 'SMTP_HOST_AUTH',
						'name' => 'smtpHostType',
						'value' => SMTPHOSTTYPE,
						'options' => array(
							'ssl' => 'ssl',
                            'tls' => 'tls',
                            'off' => 'false',
						),
					),
					array(
                        array(
							'type' => 'button',
							'labelTranslate' => 'TEST_EMAIL',
							'id' => 'testEmail',
							'icon' => 'flask',
						),
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'SMTP_HOST_AUTH',
							'name' => 'smtpHostAuth',
							'value' => SMTPHOSTAUTH,
						),
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'ENABLE_MAIL',
							'name' => 'enableMail',
							'value' => ENABLEMAIL,
						),
					),
					array(
						'type' => 'custom',
						'html' => '<h2>Custom Mail Options</h2>',
					),
					array(
	                    array(
							'type' => 'custom',
							'html' => buildAccordion($emailTemplates),
						),
	                    array(
							'type' => 'textarea',
							'name' => 'emailTemplateCSS',
							'value' => emailTemplateCSS,
	                        'labelTranslate' => 'EDIT_CUSTOM_CSS',
	                        'placeholder' => 'Please Include <style></style> tags',
							'rows' => 25,
							'style' => 'background: #000; color: #FFF;',
						),
					),
				),
			),
			array(
				'title' => 'Advanced Visual',
				'id' => 'advanced_visual',
				'image' => 'images/paint.png',
				'fields' => array(
					array(
						'type' => 'text',
						'format' => 'text',
						'labelTranslate' => 'INSTALLED_THEME',
						'name' => 'installedTheme',
						'id' => 'installedTheme',
						'class' => 'text-center',
						'placeholder' => (empty(INSTALLEDTHEME)?'No Theme Installed':INSTALLEDTHEME),
						'value' => INSTALLEDTHEME,
						'disabled' => true,
					),
					array(
						'type' => 'text',
						'placeholder' => 'images/organizr.png',
						'labelTranslate' => 'LOADING_ICON_URL',
						'name' => 'loadingIcon',
						'value' => LOADINGICON,
					),
					array(
						'type' => 'text',
						'placeholder' => 'images/organizr.png',
						'labelTranslate' => 'LOGO_URL_TITLE',
						'name' => 'titleLogo',
						'value' => TITLELOGO,
					),
					array(
						'type' => 'select',
						'labelTranslate' => 'NOTIFICATION_TYPE',
						'name' => 'notifyEffect',
						'onchange' => 'parent.notify(\'This is an example popup!\', \'bullhorn\', \'success\', 4000, this.value.split(\'-\')[0], this.value.split(\'-\')[1]);',
						'value' => NOTIFYEFFECT,
						'options' => array(
							'Slide From Top' => 'bar-slidetop',
							'Exploader From Top' => 'bar-exploader',
							'Flip' => 'attached-flip',
							'Bouncy Flip' => 'attached-bouncyflip',
							'Growl Scale' => 'growl-scale',
							'Growl Genie' => 'growl-genie',
							'Growl Jelly' => 'growl-jelly',
							'Growl Slide' => 'growl-slide',
							'Spinning Box' => 'other-boxspinner',
							'Sliding' => 'other-thumbslider',
						),
					),
                    array(
                        array(
							'type' => 'checkbox',
							'labelTranslate' => 'ENABLE_SPLASH_SCREEN',
							'name' => 'splash',
							'value' => SPLASH,
						),
                        array(
    						'type' => $userSelectType,
    						'labelTranslate' => 'MINIMUM_SPLASH_ACCESS',
    						'name' => 'splashAuth',
    						'value' => SPLASHAUTH,
    						'options' => $userTypes,
    					),
                    ),
					array(
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'ENABLE_LOADING_SCREEN',
							'name' => 'loadingScreen',
							'value' => LOADINGSCREEN,
						),
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'ENABLE_SLIMBAR',
							'name' => 'slimBar',
							'value' => SLIMBAR,
						),
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'GRAVATAR',
							'name' => 'gravatar',
							'value' => GRAVATAR,
						),
					),
				),
			),
			array(
				'title' => 'Chat Settings',
				'id' => 'chat_settings',
				'image' => 'images/settings/full-color/png/64px/chat.png',
				'fields' => array(
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'CHAT_AUTH',
							'name' => 'chatAuth',
							'value' => CHATAUTH,
							'options' => $userTypes,
							'disabled' => (!extension_loaded('sqlite3')) ? true : false,
						),
						array(
							'type' => 'checkbox',
							'labelTranslate' => (!extension_loaded('sqlite3')) ? 'SQLITE_NOT_INSTALLED' : 'ENABLE_CHAT',
							'name' => 'chat',
							'value' => CHAT,
							'disabled' => (!extension_loaded('sqlite3')) ? true : false,
						),
						array(
							'type' => 'button',
							'id' => 'deleteChat',
							'labelTranslate' => 'DELETE_CHAT_DATABASE',
							'icon' => 'trash',
							'onclick' => 'ajax_request(\'POST\', \'deleteChat\');',
							'class' => 'btn-warning',
							'disabled' => (!extension_loaded('sqlite3')) ? true : false,
						),
				),
			),
			array(
				'title' => 'Weather Settings',
				'id' => 'weather_settings',
				'image' => 'images/settings/full-color/png/64px/weather.png',
				'fields' => array(
						array(
							'type' => $userSelectType,
							'labelTranslate' => 'WEATHER_AUTH',
							'name' => 'weatherAuth',
							'value' => WEATHERAUTH,
							'options' => $userTypes,
						),
						array(
							'type' => 'checkbox',
							'labelTranslate' => 'ENABLE_WEATHER',
							'name' => 'weather',
							'value' => WEATHER,
						),
				),
			),
      array(
				'title' => 'Backup Settings',
				'id' => 'backup_settings',
				'image' => 'images/backup.png',
				'fields' => array(
					array(
                        array(
							'type' => 'button',
							'labelTranslate' => 'BACKUP_NOW',
							'id' => 'backupNow',
							'icon' => 'database',
                            'style' =>  (extension_loaded("ZIP")) ? "margin-bottom: 5px;" : "display : none",
						),
					),
                    array(
						'type' => 'textarea',
						'labelTranslate' => 'BACKUP_LIST',
						'name' => 'backupList',
						'value' => (extension_loaded("ZIP")) ? implode("\n",getBackups()) : "PLEASE ENABLE PHP ZIP",
						'rows' => 15,
						'style' => 'background: #000; color: #FFF;pointer-events: none',
					),
				),
			),
		),
	)
);
?>
                </div>
                <div class="email-content donate-box white-bg">
                    <div class="email-body">
                        <div class="email-header gray-bg">
                            <button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
                            <h1>Donate To Organizr</h1>
                        </div>
                        <div class="email-inner small-box">
                            <div class="email-inner-section">
                                <div class="small-box fade in" id="donate-org">
                                    <div class="row">
                                        <div class="col-lg-12">

                                                <div class="jumbotron">
                                                    <div class="container">
                                                        <h2><strong>Hey There <em class="gray"><?php echo ucwords($USER->username);?></em>,</strong></h2>
                                                        <br/>
                                                        <small>I had always said that I wouldn't take any donations for my work but some situations have changed in my life.  By no means does anyone need to donate but if you choose to help out and show appreciation I would surely appreciate that very much.  I do all of this for everyone and because I'm happy when i do it :)</small>
                                                        <br/><br/>
                                                        <small>I just want to take this time to thank you for even visiting this section of Organizr.  Just by you clicking into this area makes me happy.  Even the fact that you are still reading this makes me happy.  I bet now you are wondering, why am I even still reading this...  LOL, don't worry, I'm kinda laughing as I am typing this.  Anywho, thank you for reading along and I hope you enjoy the rest of your day.</small>
                                                        <br/><br/>
                                                        <p class="pull-right"><i class="fa fa-heart fa-1x red loop-animation animated pulse" aria-hidden="true"></i> CauseFX</p>
                                                    </div>
                                                </div>

                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6 col-lg-6">
                                            <div class="content-box ultra-widget blue-bg" style="cursor: pointer;" onclick="window.open('https://paypal.me/causefx', '_blank')">
                                                <div class="w-content big-box">
                                                    <div class="w-progress">
                                                        <span class="w-amount">PayPal</span>
                                                        <br>
                                                        <span class="text-uppercase w-name">Donate with PayPal</span>
                                                    </div>
                                                    <span class="w-refresh w-p-icon">
                                                        <span class="fa-stack fa-lg">
                                                            <i class="fa fa-square fa-stack-2x"></i>
                                                            <i class="fa fa-paypal blue fa-stack-1x fa-inverse"></i>
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="content-box ultra-widget green-bg" style="cursor: pointer;" onclick="window.open('https://cash.me/$causefx', '_blank')">
                                                <div class="w-content big-box">
                                                    <div class="w-progress">
                                                        <span class="w-amount">Square</span>
                                                        <br>
                                                        <span class="text-uppercase w-name">Donate with Square Cash</span>
                                                    </div>
                                                    <span class="w-refresh w-p-icon">
                                                        <span class="fa-stack fa-lg">
                                                            <i class="fa fa-square fa-stack-2x"></i>
                                                            <i class="fa fa-dollar green fa-stack-1x fa-inverse"></i>
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="content-box ultra-widget red-bg">
                                                <div class="w-content big-box">
                                                    <div class="w-progress">
                                                        <span class="w-amount">BitCoin</span>
                                                        <br>
                                                        <small class="text-uppercase">1JLWKsSgDDKdnLjPWbnxfQmCxi8uUohzVv</small>
                                                    </div>
                                                    <span class="w-refresh w-p-icon">
                                                        <span class="fa-stack fa-lg">
                                                            <i class="fa fa-square fa-stack-2x"></i>
                                                            <i class="fa fa-btc red fa-stack-1x fa-inverse"></i>
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
										</div>
                                        <div class="col-sm-6 col-lg-6">
                                            <div class="jumbotron">
                                                <div class="container">
                                                    <h2><strong>Want to become an  <em class="gray">ORGANIZR</em> Patreon?</strong></h2>
                                                    <small>By becoming a Patreon, you will get some perks on Discord as well as other things...</small>
                                                    <br/><br/>
                                                    <small>Some of the perks are:</small>
                                                    <br/><br/>
                                                    <ul>
                                                        <li>One on One RDP Sessions</li>
                                                        <li>Help with Custom CSS</li>
                                                        <li>Feature Request Priority</li>
                                                        <li>And more..</li>
                                                    </ul>
                                                    <p class="pull-right"><a class="btn btn-default" target='_blank' href="https://www.patreon.com/organizr"><i class="fa fa-hand-o-right fa-1x red loop-animation animated pulse" aria-hidden="true"></i> Become Patreon</a></p>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="email-content themes-box white-bg">
                    <div class="email-body">
                        <div class="email-header gray-bg">
                            <button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
                            <h1>Themes</h1>
                        </div>
                        <div class="email-inner small-box">
                            <div class="email-inner-section">
                                <div class="small-box fade in" id="layerCakeOrg">
                                    <div class="row">
                                        <div class="col-lg-2">
                                            <div class="content-box profile-sidebar box-shadow">
                                                <img src="images/layercake.png" width="50%" style="margin-top: -10px; margin-bottom: 10px;">
                                                <div class="profile-usermenu">
                                                    <ul class="nav" id="theme-list"></ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-10">
                                            <h1 id="chooseLayer">Choose A Theme To Preview</h1>
                                            <div class="row">
                                                <div id="layerCakePreview" class="col-lg-10"></div>
                                                <div id="layerCakeInfo" class="col-lg-2"></div>
                                        	</div>
                                    	</div>
                                	</div>
                            	</div>
                        	</div>
                    	</div>
                	</div>
				</div>
                <div class="email-content email-box white-bg"><!-- $('.email-box').find('.panel-body').html(); -->
                    <div class="email-body">
                        <div class="email-header gray-bg">
                            <button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
                            <h1>E-Mail Users</h1>
                        </div>
                        <div class="email-inner small-box">
                            <div class="email-inner-section">
                                <div class="small-box fade in">


                                    <div class="mail-header">
                                        <div class="sort-todo">
                                            <button class="btn btn-success btn-labeled waves btn-sm text-uppercase waves-effect waves-float generateEmails">
												<span class="btn-label"><i class="fa fa-users"></i></span><span class="btn-text">Choose Users</span>
											</button>
                                            <button id="selectAllEmail" style="display: none;" class="btn btn-success btn-labeled waves btn-sm text-uppercase waves-effect waves-float">
												<span class="btn-label"><i class="fa fa-users"></i></span><span class="btn-text">Select All</span>
											</button>
											<button id="sendEmail" class="btn btn-success btn-labeled waves btn-sm text-uppercase waves-effect waves-float pull-right">
												<span class="btn-label"><i class="fa fa-paper-plane"></i></span><span class="btn-text">Send</span>
											</button>
											<div class="btn-group">
												<button id="emailCustom" type="button" class="btn waves btn-labeled btn-dark btn-sm text-uppercase waves-effect waves-float dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="btn-label"><i class="fa fa-envelope"></i></span><span class="btn-text">Custom Email Templates</span></button>
												<ul class="dropdown-menu">
													<li class="dropdown-header">Choose a Template Below</li>
													<li><a onclick="customEmail('one');" href="#"><?php echo emailTemplateCustomOneName; ?></a></li>
													<li><a onclick="customEmail('two');" href="#"><?php echo emailTemplateCustomTwoName; ?></a></li>
													<li><a onclick="customEmail('three');" href="#"><?php echo emailTemplateCustomThreeName; ?></a></li>
													<li><a onclick="customEmail('four');" href="#"><?php echo emailTemplateCustomFourName; ?></a></li>
												</ul>
											</div>
                                        </div>
                                        <div style="display: none;"class="form-group" id="emailSelect">
                                        <select multiple="true" size="10" id="email-users" class="form-control"></select>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="form-control material" id="mailTo" placeholder="To">
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="form-control material" id="subject" placeholder="Subject">
                                        </div>
                                    </div>
                                    <div class="summernote"></div>
                            	</div>
                        	</div>
                    	</div>
                	</div>
				</div>
                <div class="email-content speed-box white-bg">
                    <div class="email-body">
                        <div class="email-header gray-bg">
                            <button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
                            <h1>SpeedTest History</h1>
                        </div>
                        <div class="email-inner small-box">
                            <div class="email-inner-section">
                                <div class="small-box fade in" id="speedOrg">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="content-box">
                                                <div class="content-title big-box i-block"></div>
                                                <div class="clearfix"></div>
                                                <div class="big-box">
                                                    <div id="morris-line" class="morris-container"></div>
                                                </div>
                                            </div>
                                        </div>
									</div>
									<?php if(file_exists(DATABASE_LOCATION."speedtest.db")){ ?>
                                    <div id="speedTestTable" class="table-responsive">
                                        <table id="speedLogs" class="datatable display">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $language->translate("DATE");?></th>
                                                    <th><?php echo $language->translate("IP");?></th>
                                                    <th><?php echo $language->translate("DOWNLOAD");?></th>
                                                    <th><?php echo $language->translate("UPLOAD");?></th>
                                                    <th><?php echo $language->translate("PING");?></th>
                                                    <th><?php echo $language->translate("JITTER");?></th>
                                                </tr>
                                            </thead>
                                            <tbody><?php echo speedTestDisplay(speedTestData(),"table");?></tbody>
                                        </table>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="email-content help-box white-bg">
                    <div class="email-body">
                        <div class="email-header gray-bg">
                            <button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
                            <h1>Help & Chat</h1>
                        </div>
                        <div class="email-inner small-box">
                            <div class="email-inner-section">
                                <div class="small-box fade in">

                                    <embed style="height:calc(100vh - 100px);width:calc(100%)" src='https://titanembeds.com/embed/374648602632388610' />

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="email-content info-box white-bg">
                    <div class="email-body">
                        <div class="email-header gray-bg">
                            <button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
                            <h1>About Organizr</h1>
                        </div>
                        <div class="email-inner small-box">
                            <div class="email-inner-section">
                                <div class="small-box fade in" id="about">
                                    <h4><img src="images/organizr-logo-h-d.png" height="50px"></h4>
                                    <p id="version"></p>
                                    <p id="submitFeedback">
                                        <a href='https://reddit.com/r/organizr' target='_blank' type='button' style="background: #AD80FD" class='btn waves btn-labeled btn-success btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-reddit'></i></span>SUBREDDIT</a>
                                        <a href='https://github.com/causefx/Organizr/issues/new' target='_blank' type='button' class='btn waves btn-labeled btn-success btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-github-alt'></i></span><?php echo $language->translate("SUBMIT_ISSUE");?></a>
                                        <a href='https://github.com/causefx/Organizr' target='_blank' type='button' class='btn waves btn-labeled btn-primary btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-github'></i></span><?php echo $language->translate("VIEW_ON_GITHUB");?></a>
                                        <a href='https://discord.gg/XvbT6nz' target='_blank' type='button' class='btn waves btn-labeled btn-dark btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-comments-o'></i></span><?php echo $language->translate("CHAT_WITH_US");?></a>
                                        <button type="button" class="class='btn waves btn-labeled btn-warning btn text-uppercase waves-effect waves-float" data-toggle="modal" data-target=".Help-Me-modal-lg"><span class='btn-label'><i class='fa fa-life-ring'></i></span><?php echo $language->translate("HELP");?></button>
                                        <!--<button id="deleteToggle" type="button" class="class='btn waves btn-labeled btn-danger btn text-uppercase waves-effect waves-float" ><span class='btn-label'><i class='fa fa-trash'></i></span><?php echo $language->translate("DELETE_DATABASE");?></button>-->
                                    </p>

                                    <div class="modal fade Help-Me-modal-lg" tabindex="-1" role="dialog">

                                        <div class="modal-dialog modal-lg" role="document">

                                            <div class="modal-content" style="color: <?php echo $topbartext;?> !important; background: <?php echo $topbar;?> !important;">

                                                <div class="modal-header">

                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>

                                                    <h4 class="modal-title"><?php echo $language->translate("HELP");?>!</h4>

                                                </div>

                                                <div class="modal-body" style="background: <?php echo $sidebar;?> !important;">

                                                    <div style="margin-bottom: 0px;" class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

                                                        <div style="color: <?php echo $topbartext;?> !important; background: <?php echo $topbar;?> !important;" class="panel panel-default">

                                                            <div class="panel-heading" role="tab" id="headingOne">

                                                                <h4 class="panel-title" style="text-decoration: none;" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">

                                                                    <?php echo $language->translate("ADDING_TABS");?>

                                                                </h4>

                                                            </div>

                                                            <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne" aria-expanded="true">

                                                                <div class="panel-body">

                                                                    <p><?php echo $language->translate("START_ADDING_TABS");?></p>

                                                                    <ul>

                                                                        <li><strong><?php echo $language->translate("TAB_URL");?></strong> <?php echo $language->translate("TAB_URL_ABOUT");?></li>
                                                                        <li><strong><?php echo $language->translate("ICON_URL");?></strong> <?php echo $language->translate("ICON_URL_ABOUT");?></li>
                                                                        <li><strong><?php echo $language->translate("DEFAULT");?></strong> <?php echo $language->translate("DEFAULT_ABOUT");?></li>
                                                                        <li><strong><?php echo $language->translate("ACTIVE");?></strong> <?php echo $language->translate("ACTIVE_ABOUT");?></li>
                                                                        <li><strong><?php echo $language->translate("USER");?></strong> <?php echo $language->translate("USER_ABOUT");?></li>
                                                                        <li><strong><?php echo $language->translate("GUEST");?></strong> <?php echo $language->translate("GUEST_ABOUT");?></li>
                                                                        <li><strong><?php echo $language->translate("NO_IFRAME");?></strong> <?php echo $language->translate("NO_IFRAME_ABOUT");?></li>

                                                                    </ul>

                                                                </div>

                                                            </div>

                                                        </div>

                                                        <div style="color: <?php echo $topbartext;?> !important; background: <?php echo $topbar;?> !important;" class="panel panel-default">

                                                            <div class="panel-heading" role="tab" id="headingTwo">

                                                                <h4 class="panel-title" style="text-decoration: none;" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">

                                                                    <?php echo $language->translate("QUICK_ACCESS");?>

                                                                </h4>

                                                            </div>

                                                            <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo" aria-expanded="true">

                                                                <div class="panel-body">

                                                                    <p><?php echo $language->translate("QUICK_ACCESS_ABOUT");?> <mark><?php echo getServerPath(); ?>#Sonarr</mark></p>

                                                                </div>

                                                            </div>

                                                        </div>

                                                        <div style="color: <?php echo $topbartext;?> !important; background: <?php echo $topbar;?> !important;" class="panel panel-default">

                                                            <div class="panel-heading" role="tab" id="headingThree">

                                                                <h4 class="panel-title" style="text-decoration: none;" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="true" aria-controls="collapseThree">

                                                                    <?php echo $language->translate("SIDE_BY_SIDE");?>

                                                                </h4>

                                                            </div>

                                                            <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree" aria-expanded="true">

                                                                <div class="panel-body">

                                                                    <p><?php echo $language->translate("SIDE_BY_SIDE_ABOUT");?></p>

                                                                    <ul>

                                                                        <li><?php echo $language->translate("SIDE_BY_SIDE_INSTRUCTIONS1");?></li>
                                                                        <li><?php echo $language->translate("SIDE_BY_SIDE_INSTRUCTIONS2");?> [<i class='mdi mdi-refresh'></i>]</li>
                                                                        <li><?php echo $language->translate("SIDE_BY_SIDE_INSTRUCTIONS3");?></li>

                                                                    </ul>

                                                                </div>

                                                            </div>

                                                        </div>

                                                        <div style="color: <?php echo $topbartext;?> !important; background: <?php echo $topbar;?> !important;" class="panel panel-default">

                                                            <div class="panel-heading" role="tab" id="headingFour">

                                                                <h4 class="panel-title" style="text-decoration: none;" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseFour" aria-expanded="true" aria-controls="collapseFour">

                                                                    <?php echo $language->translate("KEYBOARD_SHORTCUTS");?>

                                                                </h4>

                                                            </div>

                                                            <div id="collapseFour" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour" aria-expanded="true">

                                                                <div class="panel-body">

                                                                    <p><?php echo $language->translate("KEYBOARD_SHORTCUTS_ABOUT");?></p>

                                                                    <ul>

                                                                        <li><keyboard class="key"><span>S</span></keyboard> + <keyboard class="key"><span>S</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS1");?></li>
                                                                        <li><keyboard class="key"><span>F</span></keyboard> + <keyboard class="key"><span>F</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS6");?></li>
                                                                        <li><keyboard class="key"><span>P</span></keyboard> + <keyboard class="key"><span>P</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS7");?></li>
                                                                        <li><keyboard class="key"><span>M</span></keyboard> + <keyboard class="key"><span>M</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS8");?></li>
                                                                        <li><keyboard class="key wide"><span>Ctrl</span></keyboard> + <keyboard class="key wide"><span>Shift</span></keyboard> + <keyboard class="key"><span>&darr;</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS2");?></li>
                                                                        <li><keyboard class="key wide"><span>Ctrl</span></keyboard> + <keyboard class="key wide"><span>Shift</span></keyboard> + <keyboard class="key"><span>&uarr;</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS3");?></li>
                                                                        <li><keyboard class="key wide"><span>Ctrl</span></keyboard> + <keyboard class="key wide"><span>Shift</span></keyboard> + <keyboard class="key"><span>1</span></keyboard> - <keyboard class="key"><span>9</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS5");?></li>
                                                                        <li><keyboard class="key wide"><span>Esc</span></keyboard> + <keyboard class="key wide"><span>Esc</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS4");?></li>


                                                                    </ul>

                                                                </div>

                                                            </div>

                                                        </div>

                                                        <div style="color: <?php echo $topbartext;?> !important; background: <?php echo $topbar;?> !important;" class="panel panel-default">

                                                            <div class="panel-heading" role="tab" id="headingFive">

                                                                <h4 class="panel-title" style="text-decoration: none;" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseFive" aria-expanded="true" aria-controls="collapseFive">

                                                                    <?php echo $language->translate("TAB_NOT_LOADING");?>

                                                                </h4>

                                                            </div>

                                                            <div id="collapseFive" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFive" aria-expanded="true">

                                                                <div class="panel-body">

                                                                    <p><?php echo $language->translate("TAB_NOT_LOADING_ABOUT");?></p>

                                                                    <?php
                                                                    if(get_browser_name() == "Chrome") : echo get_browser_name() . ": <a href='https://chrome.google.com/webstore/detail/ignore-x-frame-headers/gleekbfjekiniecknbkamfmkohkpodhe' target='_blank'><strong>Ignore X-Frame headers</strong> by Guillaume Ryder</a>";
                                                                    elseif(get_browser_name() == "Firefox") : echo get_browser_name() . ": <a href='https://addons.mozilla.org/en-us/firefox/addon/ignore-x-frame-options/' target='_blank'><strong>Ignore X-Frame headers</strong> by rjhoukema</a>";
                                                                    else : echo "Sorry, currently there is no other alternative for " . get_browser_name(); endif;
                                                                    ?>

                                                                </div>

                                                            </div>

                                                        </div>

                                                        <div style="color: <?php echo $topbartext;?> !important; background: <?php echo $topbar;?> !important;" class="panel panel-default">

                                                            <div class="panel-heading" role="tab" id="headingSix">

                                                                <h4 class="panel-title" style="text-decoration: none;" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseSix" aria-expanded="true" aria-controls="collapseSix">

                                                                    <?php echo $language->translate("USER_ICONS");?>

                                                                </h4>

                                                            </div>

                                                            <div id="collapseSix" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingSix" aria-expanded="true">

                                                                <div class="panel-body">

                                                                    <p><?php echo $language->translate("USER_ICONS_ABOUT");?> <a href="http://gravatar.com" target="_blank">gravatar.com</a></p>

                                                                </div>

                                                            </div>

                                                        </div>

                                                        <div style="color: <?php echo $topbartext;?> !important; background: <?php echo $topbar;?> !important;" class="panel panel-default">

                                                            <div class="panel-heading" role="tab" id="headingSeven">

                                                                <h4 class="panel-title" style="text-decoration: none;" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseSeven" aria-expanded="true" aria-controls="collapseSeven">

                                                                    <?php echo $language->translate("TRANSLATIONS");?>

                                                                </h4>

                                                            </div>

                                                            <div id="collapseSeven" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingSeven" aria-expanded="true">

                                                                <div class="panel-body">

                                                                    <p><?php echo $language->translate("TRANSLATIONS_ABOUT");?> <a href="https://github.com/causefx/Organizr/tree/develop/lang" target="_blank">Github Develop Branch</a></p>

                                                                </div>

                                                            </div>

                                                        </div>

                                                    </div>

                                                </div>

                                                <div class="modal-footer">

													<button type="button" class="btn special" style="background: transparent !important;color: transparent !important;">Special</button>
                                                    <button type="button" class="btn btn-default waves" data-dismiss="modal"><?php echo $language->translate("CLOSE");?></button>

                                                </div>

                                            </div>

                                        </div>

                                    </div>
                                    <p id="whatsnew"></p>
                                    <p id="downloadnow"></p>
                                    <div id="deleteDiv" style="display: none;" class="panel panel-danger">
                                        <div class="panel-heading">
                                            <h3 class="panel-title"><?php echo $language->translate("DELETE_DATABASE");?></h3>
                                        </div>
                                        <div class="panel-body">
                                            <div class="">
                                                <p><?php echo $language->translate("DELETE_WARNING");?></p>
                                                <form id="deletedb" method="post" onsubmit="ajax_request('POST', 'deleteDB'); return false;">
                                                    <button class="btn waves btn-labeled btn-danger pull-right text-uppercase waves-effect waves-float" type="submit">
                                                        <span class="btn-label"><i class="fa fa-trash"></i></span><?php echo $language->translate("DELETE_DATABASE");?>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="timeline-container">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <ul class="cbp_tmtimeline" id="versionHistory">
                                                </ul>
                                                <div class="btn-group-sm btn-group btn-group-justified">
                                                    <div id="loadMore" class="btn-group" role="group">
                                                        <button type="button" class="btn waves btn-primary waves-effect waves-float text-uppercase"><?php echo $language->translate("SHOW_MORE");?></button>
                                                    </div>
                                                    <div id="showLess" class="btn-group" role="group">
                                                        <button type="button" class="btn waves btn-warning waves-effect waves-float text-uppercase"><?php echo $language->translate("SHOW_LESS");?></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="email-content users-box white-bg">
                    <div class="email-body">
                        <div class="email-header gray-bg">
                            <button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
                            <h1>Users Management</h1>
                        </div>
                        <div class="email-inner small-box">
                            <div class="email-inner-section">
                                <div class="small-box fade in" id="useredit">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="small-box">
                                                <form class="content-form form-inline" name="new user registration" id="registration" action="" method="POST">
                                                    <input type="hidden" name="op" value="register"/>
                                                    <input type="hidden" name="sha1" value=""/>
                                                    <input type="hidden" name="settings" value="true"/>

                                                    <div class="form-group">

                                                        <input type="text" class="form-control material" name="username" placeholder="<?php echo $language->translate("USERNAME");?>" autocorrect="off" autocapitalize="off" value="">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="email" class="form-control material" name="email" placeholder="<?php echo $language->translate("EMAIL");?>">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="password" class="form-control material" name="password1" placeholder="<?php echo $language->translate("PASSWORD");?>">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="password" class="form-control material" name="password2" placeholder="<?php echo $language->translate("PASSWORD_AGAIN");?>">

                                                    </div>
                                                    <button type="submit" onclick="User.processRegistration()" class="btn waves btn-labeled btn-primary btn btn-sm text-uppercase waves-effect waves-float promoteUser">

                                                        <span class="btn-label"><i class="fa fa-user-plus"></i></span><?php echo $language->translate("CREATE_USER");?>

                                                    </button>

                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="small-box">
                                        <form class="content-form form-inline" name="unregister" id="unregister" action="" method="POST">

                                            <p id="inputUsername"></p>

                                            <div class="table-responsive">

                                                <table class="table table-striped">

                                                    <thead>

                                                        <tr>

                                                            <th>#</th>

                                                            <th><?php echo $language->translate("USERNAME");?></th>
                                                            <th><?php echo $language->translate("EMAIL");?></th>

                                                            <th><?php echo $language->translate("LOGIN_STATUS");?></th>

                                                            <th><?php echo $language->translate("LAST_SEEN");?></th>

                                                            <th><?php echo $language->translate("USER_GROUP");?></th>

                                                            <th><?php echo $language->translate("USER_ACTIONS");?></th>

                                                        </tr>

                                                    </thead>

                                                    <tbody>

                                                        <?php $countUsers = 1;
                                                        foreach($gotUsers as $row) :
                                                        if($row['role'] == "admin" && $countUsers == 1) :
                                                            $userColor = "red";
                                                            $disableAction = "disabled=\"disabled\"";
                                                        else :
                                                            $userColor = "blue";
                                                            $disableAction = "";
                                                        endif;
                                                        if($row['active'] == "true") :
                                                            $userActive = $language->translate("LOGGED_IN");
                                                            $userActiveColor = "primary";
                                                        else :
                                                            $userActive = $language->translate("LOGGED_OUT");
                                                            $userActiveColor = "danger";
                                                        endif;
                                                        $userpic = md5( strtolower( trim( $row['email'] ) ) );
                                                        if(!empty($row["last"])) :
                                                           $lastActive = date("Y-m-d H:i", intval($row["last"]));
                                                        else :
                                                            $lastActive = "";
                                                        endif;
                                                        ?>

                                                        <tr id="<?=$row['username'];?>">

                                                            <th scope="row"><?=$countUsers;?></th>

                                                            <td><?php if(GRAVATAR == "true") : ?><i class="userpic"><img src="https://www.gravatar.com/avatar/<?=$userpic;?>?s=25&d=mm" class="img-circle"></i> &nbsp; <?php endif; ?><?=$row['username'];?></td>
                                                            <td><input type="text" class="form-control material newemail" name="newemail" value="<?=$row['email'];?>">
                                                                <button style="display: none" class="btn btn-success btn-sm waves editUserEmail"><i class="fa fa-check"></i></button>
                                                                <button style="display: none" type="button" class="btn btn-danger btn-sm waves closeEditUserEmail"><i class="fa fa-close"></i></button>
                                                            </td>

                                                            <td><span class="label label-<?=$userActiveColor;?>"><?=$userActive;?></span></td>

                                                            <td><?=$lastActive;?></td>

                                                            <td><span class="userRole text-uppercase <?=$userColor;?>"><?=$row['role'];?></span></td>

                                                            <td id="<?=$row['username'];?>">

                                                                <button <?=$disableAction;?> class="btn waves btn-labeled btn-danger btn btn-sm text-uppercase waves-effect waves-float deleteUser">

                                                                    <span class="btn-label"><i class="fa fa-user-times"></i></span><?php echo $language->translate("DELETE");?>

                                                                </button>
                                                                <?php if ($row['role'] == "user") : ?>
                                                                <button class="btn waves btn-labeled btn-success btn btn-sm text-uppercase waves-effect waves-float promoteUser">

                                                                    <span class="btn-label"><i class="fa fa-arrow-up"></i></span><?php echo $language->translate("PROMOTE");?>

                                                                </button>
                                                                <?php endif; ?>
                                                                <?php if ($row['role'] == "admin") : ?>
                                                                <button <?=$disableAction;?> class="btn waves btn-labeled btn-warning btn btn-sm text-uppercase waves-effect waves-float demoteUser">

                                                                    <span class="btn-label"><i class="fa fa-arrow-down"></i></span><?php echo $language->translate("DEMOTE");?>

                                                                </button>
                                                                <?php endif; ?>

                                                            </td>

                                                        </tr>

                                                        <?php $countUsers++; endforeach; ?>

                                                    </tbody>

                                                </table>

                                            </div>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				<div class="email-content invites-box white-bg">
                    <div class="email-body">
                        <div class="email-header gray-bg">
                            <button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
                            <h1>Invite Management</h1>
                        </div>
                        <div class="email-inner small-box">
                            <div class="email-inner-section">
                                <div class="small-box fade in" id="useredit">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="small-box">
                                                <form class="content-form form-inline" name="inviteNewUser" id="inviteNewUser" action="" method="POST">
                                                    <input type="hidden" name="op" value="invite"/>
                                                    <input type="hidden" name="server" value="plex"/>

                                                    <div class="form-group">

                                                        <input type="text" class="form-control material" name="username" placeholder="<?php echo $language->translate("USERNAME_NAME");?>" autocorrect="off" autocapitalize="off" value="">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="email" class="form-control material" name="email" placeholder="<?php echo $language->translate("EMAIL");?>" required>

                                                    </div>

                                                    <button type="submit" class="btn waves btn-labeled btn-primary btn btn-sm text-uppercase waves-effect waves-float">

                                                        <span class="btn-label"><i class="fa fa-user-plus"></i></span><?php echo $language->translate("SEND_INVITE");?>

                                                    </button>

                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="small-box">

										<form class="content-form form-inline" name="deleteInviteForm" id="deleteInviteForm" action="" method="POST">

											<p id="inputInvite"></p>

                                            <div class="table-responsive">

                                                <table class="table table-striped">

                                                    <thead>

                                                        <tr>

                                                            <th>#</th>

                                                            <th><?php echo $language->translate("USERNAME");?></th>
                                                            <th><?php echo $language->translate("EMAIL");?></th>
                                                            <th><?php echo $language->translate("INVITE_CODE");?></th>
                                                            <th><?php echo $language->translate("DATE_SENT");?></th>
                                                            <th><?php echo $language->translate("DATE_USED");?></th>
                                                            <th><?php echo $language->translate("USED_BY");?></th>
                                                            <th><?php echo $language->translate("IP_ADDRESS");?></th>
                                                            <th><?php echo $language->translate("VALID");?></th>
                                                            <th><?php echo $language->translate("DELETE");?></th>

                                                        </tr>

                                                    </thead>

                                                    <tbody><!-- onsubmit="return false;" -->


                                                        <?php
                                                        foreach($gotInvites as $row) :
															$validColor = ($row['valid'] == "Yes" ? "primary" : "danger");
															$inviteUser = ($row['username'] != "" ? $row['username'] : "N/A");
															$dateInviteUsed = ($row['dateused'] != "" ? $row['dateused'] : "Not Used");
															$ipUsed = ($row['ip'] != "" ? $row['ip'] : "Not Used");
															$usedBy = ($row['usedby'] != "" ? $row['usedby'] : "Not Used");

                                                        ?>

															<tr id="<?=$row['id'];?>">

																<th scope="row"><?=$row['id'];?></th>

																<td><?=$inviteUser;?></td>
																<td><?=$row['email'];?></td>

																<td><span style="font-size: 100%;" class="label label-<?=$validColor;?>"><?=$row['code'];?></span></td>

																<td><?=$row['date'];?></td>

																<td><?=$dateInviteUsed;?></td>
																<td><?=$usedBy;?></td>
																<td style="cursor: pointer" class="ipInfo"><?=$ipUsed;?></td>

																<td><span style="font-size: 100%;" class="label label-<?=$validColor;?>"><?=$row['valid'];?></span></td>

																<td id="<?=$row['id'];?>">
																	<button class="btn waves btn-labeled btn-danger btn btn-sm text-uppercase waves-effect waves-float deleteInvite">

																		<span class="btn-label"><i class="fa fa-trash"></i></span><?php echo $language->translate("DELETE");?>

																	</button>
																</td>

															</tr>

                                                        <?php endforeach; ?>


                                                    </tbody>

                                                </table>

                                            </div>

										</form>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="email-content logs-box white-bg">
                    <div class="email-body">
                        <div class="email-header gray-bg">
                            <button type="button" class="btn btn-danger btn-sm waves close-button"><i class="fa fa-close"></i></button>
                            <h1>Logs</h1>
                        </div>
                        <div class="email-inner small-box">
                            <div class="email-inner-section">
                                <div class="small-box" id="loginlog">
                                    <div>
                                        <?php if(file_exists(DATABASE_LOCATION."org.log")){ ?>
                                        <button id="viewOrgLogs" class="btn waves btn-labeled gray-bg text-uppercase waves-effect waves-float" type="button"><span class="btn-label"><i class="fa fa-terminal"></i></span>Organizr Log </button>
                                        <?php } if(file_exists(FAIL_LOG)){ ?>
                                        <button id="viewLoginLogs" class="btn waves btn-labeled grayish-blue-bg text-uppercase waves-effect waves-float" type="button" style="display: none"><span class="btn-label"><i class="fa fa-user"></i></span>Login Log </button>
                                        <?php } ?>
                                    </div>

                                    <?php if(file_exists(DATABASE_LOCATION."org.log")){ ?>
                                    <div id="orgLogTable" class="table-responsive" style="display: none">
                                        <table id="orgLogs" class="datatable display">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $language->translate("DATE");?></th>
                                                    <th><?php echo $language->translate("STATUS");?></th>
                                                    <th><?php echo $language->translate("TYPE");?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                 <?php readLog(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php } ?>

                                    <div id="loginTable" class="table-responsive">

                                        <?php if(file_exists(FAIL_LOG)){ ?>

                                        <div id="loginStats">

                                            <div class="ultra-widget">

                                                <div class="w-progress">

                                                    <span id="goodCount" class="w-amount green"></span>
                                                    <span id="badCount" class="w-amount red pull-right">3</span>

                                                    <br>

                                                    <span class="text-uppercase w-name"><?php echo $language->translate("GOOD_LOGINS");?></span>
                                                    <span class="text-uppercase w-name pull-right"><?php echo $language->translate("BAD_LOGINS");?></span>

                                                </div>

                                                <div class="progress progress-bar-sm zero-m">

                                                    <div id="goodPercent" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%"></div>

                                                    <div id="badPercent" class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="width: 80%"></div>

                                                </div>

                                                <div class="w-status clearfix">

                                                    <div id="goodTitle" class="w-status-title pull-left text-uppercase">20%</div>

                                                    <div id="badTitle" class="w-status-number pull-right text-uppercase">80%</div>

                                                </div>

                                            </div>

                                        </div>

                                        <table id="datatable" class="datatable display">

                                            <thead>

                                                <tr>

                                                    <th><?php echo $language->translate("DATE");?></th>

                                                    <th><?php echo $language->translate("USERNAME");?></th>

                                                    <th><?php echo $language->translate("IP_ADDRESS");?></th>

                                                    <th><?php echo $language->translate("TYPE");?></th>

                                                </tr>

                                            </thead>

                                            <tbody>

                                                <?php

                                                    $getFailLog = str_replace("\r\ndate", "date", file_get_contents(FAIL_LOG));
                                                    $gotFailLog = json_decode($getFailLog, true);
                                                    $goodLogin = 0;
                                                    $badLogin = 0;

                                                    function getColor($colorTest){

                                                        if($colorTest == "bad_auth") :

                                                            $gotColorTest = "danger";

                                                        elseif($colorTest == "good_auth") :

                                                            $gotColorTest = "primary";

                                                        endif;

                                                        echo $gotColorTest;

                                                    }

                                                    foreach (array_reverse($gotFailLog["auth"]) as $key => $val) :

                                                    if($val["auth_type"] == "bad_auth") : $badLogin++; elseif($val["auth_type"] == "good_auth") : $goodLogin++; endif;
                                                ?>

                                                <tr>

                                                    <td><?=$val["date"];?></td>

                                                    <td><?=$val["username"];?></td>

                                                    <td style="cursor: pointer" class="ipInfo"><?=$val["ip"];?></td>

                                                    <td><span class="label label-<?php getColor($val["auth_type"]);?>"><?=$val["auth_type"];?></span></td>

                                                </tr>

                                                <?php endforeach; ?>

                                            </tbody>

                                        </table>

                                        <?php
                                        $totalLogin = $goodLogin + $badLogin;
                                        $goodPercent = round(($goodLogin / $totalLogin) * 100);
                                        $badPercent = round(($badLogin / $totalLogin) * 100);

                                        };

                                        if(!file_exists(FAIL_LOG)){

                                            echo $language->translate("NOTHING_LOG");

                                        }

                                        ?>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--End Content-->
            <!-- Modal for IP -->
            <div id="ipModal" class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="ipIp">Modal title</h4>
                        </div>
                        <div class="modal-body">
                            <h3>Hostname: <small id="ipHostname"></small></h3>
                            <h3>Location: <small id="ipLocation"></small></h3>
                            <h3>Org: <small id="ipOrg"></small></h3>
                            <h3>City: <small id="ipCity"></small></h3>
                            <h3>Region: <small id="ipRegion"></small></h3>
                            <h3>Country: <small id="ipCountry"></small></h3>
                            <h3>Phone: <small id="ipPhone"></small></h3>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default waves" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END IP Modal -->
             <!-- Modal for Plex Token -->
            <div id="plexModal" class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title"><?php echo translate("GET_PLEX_TOKEN"); ?></h4>
                        </div>
                        <div class="modal-body">
                            <div style="display:none" id="plexError" class=""></div>
                            <input class="form-control material" placeholder="<?php echo translate("USERNAME"); ?>" type="text" name="plex_username" id="plex_username" value="<?php echo PLEXUSERNAME;?>">
                            <input class="form-control material" placeholder="<?php echo translate("PASSWORD"); ?>" type="password" name="plex_password" id="plex_password" value="<?php echo PLEXPASSWORD;?>">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default waves" data-dismiss="modal"><?php echo translate("CLOSE"); ?></button>
                            <button id="getPlexToken" type="button" class="btn btn-success waves waves-effect waves-float"><?php echo translate("GET_PLEX_TOKEN"); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END IP Modal -->

        </div>
		 <?php if(isset($_POST['op'])) : ?>
        <script>

            parent.notify("<?php echo printArray($USER->info_log); ?>","info-circle","notice","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");

            <?php if(!empty($USER->error_log)) : ?>

            parent.notify("<?php echo printArray($USER->error_log); ?>","exclamation-circle ","error","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");

            <?php endif; ?>

        </script>
        <?php endif; ?>

		<script>
		lazyload();
			<?php echo speedTestDisplay(speedTestData(),"graph");?>
			$(".settingsMenu").click(function() {
				$(".settingsMenu").removeClass("settingsMenuActive");
				$(this).addClass("settingsMenuActive");
				$(".settingsMenu").addClass("settingsMenuInactive");
				$(this).removeClass("settingsMenuInactive");
			})
			$(".special").click(function() {
                swal({
					title: "Hmmm What is This?",
					text: '<p><img src="images/settings/not-so-hidden.gif"></p>',
					html: true,
					confirmButtonColor: "#63A8EB"
				});
                console.log('hmmmmm, what the hell is this section?');
            })
            $(".generateEmails").click(function() {
                <?php if(PLEXURL != ''){
                    echo 'var backend = "plex";';
                }elseif(EMBYURL != ''){
                    echo 'var backend = "emby";';
                }else{
                    echo 'var backend = "org";';
                } ?>
                $('.generateEmails').text("Loading...");
                ajax_request('POST', 'get-emails', {type : backend}).done(function(data){
                    console.log('start');
                    $('#email-users').html(data);
                    $('#emailSelect').show();
                    $('.generateEmails').hide();
                    $('#selectAllEmail').show();
                });
            });
            $(".submitTabBtn").click(function() {
                $("#submitTabs").submit();
            });
            $(function() {
                /*$("#email-users").niceScroll({
                    cursorwidth: "12px",
                    railpadding: {top:0,right:0,left:0,bottom:0},
                    scrollspeed: 30,
                    mousescrollstep: 60,
                    grabcursorenabled: false,
                    autohidemode: false
                });*/
                $('#email-users').slimScroll({
                    width: '100%',
                    railVisible: true,
                    alwaysVisible: true,
                    allowPageScroll: true
                });
            });

            $("#email-users").on('change click', function (e) {
                var selected = $("#email-users").val();
                $('#mailTo').val(selected);
                console.log(selected);
            });
            $("#sendEmail").click(function() {
                var to = $('#mailTo').val();
                var subject = $('#subject').val();
                var message = $('.email-box').find('.panel-body').html();
                console.log(to);
                console.log(subject);
                console.log(message);
                ajax_request('POST', 'mass-email', {
                    emailto: to,
                    emailsubject: subject,
                    emailmessage: message
                });

            })
            $('#selectAllEmail').click(function() {
                $('#email-users option').prop('selected', true);
                var selected = $("#email-users").val();
                $('#mailTo').val(selected);
                console.log(selected);
            });
            //IP INFO
            $(".ipInfo").click(function(){
                $.getJSON("https://ipinfo.io/"+$(this).text()+"/?token=<?php echo IPINFOTOKEN;?>", function (response) {
                    $('#ipModal').modal('show');
                    $('#ipIp').text("IP Info for: "+response.ip);
                    $('#ipHostname').text(response.hostname);
                    $('#ipLocation').text(response.loc);
                    $('#ipOrg').text(response.org);
                    $('#ipCity').text(response.city);
                    $('#ipRegion').text(response.region);
                    $('#ipCountry').text(response.country);
                    $('#ipPhone').text(response.phone);
                    console.log(response);
                });
            });
            // Plex.tv auth token fetch
            $("#openPlexModal").click(function() {
                $('#plexModal').modal('show');
            });
            $("#getPlexToken").click(function() {
                $('#plexError').show();
                $('#plexError').addClass("well well-sm yellow-bg");
                $('#plexError').text("Grabbing Token");
                var plex_username = $("#plex_username").val().trim();
                var plex_password = $("#plex_password").val().trim();
                if ((plex_password !== '') && (plex_password !== '')) {
                    $.ajax({
                        type: 'POST',
                        headers: {
                            'X-Plex-Product':'Organizr',
                            'X-Plex-Version':'1.0',
                            'X-Plex-Client-Identifier':'01010101-10101010'
                        },
                        url: 'https://plex.tv/users/sign_in.json',
                        data: {
                            'user[login]': plex_username,
                            'user[password]': plex_password,
                            force: true
                        },
                        cache: false,
                        async: true,
                        complete: function(xhr, status) {
                            var result = $.parseJSON(xhr.responseText);
                            if (xhr.status === 201) {
                                $('#plexError').removeClass();
                                $('#plexError').addClass("well well-sm green-bg");
                                $('#plexError').show();
                                $('#plexError').text(xhr.statusText);
                                $("#plexToken_id").val(result.user.authToken);
                                $("#plexToken_id").attr('data-changed', 'true');
                                $('#plexModal').modal('hide');
                            } else {
                                $('#plexError').removeClass();
                                $('#plexError').addClass("well well-sm red-bg");
                                $('#plexError').show();
                                $('#plexError').text(xhr.statusText);
                            }
                        }
                    });
                } else {
                    $('#plexError').text("Enter Username and Password");
                }
            });
            //Generate API
            function generateCode() {
                var code = "";
                var possible = "abcdefghijklmnopqrstuvwxyz0123456789";

                for (var i = 0; i < 20; i++)
                    code += possible.charAt(Math.floor(Math.random() * possible.length));

                return code;
            }
			function performUpdate(){
				$('#updateStatus').show();
				setTimeout(function(){
					$('#updateStatusBar').attr("style", "width: 1%");
					setTimeout(function(){
						$('#updateStatusBar').attr("style", "width: 20%");
						setTimeout(function(){
							$('#updateStatusBar').attr("style", "width: 35%");
							setTimeout(function(){
								$('#updateStatusBar').attr("style", "width: 50%");
								setTimeout(function(){
									$('#updateStatusBar').attr("style", "width: 65%");
									setTimeout(function(){
										$('#updateStatusBar').attr("style", "width: 80%");
										setTimeout(function(){
											$('#updateStatusBar').attr("style", "width: 95%");
											setTimeout(function(){
												$('#updateStatusBar').attr("style", "width: 100%");
											}, 4000);
										}, 3500);
									}, 3000);
								}, 2500);
							}, 2000);
						}, 1500);
					}, 1000);
				}, 100);
			}


          	$(function () {
				//Data Tables
				$('.datatable').DataTable({
					displayLength: 10,
					dom: 'T<"clear">lfrtip',
					responsive: true,
					"order": [[ 0, 'desc' ]],
					"language": {
						"info": "<?php echo explosion($language->translate('SHOW_ENTRY_CURRENT'), 0);?> _START_ <?php echo explosion($language->translate('SHOW_ENTRY_CURRENT'), 1);?> _END_ <?php echo explosion($language->translate('SHOW_ENTRY_CURRENT'), 2);?> _TOTAL_ <?php echo explosion($language->translate('SHOW_ENTRY_CURRENT'), 3);?>",
						"infoEmpty": "<?php echo $language->translate('NO_ENTRIES');?>",
						"infoFiltered": "<?php echo explosion($language->translate('FILTERED'), 0);?> _MAX_ <?php echo explosion($language->translate('FILTERED'), 1);?>",
						"lengthMenu": "<?php echo $language->translate('SHOW');?> _MENU_ <?php echo $language->translate('ENTRIES');?>",
						"search": "",
						"searchPlaceholder": "<?php echo $language->translate('SEARCH');?>",
						"searchClass": "<?php echo $language->translate('SEARCH');?>",
						"zeroRecords": "<?php echo $language->translate('NO_MATCHING');?>",
						"paginate": {
							"next": "<?php echo $language->translate('NEXT');?>",
							"previous": "<?php echo $language->translate('PREVIOUS');?>",
						}
					}
				});
        	});
        </script>
        <script>
            (function($) {
                function startTrigger(e,data) {
                    var $elem = $(this);
                    $elem.data('mouseheld_timeout', setTimeout(function() {
                        $elem.trigger('mouseheld');
                    }, e.data));
                }

                function stopTrigger() {
                    var $elem = $(this);
                    clearTimeout($elem.data('mouseheld_timeout'));
                }

                var mouseheld = $.event.special.mouseheld = {
                    setup: function(data) {
                        var $this = $(this);
                        $this.bind('mousedown', +data || mouseheld.time, startTrigger);
                        $this.bind('mouseleave mouseup', stopTrigger);
                    },
                    teardown: function() {
                        var $this = $(this);
                        $this.unbind('mousedown', startTrigger);
                        $this.unbind('mouseleave mouseup', stopTrigger);
                    },
                    time: 200 // default to 750ms
                };
            })(jQuery);

            $(function () {

                $('.summernote').summernote({
                    height: 120,
                    codemirror: { // codemirror options
						mode: 'text/html',
						htmlMode: true,
						lineNumbers: true,
						theme: 'monokai'
					}
				});

                // summernote.change
                $('.summernote').on('summernote.change', function(we, contents, $editable) {
                    $(this).attr('data-changed', 'true');
                });


                //$(".todo ul").sortable();
                $(".todo ul").sortable({
                    'opacity': 0.9,
					//'placeholder':    "sort-placeholder",
					//'forcePlaceholderSize': true,
                });

                $("#submitTabs").on('submit', function (e) {
                 console.log('disabled this func')
                 return false;

                });

                $('#apply').on('click touchstart', function(){

                window.parent.location.reload();

                });

            });

        </script>

        <script>
            $("#iconHide").click(function(){

                $( "div[class^='jFiler jFiler-theme-dragdropbox']" ).toggle();
            });
            $("#iconAll").click(function(){

                $( "div[id^='viewAllIcons']" ).toggle();
            });
            $("#deleteToggle").click(function(){

                $( "#deleteDiv" ).toggle();
            });
			$(".deleteInvite").click(function(){

                var parent_id = $(this).parent().attr('id');
                editUsername = $('#deleteInviteForm').find('#inputInvite');
                $(editUsername).html('<input type="hidden" name="op" value="deleteinvite"/><input type="hidden" name="id"value="' + parent_id + '" />');
            });
            $(".deleteUser").click(function(){

                var parent_id = $(this).parent().attr('id');
                editUsername = $('#unregister').find('#inputUsername');
                $(editUsername).html('<input type="hidden" name="op" value="unregister"/><input type="hidden" name="username"value="' + parent_id + '" />');
            });
            $(".newemail").click(function(){
                $(".editUserEmail").hide();
                $(".closeEditUserEmail").hide();
				$(this).parent().find('.editUserEmail').show();
                $(this).parent().find('.closeEditUserEmail').show();
            });
            $(".closeEditUserEmail").click(function(){
                $(".editUserEmail").hide();
                $(".closeEditUserEmail").hide();
            });
            $(".editUserEmail").click(function(){

                var parent_ids = $(this).parent().parent().attr('id');
                newemail = $(this).parent().parent().find('input[name=newemail]').val();
                role = $(this).parent().parent().find('.userRole').text();
                editUsername = $('#unregister').find('#inputUsername');
                console.log('user: '+parent_ids+' email: '+newemail+' role: '+role);
                $(editUsername).html('<input type="hidden" name="op" value="update"/><input type="hidden" name="email" value="'+newemail+'"/><input type="hidden" name="role" value="'+role+'"/><input type="hidden" name="username"value="' + parent_ids + '" />');
            });
            $(".promoteUser").click(function(){

                var parent_ids = $(this).parent().attr('id');
                editUsername = $('#unregister').find('#inputUsername');
                $(editUsername).html('<input type="hidden" name="op" value="update"/><input type="hidden" name="role" value="admin"/><input type="hidden" name="username"value="' + parent_ids + '" />');
            });
            $(".demoteUser").click(function(){

                var parent_idz = $(this).parent().attr('id');
                editUsername = $('#unregister').find('#inputUsername');
                $(editUsername).html('<input type="hidden" name="op" value="update"/><input type="hidden" name="role" value="user"/><input type="hidden" name="username"value="' + parent_idz + '" />');
            });
            $("#viewOrgLogs, #viewLoginLogs").click(function(){
                $('#orgLogTable').toggle();
                $('#loginTable').toggle();
                $('#viewOrgLogs').toggle();
                $('#viewLoginLogs').toggle();
            });
            $('#showLess').hide();
            $('#loadMore').click(function () {
                x= (x+5 <= size_li) ? x+5 : size_li;

                $('#versionHistory li:lt('+x+')').show();
                $('#showLess').show();
                if(x == size_li){
                    $('#loadMore').hide();
                }

            });

            $('#showLess').click(function () {

                $('#versionHistory li').not(':lt(2)').hide();
                $('#loadMore').show();
                $('#showLess').hide();

            });
            $("li[class^='list-group-item']").bind('mouseheld', function(e) {

                $(this).find("span[class^='fa fa-hand-paper-o']").attr("class", "fa fa-hand-grab-o");
                $(this).addClass("dragging");
                $(this).find("div[class^='action-btns tabIconView']").addClass("animated swing");
                $(this).mouseup(function() {
                    $(this).find("span[class^='fa fa-hand-grab-o']").attr("class", "fa fa-hand-paper-o");
                    $(this).removeClass("dragging");
                    $(this).find("div[class^='action-btns tabIconView']").removeClass("animated swing");
                });
            });
            function copyToClipboard(elem) {
                  // create hidden text element, if it doesn't already exist
                var targetId = "_hiddenCopyText_";
                var isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
                var origSelectionStart, origSelectionEnd;
                if (isInput) {
                    // can just use the original source element for the selection and copy
                    target = elem;
                    origSelectionStart = elem.selectionStart;
                    origSelectionEnd = elem.selectionEnd;
                } else {
                    // must use a temporary form element for the selection and copy
                    target = document.getElementById(targetId);
                    if (!target) {
                        var target = document.createElement("textarea");
                        target.style.position = "absolute";
                        target.style.left = "-9999px";
                        target.style.top = "0";
                        target.id = targetId;
                        document.body.appendChild(target);
                    }
                    target.textContent = elem.textContent;
                }
                // select the content
                var currentFocus = document.activeElement;
                target.focus();
                target.setSelectionRange(0, target.value.length);

                // copy the selection
                var succeed;
                try {
                      succeed = document.execCommand("copy");
                } catch(e) {
                    succeed = false;
                }
                // restore original focus
                if (currentFocus && typeof currentFocus.focus === "function") {
                    //currentFocus.focus();
                }

                if (isInput) {
                    // restore prior selection
                    elem.setSelectionRange(origSelectionStart, origSelectionEnd);
                } else {
                    // clear temporary content
                    target.textContent = "";
                }
                return succeed;
            }
            $("img[class^='allIcons']").click(function(){

                $("textarea[id^='copyTarget']").val($(this).attr("src"));

                copyToClipboard(document.getElementById("copyTarget"));
                parent.notify("<?php echo $language->translate('ICON_COPY');?>","clipboard","success","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");
                $( "div[id^='viewAllIcons']" ).toggle();
            });
            $('body').on('click', 'b.allIcons', function() {

                $("textarea[id^='copyTarget2']").val($(this).attr("title"));

                copyToClipboard(document.getElementById("copyTarget2"));
                parent.notify("<?php echo $language->translate('ICON_COPY');?>","clipboard","success","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");
                $( "div[class^='jFiler jFiler-theme-dragdropbox']" ).hide();
            });
        </script>
        <script>
            //Backup
            $('#backupNow').on('click', function () {
                console.log("starting backup now");
                ajax_request('POST', 'backup-now');
                setTimeout(function(){
                    ajax_request('GET', 'get-backups').done(function(data){
                        $('#backupList_id').html(data);
                        $('#backupList_id').addClass('animated pulse');
                    });
                    console.log("ajax backup done")
                }, 500);
                ;
            });
            //TestEmail
            function isUpperCase(str) {
                return str === str.toUpperCase();
            }
            $('#smtpHostAuth_id').change(function() {
                if($('#smtpHostAuth_id').attr("data-value") == "true"){
                    $('#smtpHostAuth_id').attr("data-value", "false");
                }else{
                    $('#smtpHostAuth_id').attr("data-value", "true");
                }
            });
            $('#testEmail').on('click', function () {
                var password = '';
                if(isUpperCase($('#smtpHostPassword_id').val())){
                    password = '<?php echo SMTPHOSTPASSWORD; ?>';
                }else{
                    password = $('#smtpHostPassword_id').val();
                }
                console.log("starting");
                ajax_request('POST', 'test-email', {
                    emailto: '<?php echo $USER->email;?>',
                    emailhost: $('#smtpHost_id').val(),
                    emailport: $('#smtpHostPort_id').val(),
                    emailusername: $('#smtpHostUsername_id').val(),
                    emailpassword: password,
                    emailsendername: $('#smtpHostSenderName_id').val(),
                    emailsenderemail: $('#smtpHostSenderEmail_id').val(),
                    emailtype: $('#smtpHostType_id').val(),
                    emailauth: $('#smtpHostAuth_id').attr("data-value"),
                });
				console.log(
					'TO: <?php echo $USER->email;?>\n'+
					'HOST: '+$('#smtpHost_id').val()+'\n'+
					'PORT: '+$('#smtpHostPort_id').val()+'\n'+
					'USERNAME: '+$('#smtpHostUsername_id').val()+'\n'+
					'SENDER NAME: '+$('#smtpHostSenderName_id').val()+'\n'+
					'SENDER EMAIL: '+$('#smtpHostSenderEmail_id').val()+'\n'+
					'TYPE: '+$('#smtpHostType_id').val()+'\n'+
					'AUTH: '+$('#smtpHostAuth_id').attr("data-value")+'\n'
				);
                console.log("ajax done");
            });
            //Custom Themes
            function changeColor(elementName, elementColor) {
                var definedElement = document.getElementById(elementName);
                definedElement.focus();
                definedElement.value = elementColor;
                definedElement.style.backgroundColor = elementColor;
				$(definedElement).trigger('change');
            }

            $('#plexTheme').on('click touchstart', function(){

                changeColor("topbartext", "#E49F0C");
                changeColor("topbar", "#000000");
                changeColor("bottombar", "#000000");
                changeColor("sidebar", "#121212");
                changeColor("hoverbg", "#FFFFFF");
                changeColor("activetabBG", "#E49F0C");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#949494");
                changeColor("inactivetext", "#B8B8B8");
                changeColor("loading", "#E49F0C");
                changeColor("hovertext", "#000000");
            });
            $('#embyTheme').on('click touchstart', function(){

                changeColor("topbartext", "#52B54B");
                changeColor("topbar", "#212121");
                changeColor("bottombar", "#212121");
                changeColor("sidebar", "#121212");
                changeColor("hoverbg", "#FFFFFF");
                changeColor("activetabBG", "#52B54B");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#949494");
                changeColor("inactivetext", "#B8B8B8");
                changeColor("loading", "#52B54B");
                changeColor("hovertext", "#000000");
            });
            $('#bookTheme').on('click touchstart', function(){

                changeColor("topbartext", "#FFFFFF");
                changeColor("topbar", "#3B5998");
                changeColor("bottombar", "#3B5998");
                changeColor("sidebar", "#8B9DC3");
                changeColor("hoverbg", "#FFFFFF");
                changeColor("activetabBG", "#3B5998");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#DFE3EE");
                changeColor("inactivetext", "#DFE3EE");
                changeColor("loading", "#FFFFFF");
                changeColor("hovertext", "#000000");
            });
            $('#spaTheme').on('click touchstart', function(){

                changeColor("topbartext", "#5B391E");
                changeColor("topbar", "#66BBAE");
                changeColor("bottombar", "#66BBAE");
                changeColor("sidebar", "#C3EEE7");
                changeColor("hoverbg", "#66BBAE");
                changeColor("activetabBG", "#C6C386");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#5B391E");
                changeColor("inactivetext", "#5B391E");
                changeColor("loading", "#5B391E");
                changeColor("hovertext", "#000000");
            });
            $('#darklyTheme').on('click touchstart', function(){

                changeColor("topbartext", "#FFFFFF");
                changeColor("topbar", "#375A7F");
                changeColor("bottombar", "#375A7F");
                changeColor("sidebar", "#222222");
                changeColor("hoverbg", "#464545");
                changeColor("activetabBG", "#FFFFFF");
                changeColor("activetabicon", "#464545");
                changeColor("activetabtext", "#464545");
                changeColor("inactiveicon", "#0CE3AC");
                changeColor("inactivetext", "#0CE3AC");
                changeColor("loading", "#FFFFFF");
                changeColor("hovertext", "#000000");
            });
            $('#slateTheme').on('click touchstart', function(){

                changeColor("topbartext", "#C8C8C8");
                changeColor("topbar", "#272B30");
                changeColor("bottombar", "#272B30");
                changeColor("sidebar", "#32383E");
                changeColor("hoverbg", "#58C0DE");
                changeColor("activetabBG", "#3E444C");
                changeColor("activetabicon", "#C8C8C8");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#C8C8C8");
                changeColor("inactivetext", "#C8C8C8");
                changeColor("loading", "#C8C8C8");
                changeColor("hovertext", "#000000");
            });
            $('#defaultTheme').on('click touchstart', function(){

                changeColor("topbartext", "#FFFFFF");
                changeColor("topbar", "#eb6363");
                changeColor("bottombar", "#eb6363");
                changeColor("sidebar", "#000000");
                changeColor("hoverbg", "#eb6363");
                changeColor("activetabBG", "#eb6363");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#FFFFFF");
                changeColor("inactivetext", "#FFFFFF");
                changeColor("loading", "#FFFFFF");
                changeColor("hovertext", "#000000");
            });
            $('#redTheme').on('click touchstart', function(){

                changeColor("topbartext", "#FFFFFF");
                changeColor("topbar", "#eb6363");
                changeColor("bottombar", "#eb6363");
                changeColor("sidebar", "#000000");
                changeColor("hoverbg", "#eb6363");
                changeColor("activetabBG", "#eb6363");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#FFFFFF");
                changeColor("inactivetext", "#FFFFFF");
                changeColor("loading", "#FFFFFF");
                changeColor("hovertext", "#000000");
            });
            $('#monokaiTheme').on('click touchstart', function(){

                changeColor("topbartext", "#66D9EF");
                changeColor("topbar", "#333333");
                changeColor("bottombar", "#333333");
                changeColor("sidebar", "#393939");
                changeColor("hoverbg", "#AD80FD");
                changeColor("activetabBG", "#F92671");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#66D9EF");
                changeColor("inactivetext", "#66D9EF");
                changeColor("loading", "#66D9EF");
                changeColor("hovertext", "#000000");
            });
            $('#thejokerTheme').on('click touchstart', function(){

                changeColor("topbartext", "#CCCCCC");
                changeColor("topbar", "#000000");
                changeColor("bottombar", "#000000");
                changeColor("sidebar", "#121212");
                changeColor("hoverbg", "#CCC6CC");
                changeColor("activetabBG", "#A50CB0");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#949494");
                changeColor("inactivetext", "#B8B8B8");
                changeColor("loading", "#CCCCCC");
                changeColor("hovertext", "#000000");

            });
            $('#newPlexTheme').on('click touchstart', function(){

                changeColor("topbartext", "#E5A00D");
                changeColor("topbar", "#282A2D");
                changeColor("bottombar", "#282A2D");
                changeColor("sidebar", "#3F4245");
                changeColor("hoverbg", "#E5A00D");
                changeColor("activetabBG", "#282A2D");
                changeColor("activetabicon", "#E5A00D");
                changeColor("activetabtext", "#E5A00D");
                changeColor("inactiveicon", "#F9F9F9");
                changeColor("inactivetext", "#F9F9F9");
                changeColor("loading", "#E5A00D");
                changeColor("hovertext", "#E0E3E6");

            });//$( "div" ).not( ".green, #blueone" )
            $('textarea').not( ".no-code" ).numberedtextarea({

              // font color for line numbers
              color: null,

              // border color
              borderColor: 'null',

              // CSS class to be added to the line numbers
              class: null,

              // if true Tab key creates indentation
              allowTabChar: true,

            });
            //more/less
            $(".toggleTabExtra").click(function () {
                $(this).find('.btn-text').text(function(i, text){
                    return text === "More" ? "Less" : "More";
                })
                $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
             });
             $("#toggleAllExtra").click(function () {
                $( ".toggleTabExtra" ).each(function() {
                    $(this).click();
                });
                $(this).find('i').toggleClass('fa-toggle-off fa-toggle-on');
             });
            $(".email-header .close-button").click(function () {
				$(".email-content").removeClass("email-active");
				$(".settingsMenu").removeClass("settingsMenuActive");
				$(".settingsMenu").removeClass("settingsMenuInactive");
                $('html').removeClass("overhid");
				$("#settings-list").find("li").removeClass("active");
            });
             $(document).mouseup(function (e)
{
                var container = $(".email-content, .checkFrame, .scroller-body");

                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    $(".email-content").removeClass("email-active");
                    $('html').removeClass("overhid");
                    $("#settings-list").find("li").removeClass("active");
                }
            });
            $( document ).on( 'keydown', function ( e ) {
                if ( e.keyCode === 27 ) { // ESC
                    var container = $(".email-content");

                    if (!container.is(e.target) && container.has(e.target).length === 0) {
						$(".email-content").removeClass("email-active");
						$(".settingsMenu").removeClass("settingsMenuActive");
						$(".settingsMenu").removeClass("settingsMenuInactive");
						$('html').removeClass("overhid");
						$("#settings-list").find("li").removeClass("active");
                    }
                }
            });

            $("#open-info, #open-users, #open-logs, #open-advanced, #open-homepage, #open-colors, #open-tabs, #open-donate, #open-invites , #open-themes, #open-speedtest, #open-email, #open-help").on("click",function (e) {
                $(".email-content").removeClass("email-active");
                $('html').removeClass("overhid");
                if($(window).width() < 768){
                    $('html').addClass("overhid");
                }
                //Theme box
                if($(this).attr("box") == "themes-box"){
                    getLayerCakeThemes();
                }
                if (typeof $(this).attr("box") !== 'undefined') {
                    var settingsBox = $('.'+$(this).attr("box"));
                }else{
                    var settingsBox = $('.themes-box');
                }
                //console.log(settingsBox);
                settingsBox.addClass("email-active");
                $("#settings-list").find("li").removeClass("active");
                $(this).parent().addClass("active");

                $("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(settingsBox).show();

                setTimeout(function(){
                    var refreshMailPreloader = settingsBox.find('.refresh-preloader'),
                    deletedMailBox = refreshMailPreloader.fadeOut(300, function(){
                    refreshMailPreloader.remove();
                });
                },600);
                e.preventDefault();
            });

			function customEmail(id){
				if(id == 'one'){
					var Body = <?php echo json_encode(emailTemplateCustomOne); ?>;
					var Subject = <?php echo json_encode(emailTemplateCustomOneSubject); ?>;
				}else if(id == 'two'){
					var Body = <?php echo json_encode(emailTemplateCustomTwo); ?>;
					var Subject = <?php echo json_encode(emailTemplateCustomTwoSubject); ?>;
				}else if(id == 'three'){
					var Body = <?php echo json_encode(emailTemplateCustomThree); ?>;
					var Subject = <?php echo json_encode(emailTemplateCustomThreeSubject); ?>;
				}else if(id == 'four'){
					var Body = <?php echo json_encode(emailTemplateCustomFour); ?>;
					var Subject = <?php echo json_encode(emailTemplateCustomFourSubject); ?>;
				}
				console.log(Body);
				console.log(Subject);
				$('#subject').val(Subject);
				$('.email-box .note-editable.panel-body').html(Body);
			}

            function checkGithub() {
                $.ajax({
                    type: "GET",
                    url: "https://api.github.com/repos/causefx/Organizr/releases",
                    dataType: "json",
                    success: function(github) {
                        var currentVersion = "<?php echo INSTALLEDVERSION;?>";
                        infoTabVersion = $('#about').find('#version');
                        infoTabVersionHistory = $('#about').find('#versionHistory');
                        infoTabNew = $('#about').find('#whatsnew');
                        infoTabDownload = $('#about').find('#downloadnow');
                        $.each(github, function(i,v) {
                            if(i === 0){
                                console.log(v.tag_name);
                                githubVersion = v.tag_name;
                                githubDescription = v.body;
                                githubName = v.name;
                            }
                            $(infoTabVersionHistory).append('<li style="display: none"><time class="cbp_tmtime" datetime="' + v.published_at + '"><span>' + v.published_at.substring(0,10) + '</span> <span>' + v.tag_name + '</span></time><div class="cbp_tmicon animated jello"><i class="fa fa-github-alt"></i></div><div class="cbp_tmlabel"><h2 class="text-uppercase">' + v.name + '</h2><p>' + v.body + '</p></div></li>');
                            size_li = $("#versionHistory li").size();
                            x=2;
                            $('#versionHistory li:lt('+x+')').show();
                        });
                        if(currentVersion < githubVersion){
                            console.log("You Need To Upgrade");
                            parent.notify("<strong><?php echo $language->translate("NEW_VERSION");?></strong> <?php echo $language->translate("CLICK_INFO");?>","arrow-circle-o-down","warning","50000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");

                            $(infoTabNew).html("<br/><h4><strong><?php echo $language->translate("WHATS_NEW");?> " + githubVersion + "</strong></h4><strong><?php echo $language->translate("TITLE");?>: </strong>" + githubName + " <br/><strong><?php echo $language->translate("CHANGES");?>: </strong>" + githubDescription);
                            <?php if (extension_loaded("ZIP")){?>
                            $(infoTabDownload).html("<br/><form style=\"display:initial;\" id=\"upgradeOrg\" method=\"post\" onsubmit=\"performUpdate(); ajax_request(\'POST\', \'upgradeInstall\'); return false;\"><input type=\"hidden\" name=\"action\" value=\"upgrade\" /><button class=\"btn waves btn-labeled btn-success text-uppercase waves-effect waves-float\" type=\"submit\"><span class=\"btn-label\"><i class=\"fa fa-refresh\"></i></span><?php echo $language->translate("AUTO_UPGRADE");?></button></form> <a href='https://github.com/causefx/Organizr/archive/master.zip' target='_blank' type='button' class='btn waves btn-labeled btn-success text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-download'></i></span>Organizr v." + githubVersion + "</a>");
                            $( "p[id^='upgrade']" ).toggle();
                            <?php }else{ ?>
                            $(infoTabDownload).html("<br/><a href='https://github.com/causefx/Organizr/archive/master.zip' target='_blank' type='button' class='btn waves btn-labeled btn-success text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-download'></i></span>Organizr v." + githubVersion + "</a>");
                            $( "p[id^='upgrade']" ).toggle();
                            <?php } ?>
                        }else if(currentVersion === githubVersion){
                            console.log("You Are on Current Version");
                        }else{
                            console.log("something went wrong");
                        }
                        $(infoTabVersion).html("<strong><?php echo $language->translate("INSTALLED_VERSION");?>: </strong>" + currentVersion + " <strong><?php echo $language->translate("CURRENT_VERSION");?>: </strong>" + githubVersion + " <strong><?php echo $language->translate("DATABASE_PATH");?>:  </strong> <?php echo htmlentities(DATABASE_LOCATION);?> <strong><?php echo $language->translate("DOMAIN");?>:  </strong> <?php echo substr(getServerPath(), 0, -1);?>");
                    }
                });
            }

            function layerCake(type, path) {
                $.ajax({
                    type: "GET",
                    url: "ajax.php?a=show-file&file=https://raw.githubusercontent.com/leram84/layer.Cake/master/"+path+"/"+type+".css",
                    dataType: "text",
                    success: function(github) {
                        cssTab = $("a[href^='#tab-theme_css']");
                        cssTab.trigger("click");
                        $('#customCSS_id').text(github);
                        $('#installedTheme').val('');
                        $("#installedTheme").attr('data-changed', 'true');
                        swal({
                            title: "Loaded Layer#Cake "+type,
                            text: '<h2>Awesome Sauce!</h2><p>Now that you have enabled Layer#Cake, edit the colors here and then hit Save at the top right.<blockquote class="blockquote-reverse"><p>Layer#Cake is powered and brought to you by:</p><footer>Hackerman - <cite title="Source Title">Leram</cite></footer></blockquote>',
                            html: true,
                            confirmButtonColor: "#63A8EB"
                        });
                    }
                });
            }

            function getLayerCakeThemes() {
                $.ajax({
                    type: "GET",
                    url: "https://api.github.com/repos/leram84/layer.Cake/contents/Themes",
                    dataType: "json",
                    success: function(github) {
                        themeList = $('#theme-list');
                        themeList.html("");
                        var countThemes = 0;
                        $.each(github, function(i,v) {
                            if(v.type === "file"){
                                i++;
                                countThemes = i;
                                file = v.name.split("-");
								preview = v.name.split(".");
								preview = preview[0].substring(4, preview[0].length -2).split("-");
								version = file[3].split(".");
								version = version[0]+'.'+version[1];
								fileName = file[1];
                                fileOrder = file[0];
                                fileAuthor = file[2];
                                if(fileName == '<?php echo $themeName; ?>'){
                                    if(version !== '<?php echo $themeVersion; ?>'){
                                        //update available
                                        info = '<p class="pull-right"><span class="label label-primary">Update Available</span></p>';

                                    }else{
                                        //no update available
                                        info = '<p class="pull-right"><span class="label label-success">Installed</span></p>';
                                    }
                                }else{
                                    info = '';
                                }
                                $(themeList).append('<li><a preview="'+preview[0]+'.png" name="'+fileName+'" check="'+fileName+'-'+version+'" version="'+version+'" file="'+v.name+'" path="'+v.path+'" order="'+fileOrder+'" author="'+fileAuthor+'" id="LC-'+fileName+'">'+fileName+' v'+version+' '+info+'</a></li>');
                            }
                        });
                        console.log(countThemes);
                    }
                });
            }

            function layerCakeTheme(path, name, author, theme) {
                var settingsBox = $('.themes-box');
                $("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(settingsBox).show();
                $.ajax({
                    type: "GET",
                    url: "ajax.php?a=show-file&file=https://raw.githubusercontent.com/leram84/layer.Cake/master/Themes/"+path,
                    dataType: "text",
                    success: function(github) {
                        $("#open-colors").trigger("click");
                        $("a[href^='#tab-theme_css']").trigger("click");
                        $('#customCSS_id').text(github);
						$("#customCSS_id").attr('data-changed', 'true');
						$('#installedTheme').val(theme);
						$('.themeHeader').text('Installed Theme: '+theme);
						$("#installedTheme").attr('data-changed', 'true');
                        swal({
                            title: "Loaded Theme: "+name,
                            text: '<h2>Awesome Sauce!</h2><p>Theme has been imported. <p><strong style="color: red;">Please click Save at the top right.</strong></p><blockquote class="blockquote-reverse"><p>Layer#Cake Theme by:</p><footer><cite title="Source Title">'+author+'</cite></footer></blockquote>',
                            html: true,
                            confirmButtonColor: "#63A8EB"
                        });
                    }
                });
            }

            //layerCake Themes
            $(document).on('click', "a[id*=LC-]", function(){
                file = $(this).attr("file");
                name = $(this).attr("name");
                author = $(this).attr("author");
                theme = $(this).attr("name")+'-'+$(this).attr("version");
                $.ajax({
                    type: 'GET',
                    url: 'https://raw.githubusercontent.com/leram84/layer.Cake/master/Themes/Information/'+name+'.txt',
                    dataType: "html",
                    async: false,
                    success: function(msg){
                        gotinformation = msg.replace(/\r\n|\r|\n/g,"<br/>");

                    },
                    error: function(msg){
                        gotinformation = "There is no information for theme "+name;
                    }
                });
                information = '<div class="caption gray-bg"><h3>Theme Information</h3><p>'+gotinformation+'</p></div>';
                button = '<div class="thumbnail gray-bg"><div class="caption gray-bg"><p class="pull-left">'+name+' by: '+author+'</p><p class="pull-right"><button type="button" onclick="layerCakeTheme(\''+file+'\',\''+name+'\',\''+author+'\',\''+theme+'\')" class="btn btn-success waves waves-effect waves-float">Install</button></p></div><img src="https://raw.githubusercontent.com/leram84/layer.Cake/master/Themes/Preview/'+$(this).attr("preview")+'" alt="thumbnail">'+information+'</div>';
                $('#chooseLayer').hide();
                themeInfo = $('#layerCakeInfo');
                $('#layerCakePreview').html( ''+button+'' );
            });

            $("#clearTheme").click(function () {
                swal({
                    title: "Please Choose",
                    text: "You can clear just the theme name saved or clear theme name and CSS",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Clear Everything!",
                    cancelButtonText: "Clear Name Only!",
                    closeOnConfirm: false,
                    closeOnCancel: false,
                    confirmButtonColor: "#63A8EB"
                },
                function (isConfirm) {
                    if (isConfirm) {
                        swal("Cleared!", "All Theme settings have been cleared", "success");
                        $('#customCSS_id').text("");
						$("#customCSS_id").attr('data-changed', 'true');
						$('#installedTheme').val("");
						$('.themeHeader').text('Installed Theme: No Theme Installed!');
						$("#installedTheme").attr('data-changed', 'true');
                        $('#appearance_settings_form_submit').addClass("animated tada");
                    } else {
                        swal("Cleared", "Cleared the Theme name saved, CSS still remains", "success");
						$('#installedTheme').val("");
						$('.themeHeader').text('Installed Theme: No Theme Installed!');
						$("#installedTheme").attr('data-changed', 'true');
                        $('#appearance_settings_form_submit').addClass("animated tada");
                    }
                });
            });
        </script>
        <script>
        $( document ).ready(function() {

			$("#homepage-items").sortable({
				placeholder:    "sort-placeholder col-md-3",
				forcePlaceholderSize: true,
				start: function( e, ui ){
					ui.item.data( "start-pos", ui.item.index()+1 );
				},
				change: function( e, ui ){
					var seq,
					startPos = ui.item.data( "start-pos" ),
					$index,
					correction;
					correction = startPos <= ui.placeholder.index() ? 0 : 1;
					ui.item.parent().find( "div.sort-homepage").each( function( idx, el ){
						var $this = $( el ),
						$index = $this.index();
						if ( ( $index+1 >= startPos && correction === 0) || ($index+1 <= startPos && correction === 1 ) ){
							$index = $index + correction;
							$this.find( ".ordinal-position").text( $index);
							link = $this.find( ".ordinal-position" ).attr('data-link');
							$('#homepage-values [name='+link+']').val($index);
							$('#homepage-values [name='+link+']').attr('data-changed', 'true');
							console.log(link+' - '+$index);
						}
					});
					seq = ui.item.parent().find( "div.sort-placeholder.col-md-3").index() + correction;
					ui.item.find( ".ordinal-position" ).text( seq );
					newlink = ui.item.find( ".ordinal-position" ).attr('data-link');
					$('#homepage-values [name='+newlink+']').val(seq);
					$('#homepage-values [name='+newlink+']').attr('data-changed', 'true');
					console.log(newlink+' - '+seq);
				}
			});

			$(".iconpickeradd").on("click", function() {
                console.log('icon picker start add');
                $(this).iconpicker({placement: 'right', hideOnSelect: false, collision: true});
				$(".iconpicker-items").niceScroll({
					railpadding: {top:0,right:0,left:0,bottom:0},
					scrollspeed: 30,
	                mousescrollstep: 60,
	                grabcursorenabled: false
	            });
                $(this).focus();
            });
            //AJAX Submit for URL Check
            $('#urlTestForm_submit').on('click', function () {
                ajax_request('POST', 'check-url', {
                    checkurl: $('#urlTestForm [name=url-test]').val(),
                });
            });

            //Hide Icon box on load
            $( "div[class^='jFiler jFiler-theme-dragdropbox']" ).hide();
            //Set Some Scrollbars
			$(".note-editable panel-body").niceScroll({
                railpadding: {top:0,right:0,left:0,bottom:0},
                grabcursorenabled: false
            });
            $(".scroller-body").niceScroll({
                railpadding: {top:0,right:0,left:0,bottom:0},
                grabcursorenabled: false
            });
            $(".email-content").niceScroll({
                railpadding: {top:0,right:0,left:0,bottom:0},
                railoffset: {top:75,right:0,left:0,bottom:75},
                grabcursorenabled: false,
                zindex: 1101
            });
            $("textarea").niceScroll({
                railpadding: {top:0,right:0,left:0,bottom:0},
                grabcursorenabled: false
            });

            //Stop Div behind From Scrolling
            $( '.email-content' ).on( 'mousewheel', function ( e ) {
                e.preventDefault();
            }, false);
            //Set Hide Function
			         if (0) {
                var authTypeFunc = function() {
                    // Hide Everything
                    $('#host-selected, #host-other, #host-plex, #host-emby, #host-ldap').hide();
                    // Qualify Auth Type
                    if($('#authType').val() !== "internal"){
                        $( '#host-selected' ).show();

                        // Qualify aithBackend
                        if($('#authBackend').val() === "plex"){
                            $('#host-selected, #host-plex').show();
                        }else if($('#authBackend').val().indexOf("emby")>=0){
                            $('#host-selected, #host-other, #host-emby').show();
                        }else if($('#authBackend').val() === "ldap"){
                            $('#host-selected, #host-other, #host-ldap').show();
                        }else {
                            $('#host-selected, #host-other').show();
                        }
                    }
                }
                //Hide Settings on selection
                $('#authType, #authBackend').on('change', authTypeFunc);
                //Hide Settings on Load
                authTypeFunc();
			         } else { console.log() }
            //Simulate Edit Tabs Click
            //$("#open-tabs").trigger("click");
            //Append Delete log to User Logs and Org Logs
            $("#datatable_wrapper > div[class^='DTTT_container']").append('<form style="display: inline; margin-left: 3px;" id="deletelog" method="post" onsubmit="ajax_request(\'POST\', \'deleteLog\'); return false;"><input type="hidden" name="action" value="deleteLog" /><button class="btn waves btn-labeled btn-danger text-uppercase waves-effect waves-float" type="submit"><span class="btn-label"><i class="fa fa-trash"></i></span><?php echo $language->translate("PURGE_LOG");?> </button></form>');
            $("#orgLogs_wrapper > div[class^='DTTT_container']").append('<form style="display: inline; margin-left: 3px;" id="deleteOrglog" method="post" onsubmit="ajax_request(\'POST\', \'deleteOrgLog\'); return false;"><input type="hidden" name="action" value="deleteOrgLog" /><button class="btn waves btn-labeled btn-danger text-uppercase waves-effect waves-float" type="submit"><span class="btn-label"><i class="fa fa-trash"></i></span><?php echo $language->translate("PURGE_LOG");?> </button></form>')
            $("a[id^='ToolTables_datatable_0'] span").html('<?php echo $language->translate("PRINT");?>')
            //Enable Tooltips
            $('[data-toggle="tooltip"]').tooltip();
            //AJAX call to github to get version info
			<?php if (GIT_CHECK == "true") { echo 'checkGithub()'; } ?>

            //Edit Info tab with Github info
            <?php if(file_exists(FAIL_LOG)) : ?>
            goodCount = $('#loginStats').find('#goodCount');
            goodPercent = $('#loginStats').find('#goodPercent');
            goodTitle = $('#loginStats').find('#goodTitle');
            badCount = $('#loginStats').find('#badCount');
            badPercent = $('#loginStats').find('#badPercent');
            badTitle = $('#loginStats').find('#badTitle');
            $(goodCount).html("<?php echo $goodLogin;?>");
            $(goodTitle).html("<?php echo $goodPercent;?>%");
            $(goodPercent).attr('aria-valuenow', "<?php echo $goodPercent;?>");
            $(goodPercent).attr('style', "width: <?php echo $goodPercent;?>%");
            $(badCount).html("<?php echo $badLogin;?>");
            $(badTitle).html("<?php echo $badPercent;?>%");
            $(badPercent).attr('aria-valuenow', "<?php echo $badPercent;?>");
            $(badPercent).attr('style', "width: <?php echo $badPercent;?>%");
            <?php endif; ?>
        });
        </script>

    </body>

</html>
