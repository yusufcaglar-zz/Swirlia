<?php
if (array_key_exists('HTTP_ORIGIN', $_SERVER))
    $origin = $_SERVER['HTTP_ORIGIN'];
else if (array_key_exists('HTTP_REFERER', $_SERVER))
    $origin = $_SERVER['HTTP_REFERER'];
else
    $origin = $_SERVER['REMOTE_ADDR'];

if($origin === "https://swirlia.com" || $origin === "https://swirlia.net:3000") {
	//header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Origin: '.$origin);
	header('Content-type: application/json');

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
			$db = new DBoperations();
			
			$data = $_POST;

			if(isset($data["operation"]) && !empty($data["operation"])) {
				$operation = $data["operation"];

				//isLoggedIn
				if($operation == 'isLoggedIn') {
					if(isset($_SESSION["username"])) {
						if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
							$response["result"] = "success";
							$response["username"] = $_SESSION["username"];
							$response = json_encode($response);
						} else {
							session_unset();
							session_destroy();
							
							$response["result"] = "failure";
							$response = json_encode($response);
						}
					} else {
						$response["result"] = "failure";
						$response = json_encode($response);
					}
					
				//getProfile
				} else if ($operation == 'getProfile') {
					if(isset($data["username"]) && !empty($data["username"])) {
						$username = $data["username"];
						
						if (!($fun -> usernameCheck($username)))
							$response = $fun -> getMsgInvalidParam();
						else
							$response = $fun -> getProfile($username);
					} else
						$response = $fun -> getMsgInvalidParam();
					
				//exit
				} else if ($operation == 'exit') {
					if (isset($_SESSION["username"])) {
						$fun -> logOffUser();
						
						session_unset();
						session_destroy();
					}
					
					$response = "";
					
				//uploadPhoto	
				} else if ($operation == 'uploadPhoto') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["photo"]) && !empty($_POST["photo"]) && isset($_POST["type"]) && !empty($_POST["type"])) {
							$image = $_POST["photo"];
							
							if(strlen($image) <= 1100000) {
								$type = $_POST["type"];
								
								if($type === "image/png" || $type === "image/jpg" || $type === "image/jpeg")
									$response = $fun -> uploadPhoto($image, $type);
								else
									$response = $fun -> getMsgInvalidParam();
							} else
								$response = $fun -> getMsgInvalidParam();
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}
					
				//removePhoto	
				} else if ($operation == 'removePhoto') {
					if(isset($_SESSION["username"]))
						$response = $fun -> removePhoto();
					else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}
					
				//editBio	
				} else if ($operation == 'editBio') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["bio"]) && isset($_POST["isNull"]) && !empty($_POST["isNull"])) {
							if($_POST["isNull"] === "true")
								$bio = null;
							else
								$bio = urldecode($_POST["bio"]);
							
							$response = $fun -> editBio($bio);
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}
							
				//follow	
				} else if ($operation == 'follow') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["id"]) && !empty($_POST["id"])) {
							$id = $_POST["id"];
							
							if($id !== $_SESSION["id"])
								$response = $fun -> follow($id);
							else
								$response = $fun -> getMsgInvalidParam();
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}
							
				//block	
				} else if ($operation == 'block') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["id"]) && !empty($_POST["id"])) {
							$id = $_POST["id"];
							
							if($id !== $_SESSION["id"])
								$response = $fun -> block($id);
							else
								$response = $fun -> getMsgInvalidParam();
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}
							
				//report	
				} else if ($operation == 'report') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["id"]) && ((!empty($_POST["id"]) && empty($_POST["is_anonim"])) || (empty($_POST["id"]) && !empty($_POST["is_anonim"])))
							&& isset($_POST["reason"]) && !empty($_POST["reason"]) && isset($_POST["message"])
							&& !is_null($_POST["message"]) && isset($_POST["material_type"]) && !is_null($_POST["material_type"])
							&& isset($_POST["material"]) && !is_null($_POST["material"]) && isset($_POST["is_anonim"]) && !is_null($_POST["is_anonim"])
							&& isset($_POST["anon_name"]) && !is_null($_POST["anon_name"])) {
							$id = $_POST["id"];
							$reason = $_POST["reason"];
							$message = urldecode($_POST["message"]);
							$material_type = $_POST["material_type"];
							$material = urldecode($_POST["material"]);
							$is_anonim = $_POST["is_anonim"];
							$anon_name = $_POST["anon_name"];
							
							if(strlen($message) <= 250) {
								if((($reason === "Improper image" || $reason === "Improper bio" || $reason === "Fake account") && $material_type === "" && $material === "")
									|| (($reason === "Improper image" || $reason === "Improper message" || $reason === "Other")
									&& (($material_type === "image" && strlen($material) > 0 && strlen($material) <= 600000)
									|| ($material_type === "message" && strlen($material) > 0 && strlen($material) <= 1024)) 
									&& ($is_anonim === "1" || $is_anonim === "2") && $fun -> usernameCheck($anon_name))) {
										if ($is_anonim === "1") {
											$id = $fun -> getIdFromAnonId($anon_name);
											
											if ($id == null)
												$id = "";
										} else if ($is_anonim === "2")
											$id = $fun -> getId($anon_name);
										
										if(strlen($id) > 0) {
											if($id !== $_SESSION["id"])
												$response = $fun -> report($id, $reason, $message, $material_type, $material);
											else
												$response = $fun -> getMsgInvalidParam();
										} else
											$response = $fun -> getMsgInvalidParam();
								} else
									$response = $fun -> getMsgInvalidParam();
							} else
								$response = $fun -> getMsgInvalidParam();
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}
				//support	
				} else if ($operation == 'support') {
					if (isset($_SESSION["username"])) {
						if (isset($_POST["reason"]) && !empty($_POST["reason"]) && isset($_POST["message"]) && !empty($_POST["message"])) {
							$reason = $_POST["reason"];
							$message = urldecode($_POST["message"]);
							
							if (strlen($message) <= 250 && strlen($message) >= 10) {
								if ($reason === "account" || $reason === "website" || $reason === "chat" || $reason === "complain" || $reason === "suggestion"
								 || $reason === "privacy_policy" || $reason === "user_agreement" || $reason === "license") {
									$response = $fun -> support($reason, $message);
								} else
									$response = $fun -> getMsgInvalidParam();
							} else
								$response = $fun -> getMsgInvalidParam();
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}
					
				//getFollowings	
				} else if ($operation == 'getFollowings') {
					if(isset($_SESSION["username"]))
						$response = $fun -> getFollowings();
					else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}	
							
				//unfollow	
				} else if ($operation == 'unfollow') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["id"]) && !empty($_POST["id"])) {
							$id = $_POST["id"];
							
							if($id !== $_SESSION["id"])
								$response = $fun -> unfollow($id);
							else
								$response = $fun -> getMsgInvalidParam();
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}	
							
				//keepAlive	
				} else if ($operation == 'keepAlive') {
					if(isset($_SESSION["username"]))
						$response = $fun -> keepAlive();
					else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}	
				} else
					$response = $fun -> getMsgInvalidParam();
			} else
				$response = $fun -> getMsgInvalidParam();
				
			echo $response;
		}
	} else if ($_SERVER['REQUEST_METHOD'] == 'GET')
		echo "GET";
}
?>