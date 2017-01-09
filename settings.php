<?php 

date_default_timezone_set('America/Los_Angeles');

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

if(!$USER->authenticated) :

    die("Why you trying to access this without logging in?!?!");

elseif($USER->authenticated && $USER->role !== "admin") :

    die("C'mon man!  I give you access to my stuff and now you're trying to get in the back door?");

endif;

function printArray($arrayName){
    
    foreach ( $arrayName as $item ) :
        
        echo $item . "<br/>";
        
    endforeach;
    
}

$dbfile = DATABASE_LOCATION  . constant('User::DATABASE_NAME') . ".db";

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
    $topbar = "#eb6363"; 
    $topbartext = "#FFFFFF";
    $bottombar = "#eb6363";
    $sidebar = "#000000";
    $hoverbg = "#eb6363";
    $activetabBG = "#eb6363";
    $activetabicon = "#FFFFFF";
    $activetabtext = "#FFFFFF";
    $inactiveicon = "#FFFFFF";
    $inactivetext = "#FFFFFF";

endif;

if($tabSetup == "No") :

    $result = $file_db->query('SELECT * FROM tabs');
    
endif;

if($hasOptions == "Yes") :

    $resulto = $file_db->query('SELECT * FROM options');
    
endif;

if($hasOptions == "Yes") : 
                                    
    foreach($resulto as $row) : 

        $title = $row['title'];
        $topbartext = $row['topbartext'];
        $topbar = $row['topbar'];
        $bottombar = $row['bottombar'];
        $sidebar = $row['sidebar'];
        $hoverbg = $row['hoverbg'];
        $activetabBG = $row['activetabBG'];
        $activetabicon = $row['activetabicon'];
        $activetabtext = $row['activetabtext'];
        $inactiveicon = $row['inactiveicon'];
        $inactivetext = $row['inactivetext'];

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

    //echo "<script>window.onload = function() {if(!window.location.hash) {window.location = window.location + '#loaded';window.location.reload();}}</script>";
    
endif;

if($action == "addOptionz") :
    
    if($hasOptions == "Yes") :
    
        $file_db->exec("DELETE FROM options");
        
    endif;
    
    if($hasOptions == "No") :

        $file_db->exec("CREATE TABLE options (title TEXT UNIQUE, topbar TEXT, bottombar TEXT, sidebar TEXT, hoverbg TEXT, topbartext TEXT, activetabBG TEXT, activetabicon TEXT, activetabtext TEXT, inactiveicon TEXT, inactivetext TEXT)");
        
    endif;
            
    $title = $_POST['title'];
    $topbartext = $_POST['topbartext'];
    $topbar = $_POST['topbar'];
    $bottombar = $_POST['bottombar'];
    $sidebar = $_POST['sidebar'];
    $hoverbg = $_POST['hoverbg'];
    $activetabBG = $_POST['activetabBG'];
    $activetabicon = $_POST['activetabicon'];
    $activetabtext = $_POST['activetabtext'];
    $inactiveicon = $_POST['inactiveicon'];
    $inactivetext = $_POST['inactivetext'];

    $insert = "INSERT INTO options (title, topbartext, topbar, bottombar, sidebar, hoverbg, activetabBG, activetabicon, activetabtext, inactiveicon, inactivetext) 
                VALUES (:title, :topbartext, :topbar, :bottombar, :sidebar, :hoverbg, :activetabBG, :activetabicon , :activetabtext , :inactiveicon, :inactivetext)";
                
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

        <link rel="stylesheet" href="css/style.css">
        <link href="css/jquery.filer.css" rel="stylesheet">
	    <link href="css/jquery.filer-dragdropbox-theme.css" rel="stylesheet">

        <!--[if lt IE 9]>
        <script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
        <script src="bower_components/respondJs/dest/respond.min.js"></script>
        <![endif]-->
        
    </head>

    <body style="padding: 0; background: #273238;">
        
        <style>
        
            input.form-control.material.icp-auto.iconpicker-element.iconpicker-input {
    display: none;
}
        
        </style>
       
        <div id="main-wrapper" class="main-wrapper">

            <!--Content-->
            <div id="content"  style="margin:0 20px; overflow:hidden">
 
                <br/>
                
                <div id="versionCheck"></div>       
            
                <div class="row">
                
                    <div class="col-lg-12">
                  
                        <div class="tabbable tabs-with-bg" id="eighth-tabs">
                    
                            <ul class="nav nav-tabs" style="background: #76828e">
                      
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
                        
                                    <a href="#about" data-toggle="tab"><i class="fa fa-info indigo"></i></a>
                     
                                </li>

                            </ul>
                    
                            <div class="tab-content">
                      
                                <div class="content-box box-shadow big-box todo-list tab-pane big-box  fade in active" id="tab-tabs">

                                    <div class="sort-todo">

                                        <a class="total-tabs">Total Tabs <span class="badge gray-bg"></span></a>
                                        
                                        <button id="iconHide" type="button" class="btn waves btn-labeled btn-success btn-sm text-uppercase waves-effect waves-float">
                                            
                                            <span class="btn-label"><i class="fa fa-upload"></i></span>Upload Icons
                                            
                                        </button> 
                                        
                                        <?php if($action) : ?>
                                        
                                        <button id="apply" class="btn waves btn-labeled btn-success btn-sm pull-right text-uppercase waves-effect waves-float" type="submit">
                                        
                                            <span class="btn-label"><i class="fa fa-check"></i></span>Apply Changes
                                        
                                        </button>
                                        
                                        <?php endif; ?>

                                    </div>

                                    <input type="file" name="files[]" id="uploadIcons" multiple="multiple">
                                    
                                    <form id="add_tab" method="post">

                                        <div class="form-group add-tab">

                                            <div class="input-group">

                                                <div class="input-group-addon">

                                                    <i class="fa fa-pencil gray"></i>

                                                </div>

                                                <input type="text" class="form-control name-of-todo" placeholder="Type In New Tab Name And Hit Enter" style="border-top-left-radius: 0;
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

                                                    ?>
                                                    <li id="item-<?=$tabNum;?>" class="list-group-item gray-bg" style="position: relative; left: 0px; top: 0px;">

                                                        <tab class="content-form form-inline">

                                                            <div class="form-group">

                                                                <div class="action-btns" style="width:calc(100%)">

                                                                    <a class="" style="margin-left: 0px"><span class="fa fa-hand-paper-o"></span></a>

                                                                </div>

                                                            </div>

                                                            <div class="form-group">

                                                                <input style="width: 100%;" type="text" class="form-control material input-sm" id="name-<?=$tabNum;?>" name="name-<?=$tabNum;?>" placeholder="New Tab Name" value="<?=$row['name'];?>">

                                                            </div>

                                                            <div class="form-group">

                                                                <input style="width: 100%;" type="text" class="form-control material input-sm" id="url-<?=$tabNum;?>" name="url-<?=$tabNum;?>" placeholder="Tab URL" value="<?=$row['url']?>">

                                                            </div>

                                                            <div style="margin-right: 5px;" class="form-group">

                                                                <div class="input-group">
                                                                    <input data-placement="bottomRight" class="form-control material icp-auto" name="icon-<?=$tabNum;?>" value="<?=$row['icon'];?>" type="text" />
                                                                    <span class="input-group-addon"></span>
                                                                </div>
                                                                
                                                                - OR -

                                                            </div>
                                                            
                                                            <div class="form-group">

                                                                <input style="width: 100%;" type="text" class="form-control material input-sm" id="iconurl-<?=$tabNum;?>" name="iconurl-<?=$tabNum;?>" placeholder="Icon URL" value="<?=$row['iconurl']?>">

                                                            </div>

                                                            <div class="form-group">

                                                                <div class="radio radio-danger">


                                                                    <input type="radio" id="default[<?=$tabNum;?>]" value="true" name="default" <?=$default;?>>
                                                                    <label for="default[<?=$tabNum;?>]">Default</label>

                                                                </div>

                                                            </div>

                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-success" value="false" name="active-<?=$tabNum;?>" type="hidden">
                                                                    <input id="active[<?=$tabNum;?>]" class="switcher switcher-success" name="active-<?=$tabNum;?>" type="checkbox" <?=$activez;?>>

                                                                    <label for="active[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                Active
                                                            </div>

                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-primary" value="false" name="user-<?=$tabNum;?>" type="hidden">
                                                                    <input id="user[<?=$tabNum;?>]" class="switcher switcher-primary" name="user-<?=$tabNum;?>" type="checkbox" <?=$userz;?>>
                                                                    <label for="user[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                User
                                                            </div>

                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-primary" value="false" name="guest-<?=$tabNum;?>" type="hidden">
                                                                    <input id="guest[<?=$tabNum;?>]" class="switcher switcher-warning" name="guest-<?=$tabNum;?>" type="checkbox" <?=$guestz;?>>
                                                                    <label for="guest[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                Guest
                                                            </div>
                                                            
                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-primary" value="false" name="window-<?=$tabNum;?>" type="hidden">
                                                                    <input id="window[<?=$tabNum;?>]" class="switcher switcher-warning" name="window-<?=$tabNum;?>" type="checkbox" <?=$windowz;?>>
                                                                    <label for="window[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                No iFrame
                                                            </div>

                                                            <div class="pull-right action-btns" style="padding-top: 8px;">

                                                                <a class="trash"><span class="fa fa-close"></span></a>

                                                            </div>


                                                        </tab>

                                                    </li>
                                                    <?php $tabNum ++; endforeach; endif;?>

                                                </ul>

                                            </div>

                                            <div class="checkbox clear-todo pull-left"></div>

                                            <button class="btn waves btn-labeled btn-success btn-sm pull-right text-uppercase waves-effect waves-float" type="submit">
                                                
                                                <span class="btn-label"><i class="fa fa-floppy-o"></i></span>Save Tabs
                                                
                                            </button>
                                            
                                        </form>
                                        
                                    </div>
 
                                </div>

                                <div class="tab-pane big-box  fade in" id="useredit">
                                    
                                    <div class="row">
                                        
                                        <div class="col-lg-12">
                                          
                                            <div class="gray-bg content-box big-box box-shadow">
                                            
                                                <form class="content-form form-inline" name="new user registration" id="registration" action="" method="POST">
                        								    
                                                    <input type="hidden" name="op" value="register"/>
                                                    <input type="hidden" name="sha1" value=""/>

                                                    <div class="form-group">

                                                        <input type="text" class="form-control gray" name="username" placeholder="Username" autocorrect="off" autocapitalize="off" value="">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="email" class="form-control gray" name="email" placeholder="E-mail">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="password" class="form-control gray" name="password1" placeholder="Password">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="password" class="form-control gray" name="password2" placeholder="Retype Password">

                                                    </div>
                                                    
                                                    
                                                    
                                                    <button type="submit" onclick="User.processRegistration()" class="btn btn-primary btn-icon waves waves-circle waves-effect waves-float"><i class="fa fa-user-plus"></i></button>

                                                </form>               
                                          
                                            </div>
                                        
                                        </div>
                                      
                                    </div>
                                    
                                    <div class="content-box big-box">
                                        
                                        <form class="content-form form-inline" name="unregister" id="unregister" action="" method="POST">
                                              
                                            <input type="hidden" name="op" value="unregister"/>
                                            
                                            <p id="inputUsername"></p>

                                            <div class="table-responsive">

                                                <table class="table table-striped">

                                                    <thead>

                                                        <tr>

                                                            <th>#</th>

                                                            <th>Username</th>
                                                            
                                                            <th>E-Mail</th>

                                                            <th>Login Status</th>

                                                            <th>Last Seen</th>

                                                            <th>User Group</th>

                                                            <th>User Actions</th>

                                                        </tr>

                                                    </thead>

                                                    <tbody>

                                                        <?php $countUsers = 1; 
                                                        foreach($gotUsers as $row) : 
                                                        if($row['role'] == "admin") : 
                                                            $userColor = "red";
                                                            $disableAction = "disabled=\"disabled\"";
                                                        else : 
                                                            $userColor = "blue";
                                                            $disableAction = "";
                                                        endif;
                                                        if($row['active'] == "true") : 
                                                            $userActive = "Logged In";
                                                            $userActiveColor = "primary";
                                                        else : 
                                                            $userActive = "Logged Out";
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

                                                            <td><i class="userpic"><img src="https://www.gravatar.com/avatar/<?=$userpic;?>?s=25&d=mm" class="img-circle"></i> &nbsp; <?=$row['username'];?></td>
                                                            
                                                            <td><?=$row['email'];?></td>

                                                            <td><span class="label label-<?=$userActiveColor;?>"><?=$userActive;?></span></td>

                                                            <td><?=$lastActive;?></td>

                                                            <td><span class="text-uppercase <?=$userColor;?>"><?=$row['role'];?></span></td>

                                                            <td id="<?=$row['username'];?>">

                                                                <button <?=$disableAction;?> class="btn waves btn-labeled btn-danger btn btn-sm text-uppercase waves-effect waves-float deleteUser">

                                                                    <span class="btn-label"><i class="fa fa-user-times"></i></span>Delete

                                                                </button>

                                                            </td>

                                                        </tr>

                                                        <?php $countUsers++; endforeach; ?>

                                                    </tbody>

                                                </table>

                                            </div>
                                            
                                        </form>
                                        
                                    </div>

                                </div>
                                
                                <div class="tab-pane big-box  fade in" id="about">
                        
                                    <h4><strong>About Organizr</strong></h4>
                        
                                    <p id="version"></p>
                                    
                                    <p id="submitFeedback">
                                    
                                        <a href='https://github.com/causefx/Organizr/issues/new' target='_blank' type='button' class='btn waves btn-labeled btn-success btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-github-alt'></i></span>Submit Issue or Request</a> 
                                        <a href='https://github.com/causefx/Organizr' target='_blank' type='button' class='btn waves btn-labeled btn-primary btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-github'></i></span>View On Github</a>
                                        <a href='https://riot.im/app/#/room/#iCauseFX:matrix.org' target='_blank' type='button' class='btn waves btn-labeled btn-dark btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-comments-o'></i></span>Chat With Us</a>
                                    
                                    </p>
                                    
                                    <p id="whatsnew"></p>
                                    
                                    <p id="downloadnow"></p>
                                    
                                    <div class="panel panel-danger">
                                        
                                        <div class="panel-heading">
                                            
                                            <h3 class="panel-title">Delete Database</h3>
                                            
                                        </div>
                                        
                                        <div class="panel-body">
                                            
                                            <div class="col-lg-4">
                                            
                                                <p>Only do this if an upgrade requires it.  This will delete your database so there is no going back and you will need to set everything back up, including user accouts.</p>
                                                <form id="deletedb" method="post">
                                                    
                                                    <input type="hidden" name="action" value="deleteDB" />
                                                    <button class="btn waves btn-labeled btn-danger pull-right text-uppercase waves-effect waves-float" type="submit">
                                                
                                                        <span class="btn-label"><i class="fa fa-trash"></i></span>Delete Databse
                                                
                                                    </button>
                                                    
                                                </form>
                                        
                                            </div>
                                            
                                        </div>
                                        
                                    </div>
                      
                                </div>
                                
                                <div class="tab-pane small-box  fade in" id="customedit">

                                    <form id="add_optionz" method="post">
                                        
                                        <input type="hidden" name="action" value="addOptionz" />
                                        
                                        <button id="plexTheme" style="background: #E49F0C" type="button" class="btn waves btn-dark btn-sm text-uppercase waves-effect waves-float">Plex</button>
                                        <button id="embyTheme" style="background: #52B54B" type="button" class="btn waves btn-dark btn-sm text-uppercase waves-effect waves-float">Emby</button>
                                        <button id="bookTheme" style="background: #3B5998" type="button" class="btn waves btn-dark btn-sm text-uppercase waves-effect waves-float">Book</button>
                                        <button id="spaTheme" style="background: #66BBAE" type="button" class="btn waves btn-dark btn-sm text-uppercase waves-effect waves-float">Spa</button>
                                        <button id="darklyTheme" style="background: #375A7F" type="button" class="btn waves btn-dark btn-sm text-uppercase waves-effect waves-float">Darkly</button>
                                        <button id="slateTheme" style="background: #272B30" type="button" class="btn waves btn-dark btn-sm text-uppercase waves-effect waves-float">Slate</button>
                                        
                                        <button class="btn waves btn-labeled btn-success btn-sm pull-right text-uppercase waves-effect waves-float" type="submit">
                                                
                                                <span class="btn-label"><i class="fa fa-floppy-o"></i></span>Save Options
                                                
                                        </button>

                                        <div class="content-box box-shadow big-box grids">

                                            <div class="row show-grids">

                                                <h4><strong>Title</strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center>Title</center>

                                                    <input name="title" class="form-control gray" value="<?=$title;?>" placeholder="Organizr">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center>Title Text</center>

                                                    <input name="topbartext" id="topbartext" class="form-control jscolor {hash:true}" value="<?=$topbartext;?>">

                                                </div>

                                            </div>

                                            <div class="row show-grids">

                                                <h4><strong>Navigation Bars</strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center>Top Bar</center>

                                                    <input name="topbar" id="topbar" class="form-control jscolor {hash:true}" value="<?=$topbar;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center>Bottom Bar</center>

                                                    <input name="bottombar" id="bottombar" class="form-control jscolor {hash:true}" value="<?=$bottombar;?>">

                                                </div>

                                                <div class="clearfix visible-xs-block"></div>

                                                <div class="col-md-2 gray-bg">

                                                    <center>Side Bar</center>

                                                    <input name="sidebar" id="sidebar" class="form-control jscolor {hash:true}" value="<?=$sidebar;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center>Hover BG</center>

                                                    <input name="hoverbg" id="hoverbg" class="form-control jscolor {hash:true}" value="<?=$hoverbg;?>">

                                                </div>

                                            </div>

                                            <div class="row show-grids">

                                                <h4><strong>Active Tab</strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center>Active Tab BG</center>

                                                    <input name="activetabBG" id="activetabBG" class="form-control jscolor {hash:true}" value=<?=$activetabBG;?>"">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center>Active Tab Icon</center>

                                                    <input name="activetabicon" id="activetabicon" class="form-control jscolor {hash:true}" value="<?=$activetabicon;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center>Active Tab Text</center>

                                                    <input name="activetabtext" id="activetabtext" class="form-control jscolor {hash:true}" value="<?=$activetabtext;?>">

                                                </div>

                                            </div>

                                            <div class="row show-grids">

                                                <h4><strong>Inactive Tab</strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center>Inactive Icon</center>

                                                    <input name="inactiveicon" id="inactiveicon" class="form-control jscolor {hash:true}" value="<?=$inactiveicon;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center>Inactive Text</center>

                                                    <input name="inactivetext" id="inactivetext" class="form-control jscolor {hash:true}" value="<?=$inactivetext;?>">

                                                </div>

                                            </div>

                                        </div>
                                        
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

        <!--Notification-->
        <script src="js/notifications/notificationFx.js"></script>

        <script src="js/jqueri_ui_custom/jquery-ui.min.js"></script>
        <script src="js/jquery.filer.min.js" type="text/javascript"></script>
	    <script src="js/custom.js" type="text/javascript"></script>

        <?php if($_POST['op']) : ?>
        <script>

             $.smkAlert({
                text: '<?php echo printArray($USER->info_log); ?>',
                type: 'info'
            });
            
            <?php if(!empty($USER->error_log)) : ?>
            $.smkAlert({
                position: 'top-left',
                text: '<?php echo printArray($USER->error_log); ?>',
                type: 'warning'
                
            });
            
            <?php endif; ?>
            
        </script>
        <?php endif; ?>
        
        <?php if($action == "addTabz") : ?>
        <script>

            if(!window.location.hash) {
                
                window.location = window.location + '#loaded';
                window.location.reload();
                
            }else{
                
               swal("Tabs Saved!", "Apply Changes To Reload The Page!", "success"); 
                
            }
            
        </script>
        <?php endif; ?>
        
         <?php if($action == "addOptionz") : ?>
        <script>

            swal("Colors Saved!", "Apply Changes To Reload The Page!", "success");
            
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
                    'containment': 'parent',
                    'opacity': 0.9
                });

                $("#add_tab").on('submit', function (e) {
                    e.preventDefault();

                    var $toDo = $(this).find('.name-of-todo');
                    toDo_name = $toDo.val();

                    if (toDo_name.length >= 3) {

                        var newid = $('.list-group-item').length + 1;

                        $(".todo ul").append(
                        '<li id="item-' + newid + '" class="list-group-item gray-bg" style="position: relative; left: 0px; top: 0px;"><tab class="content-form form-inline"> <div class="form-group"><div class="action-btns" style="width:calc(100%)"><a class="" style="margin-left: 0px"><span class="fa fa-hand-paper-o"></span></a></div></div> <div class="form-group"><input style="width: 100%;" type="text" class="form-control material input-sm" name="name-' + newid + '" id="name[' + newid + ']" placeholder="New Tab Name" value="' + toDo_name + '"></div> <div class="form-group"><input style="width: 100%;" type="text" class="form-control material input-sm" name="url-' + newid + '" id="url[' + newid + ']" placeholder="Tab URL"></div> <div style="margin-right: 5px;" class="form-group"><div class="input-group"><input style="width: 100%;" name="icon-' + newid + '" data-placement="bottomRight" class="form-control material icp-auto" value="fa-diamond" type="text" /><span class="input-group-addon"></span></div> - OR -</div>  <div class="form-group"><input style="width: 100%;" type="text" class="form-control material input-sm" id="iconurl-' + newid + '" name="iconurl-' + newid + '" placeholder="Icon URL" value=""></div>  <div class="form-group"> <div class="radio radio-danger"> <input type="radio" name="default" id="default[' + newid + ']" name="default"> <label for="default[' + newid + ']">Default</label></div></div> <div class="form-group"><div class=""><input id="" class="switcher switcher-success" value="false" name="active-' + newid + '" type="hidden"><input name="active-' + newid + '" id="active[' + newid + ']" class="switcher switcher-success" type="checkbox" checked=""><label for="active[' + newid + ']"></label></div> Active</div> <div class="form-group"><div class=""><input id="" class="switcher switcher-primary" value="false" name="user-' + newid + '" type="hidden"><input id="user[' + newid + ']" name="user-' + newid + '" class="switcher switcher-primary" type="checkbox" checked=""><label for="user[' + newid + ']"></label></div> User</div> <div class="form-group"><div class=""><input id="" class="switcher switcher-primary" value="false" name="guest-' + newid + '" type="hidden"><input name="guest-' + newid + '" id="guest[' + newid + ']" class="switcher switcher-warning" type="checkbox" checked=""><label for="guest[' + newid + ']"></label></div> Guest</div> <div class="form-group"><div class=""><input id="" class="switcher switcher-primary" value="false" name="window-' + newid + '" type="hidden"><input name="window-' + newid + '" id="window[' + newid + ']" class="switcher switcher-warning" type="checkbox"><label for="window[' + newid + ']"></label></div> No iFrame</div><div class="pull-right action-btns" style="padding-top: 8px;"><a class="trash"><span class="fa fa-close"></span></a></div></tab></li>'
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

                    var clearedCompItem = $(this).closest(".list-group-item").remove();
                    e.preventDefault();
                    count();

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
            
            $("#iconHide").click(function(){

                $( "div[class^='jFiler jFiler-theme-dragdropbox']" ).toggle();
     
            });
            
            $(".deleteUser").click(function(){

                var parent_id = $(this).parent().attr('id');
                editUsername = $('#unregister').find('#inputUsername');
                $(editUsername).html('<input type="hidden" name="username"value="' + parent_id + '" />');
     
            });
            

            $('.icp-auto').iconpicker({placement: 'left', hideOnSelect: false, collision: true});
            
            $("li[class^='list-group-item']").bind('mouseheld', function(e) {

                $(this).find("span[class^='fa fa-hand-paper-o']").attr("class", "fa fa-hand-grab-o");
                $(this).mouseup(function() {
                    $(this).find("span[class^='fa fa-hand-grab-o']").attr("class", "fa fa-hand-paper-o");
                });
            })
         
        </script>
        
        <script>
            
            //Custom Themes            
            function changeColor(elementName, elementColor) {
                
                var definedElement = document.getElementById(elementName);
                
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
                
            });
        
        </script>
        
        <script>
        
        $( document ).ready(function() {
            
            $( "div[class^='jFiler jFiler-theme-dragdropbox']" ).hide();
        		
        	$.ajax({
        				
        		type: "GET",
                url: "https://api.github.com/repos/causefx/Organizr/releases/latest",
                dataType: "json",
                success: function(github) {
                   
                    var currentVersion = "0.97";
                    var githubVersion = github.tag_name;
                    var githubDescription = github.body;
                    var githubName = github.name;
                    infoTabVersion = $('#about').find('#version');
                    infoTabNew = $('#about').find('#whatsnew');
                    infoTabDownload = $('#about').find('#downloadnow');
        
        			if(currentVersion < githubVersion){
                    
                    	console.log("You Need To Upgrade");

                        $.smkAlert({
                            text: '<strong>New Version Available</strong> Click Info Tab',
                            type: 'warning',
                            permanent: true
                        });
                        
                        $(infoTabNew).html("<br/><h4><strong>What's New in " + githubVersion + "</strong></h4><strong>Title: </strong>" + githubName + " <br/><strong>Changes: </strong>" + githubDescription);
                        
                        $(infoTabDownload).html("<br/><a href='https://github.com/causefx/Organizr/archive/master.zip' target='_blank' type='button' class='btn waves btn-labeled btn-success btn-lg text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-download'></i></span>Download Organizr v." + githubVersion + "</a>");
                    
                    }else if(currentVersion === githubVersion){
                    
                    	console.log("You Are on Current Version");
                        
                        $.smkAlert({
                            text: 'Software is <strong>Up-To-Date!</strong>',
                            type: 'success'
                        });
                    
                    }else{
                    
                    	console.log("something went wrong");

                        $.smkAlert({
                            text: '<strong>WTF!? </strong>Can\'t check version.',
                            type: 'danger',
                            time: 10
                        });
                    
                    }

                    $(infoTabVersion).html("<strong>Installed Version: </strong>" + currentVersion + " <strong>Current Version: </strong>" + githubVersion);
                    
                }
                
            });
            
        });
        
        </script>

    </body>

</html>