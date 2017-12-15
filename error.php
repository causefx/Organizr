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
if(isset($_GET['error']) && $_GET['error'] !== '404'){
    $status = (isset($_GET['error'])?$_GET['error']:404);
	coookie('set','lec',$status,0.00006);
    http_response_code($status);
    //get file name
    if(!empty($_SERVER['PHP_SELF'])){
        $file = $_SERVER['PHP_SELF'];
    }elseif(!empty($_SERVER['SCRIPT_NAME'])){
        $file = $_SERVER['SCRIPT_NAME'];
    }else{
        $file = checkRootPath(dirname($_SERVER['SCRIPT_NAME'])).'error.php';
    }
    header('Location:'.$file);
    exit();
}
if(!isset($_COOKIE['lec'])) {
    $status = '404';
} else {
    $status = $_COOKIE['lec'];
}
// Create Database Connection
$file_db = new PDO('sqlite:'.DATABASE_LOCATION.'users.db');
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Some PHP config stuff
ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);
//Color stuff
foreach(loadAppearance() as $key => $value) {
	$$key = $value;
}
//error stuff
$requested = $_SERVER['REQUEST_URI'];
$codes = array(
       400 => array('Bad Request', 'The server cannot or will not process the request due to an apparent client error.', 'sowwy','400'),
       401 => array('Unauthorized', 'You do not have access to this page.', 'sowwy','401'),
       403 => array('Forbidden', 'The server has refused to fulfill your request.', 'sowwy','403'),
       404 => array('Not Found', $requested . ' was not found on this server.', 'confused','404'),
       405 => array('Method Not Allowed', 'The method specified in the Request-Line is not allowed for the specified resource.', 'confused','405'),
       408 => array('Request Timeout', 'Your browser failed to send a request in the time allowed by the server.', 'sowwy','408'),
       500 => array('Internal Server Error', 'The request was unsuccessful due to an unexpected condition encountered by the server.', 'confused','500'),
       502 => array('Bad Gateway', 'The server received an invalid response from the upstream server while trying to fulfill the request.', 'confused','502'),
       503 => array('Service Unavailable', 'The server is currently unavailable (because it is overloaded or down for maintenance).', 'confused','503'),
       504 => array('Gateway Timeout', 'The upstream server failed to send a request in the time allowed by the server.', 'confused','504'),
       999 => array('Not Logged In', 'You need to be logged in to access this page.', 'confused', '401'),
);
$errorTitle = ($codes[$status][0]) ? $codes[$status][0] : "Error";
$message = ($codes[$status][1]) ? $codes[$status][1] : "An Error Occured";
$errorImage = ($codes[$status][2]) ? $codes[$status][2] : "confused";
$status = ($codes[$status][3]) ? $codes[$status][3] : $status;
?>

<!DOCTYPE html>

<html lang="en" class="no-js">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />
        <title><?=$errorTitle;?></title>
        <link rel="stylesheet" href="<?php echo checkRootPath(dirname($_SERVER['SCRIPT_NAME'])); ?>bower_components/font-awesome/css/font-awesome.min.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" href="<?php echo checkRootPath(dirname($_SERVER['SCRIPT_NAME'])); ?>bower_components/bootstrap/dist/css/bootstrap.min.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" href="<?php echo checkRootPath(dirname($_SERVER['SCRIPT_NAME'])); ?>bower_components/Waves/dist/waves.min.css">
        <link rel="stylesheet" href="<?php echo checkRootPath(dirname($_SERVER['SCRIPT_NAME'])); ?>css/style.css?v=<?php echo INSTALLEDVERSION; ?>">
		<style><?php customCSS(); ?></style>
    </head>
    <body id="body-error" class="gray-bg" style="padding: 0;">
        <div class="main-wrapper" style="position: initial;">
            <div style="margin:0 20px; overflow:hidden">
                <div class="table-wrapper" style="background:<?=$sidebar;?>;">
                    <div class="table-row">
                        <div class="table-cell text-center">
                        	<div class="pagenotfound i-block">
                        		<div class="content-box">
                        			<div class="top" style="background:<?=$topbar;?>;">
                        				<h1 class="zero-m" style="color:<?=$topbartext;?>;"><?=$status;?></h1>
                        				<a href="#" onclick="parent.location='../'" class="fa-stack fa-2x icon-back">
                        					<i class="fa fa-circle fa-stack-2x" style="color:<?=$topbartext;?>;"></i>
                        					<i class="fa fa-long-arrow-left fa-stack-1x fa-inverse" style="color:<?=$topbar;?>;"></i>
                        				</a>
                        			</div>
                        			<div class="big-box">
                        				<h4 style="color: <?=$topbar;?>;"><?=$errorTitle;?></h4>
                        				<p style="color: <?=$topbar;?>;"><?php echo $message;?></p>
                        			</div>
                        		</div>
                        	</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
