<?php
switch ($extra) {
	case 'invite':
		$button = '
		<tr>
			<td class="divider sideborder" style="font-family:\'Helvetica\', Arial, sans-serif;color:#73747C;padding:0;text-align:left;border-left:1px solid #e9e9e9;border-right:1px solid #e9e9e9;background-color:#ffffff;">
				<table border="0" class="table-basic full-width" style="border-collapse:collapse;border:0px solid #000;width:100%;">
					<tbody>
						<tr>
							<td style="font-family: \'Helvetica\', Arial, sans-serif; color: #73747C; padding: 0; text-align: left;" width="50%">
								<hr style="margin-top: 10px; height: 1px; overflow: hidden; border: 0; border-bottom: 1px solid #e9e9e9;">
							</td>
							<td style="font-family: \'Helvetica\', Arial, sans-serif; color: #73747C; padding: 0; text-align: left;" width="175">
								<a class="button orange nowrap" href="' . getServerPath(true) . '?invite=' . $email['inviteCode'] . '" rel="noopener noreferrer" style="text-decoration:none;color:#ffffff;white-space:nowrap;display:inline-block;padding:6px 15px 6px 15px;font-size:16px;border-radius:3px;background-color:#E74D39;margin:0 15px;">Use Invite Code</a>
							</td>
							<td style="font-family: \'Helvetica\', Arial, sans-serif; color: #73747C; padding: 0; text-align: left;" width="50%">
								<hr style="margin-top: 10px; height: 1px; overflow: hidden; border: 0; border-bottom: 1px solid #e9e9e9;">
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
        ';
		break;
	case 'reset':
		$button = '
		<tr>
			<td class="divider sideborder" style="font-family:\'Helvetica\', Arial, sans-serif;color:#73747C;padding:0;text-align:left;border-left:1px solid #e9e9e9;border-right:1px solid #e9e9e9;background-color:#ffffff;">
				<table border="0" class="table-basic full-width" style="border-collapse:collapse;border:0px solid #000;width:100%;">
					<tbody>
						<tr>
							<td style="font-family: \'Helvetica\', Arial, sans-serif; color: #73747C; padding: 0; text-align: left;" width="50%">
								<hr style="margin-top: 10px; height: 1px; overflow: hidden; border: 0; border-bottom: 1px solid #e9e9e9;">
							</td>
							<td style="font-family: \'Helvetica\', Arial, sans-serif; color: #73747C; padding: 0; text-align: left;" width="175">
								<a class="button orange nowrap" href="' . getServerPath(true) . '" rel="noopener noreferrer" style="text-decoration:none;color:#ffffff;white-space:nowrap;display:inline-block;padding:6px 15px 6px 15px;font-size:16px;border-radius:3px;background-color:#E74D39;margin:0 15px;">Goto My Site</a>
							</td>
							<td style="font-family: \'Helvetica\', Arial, sans-serif; color: #73747C; padding: 0; text-align: left;" width="50%">
								<hr style="margin-top: 10px; height: 1px; overflow: hidden; border: 0; border-bottom: 1px solid #e9e9e9;">
							</td>
						</tr>
					</tbody>
				</table>
			</td>
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
 	<meta content="width=device-width" name="viewport">
 	<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
 	<title>Email</title>
 </head>
 
 
 
 
 <body style="margin:0;padding:0;width:100%;background-color:#f8f8f8;font-size:16px;line-height:22px;-webkit-font-smoothing:antialiased;">
	<table border="0" class="table-basic full-width" style="border-collapse:collapse;border:0px solid #000;width:100%;">
		<tbody>
			<tr>
				<td style="font-family: \'Helvetica\', Arial, sans-serif; color: #73747C; padding: 0; text-align: left;">
					<table align="center" border="0" class="table-basic wrapper-width" style="border-collapse:collapse;border:0px solid #000;width:592px;">
						<tbody>
							<tr>
								<td style="font-family: \'Helvetica\', Arial, sans-serif; color: #73747C; padding: 0; text-align: left;">
									<table border="0" class="table-basic full-width" style="border-collapse:collapse;border:0px solid #000;width:100%;">
										<tbody>
											<tr>
												<td class="topbar" style="font-family:\'Helvetica\', Arial, sans-serif;color:#73747C;padding:20px 0 10px 0;text-align:left;"></td>
											</tr>
											<tr>
												<td class="header side-border" style="font-family:\'Helvetica\', Arial, sans-serif;color:#a3a4a8;padding:50px 32px 32px 32px;text-align:left;background-color:#33363D;border-top-left-radius:4px;border-top-right-radius:4px;">
													<h3 style="margin: 10px 0 20px 0;">' . $subject . '</h3>
												</td>
											</tr>
											<tr>
												<td style="font-family: \'Helvetica\', Arial, sans-serif; color: #73747C; padding: 0; text-align: left;">
													<table border="0" class="table-basic full-width" style="border-collapse:collapse;border:0px solid #000;width:100%;">
														<tbody>
															<tr>
																<td class="body sideborder" style="font-family:\'Helvetica\', Arial, sans-serif;color:#73747C;padding:32px;text-align:left;border-left:1px solid #e9e9e9;border-right:1px solid #e9e9e9;background-color:#ffffff;font-size:15px;">
																	<p style="margin: 10px 0 20px 0;">' . $body . '</p><br>
																</td>
															</tr>
														</tbody>
													</table>
												</td>
											</tr>
											' . $button . '
											<tr>
												<td class="very-bottom" style="font-family:\'Helvetica\', Arial, sans-serif;color:#73747C;padding:0;text-align:left;font-size:14px;">
													<table border="0" cellspacing="0" class="full-width" style="width: 100%;">
														<tbody>
															<tr>
																<td class="round-bottom" style="font-family:\'Helvetica\', Arial, sans-serif;color:#73747C;padding:32px;text-align:left;border:1px solid #e9e9e9;border-top:0;border-radius:0 0 10px 10px;background-color:#ffffff;">
																	<table border="0" class="table-basic full-width" style="border-collapse:collapse;border:0px solid #000;width:100%;">
																		<tbody>
																			<tr>
																				<td style="font-family: \'Helvetica\', Arial, sans-serif; color: #73747C; padding: 0; text-align: left;" width="50%"><b>Powered By:</b> Organizr</td>
																			</tr>
																		</tbody>
																	</table>
																</td>
															</tr>
														</tbody>
													</table>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
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
