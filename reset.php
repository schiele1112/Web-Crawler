<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Reset DB</title>
</head>
<body>

<?php
$mysqli = new mysqli("localhost", "root", "", "webcrawler");
	
$sql = "delete from word_link;";
if (!$result = $mysqli->query($sql)) {
	echo "Reset hat nicht funktioniert<br>";
} else {
		echo "Reset hat funktioniert<br>";
}

$sql = "delete from link;";
if (!$result = $mysqli->query($sql)) {
	echo "Reset hat nicht funktioniert<br>";
} else {
		echo "Reset hat funktioniert<br>";
}

$sql = "delete from word;";
if (!$result = $mysqli->query($sql)) {
	echo "Reset hat nicht funktioniert<br>";
} else {
		echo "Reset hat funktioniert<br>";
}

$sql = "delete from domain;";
if (!$result = $mysqli->query($sql)) {
	echo "Reset hat nicht funktioniert<br>";
} else {
		echo "Reset hat funktioniert<br>";
}	

$mysqli->close();

?>

</body>
</html> 