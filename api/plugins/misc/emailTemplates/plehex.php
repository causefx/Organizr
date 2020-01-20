<?php
switch ($extra) {
	case 'invite':
		$button = '
		<tr>
			<td align="center" valign="top">
				<div>
					<a href="' . getServerPath(true) . '?invite=' . $email['inviteCode'] . '" rel="noopener noreferrer" style="background-color: #e5a00d; border: 2px solid #E5A00D; border-radius: 100px; color: #ffffff; display: inline-block; font-family: \'Roboto\', Helvetica, Arial, sans-serif; font-size: 13px; font-weight: bold; line-height: 44px; text-align: center; text-decoration: none; width: 260px; -webkit-text-size-adjust: none; mso-hide: all;">Use Invite Code</a>
				</div>
			</td>
		</tr>
		<tr>
			<td align="center" style="padding-top: 30px;" valign="top"></td>
		</tr>
        ';
		break;
	case 'reset':
		$button = '
		<tr>
			<td align="center" valign="top">
				<div>
					<a href="' . getServerPath(true) . '" rel="noopener noreferrer" style="background-color: #e5a00d; border: 2px solid #E5A00D; border-radius: 100px; color: #ffffff; display: inline-block; font-family: \'Roboto\', Helvetica, Arial, sans-serif; font-size: 13px; font-weight: bold; line-height: 44px; text-align: center; text-decoration: none; width: 260px; -webkit-text-size-adjust: none; mso-hide: all;">Goto My Site</a>
				</div>
			</td>
		</tr>
		<tr>
			<td align="center" style="padding-top: 30px;" valign="top"></td>
		</tr>
        ';
		break;
	default:
		$button = null;
		break;
}
$email = '
<!DOCTYPE html>
<html>
<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-type">
	<meta content="IE=Edge" http-equiv="X-UA-Compatible">
	<base target="_blank">
	<style class="icloud-message-base-styles">
	       @font-face {          font-family: \'SFNSText\';          src: local(".SFNSText-Light"),               url(\'/fonts/SFNSText-Light.woff\') format(\'woff\');          font-weight: 300;        }        @font-face {          font-family: \'SFNSText\';          src: local(".SFNSText-Medium"),               url(\'/fonts/SFNSText-Medium.woff\') format(\'woff\');          font-weight: 500;        }        body {          background-color: #ffffff;          padding: 13px 20px 0px 20px;          font: 15px \'SFNSText\',\'Helvetica Neue\', Helvetica, sans-serif;          font-weight: 300;          line-height: 1.4;          margin: 0px;          overflow: hidden;          word-wrap: break-word;        }        blockquote[type=cite].quoted-plain-text{        line-height:1.5;        padding-bottom: 0px;        white-space: normal;        }        blockquote[type=cite] {          border-left: 2px solid #003399;          margin:0;          padding: 0 12px 0 12px;          font-size: 15px;          color: #003399;        }        blockquote[type=cite] blockquote[type=cite] {          border-left: 2px solid #006600;          margin:0;          padding: 0 12px 0 12px;          font-size: 15px;          color: #006600        }        blockquote[type=cite] blockquote[type=cite] blockquote[type=cite] {          border-left : 2px solid #660000;          margin:0;          padding: 0 12px 0 12px;          font-size: 15px;          color: #660000        }        pre {          white-space: pre-wrap;          white-space: -moz-pre-wrap;          white-space: -pre-wrap;          white-space: -o-pre-wrap;          word-wrap: break-word;          white-space: pre-wrap !important;          word-wrap: normal !important;          font-size: 15px;        }        .pre-a660ebf3-02d8-4365-96dd-f1368f2dba37-orientation-2{          transform:scaleX(-1);          -webkit-transform:scaleX(-1);          -ms-transform:scaleX(-1);        }        .pre-a660ebf3-02d8-4365-96dd-f1368f2dba37-orientation-3{          transform:rotate(180deg);          -webkit-transform:rotate(180deg);          -ms-transform:rotate(180deg);        }        .pre-a660ebf3-02d8-4365-96dd-f1368f2dba37-orientation-4{          transform:rotate(180deg) scaleX(-1);          -webkit-transform:rotate(180deg) scaleX(-1);          -ms-transform:rotate(180deg) scaleX(-1);        }        .pre-a660ebf3-02d8-4365-96dd-f1368f2dba37-orientation-5{          transform:rotate(270deg) scaleX(-1);          -webkit-transform:rotate(270deg) scaleX(-1);          -ms-transform:rotate(270deg) scaleX(-1);        }        .pre-a660ebf3-02d8-4365-96dd-f1368f2dba37-orientation-6{          transform:rotate(90deg);          -webkit-transform:rotate(90deg);          -ms-transform:rotate(90deg);        }        .pre-a660ebf3-02d8-4365-96dd-f1368f2dba37-orientation-7{          transform:rotate(90deg) scaleX(-1);          -webkit-transform:rotate(90deg) scaleX(-1);          -ms-transform:rotate(90deg) scaleX(-1);        }        .pre-a660ebf3-02d8-4365-96dd-f1368f2dba37-orientation-8{          transform:rotate(270deg);          -webkit-transform:rotate(270deg);          -ms-transform:rotate(270deg);        }        .x-apple-maildropbanner {          margin-top:-13px;        }        a.view-message-icloud-share,        a.view-message-icloud-share:visited {          cursor: pointer;          color: #0000EE;          text-decoration: underline;        }        a.view-message-icloud-share:hover{          text-decoration: underline;        }
	</style>
	<style class="existing-message-styles" type="text/css">
	   
	   @media screen {
	       @font-face {
	           font-family: \'Open Sans\';
	           font-style: normal;
	           font-weight: 300;
	           src: local(\'Open Sans Light\'), local(\'OpenSans-Light\'), url(https://themes.googleusercontent.com/static/fonts/opensans/v10/DXI1ORHCpsQm3Vp6mXoaTaRDOzjiPcYnFooOUGCOsRk.woff) format(\'woff\');
	       }
	       @font-face {
	           font-family: \'Open Sans\';
	           font-style: normal;
	           font-weight: 400;
	           src: local(\'Open Sans\'), local(\'OpenSans\'), url(https://themes.googleusercontent.com/static/fonts/opensans/v10/cJZKeOuBrn4kERxqtaUH3bO3LdcAZYWl9Si6vvxL-qU.woff) format(\'woff\');
	       }
	       @font-face {
	           font-family: \'Open Sans\';
	           font-style: normal;
	           font-weight: 700;
	           src: local(\'Open Sans Bold\'), local(\'OpenSans-Bold\'), url(https://themes.googleusercontent.com/static/fonts/opensans/v10/k3k702ZOKiLJc3WVjuplzHhCUOGz7vYGh680lGh-uXM.woff) format(\'woff\');
	       }
	       @font-face {
	         font-family: \'Roboto\';
	         font-style: normal;
	         font-weight: 400;
	         src: local(\'Roboto\'), local(\'Roboto-Regular\'), url(https://fonts.gstatic.com/s/roboto/v15/CrYjSnGjrRCn0pd9VQsnFOvvDin1pK8aKteLpeZ5c0A.woff) format(\'woff\');
	       }
	       @font-face {
	         font-family: \'Roboto\';
	         font-style: normal;
	         font-weight: 700;
	         src: local(\'Roboto Bold\'), local(\'Roboto-Bold\'), url(https://fonts.gstatic.com/s/roboto/v15/d-6IYplOFocCacKzxwXSOLO3LdcAZYWl9Si6vvxL-qU.woff) format(\'woff\');
	       }
	   }
	   
	   #outlook a {padding:0;}
	   body{width:100% !important; min-width: 100% !important; margin:0; padding:0; -webkit-text; size-adjust:100%; -ms-text-size-adjust:100%; -webkit-font-smoothing: antialiased; font-smoothing: antialiased; text-rendering: optimizeLegibility;}
	   .ReadMsgBody {width: 100%;}
	   .ExternalClass {width:100%;}
	   .backgroundTable {margin:0 auto; padding:0; width:100%;!important;}
	   table td {border-collapse: collapse;}
	   .ExternalClass * {line-height: 115%;}
	   b {font-weight: 700;}
	   ul {line-height: 19px; padding-top: 8px;}
	   li {padding-bottom: 8px; line-height: 19px;}
	   
	   @-ms-viewport{width:device-width}
	   @media screen and (max-device-width: 680px), screen and (max-width: 680px) {
	       .header-title {font-size: 16px;}
	       .header img {width: 70px;}
	       *[class="50p"] {width:100% !important; height:auto !important; display: block;}
	       *[class="30p"] {width:100% !important; height:auto !important; display: block;}
	       *[class="25p"] {width:100% !important; height:auto !important; display: inline-block;}
	       *[class="100p"] {width:100% !important; height:auto !important;}
	       *[class="100pnopad"] {width:100% !important; height:auto !important;}
	       *[class="container-mobile"] {padding: 40px 25px !important;}
	       *[class="container-mobile-header"] {padding: 25px !important;}
	       *[class="container-mobile-alt"] {padding: 0px 25px 40px 25px !important;}
	       *[class="container-mobile-nopad"] {padding: 40px 25px 15px 25px !important;}
	       *[class="container-mobile-flush"] {padding: 40px 25px 0px 25px !important;}
	       *[class="container-mobile-flush-top"] {padding: 0px 25px 40px 25px !important;}
	       *[class="title"] {font-size: 28px !important; line-height: 34px !important;}
	       *[class="image-icon"] {padding-top: 30px; padding-bottom: 10px !important;}
	       *[class="image-icon-top"] {padding-bottom: 15px !important;}
	       *[class="icon"] {padding-bottom: 5px !important;}
	       *[class="logo"] {padding-top: 20px !important;}
	       *[class="logo-alt"] {padding-top: 0px !important;}
	       *[class="yolo"] {width:87% !important; height:auto !important; display: block; margin-bottom: 20px;}
	   }
	   
	</style>
	<style class="icloud-message-dynamic-styles">
	img._auto-scale, img._stretch {max-width: 674px !important;width: auto !important; height: auto !important; } span.body-text-content {white-space:pre-wrap;} iframe.attachment-pdf {width: 669px; height:1045px;}._stretch {max-width: 674px  ; } ._mail-body {width:674px; }
	</style>
	<title></title>
</head>
<body style="width:100% !important; min-width: 100% !important; margin: 0; padding: 0;">
	<table bgcolor="#282A2D" border="0" cellpadding="0" cellspacing="0" class="100p" style="background-color: #282a2d; min-width: 100%; width: 100%;" width="100%">
		<tbody>
			<tr>
				<td align="center" class="container-mobile-header" style="padding: 20px 40px;" valign="top">
					<table border="0" cellpadding="0" cellspacing="0" class="100p" style="width: 600px;" width="600">
						<tbody>
							<tr>
								<td align="center" class="header" valign="top">
									<a href="' . getServerPath(true) . '" rel="noopener noreferrer"><img border="0" src="' . $GLOBALS['PHPMAILER-logo'] . '" style="display: block;" width="50%"></a>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
	<table bgcolor="#404952" border="0" cellpadding="0" cellspacing="0" class="100p" style="background-color: #404952; min-width: 100%;" width="100%">
		<tbody>
			<tr>
				<td align="center" class="container-mobile" style="padding: 20px 25px 0px 25px;" valign="top">
					<table border="0" cellpadding="0" cellspacing="0" class="100p" style="width: 600px;" width="600">
						<tbody>
							<tr>
								<td align="center" class="title" style="font-size: 34px; line-height: 40px; font-family: \'Open Sans\', Helvetica, Arial, sans-serif; color: #e5a00d; font-weight: 300; letter-spacing: -1px; padding-bottom: 20px;" valign="top">
									<div class="100p" style="width: 400px;">
										' . $subject . '
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
	<table bgcolor="#FFFFFF" border="0" cellpadding="0" cellspacing="0" class="100p" style="background-color: #ffffff; min-width: 100%;" width="100%">
		<tbody>
			<tr>
				<td align="center" class="container-mobile-flush" style="padding-top: 60px; padding-right: 25px; padding-left: 25px;" valign="top">
					<table border="0" cellpadding="0" cellspacing="0" class="100p" style="width: 600px;" width="600">
						<tbody>
							<tr>
								<td align="center" style="font-size: 15px; line-height: 20px; font-family: \'Roboto\', Helvetica, Arial, sans-serif; color: #868c96; font-weight: 400; padding-bottom: 20px;" valign="top">' . $body . '</td>
							</tr>
							' . $button . '
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
	<table bgcolor="#3F4245" border="0" cellpadding="0" cellspacing="0" class="100p" style="background-color: #3f4245; min-width: 100%;" width="100%">
		<tbody>
			<tr>
				<td align="center" class="container-mobile" style="padding: 60px 25px 60px 25px;" valign="top">
					<table border="0" cellpadding="0" cellspacing="0" class="100p" style="width: 600px;" width="600">
						<tbody>
							<tr>
								<td align="center" style="font-size: 14px; line-height: 20px; font-family: \'Roboto\', Helvetica, Arial, sans-serif; color: #65686a; font-weight: 400; padding-top: 30px;" valign="top">Powered By: Organizr</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
</body>
</html>
';
