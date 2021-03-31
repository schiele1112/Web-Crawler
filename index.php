<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>DB</title>
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
</head>
<body>

<?php
class Crawler {
	
	protected $markup = '';
	public $base = '';
	
	public function __construct($uri) {
		$this->base = $uri;
		$this->markup = $this->getMarkup($uri);
	}
	
	public function getMarkup($uri) {
		return file_get_contents($uri);
	}
	
	public function get($type) {
		$method = "_get_{$type}";
		if (method_exists($this, $method)){
			return call_user_func(array($this, $method));
		}
	}
	
	protected function _get_images() {
		if (!empty($this->markup)){
			preg_match_all('/<img([^>]+)\/>/i', $this->markup, $images);
			return !empty($images[1]) ? $images[1] : FALSE;
		}
	}
	
	protected function _get_links() {
		if (!empty($this->markup)){
			//preg_match_all('/<a([^>]+)\>(.*?)\<\/a\>/i', $this->markup, $links);
			preg_match_all('/href=\"(.*?)\"/i', $this->markup, $links);
			return !empty($links[1]) ? $links[1] : FALSE;
		}
	}
	
	protected function _get_words() {
		if (!empty($this->markup)){
			//preg_match_all('/<a([^>]+)\>(.*?)\<\/a\>/i', $this->markup, $links);
			#preg_match_all('/<p>(.*?)</p>/', $this->markup, $words);
			#preg_match("'<p>(.*?)</p>'si", $source, $match);
			preg_match_all("'<p>(.*?)</p>'si", $this->markup, $words);
			return !empty($words[1]) ? $words[1] : FALSE;
		}
	}
}

/*
crawl ( $URL )
{
	speichere $URL in die DB als besuchte Seite
	mit Angaben wie title-Inhalt, und Schlagworte.
	
	für alle Links $link in der mit URL adressierten Seite
	{
		wenn ($link in der Form "/...")
			füge $link links "http://<hostname_von_$URL>" an
		wenn ($link in der Form "...")
			füge $link links "http://<hostname_von_$URL>/<dir_von_$URL>/" an
		wenn ($link in der DB nicht als besuchte Seite gespeichert ist)
			crawl ( $link );
	}
}
*/

#$crawl = new Crawler('http://www.dhbw-heidenheim.de');
#$crawl = new Crawler('https://test.de');
#$images = $crawl->get('images');
#$links = $crawl->get('links');

?>

<h2>Webcrawler</h2>

<?php

function LinkCrawler($link, $mysqli)
{
	$crawl = new Crawler('https://'.$link);
	$links = $crawl->get('links');
	$words = $crawl->get('words');
	#Worter abspeichern
	echo $link;
	var_dump($words);
	echo"<br>";
	
	#Links abspeichern
			foreach($links as $l) {
			try {
				#echo "<br>Link: $l";
				if (!str_contains($l, '.css') and !str_contains($l, '.xml') and !str_starts_with($l, 'javascript:' ) and !str_contains($l, '.dll') and !str_contains($l, '.aspx') ) {
					#Domain überprüfen
					if(str_starts_with($l, 'https://') or str_starts_with($l, 'http://'))
					{
						$sql = "select * from domain";
						$domains = $mysqli->query($sql);
						$gooddomain = 0;
						while ($row = $domains->fetch_array(MYSQLI_ASSOC)) {
							if(str_contains($l, $row['domain']))
							{
								$gooddomain = 1;
							}
						}
						if($gooddomain == 0)
						{
							#echo $l."<br>";
							continue;
						}
					}
					# Hashtags entfernen
					if(str_contains($l, '#'))
						$l = substr($l, 0, strpos($l, "#"));
						
					# / am Ende entfernen
					if(substr($l, -1) == "/") {
						$l = substr($l, 0, -1);
					}
							
					if(str_starts_with($l, '/')) {
						$sql = "insert into link (uri, visited) values ('".$link.$l."', 0)";
						$result2 = $mysqli->query($sql);
					} elseif($l == "") {
					}
					elseif(str_starts_with($l, 'https://')) {
						$l = str_replace('https://', '', $l);
						$sql = "insert into link (uri, visited) values ('".$l."', 0)";
						$result2 = $mysqli->query($sql);
					} elseif(str_starts_with($l, 'http://')) {
						$l = str_replace('http://', '', $l);
						$sql = "insert into link (uri, visited) values ('".$l."', 0)";
						$result2 = $mysqli->query($sql);
					} else {
						$sql = "insert into link (uri, visited) values ('".$link."/".$l."', 0)";
						$result2 = $mysqli->query($sql);
					}
						
				}
			} catch (Exception $e) {
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
			}				
		}
}
/*
foreach($links as $l) {
	if (substr($l,0,7)!='http://')
		#echo "<br>Link: $crawl->base/$l";
		echo "<br>Link1: $l";
		if(str_starts_with($l, '/')) {
			$crawl2 = new Crawler('https://test.de'.$l);
			$links2 = $crawl2->get('links');
			foreach($links2 as $l2) {
				echo "<br>Link2: $l2";
			}
		}
}
*/
ini_set('max_execution_time', '1000'); //1000 seconds = 16 minutes
# Links von DB holen

##############################################################################
$mysqli = new mysqli("localhost", "root", "", "webcrawler");
	
#Worker
#$sql = "select uri from link where visited = 0";
$sql = "select uri from link where time_stamp = '' or (now() - time_stamp)>'00:00:00'";
#$sql = "select uri from link";
if (!$result = $mysqli->query($sql)) {
	#FAIL
} else {
	while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
		#echo 'https://'.$row['uri'];
		
		LinkCrawler($row['uri'], $mysqli);
		$sql = "UPDATE link 
				SET time_stamp = now()
				where uri = '".$row['uri']."'";
		$result2 = $mysqli->query($sql);
	}		
}
$result->close();
##############################################################################
?>

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


<?php
/*
	$mysqli = new mysqli("localhost", "root", "", "mydb");
	
	$sql = "select u.lastname as 'Nachname', u.age as 'Alter', c.brand as 'Automarke', u.id as 'Benutzer ID'
			from user u
			left join car c on c.id = u.id_car";
	if (!$result = $mysqli->query($sql)) {
		#FAIL
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
	*/
?>

</body>
</html> 