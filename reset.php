<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Reset DB</title>
</head>
<body>

<?php
$mysqli = new mysqli("localhost", "root", "", "webcrawler");
	
$sql = "delete from link;";
if (!$result = $mysqli->query($sql)) {
	echo "Reset hat nicht funktioniert";
} else {
	#$sql = "INSERT INTO `link` (`id`, `uri`, `time_stamp`, `visited`) VALUES (NULL, 'www.dhbw-heidenheim.de', current_timestamp(), '0');";
	#if (!$result = $mysqli->query($sql)) {
	#	echo "Reset hat nicht funktioniert";
	#} else {
		echo "Reset hat funktioniert";
	#}
}

$sql = "delete from word;";
if (!$result = $mysqli->query($sql)) {
	echo "Reset hat nicht funktioniert";
} else {
		echo "Reset hat funktioniert";
}

$sql = "delete from word_link;";
if (!$result = $mysqli->query($sql)) {
	echo "Reset hat nicht funktioniert";
} else {
		echo "Reset hat funktioniert";
}

$sql = "delete from domain;";
if (!$result = $mysqli->query($sql)) {
	echo "Reset hat nicht funktioniert";
} else {
		echo "Reset hat funktioniert";
}	

$mysqli->close();

?>

</body>
</html> 