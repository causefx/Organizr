<?php include 'api/functions/static-globals.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <meta content="Organizr - Accept no others" name="description">
    <meta content="CauseFX" name="author">
    <link href="plugins/images/favicon.png" rel="icon" sizes="16x16" type="image/png">
    <title>Organizr v2</title>
    <link href="bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="plugins/bower_components/sidebar-nav/dist/sidebar-nav.min.css" rel="stylesheet">
    <link href="plugins/bower_components/toast-master/css/jquery.toast.css" rel="stylesheet">
    <link href="plugins/bower_components/jquery-wizard-master/css/wizard.css" rel="stylesheet">
    <link href="plugins/bower_components/datatables/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>
    <link href="plugins/bower_components/jquery-wizard-master/libs/formvalidation/formValidation.min.css"
          rel="stylesheet">
    <link href="plugins/bower_components/Magnific-Popup-master/dist/magnific-popup.css" rel="stylesheet">
    <link href="plugins/bower_components/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">
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
    <link id="style" href="css/dark.css?v=<?php echo $GLOBALS['installedVersion']; ?>" rel="stylesheet">
    <link href="css/organizr.css?v=<?php echo $GLOBALS['installedVersion']; ?>" rel="stylesheet">
	<?php echo pluginFiles('css'); ?>
    <link id="theme" href="css/themes/Organizr.css?v=<?php echo $GLOBALS['installedVersion']; ?>" rel="stylesheet">
    <style id="user-appearance"></style>
    <style id="custom-css"></style>
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body class="fix-header">
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
                    <span class="hidden-xs" id="main-logo"></span>
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
        <div class="internal-listing p-0 hidden"></div>
        <div class="iFrame-listing p-0 hidden"></div>
    </div>
    <!-- ============================================================== -->
    <!-- End Page Content -->
    <!-- ============================================================== -->
</div>
<!-- /#wrapper -->
<!-- jQuery -->
<!--<script src="plugins/bower_components/jquery/dist/jquery.min.js"></script>-->
<script src="js/jquery-2.2.4.min.js"></script>
<script src="bootstrap/dist/js/bootstrap.min.js"></script>
<script src="plugins/bower_components/sidebar-nav/dist/sidebar-nav.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/waves.js"></script>
<script src="plugins/bower_components/toast-master/js/jquery.toast.js"></script>
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
<script src="plugins/bower_components/ace/theme-idle_fingers.js"></script>
<script src="plugins/bower_components/blockUI/jquery.blockUI.js"></script>
<script src="plugins/bower_components/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/bower_components/datatables-plugins/sorting/datetime-moment.js"></script>
<script src="plugins/bower_components/Magnific-Popup-master/dist/jquery.magnific-popup.min.js"></script>
<script src="plugins/bower_components/sweetalert/sweetalert.min.js"></script>
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
<script src="js/jquery.mousewheel.min.js"></script>
<script src="js/plyr.js"></script>
<script src="js/simplebar.js"></script>
<script src="https://apis.google.com/js/client.js?onload=googleApiClientReady"></script>
<script src="js/functions.js?v=<?php echo $GLOBALS['installedVersion']; ?>"></script>
<script src="js/custom.js?v=<?php echo $GLOBALS['installedVersion']; ?>"></script>
<?php echo pluginFiles('js'); ?>
</body>

</html>
