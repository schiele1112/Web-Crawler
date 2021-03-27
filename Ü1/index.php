<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>DB</title>
</head>
<body>
<style>
	table {
	  font-family: arial, sans-serif;
	  border-collapse: collapse;
	}

	td, th {
	  border: 1px solid #dddddd;
	  text-align: left;
	  padding: 8px;
	}

	tr:nth-child(even) {
	  background-color: #dddddd;
	}
</style>
<?php
 
if(isset($_POST['register'])) {

    $firstname = $_POST['firstname'];
	$lastname = $_POST['lastname'];
	$age = $_POST['age'];
    	
	$mysqli = new mysqli("localhost", "root", "", "mydb");	
	$sql = "insert into user (firstname, lastname, age) values ('".$firstname."', '".$lastname."', ".$age.");";
	$result = $mysqli->query($sql);
	$mysqli->close();
}

if(isset($_POST['delete'])) {

    $user_id = $_POST['user_id'];
	
	$mysqli = new mysqli("localhost", "root", "", "mydb");	
	$sql = "delete from user where id = ".$user_id.";";
	$result = $mysqli->query($sql);
	$mysqli->close();
}
 
?>

<h2>Benutzer anlegen</h2>
<form method="post">
	Vorname:<br>
	<input size="40" name="firstname" required="true"><br><br>
	Nachname:<br>
	<input size="40" name="lastname" required="true"><br><br>
	Alter:<br>
	<input size="40" name="age" required="true"><br><br>
	 
	<input type="submit" name="register" value="Abschicken">
</form>


<h2>Benutzer löschen</h2>
<form method="post">
	Benutzer ID:<br>
	<input size="40" name="user_id" required="true"><br><br>
	
	<input type="submit" name="delete" value="Benutzer löschen">
</form>

<h2>Benutzer</h2>
<?php
	$mysqli = new mysqli("localhost", "root", "", "mydb");
	
	$sql = "select u.lastname as 'Nachname', u.age as 'Alter', c.brand as 'Automarke', u.id as 'Benutzer ID'
			from user u
			left join car c on c.id = u.id_car";
	if (!$result = $mysqli->query($sql)) {
		/* FAIL */
	} else {
		# create table
		$table = '<table>';
		$header = false;
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {	
			if($header==false){
			  $table .= '<thead><tr><th>'.implode('</th><th>',array_keys($row)).'</tr></thead><tbody>';
			  $header=true;
			}
			$table .= '<tr><td>'.implode('</td><td>',$row).'</td></tr>';
		}
		$table .= '</tbody></table>';		
		echo $table;		
	}
	$result->close();
?>

</body>
</html> 