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
$databaseConfig = configLazy(__DIR__ . '/config/config.php');

if (file_exists('custom.css')) : define('CUSTOMCSS', 'true'); else : define('CUSTOMCSS', 'false'); endif;
$notifyExplode = explode("-", NOTIFYEFFECT);
define('FAIL_LOG', 'loginLog.json');
@date_default_timezone_set(TIMEZONE);
function guestHash($start, $end)
{
    $ip   = $_SERVER['REMOTE_ADDR'];
    $ip    = md5($ip);
    return substr($ip, $start, $end);
}

define('GUEST_HASH', "guest-".guestHash(0, 5));
$group = new User();
$group = $group->role;

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
    public $error = "";
    // progress log
    public $info_log = array();
    // Information logging
    public function info($string)
    {
        $this->info_log[] = $string;
    }
    // error log
    public $error_log = array();
    // Error logging
    public function error($string)
    {
        $this->error_log[] = $string;
    }
    // all possible values for a hexadecimal number
    public $hex = "0123456789abcdef";
    // all possible values for an ascii password, skewed a bit so the number to letter ratio is closer to 1:1
    public $ascii = "0a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6A7B8C9D0E1F2G3H4I5J6K7L8M9N0O1P2Q3R4S5T6U7V8W9X0Y1Z23456789";
    // the regular expression for email matching (see http://www.regular-expressions.info/email.html)
    const emailregexp = "/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/";
    // the regular expression for SHA1 hash matching
    const sha1regexp = "/[0123456789abcdef]{40,40}/";
    // this will tell us whether the client that requested the page is authenticated or not.
    public $authenticated = false;
    // the guest user name
    const GUEST_USER  = GUEST_HASH;
    // this will contain the user name for the user doing the page request
    public $username = User::GUEST_USER;
    // if this is a properly logged in user, this will contain the data directory location for this user
    public $userdir = false;
    // the user's email address, if logged in.
    public $email = "";
    public $adminEmail = "";
    // the user's role in the system
    public $role = "";
    public $group = "";
    // global database handle
    public $database = false;

    //EMAIL SHIT
    public function startEmail($email, $username, $subject, $body)
    {
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
        if (!$mail->send()) {
            $this->error('Mailer Error: ' . $mail->ErrorInfo);
            $this->error = 'Mailer Error: ' . $mail->ErrorInfo;
        } else {
            $this->info('E-Mail sent!');
        }
    }

    // class object constructor
    public function __construct($registration_callback=false)
    {
        // session management comes first. Warnings are repressed with @ because it will warn if something else already called session_start()
        @session_start();
        if (!isset($_COOKIE['Organizr'])) {
            if (empty($_SESSION["username"]) || empty($_SESSION["token"])) {
                $this->resetSession();
            }
        } else {
            $_SESSION["username"] = $_COOKIE['OrganizrU'];
        }
        // file location for the user database
        $dbfile = DATABASE_LOCATION.'users.db';
        // do we need to build a new database?
        $rebuild = false;
        if (!file_exists($dbfile)) {
            $rebuild = true;
        }
            // bind the database handler
            $this->database = new PDO("sqlite:" . $dbfile);
            // If we need to rebuild, the file will have been automatically made by the PDO call,
            // but we'll still need to define the user table before we can use the database.
            if ($rebuild) {
                $this->rebuild_database($dbfile);
            }
            // finally, process the page request.
            $this->process($registration_callback);
    }
        // this function rebuilds the database if there is no database to work with yet
        public function rebuild_database($dbfile)
        {
            $this->info("creating/rebuilding database as ".$dbfile);
            createSQLiteDB();
            $this->database = new PDO("sqlite:" . $dbfile);
        }
        // process a page request
        public function process(&$registration_callback=false)
        {
            $this->database->beginTransaction();
            if (isset($_POST["op"])) {
                $operation = $_POST["op"];
                // logging in or out, and dropping your registration, may change authentication status
                if ($operation == "login") {
                    $this->authenticated = $this->login();
                }
                // logout and unregister will unset authentication if successful
                elseif ($operation == "logout") {
                    $this->authenticated = !$this->logout();
                } elseif ($operation == "unregister") {
                    $this->authenticated = !$this->unregister();
                }
                // anything else won't change authentication status.
                elseif ($operation == "register") {
                    $this->register($registration_callback);
                } elseif ($operation == "update") {
                    $this->update();
                } elseif ($operation == "invite") {
                    $this->invite();
                } elseif ($operation == "deleteinvite") {
                    $this->deleteInvite();
                }
                // we only allow password resetting if we can send notification mails
                elseif ($operation == "reset" && User::use_mail) {
                    $this->reset_password();
                }
            }
            // if the previous operations didn't authorise the current user,
            // see if they're already marked as authorised in the database.
            if (!$this->authenticated) {
                $username = $_SESSION["username"];
                if ($username != User::GUEST_USER) {
                    $this->authenticated = $this->authenticate_user($username, "");
                    if ($this->authenticated) {
                        $this->mark_user_active($username);
                    }
                }
            }
            // at this point we can make some globals available.
            $this->username = $_SESSION["username"];
            $this->userdir = ($this->username !=User::GUEST_USER? USER_HOME . $this->username : false);
            $this->email = $this->get_user_email($this->username);
            $this->adminEmail = $this->get_admin_email();
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
        public function login()
        {
            // get relevant values
            $username = $_POST["username"];
            $sha1 = $_POST["sha1"];
            $password = $_POST["password"];
            $rememberMe = $_POST["rememberMe"];
            // step 1: someone could have bypassed the javascript validation, so validate again.
            if (!$this->validate_user_name($username)) {
                $this->info("<strong>log in error:</strong> user name did not pass validation");
                return false;
            }
            if (preg_match(User::sha1regexp, $sha1)==0) {
                $this->info("<strong>log in error:</strong> password did not pass validation");
                return false;
            }
            // step 2: if validation passed, log the user in
            return $this->login_user($username, $sha1, $rememberMe == "true", $password);
        }
        /**
        * Called when the requested POST operation is "logout"
        */
        public function logout()
        {
            // get relevant value
            $username = $_POST["username"];
            // step 1: validate the user name.
            if (!$this->validate_user_name($username)) {
                $this->info("<strong>log in error:</strong> user name did not pass validation");
                return false;
            }
            // step 2: if validation passed, log the user out
            return $this->logout_user($username);
        }
        /**
        * Users should always have the option to unregister
        */
        public function unregister()
        {
            // get relevant value
            $username = $_POST["username"];
            // step 1: validate the user name.
            if (!$this->validate_user_name($username)) {
                $this->info("<strong>unregistration error:</strong> user name did not pass validation");
                return false;
            }
            // step 2: if validation passed, drop the user from the system
            return $this->unregister_user($username);
        }
        /**
        * Called when the requested POST operation is "register"
        */
        public function register(&$registration_callback=false)
        {
            // get relevant values
            $username = $_POST["username"];
            $email = $_POST["email"];
            $sha1 = $_POST["sha1"];
            $settings = $_POST["settings"];
            // step 1: someone could have bypassed the javascript validation, so validate again.
            if (!$this->validate_user_name($username)) {
                $this->info("<strong>registration error:</strong> user name did not pass validation");
                return false;
            }
            if (preg_match(User::emailregexp, $email)==0) {
                $this->info("<strong>registration error:</strong> email address did not pass validation");
                return false;
            }
            if (preg_match(User::sha1regexp, $sha1)==0) {
                $this->info("<strong>registration error:</strong> password did not pass validation");
                return false;
            }
            // step 2: if validation passed, register user
            $registered = $this->register_user($username, $email, $sha1, $registration_callback, $settings);
            if ($registered && User::use_mail) {
                // send email notification
                $subject = "Welcome to ".DOMAIN;
                $language = new setLanguage;
                $domain = getServerPath();
                $body = orgEmail(
                    $header = $language->translate('EMAIL_NEWUSER_HEADER'),
                    $title = $language->translate('EMAIL_NEWUSER_TITLE'),
                    $user = $username,
                    $mainMessage =$language->translate('EMAIL_NEWUSER_MESSAGE'),
                    $button = $language->translate('EMAIL_NEWUSER_BUTTON'),
                    $buttonURL = $domain,
                    $subTitle = $language->translate('EMAIL_NEWUSER_SUBTITLE'),
                    $subMessage = $language->translate('EMAIL_NEWUSER_SUBMESSAGE')
                );
                $this->startEmail($email, $username, $subject, $body);
            }
            return $registered;
        }
        /**
        * Called when the requested POST operation is "update"
        */
        public function update()
        {
            // get relevant values
            @$username = trim($_POST["username"]);
            @$email = trim($_POST["email"]);
            @$sha1 = trim($_POST["sha1"]);
            @$role = trim($_POST["role"]);
            // step 1: someone could have bypassed the javascript validation, so validate again.
            if ($email !="" && preg_match(User::emailregexp, $email)==0) {
                $this->info("<strong>registration error:</strong> email address did not pass validation");
                return false;
            }
            if ($sha1 !="" && preg_match(User::sha1regexp, $sha1)==0) {
                $this->info("<strong>registration error:</strong> password did not pass validation");
                return false;
            }
            // step 2: if validation passed, update the user's information
            return $this->update_user($username, $email, $sha1, $role);
        }
        /**
        * Called when the requested POST operation is "invite"
        */
        public function invite()
        {
            // get relevant values
            @$username = trim($_POST["username"]);
            @$email = trim($_POST["email"]);
            @$server = trim($_POST["server"]);
            // step 1: someone could have bypassed the javascript validation, so validate again.
            if ($email !="" && preg_match(User::emailregexp, $email)==0) {
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
        public function reset_password()
        {
            // get the email for which we should reset
            $email = $_POST["email"];
            // step 1: someone could have bypassed the javascript validation, so validate again.
            if (preg_match(User::emailregexp, $email)==0) {
                $this->info("email address did not pass validation");
                return false;
            }
            // step 2: if validation passed, see if there is a matching user, and reset the password if there is
            $newpassword = $this->random_ascii_string(20);
            $sha1 = sha1($newpassword);
            $query = "SELECT username, token FROM users WHERE email = '$email'";
            $username = "";
            $token = "";
            foreach ($this->database->query($query) as $data) {
                $username = $data["username"];
                $token = $data["token"];
                break;
            }
            // step 2a: if there was no user to reset a password for, stop.
            if ($username == "" || $token == "") {
                return false;
            }
            // step 2b: if there was a user to reset a password for, reset it.
            $dbpassword = $this->token_hash_password($username, $sha1, $token);
            $update = "UPDATE users SET password = '$dbpassword' WHERE email= '$email'";
            writeLog("success", "$username has reset their password");
            $this->database->exec($update);
            //$this->info("Email has been sent with new password");
            // step 3: notify the user of the new password
            $subject = DOMAIN . " Password Reset";
            $language = new setLanguage;
            $domain = getServerPath();
            $body = orgEmail(
                $header = $language->translate('EMAIL_RESET_HEADER'),
                $title = $language->translate('EMAIL_RESET_TITLE'),
                $user = $username,
                $mainMessage =$language->translate('EMAIL_RESET_MESSAGE')."<br/>".$newpassword,
                $button = $language->translate('EMAIL_RESET_BUTTON'),
                $buttonURL = $domain,
                $subTitle = $language->translate('EMAIL_RESET_SUBTITLE'),
                $subMessage = $language->translate('EMAIL_RESET_SUBMESSAGE')
            );
            $this->startEmail($email, $username, $subject, $body);
        }
        // ------------------
        // specific functions
        // ------------------
        // session management: set session values
        public function setSession($username, $token)
        {
            $_SESSION["username"]=$username;
            $_SESSION["token"]=$token;
        }
        // session management: reset session values
        public function resetSession()
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
        public function validate_user_name($username)
        {
            $cleaned = $this->clean_SQLite_string($username);
            $validated = ($cleaned != "" && $cleaned==$username);
            if (!$validated) {
                $this->error = "user name did not pass validation.";
                $this->error("user name did not pass validation.");
            }
            return $validated;
        }
        /**
        * Clean strings for SQL insertion as string in SQLite (single quote enclosed).
        * Note that if the cleaning changes the string, this system won't insert.
        * The validate_user_name() function will flag this as a validation failure and
        * the database operation is never carried out.
        */
        public function clean_SQLite_string($string)
        {
            $search = array("'", "\\", ";");
            $replace = array('', '', '');
            return trim(str_replace($search, $replace, $string));
        }
        /**
        * Verify that the given username is allowed
        * to perform the given operation.
        */
        public function authenticate_user($username, $operation)
        {
            // actually logged in?
            if ($this->is_user_active($username)===false) {
                return false;
            }

            // logged in, but do the tokens match?
            $token = $this->get_user_token($username);
            if (MULTIPLELOGIN == "false") {
                if (isset($_COOKIE["Organizr"])) {
                    if ($_COOKIE["Organizr"] == $token) {
                        return true;
                    } else {
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
                } else {
                    if ($token != $_SESSION["token"]) {
                        $this->error("token mismatch for $username");
                        return false;
                    }
                    // active, using the correct token -> authenticated
                    setcookie("cookiePassword", COOKIEPASSWORD, time() + (86400 * 7), "/", DOMAIN);
                    return true;
                }
            } else {
                setcookie("cookiePassword", COOKIEPASSWORD, time() + (86400 * 7), "/", DOMAIN);
                return true;
            }
        }
        /**
        * Unicode friendly(ish) version of strtolower
        * see: http://ca3.php.net/manual/en/function.strtolower.php#91805
        */
        public function strtolower_utf8($string)
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
        public function homogenise_username($string)
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
        public function register_user($username, $email, $sha1, &$registration_callback = false, $settings)
        {
            $username = strtolower($username);
            $dbpassword = $this->token_hash_password($username, $sha1, "");
            if ($dbpassword==$sha1) {
                die("password hashing is not implemented.");
            }
            $newRole = "admin";
            $queryAdmin = "SELECT username FROM users";
            foreach ($this->database->query($queryAdmin) as $data) {
                $newRole = "user";
            }
            // Does user already exist? (see notes on safe reporting)
            if (User::unsafe_reporting) {
                $query = "SELECT username FROM users WHERE username LIKE '$username' COLLATE NOCASE";
                foreach ($this->database->query($query) as $data) {
                    $this->info("user account for $username not created.");
                    $this->error = "this user name is already being used by someone else.";
                    $this->error("this user name is already being used by someone else.");
                    return false;
                }
            } else {
                $query = "SELECT username FROM users";
                $usernames = array();
                foreach ($this->database->query($query) as $data) {
                    $usernames[] = $this->homogenise_username($data["username"]);
                }
                if (in_array($this->homogenise_username($username), $usernames)) {
                    //$this->info("user account for $username not created.");
                    $this->error = "<strong>$username</strong> is not allowed, because it is too similar to other user names.";
                    $this->error("<strong>$username</strong> is not allowed, because it is too similar to other user names.");
                    return false;
                }
            }
            // Is email address already in use? (see notes on safe reporting)
            if (isset($email) && $email) {
                $query = "SELECT * FROM users WHERE email = '$email' COLLATE NOCASE";
                foreach ($this->database->query($query) as $data) {
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
            foreach ($this->database->query($query) as $data) {
                $this->info("created user account for $username");
                writeLog("success", "$username has just registered");
                $this->update_user_token($username, $sha1, false);
                // make the user's data directory
                $dir = USER_HOME . $username;
                if (!mkdir($dir, 0760, true)) {
                    $this->error("could not make user directory $dir");
                    return false;
                }
                //$this->info("created user directory $dir");
                // if there is a callback, call it
                if ($registration_callback !== false) {
                    $registration_callback($username, $email, $dir);
                }
                if ($settings !== 'true' && $settings !== true) {
                    $this->login_user($username, $sha1, true, '', false);
                }
                return true;
            }
            $this->error = "unknown database error occured.";
            $this->error("unknown database error occured.");
            return false;
        }
        /**
        * Log a user in
        */
        public function login_user($username, $sha1, $remember, $password, $surface = true)
        {
            $username = strtolower($username);

            $buildLog = function ($username, $authType) {
                if (file_exists(FAIL_LOG)) {
                    $getFailLog = str_replace("\r\ndate", "date", file_get_contents(FAIL_LOG));
                    $gotFailLog = json_decode($getFailLog, true);
                }

                $failLogEntryFirst = array('logType' => 'login_log', 'auth' => array(array('date' => date("Y-m-d H:i:s"), 'username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'], 'auth_type' => $authType)));
                $failLogEntry = array('date' => date("Y-m-d H:i:s"), 'username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'], 'auth_type' => $authType);
                if (isset($gotFailLog)) {
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
                    foreach ($this->database->query($query) as $data) {
                        if (password_verify($password, $data["password"])) {
                            // Better
                            $authSuccess = true;
                        } else {
                            // Legacy - Less Secure
                            $dbpassword = $this->token_hash_password($username, $sha1, $this->get_user_token($username));
                            if ($dbpassword==$data["password"]) {
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
                foreach ($this->database->query($query) as $data) {
                    $userExists = true;
                    break;
                }

                if ($userExists) {
                    // authentication passed - 1) mark active and update token
                    $this->mark_user_active($username);
                    $this->setSession($username, $this->update_user_token($username, $sha1, false));
                    // authentication passed - 2) signal authenticated
                    if ($remember == "true") {
                        setcookie("Organizr", $this->get_user_token($username), time() + (86400 * 7), "/", DOMAIN);
                        setcookie("OrganizrU", $username, time() + (86400 * 7), "/", DOMAIN);
                    }
                    $this->info("Welcome $username");
                    file_put_contents(FAIL_LOG, $buildLog($username, "good_auth"));
                    chmod(FAIL_LOG, 0660);
                    setcookie("cookiePassword", COOKIEPASSWORD, time() + (86400 * 7), "/", DOMAIN);
                    writeLog("success", "$username has logged in");
                    return true;
                } elseif (AUTHBACKENDCREATE !== 'false' && $surface) {
                    // Create User
                    $falseByRef = false;
                    $this->register_user($username, (is_array($authSuccess) && isset($authSuccess['email']) ? $authSuccess['email'] : ''), $sha1, $falseByRef, !$remember);
                } else {
                    // authentication failed
                    //$this->info("Successful Backend Auth, No User in DB, Create Set to False");
                    file_put_contents(FAIL_LOG, $buildLog($username, "bad_auth"));
                    chmod(FAIL_LOG, 0660);
                    if (User::unsafe_reporting) {
                        $this->error = "Successful Backend Auth, $username not in DB, Create Set to False.";
                        $this->error("Successful Backend Auth, $username not in DB, Create Set to False.");
                    } else {
                        $this->error = "Not permitted to login as this user, please contact an administrator.";
                        $this->error("Not permitted to login as this user, please contact an administrator");
                    }
                    return false;
                }
            } elseif (!$authSuccess) {
                // authentication failed
                //$this->info("password mismatch for $username");
                writeLog("error", "$username tried to sign-in with the wrong password");
                file_put_contents(FAIL_LOG, $buildLog($username, "bad_auth"));
                chmod(FAIL_LOG, 0660);
                if (User::unsafe_reporting) {
                    $this->error = "incorrect password for $username.";
                    $this->error("incorrect password for $username.");
                } else {
                    $this->error = "the specified username/password combination is incorrect.";
                    $this->error("the specified username/password combination is incorrect.");
                }
                return false;
            } else {
                // authentication could not take place
                //$this->info("there was no user $username in the database");
                file_put_contents(FAIL_LOG, $buildLog($username, "bad_auth"));
                chmod(FAIL_LOG, 0660);
                if (User::unsafe_reporting) {
                    $this->error = "user $username is unknown.";
                    $this->error("user $username is unknown.");
                } else {
                    $this->error = "you either did not correctly input your username, or password (... or both).";
                    $this->error("you either did not correctly input your username, or password (... or both).");
                }
                return false;
            }
        }
        /**
        * Update a user's information
        */
        public function update_user($username, $email, $sha1, $role)
        {
            if ($email !="") {
                $update = "UPDATE users SET email = '$email' WHERE username = '$username' COLLATE NOCASE";
                $this->database->exec($update);
            }
            if ($role !="") {
                $update = "UPDATE users SET role = '$role' WHERE username = '$username' COLLATE NOCASE";
                $this->database->exec($update);
            }
            if ($sha1 !="") {
                $dbpassword = $this->token_hash_password($username, $sha1, $this->get_user_token($username));
                $update = "UPDATE users SET password = '$dbpassword' WHERE username = '$username'";
                $this->database->exec($update);
            }
            writeLog("success", "information for $username has been updated");
            $this->info("updated the information for <strong>$username</strong>");
        }
        /**
        * Drop a invite from the system
        */
        public function deleteInvite()
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
        public function invite_user($username = "none", $email, $server)
        {
            //lang shit
            $language = new setLanguage;
            $domain = getServerPath();
            $topImage = $domain."images/organizr-logo-h.png";
            $uServer = strtoupper($server);
            $now = date("Y-m-d H:i:s");
            $inviteCode = randomCode(6);
            $username = (!empty($username) ? $username : strtoupper($server) . " User");
            $link = getServerPath()."?inviteCode=".$inviteCode;
            if ($email !="") {
                $insert = "INSERT INTO invites (username, email, code, valid, date) ";
                $insert .= "VALUES ('".strtolower($username)."', '$email', '$inviteCode', 'Yes', '$now') ";
                $this->database->exec($insert);
            }
            writeLog("success", "$email has been invited to the $server server");
            $this->info("$email has been invited to the $server server");
            if ($insert && User::use_mail) {
                // send email notification
                $subject = DOMAIN . " $uServer ".$language->translate('INVITE_CODE');
                $body = orgEmail(
                    $header = explosion($language->translate('EMAIL_INVITE_HEADER'), 0)." ".$uServer." ".explosion($language->translate('EMAIL_INVITE_HEADER'), 1),
                    $title = $language->translate('EMAIL_INVITE_TITLE'),
                    $user = $username,
                    $mainMessage = explosion($language->translate('EMAIL_INVITE_MESSAGE'), 0)." ".$uServer." ".explosion($language->translate('EMAIL_INVITE_MESSAGE'), 1)." ".$inviteCode,
                    $button = explosion($language->translate('EMAIL_INVITE_BUTTON'), 0)." ".$uServer." ".explosion($language->translate('EMAIL_INVITE_BUTTON'), 1),
                    $buttonURL = $link,
                    $subTitle = $language->translate('EMAIL_INVITE_SUBTITLE'),
                    $subMessage = explosion($language->translate('EMAIL_INVITE_SUBMESSAGE'), 0)." <a href='".$domain."?inviteCode'>".$domain."</a> ".explosion($language->translate('EMAIL_INVITE_SUBMESSAGE'), 1)
                );
                $this->startEmail($email, $username, $subject, $body);
            }
        }
        /**
        * Log a user out.
        */
        public function logout_user($username)
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
        public function unregister_user($username)
        {
            $delete = "DELETE FROM users WHERE username = '$username' COLLATE NOCASE";
            $this->database->exec($delete);
            $this->info("<strong>$username</strong> has been kicked out of Organizr");
            //$this->resetSession();
            $dir = USER_HOME . $username;
            if (!rmdir($dir)) {
                $this->error("could not delete user directory $dir");
            }
            $this->info("and we deleted user directory $dir");
            writeLog("success", "$username has been deleted");
            return true;
        }
        /**
        * The incoming password will already be a sha1 print (40 bytes) long,
        * but for the database we want it to be hased as sha256 (using 64 bytes).
        */
        public function token_hash_password($username, $sha1, $token)
        {
            return hash("sha256", ($this->database->query('SELECT username FROM users WHERE username = \''.$username.'\' COLLATE NOCASE')->fetch()['username']).$sha1.$token);
        }
        /**
        * Get a user's email address
        */
        public function get_user_email($username)
        {
            if ($username && $username !="" && $username !=User::GUEST_USER) {
                $query = "SELECT email FROM users WHERE username = '$username' COLLATE NOCASE";
                foreach ($this->database->query($query) as $data) {
                    return $data["email"];
                }
            }
            return "";
        }
    public function get_admin_email()
    {
        $query = "SELECT email FROM users WHERE role = 'admin' COLLATE NOCASE LIMIT 1";
        foreach ($this->database->query($query) as $data) {
            return $data["email"];
        }
        return "";
    }
        /**
        * Get a user's role
        */
        public function get_user_role($username)
        {
            if ($username && $username !="" && $username !=User::GUEST_USER) {
                $query = "SELECT role FROM users WHERE username = '$username' COLLATE NOCASE";
                foreach ($this->database->query($query) as $data) {
                    return $data["role"];
                }
            }
            return "guest";
        }

        /* function get_user_group($username)
        {
        if($username && $username !="" && $username !=User::GUEST_USER) {
        $query = "SELECT group FROM users WHERE username = '$username' COLLATE NOCASE";
        foreach($this->database->query($query) as $data)  {
        return $data["group"];
    }
}
return User::GUEST_USER;
}*/
/**
* Get the user token
*/
public function get_user_token($username)
{
    $query = "SELECT token FROM users WHERE username = '$username' COLLATE NOCASE";
    foreach ($this->database->query($query) as $data) {
        return $data["token"];
    }
    return false;
}
/**
* Update the user's token and password upon successful login
*/
public function update_user_token($username, $sha1, $noMsg)
{
    // update the user's token
    $token = $this->random_hex_string(32);
    $update = "UPDATE users SET token = '$token' WHERE username = '$username' COLLATE NOCASE";
    $this->database->exec($update);
    // update the user's password
    $newpassword = $this->token_hash_password($username, $sha1, $token);
    $update = "UPDATE users SET password = '$newpassword' WHERE username = '$username' COLLATE NOCASE";
    $this->database->exec($update);
    if ($noMsg == "false") {
        $this->info("token and password updated for <strong>$username</strong>");
    }
    return $token;
}
/**
* Mark a user as active.
*/
public function mark_user_active($username)
{
    $update = "UPDATE users SET active = 'true', last = '" . time() . "' WHERE username = '$username' COLLATE NOCASE";
    $this->database->exec($update);
    //$this->info("$username has been marked currently active.");
    return true;
}
/**
* Check if user can be considered active
*/
public function is_user_active($username)
{
    $last = 0;
    $active = "false";
    $query = "SELECT last, active FROM users WHERE username = '$username' COLLATE NOCASE";
    foreach ($this->database->query($query) as $data) {
        $last = intval($data["last"]);
        $active = $data["active"];
        break;
    }
    if ($active=="true") {
        $diff = time() - $last;
        if ($diff >= User::time_out) {
            $this->logout_user($username);
            $this->error("$username was active but timed out (timeout set at " . User::time_out . " seconds, difference was $diff seconds)");
            return false;
        }
        //$this->info("$username is active");
        return true;
    }
    $this->error("<strong>$username</strong> is not active");
    $this->resetSession();
    return false;
}
/**
* Random hex string generator
*/
public function random_hex_string($len)
{
    $string = "";
    $max = strlen($this->hex)-1;
    while ($len-->0) {
        $string .= $this->hex[mt_rand(0, $max)];
    }
    return $string;
}
/**
* Random password string generator
*/
public function random_ascii_string($len)
{
    $string = "";
    $max = strlen($this->ascii)-1;
    while ($len-->0) {
        $string .= $this->ascii[mt_rand(0, $max)];
    }
    return $string;
}
}
