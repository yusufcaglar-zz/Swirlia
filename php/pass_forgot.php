<?php
require_once 'DBOperations.php';
require_once 'Functions.php';

$db = new DBOperations();
$fun = new Functions();

$success = "\"<div style='background-color: white; margin-bottom: 10px; padding-top: 5em; padding-bottom: 5px;'>\"
				+\"<div id='table'>\"
					+\"<img style='display: none; object-fit: contain; width: 195px; height: 195px;' />\"
					+\"<img src='https://swirlia.net/images/reset_password.png' style='object-fit: contain; width: 50px; height: 50px;' />\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<div>\"
						+\"<input type='password' placeholder='Yeni şifrenizi giriniz' id='pass_first' maxlength='16' pattern='(?=.*\d)(?=.*[A-Z]).{8,}' title='Yeni şifreniz en az 8 en fazla 16 karakterden oluşmalıdır. Şifreniz en az 1 büyük harf ve 1 rakam içermelidir.' required>\"
						+\"<img id='pass_first_show' onclick='pass_first_show()' src='https://swirlia.net/images/visible_dark.png' style='display: absolute; float: right; margin-left: 15px; object-fit: contain; width: 40px; height: 40px; cursor: pointer;' />\"
					+\"</div>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<div>\"
						+\"<input type='password' placeholder='Yeni şifrenizi tekrar giriniz' id='pass_second' maxlength='16' pattern='.{8,}' title='Şifreler eşleşmelidir' required>\"
						+\"<img id='pass_second_show' onclick='pass_second_show()' src='https://swirlia.net/images/visible_dark.png' style='display: absolute; float: right; margin-left: 15px; object-fit: contain; width: 40px; height: 40px; cursor: pointer;' />\"
					+\"</div>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button id='send' onclick='check()'>Gönder</button>\"
					+\"<button style='visibility: hidden; height: 100px;'></button>\"
				+\"</div>\"
				
				+\"<label id='status' style='text-align: center; font-family: Georgia, serif; font-size: large;'>Yeni şifreniz en az 8 en fazla 16 karakterden oluşmalıdır. Şifreniz en az 1 büyük harf ve 1 rakam içermelidir.</label>\"
			+\"</div>\"";
			
$invalid_parameters = "\"<div style='background-color: white; margin-bottom: 10px; padding-top: 5em; padding-bottom: 5px;'>\"
				+\"<div id='table'>\"
					+\"<img src='https://swirlia.net/images/invalid.png' style='object-fit: contain; width: 195px; height: 195px;' />\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden; height: 100px;'></button>\"
				+\"</div>\"
				
				+\"<label id='status' style='text-align: center; font-family: Georgia, serif; font-size: large;'>Geçersiz parametre veya parametreler.</label>\"
			+\"</div>\"";

$invalid_token = "\"<div style='background-color: white; margin-bottom: 10px; padding-top: 5em; padding-bottom: 5px;'>\"
				+\"<div id='table'>\"
					+\"<img src='https://swirlia.net/images/invalid.png' style='object-fit: contain; width: 195px; height: 195px;' />\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden; height: 100px;'></button>\"
				+\"</div>\"
				
				+\"<label id='status' style='text-align: center; font-family: Georgia, serif; font-size: large;'>Geçersiz şifre değiştirme kodu.</label>\"
			+\"</div>\"";

$user_not_found = "\"<div style='background-color: white; margin-bottom: 10px; padding-top: 5em; padding-bottom: 5px;'>\"
				+\"<div id='table'>\"
					+\"<img src='https://swirlia.net/images/not_found.png' style='object-fit: contain; width: 195px; height: 195px;' />\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden; height: 100px;'></button>\"
				+\"</div>\"
				
				+\"<label id='status' style='text-align: center; font-family: Georgia, serif; font-size: large;'>Kullanıcı bulunamadı.</label>\"
			+\"</div>\"";

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	?>
	<html>
		<head>
			<base href="https://swirlia.com/" />
			<meta charset="utf-8" />
			<meta http-equiv="X-UA-Compatible" content="IE=edge" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0" />
			<link rel="icon" href="images/swirl.png" />
			<title>Swirlia - Şifrenizi sıfırlayın</title>
			<style>
				body {
					--vw: 10.24px;
					--vh: 6.57px;
					--body-min-width: 1024px;
					--body-min-height: 657px;
					width: 100vw;
					height: 100vh;
					min-width: var(--body-min-width);
					min-height: var(--body-min-height);
					overflow: auto;
					margin: 0px;
					padding: 0px;
					justify-content: center;
				}
				
				/* LOADER */
				.loader {
					position: absolute;
					top: calc(50% - 25px);
					left: calc(50% - 25px);
					transform: translate(-50%, -50%);
					border: 10px solid #f3f3f3;
					border-radius: 50%;
					border-top: 10px solid #3498db;
					width: 50px;
					height: 50px;
					-webkit-animation: spin 2s linear infinite; /* Safari */
					animation: spin 2s linear infinite;
				}

				@-webkit-keyframes spin {
					0% {
						-webkit-transform: rotate(0deg);
					}

					100% {
						-webkit-transform: rotate(360deg);
					}
				}

				@keyframes spin {
					0% {
						transform: rotate(0deg);
					}

					100% {
						transform: rotate(360deg);
					}
				}

				/* CONTAINER */
				#container {
					margin: 0px;
					padding: 0px;
					height: 100%;
					width: 100%;
					justify-content: center;
					display: none;
				}

				#div {
					background-color: #444444;
					align-self: center;
					margin-top: 10vh;
					position: absolute;
					display: flex;
				}
				
				#table {
					display: flex;
					flex-direction: column;
					align-items: center;
				}
				
				input[type=password], input[type=text] {
					border-radius: 15vh;
					outline: none;
					padding: 1em;
					height: 40px; 
					width: 400px;
					text-align: center;
				}
				
				input:focus {
					border-color: #04ADBF;
				}
				
				#send {
					height: 40px;
					width: 455px;
					background-color: dodgerblue;
					color: #F1F2F4;
					font-weight: bold;
					padding: 1em;
					cursor: pointer;
					border: none;
					border-radius: 15vh;
					box-shadow: 1px 1px #F2F2F2;
					transition: 0.4s;
					outline: none;
				}
					#send:hover {
						background-color: #F2F2F2;
						color: dodgerblue;
						box-shadow: 2px 2px dodgerblue;
						border: none;
					}

					#send:active {
						background-color: #F2F2F2;
						box-shadow: 1px 1px #264F73;
						transform: translate(1px, 1px);
						border: none;
					}

				/* HEADER */
				.header {
					width: 100%;
					height: 10vh;
					min-height: calc(var(--vh) * 10);
					margin: 0px;
					padding: 0px;
				}

					.header #iframe_header {
						width: 100%;
						height: 10vh;
						min-height: calc(var(--vh) * 10);
						margin: 0px;
						padding: 0px;
						border: none;
						min-height: 4em;
					}

				/* FOOTER */
				.footer {
					height: 4vh;
					min-height: calc(var(--vh) * 4);
					width: 100%;
					position: fixed;
					bottom: 0;
					left: 0;
				}

					.footer #iframe_footer {
						width: 100%;
						height: 100%;
						margin: 0px;
						padding: 0px;
						border: none;
					}
			</style>
		</head>
		<body>
			<!-- LOADER -->
			<div class="loader"></div>

			<!-- CONTAINER -->
			<div id="container">
				<!-- HEADER -->
				<header class="header">
					<iframe id="iframe_header" src="html/header.html" scrolling="no"> </iframe>
				</header>
				
				<div id="div"></div>
				
				<!-- FOOTER -->
				<footer class="footer">
					<iframe id="iframe_footer" src="html/footer.html" scrolling="no"> </iframe>
				</footer>
			</div>
			
			<script>
				var serverProcess = false;
			
				document.getElementById("iframe_header").onload = function() {
					document.getElementById("iframe_header").contentWindow.postMessage(window.top.location.host, '*');
				}
				
				function pass_first_show () {
					if (!serverProcess) {
						const pass_first = document.getElementById("pass_first");
						const pass_first_show = document.getElementById("pass_first_show");
						
						if (pass_first.type === "password") {
							pass_first.type = "text";
							pass_first_show.src = "https://swirlia.net/images/invisible_dark.png"
						} else {
							pass_first.type = "password";
							pass_first_show.src = "https://swirlia.net/images/visible_dark.png"
						}
					}
				}
				
				function pass_second_show () {
					if (!serverProcess) {
						const pass_second = document.getElementById("pass_second");
						const pass_second_show = document.getElementById("pass_second_show");
						
						if (pass_second.type === "password") {
							pass_second.type = "text";
							pass_second_show.src = "https://swirlia.net/images/invisible_dark.png"
						} else {
							pass_second.type = "password";
							pass_second_show.src = "https://swirlia.net/images/visible_dark.png"
						}
					}
				}
				
				function send_focused() {
					document.getElementById("send").style.border = "solid";
					document.getElementById("send").style.borderColor = "black";
					document.getElementById("send").style.borderWidth = "1px";
				}
				
				function send_blur() {
					document.getElementById("send").style.border = "none";
				}
				
				function check() {
					document.getElementById("send").style.border = "none";
					
					if (!serverProcess) {
						serverProcess = true;
						
						const pass_first = document.getElementById("pass_first");
						const pass_second = document.getElementById("pass_second");
						const pass_first_show = document.getElementById("pass_first_show");
						const pass_second_show = document.getElementById("pass_second_show");
						const send = document.getElementById("send");
						
						pass_first.readOnly = true;
						pass_second.readOnly = true;
						
						if (pass_first.value !== pass_second.value) {
							document.getElementById("status").innerHTML = "Şifreler eşleşmiyor. Lütfen eşleştiğinden emin olunuz.";
							document.getElementById("div").style.backgroundColor = "indianred";
							
							pass_first.readOnly = false;
							pass_second.readOnly = false;
							
							serverProcess = false;
						} else {
							<?php if (isset($_GET['email']) && !empty($_GET['email']) && isset($_GET['password_token']) && !empty($_GET['password_token'])) { ?>
								var params = "email=" + <?php echo '"'.$_GET["email"].'"' ?> + "&password_token=" + <?php echo '"'.$_GET["password_token"].'"' ?>
								+ "&pass_first=" + encodeURIComponent(pass_first.value);
								var xhr = new XMLHttpRequest();
								xhr.open('POST', 'https://swirlia.net/php/pass_forgot.php', true);
								xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
								xhr.withCredentials = true;
								xhr.send(params);
								
								xhr.onload = function () {
									var response = JSON.parse(this.response);
									
									if (response.result === "failure") {
										if (response.message === "Failed. Please try again") {
											document.getElementById("status").innerHTML = "Şifre değişimi başarısız oldu. Lütfen tekrar deneyiniz.";
											document.getElementById("div").style.backgroundColor = "orangered";
											
											pass_first.readOnly = false;
											pass_second.readOnly = false;
											
											serverProcess = false;
										} else if (response.message === "Invalid token") {
											document.getElementById('div').innerHTML = <?php echo $invalid_token ?>;
											document.getElementById("div").style.backgroundColor = "indianred";
										} else if (response.message === "User not found") {
											document.getElementById('div').innerHTML = <?php echo $user_not_found ?>;
											document.getElementById("div").style.backgroundColor = "indianred";
										} else if (response.message === "Password does not meet with requirements") {
											document.getElementById("status").innerHTML = "Şifre şartlara uymuyor.<br />Yeni şifreniz en az 8 en fazla 16 karakterden oluşmalıdır. Şifreniz en az 1 büyük harf ve 1 rakam içermelidir.";
											document.getElementById("div").style.backgroundColor = "indianred";
											
											pass_first.readOnly = false;
											pass_second.readOnly = false;
											
											serverProcess = false;
										} else {
											document.getElementById('div').innerHTML = <?php echo $invalid_parameters ?>;
											document.getElementById("div").style.backgroundColor = "indianred";
										}
									} else {
										for (var i = 0; i < document.getElementById("table").childNodes.length; i++) {
											if (document.getElementById("table").childNodes[i].style.visibility !== "hidden")
												document.getElementById("table").childNodes[i].style.display = "none";
										}
										
										document.getElementById("table").childNodes[0].style.display = "initial";
										document.getElementById("table").childNodes[0].src = "https://swirlia.net/images/success.gif";
										
										document.getElementById("status").innerHTML = "Şifreniz başarıyla değiştirildi. Artık yeni şifrenizle giriş yapabilirsiniz.";
										document.getElementById("div").style.backgroundColor = "forestgreen";
									}
								}
							<?php } ?>
						}
					}
				}
				
				window.onload = function (e) {
					var event = e || window.event, imgs, i;
					if (event.preventDefault) {
						imgs = document.getElementsByTagName('img');

						for (i = 0; i < imgs.length; i++) {
							imgs[i].onmousedown = disableDragging;
						}
					}
					
					//Display Page
					document.getElementsByClassName("loader")[0].style.display = "none";
					document.getElementById("container").style.display = "flex";
					
					if (document.body.contains(document.getElementById("pass_first"))) {
						document.getElementById("pass_first").focus();
						
						document.getElementById("send").onfocus = function () {
							send_focused();
						}
						
						document.getElementById("send").onblur = function () {
							send_blur();
						}
					}
					
					document.addEventListener('keyup', (e) => {
							if ((e.code === "Enter"  || e.code === "NumpadEnter") && document.body.contains(document.getElementById("pass_first")))
								check();
					});
				};

				function disableDragging(e) {
					e.preventDefault();
				}
			</script>
		</body>
	</html>
	<?php
	if (isset($_GET['email']) && !empty($_GET['email']) && isset($_GET['password_token']) && !empty($_GET['password_token'])) {
		$email = $_GET['email'];
		$password_token = $_GET['password_token'];
		
		if ($fun -> isEmailExist($email)) {
			if ($db -> checkPasswordTokenExist($email, $password_token))
				echo "<script type='text/javascript'>document.getElementById('div').innerHTML = ".$success.";</script>";
			else
				echo "<script type='text/javascript'>document.getElementById('div').style.backgroundColor = 'indianred'; document.getElementById('div').innerHTML = ".$invalid_token.";</script>"; 
		} else
			echo "<script type='text/javascript'>document.getElementById('div').style.backgroundColor = 'indianred'; document.getElementById('div').innerHTML = ".$user_not_found.";</script>"; 
	} else
		echo "<script type='text/javascript'>document.getElementById('div').style.backgroundColor = 'indianred'; document.getElementById('div').innerHTML = ".$invalid_parameters.";</script>"; 
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (isset($_POST["pass_first"]) && isset($_POST["email"]) && isset($_POST["password_token"]) && !empty($_POST["email"]) && !empty($_POST["password_token"])) {
		$email = $_POST["email"];
		$password_token = $_POST["password_token"];
		$pass_first = urldecode($_POST["pass_first"]);
		
		if ($fun -> passwordCheck($pass_first)) {
			if ($fun -> isEmailExist($email)) {
				if ($db -> checkPasswordTokenExist($email, $password_token)) {
					if ($db -> changeForgottenPassword($email, $pass_first, false)) {
						$db -> updateSwirliaStatistics("passwords_recovered", "1");
						
						$response["result"] = "success";
						echo json_encode($response);
					} else {
						$response["result"] = "failure";
						$response["message"] = "Failed. Please try again";
						echo json_encode($response);
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "Invalid token";
					echo json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "User not found";
				echo json_encode($response);
			}
		} else {
			$response["result"] = "failure";
			$response["message"] = "Password does not meet with requirements";
			echo json_encode($response);
		}
	} else {
		$response["result"] = "failure";
		$response["message"] = "Invalid Parameters";
		echo json_encode($response);
	}
}
?>