<?php 

$data = false;

ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);

require_once("user.php");
require_once("functions.php");
$USER = new User("registration_callback");
require_once("translate.php");

if(!$USER->authenticated) :

    die("Why you trying to access this without logging in?!?!");

elseif($USER->authenticated && $USER->role !== "admin") :

    die("C'mon man!  I give you access to my stuff and now you're trying to get in the back door?");

endif;

$dbfile = DATABASE_LOCATION  . constant('User::DATABASE_NAME') . ".db";
$databaseLocation = "databaseLocation.ini.php";
$homepageSettings = "homepageSettings.ini.php";
$userdirpath = USER_HOME;
$userdirpath = substr_replace($userdirpath, "", -1);

$file_db = new PDO("sqlite:" . $dbfile);
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$getUsers = $file_db->query('SELECT * FROM users');
$gotUsers = $file_db->query('SELECT * FROM users');

$dbTab = $file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="tabs"');
$dbOptions = $file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="options"');

$tabSetup = "Yes";
$hasOptions = "No";

foreach($dbTab as $row) :

    if (in_array("tabs", $row)) :
    
        $tabSetup = "No";
    
    endif;

endforeach;

foreach($dbOptions as $row) :

    if (in_array("options", $row)) :
    
        $hasOptions = "Yes";
    
    endif;

endforeach;

if($hasOptions == "No") :

    $title = "Organizr";
    $topbar = "#333333"; 
    $topbartext = "#66D9EF";
    $bottombar = "#333333";
    $sidebar = "#393939";
    $hoverbg = "#AD80FD";
    $activetabBG = "#F92671";
    $activetabicon = "#FFFFFF";
    $activetabtext = "#FFFFFF";
    $inactiveicon = "#66D9EF";
    $inactivetext = "#66D9EF";
    $loading = "#66D9EF";
    $hovertext = "#000000";

endif;

if($tabSetup == "No") :

    $result = $file_db->query('SELECT * FROM tabs');
    
endif;

if($hasOptions == "Yes") :

    $resulto = $file_db->query('SELECT * FROM options');
    
endif;

if($hasOptions == "Yes") : 
                                    
    foreach($resulto as $row) : 

        $title = isset($row['title']) ? $row['title'] : "Organizr";
        $topbartext = isset($row['topbartext']) ? $row['topbartext'] : "#66D9EF";
        $topbar = isset($row['topbar']) ? $row['topbar'] : "#333333";
        $bottombar = isset($row['bottombar']) ? $row['bottombar'] : "#333333";
        $sidebar = isset($row['sidebar']) ? $row['sidebar'] : "#393939";
        $hoverbg = isset($row['hoverbg']) ? $row['hoverbg'] : "#AD80FD";
        $activetabBG = isset($row['activetabBG']) ? $row['activetabBG'] : "#F92671";
        $activetabicon = isset($row['activetabicon']) ? $row['activetabicon'] : "#FFFFFF";
        $activetabtext = isset($row['activetabtext']) ? $row['activetabtext'] : "#FFFFFF";
        $inactiveicon = isset($row['inactiveicon']) ? $row['inactiveicon'] : "#66D9EF";
        $inactivetext = isset($row['inactivetext']) ? $row['inactivetext'] : "#66D9EF";
        $loading = isset($row['loading']) ? $row['loading'] : "#66D9EF";
        $hovertext = isset($row['hovertext']) ? $row['hovertext'] : "#000000";

    endforeach;

endif;

$action = "";
                
if(isset($_POST['action'])) :

    $action = $_POST['action'];
    
endif;

if($action == "deleteDB") : 
                     
    unset($_COOKIE['Organizr']);
    setcookie('Organizr', '', time() - 3600, '/');
    unset($_COOKIE['OrganizrU']);
    setcookie('OrganizrU', '', time() - 3600, '/');

    $file_db = null;

    unlink($dbfile); 

    foreach(glob($userdirpath . '/*') as $file) : 

        if(is_dir($file)) :

            rmdir($file); 

        elseif(!is_dir($file)) :

            unlink($file);

        endif;

    endforeach; 

    rmdir($userdirpath);

   echo "<script>window.parent.location.reload();</script>";

endif;

if($action == "deleteLog") : 
                     
    unlink(FAIL_LOG); 

   echo "<script type='text/javascript'>window.location.replace('settings.php');</script>";

endif;

if($action == "upgrade") : 
                     
    function downloadFile($url, $path){

        $folderPath = "upgrade/";

        if(!mkdir($folderPath)) : echo "can't make dir"; endif;

        $newfname = $folderPath . $path;

        $file = fopen ($url, 'rb');

        if ($file) {

            $newf = fopen ($newfname, 'wb');

            if ($newf) {

                while(!feof($file)) {

                    fwrite($newf, fread($file, 1024 * 8), 1024 * 8);

                }

            }

        }

        if ($file) {

            fclose($file);

        }

        if ($newf) {

            fclose($newf);

        }

    }

    function unzipFile($zipFile){

        $zip = new ZipArchive;

        $extractPath = "upgrade/";

        if($zip->open($extractPath . $zipFile) != "true"){

            echo "Error :- Unable to open the Zip File";
        }

        /* Extract Zip File */
        $zip->extractTo($extractPath);
        $zip->close();

    }

    // Function to remove folders and files 
    function rrmdir($dir) {

        if (is_dir($dir)) {

            $files = scandir($dir);

            foreach ($files as $file)

                if ($file != "." && $file != "..") rrmdir("$dir/$file");

            rmdir($dir);

        }

        else if (file_exists($dir)) unlink($dir);

    }

    // Function to Copy folders and files       
    function rcopy($src, $dst) {

        if (is_dir ( $src )) {

            if (!file_exists($dst)) : mkdir ( $dst ); endif;

            $files = scandir ( $src );

            foreach ( $files as $file )

                if ($file != "." && $file != "..")

                    rcopy ( "$src/$file", "$dst/$file" );

        } else if (file_exists ( $src ))

            copy ( $src, $dst );

    }

    $url = "https://github.com/causefx/Organizr/archive/master.zip";

    $file = "upgrade.zip";

    $source = __DIR__ . "/upgrade/Organizr-master/";

    $cleanup = __DIR__ . "/upgrade/";

    $destination = __DIR__ . "/";

    downloadFile($url, $file);
    unzipFile($file);

    rcopy($source, $destination);
    rrmdir($cleanup);

    echo "<script>top.location.href = 'index.php#upgrade';</script>";

endif;

if($action == "createLocation") :

    $databaseData = '; <?php die("Access denied"); ?>' . "\r\n";

    foreach ($_POST as $postName => $postValue) {
            
        if($postName !== "action") :
        
            if(substr($postValue, -1) == "/") : $postValue = rtrim($postValue, "/"); endif;
        
            $databaseData .= $postName . " = \"" . $postValue . "\"\r\n";
        
        endif;
        
    }

    write_ini_file($databaseData, $databaseLocation);

    echo "<script>window.parent.location.reload(true);</script>";

endif;

if($action == "homepageSettings") :

    $homepageData = '; <?php die("Access denied"); ?>' . "\r\n";

    foreach ($_POST as $postName => $postValue) {
            
        if($postName !== "action") :
        
            if(substr($postValue, -1) == "/") : $postValue = rtrim($postValue, "/"); endif;
        
            $homepageData .= $postName . " = \"" . $postValue . "\"\r\n";
        
        endif;
        
    }

    write_ini_file($homepageData, $homepageSettings);

    echo "<script>window.parent.location.reload(true);</script>";

endif;
                
if(!isset($_POST['op'])) :

    $_POST['op'] = "";
    
endif; 

if($action == "addTabz") :
    
    if($tabSetup == "No") :

        $file_db->exec("DELETE FROM tabs");
        
    endif;
    
    if($tabSetup == "Yes") :
    
        $file_db->exec("CREATE TABLE tabs (name TEXT UNIQUE, url TEXT, defaultz TEXT, active TEXT, user TEXT, guest TEXT, icon TEXT, iconurl TEXT, window TEXT)");
        
    endif;

    $addTabName = array();
    $addTabUrl = array();
    $addTabIcon = array();
    $addTabIconUrl = array();
    $addTabDefault = array();
    $addTabActive = array();
    $addTabUser = array();
    $addTabGuest = array();
    $addTabWindow = array();
    $buildArray = array();

    foreach ($_POST as $key => $value) :
    
        $trueKey = explode('-', $key);
        
        if ($value == "on") :
        
            $value = "true";
            
        endif;
        
        if($trueKey[0] == "name"):
            
            array_push($addTabName, $value);
            
        endif;
        
        if($trueKey[0] == "url"):
            
            array_push($addTabUrl, $value);
            
        endif;
        
        if($trueKey[0] == "icon"):
            
            array_push($addTabIcon, $value);
            
        endif;

        if($trueKey[0] == "iconurl"):
            
            array_push($addTabIconUrl, $value);
            
        endif;
        
        if($trueKey[0] == "default"):
            
            array_push($addTabDefault, $value);
            
        endif;
        
        if($trueKey[0] == "active"):
            
            array_push($addTabActive, $value);
            
        endif;
        
        if($trueKey[0] == "user"):
            
            array_push($addTabUser, $value);
            
        endif;
        
        if($trueKey[0] == "guest"):
            
            array_push($addTabGuest, $value);
            
        endif; 

        if($trueKey[0] == "window"):
            
            array_push($addTabWindow, $value);
            
        endif;  
        
    endforeach;

    $tabArray = 0;
    
    if(count($addTabName) > 0) : 
        
        foreach(range(1,count($addTabName)) as $index) :
        
            if(!isset($addTabDefault[$tabArray])) :
                
                $tabDefault = "false";
            
            else :
                
                $tabDefault = $addTabDefault[$tabArray];
            
            endif;
            
            $buildArray[] = array('name' => $addTabName[$tabArray],
                  'url' => $addTabUrl[$tabArray],
                  'defaultz' => $tabDefault,
                  'active' => $addTabActive[$tabArray],
                  'user' => $addTabUser[$tabArray],
                  'guest' => $addTabGuest[$tabArray],
                  'icon' => $addTabIcon[$tabArray],
                  'window' => $addTabWindow[$tabArray],
                  'iconurl' => $addTabIconUrl[$tabArray]);

            $tabArray++;
        
        endforeach;
        
    endif; 
    
    $insert = "INSERT INTO tabs (name, url, defaultz, active, user, guest, icon, iconurl, window) 
                VALUES (:name, :url, :defaultz, :active, :user, :guest, :icon, :iconurl, :window)";
                
    $stmt = $file_db->prepare($insert);
    
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':url', $url);
    $stmt->bindParam(':defaultz', $defaultz);
    $stmt->bindParam(':active', $active);
    $stmt->bindParam(':user', $user);
    $stmt->bindParam(':guest', $guest);
    $stmt->bindParam(':icon', $icon);
    $stmt->bindParam(':iconurl', $iconurl);
    $stmt->bindParam(':window', $window);
    
    foreach ($buildArray as $t) :
    
        $name = $t['name'];
        $url = $t['url'];
        $defaultz = $t['defaultz'];
        $active = $t['active'];
        $user = $t['user'];
        $guest = $t['guest'];
        $icon = $t['icon'];
        $iconurl = $t['iconurl'];
        $window = $t['window'];

        $stmt->execute();
        
    endforeach;
    
endif;

if($action == "addOptionz") :
    
    if($hasOptions == "Yes") :
    
        $file_db->exec("DELETE FROM options");
        
    endif;
    
    if($hasOptions == "No") :

        $file_db->exec("CREATE TABLE options (title TEXT UNIQUE, topbar TEXT, bottombar TEXT, sidebar TEXT, hoverbg TEXT, topbartext TEXT, activetabBG TEXT, activetabicon TEXT, activetabtext TEXT, inactiveicon TEXT, inactivetext TEXT, loading TEXT, hovertext TEXT)");
        
    endif;
            
    $title = $_POST['title'];
    $topbartext = $_POST['topbartext'];
    $topbar = $_POST['topbar'];
    $bottombar = $_POST['bottombar'];
    $sidebar = $_POST['sidebar'];
    $hoverbg = $_POST['hoverbg'];
    $hovertext = $_POST['hovertext'];
    $activetabBG = $_POST['activetabBG'];
    $activetabicon = $_POST['activetabicon'];
    $activetabtext = $_POST['activetabtext'];
    $inactiveicon = $_POST['inactiveicon'];
    $inactivetext = $_POST['inactivetext'];
    $loading = $_POST['loading'];

    $insert = "INSERT INTO options (title, topbartext, topbar, bottombar, sidebar, hoverbg, activetabBG, activetabicon, activetabtext, inactiveicon, inactivetext, loading, hovertext) 
                VALUES (:title, :topbartext, :topbar, :bottombar, :sidebar, :hoverbg, :activetabBG, :activetabicon , :activetabtext , :inactiveicon, :inactivetext, :loading, :hovertext)";
                
    $stmt = $file_db->prepare($insert);
    
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':topbartext', $topbartext);
    $stmt->bindParam(':topbar', $topbar);
    $stmt->bindParam(':bottombar', $bottombar);
    $stmt->bindParam(':sidebar', $sidebar);
    $stmt->bindParam(':hoverbg', $hoverbg);
    $stmt->bindParam(':activetabBG', $activetabBG);
    $stmt->bindParam(':activetabicon', $activetabicon);
    $stmt->bindParam(':activetabtext', $activetabtext);
    $stmt->bindParam(':inactiveicon', $inactiveicon);
    $stmt->bindParam(':inactivetext', $inactivetext);
    $stmt->bindParam(':loading', $loading);
    $stmt->bindParam(':hovertext', $hovertext);

    $stmt->execute();

endif;
?>

<!DOCTYPE html>

<html lang="en" class="no-js">

    <head>
        
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />

        <title>Settings</title>

        <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
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

        <link rel="stylesheet" href="css/style.css">
        <link href="css/jquery.filer.css" rel="stylesheet">
	    <link href="css/jquery.filer-dragdropbox-theme.css" rel="stylesheet">

        <!--[if lt IE 9]>
        <script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
        <script src="bower_components/respondJs/dest/respond.min.js"></script>
        <![endif]-->
        
    </head>

    <body class="scroller-body" style="padding: 0; background: #273238; overflow: hidden">
        
        <style>

            input.form-control.material.icp-auto.iconpicker-element.iconpicker-input {
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
}<?php if(CUSTOMCSS == "true") : 
$template_file = "custom.css";
$file_handle = fopen($template_file, "rb");
echo fread($file_handle, filesize($template_file));
fclose($file_handle);
echo "\n";
endif; ?>
       
        </style>
       
        <div id="main-wrapper" class="main-wrapper">

            <!--Content-->
            <div id="content"  style="margin:0 20px; overflow:hidden">
 
                <br/>
                
                <div id="versionCheck"></div>       
            
                <div class="row">
                
                    <div class="col-lg-12">
                  
                        <div class="tabbable tabs-with-bg" id="eighth-tabs">
                    
                            <ul id="settingsTabs" class="nav nav-tabs" style="background: #C0C0C0">
                      
                                <li class="active">
                        
                                    <a href="#tab-tabs" data-toggle="tab"><i class="fa fa-list gray"></i></a>
                      
                                </li>
                      
                                <li>
                        
                                    <a href="#customedit" data-toggle="tab"><i class="fa fa-paint-brush green"></i></a>
                      
                                </li>
                      
                                <li>
                        
                                    <a href="#useredit" data-toggle="tab"><i class="fa fa-user red"></i></a>
                     
                                </li>
                                
                                <li>
                        
                                    <a href="#loginlog" data-toggle="tab"><i class="fa fa-file-text-o indigo"></i></a>
                     
                                </li>
                                
                                <li>
                        
                                    <a href="#systemSettings" data-toggle="tab"><i class="fa fa-cog gray"></i></a>
                     
                                </li>
                                
                                <li>
                        
                                    <a href="#homepageSettings" data-toggle="tab"><i class="fa fa-home green"></i></a>
                     
                                </li>
                                
                                <li>
                        
                                    <a href="#about" data-toggle="tab"><i class="fa fa-info red-orange"></i></a>
                     
                                </li>
                                
                                <?php if($action) : ?>
                                        
                                        <button id="apply" style="margin: 8px" class="btn waves btn-labeled btn-success btn-sm pull-right text-uppercase waves-effect waves-float animated tada" type="submit">
                                        
                                            <span class="btn-label"><i class="fa fa-check"></i></span><?php echo $language->translate("APPLY_CHANGES");?>
                                        
                                        </button>
                                        
                                        <?php endif; ?>
    
                            </ul>
                    
                            <div class="tab-content" style="overflow: auto">
                      
                                <div class="big-box todo-list tab-pane big-box  fade in active" id="tab-tabs">

                                    <div class="sort-todo">

                                        <a class="total-tabs"><?php echo $language->translate("TABS");?> <span class="badge gray-bg"></span></a>
                                        
                                        <button id="iconHide" type="button" class="btn waves btn-labeled btn-success btn-sm text-uppercase waves-effect waves-float">
                                            
                                            <span class="btn-label"><i class="fa fa-upload"></i></span><?php echo $language->translate("UPLOAD_ICONS");?>
                                            
                                        </button>
                                        
                                        <button id="iconAll" type="button" class="btn waves btn-labeled btn-success btn-sm text-uppercase waves-effect waves-float">
                                            
                                            <span class="btn-label"><i class="fa fa-picture-o"></i></span><?php echo $language->translate("VIEW_ICONS");?>
                                            
                                        </button>

                                    </div>

                                    <input type="file" name="files[]" id="uploadIcons" multiple="multiple">
                                    
                                    <div id="viewAllIcons" style="display: none;">
                                        
                                        <h4><strong><?php echo $language->translate("ALL_ICONS");?></strong> [<?php echo $language->translate("CLICK_ICON");?>]</h4>
                                        
                                        <div class="row">
                                            
                                            <textarea id="copyTarget" class="hideCopy" style="left: -9999px; top: 0; position: absolute;"></textarea>                                           
                                            <?php
                                            $dirname = "images/";
                                            $images = scandir($dirname);
                                            $ignore = Array(".", "..", "favicon/", "favicon", "._.DS_Store", ".DS_Store", "confused.png", "sowwy.png", "sort-btns", "loading.png", "titlelogo.png", "default.svg", "login.png", "themes", "nadaplaying.jpg");
                                            foreach($images as $curimg){
                                                if(!in_array($curimg, $ignore)) { ?>

                                            <div class="col-xs-2" style="width: 75px; height: 75px; padding-right: 0px;">    
                                            
                                                <a data-toggle="tooltip" data-placement="bottom" title="<?=$dirname.$curimg;?>" class="thumbnail" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">

                                                    <img style="width: 50px; height: 50px;" src="<?=$dirname.$curimg;?>" alt="thumbnail" class="allIcons">

                                                </a>
                                                
                                            </div>

                                            <?php } } ?>

                                        </div>
                                        
                                    </div>
                                    
                                    <form id="add_tab" method="post">

                                        <div class="form-group add-tab">

                                            <div class="input-group">

                                                <div class="input-group-addon">

                                                    <i class="fa fa-pencil gray"></i>

                                                </div>

                                                <input type="text" class="form-control name-of-todo" placeholder="<?php echo $language->translate("TYPE_HIT_ENTER");?>" style="border-top-left-radius: 0;
    border-bottom-left-radius: 0;">

                                            </div>

                                        </div>

                                    </form>

                                    <div class="panel">

                                        <form id="submitTabs" method="post">
                                        
                                            <div class="panel-body todo">

                                                <input type="hidden" name="action" value="addTabz" />

                                                <ul class="list-group ui-sortable">

                                                    <?php if($tabSetup == "No") : $tabNum = 1; 

                                                    foreach($result as $row) : 

                                                    if($row['defaultz'] == "true") : $default = "checked"; else : $default = ""; endif;
                                                    if($row['active'] == "true") : $activez = "checked"; else : $activez = ""; endif;
                                                    if($row['guest'] == "true") : $guestz = "checked"; else : $guestz = ""; endif;
                                                    if($row['user'] == "true") : $userz = "checked"; else : $userz = ""; endif;
                                                    if($row['window'] == "true") : $windowz = "checked"; else : $windowz = ""; endif;
                                                    if($row['iconurl'] != "") : $backgroundListImage = "background-image: url('". $row['iconurl'] . "') !important; background-repeat: no-repeat !important; background-position: left !important; background-blend-mode: difference !important; background-size: 50px 50px !important"; else : $backgroundListImage = ""; endif;
                                                    
                                                    ?>
                                                    <li id="item-<?=$tabNum;?>" class="list-group-item" style="position: relative; left: 0px; top: 0px;">

                                                        <tab class="content-form form-inline">

                                                            <div class="form-group">

                                                                <div class="action-btns tabIconView" style="width:calc(100%)">
                                                                    
                                                                    <?php if($backgroundListImage == "") : ?>
                                                                    <a class="" style="margin-left: 0px"><span style="font: normal normal normal 30px/1 FontAwesome;" class="fa fa-hand-paper-o"></span></a>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if($backgroundListImage != "") : ?>
                                                                    <a class="" style="margin-left: 0px"><span style="display: none; font: normal normal normal 30px/1 FontAwesome;" class="fa fa-hand-paper-o"></span></a>
                                                                    <a class="" style="margin-left: 0px"><span style=""><img style="height: 30px; width: 30px" src="<?=$row['iconurl']?>"></span></a>
                                                                    
                                                                    <?php endif; ?>

                                                                </div>

                                                            </div>

                                                            <div class="form-group">

                                                                <input style="width: 100%;" type="text" class="form-control material input-sm" id="name-<?=$tabNum;?>" name="name-<?=$tabNum;?>" placeholder="<?php echo $language->translate("NEW_TAB_NAME");?>" value="<?=$row['name'];?>">

                                                            </div>

                                                            <div class="form-group">

                                                                <input style="width: 100%;" type="text" class="form-control material input-sm" id="url-<?=$tabNum;?>" name="url-<?=$tabNum;?>" placeholder="<?php echo $language->translate("TAB_URL");?>" value="<?=$row['url']?>">

                                                            </div>

                                                            <div style="margin-right: 5px;" class="form-group">

                                                                <div class="input-group">
                                                                    <input data-placement="bottomRight" class="form-control material icp-auto" name="icon-<?=$tabNum;?>" value="<?=$row['icon'];?>" type="text" />
                                                                    <span class="input-group-addon"></span>
                                                                </div>
                                                                
                                                                - <?php echo $language->translate("OR");?> -

                                                            </div>
                                                            
                                                            <div class="form-group">

                                                                <input style="width: 100%;" type="text" class="form-control material input-sm" id="iconurl-<?=$tabNum;?>" name="iconurl-<?=$tabNum;?>" placeholder="<?php echo $language->translate("ICON_URL");?>" value="<?=$row['iconurl']?>">

                                                            </div>

                                                            <div class="form-group">

                                                                <div class="radio radio-danger">


                                                                    <input type="radio" id="default[<?=$tabNum;?>]" value="true" name="default" <?=$default;?>>
                                                                    <label for="default[<?=$tabNum;?>]"><?php echo $language->translate("DEFAULT");?></label>

                                                                </div>

                                                            </div>

                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-success" value="false" name="active-<?=$tabNum;?>" type="hidden">
                                                                    <input id="active[<?=$tabNum;?>]" class="switcher switcher-success" name="active-<?=$tabNum;?>" type="checkbox" <?=$activez;?>>

                                                                    <label for="active[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                <?php echo $language->translate("ACTIVE");?>
                                                            </div>

                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-primary" value="false" name="user-<?=$tabNum;?>" type="hidden">
                                                                    <input id="user[<?=$tabNum;?>]" class="switcher switcher-primary" name="user-<?=$tabNum;?>" type="checkbox" <?=$userz;?>>
                                                                    <label for="user[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                <?php echo $language->translate("USER");?>
                                                            </div>

                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-primary" value="false" name="guest-<?=$tabNum;?>" type="hidden">
                                                                    <input id="guest[<?=$tabNum;?>]" class="switcher switcher-warning" name="guest-<?=$tabNum;?>" type="checkbox" <?=$guestz;?>>
                                                                    <label for="guest[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                <?php echo $language->translate("GUEST");?>
                                                            </div>
                                                            
                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-primary" value="false" name="window-<?=$tabNum;?>" type="hidden">
                                                                    <input id="window[<?=$tabNum;?>]" class="switcher switcher-danger" name="window-<?=$tabNum;?>" type="checkbox" <?=$windowz;?>>
                                                                    <label for="window[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                <?php echo $language->translate("NO_IFRAME");?>
                                                            </div>

                                                            <div class="pull-right action-btns" style="padding-top: 8px;">

                                                                <a class="trash"><span class="fa fa-trash"></span></a>

                                                            </div>


                                                        </tab>

                                                    </li>
                                                    <?php $tabNum ++; endforeach; endif;?>

                                                </ul>

                                            </div>

                                            <div class="checkbox clear-todo pull-left"></div>

                                            <button style="margin-top: 5px;" class="btn waves btn-labeled btn-success btn-sm pull-right text-uppercase waves-effect waves-float" type="submit">
                                                
                                                <span class="btn-label"><i class="fa fa-floppy-o"></i></span><?php echo $language->translate("SAVE_TABS");?>
                                                
                                            </button>
                                            
                                        </form>
                                        
                                    </div>
 
                                </div>

                                <div class="tab-pane big-box  fade in" id="useredit">
                                    
                                    <div class="row">
                                        
                                        <div class="col-lg-12">
                                          
                                            <div class="big-box">
                                            
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
                                                    
                                                    
                                                    
                                                    <!--<button type="submit" onclick="User.processRegistration()" class="btn btn-primary btn-icon waves waves-circle waves-effect waves-float"><i class="fa fa-user-plus"></i></button>-->
                                                    
                                                    <button type="submit" onclick="User.processRegistration()" class="btn waves btn-labeled btn-primary btn btn-sm text-uppercase waves-effect waves-float promoteUser">

                                                        <span class="btn-label"><i class="fa fa-user-plus"></i></span><?php echo $language->translate("CREATE_USER");?>

                                                    </button>

                                                </form>               
                                          
                                            </div>
                                        
                                        </div>
                                      
                                    </div>
                                    
                                    <div class="big-box">
                                        
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
                                                            
                                                            <td><?=$row['email'];?></td>

                                                            <td><span class="label label-<?=$userActiveColor;?>"><?=$userActive;?></span></td>

                                                            <td><?=$lastActive;?></td>

                                                            <td><span class="text-uppercase <?=$userColor;?>"><?=$row['role'];?></span></td>

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
                                
                                <div class="tab-pane big-box  fade in" id="systemSettings">
                                    
                                    <div class="row">
                                        
                                        <div class="col-lg-12">
                                          
                                            <div class="big-box">
                                            
                                                <form class="content-form" name="systemSettings" id="systemSettings" action="" method="POST">
                        								    
                                                    <input type="hidden" name="action" value="createLocation" />

                                                    <div class="form-group">

                                                        <input type="text" class="form-control material input-sm" name="databaseLocation" placeholder="<?php echo $language->translate("DATABASE_PATH");?>" autocorrect="off" autocapitalize="off" value="<?php echo DATABASE_LOCATION;?>">
                                                        <p class="help-text"><?php echo $language->translate("DATABASE_PATH");?></p>

                                                    </div>

                                                    <div class="form-group">

                                                        <?php echo gotTimezone();?>
                                                        <p class="help-text"><?php echo $language->translate("SET_TIMEZONE");?></p>

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="text" class="form-control material input-sm" name="titleLogo" placeholder="<?php echo $language->translate("LOGO_URL_TITLE");?>" value="<?php echo TITLELOGO;?>">
                                                        <p class="help-text"><?php echo $language->translate("LOGO_URL_TITLE");?></p>

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="text" class="form-control material input-sm" name="loadingIcon" placeholder="<?php echo $language->translate("LOADING_ICON_URL");?>" value="<?php echo LOADINGICON;?>">
                                                        <p class="help-text"><?php echo $language->translate("LOADING_ICON_URL");?></p>

                                                    </div>
                                                    
                                                    <div class="form-group">

                                                        <input type="text" class="form-control material input-sm" name="cookiePassword" placeholder="<?php echo $language->translate("COOKIE_PASSWORD");?>" value="<?php echo COOKIEPASSWORD;?>">
                                                        <p class="help-text"><?php echo $language->translate("COOKIE_PASSWORD");?></p>

                                                    </div>
                                                    
                                                    <div class="form-group">

                                                        <input type="text" class="form-control material input-sm" name="registerPassword" placeholder="<?php echo $language->translate("REGISTER_PASSWORD");?>" value="<?php echo REGISTERPASSWORD;?>">
                                                        <p class="help-text"><?php echo $language->translate("REGISTER_PASSWORD");?></p>

                                                    </div>
                                                    
                                                    <div class="content-form form-inline">
                                                        
                                                        <div class="form-group">

                                                        <?php 
                                                        
                                                        if($notifyExplode[1] == "slidetop") : $slidetopActive = "selected"; else : $slidetopActive = ""; endif;
                                                        if($notifyExplode[1] == "exploader") : $exploaderActive = "selected"; else : $exploaderActive = ""; endif;
                                                        if($notifyExplode[1] == "flip") : $flipActive = "selected"; else : $flipActive = ""; endif;
                                                        if($notifyExplode[1] == "bouncyflip") : $bouncyflipActive = "selected"; else : $bouncyflipActive = ""; endif;
                                                        if($notifyExplode[1] == "scale") : $scaleActive = "selected"; else : $scaleActive = ""; endif;
                                                        if($notifyExplode[1] == "genie") : $genieActive = "selected"; else : $genieActive = ""; endif;
                                                        if($notifyExplode[1] == "jelly") : $jellyActive = "selected"; else : $jellyActive = ""; endif;
                                                        if($notifyExplode[1] == "slide") : $slideActive = "selected"; else : $slideActive = ""; endif;
                                                        if($notifyExplode[1] == "boxspinner") : $boxspinnerActive = "selected"; else : $boxspinnerActive = ""; endif;
                                                        if($notifyExplode[1] == "thumbslider") : $thumbsliderActive = "selected"; else : $thumbsliderActive = ""; endif;
                                                        
                                                        ?>
                                                            <select id="notifyValue" name="notifyEffect" id="notifyEffect" class="form-control material input-sm" required>

                                                                <option value="bar-slidetop" <?=$slidetopActive;?>>Slide From Top</option>
                                                                <option value="bar-exploader" <?=$exploaderActive;?>>Exploader From Top</option>
                                                                <option value="attached-flip" <?=$flipActive;?>>Flip</option>
                                                                <option value="attached-bouncyflip" <?=$bouncyflipActive;?>>Bouncy Flip</option>
                                                                <option value="growl-scale" <?=$scaleActive;?>>Growl Scale</option>
                                                                <option value="growl-genie" <?=$genieActive;?>>Growl Genie</option>
                                                                <option value="growl-jelly" <?=$jellyActive;?>>Growl Jelly</option>
                                                                <option value="growl-slide" <?=$slideActive;?>>Growl Slide</option>
                                                                <option value="other-boxspinner" <?=$boxspinnerActive;?>>Spinning Box</option>
                                                                <option value="other-thumbslider" <?=$thumbsliderActive;?>>Sliding</option>

                                                            </select>

                                                            <button id="notifyTest" type="button" class="class='btn waves btn-labeled btn-success btn btn-sm text-uppercase waves-effect waves-float"><span class="btn-label"><i class="fa fa-flask"></i></span><?php echo $language->translate("TEST");?></button>

                                                            <p class="help-text"><?php echo $language->translate("NOTIFICATION_TYPE");?></p>

                                                        </div>
                                                    
                                                    </div>
                                                    
                                                    <div class="content-form form-inline">
                                                    
                                                        <div class="form-group">
                                                            <?php  if(MULTIPLELOGIN == "true") : $multipleLogin = "checked"; else : $multipleLogin = ""; endif;?>
                                                            <input id="" class="switcher switcher-success" value="false" name="multipleLogin" type="hidden">
                                                            <input id="multipleLogin" class="switcher switcher-success" value="true" name="multipleLogin" type="checkbox" <?php echo $multipleLogin;?>>

                                                            <label for="multipleLogin"></label><?php echo $language->translate("MULTIPLE_LOGINS");?>

                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <?php  if(LOADINGSCREEN == "true") : $loadingScreen = "checked"; else : $loadingScreen = ""; endif;?>
                                                            <input id="" class="switcher switcher-success" value="false" name="loadingScreen" type="hidden">
                                                            <input id="loadingScreen" class="switcher switcher-success" value="true" name="loadingScreen" type="checkbox" <?php echo $loadingScreen;?>>

                                                            <label for="loadingScreen"></label><?php echo $language->translate("ENABLE_LOADING_SCREEN");?>

                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <?php  if(ENABLEMAIL == "true") : $enableMail = "checked"; else : $enableMail = ""; endif;?>
                                                            <input id="" class="switcher switcher-success" value="false" name="enableMail" type="hidden">
                                                            <input id="enableMail" class="switcher switcher-success" value="true" name="enableMail" type="checkbox" <?php echo $enableMail;?>>

                                                            <label for="enableMail"></label><?php echo $language->translate("ENABLE_MAIL");?>

                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <?php  if(SLIMBAR == "true") : $enableSlimBar = "checked"; else : $enableSlimBar = ""; endif;?>
                                                            <input id="" class="switcher switcher-success" value="false" name="slimBar" type="hidden">
                                                            <input id="slimBar" class="switcher switcher-success" value="true" name="slimBar" type="checkbox" <?php echo $enableSlimBar;?>>

                                                            <label for="slimBar"></label><?php echo $language->translate("ENABLE_SLIMBAR");?>

                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <?php  if(GRAVATAR == "true") : $enableGravatar = "checked"; else : $enableGravatar = ""; endif;?>
                                                            <input id="" class="switcher switcher-success" value="false" name="gravatar" type="hidden">
                                                            <input id="gravatar" class="switcher switcher-success" value="true" name="gravatar" type="checkbox" <?php echo $enableGravatar;?>>

                                                            <label for="gravatar"></label><?php echo $language->translate("GRAVATAR");?>

                                                        </div>
                                                        
                                                    </div>
                                                    
                                                    <button type="submit" class="class='btn waves btn-labeled btn-success btn btn-sm pull-right text-uppercase waves-effect waves-float"><span class="btn-label"><i class="fa fa-floppy-o"></i></span>Save</button>

                                                </form>               
                                          
                                            </div>
                                        
                                        </div>
                                      
                                    </div>

                                </div>
                                
                                <div class="tab-pane big-box  fade in" id="homepageSettings">
                                    
                                    <div class="row">
                                        
                                        
                                        <div class="col-lg-12">
                                            
                                            <form class="content-form" name="homepageSettings" id="homepageSettings" action="" method="POST">    
                                            
                                                <div class="tabbable tabs-with-bg" id="homepage-tabs">
                                                                                                    
                                                    <input type="hidden" name="action" value="homepageSettings" />
                                                
                                                    <ul class="nav nav-tabs apps">

                                                        <li class="apps active">

                                                            <a href="#tab-plex" data-toggle="tab" aria-expanded="true"><img style="height:40px; width:40px;" src="images/plex.png"></a>

                                                        </li>

                                                        <li class="apps ">

                                                            <a href="#tab-sonarr" data-toggle="tab" aria-expanded="false"><img style="height:40px; width:40px;" src="images/sonarr.png"></a>

                                                        </li>

                                                        <li class="apps ">

                                                            <a href="#tab-radarr" data-toggle="tab" aria-expanded="false"><img style="height:40px; width:40px;" src="images/radarr.png"></a>

                                                        </li>
                                                        
                                                        <li class="apps ">

                                                            <a href="#tab-nzbget" data-toggle="tab" aria-expanded="false"><img style="height:40px; width:40px;" src="images/nzbget.png"></a>

                                                        </li>
                                                        
                                                        <li class="apps ">

                                                            <a href="#tab-sabnzbd" data-toggle="tab" aria-expanded="false"><img style="height:40px; width:40px;" src="images/sabnzbd.png"></a>

                                                        </li>
                                                        
                                                        <li class="apps ">

                                                            <a href="#tab-headphones" data-toggle="tab" aria-expanded="false"><img style="height:40px; width:40px;" src="images/headphones.png"></a>

                                                        </li>

                                                    </ul>

                                                    <div class="clearfix"></div>

                                                    <div class="tab-content">

                                                        <div class="tab-pane big-box fade active in" id="tab-plex">

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="plexURL" placeholder="<?php echo $language->translate("PLEX_URL");?>" autocorrect="off" autocapitalize="off" value="<?php echo PLEXURL;?>">
                                                                <p class="help-text"><?php echo $language->translate("PLEX_URL");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="plexPort" placeholder="<?php echo $language->translate("PLEX_PORT");?>" autocorrect="off" autocapitalize="off" value="<?php echo PLEXPORT;?>">
                                                                <p class="help-text"><?php echo $language->translate("PLEX_PORT");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="plexToken" placeholder="<?php echo $language->translate("PLEX_TOKEN");?>" autocorrect="off" autocapitalize="off" value="<?php echo PLEXTOKEN;?>">
                                                                <p class="help-text"><?php echo $language->translate("PLEX_TOKEN");?></p>

                                                            </div>

                                                            <div class="content-form form-inline">

                                                                <div class="form-group">
                                                                    <?php  if(PLEXRECENTMOVIE == "true") : $PLEXRECENTMOVIE = "checked"; else : $PLEXRECENTMOVIE = ""; endif;?>
                                                                    <input id="" class="switcher switcher-success" value="false" name="plexRecentMovie" type="hidden">
                                                                    <input id="plexRecentMovie" class="switcher switcher-success" value="true" name="plexRecentMovie" type="checkbox" <?php echo $PLEXRECENTMOVIE;?>>

                                                                    <label for="plexRecentMovie"></label><?php echo $language->translate("RECENT_MOVIES");?>

                                                                </div>

                                                                <div class="form-group">
                                                                    <?php  if(PLEXRECENTTV == "true") : $PLEXRECENTTV = "checked"; else : $PLEXRECENTTV = ""; endif;?>
                                                                    <input id="" class="switcher switcher-success" value="false" name="plexRecentTV" type="hidden">
                                                                    <input id="plexRecentTV" class="switcher switcher-success" value="true" name="plexRecentTV" type="checkbox" <?php echo $PLEXRECENTTV;?>>

                                                                    <label for="plexRecentTV"></label><?php echo $language->translate("RECENT_TV");?>

                                                                </div>

                                                                <div class="form-group">
                                                                    <?php  if(PLEXRECENTMUSIC == "true") : $PLEXRECENTMUSIC = "checked"; else : $PLEXRECENTMUSIC = ""; endif;?>
                                                                    <input id="" class="switcher switcher-success" value="false" name="plexRecentMusic" type="hidden">
                                                                    <input id="plexRecentMusic" class="switcher switcher-success" value="true" name="plexRecentMusic" type="checkbox" <?php echo $PLEXRECENTMUSIC;?>>

                                                                    <label for="plexRecentMusic"></label><?php echo $language->translate("RECENT_MUSIC");?>

                                                                </div>

                                                                <div class="form-group">
                                                                    <?php  if(PLEXPLAYINGNOW == "true") : $PLEXPLAYINGNOW = "checked"; else : $PLEXPLAYINGNOW = ""; endif;?>
                                                                    <input id="" class="switcher switcher-success" value="false" name="plexPlayingNow" type="hidden">
                                                                    <input id="plexPlayingNow" class="switcher switcher-success" value="true" name="plexPlayingNow" type="checkbox" <?php echo $PLEXPLAYINGNOW;?>>

                                                                    <label for="plexPlayingNow"></label><?php echo $language->translate("PLAYING_NOW");?>

                                                                </div>

                                                            </div>

                                                        </div>

                                                        <div class="tab-pane big-box fade" id="tab-sonarr">

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="sonarrURL" placeholder="<?php echo $language->translate("SONARR_URL");?>" autocorrect="off" autocapitalize="off" value="<?php echo SONARRURL;?>">
                                                                <p class="help-text"><?php echo $language->translate("SONARR_URL");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="sonarrPort" placeholder="<?php echo $language->translate("SONARR_PORT");?>" autocorrect="off" autocapitalize="off" value="<?php echo SONARRPORT;?>">
                                                                <p class="help-text"><?php echo $language->translate("SONARR_PORT");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="sonarrKey" placeholder="<?php echo $language->translate("SONARR_KEY");?>" autocorrect="off" autocapitalize="off" value="<?php echo SONARRKEY;?>">
                                                                <p class="help-text"><?php echo $language->translate("SONARR_KEY");?></p>

                                                            </div>

                                                        </div>

                                                        <div class="tab-pane big-box fade" id="tab-radarr">

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="radarrURL" placeholder="<?php echo $language->translate("RADARR_URL");?>" autocorrect="off" autocapitalize="off" value="<?php echo RADARRURL;?>">
                                                                <p class="help-text"><?php echo $language->translate("RADARR_URL");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="radarrPort" placeholder="<?php echo $language->translate("RADARR_PORT");?>" autocorrect="off" autocapitalize="off" value="<?php echo RADARRPORT;?>">
                                                                <p class="help-text"><?php echo $language->translate("RADARR_PORT");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="radarrKey" placeholder="<?php echo $language->translate("RADARR_KEY");?>" autocorrect="off" autocapitalize="off" value="<?php echo RADARRKEY;?>">
                                                                <p class="help-text"><?php echo $language->translate("RADARR_KEY");?></p>

                                                            </div>

                                                        </div>
                                                        
                                                        <div class="tab-pane big-box fade" id="tab-nzbget">

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="nzbgetURL" placeholder="<?php echo $language->translate("NZBGET_URL");?>" autocorrect="off" autocapitalize="off" value="<?php echo NZBGETURL;?>">
                                                                <p class="help-text"><?php echo $language->translate("NZBGET_URL");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="nzbgetPort" placeholder="<?php echo $language->translate("NZBGET_PORT");?>" autocorrect="off" autocapitalize="off" value="<?php echo NZBGETPORT;?>">
                                                                <p class="help-text"><?php echo $language->translate("NZBGET_PORT");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="nzbgetUsername" placeholder="<?php echo $language->translate("USERNAME");?>" autocorrect="off" autocapitalize="off" value="<?php echo NZBGETUSERNAME;?>">
                                                                <p class="help-text"><?php echo $language->translate("USERNAME");?></p>

                                                            </div>
                                                            
                                                            <div class="form-group">

                                                                <input type="password" class="form-control material input-sm" name="nzbgetPassword" placeholder="<?php echo $language->translate("PASSWORD");?>" autocorrect="off" autocapitalize="off" value="<?php echo NZBGETPASSWORD;?>">
                                                                <p class="help-text"><?php echo $language->translate("PASSWORD");?></p>

                                                            </div>

                                                        </div>
                                                        
                                                        <div class="tab-pane big-box fade" id="tab-sabnzbd">

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="sabnzbdURL" placeholder="<?php echo $language->translate("SABNZBD_URL");?>" autocorrect="off" autocapitalize="off" value="<?php echo SABNZBDURL;?>">
                                                                <p class="help-text"><?php echo $language->translate("SABNZBD_URL");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="nzbgetPort" placeholder="<?php echo $language->translate("SABNZBD_PORT");?>" autocorrect="off" autocapitalize="off" value="<?php echo SABNZBDPORT;?>">
                                                                <p class="help-text"><?php echo $language->translate("SABNZBD_PORT");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="sabnzbdKey" placeholder="<?php echo $language->translate("SABNZBD_KEY");?>" autocorrect="off" autocapitalize="off" value="<?php echo SABNZBDKEY;?>">
                                                                <p class="help-text"><?php echo $language->translate("SABNZBD_KEY");?></p>

                                                            </div>

                                                        </div>
                                                        
                                                        <div class="tab-pane big-box fade" id="tab-headphones">

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="headphonesURL" placeholder="<?php echo $language->translate("HEADPHONES_URL");?>" autocorrect="off" autocapitalize="off" value="<?php echo HEADPHONESURL;?>">
                                                                <p class="help-text"><?php echo $language->translate("HEADPHONES_URL");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="headphonesPort" placeholder="<?php echo $language->translate("HEADPHONES_PORT");?>" autocorrect="off" autocapitalize="off" value="<?php echo HEADPHONESPORT;?>">
                                                                <p class="help-text"><?php echo $language->translate("HEADPHONES_PORT");?></p>

                                                            </div>

                                                            <div class="form-group">

                                                                <input type="text" class="form-control material input-sm" name="headphonesKey" placeholder="<?php echo $language->translate("HEADPHONES_KEY");?>" autocorrect="off" autocapitalize="off" value="<?php echo HEADPHONESKEY;?>">
                                                                <p class="help-text"><?php echo $language->translate("HEADPHONES_KEY");?></p>

                                                            </div>

                                                        </div>

                                                    </div>
   
                                                </div>
                                                
                                                <button type="submit" class="class='btn waves btn-labeled btn-success btn btn-sm pull-right text-uppercase waves-effect waves-float"><span class="btn-label"><i class="fa fa-floppy-o"></i></span>Save</button>

                                            </form> 
                                            
                                        </div>
                                        
                                        

                                        
                                        
                                      
                                    </div>

                                </div>
                                
                                <div class="tab-pane big-box  fade in" id="loginlog">

                                    <div class="table-responsive">

                                        <?php if(file_exists(FAIL_LOG)) : ?>

                                        <div id="loginStats">

                                            <div class="content-box ultra-widget">

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

                                        <table id="datatable" class="display">

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

                                                    <td><?=$val["ip"];?></td>

                                                    <td><span class="label label-<?php getColor($val["auth_type"]);?>"><?=$val["auth_type"];?></span></td>

                                                </tr>

                                                <?php endforeach; ?> 

                                            </tbody>

                                        </table>

                                        <?php 
                                        $totalLogin = $goodLogin + $badLogin;     
                                        $goodPercent = round(($goodLogin / $totalLogin) * 100);
                                        $badPercent = round(($badLogin / $totalLogin) * 100);

                                        endif;

                                        if(!file_exists(FAIL_LOG)) :

                                            echo $language->translate("NOTHING_LOG");

                                        endif;

                                        ?>

                                    </div>

                                </div>
                                
                                <div class="tab-pane big-box  fade in" id="about">
                        
                                    <h4><strong><?php echo $language->translate("ABOUT");?> Organizr</strong></h4>
                        
                                    <p id="version"></p>
                                    
                                    <p id="submitFeedback">
                                    
                                        <a href='https://reddit.com/r/organizr' target='_blank' type='button' style="background: #AD80FD" class='btn waves btn-labeled btn-success btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-reddit'></i></span>SUBREDDIT</a> 
                                        <a href='https://github.com/causefx/Organizr/issues/new' target='_blank' type='button' class='btn waves btn-labeled btn-success btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-github-alt'></i></span><?php echo $language->translate("SUBMIT_ISSUE");?></a> 
                                        <a href='https://github.com/causefx/Organizr' target='_blank' type='button' class='btn waves btn-labeled btn-primary btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-github'></i></span><?php echo $language->translate("VIEW_ON_GITHUB");?></a>
                                        <a href='https://gitter.im/Organizrr/Lobby' target='_blank' type='button' class='btn waves btn-labeled btn-dark btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-comments-o'></i></span><?php echo $language->translate("CHAT_WITH_US");?></a>
                                        <button type="button" class="class='btn waves btn-labeled btn-warning btn text-uppercase waves-effect waves-float" data-toggle="modal" data-target=".Help-Me-modal-lg"><span class='btn-label'><i class='fa fa-life-ring'></i></span><?php echo $language->translate("HELP");?></button>
                                        <button id="deleteToggle" type="button" class="class='btn waves btn-labeled btn-danger btn text-uppercase waves-effect waves-float" ><span class='btn-label'><i class='fa fa-trash'></i></span><?php echo $language->translate("DELETE_DATABASE");?></button>

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
                                        
                                                        <button type="button" class="btn btn-default waves" data-dismiss="modal"><?php echo $language->translate("CLOSE");?></button>
                                        
                                                    </div>
                                        
                                                </div>
                                        
                                            </div>
                                        
                                        </div>
                                    
                                    </p>
                                    
                                    <p id="whatsnew"></p>
                                    
                                    <p id="downloadnow"></p>
                                    
                                    <div id="deleteDiv" style="display: none;" class="panel panel-danger">
                                        
                                        <div class="panel-heading">
                                            
                                            <h3 class="panel-title"><?php echo $language->translate("DELETE_DATABASE");?></h3>
                                            
                                        </div>
                                        
                                        <div class="panel-body">
                                            
                                            <div class="">
                                            
                                                <p><?php echo $language->translate("DELETE_WARNING");?></p>
                                                <form id="deletedb" method="post">
                                                    
                                                    <input type="hidden" name="action" value="deleteDB" />
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
                                
                                <div class="tab-pane small-box  fade in" id="customedit">

                                    <form id="add_optionz" method="post">
                                        
                                        <input type="hidden" name="action" value="addOptionz" />
                                        
                                        <div class="btn-group">
                                            
                                            <button type="button" class="btn btn-dark dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <?php echo $language->translate("CHOOSE_THEME");?>  <span class="caret"></span>
                                            </button>
                                            
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
                                            
                                        </div>
                                        
                                        <button id="editCssButton" class="btn waves btn-labeled btn-primary btn-sm text-uppercase waves-effect waves-float" type="button">
                                                
                                                <span class="btn-label"><i class="fa fa-css3"></i></span><?php echo $language->translate("EDIT_CUSTOM_CSS");?>
                                                
                                        </button>
                                        
                                        <button class="btn waves btn-labeled btn-success btn-sm pull-right text-uppercase waves-effect waves-float" type="submit">
                                                
                                                <span class="btn-label"><i class="fa fa-floppy-o"></i></span><?php echo $language->translate("SAVE_OPTIONS");?>
                                                
                                        </button>

                                        <div class="big-box grids">

                                            <div class="row show-grids">

                                                <h4><strong><?php echo $language->translate("TITLE");?></strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("TITLE");?></center>

                                                    <input name="title" class="form-control gray" value="<?=$title;?>" placeholder="Organizr">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("TITLE_TEXT");?></center>

                                                    <input name="topbartext" id="topbartext" class="form-control jscolor {hash:true}" value="<?=$topbartext;?>">

                                                </div>
                                                
                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("LOADING_COLOR");?></center>

                                                    <input name="loading" id="loading" class="form-control jscolor {hash:true}" value="<?=$loading;?>">

                                                </div>

                                            </div>

                                            <div class="row show-grids">

                                                <h4><strong><?php echo $language->translate("NAVIGATION_BARS");?></strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("TOP_BAR");?></center>

                                                    <input name="topbar" id="topbar" class="form-control jscolor {hash:true}" value="<?=$topbar;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("BOTTOM_BAR");?></center>

                                                    <input name="bottombar" id="bottombar" class="form-control jscolor {hash:true}" value="<?=$bottombar;?>">

                                                </div>

                                                <div class="clearfix visible-xs-block"></div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("SIDE_BAR");?></center>

                                                    <input name="sidebar" id="sidebar" class="form-control jscolor {hash:true}" value="<?=$sidebar;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("HOVER_BG");?></center>

                                                    <input name="hoverbg" id="hoverbg" class="form-control jscolor {hash:true}" value="<?=$hoverbg;?>">

                                                </div>
                                                
                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("HOVER_TEXT");?></center>

                                                    <input name="hovertext" id="hovertext" class="form-control jscolor {hash:true}" value="<?=$hovertext;?>">

                                                </div>

                                            </div>

                                            <div class="row show-grids">

                                                <h4><strong><?php echo $language->translate("ACTIVE_TAB");?></strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("ACTIVE_TAB_BG");?></center>

                                                    <input name="activetabBG" id="activetabBG" class="form-control jscolor {hash:true}" value=<?=$activetabBG;?>"">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("ACTIVE_TAB_ICON");?></center>

                                                    <input name="activetabicon" id="activetabicon" class="form-control jscolor {hash:true}" value="<?=$activetabicon;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("ACTIVE_TAB_TEXT");?></center>

                                                    <input name="activetabtext" id="activetabtext" class="form-control jscolor {hash:true}" value="<?=$activetabtext;?>">

                                                </div>

                                            </div>

                                            <div class="row show-grids">

                                                <h4><strong><?php echo $language->translate("INACTIVE_TAB");?></strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("INACTIVE_ICON");?></center>

                                                    <input name="inactiveicon" id="inactiveicon" class="form-control jscolor {hash:true}" value="<?=$inactiveicon;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("INACTIVE_TEXT");?></center>

                                                    <input name="inactivetext" id="inactivetext" class="form-control jscolor {hash:true}" value="<?=$inactivetext;?>">

                                                </div>

                                            </div>

                                        </div>
                                        
                                    </form>
                                    
                                     <form style="display: none" id="editCssForm" method="POST" action="submitCSS.php">
                                         
                                         <button class="btn waves btn-labeled btn-warning btn-sm pull-left text-uppercase waves-effect waves-float" type="button" id="backToThemeButton">

                                            <span class="btn-label"><i class="fa fa-arrow-left"></i></span><?php echo $language->translate("GO_BACK");?>
                                                
                                        </button>
                                        
                                         
                                         <button class="btn waves btn-labeled btn-success btn-sm pull-right text-uppercase waves-effect waves-float" type="submit">

                                            <span class="btn-label"><i class="fa fa-floppy-o"></i></span><?php echo $language->translate("SAVE_CSS");?>
                                                
                                        </button>
                                         
                                        <br><br>
                                        
                                        <input type="hidden" name="submit" value="editCSS" /> 
                                         
                                        <h1><?php echo $language->translate("EDIT_CUSTOM_CSS");?></h1> 
                                         
                                         <!--<p>Variables Available<code>$topbar - $topbartext - $bottombar - $sidebar - $hoverbg - $activetabBG - $activetabicon - $activetabtext - $inactiveicon - $inactivetext - $loading - $hovertext</code></p>-->
                                         
                                        <textarea class="form-control" id="css-show" name="css-show" rows="25" style="background: #000; color: #FFF;">
<?php if(CUSTOMCSS == "true") :
$template_file = "custom.css";
$file_handle = fopen($template_file, "rb");
echo fread($file_handle, filesize($template_file));
fclose($file_handle);
endif;?></textarea>
                                                                        
                                    </form>
                      
                                </div>
                                
                            </div>
                              
                        </div>
                            
                    </div>
                          
                </div>
            
            </div>
            <!--End Content-->

            <!--Welcome notification-->
            <div id="welcome"></div>

        </div>
        <?php if(!$USER->authenticated) : ?>

        <?php endif;?>
        <?php if($USER->authenticated) : ?>

        <?php endif;?>

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


        <!--Notification-->
        <script src="js/notifications/notificationFx.js"></script>

        <script src="js/jqueri_ui_custom/jquery-ui.min.js"></script>
        <script src="js/jquery.filer.min.js" type="text/javascript"></script>
	    <script src="js/custom.js" type="text/javascript"></script>
	    <script src="js/jquery.mousewheel.min.js" type="text/javascript"></script>
        
        <!--Data Tables-->
        <script src="bower_components/DataTables/media/js/jquery.dataTables.js"></script>
        <script src="bower_components/datatables.net-responsive/js/dataTables.responsive.js"></script>
        <script src="bower_components/datatables-tabletools/js/dataTables.tableTools.js"></script>

          <script>
            $(function () {
                //Data Tables
                $('#datatable').DataTable({
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
        
        <?php if($_POST['op']) : ?>
        <script>
            
            parent.notify("<?php echo printArray($USER->info_log); ?>","info-circle","notice","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");
            
            <?php if(!empty($USER->error_log)) : ?>
            
            parent.notify("<?php echo printArray($USER->error_log); ?>","exclamation-circle ","error","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");
            
            <?php endif; ?>
            
        </script>
        <?php endif; ?>
        
        <?php if($action == "addTabz") : ?>
        <script>

            if(!window.location.hash) {
                
                window.location = window.location + '#loaded';
                window.location.reload();
                
            }else{
                
               parent.notify("<strong><?php echo $language->translate('TABS_SAVED');?></strong> <?php echo $language->translate('APPLY_RELOAD');?>","floppy-o","success","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>"); 
                
            }
            
        </script>
        <?php endif; ?>
        
         <?php if($action == "addOptionz") : ?>
        <script>

            parent.notify("<strong><?php echo $language->translate('COLORS_SAVED');?></strong> <?php echo $language->translate('APPLY_RELOAD');?>","floppy-o","success","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");
            
        </script>
        <?php endif; ?>

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

                //$(".todo ul").sortable();
                $(".todo ul").sortable({
                    
                    'opacity': 0.9,
                    
                });

                $("#add_tab").on('submit', function (e) {
                    e.preventDefault();

                    var $toDo = $(this).find('.name-of-todo');
                    toDo_name = $toDo.val();

                    if (toDo_name.length >= 3) {

                        var newid = $('.list-group-item').length + 1;

                        $(".todo ul").append(
                        '<li id="item-' + newid + '" class="list-group-item animated zoomInDown" style="position: relative; left: 0px; top: 0px;"><tab class="content-form form-inline"> <div class="form-group"><div class="action-btns" style="width:calc(100%)"><a class="" style="margin-left: 0px"><span style="font: normal normal normal 30px/1 FontAwesome;" class="fa fa-hand-paper-o"></span></a></div></div> <div class="form-group"><input style="width: 100%;" type="text" class="form-control material input-sm" name="name-' + newid + '" id="name[' + newid + ']" placeholder="<?php echo $language->translate("NEW_TAB_NAME");?>" value="' + toDo_name + '"></div> <div class="form-group"><input style="width: 100%;" type="text" class="form-control material input-sm" name="url-' + newid + '" id="url[' + newid + ']" placeholder="<?php echo $language->translate("TAB_URL");?>"></div> <div style="margin-right: 5px;" class="form-group"><div class="input-group"><input style="width: 100%;" name="icon-' + newid + '" data-placement="bottomRight" class="form-control material icp-auto" value="fa-diamond" type="text" /><span class="input-group-addon"></span></div> - <?php echo $language->translate("OR");?> -</div>  <div class="form-group"><input style="width: 100%;" type="text" class="form-control material input-sm" id="iconurl-' + newid + '" name="iconurl-' + newid + '" placeholder="<?php echo $language->translate("ICON_URL");?>" value=""></div>  <div class="form-group"> <div class="radio radio-danger"> <input type="radio" name="default" id="default[' + newid + ']" name="default"> <label for="default[' + newid + ']"><?php echo $language->translate("DEFAULT");?></label></div></div> <div class="form-group"><div class=""><input id="" class="switcher switcher-success" value="false" name="active-' + newid + '" type="hidden"><input name="active-' + newid + '" id="active[' + newid + ']" class="switcher switcher-success" type="checkbox" checked=""><label for="active[' + newid + ']"></label></div> <?php echo $language->translate("ACTIVE");?></div> <div class="form-group"><div class=""><input id="" class="switcher switcher-primary" value="false" name="user-' + newid + '" type="hidden"><input id="user[' + newid + ']" name="user-' + newid + '" class="switcher switcher-primary" type="checkbox" checked=""><label for="user[' + newid + ']"></label></div> <?php echo $language->translate("USER");?></div> <div class="form-group"><div class=""><input id="" class="switcher switcher-primary" value="false" name="guest-' + newid + '" type="hidden"><input name="guest-' + newid + '" id="guest[' + newid + ']" class="switcher switcher-warning" type="checkbox" checked=""><label for="guest[' + newid + ']"></label></div> <?php echo $language->translate("GUEST");?></div> <div class="form-group"><div class=""><input id="" class="switcher switcher-primary" value="false" name="window-' + newid + '" type="hidden"><input name="window-' + newid + '" id="window[' + newid + ']" class="switcher switcher-danger" type="checkbox"><label for="window[' + newid + ']"></label></div> <?php echo $language->translate("NO_IFRAME");?></div><div class="pull-right action-btns" style="padding-top: 8px;"><a class="trash"><span class="fa fa-trash"></span></a></div></tab></li>'
                        );

                        $('.icp-auto').iconpicker({placement: 'left', hideOnSelect: false, collision: true});

                        var eventObject = {

                            title: $.trim($("#" + newid).text()),
                            className: $("#" + newid).attr("data-bg"),
                            stick: true

                        };

                        $("#" + newid).data('eventObject', eventObject);

                        $toDo.val('').focus();

                    } else {

                        $toDo.focus();
                    }

                });

                count();

                $(".list-group-item").addClass("list-item");

                //Remove one completed item
                $(document).on('click', '.trash', function (e) {

                    var listItemRemove = $(this).closest(".list-group-item");
                    var animation = "zoomOutRight";
                    var container = $(this).closest(".list-group-item");

                    //container.attr('class', 'list-group-item gray-bg animation-container');
                    container.addClass('animated ' + animation);

                    setTimeout(function() {
                        var clearedCompItem = listItemRemove.remove();
                        console.log("removed");
                        e.preventDefault();
                        count();
                    }, 800);
                    

                });

                //Count items
                function count() {

                    var active = $('.list-group-item').length;

                    $('.total-tabs span').text(active);

                };

                $("#submitTabs").on('submit', function (e) {

                    console.log("submitted");

                    $("div.radio").each(function(i) {

                        $(this).find('input').attr('name', 'default-' + i);

                        console.log(i);

                    });

                    $('form input[type="radio"]').not(':checked').each(function() {

                        $(this).prop('checked', true);
                        $(this).prop('value', "false");
                        console.log("found unchecked");

                    });

                });

                $('#apply').on('click touchstart', function(){

                window.parent.location.reload();

                });

            });

        </script>

        <script>
            
            function rememberTabSelection(tabPaneSelector, useHash) {
            
                var key = 'selectedTabFor' + tabPaneSelector;

                if(get(key)) 

                $(tabPaneSelector).find('a[href=' + get(key) + ']').tab('show');

                $(tabPaneSelector).on("click", 'a[data-toggle]', function(event) {
                    set(key, this.getAttribute('href'));
                }); 

                function get(key) {

                    return useHash ? location.hash: localStorage.getItem(key);

                }

                function set(key, value){

                    if(useHash)

                        location.hash = value;

                    else

                        localStorage.setItem(key, value);
                }
            }
            
            $("#notifyTest").click(function(){

                var notifySplit = $("#notifyValue").val().split("-");
                console.log(notifySplit[0]);
                parent.notify("<?php echo $language->translate('TEST_MESSAGE');?>","flask","notice","5000", notifySplit[0], notifySplit[1]);
     
            });
            
            $("#iconHide").click(function(){

                $( "div[class^='jFiler jFiler-theme-dragdropbox']" ).toggle();
     
            });
            
            $("#iconAll").click(function(){

                $( "div[id^='viewAllIcons']" ).toggle();
     
            });
            
            $("#deleteToggle").click(function(){

                $( "#deleteDiv" ).toggle();
     
            });
            
            $("#editCssButton").click(function(){

                $( "#add_optionz" ).toggle();
                $( "#editCssForm" ).toggle();
     
            });
            
            $("#backToThemeButton").click(function(){

                $( "#add_optionz" ).toggle();
                $( "#editCssForm" ).toggle();
     
            });
            
            $(".deleteUser").click(function(){

                var parent_id = $(this).parent().attr('id');
                editUsername = $('#unregister').find('#inputUsername');
                $(editUsername).html('<input type="hidden" name="op" value="unregister"/><input type="hidden" name="username"value="' + parent_id + '" />');
     
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
            
            $('.icp-auto').iconpicker({placement: 'left', hideOnSelect: false, collision: true});
            
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
            
            //Custom Themes            
            function changeColor(elementName, elementColor) {
                
                var definedElement = document.getElementById(elementName);
                
                definedElement.focus();
                definedElement.value = elementColor;
                definedElement.style.backgroundColor = elementColor;
                
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

            });
            
            $('textarea').numberedtextarea({

              // font color for line numbers
              color: null,

              // border color
              borderColor: 'null',

              // CSS class to be added to the line numbers
              class: null, 

              // if true Tab key creates indentation
              allowTabChar: true,       

            });

        
        </script>
        
        <script>
        
        $( document ).ready(function() {
            
            $(".scroller-body").mCustomScrollbar({
                theme:"inset-2",
                scrollInertia: 300,
                autoHideScrollbar: true,
                autoExpandScrollbar: true
            });
            
            $("div[class^='DTTT_container']").append('<form style="display: inline; margin-left: 3px;" id="deletelog" method="post"><input type="hidden" name="action" value="deleteLog" /><button class="btn waves btn-labeled btn-danger text-uppercase waves-effect waves-float" type="submit"><span class="btn-label"><i class="fa fa-trash"></i></span><?php echo $language->translate("PURGE_LOG");?> </button></form>')
            $("a[id^='ToolTables_datatable_0'] span").html('<?php echo $language->translate("PRINT");?>')
            
            $('[data-toggle="tooltip"]').tooltip(); 
            
            rememberTabSelection('#settingsTabs', !localStorage);
            
            $( "div[class^='jFiler jFiler-theme-dragdropbox']" ).hide();
        		
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
                        
                        $(infoTabDownload).html("<br/><form style=\"display:initial;\" id=\"deletedb\" method=\"post\"><input type=\"hidden\" name=\"action\" value=\"upgrade\" /><button class=\"btn waves btn-labeled btn-success text-uppercase waves-effect waves-float\" type=\"submit\"><span class=\"btn-label\"><i class=\"fa fa-refresh\"></i></span><?php echo $language->translate("AUTO_UPGRADE");?></button></form> <a href='https://github.com/causefx/Organizr/archive/master.zip' target='_blank' type='button' class='btn waves btn-labeled btn-success text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-download'></i></span>Organizr v." + githubVersion + "</a>");
                        
                        $( "p[id^='upgrade']" ).toggle();
                    
                    }else if(currentVersion === githubVersion){
                    
                    	console.log("You Are on Current Version");
                    
                    }else{
                    
                    	console.log("something went wrong");
                    
                    }

                    $(infoTabVersion).html("<strong><?php echo $language->translate("INSTALLED_VERSION");?>: </strong>" + currentVersion + " <strong><?php echo $language->translate("CURRENT_VERSION");?>: </strong>" + githubVersion + " <strong><?php echo $language->translate("DATABASE_PATH");?>:  </strong> <?php echo htmlentities(DATABASE_LOCATION);?>");
                                        
                }
                
            });
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
