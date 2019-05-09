<?php include 'api/functions/static-globals.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <meta content="<?php echo $GLOBALS['organizrIndexDescription']; ?>"
          name="description">
    <meta content="CauseFX" name="author">
	<?php echo favIcons(); ?>
    <title><?php echo $GLOBALS['organizrIndexTitle']; ?></title>
    <link href="bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="plugins/bower_components/sidebar-nav/dist/sidebar-nav.min.css" rel="stylesheet">
    <link href="plugins/bower_components/jquery-wizard-master/css/wizard.css" rel="stylesheet">
    <link href="plugins/bower_components/datatables/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>
    <link href="plugins/bower_components/jquery-wizard-master/libs/formvalidation/formValidation.min.css"
          rel="stylesheet">
    <link href="plugins/bower_components/Magnific-Popup-master/dist/magnific-popup.css" rel="stylesheet">
    <!--<link href="plugins/bower_components/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">-->
    <link href="plugins/bower_components/switchery/dist/switchery.min.css" rel="stylesheet"/>
    <link href="plugins/bower_components/dropzone-master/dist/dropzone.css" rel="stylesheet" type="text/css"/>
    <link href="plugins/bower_components/css-chart/css-chart.css" rel="stylesheet">
    <link href="plugins/bower_components/calendar/dist/fullcalendar.css" rel="stylesheet"/>
    <link href="plugins/bower_components/custom-select/custom-select.css" rel="stylesheet" type="text/css"/>
    <link href="plugins/bower_components/bootstrap-colorpicker-sliders/bootstrap.colorpickersliders.min.css"
          rel="stylesheet" type="text/css"/>
    <link href="plugins/bower_components/bootstrap-select/bootstrap-select.min.css" rel="stylesheet"/>
    <link href="plugins/bower_components/multiselect/css/multi-select.css" rel="stylesheet" type="text/css"/>
    <link href="plugins/bower_components/owl.carousel/owl.carousel.min.css" rel="stylesheet" type="text/css"/>
    <link href="plugins/bower_components/owl.carousel/owl.theme.default.css" rel="stylesheet" type="text/css"/>
    <link href="plugins/bower_components/hover/hover-min.css" rel="stylesheet" type="text/css"/>
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/simplebar.css" rel="stylesheet">
    <link href="css/plyr.css" rel="stylesheet">
    <link id="style" href="css/dark.css?v=<?php echo $GLOBALS['fileHash']; ?>" rel="stylesheet">
    <link href="css/organizr.min.css?v=<?php echo $GLOBALS['fileHash']; ?>" rel="stylesheet">
	<?php echo pluginFiles('css'); ?>
    <link id="theme" href="css/themes/Organizr.css?v=<?php echo $GLOBALS['fileHash']; ?>" rel="stylesheet">
    <style id="user-appearance"></style>
    <style id="custom-theme-css"></style>
    <style id="custom-css"></style>
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"
            integrity="sha384-0s5Pv64cNZJieYFkXYOTId2HMA2Lfb6q2nAcx2n0RTLUnCAoTTsS0nKEO27XyKcY"
            crossorigin="anonymous"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"
            integrity="sha384-ZoaMbDF+4LeFxg6WdScQ9nnR1QC2MIRxA1O9KWEXQwns1G8UNyIEZIQidzb0T1fo"
            crossorigin="anonymous"></script>
    <![endif]-->
</head>

<body class="fix-header" data-active-tab="">
<!-- ============================================================== -->
<!-- Preloader -->
<!-- ============================================================== -->
<div id="preloader" class="preloader">
    <svg class="circular" viewbox="25 25 50 50">
        <circle class="path" cx="50" cy="50" fill="none" r="20" stroke-miterlimit="10" stroke-width="10"></circle>
    </svg>
</div>
<!-- ============================================================== -->
<!-- Wrapper -->
<!-- ============================================================== -->
<div id="wrapper">
    <!-- ============================================================== -->
    <!-- Topbar header - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <nav class="navbar navbar-default navbar-static-top m-b-0 animated slideInDown">
        <div class="navbar-header">
            <div class="top-left-part hidden-xs p-r-10">
                <!-- Logo -->
                <a class="logo" href="javascript:void(0)">
                    <!-- Logo text image you can use text also -->
                    <span class="hidden-xs elip" id="main-logo"></span>
                </a>
            </div>
            <!-- /Logo -->
            <!-- Search input and Toggle icon -->
            <ul class="nav navbar-top-links navbar-left">
                <li><a class="open-close waves-effect waves-light visible-xs" href="javascript:void(0)"><i
                                class="ti-close ti-menu fa-fw"></i></a></li>
                <li class=""><a class="dropdown-toggle waves-effect waves-light" onclick="reloadCurrentTab();"> <i
                                class="ti-reload"></i></a></li>
                <li class=""><a class="dropdown-toggle waves-effect waves-light" onclick="closeCurrentTab();"> <i
                                class="ti-close"></i></a></li>
                <li class=""><a class="dropdown-toggle waves-effect waves-light hidden" onclick="splashMenu();"> <i
                                class="ti-layout-grid2"></i></a></li>
            </ul>
            <ul class="nav navbar-top-links navbar-right pull-right"></ul>
        </div>
        <!-- /.navbar-header -->
        <!-- /.navbar-top-links -->
        <!-- /.navbar-static-side -->
        <div class="dropdown-menu animated bounceInDown bg-danger text-white" id="main-org-error-container">
            <div class="mega-dropdown-menu row">
                <div class="col-lg-12 mb-4">
                    <h3 class="mb-3 pull-left"><i class="fa fa-close text-white"></i>&nbsp; <span lang="en">An Error Occured</span>
                    </h3>
                    <h3 class="mb-3 pull-right mouse" onclick="closeOrgError();"><i
                                class="fa fa-check text-success"></i>&nbsp;
                        <span lang="en">Close Error</span>
                    </h3>
                    <br/>
                    <br/>
                    <div class="m-t-20" id="main-org-error"></div>
                </div>
            </div>
        </div>
    </nav>
    <!-- End Top Navigation -->
    <!-- ============================================================== -->
    <!-- Left Sidebar - style you can find in sidebar.scss  -->
    <!-- ============================================================== -->
    <div class="navbar-default sidebar" role="navigation">
        <div class="sidebar-nav slimscrollsidebar">
            <div class="sidebar-head">
                <h3><span class="open-close m-r-5"><i class="ti-menu hidden-xs"></i><i class="ti-close visible-xs"></i></span>
                    <span class="hide-menu hidden-xs" lang="en">Navigation</span>
                    <span class="hide-menu hidden-sm hidden-md hidden-lg" id="side-logo"></span>
                </h3>
            </div>
            <ul class="nav" id="side-menu"></ul>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- End Left Sidebar -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- Page Content -->
    <!-- ============================================================== -->
    <div class="error-page bg-org"></div>
    <div class="login-area hidden"></div>
    <div class="p-0" id="page-wrapper">
        <div class="organizr-area"></div>
        <div class="plugin-listing p-0 hidden"></div>
        <div class="internal-listing p-0 hidden"></div>
        <div class="iFrame-listing p-0 hidden"></div>
    </div>
    <!-- debug modal content -->
    <div class="modal fade debugModal" tabindex="-1" role="dialog" aria-labelledby="debugModal"
         aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title" id="debugModal" lang="en">Organizr Debug Area</h4>
                </div>
                <div class="modal-body">
                    <div class="white-box m-0">
                        <div class="steamline">
                            <div class="sl-item">
                                <div class="sl-left bg-success"><i class="mdi mdi-code-tags"></i></div>
                                <div class="sl-right">
                                    <div class="form-group">
                                        <div id="" class="input-group">
                                            <input id="debug-input" lang="en" placeholder="Input Command" type="text"
                                                   class="form-control inline-focus">
                                            <div class="input-group-btn">
                                                <button type="button"
                                                        class="btn waves-effect waves-light btn-info dropdown-toggle"
                                                        data-toggle="dropdown" aria-expanded="false"><span lang="en">Commands</span>
                                                    <span class="caret"></span></button>
                                                <ul class="dropdown-menu dropdown-menu-right">
                                                    <li><a onclick="orgDebugList('activeInfo.settings.sso');"
                                                           href="javascript:void(0)"
                                                           lang="en">SSO</a></li>
                                                    <li><a onclick="orgDebugList('activeInfo.settings.sso.ombi');"
                                                           href="javascript:void(0)"
                                                           lang="en">Ombi SSO</a></li>
                                                    <li><a onclick="orgDebugList('activeInfo.settings.sso.plex');"
                                                           href="javascript:void(0)"
                                                           lang="en">Plex SSO</a></li>
                                                    <li><a onclick="orgDebugList('activeInfo.settings.sso.tautulli');"
                                                           href="javascript:void(0)"
                                                           lang="en">Tautulli SSO</a></li>
                                                    <li><a onclick="orgDebugList('activeInfo.settings.sso.misc');"
                                                           href="javascript:void(0)"
                                                           lang="en">Misc SSO</a></li>
                                                    <li><a onclick="orgDebugList('activeInfo.settings.misc.schema');"
                                                           href="javascript:void(0)"
                                                           lang="en">DB Schema</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                                </div>
                            </div>
                            <div id="debugPreInfoBox" class="sl-item">
                                <div class="sl-left bg-info"><i class="mdi mdi-package-variant-closed"></i></div>
                                <div class="sl-right">
                                    <div>
                                        <span lang="en">Organizr Information:</span>&nbsp;
                                    </div>
                                    <div id="debugPreInfo" class="desc"></div>
                                </div>
                            </div>
                            <div id="debugResultsBox" class="sl-item hidden">
                                <div class="sl-left bg-info"><i class="mdi mdi-receipt"></i></div>
                                <div class="sl-right">
                                    <div><span lang="en">Results For cmd:</span>&nbsp;<span class="cmdName"></span>
                                    </div>
                                    <div id="debugResults" class="desc"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger waves-effect text-left" data-dismiss="modal">Close
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->
    <!-- help modal content -->
    <div class="modal fade help-modal-lg" tabindex="-1" role="dialog" aria-labelledby="help-modal-lg" aria-hidden="true"
         style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title" id="help-modal-title">Large modal</h4></div>
                <div class="modal-body" id="help-modal-body"></div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->
    <!-- ============================================================== -->
    <!-- End Page Content -->
    <!-- ============================================================== -->
    <a href="#" id="scroll" style="display: none;"><span></span></a>
    <button id="internal-clipboard" class="hidden"></button>
</div>
<!-- /#wrapper -->
<!-- jQuery -->
<!--<script src="plugins/bower_components/jquery/dist/jquery.min.js"></script>-->
<?php echo "<script>languageList = " . languagePacks(true) . ";\n</script>"; ?>
<script src="js/jquery-2.2.4.min.js"></script>
<script src="bootstrap/dist/js/bootstrap.min.js"></script>
<script src="plugins/bower_components/sidebar-nav/dist/sidebar-nav.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/waves.js"></script>
<script src="plugins/bower_components/styleswitcher/jQuery.style.switcher.js"></script>
<script src="plugins/bower_components/moment/moment.js"></script>
<script src="plugins/bower_components/moment/moment-timezone.js"></script>
<script src="plugins/bower_components/jquery-wizard-master/dist/jquery-wizard.min.js"></script>
<script src="plugins/bower_components/jquery-wizard-master/libs/formvalidation/formValidation.min.js"></script>
<script src="plugins/bower_components/jquery-wizard-master/libs/formvalidation/bootstrap.min.js"></script>
<script src="js/bowser.min.js"></script>
<script src="js/jasny-bootstrap.js"></script>
<script src="js/cbpFWTabs.js"></script>
<script src="js/js.cookie.js"></script>
<script src="js/jquery-lang.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/jquery.serializeToJSON.js"></script>
<script src="js/lazyload.min2.js"></script>
<script src="js/clipboard.js"></script>
<script src="js/emulatetab.joelpurra.js"></script>
<script src="plugins/bower_components/ace/ace.js"></script>
<script src="plugins/bower_components/ace/mode-css.js"></script>
<script src="plugins/bower_components/ace/mode-html.js"></script>
<script src="plugins/bower_components/ace/mode-javascript.js"></script>
<script src="plugins/bower_components/ace/theme-idle_fingers.js"></script>
<script src="plugins/bower_components/blockUI/jquery.blockUI.js"></script>
<script src="plugins/bower_components/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/bower_components/datatables-plugins/sorting/datetime-moment.js"></script>
<script src="plugins/bower_components/Magnific-Popup-master/dist/jquery.magnific-popup.min.js"></script>
<script src="plugins/bower_components/sweetalert/sweetalert.min.js?v=<?php echo $GLOBALS['fileHash']; ?>"></script>
<script src="plugins/bower_components/switchery/dist/switchery.min.js"></script>
<script src="js/tinycolor.min.js"></script>
<script src="plugins/bower_components/bootstrap-colorpicker-sliders/bootstrap.colorpickersliders.min.js"></script>
<script src="plugins/bower_components/dropzone-master/dist/dropzone.js"></script>
<script src="plugins/bower_components/owl.carousel/owl.carousel.min.js"></script>
<script src="plugins/bower_components/calendar/dist/fullcalendar.js"></script>
<script src="plugins/bower_components/custom-select/custom-select.min.js"></script>
<script src="plugins/bower_components/bootstrap-select/bootstrap-select.min.js"></script>
<script src="plugins/bower_components/tinymce/tinymce.min.js"></script>
<script src="plugins/bower_components/multiselect/js/jquery.multi-select.js"></script>
<script src="plugins/bower_components/mousetrap/mousetrap.min.js"></script>
<script src="plugins/bower_components/bootstrap-treeview-master/dist/bootstrap-treeview.min.js"></script>
<script src="js/jquery.mousewheel.min.js"></script>
<script src="js/ua-parser.min.js"></script>
<script src="js/plyr.js"></script>
<script src="js/simplebar.js"></script>
<script src="https://apis.google.com/js/client.js?onload=googleApiClientReady"></script>
<script src="js/functions.js?v=<?php echo $GLOBALS['fileHash']; ?>"></script>
<script src="js/custom.min.js?v=<?php echo $GLOBALS['fileHash']; ?>"></script>
<script id="custom-theme-javascript"></script>
<script id="custom-javascript"></script>
<script src="https://js.pusher.com/4.1/pusher.min.js"
        integrity="sha384-e9MoFh6Cw/uluf+NZ6MJwfJ1Dm7UOvJf9oTBxxCYDyStJeeAF0q53ztnEbLLDSQP"
        crossorigin="anonymous"></script>
<?php echo googleTracking(); ?>
<?php echo pluginFiles('js');
echo formKey(); ?>
</body>

</html>
