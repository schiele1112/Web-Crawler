<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Suche</title>
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
 
if(isset($_POST['addUrl'])) {

    $url = $_POST['url'];
    	
	$mysqli = new mysqli("localhost", "root", "", "webcrawler");	
	$sql = "insert into domain (domain) values ('".$url."');";
	$result = $mysqli->query($sql);
	$sql = "insert into link (uri) values ('".$url."');";
	$result = $mysqli->query($sql);
	$mysqli->close();
}
 
?>

<h2>URL hinzufügen</h2>
<form method="post">
	URL im Format 'www.xyz.de':<br>
	<input size="40" name="url" required="true"><br><br>	 
	<input type="submit" name="addUrl" value="Hinzufügen">
</form>
<br>
<hr>
<h2>DHBW Suche</h2>
<form method="post">
	Suchbergriff:<br>
	<input size="40" name="searchString" required="true"><br><br>	 
	<input type="submit" name="search" value="Suchen">
</form>

<?php

	if(isset($_POST['search'])) {		
	
		$searchString = $_POST['searchString'];
		
		#Suchbegriffe bei Leerzeichen trennen
		$searchWords = explode (" ", $searchString);
		
		#SQL-Abfrage zusammenbauen
		$sql = "select DISTINCT l.uri as 'Links'
				from word_link wl
				left join link l on l.id = wl.id_link
				left join word w on w.id = wl.id_word
				where ";
		$first = 1;
		foreach($searchWords as $searchWord) {
			if($first == 1) {
				$sql .= "w.word like '%".$searchWord."%'";
				$first = 0;
			} else {
				$sql .= " or w.word like '%".$searchWord."%'";
			}			
		}
		
		$mysqli = new mysqli("localhost", "root", "", "webcrawler");		
		
		if (!$result = $mysqli->query($sql)) {
			/* FAIL */
		} else {
			# create table
			$table = '<table>';
			$header = false;
			$counter = 0;
			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {	
				if($header==false){
				  $table .= '<thead><tr><th>'.implode('</th><th>',array_keys($row)).'</tr></thead><tbody>';
				  $header=true;
				}
				$table .= '<tr><td><a href="https://'.implode('</td><td>',$row).'">https://'.implode('</td><td>',$row).'</a></td></tr>';
				$counter++;
			}
			$table .= '</tbody></table>';
			echo "<br><hr>";
			echo "<h2> Ergebnisse </h2>";
			echo "Die Suche nach <b>'" .$searchString.  "'</b> ergab " .$counter. " Treffer<br><br>";
			echo $table;		
		}
		$result->close();
	}
?>

</body>
</html> 