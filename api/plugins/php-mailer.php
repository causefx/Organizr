<?php

// PLUGIN INFORMATION
$GLOBALS['plugins'][]['PHP Mailer'] = array( // Plugin Name
    'name'=>'PHP Mailer', // Plugin Name
	'author'=>'PHP Mailer', // Who wrote the plugin
    'category'=>'Mail', // One to Two Word Description
    'link'=>'https://github.com/PHPMailer/PHPMailer', // Link to plugin info
    //'fileName'=>'php-mailer.php',
	//'configFile'=>'php-mailer.php',
	//'apiFile'=>'php-mailer.php',
	'idPrefix'=>'PHPMAILER', // html element id prefix
	'configPrefix'=>'PHPMAILER', // config file prefix for array items without the hypen
    'version'=>'1.0.0', // SemVer of plugin
    'image'=>'plugins/images/php-mailer.png', // 1:1 non transparent image for plugin
	'settings'=>true, // does plugin need a settings page? true or false
    'homepage'=>false // Is plugin for use on homepage? true or false
);
// INCLUDE/REQUIRE FILES

// PLUGIN FUNCTIONS
function phpmSendTestEmail(){
	try {
		$mail = new PHPMailer\PHPMailer\PHPMailer(true);
		$mail->isSMTP();
		//$mail->SMTPDebug = 3;
		$mail->Host = $GLOBALS['PHPMAILER-smtpHost'];
		$mail->Port = $GLOBALS['PHPMAILER-smtpHostPort'];
		$mail->SMTPSecure = $GLOBALS['PHPMAILER-smtpHostType'];
		$mail->SMTPAuth = $GLOBALS['PHPMAILER-smtpHostAuth'];
		$mail->Username = $GLOBALS['PHPMAILER-smtpHostUsername'];
		$mail->Password = decrypt($GLOBALS['PHPMAILER-smtpHostPassword']);
		$mail->setFrom($GLOBALS['PHPMAILER-smtpHostSenderEmail'], $GLOBALS['PHPMAILER-smtpHostSenderName']);
		$mail->addReplyTo($GLOBALS['PHPMAILER-smtpHostSenderEmail'], $GLOBALS['PHPMAILER-smtpHostSenderName']);
		$mail->isHTML(true);
		$mail->addAddress($GLOBALS['organizrUser']['email'], $GLOBALS['organizrUser']['username']);
		$mail->Subject = "Organizr Test E-Mail";
		$mail->Body    = "This was just a test!";
		$mail->send();
		writeLog('success', 'Mail Function -  E-Mail Test Sent', $GLOBALS['organizrUser']['username']);
		return true;
	} catch (PHPMailer\PHPMailer\Exception $e) {
		writeLog('error', 'Mail Function -  E-Mail Test Failed['.$mail->ErrorInfo.']', $GLOBALS['organizrUser']['username']);
		return $e->errorMessage();
	}
	return false;
}
/* GET PHPMAILER SETTINGS */
function phpmGetSettings(){
	return array(
		array(
			'type' => 'input',
			'name' => 'PHPMAILER-smtpHost',
			'label' => 'SMTP Host',
			'value' => $GLOBALS['PHPMAILER-smtpHost']
		),
		array(
			'type' => 'input',
			'name' => 'PHPMAILER-smtpHostPort',
			'label' => 'SMTP Port',
			'value' => $GLOBALS['PHPMAILER-smtpHostPort']
		),
		array(
			'type' => 'input',
			'name' => 'PHPMAILER-smtpHostUsername',
			'label' => 'Username',
			'value' => $GLOBALS['PHPMAILER-smtpHostUsername']
		),
		array(
			'type' => 'password',
			'name' => 'PHPMAILER-smtpHostPassword',
			'label' => 'Password',
			'value' => $GLOBALS['PHPMAILER-smtpHostPassword']
		),
		array(
			'type' => 'input',
			'name' => 'PHPMAILER-smtpHostSenderName',
			'label' => 'Sender Name',
			'value' => $GLOBALS['PHPMAILER-smtpHostSenderName']
		),
		array(
			'type' => 'input',
			'name' => 'PHPMAILER-smtpHostSenderEmail',
			'label' => 'Sender Email',
			'value' => $GLOBALS['PHPMAILER-smtpHostSenderEmail'],
			'placeholder' => 'i.e. same as username'
		),
		array(
			'type' => 'switch',
			'name' => 'PHPMAILER-smtpHostAuth',
			'label' => 'Authentication',
			'value' => $GLOBALS['PHPMAILER-smtpHostAuth']
		),
		array(
			'type' => 'select',
			'name' => 'PHPMAILER-smtpHostType',
			'label' => 'Authentication Type',
			'value' => $GLOBALS['PHPMAILER-smtpHostType'],
			'options' => array(
				array(
					'name'=>'tls',
					'value'=>'tls'
				),
				array(
					'name'=>'ssl',
					'value'=>'ssl'
				),
				array(
					'name'=>'off',
					'value'=>'false'
				)
			)
		),
		array(
			'type' => 'button',
			'label' => 'Send Test',
			'class' => 'phpmSendTestEmail',
			'icon' => 'fa fa-paper-plane',
			'text' => 'Send'
		)
	);
}
