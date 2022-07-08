<?php
//header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Origin: https://swirlia.net:3000');
header('Content-type: application/json');

if (array_key_exists('HTTP_ORIGIN', $_SERVER))
    $origin = $_SERVER['HTTP_ORIGIN'];
else if (array_key_exists('HTTP_REFERER', $_SERVER))
    $origin = $_SERVER['HTTP_REFERER'];
else
    $origin = $_SERVER['REMOTE_ADDR'];

if($origin === "https://swirlia.net:3000" || $origin === "swirlia.net" || $origin === "37.148.209.124") {
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {		
		//POST
		if ($_POST) {
			if (isset($_POST["phpsessid"]) && !empty($_POST["phpsessid"])) {
				if (empty(session_id())) {
					session_id($_POST["phpsessid"]);
					session_start();
				} else {
					session_destroy();
					session_id($_POST["phpsessid"]);
					session_start();
				}

				require_once 'Functions.php';

				$fun = new Functions();
				
				$data = $_POST;
				
				if(isset($data["operation"]) && !empty($data["operation"])) {
					$operation = $data["operation"];
					
					//getChat
					if ($operation === 'getChat') {
						if(isset($data["username"]) && !empty($data["username"])) {
							$username = $data["username"];
							
							if (!($fun -> usernameCheck($username)))
								$response = $fun -> getMsgInvalidParam();
							else
								$response = $fun -> getChat($username);
						} else
							$response = $fun -> getMsgInvalidParam();
						
					//createToken
					} else if ($operation === 'createToken') {
						if(isset($data["token"]) && !empty($data["token"])) {
							$token = $data["token"];
							
							if (strlen($token) !== 20)
								$response = $fun -> getMsgInvalidParam();
							else if(isset($_SESSION["username"]))			
								$response = $fun -> createToken($token);
							else {
								$response["result"] = "failure";
								$response["message"] = "User not logged in";
								$response = json_encode($response);
							}
						} else
							$response = $fun -> getMsgInvalidParam();
					} else
						$response = $fun -> getMsgInvalidParam();
				} else
					$response = $fun -> getMsgInvalidParam();
				
				echo $response;
			}
			
		//JSON
		} else if (file_get_contents("php://input")) {
			require_once 'Functions.php';

			$fun = new Functions();
			
			$data = json_decode(file_get_contents("php://input"));
			
			if(isset($data -> operation) && !empty($data -> operation)) {
				$operation = $data -> operation;
				$password = $data -> password;
				
				if (hash_equals("!Fsc%vA>vtD5qahh", $password)) {
					//getSessions
					if ($operation === 'getSessions') {
						$receiver = $data -> receiver;
						$sender = $data -> sender;
						
						echo $fun -> getSessions($receiver, $sender);
					
					//nullToken
					} else if ($operation === 'nullToken') {
						$token = $data -> token;
							
						$fun -> nullToken($token);
					
					//startConversation
					} else if ($operation === 'startConversation') {
						$receiver = $data -> receiver;
						$sender = $data -> sender;
						
						$fun -> startConversation($receiver, $sender);
						
					//endConversation
					} else if ($operation === 'endConversation') {
						$receiver = $data -> receiver;
						$sender = $data -> sender;
						
						$fun -> endConversation($receiver, $sender);
						
					//block
					} else if ($operation === 'block') {
						$id = $data -> receiverId;
						$anon_name = $data -> anon_name;
						$anon_ip = $data -> anon_ip;
						
						echo $fun -> blockChat($anon_name, $anon_ip, $id);
						
					//ensureChat
					} else if ($operation === 'ensureChat') {
						$receiver_id = $data -> receiverId;
						$receiver_phpsessid = $data -> receiverPhpsessid;
						$sender_ip = $data -> senderIp;
						$sender_id = $data -> senderId;
						$sender_online = $data -> senderOnline;
						$sender_phpsessid = $data -> senderPhpsessid;
						
						echo $fun -> ensureChat($receiver_id, $receiver_phpsessid, $sender_ip, $sender_id, $sender_online, $sender_phpsessid);
							
					//resetChats
					} else if ($operation === 'resetChats')						
						$fun -> resetChats();
					else
						echo $fun -> getMsgInvalidParam();
				} else
					echo $fun -> getMsgInvalidParam();
			} else
				echo $fun -> getMsgInvalidParam();
		} else
			echo $fun -> getMsgInvalidParam();
	} else if ($_SERVER['REQUEST_METHOD'] == 'GET')
		echo "GET";
} else
	echo "Cross-Site";
?>