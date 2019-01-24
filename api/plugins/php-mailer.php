<?php
/** @noinspection PhpUnusedLocalVariableInspection */
// PLUGIN INFORMATION
$GLOBALS['plugins'][]['PHP Mailer'] = array( // Plugin Name
	'name' => 'PHP Mailer', // Plugin Name
	'author' => 'PHP Mailer', // Who wrote the plugin
	'category' => 'Mail', // One to Two Word Description
	'link' => 'https://github.com/PHPMailer/PHPMailer', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	//'fileName'=>'php-mailer.php',
	//'configFile'=>'php-mailer.php',
	//'apiFile'=>'php-mailer.php',
	'idPrefix' => 'PHPMAILER', // html element id prefix
	'configPrefix' => 'PHPMAILER', // config file prefix for array items without the hyphen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'plugins/images/php-mailer.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings page? true or false
	'homepage' => false // Is plugin for use on homepage? true or false
);
// INCLUDE/REQUIRE FILES
// PLUGIN FUNCTIONS
function getEmails()
{
	if ($GLOBALS['authBackend']) {
		if ($GLOBALS['authBackend'] == 'plex') {
			$type = 'plex';
		}
	} else {
		$type = 'none';
	}
	if ($type == 'plex') {
		$emails = array_merge(userList('plex')['both'], getOrgUsers());
	} elseif ($type == 'emby') {
		$emails = getOrgUsers();
	} else {
		$emails = getOrgUsers();
	}
	return $emails;
}

function getTemplates()
{
	foreach (glob(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'misc' . DIRECTORY_SEPARATOR . 'emailTemplates' . DIRECTORY_SEPARATOR . "*.php") as $filename) {
		$templates[] = array(
			'name' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename)),
			'value' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename))
		);
	}
	return $templates;
}

function phpmEmailTemplate($emailTemplate)
{
	$variables = [
		'{user}' => $emailTemplate['user'],
		'{domain}' => getServerPath(true),
		'{password}' => $emailTemplate['password'],
		'{inviteCode}' => $emailTemplate['inviteCode'],
		'{fullDomain}' => getServerPath(true),
		'{title}' => $GLOBALS['title'],
	];
	$emailTemplate['body'] = strtr($emailTemplate['body'], $variables);
	$emailTemplate['subject'] = strtr($emailTemplate['subject'], $variables);
	return $emailTemplate;
}

function phpmBuildEmail($email)
{
	/** @noinspection PhpUnusedLocalVariableInspection */
	$subject = (isset($email['subject'])) ? $email['subject'] : 'Message from Server';
	$body = (isset($email['body'])) ? $email['body'] : 'Message Error Occurred';
	$type = (isset($email['type'])) ? $email['type'] : 'No Type';
	switch ($type) {
		case 'invite':
			$extra = 'invite';
			break;
		case 'reset':
			$extra = 'reset';
			break;
		default:
			$extra = null;
			break;
	}
	include('misc/emailTemplates/' . $GLOBALS['PHPMAILER-template'] . '.php');
	return $email;
}

function phpmAdminSendEmail()
{
	if ($GLOBALS['PHPMAILER-enabled']) {
		$emailTemplate = array(
			'type' => 'admin',
			'body' => $_POST['data']['body'],
			'subject' => $_POST['data']['subject'],
			'user' => null,
			'password' => null,
			'inviteCode' => null,
		);
		$emailTemplate = phpmEmailTemplate($emailTemplate);
		$sendEmail = array(
			'bcc' => $_POST['data']['bcc'],
			'subject' => $emailTemplate['subject'],
			'body' => phpmBuildEmail($emailTemplate),
		);
		return phpmSendEmail($sendEmail);
	}
	return false;
}

function phpmSendTestEmail()
{
	$emailTemplate = array(
		'type' => 'test',
		'body' => 'This is just a test email.',
		'subject' => 'Test E-Mail',
		'user' => null,
		'password' => null,
		'inviteCode' => null,
	);
	$emailTemplate = phpmEmailTemplate($emailTemplate);
	try {
		$mail = new PHPMailer\PHPMailer\PHPMailer(true);
		$mail->isSMTP();
		//$mail->SMTPDebug = 3;
		$mail->Host = $GLOBALS['PHPMAILER-smtpHost'];
		$mail->Port = $GLOBALS['PHPMAILER-smtpHostPort'];
		if ($GLOBALS['PHPMAILER-smtpHostType'] !== 'n/a') {
			$mail->SMTPSecure = $GLOBALS['PHPMAILER-smtpHostType'];
		}
		$mail->SMTPAuth = $GLOBALS['PHPMAILER-smtpHostAuth'];
		$mail->Username = $GLOBALS['PHPMAILER-smtpHostUsername'];
		$mail->Password = decrypt($GLOBALS['PHPMAILER-smtpHostPassword']);
		$mail->SMTPOptions = array(
			'ssl' => [
				'verify_peer' => $GLOBALS['PHPMAILER-verifyCert'],
				'verify_depth' => 3,
				'allow_self_signed' => true,
				'peer_name' => $GLOBALS['PHPMAILER-smtpHost'],
				'cafile' => getCert(),
			],
		);
		$mail->setFrom($GLOBALS['PHPMAILER-smtpHostSenderEmail'], $GLOBALS['PHPMAILER-smtpHostSenderName']);
		$mail->addReplyTo($GLOBALS['PHPMAILER-smtpHostSenderEmail'], $GLOBALS['PHPMAILER-smtpHostSenderName']);
		$mail->isHTML(true);
		$mail->addAddress($GLOBALS['organizrUser']['email'], $GLOBALS['organizrUser']['username']);
		$mail->Subject = $emailTemplate['subject'];
		$mail->Body = phpmBuildEmail($emailTemplate);
		$mail->send();
		writeLog('success', 'Mail Function -  E-Mail Test Sent', $GLOBALS['organizrUser']['username']);
		return true;
	} catch (PHPMailer\PHPMailer\Exception $e) {
		writeLog('error', 'Mail Function -  E-Mail Test Failed[' . $mail->ErrorInfo . ']', $GLOBALS['organizrUser']['username']);
		return $e->errorMessage();
	}
	return false;
}

function phpmSendEmail($emailInfo)
{
	$to = isset($emailInfo['to']) ? $emailInfo['to'] : null;
	$cc = isset($emailInfo['cc']) ? $emailInfo['cc'] : null;
	$bcc = isset($emailInfo['bcc']) ? $emailInfo['bcc'] : null;
	$subject = isset($emailInfo['subject']) ? $emailInfo['subject'] : null;
	$body = isset($emailInfo['body']) ? $emailInfo['body'] : null;
	$username = isset($emailInfo['user']) ? $emailInfo['user'] : 'Organizr User';
	try {
		$mail = new PHPMailer\PHPMailer\PHPMailer(true);
		$mail->isSMTP();
		//$mail->SMTPDebug = 3;
		$mail->Host = $GLOBALS['PHPMAILER-smtpHost'];
		$mail->Port = $GLOBALS['PHPMAILER-smtpHostPort'];
		if ($GLOBALS['PHPMAILER-smtpHostType'] !== 'n/a') {
			$mail->SMTPSecure = $GLOBALS['PHPMAILER-smtpHostType'];
		}
		$mail->SMTPAuth = $GLOBALS['PHPMAILER-smtpHostAuth'];
		$mail->Username = $GLOBALS['PHPMAILER-smtpHostUsername'];
		$mail->Password = decrypt($GLOBALS['PHPMAILER-smtpHostPassword']);
		$mail->SMTPOptions = array(
			'ssl' => [
				'verify_peer' => $GLOBALS['PHPMAILER-verifyCert'],
				'verify_depth' => 3,
				'allow_self_signed' => true,
				'peer_name' => $GLOBALS['PHPMAILER-smtpHost'],
				'cafile' => getCert(),
			],
		);
		$mail->setFrom($GLOBALS['PHPMAILER-smtpHostSenderEmail'], $GLOBALS['PHPMAILER-smtpHostSenderName']);
		$mail->addReplyTo($GLOBALS['PHPMAILER-smtpHostSenderEmail'], $GLOBALS['PHPMAILER-smtpHostSenderName']);
		$mail->isHTML(true);
		if ($to) {
			$mail->addAddress($to, $username);
		}
		if ($cc) {
			$mail->addCC($cc);
		}
		if ($bcc) {
			if (strpos($bcc, ',') === false) {
				$mail->addBCC($bcc);
			} else {
				$allEmails = explode(",", $bcc);
				foreach ($allEmails as $gotEmail) {
					$mail->addBCC($gotEmail);
				}
			}
		}
		$mail->Subject = $subject;
		$mail->Body = $body;
		$mail->send();
		//writeLog('success', 'Mail Function -  E-Mail Test Sent', $GLOBALS['organizrUser']['username']);
		return true;
	} catch (PHPMailer\PHPMailer\Exception $e) {
		writeLog('error', 'Mail Function -  E-Mail Test Failed[' . $mail->ErrorInfo . ']', $GLOBALS['organizrUser']['username']);
		return $e->errorMessage();
	}
	return false;
}

/* GET PHPMAILER SETTINGS */
function phpmGetSettings()
{
	return array(
		'Host' => array(
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
			)
		),
		'Authentication' => array(
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
						'name' => 'tls',
						'value' => 'tls'
					),
					array(
						'name' => 'ssl',
						'value' => 'ssl'
					),
					array(
						'name' => 'off',
						'value' => 'n/a'
					)
				)
			),
			array(
				'type' => 'switch',
				'name' => 'PHPMAILER-verifyCert',
				'label' => 'Verify Certificate',
				'value' => $GLOBALS['PHPMAILER-verifyCert']
			),
		),
		'Sender Information' => array(
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
			)
		),
		'Test & Options' => array(
			array(
				'type' => 'button',
				'label' => 'Send Test',
				'class' => 'phpmSendTestEmail',
				'icon' => 'fa fa-paper-plane',
				'text' => 'Send'
			),
			array(
				'type' => 'input',
				'name' => 'PHPMAILER-domain',
				'label' => 'Domain Link Override',
				'value' => $GLOBALS['PHPMAILER-domain'],
				'placeholder' => 'https://domain.com/',
			),
			array(
				'type' => 'select',
				'name' => 'PHPMAILER-template',
				'label' => 'Theme',
				'value' => $GLOBALS['PHPMAILER-template'],
				'options' => getTemplates()
			),
			array(
				'type' => 'input',
				'name' => 'PHPMAILER-logo',
				'label' => 'WAN Logo URL',
				'value' => $GLOBALS['PHPMAILER-logo'],
				'placeholder' => 'Full URL',
			),
			array(
				'type' => 'switch',
				'name' => 'PHPMAILER-emailTemplateRegisterUserEnabled',
				'label' => 'Send Welcome E-Mail',
				'value' => $GLOBALS['PHPMAILER-emailTemplateRegisterUserEnabled'],
			),
		),
		'Templates' => array(
			array(
				'type' => 'accordion',
				'label' => 'Edit Template',
				'id' => 'customEmailTemplates',
				'override' => 12,
				'options' => array(
					array(
						'id' => 'PHPMAILER-emailTemplateRegisterUserForm',
						'header' => 'New Registration',
						'body' => array(
							array(
								'type' => 'input',
								'name' => 'PHPMAILER-emailTemplateRegisterUserSubject',
								'smallLabel' => 'Subject',
								'value' => $GLOBALS['PHPMAILER-emailTemplateRegisterUserSubject'],
							),
							array(
								'type' => 'textbox',
								'name' => 'PHPMAILER-emailTemplateRegisterUser',
								'smallLabel' => 'Body',
								'value' => $GLOBALS['PHPMAILER-emailTemplateRegisterUser'],
								'attr' => 'rows="10"',
							)
						)
					),
					array(
						'id' => 'PHPMAILER-emailTemplateResetPasswordForm',
						'header' => 'Reset Password',
						'body' => array(
							array(
								'type' => 'input',
								'name' => 'PHPMAILER-emailTemplateResetPasswordSubject',
								'smallLabel' => 'Subject',
								'value' => $GLOBALS['PHPMAILER-emailTemplateResetPasswordSubject'],
							),
							array(
								'type' => 'textbox',
								'name' => 'PHPMAILER-emailTemplateResetPassword',
								'smallLabel' => 'Body',
								'value' => $GLOBALS['PHPMAILER-emailTemplateResetPassword'],
								'attr' => 'rows="10"',
							)
						)
					),
					array(
						'id' => 'PHPMAILER-emailTemplateInviteUserForm',
						'header' => 'Invite User',
						'body' => array(
							array(
								'type' => 'input',
								'name' => 'PHPMAILER-emailTemplateInviteUserSubject',
								'smallLabel' => 'Subject',
								'value' => $GLOBALS['PHPMAILER-emailTemplateInviteUserSubject'],
							),
							array(
								'type' => 'textbox',
								'name' => 'PHPMAILER-emailTemplateInviteUser',
								'smallLabel' => 'Body',
								'value' => $GLOBALS['PHPMAILER-emailTemplateInviteUser'],
								'attr' => 'rows="10"',
							)
						)
					),
					array(
						'id' => 'PHPMAILER-emailTemplateCustom-include-OneForm',
						'header' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-OneName'],
						'body' => array(
							array(
								'type' => 'input',
								'name' => 'PHPMAILER-emailTemplateCustom-include-OneName',
								'smallLabel' => 'Name',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-OneName'],
							),
							array(
								'type' => 'input',
								'name' => 'PHPMAILER-emailTemplateCustom-include-OneSubject',
								'smallLabel' => 'Subject',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-OneSubject'],
							),
							array(
								'type' => 'textbox',
								'name' => 'PHPMAILER-emailTemplateCustom-include-One',
								'smallLabel' => 'Body',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-One'],
								'attr' => 'rows="10"',
							)
						)
					),
					array(
						'id' => 'PHPMAILER-emailTemplateCustom-include-TwoForm',
						'header' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-TwoName'],
						'body' => array(
							array(
								'type' => 'input',
								'name' => 'PHPMAILER-emailTemplateCustom-include-TwoName',
								'smallLabel' => 'Name',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-TwoName'],
							),
							array(
								'type' => 'input',
								'name' => 'PHPMAILER-emailTemplateCustom-include-TwoSubject',
								'smallLabel' => 'Subject',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-TwoSubject'],
							),
							array(
								'type' => 'textbox',
								'name' => 'PHPMAILER-emailTemplateCustom-include-Two',
								'smallLabel' => 'Body',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-Two'],
								'attr' => 'rows="10"',
							)
						)
					),
					array(
						'id' => 'PHPMAILER-emailTemplateCustom-include-ThreeForm',
						'header' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-ThreeName'],
						'body' => array(
							array(
								'type' => 'input',
								'name' => 'PHPMAILER-emailTemplateCustom-include-ThreeName',
								'smallLabel' => 'Name',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-ThreeName'],
							),
							array(
								'type' => 'input',
								'name' => 'PHPMAILER-emailTemplateCustom-include-ThreeSubject',
								'smallLabel' => 'Subject',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-ThreeSubject'],
							),
							array(
								'type' => 'textbox',
								'name' => 'PHPMAILER-emailTemplateCustom-include-Three',
								'smallLabel' => 'Body',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-Three'],
								'attr' => 'rows="10"',
							)
						)
					),
					array(
						'id' => 'PHPMAILER-emailTemplateCustom-include-FourForm',
						'header' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-FourName'],
						'body' => array(
							array(
								'type' => 'input',
								'name' => 'PHPMAILER-emailTemplateCustom-include-FourName',
								'smallLabel' => 'Name',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-FourName'],
							),
							array(
								'type' => 'input',
								'name' => 'PHPMAILER-emailTemplateCustom-include-FourSubject',
								'smallLabel' => 'Subject',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-FourSubject'],
							),
							array(
								'type' => 'textbox',
								'name' => 'PHPMAILER-emailTemplateCustom-include-Four',
								'smallLabel' => 'Body',
								'value' => $GLOBALS['PHPMAILER-emailTemplateCustom-include-Four'],
								'attr' => 'rows="10"',
							)
						)
					),
				)
			)
		)
	);
}
