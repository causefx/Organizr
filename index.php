<?php 
//Set some variables
ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);
$data = false;
$databaseLocation = "databaseLocation.ini.php";
$needSetup = "Yes";
$tabSetup = "Yes";	
$hasOptions = "No";
$settingsicon = "No";
$settingsActive = "";
$action = "";
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
$loadingIcon = "images/organizr.png";
$baseURL = "";
require_once("translate.php");

function registration_callback($username, $email, $userdir){
    
    global $data;
    
    $data = array($username, $email, $userdir);

}

function printArray($arrayName){
    
    $messageCount = count($arrayName);
    
    $i = 0;
    
    foreach ( $arrayName as $item ) :
    
        $i++; 
    
        if($i < $messageCount) :
    
            echo "<small class='text-uppercase'>" . $item . "</small> & ";
    
        elseif($i = $messageCount) :
    
            echo "<small class='text-uppercase'>" . $item . "</small>";
    
        endif;
        
    endforeach;
    
}

function write_ini_file($content, $path) { 
    
    if (!$handle = fopen($path, 'w')) {
        
        return false; 
    
    }
    
    $success = fwrite($handle, trim($content));
    
    fclose($handle); 
    
    return $success; 

}

function getTimezone(){

    $regions = array(
        'Africa' => DateTimeZone::AFRICA,
        'America' => DateTimeZone::AMERICA,
        'Antarctica' => DateTimeZone::ANTARCTICA,
        'Asia' => DateTimeZone::ASIA,
        'Atlantic' => DateTimeZone::ATLANTIC,
        'Europe' => DateTimeZone::EUROPE,
        'Indian' => DateTimeZone::INDIAN,
        'Pacific' => DateTimeZone::PACIFIC
    );
    
    $timezones = array();

    foreach ($regions as $name => $mask) {
        
        $zones = DateTimeZone::listIdentifiers($mask);

        foreach($zones as $timezone) {

            $time = new DateTime(NULL, new DateTimeZone($timezone));

            $ampm = $time->format('H') > 12 ? ' ('. $time->format('g:i a'). ')' : '';

            $timezones[$name][$timezone] = substr($timezone, strlen($name) + 1) . ' - ' . $time->format('H:i') . $ampm;

        }
        
    }   
    
    print '<select name="timezone" id="timezone" class="form-control material" required>';
    
    foreach($timezones as $region => $list) {
    
        print '<optgroup label="' . $region . '">' . "\n";
    
        foreach($list as $timezone => $name) {
            
            print '<option value="' . $timezone . '">' . $name . '</option>' . "\n";
    
        }
    
        print '</optgroup>' . "\n";
    
    }
    
    print '</select>';
    
}
                
if(isset($_POST['action'])) :

    $action = $_POST['action'];
    
endif;

if($action == "createLocation") :

    $databaseData = '; <?php die("Access denied"); ?>' . "\r\n";

    foreach ($_POST as $postName => $postValue) {
            
        if($postName !== "action") :
        
            if(substr($postValue, -1) == "/") : $postValue = rtrim($postValue, "/"); endif;
        
                $postValue = str_replace("\\","/", $postValue);
        
            $databaseData .= $postName . " = \"" . $postValue . "\"\r\n";
        
        endif;
        
    }

    write_ini_file($databaseData, $databaseLocation);

endif;

if(!file_exists($databaseLocation)) :

    $configReady = "No";
    $userpic = "";
    $showPic = "";

else :

    $configReady = "Yes";

    require_once("user.php");

    $USER = new User("registration_callback");

    $dbfile = DATABASE_LOCATION  . constant('User::DATABASE_NAME') . ".db";

    $database = new PDO("sqlite:" . $dbfile);

    $query = "SELECT * FROM users";

    foreach($database->query($query) as $data) {

        $needSetup = "No";

    }

    $db = DATABASE_LOCATION  . constant('User::DATABASE_NAME') . ".db";
    $file_db = new PDO("sqlite:" . $db);
    $file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbTab = $file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="tabs"');
    $dbOptions = $file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="options"');

    foreach($dbTab as $row) :

        if (in_array("tabs", $row)) :

            $tabSetup = "No";

        endif;

    endforeach;

    if($tabSetup == "Yes") :

        $settingsActive = "active";
    
    endif;

    foreach($dbOptions as $row) :

        if (in_array("options", $row)) :

            $hasOptions = "Yes";

        endif;

    endforeach;

    if($tabSetup == "No") :

        if($USER->authenticated && $USER->role == "admin") :

            $result = $file_db->query('SELECT * FROM tabs WHERE active = "true"');
            $getsettings = $file_db->query('SELECT * FROM tabs WHERE active = "true"');

            foreach($getsettings as $row) :

                if(!empty($row['iconurl']) && $settingsicon == "No") :

                    $settingsicon = "Yes";

                endif;

            endforeach;

        elseif($USER->authenticated && $USER->role == "user") :

            $result = $file_db->query('SELECT * FROM tabs WHERE active = "true" AND user = "true"');

        else :

            $result = $file_db->query('SELECT * FROM tabs WHERE active = "true" AND guest = "true"');

        endif;

    endif;

    if($hasOptions == "Yes") :

        $resulto = $file_db->query('SELECT * FROM options');

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

    $userpic = md5( strtolower( trim( $USER->email ) ) );
    if(LOADINGICON !== "") : 

        $loadingIcon = LOADINGICON; 

    endif;

    if(SLIMBAR == "true") : $slimBar = "30"; $userSize = "25"; else : $slimBar = "56"; $userSize = "40"; endif;
    
    if($USER->authenticated) : 

        $showPic = "<img src='https://www.gravatar.com/avatar/$userpic?s=$userSize' class='img-circle'>"; 

    else : 

        //$showPic = "<img style='height: " . $userSize . "px'; src='images/login.png'>"; 
        $showPic = "<login class='login-btn text-uppercase'>" . $language->translate("LOGIN") . "</login>"; 

    endif;

endif;

if(!defined('SLIMBAR')) : define('SLIMBAR', 'false'); endif;
if(!defined('AUTOHIDE')) : define('AUTOHIDE', 'false'); endif;
if(!defined('ENABLEMAIL')) : define('ENABLEMAIL', 'false'); endif;
if(!defined('CUSTOMCSS')) : define('CUSTOMCSS', 'false'); endif;
if(!defined('LOADINGSCREEN')) : define('LOADINGSCREEN', 'true'); endif;
if(!isset($notifyExplode)) :

    $notifyExplode = array("bar","slidetop");

endif;

if(SLIMBAR == "true") : $slimBar = "30"; $userSize = "25"; else : $slimBar = "56"; $userSize = "40"; endif;

if(file_exists("images/settings2.png")) : $iconRotate = "false"; $settingsIcon = "settings2.png"; else: $iconRotate = "true"; $settingsIcon = "settings.png"; endif;

?>
<!--

    ___       ___       ___       ___       ___       ___       ___       ___   
   /\  \     /\  \     /\  \     /\  \     /\__\     /\  \     /\  \     /\  \  
  /::\  \   /::\  \   /::\  \   /::\  \   /:| _|_   _\:\  \   _\:\  \   /::\  \ 
 /:/\:\__\ /::\:\__\ /:/\:\__\ /::\:\__\ /::|/\__\ /\/::\__\ /::::\__\ /::\:\__\
 \:\/:/  / \;:::/  / \:\:\/__/ \/\::/  / \/|::/  / \::/\/__/ \::;;/__/ \;:::/  /
  \::/  /   |:\/__/   \::/  /    /:/  /    |:/  /   \:\__\    \:\__\    |:\/__/ 
   \/__/     \|__|     \/__/     \/__/     \/__/     \/__/     \/__/     \|__|  


-->
<!DOCTYPE html>

<html lang="<?php echo $getLanguage; ?>" class="no-js">

    <head>
        
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
        <meta name="apple-mobile-web-app-capable" content="yes" />   
        <meta name="mobile-web-app-capable" content="yes" /
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />

        <title><?=$title;?><?php if($title !== "Organizr") :  echo " - Organizr"; endif; ?></title>

        <link rel="stylesheet" href="<?=$baseURL;?>bower_components/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="<?=$baseURL;?>bower_components/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="<?=$baseURL;?>bower_components/mdi/css/materialdesignicons.min.css">
        <link rel="stylesheet" href="<?=$baseURL;?>bower_components/metisMenu/dist/metisMenu.min.css">
        <link rel="stylesheet" href="<?=$baseURL;?>bower_components/Waves/dist/waves.min.css"> 
        <link rel="stylesheet" href="<?=$baseURL;?>bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css"> 

        <link rel="stylesheet" href="<?=$baseURL;?>js/selects/cs-select.css">
        <link rel="stylesheet" href="<?=$baseURL;?>js/selects/cs-skin-elastic.css">
        <link rel="stylesheet" href="<?=$baseURL;?>bower_components/google-material-color/dist/palette.css">
        
        <link rel="stylesheet" href="<?=$baseURL;?>bower_components/sweetalert/dist/sweetalert.css">
        <link rel="stylesheet" href="<?=$baseURL;?>bower_components/smoke/dist/css/smoke.min.css">
        <link rel="stylesheet" href="<?=$baseURL;?>js/notifications/ns-style-growl.css">
        <link rel="stylesheet" href="<?=$baseURL;?>js/notifications/ns-style-other.css">


        <script src="<?=$baseURL;?>js/menu/modernizr.custom.js"></script>
        <script type="text/javascript" src="<?=$baseURL;?>js/sha1.js"></script>
		<script type="text/javascript" src="<?=$baseURL;?>js/user.js"></script>

        <link rel="stylesheet" href="<?=$baseURL;?>css/style.css">

        <link rel="icon" type="image/png" href="<?=$baseURL;?>images/favicon/android-chrome-192x192.png" sizes="192x192">
        <link rel="apple-touch-icon" sizes="180x180" href="<?=$baseURL;?>images/favicon/apple-touch-icon.png">
        <link rel="icon" type="image/png" href="<?=$baseURL;?>images/favicon/favicon-32x32.png" sizes="32x32">
        <link rel="icon" type="image/png" href="<?=$baseURL;?>images/favicon/favicon-16x16.png" sizes="16x16">
        <link rel="manifest" href="<?=$baseURL;?>images/favicon/manifest.json">
        <link rel="mask-icon" href="<?=$baseURL;?>images/favicon/safari-pinned-tab.svg" color="#2d89ef">
        <link rel="shortcut icon" href="<?=$baseURL;?>images/favicon/favicon.ico">
        <meta name="msapplication-config" content="<?=$baseURL;?>images/favicon/browserconfig.xml">
        <meta name="theme-color" content="#2d89ef">
        <link rel="stylesheet" type="text/css" href="css/addtohomescreen.css">
        <script src="js/addtohomescreen.js"></script>
        
        <!--[if lt IE 9]>
        <script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
        <script src="bower_components/respondJs/dest/respond.min.js"></script>
        <![endif]-->
        
    </head>
    
    <style>

        .bottom-bnts a {

            background: <?=$bottombar;?> !important;
            color: <?=$topbartext;?> !important;

        }.bottom-bnts {

            background-color: <?=$bottombar;?> !important;

        }.gn-menu-main {


            background-color: <?=$topbar;?>;

        }.gn-menu-main ul.gn-menu {

            background: <?=$sidebar;?>;

        }.gn-menu-wrapper {

            background: <?=$sidebar;?>;

        }.gn-menu i {

            height: 18px;
            width: 52px;

        }.la-timer.la-dark {

            color: <?=$topbartext;?>

        }.refresh-preloader {

            background: <?=$loading;?>;

        }.la-timer {

            width: 75px;
            height: 75px;
            padding-top: 20px;
            border-radius: 100px;
            background: <?=$sidebar;?>;
            border: 2px solid <?=$topbar;?>;

        }@media screen and (min-width:737px){

            .tab-item:hover a {

                color: <?=$hovertext;?> !important;
                background: <?=$hoverbg;?>;
                border-radius: 100px 0 0 100px;

            }

        }.gn-menu li.active > a {

            color: <?=$activetabtext;?> !important;
            background: <?=$activetabBG;?>;
            border-radius: 100px 0 0 100px;

        }.gn-menu li.rightActive > a {

            background: <?=$hoverbg;?>;
            border-radius: 100px 0 0 100px;

        }.active {

            display: block;

        }.hidden {

            display: none;

        }.errorz {

            background-image: linear-gradient(red, red), linear-gradient(#d2d2d2, #d2d2d2);
            outline: none;
            animation: input-highlight .5s forwards;
            box-shadow: none;
            padding-left: 0;
            border: 0;
            border-radius: 0;
            background-size: 0 2px,100% 1px;
            background-repeat: no-repeat;
            background-position: center bottom,center calc(100% - 1px);
            background-color: transparent;
            box-shadow: none;

        }.gn-menu li.active i.fa {

            color: <?=$activetabicon;?>;

        }.gn-menu li i.fa {

            color: <?=$inactiveicon;?>;

        }.gn-menu-main ul.gn-menu a {

            color: <?=$inactivetext;?>;
        }li.dropdown.some-btn .mdi {

            color: <?=$topbartext;?>;

        }.nav>li>a:focus, .nav>li>a:hover {

            text-decoration: none;
            background-color: transparent;

        }div#preloader {

            background-color: <?=$loading;?>;

        }.iframe {

            -webkit-overflow-scrolling: touch;

        }.iframe iframe{

        }#menu-toggle span {
            background: <?=$topbartext;?>;
        }logo.logo {

            opacity: 0.5;
            filter: alpha(opacity=50);

        }.mini-nav .split {
            width: calc(50% - 25px);
        }.splitRight {
            width: 50%;
            margin-left: 50% !important;
            position: absolute !important;
        }.split {
            width: 50%;
            position: absolute !important;
        }.mini-nav .splitRight {
            margin-left: calc(50% + 25px) !important;
            width: calc(50% - 25px);
        }.form-control.material {
            background-image: -webkit-gradient(linear, left top, left bottom, from(<?=$topbartext;?>), to(<?=$topbartext;?>)), -webkit-gradient(linear, left top, left bottom, from(#d2d2d2), to(#d2d2d2));
            background-image: -webkit-linear-gradient(<?=$topbartext;?>, <?=$topbartext;?>), -webkit-linear-gradient(#d2d2d2, #d2d2d2);
            background-image: linear-gradient(<?=$topbartext;?>, <?=$topbartext;?>), linear-gradient(#d2d2d2, #d2d2d2);
        }img.titlelogoclass {

            max-width: 250px; 
            max-height: <?=$slimBar;?>px;
            
        }@media only screen and (max-width: 450px) {
            
            img.titlelogoclass {

                max-width: 150px; 
            
            }
            
        }.login-btn {
            
            -webkit-border-radius: 4;
            -moz-border-radius: 4;
            border-radius: 4px;
            -webkit-box-shadow: 0px 1px 3px #666666;
            -moz-box-shadow: 0px 1px 3px #666666;
            box-shadow: 0px 1px 3px #666666;
            font-family: Arial;
            color: <?=$topbar;?>;
            font-size: 10px;
            vertical-align: top;
            background: <?=$topbartext;?>;
            padding: 5px 10px 5px 10px;
            text-decoration: none;
            font-weight: 700;
            font-style: normal;
            
        }.login-btn:hover {
            
            background: <?=$hoverbg;?>;
            color: <?=$hovertext;?>;
            text-decoration: none;
            font-weight: 700;
            
        }

        <?php if(SLIMBAR == "true") : ?>
        /* Slim Styling */
        body{

            padding-top: 30px !important;

        }.gn-menu-main {
        
            height: 30px !important;
        
        }.gn-menu-wrapper {
         
            top: 30px !important;
            
        }.gn-menu-main .navbar-right {
            
            line-height: 30px !important;
            
        }img.img-circle {
            
            vertical-align: inherit;
            margin-top: 2px;
        
        }.menu-toggle .hamburger {
            
            top: 0px !important;
            
        }.top-clock {
            
            line-height: 30px !important;
            
        }img.titlelogoclass {
            
            vertical-align: inherit;
            
        }.members-sidebar {
            
            top: 30px !important;
            
        }.menu-toggle .cross span:nth-child(2) {
        
            left: -9px;
            top: 40px;
        
        }.menu-toggle.gn-selected .cross span:nth-child(2) {
         
            width: 49%;
            
        }.menu-toggle.gn-selected .cross span:nth-child(1) {
            
            height: 105% !important;
            
        }.menu-toggle .cross span:nth-child(1) {
         
            left: 6px !important;
            top: 26px !important;
            
        }.menu-toggle .hamburger span {
         
            margin: 5px 0;
            width: 25px;
            
        }.menu-toggle .hamburger {
            
            margin-left: -17px;
            
        }.ns-effect-slidetop {
         
            padding: 6px 22px;
            
        }.ns-effect-exploader {
         
            padding: 5px 22px;
            
        }
        <?php endif; ?>
        <?php if(CUSTOMCSS == "true") : 
$template_file = "custom.css";
$file_handle = fopen($template_file, "rb");
echo fread($file_handle, filesize($template_file));
fclose($file_handle);
echo "\n";
endif; ?>

    </style>

    <body style="overflow: hidden">

        <?php if (LOADINGSCREEN == "true") : ?>
        <!--Preloader-->
        <div id="preloader" class="preloader table-wrapper">
            
            <div class="table-row">
                
                <div class="table-cell">
                    
                    <div class="la-ball-scale-multiple la-3x" style="color: <?=$topbar;?>">
                        
                        <?php if (pathinfo($loadingIcon, PATHINFO_EXTENSION) !== "gif" ) : 
                        
                            echo "<div></div><div></div><div></div>";
                        
                        endif; ?>
                        
                        <logo class="logo"><img height="192px" src="<?=$loadingIcon;?>"></logo>
                    
                    </div>
                
                </div>
            
            </div>
        
        </div>
        <?php endif; ?>

        <div id="main-wrapper" class="main-wrapper" tabindex="-1">

            <ul id="gn-menu" class="gn-menu-main">
                
                <li class="gn-trigger">
                    
                    <a id="menu-toggle" class="menu-toggle gn-icon gn-icon-menu">
                        
                        <div class="hamburger">
                            
                            <span></span>
                            <span></span>
                            <span></span>
                        
                        </div>
                        
                        <div class="cross">
                            
                            <span></span>
                            <span></span>
                        
                        </div>
                    
                    </a>
                    
                    <nav class="gn-menu-wrapper">
                        
                        <div class="gn-scroller" id="gn-scroller">
                            
                            <ul id="tabList" class="gn-menu metismenu">

                                <!--Start Tab List-->
                                
                                <?php if($tabSetup == "No") : $tabCount = 1; foreach($result as $row) : 
                                
                                if($row['defaultz'] == "true") : $defaultz = "active"; else : $defaultz = ""; endif; ?>
                                
                                <li window="<?=$row['window'];?>" class="tab-item <?=$defaultz;?>" id="<?=$row['url'];?>x" data-title="<?=$row['name'];?>" name="<?php echo strtolower($row['name']);?>">
                                    
                                    <a class="tab-link">
                                        
                                        <?php if($row['iconurl']) : ?>
                                        
                                            <i style="font-size: 19px; padding: 0 10px; font-size: 19px;">
                                                <img src="<?=$row['iconurl'];?>" style="height: 30px; width: 30px; margin-top: -2px;">
                                            </i>
                                        
                                        <?php else : ?>
                                        
                                            <i class="fa <?=$row['icon'];?> fa-lg"></i>
    
                                        <?php endif; ?>
                                        
                                        <?=$row['name'];?>
                                    
                                    </a>

                                </li>
                                
                                <?php $tabCount++; endforeach; endif;?>
                                
                                <?php if($configReady == "Yes") : if($USER->authenticated && $USER->role == "admin") :?>
                                <li class="tab-item <?=$settingsActive;?>" id="settings.phpx" data-title="Settings" name="settings">
                                                            
                                    <a class="tab-link">
                                        
                                        <?php if($settingsicon == "Yes") :
                                        
                                            echo '<i style="font-size: 19px; padding: 0 10px; font-size: 19px;">
                                                <img id="settings-icon" src="images/' . $settingsIcon . '" style="height: 30px; margin-top: -2px;"></i>';
                                        
                                        else :
                                        
                                            echo '<i id="settings-icon" class="fa fa-cog"></i>';
                                        
                                        endif; ?>
                                        
                                        <?php echo $language->translate("SETTINGS");?>
                                    
                                    </a>
                                
                                </li>
                                
                                <li style="display: none;" class="tab-item" id="updatedb.phpx" data-title="Upgrade" name="upgrade">
                                                            
                                    <a class="tab-link">
                                        
                                        <?php if($settingsicon == "Yes") :
                                        
                                            echo '<i style="font-size: 19px; padding: 0 10px; font-size: 19px;">
                                                <img id="upgrade-icon" src="images/upgrade.png" style="height: 30px; margin-top: -2px;"></i>';
                                        
                                        else :
                                        
                                            echo '<i id="upgrade-icon" class="fa fa-arrow-up"></i>';
                                        
                                        endif; ?>
                                        
                                        <?php echo $language->translate("UPGRADE");?>
                                    
                                    </a>
                                
                                </li>
                                <?php endif; endif;?>
                                
                                <!--End Tab List-->
                           
                            </ul>
                        
                        </div>

                        <!-- /gn-scroller -->
                        <div class="bottom-bnts">
                            
                            <a class="fix-nav"><i class="mdi mdi-pin"></i></a>
                        
                        </div>
                    
                    </nav>
                
                </li>

                <li class="top-clock">
                    
                    <?php 
                    
                    if($configReady == "Yes") : 
                    
                        if(TITLELOGO == "") : 
                    
                            echo "<span><span style=\"color: $topbartext\"><b>$title</b></span></span>"; 
                    
                        else : 
                    
                            echo "<img class='titlelogoclass' src='" . TITLELOGO . "'>";
                    
                        endif;
                    
                    else :
                    
                        echo "<span><span style=\"color: $topbartext\"><b>$title</b></span></span>"; 
                    
                    endif;
                    
                    ?>
                
                </li>

                <li class="pull-right">
                    
                    <ul class="nav navbar-right right-menu">
                        
                        <li class="dropdown notifications">
                            
                            <?php if($configReady == "Yes") : if(!$USER->authenticated) : ?>
                            
                            <a class="log-in">
                            
                            <?php endif; endif;?>
                            
                            <?php if($configReady == "Yes") : if($USER->authenticated) : ?>
                            
                            <a class="show-members">
                                
                            <?php endif; endif;?>
                                
                                <i class="userpic"><?=$showPic;?></i> 
                                
                            </a>
                            
                        </li>
                        
                        <li class="dropdown some-btn">
                            
                            <a class="fullscreen">
                                
                                <i class="mdi mdi-fullscreen"></i>
                            
                            </a>
                        </li>
                        
                        <li class="dropdown some-btn">
                            
                            <a id="reload" class="refresh">
                               
                                <i class="mdi mdi-refresh"></i>
                           
                            </a>
                        
                        </li>
                        
                        <li style="display: none" id="splitView" class="dropdown some-btn">
                            
                            <a class="spltView">
                                
                                <i class="mdi mdi-window-close"></i>
                            
                            </a>
                        </li>
                    
                    </ul>
                
                </li>
            
            </ul>

            <!--Content-->
            <div id="content" class="content" style="">
                <script>addToHomescreen();</script>

                <!--Load Framed Content-->
                <?php if($needSetup == "Yes" && $configReady == "Yes") : ?>
                <div class="table-wrapper" style="background:<?=$sidebar;?>;">

                    <div class="table-row">

                        <div class="table-cell text-center">

                            <div class="login i-block">

                                <div class="content-box">

                                    <div class="biggest-box" style="background:<?=$topbar;?>;">

                                        <h1 class="zero-m text-uppercase" style="color:<?=$topbartext;?>;"><?php echo $language->translate("CREATE_ADMIN");?></h1>

                                    </div>

                                    <div class="big-box text-left registration-form">

                                        <h4 class="text-center"><?php echo $language->translate("CREATE_ACCOUNT");?></h4>

                                        <form class="controlbox" name="new user registration" id="registration" action="" method="POST" data-smk-icon="glyphicon-remove-sign">

                                            <input type="hidden" name="op" value="register"/>
                                            <input type="hidden" name="sha1" value=""/>
                                            <input type="hidden" name="settings" value="false"/>

                                            <div class="form-group">

                                                <input type="text" class="form-control material" name="username" autofocus placeholder="<?php echo $language->translate("USERNAME");?>" autocorrect="off" autocapitalize="off" minlength="3" maxlength="16" required>

                                            </div>

                                            <div class="form-group">

                                                <input type="email" class="form-control material" name="email" placeholder="<?php echo $language->translate("EMAIL");?>">

                                            </div>

                                            <div class="form-group">

                                                <input type="password" class="form-control material" name="password1" placeholder="<?php echo $language->translate("PASSWORD");?>" data-smk-strongPass="weak" required>

                                            </div>

                                            <div class="form-group">

                                                <input type="password" class="form-control material" name="password2" placeholder="<?php echo $language->translate("PASSWORD_AGAIN");?>">

                                            </div>

                                            <button id="registerSubmit" style="background:<?=$topbar;?>;" type="submit" class="btn btn-block text-uppercase waves waves-effect waves-float" value="Register"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("REGISTER");?></text></button>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>
                <?php endif; ?>
                
                
                <?php if($needSetup == "Yes" && $configReady == "No") : ?>
                <div class="table-wrapper" style="background:<?=$sidebar;?>;">

                    <div class="table-row">

                        <div class="table-cell text-center">

                            <div class="login i-block">

                                <div class="content-box">

                                    <div class="biggest-box" style="background:<?=$topbar;?>;">

                                        <h1 class="zero-m text-uppercase" style="color:<?=$topbartext;?>;"><?php echo $language->translate("DATABASE_PATH");?></h1>

                                    </div>

                                    <div class="big-box text-left">

                                        <h3 class="text-center"><?php echo $language->translate("SPECIFY_LOCATION");?></h3>
                                        <h5 class="text-left"><strong><?php echo $language->translate("CURRENT_DIRECTORY");?>: <?php echo __DIR__; ?> <br><?php echo $language->translate("PARENT_DIRECTORY");?>: <?php echo dirname(__DIR__); ?></strong></h5>
                                        
                                        <form class="controlbox" name="setupDatabase" id="setupDatabase" action="" method="POST" data-smk-icon="glyphicon-remove-sign">
                                            
                                            <input type="hidden" name="action" value="createLocation" />

                                            <div class="form-group">

                                                <input type="text" class="form-control material" name="databaseLocation" autofocus value="<?php echo dirname(__DIR__);?>" autocorrect="off" autocapitalize="off" required>
                                                
                                                <h5><?php echo $language->translate("SET_DATABASE_LOCATION");?></h5>
                                                
                                                <?php echo getTimezone();?>
                                                
                                                <h5><?php echo $language->translate("SET_TIMEZONE");?></h5>
                                                
                                                <?php 
                                                
                                                if(file_exists(dirname(__DIR__) . '/users.db') || file_exists(__DIR__ . '/users.db')) : 
                                                
                                                echo '<h5 class="text-center red">';
                                                echo $language->translate("DONT_WORRY");
                                                echo '</h5>'; 
                                                
                                                endif;?>

                                            </div>

                                            <button style="background:<?=$topbar;?>;" id="databaseLocationSubmit" type="submit" class="btn btn-block btn-sm text-uppercase waves waves-effect waves-float" value="Save Location"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("SAVE_LOCATION");?></text></button>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>
                <?php endif; ?>
                
                
                <?php if($configReady == "Yes") : if(!$USER->authenticated && $tabSetup == "Yes" && $needSetup == "No") :?>
                <div class="table-wrapper">
                
                    <div class="table-row">
                
                        <div class="table-cell text-center">
                        
                            <div class="login i-block">
                                
                                <div class="content-box">
                                    
                                    <div class="blue-bg biggest-box">
                
                                        <h1 class="zero-m text-uppercase"><?php echo $language->translate("AWESOME");?></h1>
                
                                    </div>
                
                                    <div class="big-box text-left">
                
                                        <h4 class="text-center"><?php echo $language->translate("TIME_TO_LOGIN");?></h4>
                                        
                                        <button type="submit" class="btn log-in btn-block btn-primary text-uppercase waves waves-effect waves-float"><?php echo $language->translate("LOGIN");?></button>
                						                                    
                                    </div>
                                
                                </div>
                            
                            </div>
                        
                        </div>
                    
                    </div>
                
                </div>
                <?php endif; endif; ?>
                <?php if($tabSetup == "No" && $needSetup == "No") :?>        
                <div id="tabEmpty" class="table-wrapper" style="display: none; background:<?=$sidebar;?>;">
                
                    <div class="table-row">
                
                        <div class="table-cell text-center">
                        
                            <div class="login i-block">
                                
                                <div class="content-box">
                                    
                                    <div class="biggest-box" style="background:<?=$topbar;?>;">
                
                                        <h1 class="zero-m text-uppercase" style="color:<?=$topbartext;?>;"><?php echo $language->translate("HOLD_UP");?></h1>
                
                                    </div>
                
                                    <div class="big-box text-left">
                
                                        <center><img src="images/sowwy.png" style="height: 200px;"></center>
                                        <h2 class="text-center"><?php echo $language->translate("LOOKS_LIKE_YOU_DONT_HAVE_ACCESS");?></h2>
                                        
                                        <?php if(!$USER->authenticated) : ?>
                                        <button style="background:<?=$topbar;?>;" type="submit" class="btn log-in btn-block btn-primary text-uppercase waves waves-effect waves-float"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("LOGIN");?></text></button>
                                        <?php endif; ?>
        						                                    
                                    </div>
                                
                                </div>
                            
                            </div>
                        
                        </div>
                    
                    </div>
                
                </div>
                <?php endif;?>
                <!--End Load Framed Content-->
            
            </div>
            <div id="contentRight" class="content splitRight" style="">
            </div>
            <!--End Content-->

            <!--Welcome notification-->
            <div id="welcome"></div>
            
            <div id="members-sidebar" style="background: <?=$sidebar;?>;" class="members-sidebar fade in">
                
                <h4 class="pull-left zero-m"><?php echo $language->translate("OPTIONS");?></h4>
                
                <span class="close-members-sidebar"><i class="fa fa-remove fa-lg pull-right"></i></span>
                
                <div class="clearfix"><br/></div>
                
                <?php if($configReady == "Yes") : if($USER->authenticated) : ?>
                
                <br>
                
                <div class="content-box profile-sidebar box-shadow">
                
                    <img src="https://www.gravatar.com/avatar/<?=$userpic;?>?s=100&d=mm" class="img-responsive img-circle center-block" alt="user" https:="" www.gravatar.com="" avatar="">
                
                    <div class="profile-usertitle">
                
                        <div class="profile-usertitle-name">
                
                            <?php echo strtoupper($USER->username); ?>
                
                        </div>
                
                        <div class="profile-usertitle-job">
                
                            <?php echo strtoupper($USER->role); ?>
                
                        </div>
                
                    </div>
                
                    <div id="buttonsDiv" class="profile-userbuttons">
                
                        <button id="editInfo" type="button" class="btn btn-primary text-uppercase waves waves-effect waves-float"><?php echo $language->translate("EDIT_INFO");?></button>
                
                        <button type="button" class="logout btn btn-warning waves waves-effect waves-float"><?php echo $language->translate("LOGOUT");?></button>
                
                    </div>
                    
                    <div id="editInfoDiv" style="display: none" class="profile-usertitle">
                         
                        <form class="content-form form-inline" name="update" id="update" action="" method="POST">

                            <input type="hidden" name="op" value="update"/>
                            <input type="hidden" name="sha1" value=""/>
                            <input type="hidden" name="username" value="<?php echo $USER->username; ?>"/>
                            <input type="hidden" name="role" value="<?php echo $USER->role; ?>"/>

                            <div class="form-group">

                                <input autocomplete="off" type="text" value="<?php echo $USER->email; ?>" class="form-control" name="email" placeholder="<?php echo $language->translate("EMAIL_ADDRESS");?>">

                            </div>

                            <div class="form-group">

                                <input autocomplete="off" type="password" class="form-control" name="password1" placeholder="<?php echo $language->translate("PASSWORD");?>">

                            </div>

                            <div class="form-group">

                                <input autocomplete="off" type="password" class="form-control" name="password2" placeholder="<?php echo $language->translate("PASSWORD_AGAIN");?>">

                            </div>

                            <br>

                            <div class="form-group">

                                <input type="button" class="btn btn-success text-uppercase waves-effect waves-float" value="<?php echo $language->translate("UPDATE");?>" onclick="User.processUpdate()"/>
                                
                                <button id="goBackButtons" type="button" class="btn btn-primary text-uppercase waves waves-effect waves-float"><?php echo $language->translate("GO_BACK");?></button>

                            </div>

                        </form>

                    </div>
                    
                </div>

                <?php endif; endif;?>

            </div>

        </div>
        <?php if($configReady == "Yes") : if(!$USER->authenticated && $configReady == "Yes") : ?>
        <div class="login-modal modal fade">
            
            <div style="background:<?=$sidebar;?>;" class="table-wrapper">
                
                <div class="table-row">
                    
                    <div class="table-cell text-center">
                        
                        <button style="color:<?=$topbartext;?>;" type="button" class="close" data-dismiss="modal" aria-label="Close">
                            
                            <span aria-hidden="true">&times;</span>
                        
                        </button>
                        
                        <div class="login i-block">
                            
                            <div class="content-box">
                                
                                <div style="background:<?=$topbar;?>;" class="biggest-box">

                                    <h1 style="color:<?=$topbartext;?>;" class="zero-m text-uppercase"><?php echo $language->translate("WELCOME");?></h1>

                                </div>
                                
                                <div class="big-box text-left login-form">

                                    <?php if($USER->error!="") : ?>
                                    <p class="error">Error: <?php echo $USER->error; ?></p>
                                    <?php endif; ?>
                                    
                                    <form name="log in" id="login" action="" method="POST" data-smk-icon="glyphicon-remove-sign">
                                        
                                        <h4 class="text-center"><?php echo $language->translate("LOGIN");?></h4>
                                        
                                        <div class="form-group">
                                            
                                            <input type="hidden" name="op" value="login">
				                            <input type="hidden" name="sha1" value="">
                                            <input type="hidden" name="rememberMe" value="false"/>
                                            <input type="text" class="form-control material" name="username" placeholder="<?php echo $language->translate("USERNAME");?>" autocomplete="off" autocorrect="off" autocapitalize="off" value="" autofocus required>
                                        
                                        </div>
                                        
                                        <div class="form-group">
                                            
                                            <input type="password" class="form-control material" name="password1" value="" autocomplete="off" placeholder="<?php echo $language->translate("PASSWORD");?>" required>
                                        
                                        </div>
                                        
                                        <div class="form-group">
                                            
                                            <div class="i-block"> <input id="rememberMe" name="rememberMe" class="switcher switcher-success switcher-medium pull-left" value="true" type="checkbox" checked=""> 
                                                
                                                <label for="rememberMe" class="pull-left"></label>
                                            
                                                <label class="pull-right"> &nbsp; <?php echo $language->translate("REMEMBER_ME");?></label>
                                            
                                            </div>

                                        </div>

                                        <button id="loginSubmit" style="background:<?=$topbar;?>;" type="submit" class="btn btn-block btn-info text-uppercase waves" value="log in" onclick="User.processLogin()"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("LOGIN");?></text></button>

                                    </form> 
                                    
                                    <?php if (ENABLEMAIL == "true") : ?>
                                    <button id="switchForgot" style="background:<?=$topbartext;?>;" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("FORGOT_PASSWORD");?></text></button>
                                    <?php endif; ?>
    
                                    <?php if(REGISTERPASSWORD != "") : ?>
                                    <button id="switchCreateUser" style="background:<?=$hoverbg;?>;" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$hovertext;?>;"><?php echo $language->translate("CREATE_USER");?></text></button>
                                    <?php endif; ?>
                                    
                                    <form style="display: none;" name="forgotPassword" id="forgotPassword" action="" method="POST" data-smk-icon="glyphicon-remove-sign">
                                        
                                        <h4 class="text-center"><?php echo $language->translate("FORGOT_PASSWORD");?></h4>
                                        
                                        <div class="form-group">
                                            
                                            <input type="hidden" name="op" value="reset">
                                            <input type="text" class="form-control material" name="email" placeholder="<?php echo $language->translate("EMAIL");?>" autocorrect="off" autocapitalize="off" value="" autofocus required>
                                        
                                        </div>

                                        <button style="background:<?=$topbar;?>;" type="submit" class="btn btn-block btn-info text-uppercase waves" value="reset password"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("RESET_PASSWORD");?></text></button>

                                    </form> 
                                    
                                    <button id="welcomeGoBack" style="background:<?=$topbartext;?>; display: none" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("GO_BACK");?></text></button>
                                    
                                    <?php if(REGISTERPASSWORD != "") : ?>
                                    <div id="userPassForm" style="display: none;">
                                        <form id="userCreateForm" action="register.php" method="POST">
                                            <h4 class="text-center"><?php echo $language->translate("ENTER_PASSWORD_TO_REGISTER");?></h4>
                                            
                                            <center><h5 id="userCreateErrors" style="color: red"></h5></center>

                                            <div class="form-group">

                                                <input type="text" class="form-control material" name="registerPasswordValue" placeholder="<?php echo $language->translate("PASSWORD");?>" autocorrect="off" autocapitalize="off" value="" autofocus required>

                                            </div>

                                            <button style="background:<?=$topbar;?>;" type="submit" id="checkRegisterPass" class="btn btn-block btn-info text-uppercase waves" value="reset password"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("SUBMIT");?></text></button>
                                        </form>
                                        
                                        <button id="welcomeGoBack2" style="background:<?=$topbartext;?>; display: none" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("GO_BACK");?></text></button>
                                    
                                    </div>
                                    
                                    <form style="display: none;" name="createUser" id="registration" action="" method="POST" data-smk-icon="glyphicon-remove-sign">
                                        
                                        <h4 class="text-center"><?php echo $language->translate("CREATE_USER");?></h4>
                                        
                                        <input type="hidden" name="op" value="register"/>
                                        <input type="hidden" name="sha1" value=""/>
                                        <input type="hidden" name="settings" value="false"/>

                                        <div class="form-group">

                                            <input type="text" class="form-control material" name="username" autofocus placeholder="<?php echo $language->translate("USERNAME");?>" autocorrect="off" autocapitalize="off" minlength="3" maxlength="16" required>

                                        </div>

                                        <div class="form-group">

                                            <input type="email" class="form-control material" name="email" placeholder="<?php echo $language->translate("EMAIL");?>">

                                        </div>

                                        <div class="form-group">

                                            <input type="password" class="form-control material" name="password1" placeholder="<?php echo $language->translate("PASSWORD");?>" data-smk-strongPass="weak" required>

                                        </div>

                                        <div class="form-group">

                                            <input type="password" class="form-control material" name="password2" placeholder="<?php echo $language->translate("PASSWORD_AGAIN");?>">

                                        </div>

                                        <button id="registerSubmit" type="submit" class="btn green-bg btn-block btn-warning text-uppercase waves waves-effect waves-float" value="Register"><?php echo $language->translate("REGISTER");?></button>
                                        
                                        <button id="welcomeGoBack3" style="background:<?=$topbartext;?>; display: none" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("GO_BACK");?></text></button>

                                    </form> 
                                    <?php endif; ?>
                                    
                                </div>
                            
                            </div>
                       
                        </div>
                    
                    </div>
                
                </div>
            
            </div>
        
        </div>
        <?php endif; endif;?>
        <?php if($configReady == "Yes") : if($USER->authenticated) : ?>
        <div style="background:<?=$topbar;?>;" class="logout-modal modal fade">
            
            <div class="table-wrapper" style="background: <?=$sidebar;?>">
            
                <div class="table-row">
                
                    <div class="table-cell text-center">
                    
                        <div class="login i-block">
                        
                            <div class="content-box">
                            
                                <div style="background:<?=$topbar;?>;" class="biggest-box">
                                    
                                    <h1 style="color:<?=$topbartext;?>;" class="zero-m text-uppercase"><?php echo $language->translate("LOGOUT");?></h1>
                                    
                                </div>
                                
                                <div class="big-box login-form">
                                    
                                    <form name="log out" id="logout" action="" method="POST">
                                        
				                        <input type="hidden" name="op" value="logout">
                                        
                                        <input type="hidden" name="username"value="<?php echo $_SESSION["username"]; ?>" >
			                         
                                        <center><img src="images/sowwy.png" style="height: 200px;"></center>
                                        
                                        <h3 style="color:<?=$topbar;?>;" class="zero-m text-uppercase"><?php echo $language->translate("DO_YOU_WANT_TO_LOGOUT");?></h3>
                                        
                                        <a style="color:<?=$topbar;?>;" id="logoutSubmit" class="i-block" data-dismiss="modal"><?php echo $language->translate("YES_WORD");?></a>
                                        
                                        <a style="color:<?=$topbar;?>;" class="i-block" data-dismiss="modal"><?php echo $language->translate("NO_WORD");?></a>
                                
                                    </form>
                                    
                                </div>
                            
                            </div>
                    
                        </div>
                
                    </div>
            
                </div>
        
            </div>
    
        </div>
        <?php endif; endif;?>

        <!--Scripts-->
        <script src="<?=$baseURL;?>bower_components/jquery/dist/jquery.min.js"></script>
        <script src="<?=$baseURL;?>bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
        <script src="<?=$baseURL;?>bower_components/metisMenu/dist/metisMenu.min.js"></script>
        <script src="<?=$baseURL;?>bower_components/Waves/dist/waves.min.js"></script>
        <script src="<?=$baseURL;?>bower_components/moment/min/moment.min.js"></script>
        <script src="<?=$baseURL;?>bower_components/jquery.nicescroll/jquery.nicescroll.min.js"></script>
        <script src="<?=$baseURL;?>bower_components/slimScroll/jquery.slimscroll.min.js"></script>
        <script src="<?=$baseURL;?>bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.js"></script>
        <script src="<?=$baseURL;?>bower_components/cta/dist/cta.min.js"></script>

        <!--Menu-->
        <script src="<?=$baseURL;?>js/menu/classie.js"></script>
        <script src="<?=$baseURL;?>js/menu/gnmenu.js"></script>

        <!--Selects-->
        <script src="<?=$baseURL;?>js/selects/selectFx.js"></script>
        
        <script src="<?=$baseURL;?>bower_components/sweetalert/dist/sweetalert.min.js"></script>

        <script src="<?=$baseURL;?>bower_components/smoke/dist/js/smoke.min.js"></script>

        <!--Notification-->
        <script src="<?=$baseURL;?>js/notifications/notificationFx.js"></script>

        <!--Custom Scripts-->
        <script src="<?=$baseURL;?>js/common.js"></script>
        <script src="<?=$baseURL;?>js/mousetrap.min.js"></script>

        <script>

        var fixed = document.getElementById('gn-scroller');
            
        fixed.addEventListener('touchmove', function(e) {

            e.preventDefault();

        }, false);    
            
        function setHeight() {
            
            windowHeight = $(window).innerHeight();
            
            $("div").find(".iframe").css('height', windowHeight - <?=$slimBar;?> + "px");
            
            $('#content').css('height', windowHeight - <?=$slimBar;?> + "px");
            
        };
            
        function notify(notifyString, notifyIcon, notifyType, notifyLength, notifyLayout, notifyEffect) {
            
            var notifyString = notifyString;
            var notifyIcon = notifyIcon;
            var notifyType = notifyType;
            var notifyLength = notifyLength;
            var notifyLayout = notifyLayout; 
            var notifyEffect = notifyEffect;
            
            if (notifyEffect === "slidetop"){
                
                var addMeesage = '<span class="fa fa-' + notifyIcon + ' fa-2x"></span>' + '<p>' + notifyString + '</p>';
                
            }else if (notifyEffect === "exploader"){
                
                var addMeesage = '<span class="fa fa-' + notifyIcon + ' fa-2x pull-left"></span>' + '<p>' + notifyString + '</p>';
            
            }else if (notifyEffect === "thumbslider"){
                
                var addMeesage = '<div class="ns-thumb"><img src="images/alert.png"/></div><div class="ns-content"><p>' + notifyString + '</p></div>';    
                
            }else{
                
                var addMeesage = '<p>' + notifyString + '</p>';
            
            }

            setTimeout(function () {

                var notification = new NotificationFx({
                    
                    message: addMeesage,

                    layout: notifyLayout,

                    effect: notifyEffect,
                    
                    ttl: notifyLength,

                    type: notifyType,
                    
                    onClose: function () {
                        
                        $(".ns-box.ns-effect-thumbslider").fadeOut(400);
                    }

                });

                notification.show();

            }, 500);

        }
            
        $('#loginSubmit').click(function() {
            
            if ($('#login').smkValidate()) {
                
                console.log("validated");
                
            }
            
            console.log("didnt validate");
            
        });
            
        $('#registerSubmit').click(function() {
            
            if ($('#registration').smkValidate()) {
                
                console.log("validated");
                
            }
            
            console.log("didnt validate");
            User.processRegistration();
            
        });
            
        $("#editInfo").click(function(){

            $( "div[id^='editInfoDiv']" ).toggle();
            $( "div[id^='buttonsDiv']" ).toggle();
     
        });
            
        $("#goBackButtons").click(function(){

            $( "div[id^='editInfoDiv']" ).toggle();
            $( "div[id^='buttonsDiv']" ).toggle();
     
        });
            
        $("#welcomeGoBack").click(function(){
            
            $( "form[id^='login']" ).toggle();
            $( "form[id^='forgotPassword']" ).toggle();
            $("#switchForgot").toggle();
            $("#switchCreateUser").toggle();
            $("#welcomeGoBack").toggle();
     
        });
            
        $("#welcomeGoBack2").click(function(){
            
            $( "form[id^='login']" ).toggle();
            $("#userPassForm").toggle();
            $("#switchForgot").toggle();
            $("#switchCreateUser").toggle();
            $("#welcomeGoBack2").toggle();
     
        });
            
        $("#welcomeGoBack3").click(function(){
            
            $("#registration").toggle();  
            $("#welcomeGoBack3").toggle();
            $( "form[id^='login']" ).toggle();
            $("#switchForgot").toggle();
            $("#switchCreateUser").toggle();
     
        });
            
        $("#switchForgot").click(function(){

            $( "form[id^='login']" ).toggle();
            $( "form[id^='forgotPassword']" ).toggle();
            $("#switchForgot").toggle();
            $("#switchCreateUser").toggle();
            $("#welcomeGoBack").toggle();
     
        });
            
        $("#switchCreateUser").click(function(){

            $( "form[id^='login']" ).toggle();
            $("#userPassForm").toggle();
            $("#switchForgot").toggle();
            $("#switchCreateUser").toggle();
            $("#welcomeGoBack2").toggle();
     
        });  
            
        //Sign in
        $(".log-in").click(function(e){
            
            var e1 = document.querySelector(".log-in"),
            
                e2 = document.querySelector(".login-modal");
            
            cta(e1, e2, {relativeToWindow: true}, function () {
                
                $('.login-modal').modal("show");
            
            });

            e.preventDefault();
        
        });

        //Logout
        $(".logout").click(function(e){
        var el1 = document.querySelector(".logout"),
        el2 = document.querySelector(".logout-modal");
        cta(el1, el2, {relativeToWindow: true}, function () {
        $('.logout-modal').modal("show");
        });

        e.preventDefault();
        });

        //Members Sidebar
        $(".show-members").click(function(e){
        var e_s1 = document.querySelector(".show-members"),
        e_s2 = document.querySelector("#members-sidebar");

        cta(e_s1, e_s2, {relativeToWindow: true}, function () {
        $('#members-sidebar').addClass('members-sidebar-open');
        });

        e.preventDefault();
        });

        $('.close-members-sidebar').click(function(){
        $('#members-sidebar').removeClass('members-sidebar-open');
        });

        $(document).ready(function(){
            
            $('#userCreateForm').submit(function(event) {

                var formData = {
                    'registerPasswordValue' : $('input[name=registerPasswordValue]').val()
                };

                $.ajax({
                    type        : 'POST', 
                    url         : 'register.php', 
                    data        : formData,
                    dataType    : 'json',
                    encode      : true
                })
                 
                    .done(function(data) {

                        console.log(data); 
                    
                        if ( ! data.success) {

                            $('#userCreateErrors').html('Wrong Password!'); // add the actual error message under our input

                        } else {

                            $("#userPassForm").toggle();
                            $("#registration").toggle(); 
                            $("#welcomeGoBack3").toggle();

                        }

                    });

                event.preventDefault();
                
            });
            
            defaultTab = $("li[class^='tab-item active']").attr("id");
           
            if (defaultTab){
           
                defaultTab = defaultTab.substr(0, defaultTab.length-1);
           
            }else{
           
                defaultTabNone = $("li[class^='tab-item']").attr("id");
                
                if (defaultTabNone){
                
                    $("li[class^='tab-item']").first().attr("class", "tab-item active");
                    defaultTab = defaultTabNone.substr(0, defaultTabNone.length-1);
           
                }
            
            }

            if (defaultTab){

                $("#content").html('<div class="iframe active" data-content-url="'+defaultTab+'"><iframe scrolling="auto" sandbox="allow-forms allow-same-origin allow-pointer-lock allow-scripts allow-popups allow-modals allow-top-navigation" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" frameborder="0" style="width:100%; height:100%;" src="'+defaultTab+'"></iframe></div>');
                document.getElementById('main-wrapper').focus();
                
            }
            
            if (defaultTab == null){
             
                $("div[id^='tabEmpty']").show();
                <?php if($needSetup == "No" && $configReady == "Yes") : if(!$USER->authenticated) : ?>
                $('.login-modal').modal("show");
                <?php endif; endif; ?>
                
            }
            
            if ($(location).attr('hash')){
            
                var getHash = $(location).attr('hash').substr(1).replace("%20", " ").replace("_", " ");

                var gotHash = getHash.toLowerCase();

                var getLiTab = $("li[name^='" + gotHash + "']");
                
                if(gotHash === "upgrade"){ getLiTab.toggle(); console.log("got it"); }
                
                getLiTab.trigger("click");
                
                

            }   

            setHeight();

        }); 
            
        <?php if(!empty($USER->info_log)) : ?>

        notify("<?php echo printArray($USER->info_log); ?>","info-circle","notice","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");

        <?php endif; ?>

        <?php if(!empty($USER->error_log)) : ?>

        notify("<?php echo printArray($USER->error_log); ?>","exclamation-circle ","error","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");

        <?php endif; ?>

            
        $('#reload').on('click tap', function(){

            $("i[class^='mdi mdi-refresh']").attr("class", "mdi mdi-refresh fa-spin");

            var activeFrame = $('#content').find('.active').children('iframe');

            activeFrame.attr('src', activeFrame.attr('src'));

            var refreshBox = $('#content').find('.active');

            $("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(refreshBox).fadeIn(10);

            setTimeout(function(){

                var refreshPreloader = refreshBox.find('.refresh-preloader'),
                deletedRefreshBox = refreshPreloader.fadeOut(300, function(){

                    refreshPreloader.remove();
                    $("i[class^='mdi mdi-refresh fa-spin']").attr("class", "mdi mdi-refresh");

                });

            },500);

        });
            
        $('#reload').on('contextmenu', function(e){

            $("i[class^='mdi mdi-refresh']").attr("class", "mdi mdi-refresh fa-spin");

            var activeFrame = $('#contentRight').find('.active').children('iframe');

            activeFrame.attr('src', activeFrame.attr('src'));

            var refreshBox = $('#contentRight').find('.active');

            $("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(refreshBox).fadeIn(10);

            setTimeout(function(){

                var refreshPreloader = refreshBox.find('.refresh-preloader'),
                deletedRefreshBox = refreshPreloader.fadeOut(300, function(){

                    refreshPreloader.remove();
                    $("i[class^='mdi mdi-refresh fa-spin']").attr("class", "mdi mdi-refresh");

                });

            },500);
            
            return false;

        });
            
        $('#splitView').on('click tap', function(){

            $('#splitView').hide();
            $("#content").attr("class", "content");
            $("li[class^='tab-item rightActive']").attr("class", "tab-item");
            $("#contentRight").html('');

        });
        <?php if($iconRotate == "true") : ?>   
        $("li[id^='settings.phpx']").on('click tap', function(){

            $("img[id^='settings-icon']").attr("class", "fa-spin");
            $("i[id^='settings-icon']").attr("class", "fa fa-cog fa-spin");

            setTimeout(function(){

                $("img[id^='settings-icon']").attr("class", "");
                $("i[id^='settings-icon']").attr("class", "fa fa-cog");

            },1000);

        });
        <?php endif; ?>

        $('#logoutSubmit').on('click tap', function(){

            $( "#logout" ).submit();

        });
            
        $(window).resize(function(){
            
            setHeight();

        });
            
        $("li[class^='tab-item']").on('click vclick', function(){
                
            var thisidfull = $(this).attr("id");
            
            var thistitle = $(this).attr("data-title");
            
            var thisname = $(this).attr("name");

            var thisid = thisidfull.substr(0, thisidfull.length-1);

            var currentframe = $("#content div[data-content-url^='"+thisid+"']");

            if (currentframe.attr("class") == "iframe active") {

                console.log(thisid + " is active already");
                
                setHeight();

            }else if (currentframe.attr("class") == "iframe hidden") {

                console.log(thisid + " is active already but hidden");

                $("#content div[class^='iframe active']").attr("class", "iframe hidden");

                currentframe.attr("class", "iframe active");
                
                document.title = thistitle;
                
                window.location.href = '#' + thisname;
                
                setHeight();

                $("li[class^='tab-item active']").attr("class", "tab-item");

                $(this).attr("class", "tab-item active");

            }else {
                
                if ($(this).attr("window") == "true") {
                    
                    window.open(thisid,'_blank');
                    
                }else {
                
                    console.log(thisid + " make new div");

                    $("#content div[class^='iframe active']").attr("class", "iframe hidden");

                    $( '<div class="iframe active" data-content-url="'+thisid+'"><iframe scrolling="auto" sandbox="allow-forms allow-same-origin allow-pointer-lock allow-scripts allow-popups allow-modals allow-top-navigation" allowfullscreen="true" webkitallowfullscreen="true" frameborder="0" style="width:100%; height:100%;" src="'+thisid+'"></iframe></div>' ).appendTo( "#content" );
                    
                    document.title = thistitle;
                    
                    window.location.href = '#' + thisname;

                    setHeight();

                    $("li[class^='tab-item active']").attr("class", "tab-item");

                    $(this).attr("class", "tab-item active");
                    
                }

            }

        });
            
        $("li[class^='tab-item']").on('contextmenu', function(e){
            
            e.stopPropagation();
            
            $('#splitView').show();
            
            $("#content").attr("class", "content split");
            
            var thisidfull = $(this).attr("id");
            
            var thistitle = $(this).attr("data-title");
            
            var thisname = $(this).attr("name");

            var thisid = thisidfull.substr(0, thisidfull.length-1);

            var currentframe = $("#contentRight div[data-content-url^='"+thisid+"']");

            if (currentframe.attr("class") == "iframe active") {

                console.log(thisid + " is active already");

            }else if (currentframe.attr("class") == "iframe hidden") {

                console.log(thisid + " is active already but hidden");

                $("#contentRight div[class^='iframe active']").attr("class", "iframe hidden");

                currentframe.attr("class", "iframe active");
                
                document.title = thistitle;
                
                window.location.href = '#' + thisname;
                
                setHeight();

                $("li[class^='tab-item rightActive']").attr("class", "tab-item");

                $(this).attr("class", "tab-item rightActive");

            }else {
                
                if ($(this).attr("window") == "true") {
                    
                    window.open(thisid,'_blank');
                    
                }else {
                
                    console.log(thisid + " make new div");

                    $("#contentRight div[class^='iframe active']").attr("class", "iframe hidden");

                    $( '<div class="iframe active" data-content-url="'+thisid+'"><iframe scrolling="auto" sandbox="allow-forms allow-same-origin allow-pointer-lock allow-scripts allow-popups allow-modals allow-top-navigation" allowfullscreen="true" webkitallowfullscreen="true" frameborder="0" style="width:100%; height:100%;" src="'+thisid+'"></iframe></div>' ).appendTo( "#contentRight" );
                    
                    document.title = thistitle;
                    
                    window.location.href = '#' + thisname;

                    setHeight();

                    $("li[class^='tab-item rightActive']").attr("class", "tab-item");

                    $(this).attr("class", "tab-item rightActive");
                    
                }

            }
            
            return false;
                
        });
            
        Mousetrap.bind('ctrl+shift+up', function(e) {
            
            var getCurrentTab = $("li[class^='tab-item active']");
            var previousTab = getCurrentTab.prev().attr( "class", "tab-item" );
            previousTab.trigger("click");
            return false;
            
        }); 
            
        Mousetrap.bind('ctrl+shift+down', function(e) {
            
            var getCurrentTab = $("li[class^='tab-item active']");
            var nextTab = getCurrentTab.next().attr( "class", "tab-item" );
            nextTab.trigger("click");
            return false;
            
        });     

        Mousetrap.bind('s s', function() { $("li[id^='settings.phpx']").trigger("click");  });
        
        Mousetrap.bind('p p', function() { $("a[class^='fix-nav']").trigger("click");  });
            
        Mousetrap.bind('m m', function() { $("div[class^='hamburger']").trigger("click");  });
            
        Mousetrap.bind('r r', function() { $("a[id^='reload']").trigger("click");  });
            
        Mousetrap.bind('f f', function() { $("a[class^='fullscreen']").trigger("click");  });
            
        <?php if($tabSetup == "No") : foreach(range(1,$tabCount) as $index) : if ($index == 10) : break; endif;?>
            
        Mousetrap.bind('ctrl+shift+<?php echo $index; ?>', function() { $("ul[id^='tabList'] li:nth-child(<?php echo $index; ?>)").trigger("click"); });    
        <?php endforeach; endif; ?>
        
        Mousetrap.bind('esc esc', function() {
            
            $("#content").attr("class", "content");
            $("li[class^='tab-item rightActive']").attr("class", "tab-item");
            $("#contentRight").html('');
        
        });    
        
        var ref = document.referrer;
        if(ref.indexOf("updated")>=0){

            notify("<?php echo $language->translate('UPDATE_COMPLETE');?>","exclamation-circle ","success","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");

        }
            
        if(ref.indexOf("submit")>=0){

            notify("<?php echo $language->translate('CUSTOM_COMPLETE');?>","exclamation-circle ","success","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");

        } 
            
        </script>

    </body>

</html>
