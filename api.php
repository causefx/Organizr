<?php
// Include functions
require_once('user.php');
$USER = new User();
// Get Action
if (isset($_POST['a'])) { $action = $_POST['a']; }
if (isset($_POST['k'])) { $key = $_POST['k']; }
if (isset($_POST['v'])) { $values = $_POST['v']; }
if (isset($_GET['a'])) { $action = $_GET['a']; }
if (isset($_GET['k'])) { $key = $_GET['k']; }
if (isset($_GET['v'])) {
    $values = (strpos($_GET['v'], '|') !== false ? explode('|',$_GET['v']) : $_GET['v']);
}
unset($_POST['a']);
unset($_POST['k']);
unset($_POST['v']);
$result = array();
//Check Key
if (!isset($key)) {
    $result['error'] = "No API Key Set";
    exit(json_encode($result));
}elseif (strtolower(ORGANIZRAPI) != strtolower($key)) {
    $result['error'] = "API Key mismatch";
    exit(json_encode($result));
}
//Start API Call
if (isset($action)) {
    switch ($action) {
        case "invite-user":
            if($values){
                if(count($values) == 2){
                    $user = null;
                    $email = $values[0];
                    $server = $values[1];
                }else{
					$user = $values[0];
					$email = $values[1];
                    $server = $values[2];
                }
                //$USER = new User();
                $USER->invite_user("chris", "causefx@me.com", "plex");
                $result['data'] = "User has been invited";
                //$result['data'] = "user = $user | email = $email | server = $server";
            }else{
            	$result['error'] = "No Values Were Set For Function";
            }
            break;
        case "ombi":
			if($values){
				switch ($values) {
					case 'plex-update':
						$ombi = ombiAPI('plex-cache');
						if($ombi){
							$result['data'] = "Plex Content Cache Successful";
						}else{
							$result['error'] = "Plex Content Cache Error";
						}
						break;
					default:
                        $result['error'] = "$values is not a valid Ombi function";
						break;
				}
			}else{
                $result['error'] = "No Ombi Action Supplied";
            }

            break;
        default:
            $result = "$action Not Defined As API Function";
    }
}else{
    $result['error'] = "No API Action Set";
}
//Set Default Result
if(!$result){
    $result['error'] = "An error has occurred";
}
//return JSON array
exit(json_encode($result));
?>
