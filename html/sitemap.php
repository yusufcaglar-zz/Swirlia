<?php
header("Content-Type: application/xml; charset=utf-8");

print_r(file_get_contents("https://swirlia.net/sitemap.php"));
?>