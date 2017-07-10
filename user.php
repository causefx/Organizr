<?php
	/**
	 *	A framework for simple user authentication.
	 *
	 *	Users are recorded using {username, password, token} triplets.
	 *	Whenever a user logs in successfully, his or her database
	 *	entry is assigned a new random token,  which is used in
	 * salting subsequent password checks.
	 */
	
	// Include functions if not already included
	require_once('functions.php');
	 
    // Autoload frameworks
	require_once(__DIR__ . '/vendor/autoload.php');
	
    // Lazyload settings
	$databaseConfig = configLazy('config/config.php');
    
    if(file_exists('custom.css')) : define('CUSTOMCSS', 'true'); else : define('CUSTOMCSS', 'false'); endif; 
    $notifyExplode = explode("-", NOTIFYEFFECT);
    define('FAIL_LOG', 'loginLog.json');
    @date_default_timezone_set(TIMEZONE);
    function guestHash($start, $end){
        $ip   = $_SERVER['REMOTE_ADDR'];
        $ip    = md5($ip);
        return substr($ip, $start, $end);
    }


    define('GUEST_HASH', "guest-".guestHash(0, 5));
    define('EMAIL_CSS', "
    <style type=\"text/css\" id=\"media-query\">
      body {
  margin: 0;
  padding: 0; }

table, tr, td {
  vertical-align: top;
  border-collapse: collapse; }

.ie-browser table, .mso-container table {
  table-layout: fixed; }

* {
  line-height: inherit; }

a[x-apple-data-detectors=true] {
  color: inherit !important;
  text-decoration: none !important; }

[owa] .img-container div, [owa] .img-container button {
  display: block !important; }

[owa] .fullwidth button {
  width: 100% !important; }

[owa] .block-grid .col {
  display: table-cell;
  float: none !important;
  vertical-align: top; }

.ie-browser .num12, .ie-browser .block-grid, [owa] .num12, [owa] .block-grid {
  width: 615px !important; }

.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {
  line-height: 100%; }

.ie-browser .mixed-two-up .num4, [owa] .mixed-two-up .num4 {
  width: 204px !important; }

.ie-browser .mixed-two-up .num8, [owa] .mixed-two-up .num8 {
  width: 408px !important; }

.ie-browser .block-grid.two-up .col, [owa] .block-grid.two-up .col {
  width: 307px !important; }

.ie-browser .block-grid.three-up .col, [owa] .block-grid.three-up .col {
  width: 205px !important; }

.ie-browser .block-grid.four-up .col, [owa] .block-grid.four-up .col {
  width: 153px !important; }

.ie-browser .block-grid.five-up .col, [owa] .block-grid.five-up .col {
  width: 123px !important; }

.ie-browser .block-grid.six-up .col, [owa] .block-grid.six-up .col {
  width: 102px !important; }

.ie-browser .block-grid.seven-up .col, [owa] .block-grid.seven-up .col {
  width: 87px !important; }

.ie-browser .block-grid.eight-up .col, [owa] .block-grid.eight-up .col {
  width: 76px !important; }

.ie-browser .block-grid.nine-up .col, [owa] .block-grid.nine-up .col {
  width: 68px !important; }

.ie-browser .block-grid.ten-up .col, [owa] .block-grid.ten-up .col {
  width: 61px !important; }

.ie-browser .block-grid.eleven-up .col, [owa] .block-grid.eleven-up .col {
  width: 55px !important; }

.ie-browser .block-grid.twelve-up .col, [owa] .block-grid.twelve-up .col {
  width: 51px !important; }

@media only screen and (min-width: 635px) {
  .block-grid {
    width: 615px !important; }
  .block-grid .col {
    display: table-cell;
    Float: none !important;
    vertical-align: top; }
    .block-grid .col.num12 {
      width: 615px !important; }
  .block-grid.mixed-two-up .col.num4 {
    width: 204px !important; }
  .block-grid.mixed-two-up .col.num8 {
    width: 408px !important; }
  .block-grid.two-up .col {
    width: 307px !important; }
  .block-grid.three-up .col {
    width: 205px !important; }
  .block-grid.four-up .col {
    width: 153px !important; }
  .block-grid.five-up .col {
    width: 123px !important; }
  .block-grid.six-up .col {
    width: 102px !important; }
  .block-grid.seven-up .col {
    width: 87px !important; }
  .block-grid.eight-up .col {
    width: 76px !important; }
  .block-grid.nine-up .col {
    width: 68px !important; }
  .block-grid.ten-up .col {
    width: 61px !important; }
  .block-grid.eleven-up .col {
    width: 55px !important; }
  .block-grid.twelve-up .col {
    width: 51px !important; } }

@media (max-width: 635px) {
  .block-grid, .col {
    min-width: 320px !important;
    max-width: 100% !important; }
  .block-grid {
    width: calc(100% - 40px) !important; }
  .col {
    width: 100% !important; }
    .col > div {
      margin: 0 auto; }
  img.fullwidth {
    max-width: 100% !important; } }

    </style>	
	");
	
	class User
	{
		// =======================================================================
		// IMPORTANT VALUES THAT YOU *NEED* TO CHANGE FOR THIS TO BE SECURE
		// =======================================================================
			// Keeping this location on ./... means that it will be publically visible to all,
			// and you need to use htaccess rules or some such to ensure no one
			// grabs your user's data.
			//const USER_HOME = "../users/";
			// In order for users to be notified by email of certain things, set this to true.
			// Note that the server you run this on should have sendmail in order for
			// notification emails to work. Also note that password resetting doesn't work
			// unless mail notification is turned on.
			const use_mail = ENABLEMAIL;
			// This value should point to a directory that is not available to web users.
			// If your documents are in ./public_html, for instance., then put database
			// in something like ./database - that way, you don't have to rely on
			// htaccess rules or the likes, because it's simply impossible to get to the
			// database from a public, or private, URL.
			//
			// By default it's set to the stupidly dangerous and publically accessible same
			// base dir as your web page. So change it, because people are going to try
			// to download your database file. And succeed.
			//const DATABASE_LOCATION = "../";
			// if this is set to "true", registration failure due to known usernames is reported,
			// and login failures are explained as either the wrong username or the wrong password.
			// You really want to set this to 'false', but it's on true by default because goddamnit
			// I'm going to confront you with security issues right off the bat =)
			const unsafe_reporting = false;
			/**
				Think about security for a moment. On the one hand, you want your website
				to not reveal whether usernames are already taken, so when people log in
				you will want to say "username or password incorrect". However, you also want
				to be able to tell people that they can't register because the username they
				picked is already taken.
				Because these are mutually exclusive, you can't do both using this framework.
				You can either use unsafe reporting, where the system will will tell you that
				a username exists, both during registration and login, or you can use safe
				reporting, and then the system will reject registrations based on username
				similarity, not exact match. But then it also won't say which of the username
				or password in a login attempt was incorrect.
			**/
		// =======================================================================
		// 	You can modify the following values, but they're not security related
		// =======================================================================
			// rename this to whatever you like
			const DATABASE_NAME = "users";  // Obsolete
			// this is the session timeout. If someone hasn't performed any page requests
			// in [timeout] seconds, they're considered logged out.
			const time_out = 604800;
			// You'll probably want to change this to something sensible. If your site is
			// www.sockmonkey.com, then you want this to be "sockmonkey.com"
			const DOMAIN_NAME = "Organizr";
			// This is going to be the "from" address
			const MAILER_NAME = "noreply@organizr";
			// if you want people to be able to reply to a real address, override
			// this variable to "yourmail@somedomain.ext" here.
			const MAILER_REPLYTO = "noreply@organizr";
		// =======================================================================
		// 	Don't modify any variables beyond this point =)
		// =======================================================================
		// this is the global error message. If anything goes wrong, this tells you why.
		var $error = "";
		// progress log
		var $info_log = array();
		// Information logging
		function info($string) { $this->info_log[] = $string; }
		// error log
		var $error_log = array();
		// Error logging
		function error($string) { $this->error_log[] = $string; }
		// all possible values for a hexadecimal number
		var $hex = "0123456789abcdef";
		// all possible values for an ascii password, skewed a bit so the number to letter ratio is closer to 1:1
		var $ascii = "0a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6A7B8C9D0E1F2G3H4I5J6K7L8M9N0O1P2Q3R4S5T6U7V8W9X0Y1Z23456789";
		// the regular expression for email matching (see http://www.regular-expressions.info/email.html)
		const emailregexp = "/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/";
		// the regular expression for SHA1 hash matching
		const sha1regexp = "/[0123456789abcdef]{40,40}/";
		// this will tell us whether the client that requested the page is authenticated or not.
		var $authenticated = false;
		// the guest user name
		const GUEST_USER  = GUEST_HASH;
		// this will contain the user name for the user doing the page request
		var $username = User::GUEST_USER;
		// if this is a properly logged in user, this will contain the data directory location for this user
		var $userdir = false;
		// the user's email address, if logged in.
		var $email = "";
		// the user's role in the system
		var $role = "";
		var $group = "";
		// global database handle
		var $database = false;
        
        //EMAIL SHIT
        function startEmail($email, $username, $subject, $body){
            
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = SMTPHOST;
            $mail->SMTPAuth = SMTPHOSTAUTH;
            $mail->Username = SMTPHOSTUSERNAME;
            $mail->Password = SMTPHOSTPASSWORD;
            $mail->SMTPSecure = SMTPHOSTTYPE;
            $mail->Port = SMTPHOSTPORT;
            $mail->setFrom(SMTPHOSTSENDEREMAIL, SMTPHOSTSENDERNAME);
            $mail->addReplyTo(SMTPHOSTSENDEREMAIL, SMTPHOSTSENDERNAME);
            $mail->isHTML(true);
            $mail->addAddress($email, $username);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            //$mail->send();
            if(!$mail->send()) {
                $this->error('Mailer Error: ' . $mail->ErrorInfo);
                $this->error = 'Mailer Error: ' . $mail->ErrorInfo;
            } else {
                $this->info('E-Mail sent!');
            }
            
        }
       
		// class object constructor
		function __construct($registration_callback=false)
		{
			// session management comes first. Warnings are repressed with @ because it will warn if something else already called session_start()
			@session_start();
            if(!isset($_COOKIE['Organizr'])) {
                if (empty($_SESSION["username"]) || empty($_SESSION["token"])) $this->resetSession();
            }else{
                $_SESSION["username"] = $_COOKIE['OrganizrU'];
            }
			// file location for the user database
			$dbfile = DATABASE_LOCATION.'users.db';
			// do we need to build a new database?
			$rebuild = false;
			if(!file_exists($dbfile)) { $rebuild = true;}
			// bind the database handler
			$this->database = new PDO("sqlite:" . $dbfile);
			// If we need to rebuild, the file will have been automatically made by the PDO call,
			// but we'll still need to define the user table before we can use the database.
			if($rebuild) { $this->rebuild_database($dbfile); }
			// finally, process the page request.
			$this->process($registration_callback);
		}
		// this function rebuilds the database if there is no database to work with yet
		function rebuild_database($dbfile)
		{
			$this->info("creating/rebuilding database as ".$dbfile);
			createSQLiteDB();
			$this->database = new PDO("sqlite:" . $dbfile);
		}
		// process a page request
		function process(&$registration_callback=false)
		{
			$this->database->beginTransaction();
			if(isset($_POST["op"]))
			{
				$operation = $_POST["op"];
				// logging in or out, and dropping your registration, may change authentication status
				if($operation == "login") { $this->authenticated = $this->login(); }
				// logout and unregister will unset authentication if successful
				elseif($operation == "logout") { $this->authenticated = !$this->logout(); }
				elseif($operation == "unregister") { $this->authenticated = !$this->unregister(); }
				// anything else won't change authentication status.
				elseif($operation == "register") { $this->register($registration_callback); }
				elseif($operation == "update") { $this->update(); }
				elseif($operation == "invite") { $this->invite(); }
				elseif($operation == "deleteinvite") { $this->deleteInvite(); }
				// we only allow password resetting if we can send notification mails
				elseif($operation == "reset" && User::use_mail) { $this->reset_password(); }
			}
			// if the previous operations didn't authorise the current user,
			// see if they're already marked as authorised in the database.
			if(!$this->authenticated) {
				$username = $_SESSION["username"];
				if($username != User::GUEST_USER) {
					$this->authenticated = $this->authenticate_user($username,"");
					if($this->authenticated) { $this->mark_user_active($username); }}}
			// at this point we can make some globals available.
			$this->username = $_SESSION["username"];
			$this->userdir = ($this->username !=User::GUEST_USER? USER_HOME . $this->username : false);
			$this->email = $this->get_user_email($this->username);
			$this->role = $this->get_user_role($this->username);
			//$this->group = $this->get_user_group($this->username);
			// clear database
			$this->database->commit();
			$this->database = null;
		}
	// ---------------------
	// validation passthroughs
	// ---------------------
		/**
		 * Called when the requested POST operation is "login"
		 */
		function login()
		{
			// get relevant values
			$username = $_POST["username"];
			$sha1 = $_POST["sha1"];
			$password = $_POST["password"];
            $rememberMe = $_POST["rememberMe"];
			// step 1: someone could have bypassed the javascript validation, so validate again.
			if(!$this->validate_user_name($username)) {
				$this->info("<strong>log in error:</strong> user name did not pass validation");
				return false; }
			if(preg_match(User::sha1regexp, $sha1)==0) {
				$this->info("<strong>log in error:</strong> password did not pass validation");
				return false; }
			// step 2: if validation passed, log the user in
			return $this->login_user($username, $sha1, $rememberMe == "true", $password); 
		}
		/**
		 * Called when the requested POST operation is "logout"
		 */
		function logout()
		{
			// get relevant value
			$username = $_POST["username"];
			// step 1: validate the user name.
			if(!$this->validate_user_name($username)) {
				$this->info("<strong>log in error:</strong> user name did not pass validation");
				return false; }
			// step 2: if validation passed, log the user out
			return $this->logout_user($username);
		}
		/**
		 * Users should always have the option to unregister
		 */
		function unregister()
		{
			// get relevant value
			$username = $_POST["username"];
			// step 1: validate the user name.
			if(!$this->validate_user_name($username)) {
				$this->info("<strong>unregistration error:</strong> user name did not pass validation");
				return false; }
			// step 2: if validation passed, drop the user from the system
			return $this->unregister_user($username);
		}
		/**
		 * Called when the requested POST operation is "register"
		 */
		function register(&$registration_callback=false)
		{
			// get relevant values
			$username = $_POST["username"];
			$email = $_POST["email"];
			$sha1 = $_POST["sha1"];
			$settings = $_POST["settings"];
			// step 1: someone could have bypassed the javascript validation, so validate again.
			if(!$this->validate_user_name($username)) {
				$this->info("<strong>registration error:</strong> user name did not pass validation");
				return false; }
			if(preg_match(User::emailregexp, $email)==0) {
				$this->info("<strong>registration error:</strong> email address did not pass validation");
				return false; }
			if(preg_match(User::sha1regexp, $sha1)==0) {
				$this->info("<strong>registration error:</strong> password did not pass validation");
				return false; }
			// step 2: if validation passed, register user
			$registered = $this->register_user($username, $email, $sha1, $registration_callback, $settings);
			if($registered && User::use_mail)
			{
				// send email notification
				$from = User::MAILER_NAME;
				$replyto = User::MAILER_REPLYTO;
				$domain_name = User::DOMAIN_NAME;
				$subject = User::DOMAIN_NAME . " registration";
				$body = <<<EOT
	Hi,
	this is an automated message to let you know that someone signed up at $domain_name with the user name "$username", using this email address as mailing address.
	Because of the way our user registration works, we have no idea which password was used to register this account (it gets one-way hashed by the browser before it is sent to our user registration system, so that we don't know your password either), so if you registered this account, hopefully you wrote your password down somewhere.
	However, if you ever forget your password, you can click the "I forgot my password" link in the log-in section for $domain_name and you will be sent an email containing a new, ridiculously long and complicated password that you can use to log in. You can change your password after logging in, but that's up to you. No one's going to guess it, or brute force it, but if other people can read your emails, it's generally a good idea to change passwords.
	If you were not the one to register this account, you can either contact us the normal way or —much easier— you can ask the system to reset the password for the account, after which you can simply log in with the temporary password and delete the account. That'll teach whoever pretended to be you not to mess with you!
	Of course, if you did register it yourself, welcome to $domain_name!
	- the $domain_name team
EOT;
				$headers = "From: $from\r\n";
				$headers .= "Reply-To: $replyto\r\n";
				$headers .= "X-Mailer: PHP/" . phpversion();
				//mail($email, $subject, $body, $headers);
                $this->startEmail($email, $username, $subject, $body);
			}
			return $registered;
		}
		/**
		 * Called when the requested POST operation is "update"
		 */
		function update()
		{
			// get relevant values
            @$username = trim($_POST["username"]);
			@$email = trim($_POST["email"]);
			@$sha1 = trim($_POST["sha1"]);
            @$role = trim($_POST["role"]);
			// step 1: someone could have bypassed the javascript validation, so validate again.
			if($email !="" && preg_match(User::emailregexp, $email)==0) {
				$this->info("<strong>registration error:</strong> email address did not pass validation");
				return false; }
			if($sha1 !="" && preg_match(User::sha1regexp, $sha1)==0) {
				$this->info("<strong>registration error:</strong> password did not pass validation");
				return false; }
			// step 2: if validation passed, update the user's information
			return $this->update_user($username, $email, $sha1, $role);
		}
		/**
		 * Called when the requested POST operation is "invite"
		 */
		function invite()
		{
			// get relevant values
            @$username = trim($_POST["username"]);
			@$email = trim($_POST["email"]);
			@$server = trim($_POST["server"]);
			// step 1: someone could have bypassed the javascript validation, so validate again.
			if($email !="" && preg_match(User::emailregexp, $email)==0) {
				$this->info("<strong>invite error:</strong> email address did not pass validation");
				writeLog("error", "$email didn't pass validation");
				return false; 
			}
			// step 2: if validation passed, send the user's information for invite
			return $this->invite_user($username, $email, $server);
			writeLog("success", "passing invite info for $email");
		}
		/**
		 * Reset a user's password
		 */
		function reset_password()
		{
			// get the email for which we should reset
			$email = $_POST["email"];
			// step 1: someone could have bypassed the javascript validation, so validate again.
			if(preg_match(User::emailregexp, $email)==0) {
				$this->info("email address did not pass validation");
				return false; }
			// step 2: if validation passed, see if there is a matching user, and reset the password if there is
			$newpassword = $this->random_ascii_string(64);
			$sha1 = sha1($newpassword);
			$query = "SELECT username, token FROM users WHERE email = '$email'";
			$username = "";
			$token = "";
			foreach($this->database->query($query) as $data) { $username = $data["username"]; $token = $data["token"]; break; }
			// step 2a: if there was no user to reset a password for, stop.
			if($username == "" || $token == "") return false;
			// step 2b: if there was a user to reset a password for, reset it.
			$dbpassword = $this->token_hash_password($username, $sha1, $token);
			$update = "UPDATE users SET password = '$dbpassword' WHERE email= '$email'";
   			writeLog("success", "$username has reset their password");
			$this->database->exec($update);
            //$this->info("Email has been sent with new password");
			// step 3: notify the user of the new password
			$from = User::MAILER_NAME;
			$replyto = User::MAILER_REPLYTO;
			$domain_name = User::DOMAIN_NAME;
			$subject = User::DOMAIN_NAME . " password reset request";
			$body = <<<EOT
	Hi,
	this is an automated message to let you know that someone requested a password reset for the $domain_name user account with user name "$username", which is linked to this email address.
	We've reset the password to the following 64 character string, so make sure to copy/paste it without any leading or trailing spaces:
	$newpassword
	If you didn't even know this account existed, now is the time to log in and delete it. How dare people use your email address to register accounts! Of course, if you did register it yourself, but you didn't request the reset, some jerk is apparently reset-spamming. We hope he gets run over by a steam shovel driven by rabid ocelots or something.
	Then again, it's far more likely that you did register this account, and you simply forgot the password so you asked for the reset yourself, in which case: here's your new password, and thank you for your patronage at $domain_name!
	- the $domain_name team
EOT;
			$headers = "From: $from\r\n";
			$headers .= "Reply-To: $replyto\r\n";
			$headers .= "X-Mailer: PHP/" . phpversion();
			//mail($email, $subject, $body, $headers);
            $this->startEmail($email, $username, $subject, $body);
		}
	// ------------------
	// specific functions
	// ------------------
		// session management: set session values
		function setSession($username, $token)
		{
			$_SESSION["username"]=$username;
			$_SESSION["token"]=$token;
		}
		// session management: reset session values
		function resetSession()
		{
			$_SESSION["username"] = User::GUEST_USER;
			$_SESSION["token"] = -1;
            unset($_COOKIE['Organizr']);
            setcookie('Organizr', '', time() - 3600, '/', DOMAIN);
            setcookie('Organizr', '', time() - 3600, '/');
            unset($_COOKIE['OrganizrU']);
            setcookie('OrganizrU', '', time() - 3600, '/', DOMAIN);
            setcookie('OrganizrU', '', time() - 3600, '/');
            unset($_COOKIE['cookiePassword']);
            setcookie("cookiePassword", '', time() - 3600, '/', DOMAIN);
            setcookie("cookiePassword", '', time() - 3600, '/');
		}
		/**
		 * Validate a username. Empty usernames or names
		 * that are modified by making them SQL safe are
		 * considered not validated.
		 */
		function validate_user_name($username)
		{
			$cleaned = $this->clean_SQLite_string($username);
			$validated = ($cleaned != "" && $cleaned==$username);
			if(!$validated) { $this->error = "user name did not pass validation."; $this->error("user name did not pass validation."); }
			return $validated;
		}
		/**
		 * Clean strings for SQL insertion as string in SQLite (single quote enclosed).
		 * Note that if the cleaning changes the string, this system won't insert.
		 * The validate_user_name() function will flag this as a validation failure and
		 * the database operation is never carried out.
		 */
		function clean_SQLite_string($string)
		{
			$search = array("'", "\\", ";");
			$replace = array('', '', '');
			return trim(str_replace($search, $replace, $string));
		}
		/**
		 * Verify that the given username is allowed
		 * to perform the given operation.
		 */
		function authenticate_user($username, $operation)
		{
			// actually logged in?
			if($this->is_user_active($username)===false) { return false; }
            
			// logged in, but do the tokens match?
			$token = $this->get_user_token($username);
            if(MULTIPLELOGIN == "false"){
            
                if(isset($_COOKIE["Organizr"])){
                    if($_COOKIE["Organizr"] == $token){
                        return true;
                    }else{
                        $this->error("cookie token mismatch for $username");
                        unset($_COOKIE['Organizr']);
                        setcookie('Organizr', '', time() - 3600, '/', DOMAIN);
                        setcookie('Organizr', '', time() - 3600, '/');
                        unset($_COOKIE['OrganizrU']);
                        setcookie('OrganizrU', '', time() - 3600, '/', DOMAIN);
                        setcookie('OrganizrU', '', time() - 3600, '/');
                        unset($_COOKIE['cookiePassword']);
                        setcookie("cookiePassword", '', time() - 3600, '/', DOMAIN);
                        setcookie("cookiePassword", '', time() - 3600, '/');
                        return false;
                    }
                }else{
                    if($token != $_SESSION["token"]) {
                        
                        $this->error("token mismatch for $username");
                        return false; 
                    
                    }
                    // active, using the correct token -> authenticated
                     setcookie("cookiePassword", COOKIEPASSWORD, time() + (86400 * 7), "/", DOMAIN);
                     return true;
                    
                }
                
            }else{
                
                setcookie("cookiePassword", COOKIEPASSWORD, time() + (86400 * 7), "/", DOMAIN);
                return true;
                
            }    
            
		}
		/**
		 * Unicode friendly(ish) version of strtolower
		 * see: http://ca3.php.net/manual/en/function.strtolower.php#91805
		 */
		function strtolower_utf8($string)
		{
			$convert_to = array( "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u",
							"v", "w", "x", "y", "z", "à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï",
							"ð", "ñ", "ò", "ó", "ô", "õ", "ö", "ø", "ù", "ú", "û", "ü", "ý", "а", "б", "в", "г", "д", "е", "ё", "ж",
							"з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч", "ш", "щ", "ъ", "ы",
							"ь", "э", "ю", "я" );
			$convert_from = array( "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U",
							"V", "W", "X", "Y", "Z", "À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï",
							"Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ù", "Ú", "Û", "Ü", "Ý", "А", "Б", "В", "Г", "Д", "Е", "Ё", "Ж",
							"З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Ъ", "Ъ",
							"Ь", "Э", "Ю", "Я" );
			return str_replace($convert_from, $convert_to, $string);
		}
		/**
		 * This functions flattens user name strings for similarity comparison purposes
		 */
		function homogenise_username($string)
		{
			// cut off trailing numbers
			$string = preg_replace("/\d+$/", '', $string);
			// and then replace non-terminal numbers with
			// their usual letter counterparts.
			$s = array("1","3","4","5","7","8","0");
			$r = array("i","e","a","s","t","ate","o");
			$string = str_replace($s, $r, $string);
			// finally, collapse case
			return $this->strtolower_utf8($string);
		}
		/**
		 * We don't require assloads of personal information.
		 * A username and a password are all we want. The rest
		 * is profile information that can be set, but in no way
		 * needs to be, in the user's profile section
		 */
		function register_user($username, $email, $sha1, &$registration_callback = false, $settings) {
			$username = strtolower($username);
			$dbpassword = $this->token_hash_password($username, $sha1, "");
			if($dbpassword==$sha1) die("password hashing is not implemented.");
            $newRole = "admin"; 
            $queryAdmin = "SELECT username FROM users";
            foreach($this->database->query($queryAdmin) as $data) {
                $newRole = "user";
            }
			// Does user already exist? (see notes on safe reporting)
			if(User::unsafe_reporting) {
				$query = "SELECT username FROM users WHERE username LIKE '$username' COLLATE NOCASE";
				foreach($this->database->query($query) as $data) {
					$this->info("user account for $username not created.");
					$this->error = "this user name is already being used by someone else.";
                    $this->error("this user name is already being used by someone else.");
					return false; }
			} else {	
				$query = "SELECT username FROM users";
				$usernames = array();
				foreach($this->database->query($query) as $data) { $usernames[] = $this->homogenise_username($data["username"]); }
				if(in_array($this->homogenise_username($username), $usernames)) {
					//$this->info("user account for $username not created.");
					$this->error = "<strong>$username</strong> is not allowed, because it is too similar to other user names.";
                    $this->error("<strong>$username</strong> is not allowed, because it is too similar to other user names.");
					return false; }
			}
			// Is email address already in use? (see notes on safe reporting)
			if (isset($email) && $email) {
				$query = "SELECT * FROM users WHERE email = '$email' COLLATE NOCASE";
				foreach($this->database->query($query) as $data) {
					$this->info("user account for $username not created.");
					$this->error = "this email address is already in use by someone else.";
					$this->error("this email address is already in use by someone else.");
					return false; 
				}
			} else {
				$email = $this->random_ascii_string(32).'@placeholder.eml';
			}

			// This user can be registered
			$insert = "INSERT INTO users (username, email, password, token, role, active, last) ";
			$insert .= "VALUES ('".strtolower($username)."', '$email', '$dbpassword', '', '$newRole', 'false', '') ";
			$this->database->exec($insert);
			$query = "SELECT * FROM users WHERE username = '$username'";
			foreach($this->database->query($query) as $data) {
				$this->info("created user account for $username");
    			writeLog("success", "$username has just registered");
				$this->update_user_token($username, $sha1, false);
				// make the user's data directory
				$dir = USER_HOME . $username;
				if(!mkdir($dir, 0760, true)) { $this->error("could not make user directory $dir"); return false; }
				//$this->info("created user directory $dir");
				// if there is a callback, call it
				if($registration_callback !== false) { $registration_callback($username, $email, $dir); }
                if($settings !== 'true' && $settings !== true) { $this->login_user($username, $sha1, true, '', false); }
				return true; }
			$this->error = "unknown database error occured.";
            $this->error("unknown database error occured.");
			return false;
		}
		/**
		 * Log a user in
		 */
		function login_user($username, $sha1, $remember, $password, $surface = true) {
			$username = strtolower($username);
			
            $buildLog = function($username, $authType) {
                if(file_exists(FAIL_LOG)) {
                    $getFailLog = str_replace("\r\ndate", "date", file_get_contents(FAIL_LOG));
                    $gotFailLog = json_decode($getFailLog, true);
                }
                
                $failLogEntryFirst = array('logType' => 'login_log', 'auth' => array(array('date' => date("Y-m-d H:i:s"), 'username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'], 'auth_type' => $authType)));
                $failLogEntry = array('date' => date("Y-m-d H:i:s"), 'username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'], 'auth_type' => $authType);
                if(isset($gotFailLog)) { 
                    array_push($gotFailLog["auth"], $failLogEntry);
                    $writeFailLog = str_replace("date", "\r\ndate", json_encode($gotFailLog));
                } else {
                    $writeFailLog = str_replace("date", "\r\ndate", json_encode($failLogEntryFirst));
                }
                return $writeFailLog;
            };
			
			// External Authentication
			$authSuccess = false;
			$function = 'plugin_auth_'.AUTHBACKEND;
			switch (AUTHTYPE) {
				case 'external':
					if (function_exists($function)) {
						$authSuccess = $function($username, $password);
					}
					break;
				case 'both':
					if (function_exists($function)) {
						$authSuccess = $function($username, $password);
					}
				default: // Internal
					if (!$authSuccess) {
						// perform the internal authentication step
						$query = "SELECT password FROM users WHERE username = '".$username."' COLLATE NOCASE";
						foreach($this->database->query($query) as $data) {
							if (password_verify($password, $data["password"])) { // Better
								$authSuccess = true;
							} else {
								// Legacy - Less Secure
								$dbpassword = $this->token_hash_password($username, $sha1, $this->get_user_token($username));
								if($dbpassword==$data["password"]) { 
									$authSuccess = true;
								}
							}
						}
					}
			}
			
			if ($authSuccess) {
				// Make sure user exists in database
				$query = "SELECT username FROM users WHERE username = '".$username."' COLLATE NOCASE";
				$userExists = false;
				foreach($this->database->query($query) as $data) {
					$userExists = true;
					break;
				}
				
				if ($userExists) {
					// authentication passed - 1) mark active and update token
					$this->mark_user_active($username);
					$this->setSession($username, $this->update_user_token($username, $sha1, false));
					// authentication passed - 2) signal authenticated
					if($remember == "true") {
						setcookie("Organizr", $this->get_user_token($username), time() + (86400 * 7), "/", DOMAIN);
						setcookie("OrganizrU", $username, time() + (86400 * 7), "/", DOMAIN);
						
					}
					$this->info("Welcome $username");
					file_put_contents(FAIL_LOG, $buildLog($username, "good_auth"));
					chmod(FAIL_LOG, 0660);
					setcookie("cookiePassword", COOKIEPASSWORD, time() + (86400 * 7), "/", DOMAIN);
     				writeLog("success", "$username has logged in");
					return true; 
				} else if (AUTHBACKENDCREATE !== 'false' && $surface) {
					// Create User
					$falseByRef = false;
					$this->register_user($username, (is_array($authSuccess) && isset($authSuccess['email']) ? $authSuccess['email'] : ''), $sha1, $falseByRef, !$remember);
				} else {
					// authentication failed
					//$this->info("Successful Backend Auth, No User in DB, Create Set to False");
					file_put_contents(FAIL_LOG, $buildLog($username, "bad_auth"));
					chmod(FAIL_LOG, 0660);
					if(User::unsafe_reporting) { $this->error = "Successful Backend Auth, $username not in DB, Create Set to False."; $this->error("Successful Backend Auth, $username not in DB, Create Set to False."); }
					else { $this->error = "Not permitted to login as this user, please contact an administrator."; $this->error("Not permitted to login as this user, please contact an administrator"); }
					return false; 
				}
			} else if (!$authSuccess) {
				// authentication failed
				//$this->info("password mismatch for $username");
    			writeLog("error", "$username tried to sign-in with the wrong password");
				file_put_contents(FAIL_LOG, $buildLog($username, "bad_auth"));
				chmod(FAIL_LOG, 0660);
				if(User::unsafe_reporting) { $this->error = "incorrect password for $username."; $this->error("incorrect password for $username."); }
				else { $this->error = "the specified username/password combination is incorrect."; $this->error("the specified username/password combination is incorrect."); }
				return false; 
			} else {
				// authentication could not take place
				//$this->info("there was no user $username in the database");
				file_put_contents(FAIL_LOG, $buildLog($username, "bad_auth"));
				chmod(FAIL_LOG, 0660);
				if(User::unsafe_reporting) { $this->error = "user $username is unknown."; $this->error("user $username is unknown."); }
				else { $this->error = "you either did not correctly input your username, or password (... or both)."; $this->error("you either did not correctly input your username, or password (... or both)."); }
				return false;
			}
		}
		/**
		 * Update a user's information
		 */
		function update_user($username, $email, $sha1, $role)
		{
			if($email !="") {
				$update = "UPDATE users SET email = '$email' WHERE username = '$username' COLLATE NOCASE";
				$this->database->exec($update); }
            if($role !="") {
				$update = "UPDATE users SET role = '$role' WHERE username = '$username' COLLATE NOCASE";
				$this->database->exec($update); }
			if($sha1 !="") {
				$dbpassword = $this->token_hash_password($username, $sha1, $this->get_user_token($username));
				$update = "UPDATE users SET password = '$dbpassword' WHERE username = '$username'";
				$this->database->exec($update); }
   			writeLog("success", "information for $username has been updated");
			$this->info("updated the information for <strong>$username</strong>");
		}
		/**
		 * Drop a invite from the system
		 */
		function deleteInvite()
		{
			@$id = trim($_POST["id"]);
			$delete = "DELETE FROM invites WHERE id = '$id' COLLATE NOCASE";
			$this->database->exec($delete);
			$this->info("Plex Invite: <strong>$id</strong> has been deleted out of Organizr");
    		writeLog("success", "PLEX INVITE: $id has been deleted");
			return true;
		}
		
		/**
		 * Invite using a user's information
		 */
		function invite_user($username = "none", $email, $server)
		{
			$emailCSS = constant('EMAIL_CSS');
			$domain = getServerPath();
			$topImage = $domain."images/organizr-logo-h.png";
			$uServer = strtoupper($server);
			$now = date("Y-m-d H:i:s");
			$inviteCode = randomCode(6);
			$username = (!empty($username) ? $username : strtoupper($server) . " User");
			$link = getServerPath()."?inviteCode=".$inviteCode;
			if($email !="") {
				$insert = "INSERT INTO invites (username, email, code, valid, date) ";
				$insert .= "VALUES ('".strtolower($username)."', '$email', '$inviteCode', 'Yes', '$now') ";
				$this->database->exec($insert);
			}
   			writeLog("success", "$email has been invited to the $server server");
			$this->info("$email has been invited to the $server server");
			if($insert && User::use_mail)
			{
				// send email notification
				$subject = DOMAIN . " $uServer invite!";
				$body = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <!--[if gte mso 9]><xml>
<o:OfficeDocumentSettings>
<o:AllowPNG/>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml><![endif]-->
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width">
  <!--[if !mso]><!-->
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <!--<![endif]-->
  <title></title>
  <!--[if !mso]><!-- -->
  <link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet" type="text/css">
  <!--<![endif]-->
</head>
<body class="clean-body" style="margin: 0;padding: 0;-webkit-text-size-adjust: 100%;background-color: #FFFFFF">
  <!--[if IE]><div class="ie-browser"><![endif]-->
  <!--[if mso]><div class="mso-container"><![endif]-->
  <div class="nl-container" style="min-width: 320px;Margin: 0 auto;background-color: #FFFFFF">
    <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color: #FFFFFF;"><![endif]-->
    <div style="background-color:#333333;">
      <div style="Margin: 0 auto;min-width: 320px;max-width: 615px;width: 615px;width: calc(30500% - 193060px);overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"
        class="block-grid ">
        <div style="border-collapse: collapse;display: table;width: 100%;">
          <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#333333;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width: 615px;"><tr class="layout-full-width" style="background-color:transparent;"><![endif]-->
          <!--[if (mso)|(IE)]><td align="center" width="615" style=" width:615px; padding-right: 0px; padding-left: 0px; padding-top:0px; padding-bottom:0px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><![endif]-->
          <div class="col num12" style="min-width: 320px;max-width: 615px;width: 615px;width: calc(29500% - 180810px);background-color: transparent;">
            <div style="background-color: transparent; width: 100% !important;">
              <!--[if (!mso)&(!IE)]><!-->
              <div style="border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent; padding-top:0px; padding-bottom:0px; padding-right: 0px; padding-left: 0px;">
                <!--<![endif]-->
                <div align="left" class="img-container left fullwidth" style="padding-right: 30px;  padding-left: 30px;">
                  <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 30px; padding-left: 30px;" align="left"><![endif]-->
                  <img class="left fullwidth" align="left" border="0" src="https://sonflix.com/images/organizr-logo-h.png" alt="Image" title="Image"
                    style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: block !important;border: 0;height: auto;float: none;width: 100%;max-width: 555px"
                    width="555">
                  <!--[if mso]></td></tr></table><![endif]-->
                </div>
                <!--[if (!mso)&(!IE)]><!-->
              </div>
              <!--<![endif]-->
            </div>
          </div>
          <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
        </div>
      </div>
    </div>
    <div style="background-color:#333333;">
      <div style="Margin: 0 auto;min-width: 320px;max-width: 615px;width: 615px;width: calc(30500% - 193060px);overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"
        class="block-grid ">
        <div style="border-collapse: collapse;display: table;width: 100%;">
          <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#333333;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width: 615px;"><tr class="layout-full-width" style="background-color:transparent;"><![endif]-->
          <!--[if (mso)|(IE)]><td align="center" width="615" style=" width:615px; padding-right: 0px; padding-left: 0px; padding-top:0px; padding-bottom:0px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><![endif]-->
          <div class="col num12" style="min-width: 320px;max-width: 615px;width: 615px;width: calc(29500% - 180810px);background-color: transparent;">
            <div style="background-color: transparent; width: 100% !important;">
              <!--[if (!mso)&(!IE)]><!-->
              <div style="border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent; padding-top:0px; padding-bottom:0px; padding-right: 0px; padding-left: 0px;">
                <!--<![endif]-->
                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top: 0px; padding-bottom: 0px;"><![endif]-->
                <div style="font-family:'Lato', Tahoma, Verdana, Segoe, sans-serif;line-height:120%;color:#FFFFFF; padding-right: 0px; padding-left: 0px; padding-top: 0px; padding-bottom: 0px;">
                  <div style="font-size:12px;line-height:14px;color:#FFFFFF;font-family:'Lato', Tahoma, Verdana, Segoe, sans-serif;text-align:left;">
                    <p style="margin: 0;font-size: 12px;line-height: 14px;text-align: center"><span style="font-size: 16px; line-height: 19px;"><strong><span style="line-height: 19px; font-size: 16px;">Join My $uServer Server</span></strong>
                      </span>
                    </p>
                  </div>
                </div>
                <!--[if mso]></td></tr></table><![endif]-->
                <!--[if (!mso)&(!IE)]><!-->
              </div>
              <!--<![endif]-->
            </div>
          </div>
          <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
        </div>
      </div>
    </div>
    <div style="background-color:#393939;">
      <div style="Margin: 0 auto;min-width: 320px;max-width: 615px;width: 615px;width: calc(30500% - 193060px);overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"
        class="block-grid ">
        <div style="border-collapse: collapse;display: table;width: 100%;">
          <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#393939;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width: 615px;"><tr class="layout-full-width" style="background-color:transparent;"><![endif]-->
          <!--[if (mso)|(IE)]><td align="center" width="615" style=" width:615px; padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><![endif]-->
          <div class="col num12" style="min-width: 320px;max-width: 615px;width: 615px;width: calc(29500% - 180810px);background-color: transparent;">
            <div style="background-color: transparent; width: 100% !important;">
              <!--[if (!mso)&(!IE)]><!-->
              <div style="border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
                <!--<![endif]-->
                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 30px; padding-left: 30px; padding-top: 0px; padding-bottom: 0px;"><![endif]-->
                <div style="font-family:'Ubuntu', Tahoma, Verdana, Segoe, sans-serif;line-height:120%;color:#FFFFFF; padding-right: 30px; padding-left: 30px; padding-top: 0px; padding-bottom: 0px;">
                  <div style="font-family:Ubuntu, Tahoma, Verdana, Segoe, sans-serif;font-size:12px;line-height:14px;color:#FFFFFF;text-align:left;">
                    <p style="margin: 0;font-size: 12px;line-height: 14px;text-align: center"><span style="font-size: 16px; line-height: 19px;"><strong>LOOK WHO JUST GOT AN INVITE</strong></span></p>
                  </div>
                </div>
                <!--[if mso]></td></tr></table><![endif]-->
                <div style="padding-right: 5px; padding-left: 5px; padding-top: 5px; padding-bottom: 5px;">
                  <!--[if (mso)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 5px;padding-left: 5px; padding-top: 5px; padding-bottom: 5px;"><table width="55%" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                  <div align="center">
                    <div style="border-top: 2px solid #66D9EF; width:55%; line-height:2px; height:2px; font-size:2px;">&#160;</div>
                  </div>
                  <!--[if (mso)]></td></tr></table></td></tr></table><![endif]-->
                </div>
                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 30px; padding-left: 30px; padding-top: 15px; padding-bottom: 10px;"><![endif]-->
                <div style="font-family:'Lato', Tahoma, Verdana, Segoe, sans-serif;line-height:120%;color:#FFFFFF; padding-right: 30px; padding-left: 30px; padding-top: 15px; padding-bottom: 10px;">
                  <div style="font-family:'Lato',Tahoma,Verdana,Segoe,sans-serif;font-size:12px;line-height:14px;color:#FFFFFF;text-align:left;">
                    <p style="margin: 0;font-size: 12px;line-height: 14px"><span style="font-size: 28px; line-height: 33px;">Hey $username,</span></p>
                  </div>
                </div>
                <!--[if mso]></td></tr></table><![endif]-->
                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 15px; padding-left: 30px; padding-top: 10px; padding-bottom: 25px;"><![endif]-->
                <div style="font-family:'Lato', Tahoma, Verdana, Segoe, sans-serif;line-height:180%;color:#FFFFFF; padding-right: 15px; padding-left: 30px; padding-top: 10px; padding-bottom: 25px;">
                  <div style="font-size:12px;line-height:22px;font-family:'Lato',Tahoma,Verdana,Segoe,sans-serif;color:#FFFFFF;text-align:left;">
                    <p style="margin: 0;font-size: 14px;line-height: 25px"><span style="font-size: 18px; line-height: 32px;"><em><span style="line-height: 32px; font-size: 18px;">Here is an invite to my $uServer server.  The code to join is $inviteCode.</span></em>
                      </span>
                    </p>
                  </div>
                </div>
                <!--[if mso]></td></tr></table><![endif]-->

                <div align="center" class="button-container center" style="padding-right: 30px; padding-left: 30px; padding-top:15px; padding-bottom:15px;">
                  <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; border-collapse: collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;"><tr><td style="padding-right: 30px; padding-left: 30px; padding-top:15px; padding-bottom:15px;" align="center"><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="https://sonflix.com" style="height:48px; v-text-anchor:middle; width:194px;" arcsize="53%" strokecolor="" fillcolor="#66D9EF"><w:anchorlock/><center style="color:#000; font-family:'Lato', Tahoma, Verdana, Segoe, sans-serif; font-size:18px;"><![endif]-->
                  <a href="$link" target="_blank" style="display: inline-block;text-decoration: none;-webkit-text-size-adjust: none;text-align: center;color: #000; background-color: #66D9EF; border-radius: 25px; -webkit-border-radius: 25px; -moz-border-radius: 25px; max-width: 180px; width: 114px; width: auto; border-top: 3px solid transparent; border-right: 3px solid transparent; border-bottom: 3px solid transparent; border-left: 3px solid transparent; padding-top: 5px; padding-right: 30px; padding-bottom: 5px; padding-left: 30px; font-family: 'Lato', Tahoma, Verdana, Segoe, sans-serif;mso-border-alt: none">
<span style="font-size:12px;line-height:21px;"><span style="font-size: 18px; line-height: 32px;" data-mce-style="font-size: 18px; line-height: 44px;">JOIN MY SERVER</span></span></a>
                  <!--[if mso]></center></v:roundrect></td></tr></table><![endif]-->
                </div>
                  <!--[if mso]></center></v:roundrect></td></tr></table><![endif]-->
                </div>
                <!--[if (!mso)&(!IE)]><!-->
              </div>
              <!--<![endif]-->
            </div>
          </div>
          <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
        </div>
      </div>
    </div>
    <div style="background-color:#ffffff;">
      <div style="Margin: 0 auto;min-width: 320px;max-width: 615px;width: 615px;width: calc(30500% - 193060px);overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"
        class="block-grid ">
        <div style="border-collapse: collapse;display: table;width: 100%;">
          <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#ffffff;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width: 615px;"><tr class="layout-full-width" style="background-color:transparent;"><![endif]-->
          <!--[if (mso)|(IE)]><td align="center" width="615" style=" width:615px; padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:30px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><![endif]-->
          <div class="col num12" style="min-width: 320px;max-width: 615px;width: 615px;width: calc(29500% - 180810px);background-color: transparent;">
            <div style="background-color: transparent; width: 100% !important;">
              <!--[if (!mso)&(!IE)]><!-->
              <div style="border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent; padding-top:5px; padding-bottom:30px; padding-right: 0px; padding-left: 0px;">
                <!--<![endif]-->
                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 0px; padding-bottom: 10px;"><![endif]-->
                <div style="font-family:'Lato', Tahoma, Verdana, Segoe, sans-serif;line-height:120%;color:#555555; padding-right: 10px; padding-left: 10px; padding-top: 0px; padding-bottom: 10px;">
                  <div style="font-size:12px;line-height:14px;color:#555555;font-family:'Lato', Tahoma, Verdana, Segoe, sans-serif;text-align:left;">
                    <p style="margin: 0;font-size: 14px;line-height: 17px;text-align: center"><strong><span style="font-size: 26px; line-height: 31px;">What&#160;do I do?<br></span></strong></p>
                  </div>
                </div>
                <!--[if mso]></td></tr></table><![endif]-->
                <div style="padding-right: 20px; padding-left: 20px; padding-top: 15px; padding-bottom: 20px;">
                  <!--[if (mso)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 20px;padding-left: 20px; padding-top: 15px; padding-bottom: 20px;"><table width="40%" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                  <div align="center">
                    <div style="border-top: 3px solid #66D9EF; width:40%; line-height:3px; height:3px; font-size:3px;">&#160;</div>
                  </div>
                  <!--[if (mso)]></td></tr></table></td></tr></table><![endif]-->
                </div>
                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 0px; padding-bottom: 0px;"><![endif]-->
                <div style="font-family:'Lato', Tahoma, Verdana, Segoe, sans-serif;line-height:180%;color:#7E7D7D; padding-right: 10px; padding-left: 10px; padding-top: 0px; padding-bottom: 0px;">
                  <div style="font-size:12px;line-height:22px;color:#7E7D7D;font-family:'Lato', Tahoma, Verdana, Segoe, sans-serif;text-align:left;">
                    <p style="margin: 0;font-size: 14px;line-height: 25px;text-align: center"><em><span style="font-size: 18px; line-height: 32px;">You can click the link above - You could also head over to my website to join by going here: <a href="$domain">$domain</a> and clicking Join My Server</span></em></p>
                  </div>
                </div>
                <!--[if mso]></td></tr></table><![endif]-->
                <!--[if (!mso)&(!IE)]><!-->
              </div>
              <!--<![endif]-->
            </div>
          </div>
          <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
        </div>
      </div>
    </div>
    <div style="background-color:#333333;">
      <div style="Margin: 0 auto;min-width: 320px;max-width: 615px;width: 615px;width: calc(30500% - 193060px);overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;"
        class="block-grid ">
        <div style="border-collapse: collapse;display: table;width: 100%;">
          <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#333333;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width: 615px;"><tr class="layout-full-width" style="background-color:transparent;"><![endif]-->
          <!--[if (mso)|(IE)]><td align="center" width="615" style=" width:615px; padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><![endif]-->
          <div class="col num12" style="min-width: 320px;max-width: 615px;width: 615px;width: calc(29500% - 180810px);background-color: transparent;">
            <div style="background-color: transparent; width: 100% !important;">
              <!--[if (!mso)&(!IE)]><!-->
              <div style="border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
                <!--<![endif]-->
                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px;"><![endif]-->
                <div style="font-family:'Lato', Tahoma, Verdana, Segoe, sans-serif;line-height:120%;color:#959595; padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px;">
                  <div style="font-size:12px;line-height:14px;color:#959595;font-family:'Lato', Tahoma, Verdana, Segoe, sans-serif;text-align:left;">
                    <p style="margin: 0;font-size: 14px;line-height: 17px;text-align: center">This&#160;email was sent by <a style="color:#AD80FD;text-decoration: underline;" title="Organizr" href="https://github.com/causefx/Organizr"
                        target="_blank" rel="noopener noreferrer">Organizr</a><strong><br></strong></p>
                  </div>
                </div>
                <!--[if mso]></td></tr></table><![endif]-->
                <!--[if (!mso)&(!IE)]><!-->
              </div>
              <!--<![endif]-->
            </div>
          </div>
          <!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
        </div>
      </div>
    </div>
    <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
  </div>
  <!--[if (mso)|(IE)]></div><![endif]-->
</body>
</html>
EOT;
				
                $this->startEmail($email, $username, $subject, $body);
			}
		}
		/**
		 * Log a user out.
		 */
		function logout_user($username)
		{
			$update = "UPDATE users SET active = 'false' WHERE username = '$username' COLLATE NOCASE";
			$this->database->exec($update);
			$this->resetSession();
			$this->info("Buh-Bye <strong>$username</strong>!");
            unset($_COOKIE['Organizr']);
            setcookie('Organizr', '', time() - 3600, '/', DOMAIN);
            setcookie('Organizr', '', time() - 3600, '/');
            unset($_COOKIE['OrganizrU']);
            setcookie('OrganizrU', '', time() - 3600, '/', DOMAIN);
            setcookie('OrganizrU', '', time() - 3600, '/');
            unset($_COOKIE['cookiePassword']);
            setcookie("cookiePassword", '', time() - 3600, '/', DOMAIN);
            setcookie("cookiePassword", '', time() - 3600, '/');
   			writeLog("success", "$username has signed out");
			return true;
		}
		/**
		 * Drop a user from the system
		 */
		function unregister_user($username)
		{
			$delete = "DELETE FROM users WHERE username = '$username' COLLATE NOCASE";
			$this->database->exec($delete);
			$this->info("<strong>$username</strong> has been kicked out of Organizr");
			//$this->resetSession();
    		$dir = USER_HOME . $username;
    		if(!rmdir($dir)) { $this->error("could not delete user directory $dir"); }
    		$this->info("and we deleted user directory $dir");
    		writeLog("success", "$username has been deleted");
			return true;
		}
		/**
		 * The incoming password will already be a sha1 print (40 bytes) long,
		 * but for the database we want it to be hased as sha256 (using 64 bytes).
		 */
		function token_hash_password($username, $sha1, $token)
		{
			
			return hash("sha256",($this->database->query('SELECT username FROM users WHERE username = \''.$username.'\' COLLATE NOCASE')->fetch()['username']).$sha1.$token);
		}
		/**
		 * Get a user's email address
		 */
		function get_user_email($username)
		{
			if($username && $username !="" && $username !=User::GUEST_USER) {
				$query = "SELECT email FROM users WHERE username = '$username' COLLATE NOCASE";
				foreach($this->database->query($query) as $data) { return $data["email"]; }}
			return "";
		}
		/**
		 * Get a user's role
		 */
		function get_user_role($username)
		{
			if($username && $username !="" && $username !=User::GUEST_USER) {
				$query = "SELECT role FROM users WHERE username = '$username' COLLATE NOCASE";
				foreach($this->database->query($query) as $data) { return $data["role"]; }}
			return User::GUEST_USER;
		}
        
       /* function get_user_group($username)
		{
			if($username && $username !="" && $username !=User::GUEST_USER) {
				$query = "SELECT group FROM users WHERE username = '$username' COLLATE NOCASE";
				foreach($this->database->query($query) as $data) { return $data["group"]; }}
			return User::GUEST_USER;
		}*/
		/**
		 * Get the user token
		 */
		function get_user_token($username)
		{
			$query = "SELECT token FROM users WHERE username = '$username' COLLATE NOCASE";
			foreach($this->database->query($query) as $data) { return $data["token"]; }
			return false;
		}
		/**
		 * Update the user's token and password upon successful login
		 */
		function update_user_token($username, $sha1, $noMsg)
		{
			// update the user's token
			$token = $this->random_hex_string(32);
			$update = "UPDATE users SET token = '$token' WHERE username = '$username' COLLATE NOCASE";
			$this->database->exec($update);
			// update the user's password
			$newpassword = $this->token_hash_password($username, $sha1, $token);
			$update = "UPDATE users SET password = '$newpassword' WHERE username = '$username' COLLATE NOCASE";
			$this->database->exec($update);
			if($noMsg == "false"){
                $this->info("token and password updated for <strong>$username</strong>");   
            }
			return $token;
		}
		/**
		 * Mark a user as active.
		 */
		function mark_user_active($username)
		{
			$update = "UPDATE users SET active = 'true', last = '" . time() . "' WHERE username = '$username' COLLATE NOCASE";
			$this->database->exec($update);
			//$this->info("$username has been marked currently active.");
			return true;
		}
		/**
		 * Check if user can be considered active
		 */
		function is_user_active($username)
		{
			$last = 0;
			$active = "false";
			$query = "SELECT last, active FROM users WHERE username = '$username' COLLATE NOCASE";
			foreach($this->database->query($query) as $data) {
				$last = intval($data["last"]);
				$active = $data["active"];
				break; }
			if($active=="true") {
				$diff = time() - $last;
				if($diff >= User::time_out) {
					$this->logout_user($username);
					$this->error("$username was active but timed out (timeout set at " . User::time_out . " seconds, difference was $diff seconds)");
					return false; }
				//$this->info("$username is active");
				return true; }
			$this->error("<strong>$username</strong> is not active");
			$this->resetSession();
			return false;
		}
		/**
		 * Random hex string generator
		 */
		function random_hex_string($len)
		{
			$string = "";
			$max = strlen($this->hex)-1;
			while($len-->0) { $string .= $this->hex[mt_rand(0, $max)]; }
			return $string;
		}
		/**
		 * Random password string generator
		 */
		function random_ascii_string($len)
		{
			$string = "";
			$max = strlen($this->ascii)-1;
			while($len-->0) { $string .= $this->ascii[mt_rand(0, $max)]; }
			return $string;
		}
	}
?>
