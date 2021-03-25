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
$mysqli = new mysqli("localhost", "root", "", "webcrawler");
$mysqli2 = new mysqli("localhost", "root", "", "webcrawler");
	
$sql = "select uri from link where visited = 0";
#$sql = "select uri from link";
if (!$result = $mysqli->query($sql)) {
	#FAIL
} else {
	while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
		#echo 'https://'.$row['uri'];
		$crawl = new Crawler('https://'.$row['uri']);
		$links = $crawl->get('links');
		foreach($links as $l) {
			try {
				#echo "<br>Link: $l";
				if(str_starts_with($l, '/')) {
					$sql = "insert into link (uri, visited) values ('".$row['uri'].$l."', 0)";
					#echo "<br>$sql";
					$result2 = $mysqli2->query($sql);
				} elseif(str_starts_with($l, 'https://')) {
					$l = str_replace('https://', '', $l);
					$sql = "insert into link (uri, visited) values ('".$row['uri'].$l."', 0)";
					#echo "<br>$sql";
					$result2 = $mysqli2->query($sql);
				} else {
					$sql = "insert into link (uri, visited) values ('".$row['uri']."/".$l."', 0)";
					#echo "<br>$sql";
					$result2 = $mysqli2->query($sql);
				}
			} catch (Exception $e) {
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
			}				
		}
		$sql = "update link
				set visited = 1
				where uri = '".$row['uri']."'";
		$result2 = $mysqli2->query($sql);
	}		
}
$result->close();
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