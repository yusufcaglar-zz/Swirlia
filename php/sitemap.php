<?php
$base_url = "https://swirlia.com/";

$host = 'localhost';
$user = 'root';
$pass = 'Q4!e\9rr7u83#t#A';
$db = 'swirlia';
$conn;

$conn = new PDO("mysql:host=".$host.";dbname=".$db, $user, $pass);
$conn -> exec("SET GLOBAL time_zone='+00:00';");

$sql = 'SELECT username FROM users WHERE deleted = :deleted';
$query = $conn -> prepare($sql);
$query -> execute(array(':deleted' => 0));

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL; 
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'.PHP_EOL;

echo '<url>'.PHP_EOL;
echo '<loc>'.$base_url.'</loc>'.PHP_EOL;
echo '<changefreq>daily</changefreq>'.PHP_EOL;
echo '</url>'.PHP_EOL;

if ($query) {
	$result = $query -> fetchAll(PDO::FETCH_ASSOC);
	
	if (count($result) != 0) {
		for($i = 0; $i < count($result); $i++) {
			echo '<url>'.PHP_EOL;
			echo '<loc>'.$base_url.$result[$i]["username"].'</loc>'.PHP_EOL;
			echo '<changefreq>daily</changefreq>'.PHP_EOL;
			echo '</url>'.PHP_EOL;
		}
	}
}

echo '</urlset>'.PHP_EOL;
?>