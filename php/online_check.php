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

$sql = 'SELECT id, updated FROM online_users';
$query = $conn -> prepare($sql);
$query -> execute();

if ($query) {
	$result = $query -> fetchAll(PDO::FETCH_ASSOC);
	
	if (count($result) != 0) {
		for($i = 0; $i < count($result); $i++) {
			if(time() - strtotime($result[$i]["updated"]) >= 45)
				logOffUser($conn, $result[$i]["id"]);
		}
	}
}

function logOffUser($conn, $id) {			
	$sql = 'DELETE FROM online_users WHERE id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id));
	
	$sql = 'UPDATE swirlia_statistics SET `logouts` = `logouts` +1 ORDER BY sno DESC LIMIT 1;';
	$query = $conn -> prepare($sql);
	$query -> execute();
	
	updateLastLogout($conn, $id);
}

function updateLastLogout($conn, $id) {
	$sql = 'UPDATE statistics SET last_logout = :last_logout WHERE id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id, ':last_logout' => strval(date('Y-m-d H:i:s', time()))));
	
	updateTodaysOnline($conn, $id);
}

function updateTodaysOnline($conn, $id) {
	$sql = 'SELECT last_login, last_logout FROM statistics WHERE id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id));
	
	$data = $query -> fetchObject();
	$last_login = $data -> last_login;
	$last_logout = $data -> last_logout;
	
	$session_online_time = strtotime($last_logout) - strtotime($last_login);
	
	$sql = 'UPDATE statistics SET `todays_online` = `todays_online` +'.$session_online_time.', `total_online` = `total_online` +'.$session_online_time.' WHERE id = :id';
	$query = $conn -> prepare($sql);
	$query -> execute(array(':id' => $id));
	
	$sql = 'UPDATE swirlia_statistics SET `online_time` = `online_time` +'.$session_online_time.' ORDER BY sno DESC LIMIT 1;';
	$query = $conn -> prepare($sql);
	$query -> execute();
}

/*
if (array_key_exists('HTTP_ORIGIN', $_SERVER))
    $origin = $_SERVER['HTTP_ORIGIN'];
else if (array_key_exists('HTTP_REFERER', $_SERVER))
    $origin = $_SERVER['HTTP_REFERER'];
else
    $origin = $_SERVER['REMOTE_ADDR'];
*/
?>