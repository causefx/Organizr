<?php
// Some PHP config stuff
ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);
// Include functions if not already included
require_once('functions.php');

// Upgrade environment
upgradeCheck();

// Lazyload settings
$databaseConfig = configLazy('config/config.php');

// Load USER
require_once("user.php");
qualifyUser("admin", true);
$USER = new User("registration_callback");

// Load Colours/Appearance
foreach(loadAppearance() as $key => $value) {
	$$key = $value;
}

$logs = getLogs();

?>

<!DOCTYPE html>

<html lang="en" class="no-js">
    <head>
       <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />

        <title><?=$title;?> Logs</title>

        <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="bower_components/mdi/css/materialdesignicons.min.css">
        <link rel="stylesheet" href="bower_components/metisMenu/dist/metisMenu.min.css">
        <link rel="stylesheet" href="bower_components/Waves/dist/waves.min.css"> 
        <link rel="stylesheet" href="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css"> 

        <link rel="stylesheet" href="js/selects/cs-select.css">
        <link rel="stylesheet" href="js/selects/cs-skin-elastic.css">
        <link href="bower_components/iconpick/dist/css/fontawesome-iconpicker.min.css" rel="stylesheet">
        <link rel="stylesheet" href="bower_components/google-material-color/dist/palette.css">
        <link rel="stylesheet" href="bower_components/sweetalert/dist/sweetalert.css">
        <link rel="stylesheet" href="bower_components/smoke/dist/css/smoke.min.css">

        <script src="js/menu/modernizr.custom.js"></script>
        <script type="text/javascript" src="js/sha1.js"></script>
        <script type="text/javascript" src="js/user.js"></script>
        <link rel="stylesheet" href="bower_components/animate.css/animate.min.css">
        <link rel="stylesheet" href="bower_components/DataTables/media/css/jquery.dataTables.css">
        <link rel="stylesheet" href="bower_components/datatables-tabletools/css/dataTables.tableTools.css">
        <link rel="stylesheet" href="bower_components/numbered/jquery.numberedtextarea.css">

        <link rel="stylesheet" href="css/style.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" href="css/settings.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" href="bower_components/summernote/dist/summernote.css">
        <link href="css/jquery.filer.css" rel="stylesheet">
	    <link href="css/jquery.filer-dragdropbox-theme.css" rel="stylesheet">

        <!--[if lt IE 9]>
        <script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
        <script src="bower_components/respondJs/dest/respond.min.js"></script>
        <![endif]-->
		
        <!--Scripts-->
        <script src="bower_components/jquery/dist/jquery.min.js"></script>
        <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
        <script src="bower_components/metisMenu/dist/metisMenu.min.js"></script>
        <script src="bower_components/Waves/dist/waves.min.js"></script>
        <script src="bower_components/moment/min/moment.min.js"></script>
        <script src="bower_components/jquery.nicescroll/jquery.nicescroll.min.js"></script>
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
		<style><?php customCSS(); ?></style>
    </head>
    <body class="scroller-body" style="padding: 0; overflow: hidden">
        <div id="main-wrapper" class="main-wrapper">
            <!--Content-->
            <div id="content"  style="margin:0 10px; overflow:hidden">
                <div class="big-box">
                    <div class="row">
                        <div class="col-lg-12">
                            <?php if( count($logs) < 5){?>
                            <div class="btn-group btn-group-justified gray-bg">
                                <?php foreach($logs as $k => $v){ ?>
                                <div class="btn-group" role="group">
                                    <button type="button" data-name="<?php echo $k; ?>" class="btn waves btn-info waves-effect waves-float log-link gray-bg"><?php echo $k; ?></button>
                                </div>
                                <?php } ?>
                                <div class="btn-group" role="group">
                                    <button type="button" data-name="All" class="btn waves btn-info waves-effect waves-float log-link gray-bg">Combined</button>
                                </div>
                                <div class="btn-group" role="group">
                                    <button type="button" data-name="All" class="btn waves btn-info waves-effect waves-float all-link gray-bg">Show All Logs</button>
                                </div>
                            </div>
                            <?php } ?>
                            <?php if( count($logs) >= 5){?>
                            <div class="btn-group pull-right" role="group" aria-label="...">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn waves btn-default dropdown-toggle waves-effect waves-float green-bg" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Choose Log <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu pull-right" style="position: fixed;right: 20px;top: 50px;">
                                        <?php foreach($logs as $k => $v){ ?>
                                        <li data-name="<?php echo $k; ?>" class="log-link"><a href="#"><?php echo $k; ?></a></li>
                                        <?php } ?>
                                        <li class="divider"></li>
                                        <li data-name="All" class="log-link"><a href="#">Combined</a></li>
                                        <li data-name="All" class="all-link"><a href="#">Show All Logs</a></li>
                                    </ul>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    <br/>
                    <div class="row">
                        <div class="col-lg-12">

                            <?php foreach($logs as $k => $v){ ?>
                            <div id="<?php echo $k;?>-table" class="table-responsive content-box" style="padding: 10px; display: none">
                            <h2><?php echo $k;?></h2>
                                <table id="datatable" class="datatable display">
                                    <thead>
                                        <tr>
                                            <th><?php echo $language->translate("LOG");?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                            <?php readExternalLog('single',$v); ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php }?>

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">                            
                            <div id="All-table" class="table-responsive" style="padding: 10px; display: none">
                            <h2>All Logs</h2>
                                <table id="datatable" class="datatable display">
                                    <thead>
                                        <tr>
                                            <th><?php echo $language->translate("SOURCE");?></th>
                                            <th><?php echo $language->translate("LOG");?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($logs as $k => $v){
                                        readExternalLog('all',$v,$k);
                                    }?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script>
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
    $(".scroller-body").niceScroll({
                railpadding: {top:0,right:0,left:0,bottom:0}
    });
    $('.log-link').click(function(e){
        var target = $(this).attr('data-name')+'-table';
        $('.table-responsive').hide();
        $('#'+target).show();
        console.log(target);
        e.preventDefault();
    });
    $('.all-link').click(function(e){
        $('.table-responsive').show();
        $('#All-table').hide();
        e.preventDefault();
    });
    $('#All-table').show();
    </script>
</html>
