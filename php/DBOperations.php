<?php
class DBOperations {
	private $host = 'localhost';
	private $user = 'root';
	private $pass = 'Q4!e\9rr7u83#t#A';
	private $db = 'swirlia';
	private $conn;

	public function __construct() {
		$this -> conn = new PDO("mysql:host=".$this -> host.";dbname=".$this -> db, $this -> user, $this -> pass);
		$this -> conn -> exec("set names utf8mb4");
		$this -> conn -> exec("SET GLOBAL time_zone='+00:00';");
	}

	//Index
	public function createNewUser($username, $password, $email, $gender, $birthdate, $anon_id) {		
		$hash = $this -> getHash($password);
		$encrypted_password = $hash["encrypted"];
		$salt = $hash["salt"];

		$sql = 'INSERT INTO users SET anon_id = :anon_id, username = :username,
			encrypted_password = :encrypted_password, salt = :salt, email = :email, gender = :gender, birthdate = :birthdate';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':anon_id' => $anon_id, ':username' => $username,':encrypted_password' => $encrypted_password,
			':salt' => $salt, ':email' => $email, ':gender' => $gender, ':birthdate' => $birthdate ));

		if ($query) {
			//Id
			$sql = 'SELECT id FROM users WHERE email = :email AND deleted = :deleted';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':email' => $email, ':deleted' => 0));
			$id = $query -> fetchObject() -> id;
			
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
			
			//Create Statistics
			$sql = 'INSERT INTO statistics SET id = :id, ip = :ip';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id, ':ip' => $ip));
			
			//Create Preferences
			$sql = 'INSERT INTO preferences SET id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));
			
			//Create online_users
			$this -> createOnlineUser($id, session_id());
			
			$_SESSION["id"] = $id;
			$_SESSION["email"] = $email;
			$_SESSION["username"] = $username;

			return true;
		} else
			return false;
	}

	public function loginUser($username, $password) {
		$sql = 'SELECT id, username AS real_username, email, encrypted_password, salt, deactivated FROM users WHERE username = :username AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':username' => $username, ':deleted' => 0));

		$data = $query -> fetchObject();
		$id = $data -> id;
		$email = $data -> email;
		$real_username = $data -> real_username;
		$db_encrypted_password = $data -> encrypted_password;
		$salt = $data -> salt;

		if ($this -> verifyHash($password.$salt, $db_encrypted_password)) {
			//Activate if deactivated
			if($data -> deactivated == 1)
				$this -> toggleAccountActivation($id, 0);
			
			//Log off if logged on
			if($this -> isOnlineUser($id, false))
				$this -> logOffUser($id);
		
			//Create online_users
			$this -> createOnlineUser($id, session_id());
			
			//Update last login
			$sql = 'UPDATE statistics SET last_login = :last_login WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id, ':last_login' => strval(date('Y-m-d H:i:s', time()))));
			
			//Update ip
			$client  = @$_SERVER['HTTP_CLIENT_IP'];
			$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
			$remote  = $_SERVER['REMOTE_ADDR'];

			if(filter_var($client, FILTER_VALIDATE_IP))
				$ip = $client;
			else if(filter_var($forward, FILTER_VALIDATE_IP))
				$ip = $forward;
			else
				$ip = $remote;
			
			$sql = 'SELECT ip FROM statistics WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));

			$total_ip = $query -> fetchObject() -> ip;
			$exploded_ips = explode(",", $total_ip);
			
			$check = true;
			for($j = 0; $j < count($exploded_ips); $j++) {
				if($ip === $exploded_ips[$j])
					$check = false;
			}
			
			if($check) {
				$total_ip = $total_ip.",".$ip;
				
				$sql = 'UPDATE statistics SET ip = :ip WHERE id = :id';
				$query = $this -> conn -> prepare($sql);
				$query -> execute(array(':id' => $id, ':ip' => $total_ip));
			}
			
			//Return
			$_SESSION["id"] = $id;
			$_SESSION["email"] = $email;
			$_SESSION["username"] = $real_username;
			
			if ($this -> isEmailVerified($_SESSION["id"]) && $data -> deactivated == 1) {
				//Mail					
				$subject = "Hesabınız Aktifleştirildi";
						
				$message_p = "Merhaba, ".$_SESSION["username"].",<br /><br />";
				$message_p .= "Az önce hesabınız tekrar aktif hale getirildi.<br />";
				$message_p .= "Dilediğiniz zaman profilinizde tercihler menüsünden tekrar hesabınızı dondurabilirsiniz.";
				
				$this -> send("notify@swirlia.com", $_SESSION["email"], $subject, null, "Hesabınız Aktifleştirildi 🌀", $message_p, "3");
				//Mail
			}
			
			return true;
		} else
			return false;
	}
	
	public function toggleAccountActivation($id, $deactivated) {
		$sql = 'UPDATE users SET deactivated = :deactivated, delete_deadline = :delete_deadline WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':deactivated' => $deactivated, ':delete_deadline' => null, ':id' => $id));
		
		if ($deactivated === 0)
			$this -> updateSwirliaStatistics("activated_users", "1");
	}

	public function getPreferences($id) {
		$sql = 'SELECT conversations_count, followers_count, register_date, last_seen_date, sounds_enabled, private_profile, 
		registered_access, registered_message FROM preferences WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		$data = $query -> fetchObject();
		
		$response["result"] = "success";
		$response["conversations_count"] = $data -> conversations_count;
		$response["followers_count"] = $data -> followers_count;
		$response["register_date"] = $data -> register_date;
		$response["last_seen_date"] = $data -> last_seen_date;
		$response["sounds_enabled"] = $data -> sounds_enabled;
		$response["private_profile"] = $data -> private_profile;
		$response["registered_access"] = $data -> registered_access;
		$response["registered_message"] = $data -> registered_message;
		
		return json_encode($response);
	}
	
	public function setPreferences($id, $type, $value) {
		if($type === "conversations_count")
			$sql = 'UPDATE preferences SET conversations_count = :value WHERE id = :id';
		else if($type === "followers_count")
			$sql = 'UPDATE preferences SET followers_count = :value WHERE id = :id';
		else if($type === "register_date")
			$sql = 'UPDATE preferences SET register_date = :value WHERE id = :id';
		else if($type === "last_seen_date")
			$sql = 'UPDATE preferences SET last_seen_date = :value WHERE id = :id';
		else if($type === "sounds_enabled")
			$sql = 'UPDATE preferences SET sounds_enabled = :value WHERE id = :id';
		else if($type === "private_profile")
			$sql = 'UPDATE preferences SET private_profile = :value WHERE id = :id';
		else if($type === "registered_access")
			$sql = 'UPDATE preferences SET registered_access = :value WHERE id = :id';
		else
			$sql = 'UPDATE preferences SET registered_message = :value WHERE id = :id';
		
		$query = $this -> conn -> prepare($sql);
		if ($query -> execute(array(':value' => $value, ':id' => $id)))
			return true;
		else
			return false;
	}
	
	public function getBlacklist($id) {	
		$sql = 'SELECT sno, blocked_id, anon_name FROM blacklist WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		
		if ($query) {
			$result = $query -> fetchAll(PDO::FETCH_ASSOC);
			
			if (count($result) != 0) {
				for($i = 0; $i<count($result); $i++) {
					$array = $result[$i];
					
					$blacklist[$i]["sno"] = $array["sno"];
					
					if ($array["blocked_id"] !== NULL) {
						$blacklist[$i]["name"] = $this -> getUsername($array["blocked_id"]);
						$blacklist[$i]["type"] = 0;
					} else {
						$blacklist[$i]["name"] = $array["anon_name"];
						$blacklist[$i]["type"] = 1;
					}
				}
				
				return $blacklist;
			} else 
				return false;
		} else 
			return false;
	}
	
	public function unblock($sno) {
		$sql = 'DELETE FROM blacklist WHERE sno = :sno';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':sno' => $sno));
		
		if($query){return true;}
		else {return false;}
	}
	
	public function getAccount($id) {	
		$sql = 'SELECT email, email_verified, gender, birthdate FROM users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		
		if ($query) {
			$data = $query -> fetchObject();
			
			$account["email"] = $data -> email;
			$account["email_verified"] = $data -> email_verified;
			$account["gender"] = $data -> gender;
			$account["birthdate"] = $data -> birthdate;
			
			return $account;
		} else 
			return false;
	}
	
	public function changePassword($id, $oldPassword, $newPassword) {
		$sql = 'SELECT salt, encrypted_password FROM users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));

		$data = $query -> fetchObject();
		$salt = $data -> salt;
		$db_encrypted_password = $data -> encrypted_password;

		if ($this -> verifyHash($oldPassword.$salt, $db_encrypted_password)) {
			$hash = $this -> getHash($newPassword);
			$encrypted_password = $hash["encrypted"];
			$salt = $hash["salt"];

			$sql = 'UPDATE users SET encrypted_password = :encrypted_password, salt = :salt WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id, ':encrypted_password' => $encrypted_password, ':salt' => $salt));

			if ($query)
				return true;
			else
				return false;
		} else
			return false;
	}
	
	public function changeGender($id, $gender) {
		$sql = 'UPDATE users SET gender = :gender WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':gender' => $gender));

		if ($query)
			return true;
		else
			return false;
	}
	
	public function changeBirthdate($id, $birthdate) {
		$sql = 'UPDATE users SET birthdate = :birthdate WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':birthdate' => $birthdate));

		if ($query)
			return true;
		else
			return false;
	}
	
	public function deactivate($id) {
		$sql = 'UPDATE users SET deactivated = :deactivated WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':deactivated' => 1));

		if ($query) {
			$this -> logOffUser($id);
			
			return true;
		} else
			return false;
	}
	
	public function deleteRequest($id, $date) {
		$sql = 'UPDATE users SET deactivated = :deactivated, delete_deadline = :delete_deadline WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':deactivated' => 1, ':delete_deadline' => $date));

		if ($query) {
			$this -> logOffUser($id);
			
			return true;
		} else
			return false;
	}
	
	public function createOnlineUser($id, $phpsessid) {		
		//Device Info
		$device_info = $_SERVER['HTTP_USER_AGENT'];
		
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
		
		//Country
		$ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));
		$country = $ipdat -> geoplugin_countryName;

		//Create
		$sql = 'INSERT INTO online_users SET id = :id, country = :country, device_info = :device_info, ip = :ip, phpsessid = :phpsessid';
		$query = $this -> conn -> prepare($sql);
		
		if($query -> execute(array(':id' => $id, ':country' => $country, ':device_info' => $device_info, ':ip' => $ip, ':phpsessid' => $phpsessid)))
			return true;
		else
			return false;
	}
	
	public function getSwirl($id) {
		if($id) {
			$sql = "SELECT online_users.id
				FROM online_users
				INNER JOIN preferences ON (online_users.id = preferences.id AND online_users.token IS NOT NULL AND preferences.private_profile = 0)
				WHERE NOT EXISTS(SELECT 1 FROM blacklist WHERE (id = :id AND blocked_id = online_users.id) OR (id = online_users.id AND blocked_id = :id))
				ORDER BY RAND()
				LIMIT 32";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));
			
			if ($query) {
				$result = $query -> fetchAll(PDO::FETCH_ASSOC);
				
				if (count($result) != 0) {
					for($i = 0; $i<count($result); $i++) {
						$array = $result[$i];
						$swirl_data = $this -> getSwirlData($array["id"]);
						
						$swirl[$i]["username"] = $swirl_data["username"];
						$swirl[$i]["profile_img"] = $swirl_data["profile_img"];
						$swirl[$i]["bio"] = $swirl_data["bio"];
					}
					
					return $swirl;
				} else 
					return null;
			} else 
				return null;
		} else {
			$sql = "SELECT online_users.id
				FROM online_users
				INNER JOIN preferences
				ON (online_users.id = preferences.id AND online_users.token IS NOT NULL AND preferences.private_profile = 0 AND preferences.registered_access = 0)
				ORDER BY RAND()
				LIMIT 32";
			$query = $this -> conn -> prepare($sql);
			$query -> execute();
			
			if ($query) {
				$result = $query -> fetchAll(PDO::FETCH_ASSOC);
				
				if (count($result) != 0) {
					for($i = 0; $i<count($result); $i++) {
						$array = $result[$i];
						$swirl_data = $this -> getSwirlData($array["id"]);
						
						$swirl[$i]["username"] = $swirl_data["username"];
						$swirl[$i]["profile_img"] = $swirl_data["profile_img"];
						$swirl[$i]["bio"] = $swirl_data["bio"];
					}
					
					return $swirl;
				} else 
					return null;
			} else 
				return null;
		}
	}
	
	public function getSwirlData($id) {
		$sql = 'SELECT username, profile_img, bio FROM users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		$data = $query -> fetchObject();
		
		$swirl_data["username"] = $data -> username;
		$swirl_data["profile_img"] = $data -> profile_img;
		$swirl_data["bio"] = $data -> bio;
		
		return $swirl_data;
	}
	
	public function getRandom($id) {
		if($id) {
			$sql = "SELECT online_users.id
				FROM online_users
				INNER JOIN preferences ON (online_users.id = preferences.id AND online_users.token IS NOT NULL AND preferences.private_profile = 0 AND online_users.id != :id)
				WHERE NOT EXISTS(SELECT 1 FROM blacklist WHERE (id = :id AND blocked_id = online_users.id) OR (id = online_users.id AND blocked_id = :id))
				ORDER BY RAND()
				LIMIT 1";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));
			
			if ($query) {
				$result = $query -> fetchAll(PDO::FETCH_ASSOC);
				
				if (count($result) != 0)				
					return $this -> getUsername($result[0]["id"]);
				else 
					return null;
			} else 
				return null;
		} else {
			$sql = "SELECT online_users.id
				FROM online_users
				INNER JOIN preferences
				ON (online_users.id = preferences.id AND online_users.token IS NOT NULL AND preferences.private_profile = 0 AND preferences.registered_access = 0)
				ORDER BY RAND()
				LIMIT 1";
			$query = $this -> conn -> prepare($sql);
			$query -> execute();
			
			if ($query) {
				$result = $query -> fetchAll(PDO::FETCH_ASSOC);
				
				if (count($result) != 0)				
					return $this -> getUsername($result[0]["id"]);
				else 
					return null;
			} else 
				return null;
		}
	}
	
	public function search($id, $keyword) {		
		if($id) {
			$sql = "SELECT online_users.id
				FROM online_users
				INNER JOIN preferences ON (online_users.id = preferences.id AND online_users.token IS NOT NULL AND preferences.private_profile = 0)
				INNER JOIN users ON (online_users.id = users.id AND (users.username LIKE :keyword OR users.bio LIKE :keyword))
				WHERE NOT EXISTS(SELECT 1 FROM blacklist WHERE (id = :id AND blocked_id = online_users.id) OR (id = online_users.id AND blocked_id = :id))
				ORDER BY RAND()
				LIMIT 32";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id, ':keyword' => "%".$keyword."%"));
			
			if ($query) {
				$result = $query -> fetchAll(PDO::FETCH_ASSOC);
				
				if (count($result) != 0) {
					for($i = 0; $i<count($result); $i++) {
						$array = $result[$i];
						$swirl_data = $this -> getSwirlData($array["id"]);
						
						$swirl[$i]["username"] = $swirl_data["username"];
						$swirl[$i]["profile_img"] = $swirl_data["profile_img"];
						$swirl[$i]["bio"] = $swirl_data["bio"];
					}
					
					return $swirl;
				} else
					return null;
			} else
				return null;
		} else {
			$sql = "SELECT online_users.id
				FROM online_users
				INNER JOIN preferences ON (online_users.id = preferences.id AND online_users.token IS NOT NULL AND preferences.private_profile = 0 AND preferences.registered_access = 0)
				INNER JOIN users ON (online_users.id = users.id AND (users.username LIKE :keyword OR users.bio LIKE :keyword))
				ORDER BY RAND()
				LIMIT 32";
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':keyword' => "%".$keyword."%"));
			
			if ($query) {
				$result = $query -> fetchAll(PDO::FETCH_ASSOC);
				
				if (count($result) != 0) {
					for($i = 0; $i<count($result); $i++) {
						$array = $result[$i];
						$swirl_data = $this -> getSwirlData($array["id"]);
						
						$swirl[$i]["username"] = $swirl_data["username"];
						$swirl[$i]["profile_img"] = $swirl_data["profile_img"];
						$swirl[$i]["bio"] = $swirl_data["bio"];
					}
					
					return $swirl;
				} else
					return null;
			} else
				return null;
		}
	}
	
	//Mail
	public function isEmailVerified($id) {
		$sql = 'SELECT COUNT(*) FROM users WHERE email_verified = :email_verified AND id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email_verified' => 1, ':id' => $id));

		if ($query) {
			$row_count = $query -> fetchColumn();
			
			if ($row_count == 0)
				return false;
			else
				return true;
		} else
			return false;
	}
	
	public function createEmailToken($email_token, $email) {
		$sql = 'UPDATE users SET email_token = :email_token WHERE email = :email AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email' => $email, ':email_token' => $email_token, ':deleted' => 0));
	}
		
	public function checkTokenExist($email_token) {
		$sql = 'SELECT COUNT(*) FROM users WHERE email_verified = :email_verified AND email_token = :email_token AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email_verified' => 0, ':email_token' => $email_token, ':deleted' => 0));

		if ($query) {
			$row_count = $query -> fetchColumn();
			
			if ($row_count == 0)
				return false;
			else
				return true;
		} else
			return false;
	}
	
	public function checkChangeTokenExist($email_change_token) {
		$sql = 'SELECT COUNT(*) FROM users WHERE email_change_token = :email_change_token AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email_change_token' => $email_change_token, ':deleted' => 0));

		if ($query) {
			$row_count = $query -> fetchColumn();
			
			if ($row_count == 0)
				return false;
			else
				return true;
		} else
			return false;
	}
	
	public function checkRecoverTokenExist($email_recover_token) {
		$sql = 'SELECT COUNT(*) FROM users WHERE email_token = :email_recover_token AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email_recover_token' => $email_recover_token, ':deleted' => 0));

		if ($query) {
			$row_count = $query -> fetchColumn();
			
			if ($row_count == 0)
				return false;
			else {
				$sql = 'SELECT id, email_lastSent FROM users WHERE email_token = :email_recover_token AND deleted = :deleted';
				$query = $this -> conn -> prepare($sql);
				$query -> execute(array(':email_recover_token' => $email_recover_token, ':deleted' => 0));
				
				if ($query) {
					$data = $query -> fetchObject();
					$email_lastSent = $data -> email_lastSent;
					$id = $data -> id;
					
					if (time() - strtotime($email_lastSent) <= 604800)
						return true;
					else {
						$sql = 'UPDATE users SET email_old = NULL, email_token = NULL, email_lastSent = NULL WHERE id = :id';
						$query = $this -> conn -> prepare($sql);
						$query -> execute(array(':id' => $id));
						
						return false;
					}
				} else
					return false;
			}
		} else
			return false;
	}
	
	public function getEmailVerifyData($id) {
		$sql = 'SELECT username, email, email_verified, email_token, email_lastSent FROM users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		
		if ($query) {
			$data = $query -> fetchObject();
			
			$account["email_verified"] = $data -> email_verified;
			$account["email_token"] = $data -> email_token;
			$account["email_lastSent"] = $data -> email_lastSent;
			
			return $account;
		} else 
			return false;
	}
	
	public function getChangeEmailVerifyData($id) {
		$sql = 'SELECT email, email_change_lastSent FROM users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		
		if ($query) {
			$data = $query -> fetchObject();
			
			$account["email"] = $data -> email;
			$account["email_change_lastSent"] = $data -> email_change_lastSent;
			
			return $account;
		} else 
			return false;
	}
	
	public function activateAccount($email_token) {
		$sql = 'UPDATE users SET email_verified = :email_verified, email_lastSent = NULL, email_token = NULL WHERE email_token = :email_token';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email_verified' => 1, ':email_token' => $email_token));
		
		if ($query)
			return true;
		else 
			return false;
	}
	
	public function changeEmail($email_change_token) {
		$sql = 'SELECT id, username, email, email_new, email_verified FROM users WHERE email_change_token = :email_change_token';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email_change_token' => $email_change_token));
		
		if ($query) {
			$data = $query -> fetchObject();
			$id = $data -> id;
			$username = $data -> username;
			$email = $data -> email;
			$email_new = $data -> email_new;
			$email_verified = $data -> email_verified;
			
			$sql = 'UPDATE users SET email = :email, email_verified = :email_verified, email_old = :email_old, email_new = NULL, email_lastSent = NULL, email_token = NULL, email_change_token = NULL WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':email' => $email_new, ':email_verified' => 1, ':email_old' => $email, ':id' => $id));
			
			if ($query) {
				if ($email_verified == 1) {
					$email_recover_token = sha1(rand());
					
					//Mail
					$subject = "E-mail Adresiniz Değişti";
							
					$message_p = "Merhaba, ".$username.",<br /><br />";
					$message_p .= "Hesabınızın e-mail adresi artık ".$email_new." olarak değiştirildi.<br />";
					$message_p .= "Eğer bu işlem sizin tarafınızdan gerçekleştirilmediyse aşağıdaki bağlantıya tıklayarak hesabınızın e-mail adresini tekrar bu adres olarak ayarlayabilirsiniz. Bu işlem için 7 gün vaktiniz olduğunu unutmayınız.<br /><br />";
					$message_p .= "<a style='color:#05c7f2;' href='https://swirlia.net/php/recover_email.php?email_recover_token=".$email_recover_token."'>Aktivasyon Bağlantısı</a>";
					
					$this -> send("notify@swirlia.com", $email, $subject, "https://swirlia.net/images/email_recovered_light.png", "E-mail Adresiniz Değişti", $message_p, "1");
					//Mail
					
					$this -> updateEmailLastSent($email_recover_token, $id);
				}
				
				if (isset($_SESSION["email"])) {
					if (strtolower($_SESSION["email"]) === strtolower($email))
						$_SESSION["email"] = $email_new;
				}
				
				return true;
			} else 
				return false;
		} else
			return false;
	}
	
	public function recoverEmail($email_recover_token) {
		$sql = 'SELECT id, email, email_old FROM users WHERE email_token = :email_recover_token';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email_recover_token' => $email_recover_token));
		
		if ($query) {
			$data = $query -> fetchObject();
			$id = $data -> id;
			$email = $data -> email;
			$email_old = $data -> email_old;
			
			$sql = 'UPDATE users SET email = :email, email_old = NULL, email_new = NULL, email_lastSent = NULL, email_token = NULL, email_change_token = NULL WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':email' => $email_old, ':id' => $id));
			
			if ($query) {				
				if (isset($_SESSION["email"])) {
					if (strtolower($_SESSION["email"]) === strtolower($email))
						$_SESSION["email"] = $email_old;
				}
				
				return true;
			} else 
				return false;
		} else
			return false;
	}
	
	public function updateEmailLastSent($email_token, $id) {
		$sql = 'UPDATE users SET email_token = :email_token, email_lastSent = :email_lastSent WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email_token' => $email_token, ':email_lastSent' => strval(date('Y-m-d H:i:s', time())), ':id' => $id));
	}
	
	public function updateChangeEmailLastSent($email_change_token, $email_new, $id) {
		$sql = 'UPDATE users SET email_new = :email_new, email_change_token = :email_change_token, email_change_lastSent = :email_change_lastSent WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email_new' => $email_new, ':email_change_token' => $email_change_token, ':email_change_lastSent' => strval(date('Y-m-d H:i:s', time())), ':id' => $id));
	}
	
	public function getForgotPasswordStatus($variable, $isEmail) {
		if ($isEmail) {
			$sql = 'SELECT COUNT(*) FROM users WHERE email_verified = :email_verified AND email = :email AND deleted = :deleted';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':email_verified' => 1, ':email' => $variable, ':deleted' => 0));
		} else {
			$sql = 'SELECT COUNT(*) FROM users WHERE email_verified = :email_verified AND username = :username AND deleted = :deleted';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':email_verified' => 1, ':username' => $variable, ':deleted' => 0));
		}

		if ($query) {
			$row_count = $query -> fetchColumn();
			
			if ($row_count == 0) {
				$passwordForgotStatus["result"] = "failure";
			
				return $passwordForgotStatus;
			} else {
				if ($isEmail) {
					$sql = 'SELECT username, password_token, password_lastSent FROM users WHERE email = :email AND deleted = :deleted';
					$query = $this -> conn -> prepare($sql);
					$query -> execute(array(':email' => $variable, ':deleted' => 0));
					
					$data = $query -> fetchObject();
					
					$username = $data -> username;
					$email = $variable;
				} else {
					$sql = 'SELECT email, password_token, password_lastSent FROM users WHERE username = :username AND deleted = :deleted';
					$query = $this -> conn -> prepare($sql);
					$query -> execute(array(':username' => $variable, ':deleted' => 0));
					
					$data = $query -> fetchObject();
					
					$username = $variable;
					$email = $data -> email;
				}
				
				if ($data -> password_lastSent == null) {
					$password_token = sha1(rand());
					
					$sql = 'UPDATE users SET password_token = :password_token, password_lastSent = :password_lastSent WHERE email = :email AND deleted = :deleted';
					$query = $this -> conn -> prepare($sql);
					$query -> execute(array(':email' => $email, ':password_token' => $password_token, ':password_lastSent' => strval(date('Y-m-d H:i:s', time())), ':deleted' => 0));
					
					$passwordForgotStatus["result"] = "success";
					$passwordForgotStatus["username"] = $username;
					$passwordForgotStatus["email"] = $email;
					$passwordForgotStatus["password_token"] = $password_token;
					
					return $passwordForgotStatus;
				} else {
					if(time() - strtotime($data -> password_lastSent) >= 3600) {
						$sql = 'UPDATE users SET password_lastSent = :password_lastSent WHERE email = :email AND deleted = :deleted';
						$query = $this -> conn -> prepare($sql);
						$query -> execute(array(':email' => $email, ':password_lastSent' => strval(date('Y-m-d H:i:s', time())), ':deleted' => 0));
						
						$passwordForgotStatus["result"] = "success";
						$passwordForgotStatus["username"] = $username;
						$passwordForgotStatus["email"] = $email;
						$passwordForgotStatus["password_token"] = $data -> password_token;
						
						return $passwordForgotStatus;
					} else {
						$passwordForgotStatus["result"] = "failure";
						
						return $passwordForgotStatus;
					}
				}
			}
		} else {
			$passwordForgotStatus["result"] = "failure";
			
			return $passwordForgotStatus;
		}
	}
	
	public function checkPasswordTokenExist($email, $password_token){
		$sql = 'SELECT COUNT(*) FROM users WHERE email = :email AND password_token = :password_token AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email' => $email, ':password_token' => $password_token, ':deleted' => 0));

		if($query) {
			$row_count = $query -> fetchColumn();
			
			if ($row_count == 0)
				return false;
			else 
				return true;			
		} else
			return false;
	}
	
	public function changeForgottenPassword($email, $password) {
		$hash = $this -> getHash($password);
		$encrypted_password = $hash["encrypted"];
		$salt = $hash["salt"];
		
		$sql = 'UPDATE users SET encrypted_password = :encrypted_password, salt = :salt, password_token = NULL, password_lastSent = NULL WHERE email = :email AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':encrypted_password' => $encrypted_password, ':salt' => $salt, ':email' => $email, ':deleted' => 0));
		
		if ($query)
			return true;
		else 
			return false;
	}
	
	//Profile	
	public function getUserInfo($username, $id, $isSelf, $isOnline, $senderId) {
		$canAccess = false;
		$canSee = false;
		
		if(!$isSelf) {
			$sql = 'SELECT register_date, last_seen_date FROM preferences WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));
			
			$data = $query -> fetchObject();
			
			if (($data -> register_date) == 1)
				$canAccess = true;
			
			if($isOnline && (($data -> last_seen_date) == 1)) {
				$sql = 'SELECT last_seen_date FROM preferences WHERE id = :id';
				$query = $this -> conn -> prepare($sql);
				$query -> execute(array(':id' => $senderId));
				
				if (($query -> fetchObject() -> last_seen_date) == 1)
					$canSee = true;
			}			
		}
		
		$user_info["sounds_enabled"] = true;
		
		if($isOnline) {
			$sql = 'SELECT sounds_enabled FROM preferences WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $senderId));
			
			if (($query -> fetchObject() -> sounds_enabled) == 0)
				$user_info["sounds_enabled"] = false;
		}
		
		if($isSelf || $canAccess) {
			$sql = 'SELECT username, created_at, profile_img, bio FROM users WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));

			$data = $query -> fetchObject();
			$user_info["id"] = $id;
			$user_info["created_at"] = $data -> created_at;
			$user_info["username"] = $data -> username;
			$user_info["profile_img"] = $data -> profile_img;
			$user_info["bio"] = $data -> bio;
		} else {
			$sql = 'SELECT username, profile_img, bio FROM users WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));

			$data = $query -> fetchObject();
			$user_info["id"] = $id;
			$user_info["created_at"] = null;
			$user_info["username"] = $data -> username;
			$user_info["profile_img"] = $data -> profile_img;
			$user_info["bio"] = $data -> bio;
		}
		
		if($isSelf)
			$user_info["is_online"] = true;
		else if($this -> isOnlineUser($id, false))
			$user_info["is_online"] = true;
		else {
			$user_info["is_online"] = false;
			
			if($canSee) {
				$sql = 'SELECT last_logout FROM statistics WHERE id = :id';
				$query = $this -> conn -> prepare($sql);
				$query -> execute(array(':id' => $id));
					
				$user_info["last_seen"] = time() - strtotime($query -> fetchObject() -> last_logout);
			} else
				$user_info["last_seen"] = -1;
		}
		
		return $user_info;
	}
	
	public function getFollowersCount($id, $isSelf) {
		$canAccess = false;
		
		if(!$isSelf) {
			$sql = 'SELECT followers_count FROM preferences WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));
			
			if (($query -> fetchObject() -> followers_count) == 1)
				$canAccess = true;
		}
		
		if($isSelf || $canAccess) {
			$sql = 'SELECT COUNT(*) FROM follows WHERE followed_id = :followed_id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':followed_id' => $id));

			return $query -> fetchColumn();
		} else
			return null;
	}
	
	public function getConversationsCount($id, $isSelf) {
		$canAccess = false;
		
		if(!$isSelf) {
			$sql = 'SELECT conversations_count FROM preferences WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));
			
			if (($query -> fetchObject() -> conversations_count) == 1)
				$canAccess = true;
		}
		
		if($isSelf || $canAccess) {
			$sql = 'SELECT total_conversations FROM statistics WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));

			return $query -> fetchObject() -> total_conversations;
		} else
			return null;
	}
	
	public function isFollowing($requested_id, $id) {
		$sql = 'SELECT COUNT(*) FROM follows WHERE id = :id AND followed_id = :followed_id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':followed_id' => $requested_id));

		$row_count = $query -> fetchColumn();

		if ($row_count == 0)
			return false;
		else
			return true;
	}
	
	public function uploadPhoto() {
		$direction = "php/uploads/";
		
		$sql = 'UPDATE users SET profile_img = :profile_img WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		if ($query -> execute(array(':profile_img' => $direction.md5($_SESSION["username"]).".png", ':id' => $_SESSION["id"])))
			return true;
		else
			return false;
	}
	
	public function removePhoto() {
		$direction = "php/uploads/user.png";
		
		$sql = 'UPDATE users SET profile_img = :profile_img WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		if ($query -> execute(array(':profile_img' => $direction, ':id' => $_SESSION["id"])))
			return true;
		else
			return false;
	}
	
	public function editBio($bio) {
		$sql = 'UPDATE users SET bio = :bio WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		if ($query -> execute(array(':bio' => $bio, ':id' => $_SESSION["id"])))
			return true;
		else
			return false;
	}
	
	public function follow($requested_id, $id) {
		$sql = 'INSERT INTO follows SET id = :id, followed_id = :followed_id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':followed_id' => $requested_id));

		if ($query) {
			$sql = 'UPDATE statistics SET `todays_followers` = `todays_followers` +1 WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));
		
			return true;
		} else
			return false;
	}
	
	public function unfollow($requested_id, $id) {
		$sql = 'DELETE FROM follows WHERE id = :id AND followed_id = :followed_id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':followed_id' => $requested_id));
		
		if($query) {
			$sql = 'UPDATE statistics SET `todays_followers` = `todays_followers` -1 WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));
			
			return true;
		}
		else 
			return false;
	}
	
	public function block($requested_id, $id) {
		$sql = 'INSERT INTO blacklist SET id = :id, blocked_id = :blocked_id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':blocked_id' => $requested_id));

		if ($query)
			return true;
		else
			return false;
	}
		
	public function isReportExists($requested_id, $id, $reason, $message, $material) {
		if (IS_NULL($message) && !IS_NULL($material)) {
			$sql = 'SELECT COUNT(*) FROM reports WHERE id = :id AND reported_id = :reported_id AND reason = :reason AND message IS NULL AND material = :material';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id, ':reported_id' => $requested_id, ':reason' => $reason, ':material' => $material));
		} else if (!IS_NULL($message) && IS_NULL($material)) {
			$sql = 'SELECT COUNT(*) FROM reports WHERE id = :id AND reported_id = :reported_id AND reason = :reason AND message = :message AND material IS NULL';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id, ':reported_id' => $requested_id, ':reason' => $reason, ':message' => $message));
		} else if (IS_NULL($message) && IS_NULL($material)) {
			$sql = 'SELECT COUNT(*) FROM reports WHERE id = :id AND reported_id = :reported_id AND reason = :reason AND message IS NULL AND material IS NULL';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id, ':reported_id' => $requested_id, ':reason' => $reason));
		} else {
			$sql = 'SELECT COUNT(*) FROM reports WHERE id = :id AND reported_id = :reported_id AND reason = :reason AND message = :message AND material = :material';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id, ':reported_id' => $requested_id, ':reason' => $reason, ':message' => $message, ':material' => $material));
		}
		
		$row_count = $query -> fetchColumn();
		
		if ($row_count == 0)
			return false;
		else
			return true;
	}
	
	public function report($requested_id, $id, $reason, $message, $material) {
		$sql = 'INSERT INTO reports SET id = :id, reported_id = :reported_id, reason = :reason, message = :message, material = :material';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':reported_id' => $requested_id, ':reason' => $reason, ':message' => $message, ':material' => $material));

		if ($query) {
			$sql = 'UPDATE statistics SET `report_requests` = `report_requests` +1 WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));
			
			$sql = 'UPDATE statistics SET `users_reported` = `users_reported` +1 WHERE id = :requested_id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':requested_id' => $requested_id));
			
			return true;
		} else
			return false;
	}
		
	public function isSupportExists($id, $reason, $message) {
		$sql = 'SELECT COUNT(*) FROM support WHERE id = :id AND reason = :reason AND message = :message';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':reason' => $reason, ':message' => $message));
		
		$row_count = $query -> fetchColumn();
		
		if ($row_count == 0)
			return false;
		else
			return true;
	}
	
	public function support($id, $reason, $message) {
		$sql = 'INSERT INTO support SET id = :id, reason = :reason, message = :message';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':reason' => $reason, ':message' => $message));

		if ($query) {
			$sql = 'UPDATE statistics SET `support_requests` = `support_requests` +1 WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));
			
			return true;
		} else
			return false;
	}
	
	public function getFollowings($id) {	
		$sql = 'SELECT followed_id FROM follows WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		
		if ($query) {
			$result = $query -> fetchAll(PDO::FETCH_ASSOC);
			
			$counter = 0;
			if (count($result) != 0) {
				for($i = 0; $i<count($result); $i++) {
					if($this -> isAccountAvailable($result[$i]["followed_id"], null)) {
						$array = $result[$i];
						
						$followings_data = $this -> getFollowingsData($array["followed_id"]);
						
						$followings[$counter]["id"] = $array["followed_id"];
						$followings[$counter]["username"] = $followings_data["username"];
						$followings[$counter]["profile_img"] = $followings_data["profile_img"];
						$followings[$counter]["is_online"] = $followings_data["is_online"];
						
						$counter++;
					}
				}
				
				if($counter == 0)
					return null;
				else
					return $followings;
			} else 
				return null;
		} else 
			return null;
	}
	
	public function getFollowingsData($id) {
		$sql = 'SELECT username, profile_img FROM users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		$data = $query -> fetchObject();
		
		$followings_data["username"] = $data -> username;
		$followings_data["profile_img"] = $data -> profile_img;
		
		if($this -> isOnlineUser($id, false))
			$followings_data["is_online"] = true;
		else
			$followings_data["is_online"] = false;
		
		return $followings_data;
	}
	
	public function keepAlive($id) {
		if(!($this -> isOnlineUser($id, false))) {
			if($this -> createOnlineUser($id, session_id()))
				return true;
			else
				return false;
		} else {
			$sql = 'UPDATE online_users SET updated = :updated WHERE id = :id';
			$query = $this -> conn -> prepare($sql);

			if ($query -> execute(array(':updated' => strval(date('Y-m-d H:i:s', time())), ':id' => $id)))
				return true;
			else
				return false;
		}
	}
	
	public function logOffUser($id) {
		//Log Off
		$sql = 'DELETE FROM online_users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		
		//Update Last Log Off
		$sql = 'UPDATE statistics SET last_logout = :last_logout WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':last_logout' => strval(date('Y-m-d H:i:s', time()))));
		
		//Update Todays Online
		$sql = 'SELECT last_login, last_logout FROM statistics WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		
		$data = $query -> fetchObject();
		$last_login = $data -> last_login;
		$last_logout = $data -> last_logout;
		
		$session_online_time = strtotime($last_logout) - strtotime($last_login);
		
		$sql = 'UPDATE statistics SET `todays_online` = `todays_online` +'.$session_online_time.', `total_online` = `total_online` +'.$session_online_time.' WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		
		$this -> updateSwirliaStatistics("logouts", "1");
		$this -> updateSwirliaStatistics("online_time", $session_online_time);
	}
	
	//Chat
	public function canCreateChatroom($id) {
		$sql = 'SELECT COUNT(*) FROM online_users WHERE id = :id AND token IS NULL';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));

		$row_count = $query -> fetchColumn();

		if ($row_count == 0)
			return false;
		else
			return true;
	}
	
	public function canJoinChatroom($requested_id, $id) {
		$sql = 'SELECT COUNT(*) FROM chats WHERE receiver = :receiver AND sender = :sender AND currently_chatting = :currently_chatting';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':receiver' => $requested_id, ':sender' => $id, ':currently_chatting' => 1));

		$row_count = $query -> fetchColumn();

		if ($row_count == 0)
			return true;
		else
			return false;
	}
	
	public function getChatToken($id) {
		$sql = 'SELECT token, phpsessid FROM online_users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		
		$data = $query -> fetchObject();
		return $data;
	}
	
	public function getPhpsessid($id) {
		$sql = 'SELECT phpsessid FROM online_users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		
		return $query -> fetchObject() -> phpsessid;
	}
	
	public function createToken($token) {
		$sql = 'UPDATE online_users SET token = :token WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		if ($query -> execute(array(':token' => $token, ':id' => $_SESSION["id"])))
			return true;
		else
			return false;
	}
	
	public function nullToken($token) {
		$sql = 'UPDATE online_users SET token = :null WHERE token = :token';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':null' => null, ':token' => $token));
	}
	
	public function getAnonId() {
		$sql = 'SELECT anon_id FROM users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $_SESSION["id"]));
		
		return $query -> fetchObject() -> anon_id;
	}
	
	public function isConversationExist($receiver, $sender) {
		$sql = 'SELECT COUNT(*) FROM chats WHERE receiver = :receiver AND sender = :sender';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':receiver' => $receiver, ':sender' => $sender));

		$row_count = $query -> fetchColumn();

		if ($row_count == 0)
			return false;
		else
			return true;
	}
	
	public function updateConversation($receiver, $sender, $currently_chatting) {
		$sql = 'UPDATE chats SET currently_chatting = :currently_chatting WHERE receiver = :receiver AND sender = :sender';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':currently_chatting' => $currently_chatting, ':receiver' => $receiver, ':sender' => $sender));
	}
	
	public function startConversation($receiver, $sender) {
		$sql = 'INSERT INTO chats SET receiver = :receiver, sender = :sender';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array( ':receiver' => $receiver, ':sender' => $sender));
	}
	
	public function increaseConversationCount($id) {
		$sql = 'UPDATE statistics SET `todays_conversations` = `todays_conversations` +1, `total_conversations` = `total_conversations` +1 WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
	}
	
	public function blockChat($anon_name, $anon_ip, $id) {
		$sql = 'INSERT INTO blacklist SET id = :id, anon_name = :anon_name, anon_ip = :anon_ip';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':anon_name' => $anon_name, ':anon_ip' => $anon_ip));

		if ($query)
			return true;
		else
			return false;
	}
	
	public function resetChats() {
		//nullToken
		$sql = 'UPDATE online_users SET token = NULL WHERE 1 = 1';
		$query = $this -> conn -> prepare($sql);
		$query -> execute();
		
		//updateConversation
		$sql = 'UPDATE chats SET currently_chatting = 0 WHERE 1 = 1';
		$query = $this -> conn -> prepare($sql);
		$query -> execute();
	}
	
	//Unique Validation
	public function checkUniqueUsernameExist($username) {
		$sql = 'SELECT COUNT(*) FROM users WHERE username = :username';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':username' => $username));

		if($query) {
			$row_count = $query -> fetchColumn();

			if ($row_count == 0)
				return false;
			else
				return true;
		} else
			return false;
	}
	
	public function checkUsernameExist($username) {
		$sql = 'SELECT COUNT(*) FROM users WHERE username = :username AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':username' => $username, ':deleted' => 0));

		if($query) {
			$row_count = $query -> fetchColumn();

			if ($row_count == 0)
				return false;
			else
				return true;
		} else
			return false;
	}
	 
	public function checkEmailExist($email) {
		$sql = 'SELECT COUNT(*) FROM users WHERE email = :email AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email' => $email, ':deleted' => 0));

		if($query) {
			$row_count = $query -> fetchColumn();

			if ($row_count == 0)
				return false;
			else
				return true;
		} else
			return false;
	}
	 
	public function checkAnonIdExist($anon_id) {
		$sql = 'SELECT COUNT(*) FROM users WHERE anon_id = :anon_id AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':anon_id' => $anon_id, ':deleted' => 0));

		if($query) {
			$row_count = $query -> fetchColumn();

			if ($row_count == 0)
				return false;
			else
				return true;
		} else
			return false;
	}
	
	//Encryption
	public function getHash($password) {
		$salt = sha1(rand());
		$salt = substr($salt, 0, 10);
		$encrypted = password_hash($password.$salt, PASSWORD_DEFAULT);
		$hash = array("salt" => $salt, "encrypted" => $encrypted);

		return $hash;
	}

	public function verifyHash($password, $hash) {
		return password_verify($password, $hash);
	}
	
	//AUX	
	public function updateSwirliaStatistics($column, $magnitude) {
		$sql = "UPDATE swirlia_statistics SET `".$column."` = `".$column."` +".$magnitude." ORDER BY sno DESC LIMIT 1;";
		$query = $this -> conn -> prepare($sql);
		$query -> execute();
	}
	
	public function isOnlineUser($id, $chat) {
		if ($chat)
			$sql = 'SELECT COUNT(*) FROM online_users WHERE id = :id AND token IS NOT NULL';
		else
			$sql = 'SELECT COUNT(*) FROM online_users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));

		$row_count = $query -> fetchColumn();

		if ($row_count == 0)
			return false;
		else
			return true;
	}
	
	public function getUsername($id) {
		$sql = 'SELECT username FROM users WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		return $query -> fetchObject() -> username;
	}
	
	public function getUsernameFromEmail($email) {
		$sql = 'SELECT username FROM users WHERE email = :email AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':email' => $email, ':deleted' => 0));
		return $query -> fetchObject() -> username;
	}
	
	public function getEmailFromUsername($username) {
		$sql = 'SELECT email FROM users WHERE username = :username AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':username' => $username, ':deleted' => 0));
		return $query -> fetchObject() -> email;
	}
	
	public function getId($username) {
		$sql = 'SELECT id FROM users WHERE username = :username AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':username' => $username, ':deleted' => 0));
		return $query -> fetchObject() -> id;
	}
	
	public function getIdFromAnonId($anon_id) {
		$sql = 'SELECT COUNT(*) FROM users WHERE anon_id = :anon_id AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':anon_id' => $anon_id, ':deleted' => 0));

		if($query) {
			$row_count = $query -> fetchColumn();

			if ($row_count == 0)
				return null;
			else {
				$sql = 'SELECT id FROM users WHERE anon_id = :anon_id AND deleted = :deleted';
				$query = $this -> conn -> prepare($sql);
				$query -> execute(array(':anon_id' => $anon_id, ':deleted' => 0));
				return $query -> fetchObject() -> id;
			}
		} else
			return null;
	}
		
	public function isAccountAvailable($id, $phpsessid) {		
		$sql = 'SELECT COUNT(*) FROM users WHERE id = :id AND deactivated = :deactivated AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':deactivated' => 0, ':deleted' => 0));

		$row_count = $query -> fetchColumn();

		if ($row_count == 0)
			return false;
		else {
			$sql = 'SELECT COUNT(*) FROM restricted_users WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));

			$row_count = $query -> fetchColumn();

			if ($row_count == 0) {
				if ($phpsessid) {
					$sql = 'SELECT COUNT(*) FROM online_users WHERE id = :id AND phpsessid = :phpsessid';
					$query = $this -> conn -> prepare($sql);
					$query -> execute(array(':id' => $id, ':phpsessid' => $phpsessid));

					$row_count = $query -> fetchColumn();

					if ($row_count == 0)
						return false;
					else
						return true;
				} else				
					return true;
			} else
				return false;
		}
	}
	
	public function isAccountAvailableForLogin($username) {
		$id = $this -> getId($username);
		
		$sql = 'SELECT COUNT(*) FROM users WHERE id = :id AND deleted = :deleted';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':deleted' => 0));

		$row_count = $query -> fetchColumn();

		if ($row_count == 0)
			return false;
		else {
			$sql = 'SELECT COUNT(*) FROM restricted_users WHERE id = :id';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id));

			$row_count = $query -> fetchColumn();

			if ($row_count == 0)
				return true;
			else
				return false;
		}
	}
	
	public function isAccountAvailableForChat($id, $checkSelf, $phpsessid) {
		if ($checkSelf) {
			$sql = 'SELECT COUNT(*) FROM online_users WHERE id = :id AND phpsessid = :phpsessid AND token IS NULL';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id, ':phpsessid' => $phpsessid));
		} else {
			$sql = 'SELECT COUNT(*) FROM online_users WHERE id = :id AND phpsessid = :phpsessid AND token IS NOT NULL';
			$query = $this -> conn -> prepare($sql);
			$query -> execute(array(':id' => $id, ':phpsessid' => $phpsessid));
		}

		$row_count = $query -> fetchColumn();

		if ($row_count == 0)
			return false;
		else
			return true;
	}
	
	public function isBlocked($id, $requested_id) {		
		$sql = 'SELECT COUNT(*) FROM blacklist WHERE (id = :id AND blocked_id = :blocked_id) OR (id = :blocked_id AND blocked_id = :id)';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':blocked_id' => $requested_id));

		$row_count = $query -> fetchColumn();

		if ($row_count == 0)
			return false;
		else
			return true;
	}
	
	public function isBlockedChat($id, $anon_ip) {		
		$sql = 'SELECT COUNT(*) FROM blacklist WHERE id = :id AND anon_ip = :anon_ip';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id, ':anon_ip' => $anon_ip));

		$row_count = $query -> fetchColumn();

		if ($row_count == 0)
			return false;
		else
			return true;
	}
	
	public function isGuestAllowed($id) {		
		$sql = 'SELECT registered_access FROM preferences WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));
		
		if (($query -> fetchObject() -> registered_access) == 0)
			return true;
		else
			return false;
	}
	
	public function isGuestAllowedForChat($id) {		
		$sql = 'SELECT registered_access, registered_message FROM preferences WHERE id = :id';
		$query = $this -> conn -> prepare($sql);
		$query -> execute(array(':id' => $id));

		$data = $query -> fetchObject();
		return $data;
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
			$this -> updateSwirliaStatistics("emails_sent", "1");
			
			return true;
		} else
			return false;
	}
}
?>