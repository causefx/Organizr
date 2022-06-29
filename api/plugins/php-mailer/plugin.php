<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['PHP Mailer'] = array( // Plugin Name
	'name' => 'PHP Mailer', // Plugin Name
	'author' => 'PHP Mailer', // Who wrote the plugin
	'category' => 'Mail', // One to Two Word Description
	'link' => 'https://github.com/PHPMailer/PHPMailer', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'PHPMAILER', // html element id prefix
	'configPrefix' => 'PHPMAILER', // config file prefix for array items without the hyphen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'api/plugins/php-mailer/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/php-mailer/settings', // api route for settings page
	'homepage' => false // Is plugin for use on homepage? true or false
);

class PhpMailer extends Organizr
{
	public function _phpMailerPluginGetEmails()
	{
		$type = null;
		if ($this->config['authBackend']) {
			if ($this->config['authBackend'] == 'plex') {
				$type = 'plex';
			}
		}
		if ($type == 'plex') {
			$emails = array_merge($this->userList('plex')['both'], $this->_phpMailerPluginGetOrgUsers());
		} elseif ($type == 'emby') {
			$emails = $this->_phpMailerPluginGetOrgUsers();
		} else {
			$emails = $this->_phpMailerPluginGetOrgUsers();
		}
		return $emails;
	}

	public function _phpMailerPluginGetOrgUsers()
	{
		$return = null;
		$result = $this->getAllUsers(true);
		if (is_array($result) || is_object($result)) {
			foreach ($result['users'] as $k => $v) {
				$return[$v['username']] = $v['email'];
			}
			return ($return) ?? false;
		}
	}

	public function _phpMailerPluginGetTemplates()
	{
		foreach (glob(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'php-mailer' . DIRECTORY_SEPARATOR . 'misc' . DIRECTORY_SEPARATOR . 'emailTemplates' . DIRECTORY_SEPARATOR . "*.php") as $filename) {
			$templates[] = array(
				'name' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename)),
				'value' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename))
			);
		}
		return $templates;
	}

	public function _phpMailerPluginEmailTemplate($emailTemplate)
	{
		$variables = [
			'{user}' => $emailTemplate['user'],
			'{domain}' => $this->getServerPath(true),
			'{password}' => $emailTemplate['password'],
			'{inviteCode}' => $emailTemplate['inviteCode'],
			'{fullDomain}' => $this->getServerPath(true),
			'{title}' => $this->config['title'],
		];
		$emailTemplate['body'] = strtr($emailTemplate['body'], $variables);
		$emailTemplate['subject'] = strtr($emailTemplate['subject'], $variables);
		return $emailTemplate;
	}

	public function _phpMailerPluginBuildEmail($email)
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
		include('misc/emailTemplates/' . $this->config['PHPMAILER-template'] . '.php');
		return $email;
	}

	public function _phpMailerPluginAdminSendEmail($array)
	{
		if ($this->config['PHPMAILER-enabled']) {
			$emailTemplate = array(
				'type' => 'admin',
				'body' => $array['body'],
				'subject' => $array['subject'],
				'user' => null,
				'password' => null,
				'inviteCode' => null,
			);
			$emailTemplate = $this->_phpMailerPluginEmailTemplate($emailTemplate);
			$sendEmail = array(
				'bcc' => $array['bcc'],
				'subject' => $emailTemplate['subject'],
				'body' => $this->_phpMailerPluginBuildEmail($emailTemplate),
			);
			$response = $this->_phpMailerPluginSendEmail($sendEmail);
			if ($response == true) {
				$msg = ($this->config['PHPMAILER-debugTesting']) ? $this->config['phpmOriginalDebug'] : 'Email sent';
				$this->setAPIResponse('success', $msg, 200);
			} else {
				$this->setAPIResponse('error', $response, 409);
				return false;
			}
			return true;
		} else {
			$this->setAPIResponse('error', 'PHP-Mailer is not enabled', 409);
			return false;
		}
		return false;
	}

	public function _phpMailerPluginGetDebug($str, $level)
	{
		$this->config['phpmOriginalDebug'] = $this->config['phpmOriginalDebug'] . $str;
		return $this->config['phpmOriginalDebug'];
	}

	public function _phpMailerPluginSendTestEmail()
	{
		$emailTemplate = array(
			'type' => 'test',
			'body' => 'This is just a test email.',
			'subject' => 'Test E-Mail',
			'user' => null,
			'password' => null,
			'inviteCode' => null,
		);
		$emailTemplate = $this->_phpMailerPluginEmailTemplate($emailTemplate);
		$this->config['phpmOriginalDebug'] = '|||DEBUG|||';
		try {
			$mail = new PHPMailer\PHPMailer\PHPMailer(true);
			$mail->SMTPDebug = 2;
			$mail->isSMTP();
			$mail->Debugoutput = function ($str, $level) {
				$this->_phpMailerPluginGetDebug($str, $level);
			};
			$mail->Host = $this->config['PHPMAILER-smtpHost'];
			$mail->Port = $this->config['PHPMAILER-smtpHostPort'];
			if ($this->config['PHPMAILER-smtpHostType'] !== 'n/a') {
				$mail->SMTPSecure = $this->config['PHPMAILER-smtpHostType'];
			}
			$mail->SMTPAuth = $this->config['PHPMAILER-smtpHostAuth'];
			$mail->Username = $this->config['PHPMAILER-smtpHostUsername'];
			$mail->Password = $this->decrypt($this->config['PHPMAILER-smtpHostPassword']);
			$mail->SMTPOptions = array(
				'ssl' => [
					'verify_peer' => $this->config['PHPMAILER-verifyCert'],
					'verify_depth' => 3,
					'allow_self_signed' => true,
					'peer_name' => $this->config['PHPMAILER-smtpHost'],
					'cafile' => $this->getCert(),
				],
			);
			$mail->setFrom($this->config['PHPMAILER-smtpHostSenderEmail'], $this->config['PHPMAILER-smtpHostSenderName']);
			$mail->addReplyTo($this->config['PHPMAILER-smtpHostSenderEmail'], $this->config['PHPMAILER-smtpHostSenderName']);
			$mail->isHTML(true);
			$mail->addAddress($this->user['email'], $this->user['username']);
			$mail->Subject = $emailTemplate['subject'];
			$mail->Body = $this->_phpMailerPluginBuildEmail($emailTemplate);
			$mail->send();
			$this->setLoggerChannel('Email')->info('E-Mail Test Sent');
			$msg = ($this->config['PHPMAILER-debugTesting']) ? $this->config['phpmOriginalDebug'] : 'Email sent';
			$this->setAPIResponse('success', $msg, 200);
			return true;
		} catch (PHPMailer\PHPMailer\Exception $e) {
			$this->setLoggerChannel('Email')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
		return false;
	}

	public function _phpMailerPluginSendEmail($emailInfo)
	{
		$to = isset($emailInfo['to']) ? $emailInfo['to'] : null;
		$cc = isset($emailInfo['cc']) ? $emailInfo['cc'] : null;
		$bcc = isset($emailInfo['bcc']) ? $emailInfo['bcc'] : null;
		$subject = isset($emailInfo['subject']) ? $emailInfo['subject'] : null;
		$body = isset($emailInfo['body']) ? $emailInfo['body'] : null;
		$username = isset($emailInfo['user']) ? $emailInfo['user'] : 'Organizr User';
		$data = [
			'to' => $to,
			'cc' => $cc,
			'bcc' => $bcc,
			'subject' => $subject,
			'body' => $body,
			'username' => $username,
		];
		try {
			$mail = new PHPMailer\PHPMailer\PHPMailer(true);
			$mail->isSMTP();
			//$mail->SMTPDebug = 3;
			$mail->Host = $this->config['PHPMAILER-smtpHost'];
			$mail->Port = $this->config['PHPMAILER-smtpHostPort'];
			if ($this->config['PHPMAILER-smtpHostType'] !== 'n/a') {
				$mail->SMTPSecure = $this->config['PHPMAILER-smtpHostType'];
			}
			$mail->SMTPAuth = $this->config['PHPMAILER-smtpHostAuth'];
			$mail->Username = $this->config['PHPMAILER-smtpHostUsername'];
			$mail->Password = $this->decrypt($this->config['PHPMAILER-smtpHostPassword']);
			$mail->SMTPOptions = array(
				'ssl' => [
					'verify_peer' => $this->config['PHPMAILER-verifyCert'],
					'verify_depth' => 3,
					'allow_self_signed' => true,
					'peer_name' => $this->config['PHPMAILER-smtpHost'],
					'cafile' => $this->getCert(),
				],
			);
			$mail->setFrom($this->config['PHPMAILER-smtpHostSenderEmail'], $this->config['PHPMAILER-smtpHostSenderName']);
			$mail->addReplyTo($this->config['PHPMAILER-smtpHostSenderEmail'], $this->config['PHPMAILER-smtpHostSenderName']);
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
			return true;
		} catch (PHPMailer\PHPMailer\Exception $e) {
			$this->setLoggerChannel('Email')->error($e, $data);
			return false;
		}
	}

	/* GET PHPMAILER SETTINGS */
	public function _phpMailerPluginGetSettings()
	{
		return [
			'Host' => [
				[
					'type' => 'input',
					'name' => 'PHPMAILER-smtpHost',
					'label' => 'SMTP Host',
					'value' => $this->config['PHPMAILER-smtpHost']
				],
				[
					'type' => 'input',
					'name' => 'PHPMAILER-smtpHostPort',
					'label' => 'SMTP Port',
					'value' => $this->config['PHPMAILER-smtpHostPort']
				]
			],
			'Authentication' => [
				[
					'type' => 'input',
					'name' => 'PHPMAILER-smtpHostUsername',
					'label' => 'Username',
					'value' => $this->config['PHPMAILER-smtpHostUsername']
				],
				[
					'type' => 'password',
					'name' => 'PHPMAILER-smtpHostPassword',
					'label' => 'Password',
					'value' => $this->config['PHPMAILER-smtpHostPassword']
				],
				[
					'type' => 'switch',
					'name' => 'PHPMAILER-smtpHostAuth',
					'label' => 'Authentication',
					'value' => $this->config['PHPMAILER-smtpHostAuth']
				],
				[
					'type' => 'select',
					'name' => 'PHPMAILER-smtpHostType',
					'label' => 'Authentication Type',
					'value' => $this->config['PHPMAILER-smtpHostType'],
					'options' => [
						[
							'name' => 'tls',
							'value' => 'tls'
						],
						[
							'name' => 'ssl',
							'value' => 'ssl'
						],
						[
							'name' => 'off',
							'value' => 'n/a'
						]
					]
				],
				[
					'type' => 'switch',
					'name' => 'PHPMAILER-verifyCert',
					'label' => 'Verify Certificate',
					'value' => $this->config['PHPMAILER-verifyCert']
				],
			],
			'Sender Information' => [
				[
					'type' => 'input',
					'name' => 'PHPMAILER-smtpHostSenderName',
					'label' => 'Sender Name',
					'value' => $this->config['PHPMAILER-smtpHostSenderName']
				],
				[
					'type' => 'input',
					'name' => 'PHPMAILER-smtpHostSenderEmail',
					'label' => 'Sender Email',
					'value' => $this->config['PHPMAILER-smtpHostSenderEmail'],
					'placeholder' => 'i.e. same as username'
				]
			],
			'Test & Options' => [
				[
					'type' => 'button',
					'label' => 'Send Test',
					'class' => 'phpmSendTestEmail',
					'icon' => 'fa fa-paper-plane',
					'text' => 'Send'
				],
				[
					'type' => 'switch',
					'name' => 'PHPMAILER-debugTesting',
					'label' => 'Enable Debug Output on Email Test',
					'value' => $this->config['PHPMAILER-debugTesting'],
				],
				[
					'type' => 'input',
					'name' => 'PHPMAILER-domain',
					'label' => 'Domain Link Override',
					'value' => $this->config['PHPMAILER-domain'],
					'placeholder' => 'https://domain.com/',
				],
				[
					'type' => 'select',
					'name' => 'PHPMAILER-template',
					'label' => 'Theme',
					'value' => $this->config['PHPMAILER-template'],
					'options' => $this->_phpMailerPluginGetTemplates()
				],
				[
					'type' => 'input',
					'name' => 'PHPMAILER-logo',
					'label' => 'WAN Logo URL',
					'value' => $this->config['PHPMAILER-logo'],
					'placeholder' => 'Full URL',
				],
				[
					'type' => 'switch',
					'name' => 'PHPMAILER-emailTemplateRegisterUserEnabled',
					'label' => 'Send Welcome E-Mail',
					'value' => $this->config['PHPMAILER-emailTemplateRegisterUserEnabled'],
				],
			],
			'Templates' => [
				[
					'type' => 'accordion',
					'label' => 'Edit Template',
					'id' => 'customEmailTemplates',
					'override' => 12,
					'options' => [
						[
							'id' => 'PHPMAILER-emailTemplateRegisterUserForm',
							'header' => 'New Registration',
							'body' => [
								[
									'type' => 'input',
									'name' => 'PHPMAILER-emailTemplateRegisterUserSubject',
									'smallLabel' => 'Subject',
									'value' => $this->config['PHPMAILER-emailTemplateRegisterUserSubject'],
								],
								[
									'type' => 'textbox',
									'name' => 'PHPMAILER-emailTemplateRegisterUser',
									'smallLabel' => 'Body',
									'value' => $this->config['PHPMAILER-emailTemplateRegisterUser'],
									'attr' => 'rows="10"',
								]
							]
						],
						[
							'id' => 'PHPMAILER-emailTemplateResetPasswordForm',
							'header' => 'Reset Password',
							'body' => [
								[
									'type' => 'input',
									'name' => 'PHPMAILER-emailTemplateResetSubject',
									'smallLabel' => 'Subject',
									'value' => $this->config['PHPMAILER-emailTemplateResetSubject'],
								],
								[
									'type' => 'textbox',
									'name' => 'PHPMAILER-emailTemplateReset',
									'smallLabel' => 'Body',
									'value' => $this->config['PHPMAILER-emailTemplateReset'],
									'attr' => 'rows="10"',
								]
							]
						],
						[
							'id' => 'PHPMAILER-emailTemplateInviteUserForm',
							'header' => 'Invite User',
							'body' => [
								[
									'type' => 'input',
									'name' => 'PHPMAILER-emailTemplateInviteUserSubject',
									'smallLabel' => 'Subject',
									'value' => $this->config['PHPMAILER-emailTemplateInviteUserSubject'],
								],
								[
									'type' => 'textbox',
									'name' => 'PHPMAILER-emailTemplateInviteUser',
									'smallLabel' => 'Body',
									'value' => $this->config['PHPMAILER-emailTemplateInviteUser'],
									'attr' => 'rows="10"',
								]
							]
						],
						[
							'id' => 'PHPMAILER-emailTemplateCustom-include-OneForm',
							'header' => $this->config['PHPMAILER-emailTemplateCustom-include-OneName'],
							'body' => [
								[
									'type' => 'input',
									'name' => 'PHPMAILER-emailTemplateCustom-include-OneName',
									'smallLabel' => 'Name',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-OneName'],
								],
								[
									'type' => 'input',
									'name' => 'PHPMAILER-emailTemplateCustom-include-OneSubject',
									'smallLabel' => 'Subject',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-OneSubject'],
								],
								[
									'type' => 'textbox',
									'name' => 'PHPMAILER-emailTemplateCustom-include-One',
									'smallLabel' => 'Body',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-One'],
									'attr' => 'rows="10"',
								]
							]
						],
						[
							'id' => 'PHPMAILER-emailTemplateCustom-include-TwoForm',
							'header' => $this->config['PHPMAILER-emailTemplateCustom-include-TwoName'],
							'body' => [
								[
									'type' => 'input',
									'name' => 'PHPMAILER-emailTemplateCustom-include-TwoName',
									'smallLabel' => 'Name',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-TwoName'],
								],
								[
									'type' => 'input',
									'name' => 'PHPMAILER-emailTemplateCustom-include-TwoSubject',
									'smallLabel' => 'Subject',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-TwoSubject'],
								],
								[
									'type' => 'textbox',
									'name' => 'PHPMAILER-emailTemplateCustom-include-Two',
									'smallLabel' => 'Body',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-Two'],
									'attr' => 'rows="10"',
								]
							]
						],
						[
							'id' => 'PHPMAILER-emailTemplateCustom-include-ThreeForm',
							'header' => $this->config['PHPMAILER-emailTemplateCustom-include-ThreeName'],
							'body' => [
								[
									'type' => 'input',
									'name' => 'PHPMAILER-emailTemplateCustom-include-ThreeName',
									'smallLabel' => 'Name',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-ThreeName'],
								],
								[
									'type' => 'input',
									'name' => 'PHPMAILER-emailTemplateCustom-include-ThreeSubject',
									'smallLabel' => 'Subject',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-ThreeSubject'],
								],
								[
									'type' => 'textbox',
									'name' => 'PHPMAILER-emailTemplateCustom-include-Three',
									'smallLabel' => 'Body',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-Three'],
									'attr' => 'rows="10"',
								]
							]
						],
						[
							'id' => 'PHPMAILER-emailTemplateCustom-include-FourForm',
							'header' => $this->config['PHPMAILER-emailTemplateCustom-include-FourName'],
							'body' => [
								[
									'type' => 'input',
									'name' => 'PHPMAILER-emailTemplateCustom-include-FourName',
									'smallLabel' => 'Name',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-FourName'],
								],
								[
									'type' => 'input',
									'name' => 'PHPMAILER-emailTemplateCustom-include-FourSubject',
									'smallLabel' => 'Subject',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-FourSubject'],
								],
								[
									'type' => 'textbox',
									'name' => 'PHPMAILER-emailTemplateCustom-include-Four',
									'smallLabel' => 'Body',
									'value' => $this->config['PHPMAILER-emailTemplateCustom-include-Four'],
									'attr' => 'rows="10"',
								]
							]
						],
					]
				]
			]
		];
	}
}