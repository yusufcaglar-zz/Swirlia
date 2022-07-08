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

$sql = 'INSERT INTO swirlia_statistics () VALUES()';
$query = $conn -> prepare($sql);
$query -> execute();

$sql = 'DELETE FROM chats WHERE currently_chatting = :currently_chatting';
$query = $conn -> prepare($sql);
$query -> execute(array(':currently_chatting' => 0));

$sql = 'UPDATE statistics SET todays_conversations = :todays_conversations, todays_followers = :todays_followers, todays_online = :todays_online';
$query = $conn -> prepare($sql);
$query -> execute(array(':todays_conversations' => 0, ':todays_followers' => 0, ':todays_online' => 0));
?>