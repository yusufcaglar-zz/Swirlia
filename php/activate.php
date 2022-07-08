<?php
require_once 'DBOperations.php';

$db = new DBOperations();

$success = "\"<div style='background-color: white; margin-bottom: 10px; padding-top: 5em; padding-bottom: 5px;'>\"
				+\"<div id='table'>\"
					+\"<img src='https://swirlia.net/images/email_verified.png' style='object-fit: contain; width: 195px; height: 195px;' />\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden; height: 100px;'></button>\"
				+\"</div>\"
				
				+\"<label id='status' style='text-align: center; font-family: Georgia, serif; font-size: large;'>Tebrikler! E-mailiniz onaylandı.</label>\"
			+\"</div>\"";
			
$invalid_parameter = "\"<div style='background-color: white; margin-bottom: 10px; padding-top: 5em; padding-bottom: 5px;'>\"
				+\"<div id='table'>\"
					+\"<img src='https://swirlia.net/images/invalid.png' style='object-fit: contain; width: 195px; height: 195px;' />\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden; height: 100px;'></button>\"
				+\"</div>\"
				
				+\"<label id='status' style='text-align: center; font-family: Georgia, serif; font-size: large;'>Geçersiz parametre.</label>\"
			+\"</div>\"";

$invalid_token = "\"<div style='background-color: white; margin-bottom: 10px; padding-top: 5em; padding-bottom: 5px;'>\"
				+\"<div id='table'>\"
					+\"<img src='https://swirlia.net/images/invalid.png' style='object-fit: contain; width: 195px; height: 195px;' />\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden; height: 100px;'></button>\"
				+\"</div>\"
				
				+\"<label id='status' style='text-align: center; font-family: Georgia, serif; font-size: large;'>Aktivasyon kodu geçersiz.</label>\"
			+\"</div>\"";

$failed_try_again = "\"<div style='background-color: white; margin-bottom: 10px; padding-top: 5em; padding-bottom: 5px;'>\"
				+\"<div id='table'>\"
					+\"<img src='https://swirlia.net/images/invalid.png' style='object-fit: contain; width: 195px; height: 195px;' />\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden;'></button>\"
					+\"<button style='visibility: hidden; height: 100px;'></button>\"
				+\"</div>\"
				
				+\"<label id='status' style='text-align: center; font-family: Georgia, serif; font-size: large;'>Başarısız oldu. Lütfen tekrar deneyiniz.</label>\"
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
			<title>Swirlia - Email Onaylama</title>
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
					width: 100%;
					min-height: calc(var(--vh) * 4);
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
				};

				function disableDragging(e) {
					e.preventDefault();
				}
			</script>
		</body>
	</html>
	<?php
	if (isset($_GET['email_token']) && !empty($_GET['email_token'])) {
		$email_token = $_GET['email_token'];

		if ($db -> checkTokenExist($email_token)) {
			if ($db -> activateAccount($email_token)) {
				$db -> updateSwirliaStatistics("verified_emails", "1");
				
				echo "<script type='text/javascript'>document.getElementById('div').style.backgroundColor = 'forestgreen'; document.getElementById('div').innerHTML = ".$success.";</script>"; 
			} else
				echo "<script type='text/javascript'>document.getElementById('div').style.backgroundColor = 'indianred'; document.getElementById('div').innerHTML = ".$failed_try_again.";</script>"; 
		} else
			echo "<script type='text/javascript'>document.getElementById('div').style.backgroundColor = 'indianred'; document.getElementById('div').innerHTML = ".$invalid_token.";</script>"; 
	} else
		echo "<script type='text/javascript'>document.getElementById('div').style.backgroundColor = 'indianred'; document.getElementById('div').innerHTML = ".$invalid_parameter.";</script>"; 
}
?>