<?php
if(!isset($_SESSION))
		session_start();
?>
<html>
<head>
	<base href="/" />
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="icon" href="https://swirlia.com/images/swirl.png" />
	<title>Swirlia</title>
	<style type="text/css">
		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
		}
	</style>
</head>
<body>
<h2 style="text-align:center;">Sunucu onayınız alındı.</h2>
<h2 style="text-align:center;">Bu sekmeyi kapatıp Swirlia'yı kullanmaya devam edebilirsiniz.</h2>
<br />
<h3 id="h3" style="text-align:center;">Bu sayfa 5 saniye sonra otomatikmen kapanacaktır.</h3>

<script type="text/javascript">
	var counter = 4;
	
	var y = setInterval(function () {
		if (counter === 0)
			document.getElementById("h3").innerText = "";
		else {
			document.getElementById("h3").innerText = "Bu sayfa " + counter + " saniye sonra otomatikmen kapanacaktır.";
			counter--;
		}
    }, 1000);
</script>
</body>
</html>