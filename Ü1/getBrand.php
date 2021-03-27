<?php
 
if(isset($_GET['lastname'])) {

	$lastname = $_GET['lastname'];

	$mysqli = new mysqli("localhost", "root", "", "mydb");	
	$sql = "select c.brand
			from user u
			left join car c on c.id = u.id_car
			where u.lastname = ?";		
	$stmt = $mysqli->prepare($sql);
	$stmt->bind_param("s", $lastname);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	echo $row['brand'];
	$stmt->close();
}

if(isset($_GET['id'])) {

    $id = $_GET['id'];
	if($id ==3)
		exit;
	
    $mysqli = new mysqli("localhost", "root", "", "mydb");	
	$sql = "select c.brand
			from user u
			left join car c on c.id = u.id_car
			where u.id = ?";
	$stmt = $mysqli->prepare($sql);
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	echo $row['brand'];
	$stmt->close();
}
?>