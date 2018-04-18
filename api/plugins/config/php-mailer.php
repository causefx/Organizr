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
	'PHPMAILER-logo' => 'https://raw.githubusercontent.com/causefx/Organizr/master/images/organizr-logo-h.png',
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
	'PHPMAILER-emailTemplateCustomOne' => '',
	'PHPMAILER-emailTemplateCustomOneName' => 'Template #1',
	'PHPMAILER-emailTemplateCustomOneSubject' => '',
	'PHPMAILER-emailTemplateCustomTwo' => '',
	'PHPMAILER-emailTemplateCustomTwoName' => 'Template #2',
	'PHPMAILER-emailTemplateCustomTwoSubject' => '',
	'PHPMAILER-emailTemplateCustomThree' => '',
	'PHPMAILER-emailTemplateCustomThreeName' => 'Template #3',
	'PHPMAILER-emailTemplateCustomThreeSubject' => '',
	'PHPMAILER-emailTemplateCustomFour' => '',
	'PHPMAILER-emailTemplateCustomFourName' => 'Template #4',
	'PHPMAILER-emailTemplateCustomFourSubject' => '',
);
