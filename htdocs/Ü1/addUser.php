<!DOCTYPE html> 
<html> 
<head>
	<meta charset="utf-8">
	<title>Nutzer anlegen</title>    
</head> 
<body>
 
<?php
 
if(isset($_GET['register'])) {

    $firstname = $_POST['firstname'];
	$lastname = $_POST['lastname'];
	$age = $_POST['age'];
    
	
	$mysqli = new mysqli("localhost", "root", "", "mydb");
	
	$sql = "insert into user (firstname, lastname, age) values ('".$firstname."', '".$lastname."', ".$age.");";
	$result = $mysqli->query($sql);

	$mysqli->close();
}
 
?>
 
<h2 style="text-align: center;">Benutzer anlegen</h2>
 
<br>
 
<form style="width: revert; text-align: center;" action="?register=1" method="post">

Vorname:<br>
<input size="40" maxlength="250" name="firstname" required="true"><br><br>
Nachname:<br>
<input size="40" maxlength="250" name="lastname" required="true"><br><br>
Alter:<br>
<input size="40" maxlength="250" name="age" required="true"><br><br>
 
<input class="button" type="submit" value="Abschicken">
</form>
 
<?php
?>
 
</body>
</html>