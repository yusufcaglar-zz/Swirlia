<?php
if (php_sapi_name() !== 'cli') {
    die('Can only be executed via CLI');
}

$host = 'localhost';
$user = 'root';
$pass = 'Q4!e\9rr7u83#t#A';
$db = 'swirlia';
$conn;

$conn = new PDO("mysql:host=".$host.";dbname=".$db, $user, $pass);
$conn -> exec("SET GLOBAL time_zone='+00:00';");

$sql = 'SELECT id, username, email, profile_img, delete_deadline FROM users WHERE deleted = :deleted AND delete_deadline IS NOT NULL';
$query = $conn -> prepare($sql);
$query -> execute(array(':deleted' => 0));

if ($query) {
	$result = $query -> fetchAll(PDO::FETCH_ASSOC);
	
	if (count($result) != 0) {
		for($i = 0; $i < count($result); $i++) {
			if(time() >= strtotime($result[$i]["delete_deadline"]))
				deleteUser($conn, $result[$i]["id"], $result[$i]["username"], $result[$i]["email"], $result[$i]["profile_img"]);
		}
	}
}

function deleteUser($conn, $id, $username, $email, $profile_img) {
	isEmailVerified($conn, $id, $username, $email);
	
	removeImages($conn, $id, $profile_img);
	
	$sql = 'UPDATE users SET deleted = :deleted, profile_img = :profile_img, 
	password_token = NULL, password_lastSent = NULL, email_token = NULL, email_lastSent = NULL, email_change_token = NULL, email_change_lastSent = NULL 
	WHERE id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id, ':deleted' => 1, ':profile_img' => "php/uploads/user.png"));
	
	$sql = 'DELETE FROM blacklist WHERE id = :id OR blocked_id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id));
	
	$sql = 'DELETE FROM follows WHERE id = :id OR followed_id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id));
	
	$sql = 'DELETE FROM preferences WHERE id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id));
	
	$sql = 'DELETE FROM reports WHERE id = :id OR reported_id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id));
	
	$sql = 'DELETE FROM restricted_users WHERE id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id));
	
	$sql = 'DELETE FROM support WHERE id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id));
	
	$sql = 'UPDATE swirlia_statistics SET `deleted_users` = `deleted_users` +1 ORDER BY sno DESC LIMIT 1;';
	$query = $conn -> prepare($sql);
	$query -> execute();
}

function removeImages($conn, $id, $profile_img) {
	if ($profile_img !== "php/uploads/user.png") {
		$explode = explode("/", $profile_img);
		
		if (file_exists("uploads/".$explode[2]))
			unlink("uploads/".$explode[2]);
	}
	
	$sql = 'SELECT material FROM reports WHERE (id = :id OR reported_id = :id) AND reason = :reason';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id, ':reason' => "Improper image"));

	if ($query) {
		$result = $query -> fetchAll(PDO::FETCH_ASSOC);
		
		if (count($result) != 0) {
			for($i = 0; $i < count($result); $i++) {
				$explode = explode("/", $result[$i]["material"]);
				
				if (file_exists("uploads/reports/".$explode[3]))
					unlink("uploads/reports/".$explode[3]);
			}
		}
	}
}

function isEmailVerified($conn, $id, $username, $email) {
	$sql = 'SELECT COUNT(*) FROM users WHERE email_verified = :email_verified AND id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':email_verified' => 1, ':id' => $id));

	if ($query) {
		$row_count = $query -> fetchColumn();
		
		if ($row_count != 0) {
			//Mail			
			$subject = "Hesabınız Silindi";
			
			$message_p = "Merhaba, ".$username.",<br /><br />";
			$message_p .= "Az önce hesabınız silindi.<br />";
			$message_p .= "Artık hesabın kullanılması mümkün değildir.<br />";
			$message_p .= "Sitemizi anonim olarak kullanmaya devam edebilirsiniz veya yeni bir hesap oluşturabilirsiniz.<br />";
			$message_p .= "Kullanıcı adınız artık bir daha alınamaz fakat e-mail adresi tekrar kullanılabilir.";
			
			send("notify@swirlia.com", $email, $subject, null, "Hesabınız Silindi ❌", $message_p, "3");
			//Mail
			
			$sql = 'UPDATE swirlia_statistics SET `emails_sent` = `emails_sent` +1 ORDER BY sno DESC LIMIT 1;';
			$query = $conn -> prepare($sql);
			$query -> execute();
		}
	}
}

function send($fromEmail, $to, $subject, $caption_img, $caption_label, $message_p, $priority) {
	$caption_img_display = "display: none;";
	
	if (!is_null($caption_img)) {
		$caption_img = "src='".$caption_img."'";
		$caption_img_display = "";
	} else
		$caption_img = "";
	
	$plain_body = $caption_label;
	$plain_body .= "\r\n\r\n\r\n";
	
	$explode = explode("<br />", $message_p);
	
	for ($i = 0; $i < count($explode); $i++) {
		if (strpos($explode[$i], "<font") !== false) {
			$explode_font = explode("</font>", $explode[$i]);
			
			if (count($explode_font) == 1) {
				$start_font = strpos($explode_font[0], "'>") + 2;

				$plain_body .= substr($explode_font[0], $start_font)."\r\n";
			} else if (count($explode_font) == 2) {
				if (strpos($explode[$i], "<font") === 0) {
					$start_font = strpos($explode_font[0], "'>") + 2;

					$plain_body .= substr($explode_font[0], $start_font).$explode_font[1]."\r\n";
				} else {
					$explode_font_nd = explode("<font", $explode[$i]);
					
					$start_font = strpos($explode_font[0], "'>") + 2;

					$plain_body .= $explode_font_nd[0].substr($explode_font[0], $start_font).$explode_font[1]."\r\n";
				}
			}
		} else if (strpos($explode[$i], "href=") !== false) {
			$start = strpos($explode[$i], "https://swirlia.net/php/activate.php?email_token=");
			$end = strrpos($explode[$i], "'");

			$plain_body .= substr($explode[$i], $start, $end - $start)."\r\n";
		} else
			$plain_body .= $explode[$i]."\r\n";
	}
	
	$html_body = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>";
	$html_body .= "<html xmlns='https://www.w3.org/1999/xhtml' xmlns:v='urn:schemas-microsoft-com:vml' xmlns:o='urn:schemas-microsoft-com:office:office'>";
	$html_body .= "<head>";
	$html_body .= "	<!--[if gte mso 9]><xml>";
	$html_body .= "	<o:OfficeDocumentSettings>";
	$html_body .= "	<o:AllowPNG/>";
	$html_body .= "	<o:PixelsPerInch>96</o:PixelsPerInch>";
	$html_body .= "	</o:OfficeDocumentSettings>";
	$html_body .= "	</xml><![endif]-->";
	$html_body .= "	<title>Swirlia - ".$subject."</title>";
	$html_body .= "	<link rel='icon' href='https://swirlia.com/images/swirl.png' />";
	$html_body .= "	<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
	$html_body .= "	<meta http-equiv='X-UA-Compatible' content='IE=edge'>";
	$html_body .= "	<meta name='viewport' content='width=device-width, initial-scale=1.0 '>";
	$html_body .= "	<meta name='format-detection' content='telephone=no'>";
	$html_body .= "	<!--[if !mso]><!-->";
	$html_body .= "	<link href='https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800' rel='stylesheet'>";
	$html_body .= "	<!--<![endif]-->";
	$html_body .= "	<style type='text/css'>";
	$html_body .= "		body {";
	$html_body .= "			margin: 0 !important;";
	$html_body .= "			padding: 0 !important;";
	$html_body .= "			-webkit-text-size-adjust: 100% !important;";
	$html_body .= "			-ms-text-size-adjust: 100% !important;";
	$html_body .= "			-webkit-font-smoothing: antialiased !important;";
	$html_body .= "		}";
	$html_body .= "		";
	$html_body .= "		img {";
	$html_body .= "			border: 0 !important;";
	$html_body .= "			outline: none !important;";
	$html_body .= "		}";
	$html_body .= "		";
	$html_body .= "		p {";
	$html_body .= "			Margin: 0px !important;";
	$html_body .= "			Padding: 0px !important;";
	$html_body .= "		}";
	$html_body .= "		";
	$html_body .= "		table {";
	$html_body .= "			border-collapse: collapse;";
	$html_body .= "			mso-table-lspace: 0px;";
	$html_body .= "			mso-table-rspace: 0px;";
	$html_body .= "		}";
	$html_body .= "		";
	$html_body .= "		td, a, span {";
	$html_body .= "			border-collapse: collapse;";
	$html_body .= "			mso-line-height-rule: exactly;";
	$html_body .= "		}";
	$html_body .= "		";
	$html_body .= "		.ExternalClass * {";
	$html_body .= "			line-height: 100%;";
	$html_body .= "		}";
	$html_body .= "		";
	$html_body .= "		.em_defaultlink a {";
	$html_body .= "			color: inherit !important;";
	$html_body .= "			text-decoration: none !important;";
	$html_body .= "		}";
	$html_body .= "		";
	$html_body .= "		span.MsoHyperlink {";
	$html_body .= "			mso-style-priority: 99;";
	$html_body .= "			color: inherit;";
	$html_body .= "		}";
	$html_body .= "		";
	$html_body .= "		span.MsoHyperlinkFollowed {";
	$html_body .= "			mso-style-priority: 99;";
	$html_body .= "			color: inherit;";
	$html_body .= "		}";
	$html_body .= "		";
	$html_body .= "		@media only screen and (min-width:481px) and (max-width:699px) {";
	$html_body .= "			.em_main_table {";
	$html_body .= "				width: 100% !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			.em_wrapper {";
	$html_body .= "				width: 100% !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			.em_hide {";
	$html_body .= "				display: none !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			.em_img {";
	$html_body .= "				width: 100% !important;";
	$html_body .= "				height: auto !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			.em_h20 {";
	$html_body .= "				height: 20px !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			.em_padd {";
	$html_body .= "				padding: 20px 10px !important;";
	$html_body .= "			}";
	$html_body .= "		}";
	$html_body .= "		";
	$html_body .= "		@media screen and (max-width: 480px) {";
	$html_body .= "			.em_main_table {";
	$html_body .= "				width: 100% !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			.em_wrapper {";
	$html_body .= "				width: 100% !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			.em_hide {";
	$html_body .= "				display: none !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			.em_img {";
	$html_body .= "				width: 100% !important;";
	$html_body .= "				height: auto !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			.em_h20 {";
	$html_body .= "				height: 20px !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			.em_padd {";
	$html_body .= "				padding: 20px 10px !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			.em_text1 {";
	$html_body .= "				font-size: 16px !important;";
	$html_body .= "				line-height: 24px !important;";
	$html_body .= "			}";
	$html_body .= "			";
	$html_body .= "			u + .em_body .em_full_wrap {";
	$html_body .= "				width: 100% !important;";
	$html_body .= "				width: 100vw !important;";
	$html_body .= "			}";
	$html_body .= "		}";
	$html_body .= "		";
	$html_body .= "		.caption_image {";
	$html_body .= "			".$caption_img_display;
	$html_body .= "		}";
	$html_body .= "	</style>";
	$html_body .= "</head>";
	$html_body .= "<body class='em_body' style='margin:0px; padding:0px;' bgcolor='#1e1e1e'>";
	$html_body .= "	<table class='em_full_wrap' valign='top' width='100%' cellspacing='0' cellpadding='0' border='0' bgcolor='#1e1e1e' align='center'>";
	$html_body .= "		<tbody>";
	$html_body .= "			<tr>";
	$html_body .= "				<td valign='top' align='center'>";
	$html_body .= "					<table class='em_main_table' style='width:700px;' width='700' cellspacing='0' cellpadding='0' border='0' align='center'>";
	$html_body .= "						<tbody>";
	$html_body .= "							<!--Banner section-->";
	$html_body .= "							<tr bgcolor='#000000'>";
	$html_body .= "								<td valign='top' align='center'>";
	$html_body .= "									<table width='100%' cellspacing='0' cellpadding='10' border='0' align='center'>";
	$html_body .= "										<tbody>";											
	$html_body .= "											<tr>";												
	$html_body .= "												<td valign='top' align='center'>";
	$html_body .= "													<a href='https://swirlia.com' style='display:inline-block; max-width:250px;' target='_blank' width=150>";
	$html_body .= "														<img class='em_img' alt='Swirlia' style='display:block; font-family:Arial, sans-serif; font-size:30px; line-height:34px; color:#ffffff; max-width:250px;' src='https://swirlia.com/images/swirlia.png' width='150' border='0' height='60' />";
	$html_body .= "													</a>";
	$html_body .= "												</td>";
	$html_body .= "											</tr>";
	$html_body .= "										</tbody>";
	$html_body .= "									</table>";
	$html_body .= "								</td>";
	$html_body .= "							</tr>";
	$html_body .= "							<!--Banner section-->";
	$html_body .= "							";
	$html_body .= "							<!--Content Text Section-->";
	$html_body .= "							<tr>";
	$html_body .= "								<td style='padding:35px 70px 30px;' class='em_padd' valign='top' align='center'>";
	$html_body .= "									<table width='100%' cellspacing='0' cellpadding='0' border='0' align='center'>";
	$html_body .= "										<tbody>";
	$html_body .= "											<tr>";
	$html_body .= "												<td class='em_h20' style='font-size:0px; line-height:0px; height:25px;' height='25'>";
	$html_body .= "													&nbsp;";
	$html_body .= "												</td>";
	$html_body .= "												<!--—this is space of 25px to separate two paragraphs ---->";
	$html_body .= "											</tr>";
	$html_body .= "											";
	$html_body .= "											<tr class='caption_image'>";
	$html_body .= "												<td valign='top' align='center'>";
	$html_body .= "													<img ".$caption_img." alt='' class='em_img' style='display:block; object-fit:contain; max-width:50px;' width='50' border='0' height='50' />";
	$html_body .= "												</td>";
	$html_body .= "											</tr>";
	$html_body .= "											";
	$html_body .= "											<tr>";
	$html_body .= "												<td class='em_h20' style='font-size:0px; line-height:0px; height:10px;' height='10'>";
	$html_body .= "													&nbsp;";
	$html_body .= "												</td>";
	$html_body .= "												<!--—this is space of 10px to separate two paragraphs ---->";
	$html_body .= "											</tr>";
	$html_body .= "											";
	$html_body .= "											<tr>";
	$html_body .= "												<td style='font-family:'Open Sans', Arial, sans-serif; font-size:20px; font-weight:bold; line-height:22px; color:white; letter-spacing:2px; padding-bottom:12px;' valign='top' align='center'>";
	$html_body .= "													<font style='font-weight:bold; color:#ffffff;'>".$caption_label."</font>";
	$html_body .= "												</td>";
	$html_body .= "											</tr>";
	$html_body .= "											";
	$html_body .= "											<tr>";
	$html_body .= "												<td class='em_h20' style='font-size:0px; line-height:0px; height:40px;' height='40'>";
	$html_body .= "													&nbsp;";
	$html_body .= "												</td>";
	$html_body .= "												<!--—this is space of 40px to separate two paragraphs ---->";
	$html_body .= "											</tr>";
	$html_body .= "											";
	$html_body .= "											<tr>";
	$html_body .= "												<td style='font-family:'Open Sans', Arial, sans-serif; font-size:16px; line-height:30px; color:#ffffff;' valign='top' align='center'>";
	$html_body .= "													<font style='color:#ffffff;'>".$message_p."</font>";
	$html_body .= "												</td>";
	$html_body .= "											</tr>";
	$html_body .= "											";
	$html_body .= "											<tr>";
	$html_body .= "												<td class='em_h20' style='font-size:0px; line-height:0px; height:25px;' height='25'>";
	$html_body .= "													&nbsp;";
	$html_body .= "												</td>";
	$html_body .= "												<!--—this is space of 25px to separate two paragraphs ---->";
	$html_body .= "											</tr>";
	$html_body .= "										</tbody>";
	$html_body .= "									</table>";
	$html_body .= "								</td>";
	$html_body .= "							</tr>";
	$html_body .= "							<!--Content Text Section-->";
	$html_body .= "							";
	$html_body .= "							<!--Footer Section-->";
	$html_body .= "							<tr>";
	$html_body .= "								<td style='padding:10px 30px;' class='em_padd' valign='top' bgcolor='#000000' align='center'>";
	$html_body .= "									<table width='100%' cellspacing='0' cellpadding='0' border='0' align='center'>";
	$html_body .= "										<tbody>";											
	$html_body .= "											<tr>";
	$html_body .= "												<td style='font-family:'Open Sans', Arial, sans-serif; font-size:11px; line-height:18px; color:#999999;' valign='top' align='center'>";
	$html_body .= "													<font style='color:#999999;'>Copyright © 2021 Swirlia, Tüm Hakları Saklıdır</font><br /><br />";
	$html_body .= "													<a href='https://swirlia.com/html/privacy_policy.html' target='_blank' style='color:#999999; text-decoration:underline;'>Çerezler ve Gizlilik Politikası</a> | <a href='https://swirlia.com/html/user_agreement.html' target='_blank' style='color:#999999; text-decoration:underline;'>Kullanıcı Sözleşmesi</a> | <a href='https://swirlia.com/html/license.html' target='_blank' style='color:#999999; text-decoration:underline;'>Lisans</a> | <a href='mailto:communication@swirlia.com' target='_blank' style='color:#999999; text-decoration:underline;'>İletişim</a>";
	$html_body .= "												</td>";
	$html_body .= "											</tr>";
	$html_body .= "										</tbody>";
	$html_body .= "									</table>";
	$html_body .= "								</td>";
	$html_body .= "							</tr>";
	$html_body .= "							<!--Footer Section-->";
	$html_body .= "						</tbody>";
	$html_body .= "					</table>";
	$html_body .= "				</td>";
	$html_body .= "			</tr>";
	$html_body .= "		</tbody>";
	$html_body .= "	</table>";
	$html_body .= "	";
	$html_body .= "	<div class='em_hide' style='white-space: nowrap; display: none; font-size:0px; line-height:0px;'>";
	$html_body .= "		&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;";
	$html_body .= "	</div>";
	$html_body .= "</body>";
	$html_body .= "</html>";
	
	$boundary = sha1(rand());
	
	$headers  = "From: Swirlia <".$fromEmail.">\r\n";
	$headers .= "X-Sender: ".$fromEmail."\r\n";
	$headers .= "Reply-To: communication@swirlia.com\r\n";
	$headers .= "Return-Path: communication@swirlia.com\r\n";
	$headers .= "Errors-To: root@swirlia.com\r\n";
	$headers .= "Subject: "."=?utf-8?B?".base64_encode($subject)."?="."\r\n";
	$headers .= "Content-Type: multipart/alternative; boundary=".$boundary."\r\n";
	$headers .= "X-Priority: ".$priority."\r\n";
	$headers .= "Date: ".date("D, d M Y H:i:s O")."\r\n";
	$headers .= "X-Mailer: PHP/".phpversion()."\r\n";
	$headers .= "MIME-Version: 1.0";
	
	$parameters = "-f ".$fromEmail;
	
	//Plain body
	$body = "--" . $boundary . "\r\n";
	$body .= "Content-type: text/plain; charset=utf-8\r\n\r\n";		
	
	$body .= $plain_body;
	
	//Html body
	$body .= "\r\n\r\n--" . $boundary . "\r\n";
	$body .= "Content-type: text/html; charset=utf-8\r\n";
	$body .= "Content-Transfer-Encoding: base64\r\n\r\n";
	
	$body .= chunk_split(base64_encode($html_body), 76, PHP_EOL);
	
	$body .= "\r\n\r\n--" . $boundary . "--";
	
	return mail($to, '=?utf-8?B?'.base64_encode($subject).'?=', $body, $headers, $parameters);
}
?>