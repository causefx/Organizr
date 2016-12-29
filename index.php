<?php 

date_default_timezone_set("America/Los Angeles");

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

$dbfile = constant('User::DATABASE_LOCATION')  . constant('User::DATABASE_NAME') . ".db";
$database = new PDO("sqlite:" . $dbfile);

$needSetup = "Yes";

$query = "SELECT * FROM users";
			
foreach($database->query($query) as $data) {

    $needSetup = "No";

}

$db = constant('User::DATABASE_LOCATION')  . constant('User::DATABASE_NAME') . ".db";
$file_db = new PDO("sqlite:" . $db);
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$dbTab = $file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="tabs"');
$dbColor = $file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="color"');

$tabSetup = "Yes";	
$hasColors = "No";

foreach($dbTab as $row) :

    if (in_array("tabs", $row)) :
    
        $tabSetup = "No";
    
    endif;

endforeach;

foreach($dbColor as $row) :

    if (in_array("color", $row)) :
    
        $hasColors = "Yes";
    
    endif;

endforeach;

if($tabSetup == "No") :

    if($USER->authenticated && $USER->role == "admin") :
        $result = $file_db->query('SELECT * FROM tabs WHERE active = "true"');
    elseif($USER->authenticated && $USER->role == "user") :
        $result = $file_db->query('SELECT * FROM tabs WHERE active = "true" AND user = "true"');
    else :
        $result = $file_db->query('SELECT * FROM tabs WHERE active = "true" AND guest = "true"');
    endif;
    
endif;

$settingsActive = "";

if($tabSetup == "Yes") :

    $settingsActive = "active";
    
endif;

?>

<!DOCTYPE html>

<html lang="en" class="no-js">

    <head>
        
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />

        <title>myDashboard</title>

        <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="bower_components/mdi/css/materialdesignicons.min.css">
        <link rel="stylesheet" href="bower_components/metisMenu/dist/metisMenu.min.css">
        <link rel="stylesheet" href="bower_components/Waves/dist/waves.min.css"> 
        <link rel="stylesheet" href="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css"> 

        <link rel="stylesheet" href="js/selects/cs-select.css">
        <link rel="stylesheet" href="js/selects/cs-skin-elastic.css">
        <link rel="stylesheet" href="bower_components/google-material-color/dist/palette.css">
        
        <link rel="stylesheet" href="bower_components/sweetalert/dist/sweetalert.css">
        <link rel="stylesheet" href="bower_components/smoke/dist/css/smoke.min.css">


        <script src="js/menu/modernizr.custom.js"></script>
        <script type="text/javascript" src="js/sha1.js"></script>
		<script type="text/javascript" src="js/user.js"></script>

        <link rel="stylesheet" href="css/style.css">

        <link rel="icon" href="img/favicon.ico" type="image/x-icon" />
        <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon" />
        
        <!--[if lt IE 9]>
        <script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
        <script src="bower_components/respondJs/dest/respond.min.js"></script>
        <![endif]-->
        
    </head>

    <body>
        <?php $color = "#eb6363"; $color2 = "black"; ?>
        <!--Preloader-->
        <div id="preloader" class="preloader table-wrapper">
            
            <div class="table-row">
                
                <div class="table-cell">
                    
                    <div class="la-ball-scale-multiple la-3x" style="color: <?=$color;?>">
                        
                        <div></div>
                        <div></div>
                        <div></div>
                    
                    </div>
                
                </div>
            
            </div>
        
        </div>

        <div id="main-wrapper" class="main-wrapper">
            
            <style>
                .bottom-bnts a {
                    
                    background: <?=$color;?>;
                    color: white;
                
                }.bottom-bnts {
                    
                    background-color: <?=$color;?>;
                
                }.gn-menu-main {
                    
                   
                    background-color: <?=$color;?>;
                
                }.gn-menu-main ul.gn-menu {
                    
                    background: <?=$color2;?>;
                
                }.gn-menu-wrapper {
                
                    background: <?=$color2;?>;
                
                }.gn-menu i {
                    
                    height: 18px;
                    width: 52px;
                
                }.la-timer.la-dark {
                    
                    color: <?=$color;?>
                
                }.refresh-preloader {
                    
                    background: transparent;
                
                }.la-timer {
                    
                    width: 75px;
                    height: 75px;
                    padding-top: 20px;
                    border-radius: 10px;
                    background: <?=$color2;?>;
                    border: 2px solid <?=$color;?>;
                
                }.tab-item:hover a {
                    
                    color: #fff !important;
                    background: <?=$color;?>;
                
                }.gn-menu li.active > a {
                    
                    color: #fff !important;
                    background: <?=$color;?>;
                
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
                	    
                }
            </style>

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
                        
                        <div class="gn-scroller">
                            
                            <ul class="gn-menu metismenu">
                                <?php /*echo str_replace("\n", "<br/>\n\t\t\t", print_r($_POST, true)); 
                                echo str_replace("\n", "<br/>\n\t\t\t", print_r($USER->info_log, true)); 
                                echo str_replace("\n", "<br/>\n\t\t\t", print_r($USER->error_log, true)); */?>
                                <!--Start Tab List-->
                                
                                <?php if($tabSetup == "No") : foreach($result as $row) : 
                                
                                if($row['defaultz'] == "true") : $defaultz = "active"; else : $defaultz = ""; endif;?>
                                
                                <li class="tab-item <?=$defaultz;?>" id="<?=$row['url'];?>x">
                                    
                                    <a class="tab-link" href="#"><i class="fa <?=$row['icon'];?>"></i><?=$row['name'];?></a>

                                </li>
                                
                                <?php endforeach; endif;?>
                                
                                <?php if($USER->authenticated && $USER->role == "admin") :?>
                                <li class="tab-item <?=$settingsActive;?>" id="settings.phpx">
                                                            
                                    <a class="tab-link" href="#"><i class="fa fa-key"></i>Settings</a>
                                
                                </li>
                                <?php endif;?>

                                <?php if(!$USER->authenticated && $tabSetup == "Yes" && $needSetup == "No") : echo "Sign in with the icon at the bottom"; endif; ?>

                                <!--End Tab List-->
                           
                            </ul>
                        
                        </div>

                        <!-- /gn-scroller -->
                        <div class="bottom-bnts">
                            
                            <!--<li class="tab-item profile" id="settings.phpx"><i class="mdi mdi-account"></i></li>
                            <a class="fix-nav" href="#"><i class="mdi mdi-pin"></i></a>-->
                            <?php if(!$USER->authenticated) : ?>
                            <a class="log-in" href="#"><i class="mdi mdi-login"></i></a>
                            <?php endif ?>
                            <?php if($USER->authenticated) : ?>
                            <a class="logout" href="#"><i class="mdi mdi-logout"></i></a>
                            <?php endif ?>
                        
                        </div>
                    
                    </nav>
                
                </li>

                <li class="top-clock">
                    
                    <span><span style="color:black;"><b>Organizr</b></span></span>
                
                </li>

                <li class="pull-right">
                    
                    <ul class="nav navbar-right right-menu">
                        
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
                    
                    </ul>
                
                </li>
            
            </ul>

            <!--Content-->
            <div id="content" class="content" style=" overflow:hidden">

                <!--Load Framed Content-->
                
                <?php if($needSetup == "Yes") : ?>
								<div class="table-wrapper">
								
								    <div class="table-row">
								
								        <div class="table-cell text-center">
								        
								            <div class="login i-block">
								                
								                <div class="content-box">
								                    
								                    <div class="green-bg biggest-box">
								
								                        <h1 class="zero-m text-uppercase">Create Admin</h1>
								
								                    </div>
								
								                    <div class="big-box text-left registration-form">
								
								                        <h4 class="text-center">Create an account for Admin Access</h4>
								
                        								<form class="controlbox" name="new user registration" id="registration" action="index.php" method="POST">
                        								    
                        								    <input type="hidden" name="op" value="register"/>
                        								    <input type="hidden" name="sha1" value=""/>
                        								
                        								    <div class="form-group">
                        								
                        								        <input type="text" class="form-control material" name="username" placeholder="Username" autocorrect="off" autocapitalize="off" value="" autofocus>
                        								
                        								    </div>
                        								
                        								    <div class="form-group">
                        								
                        								        <input type="email" class="form-control material" name="email" placeholder="E-mail">
                        								
                        								    </div>
                        								
                        								    <div class="form-group">
                        								
                        								        <input type="password" class="form-control material" name="password1" placeholder="Password">
                        								
                        								    </div>
                        								
                        								    <div class="form-group">
                        								
                        								        <input type="password" class="form-control material" name="password2" placeholder="Retype Password">
                        								
                        								    </div>
                        								
                        								    <input type="button" class="btn green-bg btn-block btn-warning text-uppercase waves waves-effect waves-float" value="Register" onclick="User.processRegistration()"/>
                        								
                        								</form>
								                    
								                    </div>
								                
								                </div>
								            
								            </div>
								        
								        </div>
								    
								    </div>
								
								</div>
                <?php endif; ?>
                
                <?php if(!$USER->authenticated && $tabSetup == "Yes" && $needSetup == "No") :?>
                <div class="table-wrapper">
                
                    <div class="table-row">
                
                        <div class="table-cell text-center">
                        
                            <div class="login i-block">
                                
                                <div class="content-box">
                                    
                                    <div class="blue-bg biggest-box">
                
                                        <h1 class="zero-m text-uppercase">Awesome!</h1>
                
                                    </div>
                
                                    <div class="big-box text-left registration-form">
                
                                        <h4 class="text-center">Now that you created an Admin account, time to sign and start making some tabs...</h4>
                                        
                                        <button type="submit" class="btn log-in btn-block btn-primary text-uppercase waves waves-effect waves-float">Login</button>
                						                                    
                                    </div>
                                
                                </div>
                            
                            </div>
                        
                        </div>
                    
                    </div>
                
                </div>
                <?php endif; ?>
                
                <?php if($USER->authenticated && $USER->role == "admin" && $tabSetup == "Yes" && $needSetup == "No") :?>
                <div class="table-wrapper">
                
                    <div class="table-row">
                
                        <div class="table-cell text-center">
                        
                            <div class="login i-block">
                                
                                <div class="content-box">
                                    
                                    <div class="yellow-bg biggest-box">
                
                                        <h1 class="zero-m text-uppercase">Almost Done!</h1>
                
                                    </div>
                
                                    <div class="big-box text-left registration-form">
                
                                        <h2 class="text-center">Looks like this is a fresh install.</h4>
                                        <h3 class="text-center">Here's a couple hints before you get started.</h4>
                                        <h5 class="">The new layout now has 3 groups:<br><br>Admins - Have access to everything<br><br>Users - Have access to tabs marked active and for user<br><br>Guests - Have access to tabs marked active and for guest<br><br>You can have the side-bar pinned if you enable that on the bottom of the side-bar itself<br><br>Alright, Click the Hamburger on the top right and goto Settings to start making your tabs!</h4>
                						                                    
                                    </div>
                                
                                </div>
                            
                            </div>
                        
                        </div>
                    
                    </div>
                
                </div>
                <?php endif; ?>
                
                
                
                <!--End Load Framed Content-->

            </div>
            <!--End Content-->

            <!--Welcome notification-->
            <div id="welcome"></div>


        </div>
        <?php if(!$USER->authenticated) : ?>
        <div class="login-modal modal fade">
            
            <div class="gray-bg table-wrapper">
                
                <div class="table-row">
                    
                    <div class="table-cell text-center">
                        
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            
                            <span aria-hidden="true">&times;</span>
                        
                        </button>
                        
                        <div class="login i-block">
                            
                            <div class="content-box">
                                
                                <div class="red-bg biggest-box">

                                    <h1 class="zero-m text-uppercase">Welcome</h1>

                                </div>
                                
                                <div class="big-box text-left login-form">
                                    
                                    <h4 class="text-center">Login</h4>
                                    
                                    <form name="log in" id="login" action="index.php" method="POST">
                                        
                                        <div class="form-group">
                                            
                                            <input type="hidden" name="op" value="login">
				                            <input type="hidden" name="sha1" value="">
                                            <input type="text" class="form-control material" name="username" placeholder="Username" autocorrect="off" autocapitalize="off" value="" autofocus>
                                        
                                        </div>
                                        
                                        <div class="form-group">
                                            
                                            <input type="password" class="form-control material" name="password1" placeholder="Password">
                                        
                                        </div>

                                        <button type="submit" class="red-bg btn btn-block btn-info text-uppercase waves" value="log in" onclick="User.processLogin()">Login</button>

                                    </form>                                   
                                    
                                </div>
                            
                            </div>
                       
                        </div>
                    
                    </div>
                
                </div>
            
            </div>
        
        </div>
        <?php endif;?>
        <?php if($USER->authenticated) : ?>
        <div class="logout-modal modal fade">
            
            <div class="table-wrapper" style="background: <?=$color;?>">
            
                <div class="table-row">
                
                    <div class="table-cell text-center">
                    
                        <div class="login i-block">
                        
                            <div class="content-box">
                            
                                <div class="light-blue-bg biggest-box">
                                
                                    <form name="log out" id="logout" action="index.php" method="POST">
				                        <input type="hidden" name="op" value="logout">
                                        <input type="hidden" name="username"value="<?php echo $_SESSION["username"]; ?>" >
				
				
			
                                    <h3 class="zero-m text-uppercase">Do you want to logout?</h3>
                                        
                                    <a href="#" id="logoutSubmit" class="i-block" data-dismiss="modal">Yes</a>
                                    <a href="#" class="i-block" data-dismiss="modal">No</a>
                                
                                    </form>
                                    
                                </div>
                            
                            </div>
                    
                        </div>
                
                    </div>
            
                </div>
        
            </div>
    
        </div>
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
        <script src="js/menu/gnmenu.js"></script>

        <!--Selects-->
        <script src="js/selects/selectFx.js"></script>
        
        <script src="bower_components/sweetalert/dist/sweetalert.min.js"></script>

        <script src="bower_components/smoke/dist/js/smoke.min.js"></script>

        <!--Notification-->
        <script src="js/notifications/notificationFx.js"></script>

        <!--Custom Scripts-->
        <script src="js/common.js"></script>

        <script>
        $(function () {

            // show the notification
           /* setTimeout(function () {
                // create the notification
                var notification = new NotificationFx({
                    message: '<span><?php if(!empty($USER->info_log)) : 
                    echo $USER->info_log[0]; 
                    elseif(empty($USER->info_log)) :
                    echo "Welcome Guest!";
                    endif;?>
                    </span>',
                    layout: 'attached',
                    effect: 'bouncyflip',
                    ttl: 5500,
                    wrapper: document.getElementById("welcome"),
                    type: 'warning', // notice, warning, success or error
                });
                notification.show();
            }, 1000);*/
            
            $.smkAlert({
                text: '<?php if(!empty($USER->info_log)) : 
                    echo $USER->info_log[0]; 
                    elseif(empty($USER->info_log)) :
                    echo "Welcome Guest!";
                    endif;?>',
                type: 'info'
            });

        });

        $(document).ready(function(){
            
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
                $("#content").html('<div class="iframe active" data-content-url="'+defaultTab+'"><iframe frameborder="0" style="width:100%; height:100%;" src="'+defaultTab+'"></iframe></div>');
            }
            
            $('#content').css("height", $(window).height() - 56 + "px" );
            
            $("div").find(".iframe").css("height", $(window).height() - 56 + "px" );

            $(window).resize(function(){

                $('#content').css("height", $(window).height() - 56 + "px" );
                $("div").find(".iframe").css("height", $(window).height() - 56 + "px" );

            });

            $('#reload').on('click touchstart', function(){

                $("i[class^='mdi mdi-refresh']").attr("class", "mdi mdi-refresh fa-spin");
                var activeFrame = $('#content').find('.active').children('iframe');
                activeFrame.attr('src', activeFrame.attr('src'));
                
                var refreshBox = $('#content').find('.active');
                $("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(refreshBox).fadeIn(300);

                setTimeout(function(){
                    
                    var refreshPreloader = refreshBox.find('.refresh-preloader'),
                    deletedRefreshBox = refreshPreloader.fadeOut(300, function(){
                        
                        refreshPreloader.remove();
                        $("i[class^='mdi mdi-refresh fa-spin']").attr("class", "mdi mdi-refresh");
                    
                    });
                
                },1000);
                
                

            })
            
            $('#logoutSubmit').on('click touchstart', function(){

                $( "#logout" ).submit();

            })
            
            $("li[class^='tab-item']").on('click touchstart', function(){
                
                var thisidfull = $(this).attr("id");
                var thisid = thisidfull.substr(0, thisidfull.length-1);
                var currentframe = $("div[data-content-url^='"+thisid+"']");
                
                if (currentframe.attr("class") == "iframe active") {
                    console.log(thisid + " is active already");
                }else if (currentframe.attr("class") == "iframe hidden") {
                    console.log(thisid + " is active already but hidden");
                    $("div[class^='iframe active']").attr("class", "iframe hidden");
                    currentframe.attr("class", "iframe active");
                    $('#content').css("height", $(window).height() - 56 + "px" );
                    $("div").find(".iframe").css("height", $(window).height() - 56 + "px" );
                    $("li[class^='tab-item active']").attr("class", "tab-item");
                    $(this).attr("class", "tab-item active");

                    
                    
           
  
                    
                }else {
                    console.log(thisid + " make new div");
                    $("div[class^='iframe active']").attr("class", "iframe hidden");
                    $( '<div class="iframe active" data-content-url="'+thisid+'"><iframe frameborder="0" style="width:100%; height:100%;" src="'+thisid+'"></iframe></div>' ).appendTo( "#content" );
                    $('#content').css("height", $(window).height() - 56 + "px" );
                    $("div").find(".iframe").css("height", $(window).height() - 56 + "px" );
                    $("li[class^='tab-item active']").attr("class", "tab-item");
                    $(this).attr("class", "tab-item active");

                }
                
            });

        }); 
        </script>


    </body>

</html>