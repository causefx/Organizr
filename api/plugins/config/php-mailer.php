<?php
return array(
	'PHPMAILER-enabled' => false,
	'PHPMAILER-smtpHost' => '',
	'PHPMAILER-smtpHostPort' => '',
	'PHPMAILER-smtpHostAuth' => true,
	'PHPMAILER-smtpHostUsername' => '',
	'PHPMAILER-smtpHostPassword' => '',
	'PHPMAILER-smtpHostSenderName' => 'Organizr',
	'PHPMAILER-smtpHostSenderEmail' => 'no-reply@Organizr.tld',
	'PHPMAILER-smtpHostType' => 'tls',
	'PHPMAILER-domain' => '',
	'PHPMAILER-template' => 'default',
	'PHPMAILER-logo' => 'https://raw.githubusercontent.com/causefx/Organizr/v2-develop/plugins/images/organizr/logo-wide.png',
	'PHPMAILER-emailTemplateResetPassword' => '
	<h2>Hey there {user}!</h2><br />
	Looks like you forgot your password.  Well, I got you...  Here is your new password: {password}<br />
	If you want to change it once you log in, you can.  Head over to my website: {domain}<br />
	',
	'PHPMAILER-emailTemplateResetPasswordSubject' => 'Password Reset',
	'PHPMAILER-emailTemplateInviteUser' => '
	<h2>Hey there {user}!</h2><br />
	Here is the invite code to join my cool media server: {inviteCode}<br/>
	Head over to my website and enter the code to join: {domain}<br />
	',
	'PHPMAILER-emailTemplateInviteUserSubject' => 'You have been invited to join my server',
	'PHPMAILER-emailTemplateRegisterUser' => '
	<h2>Hey there {user}!</h2><br />
	Welcome to my site.<br/>
	If you need anything, please let me know.<br />
	',
	'PHPMAILER-emailTemplateRegisterUserSubject' => 'Thank you For Registering',
	'PHPMAILER-emailTemplateRegisterUserEnabled' => true,
	'PHPMAILER-emailTemplateCustom-include-One' => '',
	'PHPMAILER-emailTemplateCustom-include-OneName' => 'Template #1',
	'PHPMAILER-emailTemplateCustom-include-OneSubject' => '',
	'PHPMAILER-emailTemplateCustom-include-Two' => '',
	'PHPMAILER-emailTemplateCustom-include-TwoName' => 'Template #2',
	'PHPMAILER-emailTemplateCustom-include-TwoSubject' => '',
	'PHPMAILER-emailTemplateCustom-include-Three' => '',
	'PHPMAILER-emailTemplateCustom-include-ThreeName' => 'Template #3',
	'PHPMAILER-emailTemplateCustom-include-ThreeSubject' => '',
	'PHPMAILER-emailTemplateCustom-include-Four' => '',
	'PHPMAILER-emailTemplateCustom-include-FourName' => 'Template #4',
	'PHPMAILER-emailTemplateCustom-include-FourSubject' => '',
	'PHPMAILER-verifyCert' => true,
);
