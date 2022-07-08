<?php
//header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Origin: https://swirlia.com');
header('Content-type: application/json');

if (array_key_exists('HTTP_ORIGIN', $_SERVER))
    $origin = $_SERVER['HTTP_ORIGIN'];
else if (array_key_exists('HTTP_REFERER', $_SERVER))
    $origin = $_SERVER['HTTP_REFERER'];
else
    $origin = $_SERVER['REMOTE_ADDR'];

if($origin === "https://swirlia.com") {
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
			
			$data = $_POST;

			if(isset($data["operation"]) && !empty($data["operation"])) {
				$operation = $data["operation"];

				//register
				if($operation == 'register') {
					if(isset($_SESSION["username"])) {
						if($db -> isAccountAvailable($_SESSION["id"])) {
							$response["result"] = "failure";
							$response["message"] = "User already logged in";
							$response["username"] = $_SESSION["username"];
							$response = json_encode($response);
						} else {
							session_unset();
							session_destroy();
							
							$response["result"] = "failure";
							$response["message"] = "This username has taken";
							$response = json_encode($response);
						}
					} else {
						if(isset($data["username"]) && !empty($data["username"]) && isset($data["password"])&& !empty($data["password"])
							&& isset($data["email"]) && !empty($data["email"]) && isset($data["gender"]) && !empty($data["gender"]) 
							&& isset($data["birthdate"]) && !empty($data["birthdate"])) {
							$username = $data["username"];
							$password = urldecode($data["password"]);
							$email = urldecode($data["email"]);
							$gender = $data["gender"];
							$birthdate = $data["birthdate"];
							
							if ($fun -> isEmailValid($email)) {
								if (!($fun -> usernameCheck($username)) || !($fun -> passwordCheck($password)))
									$response = $fun -> getMsgInvalidParam();
								else
									$response = $fun -> createNewUser($username, $password, $email, $gender ,$birthdate);
							} else
								$response = $fun -> getMsgInvalidEmail();
						} else
							$response = $fun -> getMsgInvalidParam();
					}
					
				//login	
				} else if ($operation == 'login') {
					if(isset($_SESSION["username"])) {
						if($fun -> isAccountAvailableForLogin($_SESSION["username"])) {
							$response["result"] = "failure";
							$response["message"] = "User already logged in";
							$response["username"] = $_SESSION["username"];
							$response = json_encode($response);
						} else {
							session_unset();
							session_destroy();
							
							$response["result"] = "failure";
							$response["message"] = "Invaild Login Credentials";
							$response = json_encode($response);
						}
					} else {
						if(isset($data["username"]) && !empty($data["username"]) && 
							isset($data["password"]) && !empty($data["password"])) {
							$username = urldecode($data["username"]);
							$password = urldecode($data["password"]);
							
							if (strpos($username, "@") === false) {
								if (!($fun -> usernameCheck($username)) || !($fun -> passwordCheck($password)))
									$response = $fun -> getMsgInvalidParam();
								else
									$response = $fun -> loginUserWithUsername($username, $password);
							} else {
								if (!($fun -> isEmailValid($username)) || !($fun -> passwordCheck($password)))
									$response = $fun -> getMsgInvalidParam();
								else
									$response = $fun -> loginUserWithEmail($username, $password);
							}
						} else
							$response = $fun -> getMsgInvalidParam();
					}
					
				//getPreferences	
				} else if ($operation == 'getPreferences') {
					if(isset($_SESSION["username"]))			
						$response = $fun -> getPreferences();
					else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}
					
				//setPreferences	
				} else if ($operation == 'setPreferences') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["type"]) && !empty($_POST["type"]) && isset($_POST["value"]) && !is_null($_POST["value"])) {
							$type = $_POST["type"];
							$value = $_POST["value"];
							
							if($type === "conversations_count" || $type === "followers_count" || $type === "register_date"
								 || $type === "last_seen_date" || $type === "sounds_enabled" || $type === "private_profile" 
								 || $type === "registered_access" || $type === "registered_message") {
								if($value === "0" || $value === "1")
									$response = $fun -> setPreferences($type, $value);
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
							
				//getBlacklist	
				} else if ($operation == 'getBlacklist') {
					if(isset($_SESSION["username"]))			
						$response = $fun -> getBlacklist();
					else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}
					
				//unblock	
				} else if ($operation == 'unblock') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["sno"]) && !is_null($_POST["sno"])) {
							$sno = $_POST["sno"];
							
							$response = $fun -> unblock($sno);
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}
						
				//getAccount	
				} else if ($operation == 'getAccount') {
					if(isset($_SESSION["username"]))			
						$response = $fun -> getAccount();
					else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}
					
				//verifyEmail	
				} else if ($operation == 'verifyEmail') {
					if(isset($_SESSION["username"]))			
						$response = $fun -> verifyEmail();
					else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}	
					
				//changeEmail	
				} else if ($operation == 'changeEmail') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["email_new"]) && !empty($_POST["email_new"])) {
							$email_new = urldecode($_POST["email_new"]);
							
							if (!($fun -> isEmailValid($email_new)))
								$response = $fun -> getMsgInvalidParam();
							else
								$response = $fun -> changeEmail($email_new);
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}		
					
				//changePassword	
				} else if ($operation == 'changePassword') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["oldPassword"]) && !empty($_POST["oldPassword"]) && isset($_POST["newPassword"]) && !empty($_POST["newPassword"])) {
							$oldPassword = urldecode($_POST["oldPassword"]);
							$newPassword = urldecode($_POST["newPassword"]);
							
							if (!($fun -> passwordCheck($oldPassword)) || !($fun -> passwordCheck($newPassword)) || ($oldPassword === $newPassword))
								$response = $fun -> getMsgInvalidParam();
							else
								$response = $fun -> changePassword($oldPassword, $newPassword);
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}		
					
				//changeGender	
				} else if ($operation == 'changeGender') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["gender"]) && !empty($_POST["gender"])) {
							$gender = $_POST["gender"];
							
							$response = $fun -> changeGender($gender);
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}		
					
				//changeBirthdate
				} else if ($operation == 'changeBirthdate') {
					if(isset($_SESSION["username"])) {
						if(isset($_POST["birthdate"]) && !empty($_POST["birthdate"])) {
							$birthdate = $_POST["birthdate"];
							
							$response = $fun -> changeBirthdate($birthdate);
						} else
							$response = $fun -> getMsgInvalidParam();
					} else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}		
					
				//deactivate
				} else if ($operation == 'deactivate') {
					if(isset($_SESSION["username"]))
						$response = $fun -> deactivate();
					else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}		
					
				//delete
				} else if ($operation == 'delete') {
					if(isset($_SESSION["username"]))
						$response = $fun -> deleteRequest();
					else {
						$response["result"] = "failure";
						$response["message"] = "User not logged in";
						$response = json_encode($response);
					}		
							
				//getSwirl	
				} else if ($operation == 'getSwirl') {
					if(isset($_SESSION["username"]))			
						$id = $_SESSION["id"];
					else
						$id = null;

					$response = $fun -> getSwirl($id);
					
				//getRandom	
				} else if ($operation == 'getRandom') {
					if(isset($_SESSION["username"]))			
						$id = $_SESSION["id"];
					else
						$id = null;
					
					$response = $fun -> getRandom($id);
					
				//search	
				} else if ($operation == 'search') {
					if(isset($_POST["keyword"]) && !is_null($_POST["keyword"])) {
						$keyword = urldecode($_POST["keyword"]);
						
						if(isset($_SESSION["username"]))			
							$id = $_SESSION["id"];
						else
							$id = null;
						
						$response = $fun -> search($id, $keyword);
					} else
						$response = $fun -> getMsgInvalidParam();
				
				//forgotPassword	
				} else if ($operation == 'forgotPassword') {
					if(isset($_SESSION["username"])) {
						if($fun -> isAccountAvailableForLogin($_SESSION["username"])) {
							$response["result"] = "failure";
							$response["message"] = "User already logged in";
							$response["username"] = $_SESSION["username"];
							$response = json_encode($response);
						} else {
							session_unset();
							session_destroy();
							
							$response["result"] = "failure";
							$response["message"] = "Invaild Login Credentials";
							$response = json_encode($response);
						}
					} else {
						if (isset($data["variable"]) && !empty($data["variable"])) {
							$variable = urldecode($data["variable"]);
							
							if (strpos($variable, "@") === false) {
								if ($fun -> usernameCheck($variable))
									$fun -> forgotPasswordWithUsername($variable);
							} else {
								if ($fun -> isEmailValid($variable))
									$fun -> forgotPasswordWithEmail($variable);
							}
							
							$response = "";
						} else
							$response = $fun -> getMsgInvalidParam();
					}
				} else
					$response = $fun -> getMsgInvalidParam();
			} else
				$response = $fun -> getMsgInvalidParam();
				
			echo $response;
		}
	} else if ($_SERVER['REQUEST_METHOD'] == 'GET')
		echo "GET";
} else
	echo "Cross-Site";
?>