<?php
require_once 'DBOperations.php';

class Functions {
	private $db;

	public function __construct() {
		  $this -> db = new DBOperations();
	}

	//INDEX
	public function createNewUser($username, $password, $email, $gender, $birthdate) {
		$db = $this -> db;
	   
		if ($db -> checkUniqueUsernameExist($username)) {
			$response["result"] = "failure";
			$response["message"] = "This username has taken";
			return json_encode($response);
		} else {
			if ($db -> checkEmailExist($email)) {
				$response["result"] = "failure";
				$response["message"] = "This email is already in use";
				return json_encode($response);
			} else {
				if($this -> validateDate($birthdate)) {
					if($this -> checkAge($birthdate)) {
						$anon_id = rand(10000000, 99999999);
				
						while($db -> checkAnonIdExist($anon_id))
							$anon_id = rand(10000000, 99999999);

						if($gender == "1")
							$gender = 1;
						else if($gender == "2")
							$gender = 2;
						else if($gender == "3")
							$gender = 3;
						else
							$gender = 1;
						
						$result = $db -> createNewUser($username, $password, $email, $gender, $birthdate, $anon_id);

						if ($result) {
							//Mail
							$subject = "Hoşgeldiniz";
							
							$message_p = "Merhaba, ".$username.",<br /><br />";
							$message_p .= "Swirlia için kayıt işleminiz tamamlanmıştır, siteyi kullanıcı adınız veya email adresinizle ve şifrenizi girerek kullanmaya başlayabilirsiniz.<br /><br />";
							$message_p .= "<font style='font-weight:bold; color:#ffffff;'>E-mailinizi Onaylayın</font> başlıklı mail ile gelecek aktivasyon linkini açarak mail adresinizi doğrulamanız hem güvenliğiniz hem de ileriki yeniliklerden en iyi şekilde faydalanabilmeniz için önemlidir.";
							
							$this -> send("welcome@swirlia.com", $email, $subject, null, "Hoşgeldiniz 🤗", $message_p, "3");
							//Mail
							
							//Mail
							$email_token = sha1(rand());
							
							$subject = "E-mailinizi Onaylayın";
							
							$message_p = "Merhaba, ".$username.",<br /><br />";
							$message_p .= "Güvenliğiniz ve ileride çıkacak olan en son özelliklerden en iyi şekilde faydalanabilmeniz için aşağıdaki bağlantıyı açarak e-mailinizi onaylamanız gerekmektedir.<br /><br />";
							$message_p .= "<a style='color:#05c7f2;' href='https://swirlia.net/php/activate.php?email_token=".$email_token."'>Aktivasyon Bağlantısı</a>";
							
							$this -> send("verification@swirlia.com", $email, $subject, "https://swirlia.net/images/email_verified_light.png", "E-mailinizi Onaylayın", $message_p, "3");
							
							$db -> createEmailToken($email_token, $email);
							//Mail
							
							$db -> updateSwirliaStatistics("registered_users", "1");
							
							$response["result"] = "success";
							$response["message"] = "User Registered Successfully";
							return json_encode($response);
						} else {
							$response["result"] = "failure";
							$response["message"] = "Registration Failure";
							return json_encode($response);
						}
					} else {
						$response["result"] = "failure";
						$response["message"] = "User's age must be between 14-100";
						return json_encode($response);
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "Invalid date time";
					return json_encode($response);
				}
			}
		}
	}

	public function loginUserWithUsername($username, $password) {
		$db = $this -> db;

		if ($db -> checkUsernameExist($username)) {
			if ($db -> isAccountAvailableForLogin($username)) {
				if ($db -> loginUser($username, $password)) {
					$db -> updateSwirliaStatistics("logins", "1");
					
					$response["result"] = "success";
					$response["message"] = "Login Sucessful";
					return json_encode($response);
				} else {
					$db -> updateSwirliaStatistics("invalid_logins", "1");
					
					$response["result"] = "failure";
					$response["message"] = "Invaild Login Credentials";
					return json_encode($response);
				}
			} else {
				$db -> updateSwirliaStatistics("invalid_logins", "1");
				
				$response["result"] = "failure";
				$response["message"] = "Invaild Login Credentials";
				return json_encode($response);
			}
		} else {
			$db -> updateSwirliaStatistics("invalid_logins", "1");
			
			$response["result"] = "failure";
			$response["message"] = "Invaild Login Credentials";
			return json_encode($response);
		}
	}
	
	public function loginUserWithEmail($email, $password) {
		$db = $this -> db;

		if ($db -> checkEmailExist($email)) {
			$username = $db -> getUsernameFromEmail($email);
			
			if ($db -> isAccountAvailableForLogin($username)) {
				if ($db -> loginUser($username, $password)) {
					$db -> updateSwirliaStatistics("logins", "1");
					
					$response["result"] = "success";
					$response["message"] = "Login Sucessful";
					return json_encode($response);
				} else {
					$db -> updateSwirliaStatistics("invalid_logins", "1");
					
					$response["result"] = "failure";
					$response["message"] = "Invaild Login Credentials";
					return json_encode($response);
				}
			} else {
				$db -> updateSwirliaStatistics("invalid_logins", "1");
				
				$response["result"] = "failure";
				$response["message"] = "Invaild Login Credentials";
				return json_encode($response);
			}
		} else {
			$db -> updateSwirliaStatistics("invalid_logins", "1");
			
			$response["result"] = "failure";
			$response["message"] = "Invaild Login Credentials";
			return json_encode($response);
		}
	}

	public function getPreferences() {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id()))		
			return $db -> getPreferences($_SESSION["id"]);
		else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not logged in";
			return json_encode($response);
		}
	}
	
	public function setPreferences($type, $value) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if ($db -> setPreferences($_SESSION["id"], $type, $value)) {
				$response["result"] = "success";
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not logged in";
			return json_encode($response);
		}
	}
	
	public function getBlacklist() {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {		
			if($blacklist = $db -> getBlacklist($_SESSION["id"])) {
				$response["result"] = "success";
				$response["blacklist"] = $blacklist;
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				$response["message"] = "Blacklist is empty";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not logged in";
			return json_encode($response);
		}
	}
	
	public function unblock($sno) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($db -> unblock($sno)) {
				$db -> updateSwirliaStatistics("unblocks", "1");
				
				$response["result"] = "success";
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	public function getAccount() {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($account = $db -> getAccount($_SESSION["id"])) {
				$response["result"] = "success";
				$response["account"] = $account;
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not logged in";
			return json_encode($response);
		}
	}
	
	public function verifyEmail() {
		$db = $this -> db;
		
		if ($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if ($data = $db -> getEmailVerifyData($_SESSION["id"])) {
				if ($data["email_verified"] == 0) {
					if (time() - strtotime($data["email_lastSent"]) >= 3600) {
						if (isset($data["email_token"]))
							$email_token = $data["email_token"];	
						else
							$email_token = sha1(rand());
						
						//Mail							
						$subject = "E-mailinizi Onaylayın";
						
						$message_p = "Merhaba, ".$_SESSION["username"].",<br /><br />";
						$message_p .= "Güvenliğiniz ve ileride çıkacak olan en son özelliklerden en iyi şekilde faydalanabilmeniz için aşağıdaki bağlantıyı açarak e-mailinizi onaylamanız gerekmektedir.<br /><br />";
						$message_p .= "<a style='color:#05c7f2;' href='https://swirlia.net/php/activate.php?email_token=".$email_token."'>Aktivasyon Bağlantısı</a>";
						
						$this -> send("verification@swirlia.com", $_SESSION["email"], $subject, "https://swirlia.net/images/email_verified_light.png", "E-mailinizi Onaylayın", $message_p, "1");
						//Mail
						
						$db -> updateEmailLastSent($email_token, $_SESSION["id"]);
						
						$response["result"] = "success";
						return json_encode($response);
					} else {
						$response["result"] = "failure";
						$response["message"] = "1 Hour not passed since last request. Remaining time in seconds: ".(3600 - (time() - strtotime($data["email_lastSent"])));
						return json_encode($response);
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "Email already verified";
					return json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "Server failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not logged in";
			return json_encode($response);
		}
	}
	
	public function changeEmail($email_new) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if ($data = $db -> getChangeEmailVerifyData($_SESSION["id"])) {
				if (strtolower($data["email"]) !== strtolower($email_new)) {
					if (!($db -> checkEmailExist($email_new))) {
						if (time() - strtotime($data["email_change_lastSent"]) >= 43200) {
							$email_change_token = sha1(rand());
							
							//Mail							
							$subject = "E-mail Değişim Talebi";
							
							$message_p = "Merhaba, ".$_SESSION["username"].",<br /><br />";
							$message_p .= "Hesabınızın e-mailinin artık bu e-mail adresi olması için bir başvuru aldık.<br />";
							$message_p .= "Eğer bu işlem sizin tarafınızdan gerçekleştirilmediyse lütfen bu maili görmezden geliniz. Aksi takdirde aşağıdaki bağlantıyı açarak talebi onaylayınız.<br /><br />";
							$message_p .= "<a style='color:#05c7f2;' href='https://swirlia.net/php/change_email.php?email_change_token=".$email_change_token."'>Aktivasyon Bağlantısı</a>";
							
							$this -> send("verification@swirlia.com", $email_new, $subject, "https://swirlia.net/images/email_changed_light.png", "E-mail Değişim Talebi", $message_p, "1");
							//Mail
							
							$db -> updateChangeEmailLastSent($email_change_token, $email_new, $_SESSION["id"]);
							
							$db -> updateSwirliaStatistics("email_change_requests", "1");
							
							$response["result"] = "success";
							return json_encode($response);
						} else {
							$response["result"] = "failure";
							$response["message"] = "12 Hour not passed since last request. Remaining time in seconds: ".(43200 - (time() - strtotime($data["email_change_lastSent"])));
							return json_encode($response);
						}
					} else {
						$response["result"] = "failure";
						$response["message"] = "Email is already in use";
						return json_encode($response);
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "Emails are same";
					return json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "Server failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not logged in";
			return json_encode($response);
		}
	}
	
	public function changePassword($oldPassword, $newPassword) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if ($db -> changePassword($_SESSION["id"], $oldPassword, $newPassword)) {
				
				if ($db -> isEmailVerified($_SESSION["id"])) {					
					//Mail					
					$subject = "Şifreniz Değiştirildi";
					
					$message_p = "Merhaba, ".$_SESSION["username"].",<br /><br />";
					$message_p .= "Az önce hesabınızın şifresi değiştirildi.<br />";
					$message_p .= "Eğer bu işlem sizin tarafınızdan gerçekleştirilmediyse sitede ana sayfada sağ üstte bulunan <font style='color:#ffffff; font-weight:bold;'>Şifrenizi mi unuttunuz?</font> yazısına tıklayarak ve kullanıcı adınızı veya e-mail adresinizi girerek şifrenizi sıfırlama talebinde bulunabilirsiniz.";
					
					$this -> send("notify@swirlia.com", $_SESSION["email"], $subject, "https://swirlia.net/images/reset_password_light.png", "Şifreniz Değiştirildi", $message_p, "2");
					//Mail
				}
				
				$db -> updateSwirliaStatistics("passwords_changed", "1");
				
				$response["result"] = "success";
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not logged in";
			return json_encode($response);
		}
	}
	
	public function changeGender($gender) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($gender == "1")
				$gender = 1;
			else if($gender == "2")
				$gender = 2;
			else if($gender == "3")
				$gender = 3;
			else
				$gender = 1;
						
			if ($db -> changeGender($_SESSION["id"], $gender)) {
				$response["result"] = "success";
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not logged in";
			return json_encode($response);
		}
	}
	
	public function changeBirthdate($birthdate) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($this -> validateDate($birthdate)) {
				if($this -> checkAge($birthdate)) {
					if ($db -> changeBirthdate($_SESSION["id"], $birthdate)) {
						$response["result"] = "success";
						return json_encode($response);
					} else {
						$response["result"] = "failure";
						return json_encode($response);
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "User's age must be between 14-100";
					return json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "Invalid date time";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not logged in";
			return json_encode($response);
		}
	}
	
	public function deactivate() {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($db -> deactivate($_SESSION["id"])) {
				if ($db -> isEmailVerified($_SESSION["id"])) {
					//Mail					
					$subject = "Hesabınız Donduruldu";
							
					$message_p = "Merhaba, ".$_SESSION["username"].",<br /><br />";
					$message_p .= "Az önce hesabınız donduruldu.<br />";
					$message_p .= "Dilediğiniz zaman hesabınıza tekrar giriş yaparak hesabınızı tekrar aktif hale getirebilirsiniz.";
					
					$this -> send("notify@swirlia.com", $_SESSION["email"], $subject, null, "Hesabınız Donduruldu 💢", $message_p, "3");
					//Mail
				}
				
				$db -> updateSwirliaStatistics("deactivated_users", "1");
				
				$response["result"] = "success";
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not logged in";
			return json_encode($response);
		}
	}
	
	public function deleteRequest() {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			$date = date('Y-m-d H:i:s', strtotime('+31 days'));
			$date = date_create($date);
			date_time_set($date, 0, 0, 0);
			$date = date_format($date, "Y-m-d H:i:s");
					
			if($db -> deleteRequest($_SESSION["id"], $date)) {
				if ($db -> isEmailVerified($_SESSION["id"])) {					
					//Mail					
					$subject = "Hesabınız İçin Silinme Talebi";
					
					$message_p = "Merhaba, ".$_SESSION["username"].",<br /><br />";
					$message_p .= "Az önce hesabınız için bir silinme talebi aldık.<br />";
					$message_p .= "Hesabınız donduruldu ve hesabınızın silinmesi için 30 gün boyunca hesabınıza giriş yapmamanız gerekmektedir.<br />";
					$message_p .= "Eğer silinme işlemini iptal etmek isterseniz hesabınıza giriş yapmanız yeterlidir.<br />";
					$message_p .= "Unutmayınız ki hesabınız bir defa silindikten sonra geri döndürülmesi mümkün değildir ve kullanılan kullanıcı adı bir daha alınamayacaktır.<br /><br />";
					$message_p .= "Hesabınızın silineceği tarih, ".$date."<br />";
					
					$this -> send("notify@swirlia.com", $_SESSION["email"], $subject, null, "Hesabınız İçin Silinme Talebi ⚠️", $message_p, "1");
					//Mail
				}
				
				$db -> updateSwirliaStatistics("delete_requests", "1");
				
				$response["result"] = "success";
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not logged in";
			return json_encode($response);
		}
	}
		
	public function getSwirl($id) {
		$db = $this -> db;
		
		$db -> updateSwirliaStatistics("swirled", "1");
		
		if($id) {
			if($db -> isAccountAvailable($id, session_id())) {
				if($swirl = $db -> getSwirl($id)) {
					$response["result"] = "success";
					$response["swirl"] = $swirl;
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					return json_encode($response);
				}
			} else {
				session_unset();
				session_destroy();
					
				$response["result"] = "failure";
				$response["message"] = "User not exists";
				return json_encode($response);
			}
		} else {
			if($swirl = $db -> getSwirl(null)) {
				$response["result"] = "success";
				$response["swirl"] = $swirl;

				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}	
		}
	}
	
	public function getRandom($id) {
		$db = $this -> db;
		
		$db -> updateSwirliaStatistics("swirled", "1");
		
		if($id) {
			if($db -> isAccountAvailable($id, session_id())) {
				if($username = $db -> getRandom($id)) {
					$response["result"] = "success";
					$response["username"] = $username;
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					return json_encode($response);
				}
			} else {
				session_unset();
				session_destroy();
					
				$response["result"] = "failure";
				$response["message"] = "User not exists";
				return json_encode($response);
			}
		} else {
			if($username = $db -> getRandom(null)) {
				$response["result"] = "success";
				$response["username"] = $username;
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}	
		}
	}
	
	public function search($id, $keyword) {
		$db = $this -> db;
		
		$db -> updateSwirliaStatistics("swirled", "1");
		
		if($id) {
			if($db -> isAccountAvailable($id, session_id())) {
				if($swirl = $db -> search($id, $keyword)) {
					$response["result"] = "success";
					$response["swirl"] = $swirl;
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					return json_encode($response);
				}
			} else {
				session_unset();
				session_destroy();
					
				$response["result"] = "failure";
				$response["message"] = "User not exists";
				return json_encode($response);
			}
		} else {
			if($swirl = $db -> search(null, $keyword)) {
				$response["result"] = "success";
				$response["swirl"] = $swirl;
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}	
		}
	}
	
	public function forgotPasswordWithUsername($username) {
		$db = $this -> db;

		if ($db -> checkUsernameExist($username)) {			
			$passwordForgotStatus = $db -> getForgotPasswordStatus($username, false);
			
			if ($passwordForgotStatus["result"] == "success") {
				//Mail
				$subject = "Şifrenizi mi Unuttunuz?";
				
				$message_p = "Merhaba, ".$passwordForgotStatus["username"].",<br /><br />";
				$message_p .= "Hesabınızın şifresini unuttuğunuza dair bir bildirim aldık.<br />";
				$message_p .= "Eğer böyle bir talepte bulunmadıysanız lütfen bu maili görmezden geliniz. Aksi halde aşağıdaki bağlantıya tıklayarak şifrenizi sıfırlayabilirsiniz.<br /><br />";
				$message_p .= "<a style='color:#05c7f2;' href='https://swirlia.net/php/pass_forgot.php?email=".$passwordForgotStatus["email"]."&password_token=".$passwordForgotStatus["password_token"]."'>Şifre Yenileme Bağlantısı</a>";
				
				$this -> send("forgot_password@swirlia.com", $passwordForgotStatus["email"], $subject, "https://swirlia.net/images/reset_password_light.png", "Şifrenizi mi Unuttunuz?", $message_p, "1");
				//Mail
				
				$db -> updateSwirliaStatistics("forgotten_passwords", "1");
			}
		}
	}
	
	public function forgotPasswordWithEmail($email) {
		$db = $this -> db;

		if ($db -> checkEmailExist($email)) {
			$passwordForgotStatus = $db -> getForgotPasswordStatus($email, true);
			
			if ($passwordForgotStatus["result"] == "success") {
				//Mail				
				$subject = "Şifrenizi mi Unuttunuz?";
				
				$message_p = "Merhaba, ".$passwordForgotStatus["username"].",<br /><br />";
				$message_p .= "Hesabınızın şifresini unuttuğunuza dair bir bildirim aldık.<br />";
				$message_p .= "Eğer böyle bir talepte bulunmadıysanız lütfen bu maili görmezden geliniz. Aksi halde aşağıdaki bağlantıya tıklayarak şifrenizi sıfırlayabilirsiniz.<br /><br />";
				$message_p .= "<a style='color:#05c7f2;' href='https://swirlia.net/php/pass_forgot.php?email=".$passwordForgotStatus["email"]."&password_token=".$passwordForgotStatus["password_token"]."'>Şifre Yenileme Bağlantısı</a>";
				
				$this -> send("forgot_password@swirlia.com", $passwordForgotStatus["email"], $subject, "https://swirlia.net/images/reset_password_light.png", "Şifrenizi mi Unuttunuz?", $message_p, "1");
				//Mail
				
				$db -> updateSwirliaStatistics("forgotten_passwords", "1");
			}
		}
	}
	
	//PROFILE
	public function getProfile($username) {
		$db = $this -> db;
		$isOnline = false;
		
		//Check user logged in
		if(isset($_SESSION["username"])) {
			if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
				$response["result"] = "success";
				$response["id"] = $_SESSION["id"];
				$response["username"] = $_SESSION["username"];
				
				$isOnline = true;
			} else {
				session_unset();
				session_destroy();
				
				$response["result"] = "failure";
			}
		} else
			$response["result"] = "failure";
		
		//Check requested user
		if($db -> checkUsernameExist($username)) {
			$id = $db -> getId($username);
			
			if($db -> isAccountAvailable($id, null)) {
				if($isOnline) {
					if(!($db -> isBlocked($_SESSION["id"], $id))) {
						if($username === $_SESSION["username"])
							$isSelf = true;
						else
							$isSelf = false;
							
						$response["followers"] = $db -> getFollowersCount($id, $isSelf);
						$response["conversations"] = $db -> getConversationsCount($id, $isSelf);
						$response["user_info"] = $db -> getUserInfo($username, $id, $isSelf, true, $_SESSION["id"]);
						if(!$isSelf)
							$response["isFollowing"] = $db -> isFollowing($id, $_SESSION["id"]);
						
						$db -> updateSwirliaStatistics("profiles_visited", "1");
					} else {
						$db -> updateSwirliaStatistics("profile_visits_failed", "1");
						
						$response["message"] = "User not exists";
					}
				} else {
					if($db -> isGuestAllowed($id)) {						
						$response["followers"] = $db -> getFollowersCount($id, false);
						$response["conversations"] = $db -> getConversationsCount($id, false);
						$response["user_info"] = $db -> getUserInfo($username, $id, false, false, null);
						
						$db -> updateSwirliaStatistics("profiles_visited", "1");
					} else {
						$db -> updateSwirliaStatistics("profile_visits_failed", "1");
						
						$response["message"] = "User not exists";
					}
				}
			} else {
				$db -> updateSwirliaStatistics("profile_visits_failed", "1");
				
				$response["message"] = "User not exists";
			}
		} else {
			$db -> updateSwirliaStatistics("profile_visits_failed", "1");
			
			$response["message"] = "User not exists";
		}
		
		return json_encode($response);
	}
	
	public function uploadPhoto($image, $type) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($db -> uploadPhoto()) {
				if($type === "image/png")
					$image = str_replace('data:image/png;base64,', '', $image);
				else if($type === "image/jpeg")
					$image = str_replace('data:image/jpeg;base64,', '', $image);
				else
					$image = str_replace('data:image/jpg;base64,', '', $image);
				
				$image = str_replace(' ', '+', $image);
				$image = base64_decode($image);
				$direction = "uploads/";
				file_put_contents($direction.md5($_SESSION["username"]).".png", $image);
				
				$response["result"] = "success";
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
			
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	public function removePhoto() {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($db -> removePhoto()) {
				$direction = "uploads/";
				
				if(file_exists($direction.md5($_SESSION["username"]).".png"))
					unlink($direction.md5($_SESSION["username"]).".png");
				
				$response["result"] = "success";
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}			
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	public function editBio($bio) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($db -> editBio($bio)) {
				$response["result"] = "success";
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}			
		} else {
			session_unset();
			session_destroy();
			
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	public function follow($id) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($db -> isAccountAvailable($id, null)) {
				if(!($db -> isBlocked($_SESSION["id"], $id))) {
					if(!($db -> isFollowing($id, $_SESSION["id"]))) {
						if($db -> follow($id, $_SESSION["id"])) {
							$db -> updateSwirliaStatistics("follows", "1");
							
							$response["result"] = "success";
							$response["isFollowing"] = "true";
							return json_encode($response);
						} else {
							$response["result"] = "success";
							$response["isFollowing"] = "false";
							return json_encode($response);
						}
					} else {
						if($db -> unfollow($id, $_SESSION["id"])) {
							$db -> updateSwirliaStatistics("unfollows", "1");
							
							$response["result"] = "success";
							$response["isFollowing"] = "false";
							return json_encode($response);
						} else {
							$response["result"] = "success";
							$response["isFollowing"] = "true";
							return json_encode($response);
						}
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "User not exists";
					return json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "User not exists";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	public function block($id) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($db -> isAccountAvailable($id, null)) {
				if(!($db -> isBlocked($_SESSION["id"], $id))) {
					if($db -> isFollowing($id, $_SESSION["id"])) {
						$db -> unfollow($id, $_SESSION["id"]);
						$db -> updateSwirliaStatistics("unfollows", "1");
					}
					
					if($db -> isFollowing($_SESSION["id"], $id)) {
						$db -> unfollow($_SESSION["id"], $id);
						$db -> updateSwirliaStatistics("unfollows", "1");
					}
					
					if($db -> block($id, $_SESSION["id"])) {
						$db -> updateSwirliaStatistics("blocks", "1");
						
						$response["result"] = "success";
						return json_encode($response);
					} else {
						$response["result"] = "failure";
						return json_encode($response);
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "User not exists";
					return json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "User not exists";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	public function report($id, $reason, $message, $material_type, $material) {
		$db = $this -> db;
		
		if(strlen($message) < 1)
			$message = null;
		if(strlen($material) < 1)
			$material = null;
		
		if($material_type === "image") {
			$random = substr(md5(rand()), 0, 7);
			$sent_material = "php/uploads/reports/".md5($_SESSION["username"])."_".$random.".png";
		} else
			$sent_material = $material;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($db -> isAccountAvailable($id, null)) {
				if(!($db -> isBlocked($_SESSION["id"], $id))) {
					if(!($db -> isReportExists($id, $_SESSION["id"], $reason, $message, $material))) {
						if($db -> report($id, $_SESSION["id"], $reason, $message, $sent_material)) {
							if($material_type === "image") {
								$material = str_replace('data:image/png;base64,', '', $material);
								$material = str_replace('data:image/jpeg;base64,', '', $material);
								$material = str_replace('data:image/jpg;base64,', '', $material);
								
								$material = str_replace(' ', '+', $material);
								$material = base64_decode($material);
								
								$direction = "uploads/reports/";
								
								file_put_contents($direction.md5($_SESSION["username"])."_".$random.".png", $material);
							}
							
							$db -> updateSwirliaStatistics("reports", "1");
							
							$response["result"] = "success";
							return json_encode($response);
						} else {
							$response["result"] = "failure";
							return json_encode($response);
						}
					} else {
						$response["result"] = "failure";
						$response["message"] = "Report already exists";
						return json_encode($response);
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "User not exists";
					return json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "User not exists";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	public function support($reason, $message) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if(!($db -> isSupportExists($_SESSION["id"], $reason, $message))) {
				if($db -> support($_SESSION["id"], $reason, $message)) {
					if ($db -> isEmailVerified($_SESSION["id"])) {
						if ($reason === "account")
							$reason = "Hesap";
						else if ($reason === "website")
							$reason = "Site Kullanımı";
						else if ($reason === "chat")
							$reason = "Sohbet Ekranı/Fonksiyonu";
						else if ($reason === "complain")
							$reason = "Şikayet";
						else if ($reason === "suggestion")
							$reason = "Öneri";
						else if ($reason === "privacy_policy")
							$reason = "Gizlilik Politikası ve Çerezler";
						else if ($reason === "user_agreement")
							$reason = "Kullanıcı Sözleşmesi";
						else
							$reason = "Lisans";
						
						//Mail
						$subject = "Destek Talebiniz Alındı";
						
						$message_p = "<font color:#ffffff;'>Merhaba, ".$_SESSION["username"].",</font><br /><br />";
						$message_p .= "<font color:#ffffff;'>Az önce tarafınızdan bir destek talebi aldık.</font><br />";
						$message_p .= "<font color:#ffffff;'>Mümkün olduğunca kısa bir sürede destek talebinizi bu mail adresi üzerinden yanıtlayacağız.</font><br />";
						$message_p .= "<font color:#ffffff;'>Lütfen başka e-mail adreslerinden gelen mailleri dikkate almayınız.</font><br />";
						$message_p .= "<font color:#ffffff;'>Destek talebiniz hakkındaki detaylar aşağıdadır.</font><br /><br />";
						$message_p .= "Sebep: ".$reason."<br />";
						$message_p .= "Mesaj: ".$message;
						
						$this -> send("support@swirlia.com", $_SESSION["email"], $subject, null, "Destek Talebiniz Alındı 🔧", $message_p, "3");
						//Mail
					}
					
					$db -> updateSwirliaStatistics("supports", "1");
					
					$response["result"] = "success";
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					return json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "Support already exists";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
		
	public function getFollowings() {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($followings = $db -> getFollowings($_SESSION["id"])) {
				$response["result"] = "success";
				$response["followings"] = $followings;
				return json_encode($response);
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	public function unfollow($id) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($db -> isAccountAvailable($id, null)) {
				if(!($db -> isBlocked($_SESSION["id"], $id))) {
					if($db -> isFollowing($id, $_SESSION["id"])) {
						if($db -> unfollow($id, $_SESSION["id"])) {
							$db -> updateSwirliaStatistics("unfollows", "1");
							
							$response["result"] = "success";
							return json_encode($response);
						} else {
							$response["result"] = "failure";
							return json_encode($response);
						}
					} else {
						$response["result"] = "failure";
						return json_encode($response);
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "User not exists";
					return json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "User not exists";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	public function keepAlive() {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($db -> keepAlive($_SESSION["id"])) {
				$response["result"] = "success";
				return json_encode($response);
			} else {
				session_unset();
				session_destroy();

				$response["result"] = "failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	public function logOffUser() {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
			if($db -> isOnlineUser($_SESSION["id"], false)) {
				if($db -> logOffUser($_SESSION["id"])) {
					$response["result"] = "success";
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					return json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	//CHAT
	public function getChat($username) {
		$db = $this -> db;
		$isSelf = false;
		$isOnline = false;
		
		//Check user logged in
		if(isset($_SESSION["username"])) {
			if($db -> isAccountAvailable($_SESSION["id"], session_id())) {
				$response["id"] = $_SESSION["id"];
				$response["username"] = $_SESSION["username"];
				$response["isOnline"]= "online";
				
				if($username === $_SESSION["username"])
					$isSelf = true;
				else
					$isSelf = false;
				
				$isOnline = true;
			} else {
				session_unset();
				session_destroy();
				
				//Ip
				$client  = @$_SERVER['HTTP_CLIENT_IP'];
				$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
				$remote  = $_SERVER['REMOTE_ADDR'];

				if(filter_var($client, FILTER_VALIDATE_IP))
					$ip = $client;
				else if(filter_var($forward, FILTER_VALIDATE_IP))
					$ip = $forward;
				else
					$ip = $remote;
				
				$response["id"] = $ip;
				$response["isOnline"]= "offline";
			}
		} else {			
			//Ip
			$client  = @$_SERVER['HTTP_CLIENT_IP'];
			$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
			$remote  = $_SERVER['REMOTE_ADDR'];

			if(filter_var($client, FILTER_VALIDATE_IP))
				$ip = $client;
			else if(filter_var($forward, FILTER_VALIDATE_IP))
				$ip = $forward;
			else
				$ip = $remote;
			
			$response["id"] = $ip;
			$response["isOnline"]= "offline";
		}
		
		//Check requested user
		$check = true;
		
		if(!$isSelf) {
			if($db -> checkUsernameExist($username)) {
				$id = $db -> getId($username);
				
				$data = $db -> getChatToken($id);
				
				if (is_object($data)) {
					$token = $data -> token;
					$receiver_phpsessid = $data -> phpsessid;
				
					if($db -> isAccountAvailable($id, $receiver_phpsessid)) {
						if($db -> isAccountAvailableForChat($id, false, $receiver_phpsessid)) {
							if($isOnline) {
								if($db -> isBlocked($_SESSION["id"], $id)) {
									$response["result"] = "failure";
									$response["message"] = "User not exists";
									$check = false;
								} else
									$response["requested_id"] = $id;
							} else {
								$data = $db -> isGuestAllowedForChat($id);
								
								if ($data -> registered_access !== "0") {
									$response["result"] = "failure";
									$response["message"] = "User not exists";
									$check = false;
								} else if ($data -> registered_message !== "0") {
									$response["result"] = "failure";
									$response["message"] = "User doesn't want to take messages from guests";
									$check = false;
								} else
									$response["requested_id"] = $id;
							}
						} else {
							$response["result"] = "failure";
							$response["message"] = "User is offline";
							$check = false;
						}
					} else {
						$response["result"] = "failure";
						$response["message"] = "User either not exists or offline";
						$check = false;
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "User is offline";
					$check = false;
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "User not exists";
				$check = false;
			}
		}
		
		//isBlocked
		if ($check && !$isSelf) {
			if ($db -> isBlockedChat($id, $response["id"])) {
				$response["result"] = "failure";
				$response["message"] = "User is offline";
				$check = false;
			}
		}
		
		//Token
		if($check) {
			if($isSelf) {
				if($db -> canCreateChatroom($_SESSION["id"])) {
					$response["result"] = "success";
					$response["message"] = "Room will be created";
				} else {
					$response["result"] = "failure";
					$response["message"] = "A room have been created already";
				}
			} else {
				if($db -> canJoinChatroom($id, $response["id"])) {
					if(!$token) {
						$response["result"] = "failure";
						$response["message"] = "Connection couldn't established";
					} else {
						//Ip
						$client  = @$_SERVER['HTTP_CLIENT_IP'];
						$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
						$remote  = $_SERVER['REMOTE_ADDR'];

						if(filter_var($client, FILTER_VALIDATE_IP))
							$ip = $client;
						else if(filter_var($forward, FILTER_VALIDATE_IP))
							$ip = $forward;
						else
							$ip = $remote;
						
						//Anon Id
						if($isOnline)
							$anon_id = $db -> getAnonId();
						else {
							$anon_id = rand(10000000, 99999999);
				
							while($db -> checkAnonIdExist($anon_id))
								$anon_id = rand(10000000, 99999999);
						}
						
						$anon_id = "anon-".$anon_id;
						
						$response["result"] = "success";
						$response["message"] = "Room will be created";
						$response["token"] = $token;
						$response["anon_id"] = $anon_id;
						$response["ip"] = $ip;
					}
				} else {
					$response["result"] = "failure";
					$response["message"] = "A room have been created already";
				}
			}
		}
		
		return json_encode($response);
	}
	
	public function getSessions($receiver, $sender) {
		$db = $this -> db;
		
		$receiver_phpsessid = $db -> getPhpsessid($receiver);
		if ($receiver_phpsessid) {
			$response["receiver_phpsessid"] = $receiver_phpsessid;
			
			if ($sender) {
				$sender_phpsessid = $db -> getPhpsessid($sender);
				
				if ($sender_phpsessid) {
					$response["sender_phpsessid"] = $sender_phpsessid;
					$response["result"] = "success";
					return json_encode($response);
				} else {
					$response["result"] = "failure";
					json_encode($response);
				}
			} else {
				$response["sender_phpsessid"] = null;
				$response["result"] = "success";
				return json_encode($response);
			}
		} else {
			$response["result"] = "failure";
			return json_encode($response);
		}
	}
	
	public function createToken($token) {
		$db = $this -> db;
		
		if($db -> isAccountAvailable($_SESSION["id"], null)) {
			if($db -> isAccountAvailableForChat($_SESSION["id"], true, session_id())) {
				$token = $db -> createToken($token);
				
				if(!$token) {
					$response["result"] = "failure";
					$response["message"] = "Token couldn't created";
					return json_encode($response);
					
				} else {
					$response["result"] = "success";
					return json_encode($response);
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "User is offline";
				return json_encode($response);
			}
		} else {
			session_unset();
			session_destroy();
				
			$response["result"] = "failure";
			$response["message"] = "User not exists";
			return json_encode($response);
		}
	}
	
	public function nullToken($token) {
		$this -> db -> nullToken($token);
	}
	
	public function endConversation($receiver, $sender) {
		$this -> db -> updateConversation($receiver, $sender, 0);
	}
	
	public function startConversation($receiver, $sender) {
		$db = $this -> db;
		
		if($db -> isConversationExist($receiver, $sender))
			$db -> updateConversation($receiver, $sender, 1);
		else {
			$db -> startConversation($receiver, $sender);
			$db -> increaseConversationCount($receiver);
			
			if (strpos($sender, ".") !== false)
				$db -> updateSwirliaStatistics("anon_chats", "1");
				
			$db -> updateSwirliaStatistics("chats", "1");
		}
	}
	
	public function blockChat($anon_name, $anon_ip, $id) {
		$db = $this -> db;
		
		if(!($db -> isBlockedChat($id, $anon_ip))) {
			$db -> blockChat($anon_name, $anon_ip, $id);
			$db -> updateSwirliaStatistics("blocks", "1");
		}
	}
		
	public function ensureChat($receiver_id, $receiver_phpsessid, $sender_ip, $sender_id, $sender_online, $sender_phpsessid) {
		$db = $this -> db;
		
		//Receiver
		$check = true;
		
		if($db -> isAccountAvailable($receiver_id, $receiver_phpsessid)) {
			if($db -> isAccountAvailableForChat($receiver_id, false, $receiver_phpsessid)) {
				if($sender_online) {
					if($db -> isBlocked($receiver_id, $sender_id)) {
						$response["result"] = "failure";
						$response["message"] = "User not exists";
						$check = false;
					}
				} else {
					$data = $db -> isGuestAllowedForChat($receiver_id);
					
					if ($data -> registered_access !== "0") {
						$response["result"] = "failure";
						$response["message"] = "User not exists";
						$check = false;
					} else if ($data -> registered_message !== "0") {
						$response["result"] = "failure";
						$response["message"] = "User doesn't want to take messages from guests";
						$check = false;
					}
				}
				
				if ($check) {
					if($db -> isBlockedChat($receiver_id, $sender_ip)) {
						$response["result"] = "failure";
						$response["message"] = "User not exists";
						$check = false;
					}
				}
			} else {
				$response["result"] = "failure";
				$response["message"] = "User is offline";
				$check = false;
			}
		} else {
			$response["result"] = "failure";
			$response["message"] = "User either not exists or offline";
			$check = false;
		}
		
		//Sender
		if($check) {
			if($sender_online) {
				if(!$db -> isAccountAvailable($sender_id, $sender_phpsessid)) {
					$response["result"] = "failure";
					$response["message"] = "Sender online status changed";
					$check = false;
				} else
					$response["result"] = "success";
			} else
				$response["result"] = "success";
		}
		
		return json_encode($response);
	}
	
	public function resetChats() {
		$this -> db -> resetChats();
	}
	
	//AUX Methods
	public function isAccountAvailableForLogin($username) {
		return $this -> db -> isAccountAvailableForLogin($username);
	}
	
	public function getId($username) {
		return $this -> db -> getId($username);
	}
	
	public function getIdFromAnonId($anon_id) {
		return $this -> db -> getIdFromAnonId($anon_id);
	}
	
	public function usernameCheck($username) {
		if(is_null($username) || empty($username))
			return false;
		
		///// Username check
		$allowed_characters = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
			"S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j",
			"k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "_", ".",
			"-", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
		$username_characters = str_split($username);

		$username_check = true;
		for ($i = 0; $i < count($username_characters); $i++) {
			$check = false;

			for ($j = 0; $j < count($allowed_characters); $j++) {
				if ($username_characters[$i] === $allowed_characters[$j])
					$check = true;
			}

			if (!$check) {
				$username_check = false;
				break;
			}
		}
		
		if (strlen($username) < 4 || strlen($username) > 16)
			return false;
		else if (strpos($username, ".html") !== false)
			return false;
		else
			return $username_check;
	}
	
	public function passwordCheck($password) {
		if (is_null($password) || empty($password))
			return false;
		
		////// Password check
		$upper_characters = ["A", "B", "C", "Ç", "D", "E", "F", "G", "Ğ", "H", "I", "İ", "J", "K", "L", "M",
			"N", "O", "Ö", "P", "Q", "R", "S", "Ş", "T", "U", "Ü", "V", "W", "X", "Y", "Z"];
		$number_characters = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
		$password_characters = str_split($password);

		$upperCheck = false;
		for ($i = 0; $i < count($password_characters); $i++) {
			for ($j = 0; $j < count($upper_characters); $j++) {
				if ($password_characters[$i] === $upper_characters[$j])
					$upperCheck = true;
			}
		}

		$numberCheck = false;
		for ($i = 0; $i < count($password_characters); $i++) {
			for ($j = 0; $j < count($number_characters); $j++) {
				if ($password_characters[$i] === $number_characters[$j])
					$numberCheck = true;
			}
		}
		
		if (strlen($password) < 8 || strlen($password) > 16)
			return false;
		else if (!$upperCheck || !$numberCheck)
			return false;
		else
			return true;
	}
	
	function checkAge($bd) {
		$split_bd = explode("-", $bd);
		$split_today = explode("-", date("Y-m-d"));
		
		if(($split_today[0] - $split_bd[0]) < 101 && ($split_today[0] - $split_bd[0]) >	13) {
			if((intval($split_today[0]) - intval($split_bd[0])) == 100) {
				if(($split_today[1] - $split_bd[1] < 0))
					return true;
				else if(($split_today[1] - $split_bd[1] == 0)) {
					if(($split_today[2] - $split_bd[2] < 0))
						return true;
					else
						return false;
				} else
					return false;
					
			} else if((intval($split_today[0]) - intval($split_bd[0])) == 14) {
				if(($split_today[1] - $split_bd[1] > 0))
					return true;
				else if(($split_today[1] - $split_bd[1] == 0)) {
					if(($split_today[2] - $split_bd[2] > 0))
						return true;
					else
						return false;
				} else
					return false;
			} else
				return true;
		} else
			return false;
	}
	
	function validateDate($date) {
		$format = 'Y-m-d';
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) === $date;
	}

	public function isEmailValid($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	public function isEmailExist($email) {
		if (filter_var($email, FILTER_VALIDATE_EMAIL))
			return $this -> db -> checkEmailExist($email);
		else
			return false;
	}
	
	public function getMsgInvalidParam() {
		$response["result"] = "failure";
		$response["message"] = "Invalid Parameters";
		return json_encode($response);
	}

	public function getMsgInvalidEmail() {
		$response["result"] = "failure";
		$response["message"] = "Invalid Email";
		return json_encode($response);
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
		
		if (mail($to, '=?utf-8?B?'.base64_encode($subject).'?=', $body, $headers, $parameters)) {
			$this -> db -> updateSwirliaStatistics("emails_sent", "1");
			
			return true;
		} else
			return false;
	}
}
?>