<?php
	/**
	 *	A framework for simple user authentication.
	 *
	 *	Users are recorded using {username, password, token} triplets.
	 *	Whenever a user logs in successfully, his or her database
	 *	entry is assigned a new random token,  which is used in
	 * salting subsequent password checks.
	 */

	class User
	{
		// =======================================================================
		// IMPORTANT VALUES THAT YOU *NEED* TO CHANGE FOR THIS TO BE SECURE
		// =======================================================================

			// Keeping this location on ./... means that it will be publically visible to all,
			// and you need to use htaccess rules or some such to ensure no one
			// grabs your user's data.

			const USER_HOME = "../users/";

			// In order for users to be notified by email of certain things, set this to true.
			// Note that the server you run this on should have sendmail in order for
			// notification emails to work. Also note that password resetting doesn't work
			// unless mail notification is turned on.

			const use_mail = false;

			// This value should point to a directory that is not available to web users.
			// If your documents are in ./public_html, for instance., then put database
			// in something like ./database - that way, you don't have to rely on
			// htaccess rules or the likes, because it's simply impossible to get to the
			// database from a public, or private, URL.
			//
			// By default it's set to the stupidly dangerous and publically accessible same
			// base dir as your web page. So change it, because people are going to try
			// to download your database file. And succeed.

			const DATABASE_LOCATION = "../";

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
			const DATABASE_NAME = "users";

			// this is the session timeout. If someone hasn't performed any page requests
			// in [timeout] seconds, they're considered logged out.
			const time_out = 7200;

			// You'll probably want to change this to something sensible. If your site is
			// www.sockmonkey.com, then you want this to be "sockmonkey.com"
			const DOMAIN_NAME = "localhost";

			// This is going to be the "from" address
			const MAILER_NAME = "noreply@localhost";

			// if you want people to be able to reply to a real address, override
			// this variable to "yourmail@somedomain.ext" here.
			const MAILER_REPLYTO = "noreply@localhost";

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
		const GUEST_USER  = "guest user";

		// this will contain the user name for the user doing the page request
		var $username = User::GUEST_USER;

		// if this is a properly logged in user, this will contain the data directory location for this user
		var $userdir = false;

		// the user's email address, if logged in.
		var $email = "";

		// the user's role in the system
		var $role = "";

		// global database handle
		var $database = false;

		// class object constructor
		function __construct($registration_callback=false)
		{
			// session management comes first. Warnings are repressed with @ because it will warn if something else already called session_start()
			@session_start();
			if (empty($_SESSION["username"]) || empty($_SESSION["token"])) $this->resetSession();

			// file location for the user database
			$dbfile = User::DATABASE_LOCATION  . User::DATABASE_NAME . ".db";

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
			$this->info("rebuilding database as ".$dbfile);
			$this->database->beginTransaction();
			$create = "CREATE TABLE users (username TEXT UNIQUE, password TEXT, email TEXT UNIQUE, token TEXT, role TEXT, active TEXT, last TEXT);";
			$this->database->exec($create);
			$this->database->commit();
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
			$this->userdir = ($this->username !=User::GUEST_USER? User::USER_HOME . $this->username : false);
			$this->email = $this->get_user_email($this->username);
			$this->role = $this->get_user_role($this->username);

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
			// step 1: someone could have bypassed the javascript validation, so validate again.
			if(!$this->validate_user_name($username)) {
				$this->info("log in error: user name did not pass validation");
				return false; }
			if(preg_match(User::sha1regexp, $sha1)==0) {
				$this->info("log in error: password did not pass validation");
				return false; }
			// step 2: if validation passed, log the user in
			return $this->login_user($username, $sha1);
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
				$this->info("log in error: user name did not pass validation");
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
				$this->info("unregistration error: user name did not pass validation");
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
			// step 1: someone could have bypassed the javascript validation, so validate again.
			if(!$this->validate_user_name($username)) {
				$this->info("registration error: user name did not pass validation");
				return false; }
			if(preg_match(User::emailregexp, $email)==0) {
				$this->info("registration error: email address did not pass validation");
				return false; }
			if(preg_match(User::sha1regexp, $sha1)==0) {
				$this->info("registration error: password did not pass validation");
				return false; }
			// step 2: if validation passed, register user
			$registered = $this->register_user($username, $email, $sha1, $registration_callback);
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
				mail($email, $subject, $body, $headers);
			}

			return $registered;
		}

		/**
		 * Called when the requested POST operation is "update"
		 */
		function update()
		{
			// get relevant values
			$email = trim($_POST["email"]);
			$sha1 = trim($_POST["sha1"]);
            $role = trim($_POST["role"]);
			// step 1: someone could have bypassed the javascript validation, so validate again.
			if($email !="" && preg_match(User::emailregexp, $email)==0) {
				$this->info("registration error: email address did not pass validation");
				return false; }
			if($sha1 !="" && preg_match(User::sha1regexp, $sha1)==0) {
				$this->info("registration error: password did not pass validation");
				return false; }
			// step 2: if validation passed, update the user's information
			return $this->update_user($email, $sha1, $role);
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
				$this->info("registration error: email address did not pass validation");
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
			$this->database->exec($update);

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
			mail($email, $subject, $body, $headers);
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
			if(!$validated) { $this->error = "user name did not pass validation."; }
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
			if($token != $_SESSION["token"]) {
				$this->error("token mismatch for $username");
				return false; }

			// active, using the correct token -> authenticated
			return true;
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
		function register_user($username, $email, $sha1, &$registration_callback = false)
		{
			$dbpassword = $this->token_hash_password($username, $sha1, "");
			if($dbpassword==$sha1) die("password hashing is not implemented.");
            $newRole = "admin"; 
            $queryAdmin = "SELECT username FROM users";
            foreach($this->database->query($queryAdmin) as $data) {
                $newRole = "user";
            }

			// Does user already exist? (see notes on safe reporting)
			if(User::unsafe_reporting) {
				$query = "SELECT username FROM users WHERE username LIKE '$username'";
				foreach($this->database->query($query) as $data) {
					$this->info("user account for $username not created.");
					$this->error = "this user name is already being used by someone else.";
					return false; }}
			else{	$query = "SELECT username FROM users";
				$usernames = array();
				foreach($this->database->query($query) as $data) { $usernames[] = $this->homogenise_username($data["username"]); }
				if(in_array($this->homogenise_username($username), $usernames)) {
					$this->info("user account for $username not created.");
					$this->error = "this user name is not allowed, because it is too similar to other user names.";
					return false; }}

			// Is email address already in use? (see notes on safe reporting)
			$query = "SELECT * FROM users WHERE email = '$email'";
			foreach($this->database->query($query) as $data) {
				$this->info("user account for $username not created.");
				$this->error = "this email address is already in use by someone else.";
				return false; }

			// This user can be registered
			$insert = "INSERT INTO users (username, email, password, token, role, active, last) ";
			$insert .= "VALUES ('$username', '$email', '$dbpassword', '', '$newRole', 'true', '" . time() . "') ";
			$this->database->exec($insert);
			$query = "SELECT * FROM users WHERE username = '$username'";
			foreach($this->database->query($query) as $data) {
				$this->info("created user account for $username");
				$this->update_user_token($username, $sha1);
				// make the user's data directory
				$dir = User::USER_HOME . $username;
				if(!mkdir($dir, 0760, true)) { $this->error("could not make user directory $dir"); return false; }
				$this->info("created user directory $dir");
				// if there is a callback, call it
				if($registration_callback !== false) { $registration_callback($username, $email, $dir); }
				return true; }
			$this->error = "unknown database error occured.";
			return false;
		}

		/**
		 * Log a user in
		 */
		function login_user($username, $sha1)
		{
			// transform sha1 into real password
			$dbpassword = $this->token_hash_password($username, $sha1, $this->get_user_token($username));
			if($dbpassword==$sha1) {
				$this->info("password hashing is not implemented.");
				return false; }

			// perform the authentication step
			$query = "SELECT password FROM users WHERE username = '$username'";
			foreach($this->database->query($query) as $data) {
				if($dbpassword==$data["password"]) {
					// authentication passed - 1) mark active and update token
					$this->mark_user_active($username);
					$this->setSession($username, $this->update_user_token($username, $sha1));
					// authentication passed - 2) signal authenticated
					return true; }
				// authentication failed
				$this->info("password mismatch for $username");
				if(User::unsafe_reporting) { $this->error = "incorrect password for $username."; }
				else { $this->error = "the specified username/password combination is incorrect."; }
				return false; }

			// authentication could not take place
			$this->info("there was no user $username in the database");
			if(User::unsafe_reporting) { $this->error = "user $username is unknown."; }
			else { $this->error = "you either did not correctly input your username, or password (... or both)."; }
			return false;
		}

		/**
		 * Update a user's information
		 */
		function update_user($email, $sha1, $role)
		{
			$username = $_SESSION["username"];
			if($email !="") {
				$update = "UPDATE users SET email = '$email' WHERE username = '$username'";
				$this->database->exec($update); }
            if($role !="") {
				$update = "UPDATE users SET role = '$role' WHERE username = '$username'";
				$this->database->exec($update); }
			if($sha1 !="") {
				$dbpassword = $this->token_hash_password($username, $sha1, $this->get_user_token($username));
				$update = "UPDATE users SET password = '$dbpassword' WHERE username = '$username'";
				$this->database->exec($update); }
			$this->info("update the information for $username");
		}

		/**
		 * Log a user out.
		 */
		function logout_user($username)
		{
			$update = "UPDATE users SET active = 'false' WHERE username = '$username'";
			$this->database->exec($update);
			$this->resetSession();
			$this->info("logged $username out");
			return true;
		}

		/**
		 * Drop a user from the system
		 */
		function unregister_user($username)
		{
			$delete = "DELETE FROM users WHERE username = '$username'";
			$this->database->exec($delete);
			$this->info("removed $username from the system");
			//$this->resetSession();
			return true;
		}

		/**
		 * The incoming password will already be a sha1 print (40 bytes) long,
		 * but for the database we want it to be hased as sha256 (using 64 bytes).
		 */
		function token_hash_password($username, $sha1, $token)
		{
			return hash("sha256", $username . $sha1 . $token);
		}

		/**
		 * Get a user's email address
		 */
		function get_user_email($username)
		{
			if($username && $username !="" && $username !=User::GUEST_USER) {
				$query = "SELECT email FROM users WHERE username = '$username'";
				foreach($this->database->query($query) as $data) { return $data["email"]; }}
			return "";
		}

		/**
		 * Get a user's role
		 */
		function get_user_role($username)
		{
			if($username && $username !="" && $username !=User::GUEST_USER) {
				$query = "SELECT role FROM users WHERE username = '$username'";
				foreach($this->database->query($query) as $data) { return $data["role"]; }}
			return User::GUEST_USER;
		}

		/**
		 * Get the user token
		 */
		function get_user_token($username)
		{
			$query = "SELECT token FROM users WHERE username = '$username'";
			foreach($this->database->query($query) as $data) { return $data["token"]; }
			return false;
		}

		/**
		 * Update the user's token and password upon successful login
		 */
		function update_user_token($username, $sha1)
		{
			// update the user's token
			$token = $this->random_hex_string(32);
			$update = "UPDATE users SET token = '$token' WHERE username = '$username'";
			$this->database->exec($update);

			// update the user's password
			$newpassword = $this->token_hash_password($username, $sha1, $token);
			$update = "UPDATE users SET password = '$newpassword' WHERE username = '$username'";
			$this->database->exec($update);
			$this->info("updated token and password for $username");

			return $token;
		}

		/**
		 * Mark a user as active.
		 */
		function mark_user_active($username)
		{
			$update = "UPDATE users SET active = 'true', last = '" . time() . "' WHERE username = '$username'";
			$this->database->exec($update);
			$this->info("$username has been marked currently active.");
			return true;
		}

		/**
		 * Check if user can be considered active
		 */
		function is_user_active($username)
		{
			$last = 0;
			$active = "false";
			$query = "SELECT last, active FROM users WHERE username = '$username'";
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
				$this->info("$username is active");
				return true; }

			$this->error("$username is not active");
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