<?php
// Print output all purrty
function prettyPrint($v) {
	$trace = debug_backtrace()[0];
	echo '<pre style="white-space: pre; text-overflow: ellipsis; overflow: hidden; background-color: #f2f2f2; border: 2px solid black; border-radius: 5px; padding: 5px; margin: 5px;">'.$trace['file'].':'.$trace['line'].' '.gettype($v)."\n\n".print_r($v, 1).'</pre><br/>';
}
// Clean Directory string
function cleanDirectory($path){
	$path = str_replace(array('/', '\\'), '/', $path);
    if(substr($path, -1) != '/'){
        $path = $path . '/';
    }
    if($path[0] != '/' && $path[1] != ':'){
        $path = '/' . $path;
    }
    return $path;
}
// Get Gravatar Email Image
function gravatar($email = '') {
    $email = md5(strtolower(trim($email)));
    $gravurl = "https://www.gravatar.com/avatar/$email?s=100&d=mm";
    return $gravurl;
}
// Cookie Custom Function
function coookie($type, $name, $value = '', $days = -1, $http = true){
	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https"){
		$Secure = true;
 	   	$HTTPOnly = true;
	}elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
		$Secure = true;
 	   	$HTTPOnly = true;
	} else {
		$Secure = false;
 	   	$HTTPOnly = false;
   }
   if(!$http){ $HTTPOnly = false; }
	$Path = '/';
	$Domain = $_SERVER['HTTP_HOST'];
	$Port = strpos($Domain, ':');
	if ($Port !== false)  $Domain = substr($Domain, 0, $Port);
	$Port = strpos($Domain, ':');
	$check = substr_count($Domain, '.');
	if($check >= 3){
		if(is_numeric($Domain[0])){
			$Domain = '';
		}else{
			$Domain = '.'.explode('.',$Domain)[1].'.'.explode('.',$Domain)[2].'.'.explode('.',$Domain)[3];
		}
	}elseif($check == 2){
		$Domain = '.'.explode('.',$Domain)[1].'.'.explode('.',$Domain)[2];
	}elseif($check == 1){
		$Domain = '.' . $Domain;
	}else{
		$Domain = '';
	}
	if($type = 'set'){
		$_COOKIE[$name] = $value;
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
							. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() + (86400 * $days)) . ' GMT')
							. (empty($Path) ? '' : '; path=' . $Path)
							. (empty($Domain) ? '' : '; domain=' . $Domain)
							. (!$Secure ? '' : '; secure')
							. (!$HTTPOnly ? '' : '; HttpOnly'), false);
	}elseif($type = 'delete'){
		unset($_COOKIE[$name]);
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
							. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() - 3600) . ' GMT')
							. (empty($Path) ? '' : '; path=' . $Path)
							. (empty($Domain) ? '' : '; domain=' . $Domain)
							. (!$Secure ? '' : '; secure')
							. (!$HTTPOnly ? '' : '; HttpOnly'), false);
	}
}
function getOS(){
	if(PHP_SHLIB_SUFFIX == "dll"){
		return "win";
	}else{
		return "*nix";
	}
}
if(!function_exists('getallheaders')){
    function getallheaders(){
        $headers = array ();
        foreach ($_SERVER as $name => $value){
            if (substr($name, 0, 5) == 'HTTP_'){
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
function random_ascii_string($len){
	$string = "";
	$max = strlen($this->ascii)-1;
	while($len-->0) { $string .= $this->ascii[mt_rand(0, $max)]; }
	return $string;
}
function encrypt($password, $key = null) {
    $key = (isset($GLOBALS['organizrHash'])) ? $GLOBALS['organizrHash'] : $key;
    return openssl_encrypt($password, 'AES-256-CBC', $key, 0, fillString($key,16));
}
function decrypt($password, $key = null) {
    $key = (isset($GLOBALS['organizrHash'])) ? $GLOBALS['organizrHash'] : $key;
    return openssl_decrypt($password, 'AES-256-CBC', $key, 0, fillString($key,16));
}
function fillString($string, $length){
    $filler = '0123456789abcdefghijklmnopqrstuvwxyz!@#$%^&*';
    if(strlen($string) < $length){
        $diff = $length - strlen($string);
        $filler = substr($filler,0,$diff);
        return $string.$filler;
    }elseif(strlen($string) > $length){
        return substr($string,0,$length);
    }else{
        return $string;
    }
    return $diff;
}
function userIP() {
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    if (strpos($ipaddress, ',') !== false) {
        list($first, $last) = explode(",", $ipaddress);
        return $first;
    }else{
        return $ipaddress;
    }

}
function arrayIP($string){
    if (strpos($string, ',') !== false) {
        $result = explode(",", $string);
    }else{
        $result = array($string);
    }
    foreach($result as &$ip){
        $ip = is_numeric(substr($ip, 0, 1)) ? $ip : gethostbyname($ip);
    }
    return $result;
}
