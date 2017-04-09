<?php

$requested = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

if (isset($_GET['error'])) {
    
    $status = $_GET['error'];
    
}else{
    
    $status = "";
    
}

$codes = array(  
    
       400 => array('Bad Request', 'The server cannot or will not process the request due to an apparent client error.', 'sowwy'),
       401 => array('Unauthorized', 'You do not have access to this page.', 'sowwy'),
       403 => array('Forbidden', 'The server has refused to fulfill your request.', 'sowwy'),
       404 => array('Not Found', $requested . ' was not found on this server.', 'confused'),
       405 => array('Method Not Allowed', 'The method specified in the Request-Line is not allowed for the specified resource.', 'confused'),
       408 => array('Request Timeout', 'Your browser failed to send a request in the time allowed by the server.', 'sowwy'),
       500 => array('Internal Server Error', 'The request was unsuccessful due to an unexpected condition encountered by the server.', 'confused'),
       502 => array('Bad Gateway', 'The server received an invalid response from the upstream server while trying to fulfill the request.', 'confused'),
       503 => array('Service Unavailable', 'The server is currently unavailable (because it is overloaded or down for maintenance).', 'confused'),
       504 => array('Gateway Timeout', 'The upstream server failed to send a request in the time allowed by the server.', 'confused'),
       999 => array('Not Logged In', 'You need to be logged in to access this page.', 'confused'),
    
);

@$errorTitle = $codes[$status][0];
@$message = $codes[$status][1];
@$errorImage = $codes[$status][2];

if ($errorTitle == false || strlen($status) != 3) {
    
    $message = 'Please supply a valid status code.';
    $errorTitle = "Error";
    $errorImage = "confused";

}

$data = false;

ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);

function registration_callback($username, $email, $userdir)
{
    global $data;
    $data = array($username, $email, $userdir);
}

require_once("user.php");
$USER = new User("registration_callback");

$dbfile = DATABASE_LOCATION.'users.db';

$file_db = new PDO("sqlite:" . $dbfile);
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$dbOptions = $file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="options"');

$hasOptions = "No";

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

?>

<!DOCTYPE html>

<html lang="en" class="no-js">

    <head>
        
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />

        <title><?=$errorTitle;?></title>

        <link rel="stylesheet" href="/bower_components/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="/bower_components/Waves/dist/waves.min.css"> 
        <link rel="stylesheet" href="/css/style.css">
        
    </head>

    <body class="gray-bg" style="padding: 0;">

        <div class="main-wrapper" style="position: initial;">

            <div style="margin:0 20px; overflow:hidden">
                
                <div class="table-wrapper" style="background:<?=$sidebar;?>;">
                
                    <div class="table-row">
                
                        <div class="table-cell text-center">
                        
                            <div class="login i-block">
                                
                                <div class="content-box">
                                    
                                    <div class="biggest-box" style="background:<?=$topbar;?>;">
                
                                        <h1 class="zero-m text-uppercase" style="color:<?=$topbartext;?>; font-size: 40px;"><?=$errorTitle;?></h1>
                
                                    </div>
                
                                    <div class="big-box text-left">
                
                                        <center><img src="/images/<?=$errorImage;?>.png" style="height: 200px;"></center>
                                        <h4 style="color: <?=$topbar;?>;" class="text-center"><?php echo $message;?></h4>

                                        <button style="background:<?=$topbar;?>;" onclick="goBack()" type="button" class="btn log-in btn-block btn-primary text-uppercase waves waves-effect waves-float"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("GO_BACK");?></text></button>					                                    
                                    </div>
                                
                                </div>
                            
                            </div>
                        
                        </div>
                    
                    </div>
                
                </div>

            </div>

        </div>
        
        <script>
            
            function goBack() {
                window.history.back();
            }
            
        </script>

    </body>

</html>
