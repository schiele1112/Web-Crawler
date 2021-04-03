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
			#preg_match_all("'<p>(.*?)</p>'si", $this->markup, $words);
			#preg_match_all("'<h[0-9]>(.*?)</h[0-9]>'si", $this->markup, $words);
			preg_match_all("'<p>(.*?)</p>|<h[1-9]>(.*?)</h[1-9]>'si", $this->markup, $words);
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
	#echo "Neuer Link<br>";
	$crawl = new Crawler('https://'.$link);
	$links = $crawl->get('links');
	$words = $crawl->get('words');
	
	#echo $link ."<br>";
	#Leere Elemente entfernen
	$words = array_filter($words);
	
	$wordArray = [];
	
	foreach ($words as $text) 
	{
		$text = strip_tags($text); #HTML-Tags werden entfernt
		$text = preg_replace('/\s+/', ' ', $text); #Anhäufung von Leerzeichen entfernen
		$arrayfillword = explode (" ", $text); #Wörter werden bei Leerzeichen getrennt
		
		foreach ($arrayfillword as $text2)
		{
			if(!$text2 == "")
			{
				array_push($wordArray, $text2);
			}
		}
	}
	
	#var_dump($wordArray);
	
	$sql = "select id from link where uri = '".$link."'";
	$result = $mysqli->query($sql);
	$row = $result->fetch_array(MYSQLI_ASSOC);
	$uriId = $row['id'];

	#Worter in DB abspeichern
	
	foreach ($wordArray as $word) {
		try {
			#echo "Neues Wort<br>";
			#Prüfen ob Wort bereits in DB
			#$sql = "select * from word where word = '".$word."'";
			#$bool = $mysqli->query($sql);
			#$row = $bool->fetch_array(MYSQLI_NUM);
			#$query = mysqli_query($mysqli, "select * from word where word = '".$word."'");

			$sql = "select * from word where word = ?";
			$stmt = $mysqli->prepare($sql);
			$stmt->bind_param("s", $word);
			$stmt->execute();
			$result = $stmt->get_result();
			$row_cnt = $result->num_rows;
			#$row = $result->fetch_assoc();
			
			if (!$result)
			{
				echo "<br>Fehler beim Prüfen ob Wort bereits in DB. Das Wort lautet: " .$word;
				continue;			
			}

			if($row_cnt > 0){
				#Wort exisitiert bereits
				
				#Verlinkung zur URL erstellen
				$sql = "select id from word where word = ?";
				$stmt = $mysqli->prepare($sql);
				$stmt->bind_param("s", $word);
				$stmt->execute();
				$result = $stmt->get_result();
				$row = $result->fetch_assoc();			
				#$sql = "select id from word where word = '".$word."'";
				#$result = $mysqli->query($sql);
				#$row = $result->fetch_array(MYSQLI_ASSOC);
				$wordId = $row['id'];
				
				$sql = "insert into word_link (id_word, id_link) values (?, ?)";
				$stmt = $mysqli->prepare($sql);
				$stmt->bind_param("ii", $wordId, $uriId);
				$stmt->execute();
				#$sql = "insert into word_link (id_word, id_link) values (".$wordId.", ".$uriId.")";
				#$result = $mysqli->query($sql);
				
			}else{
				#Wort exisitiert nicht
				$sql = "insert into word (word) values (?)";
				$stmt = $mysqli->prepare($sql);
				$stmt->bind_param("s", $word);
				$stmt->execute();
				#$sql = "insert into word (word) values ('".$word."')";
				#$result = $mysqli->query($sql);
				
				#Verlinkung zur URL erstellen
				$sql = "select id from word where word = ?";
				$stmt = $mysqli->prepare($sql);
				$stmt->bind_param("s", $word);
				$stmt->execute();
				$result = $stmt->get_result();				
				#$sql = "select id from word where word = '".$word."'";
				#$result = $mysqli->query($sql);
				if (!$result)
				{
					echo "<br>Fehler beim Prüfen ob Wort bereits in DB. Das Wort lautet: " .$word;
					continue;			
				}
				$row = $result->fetch_assoc();
				#$row = $result->fetch_array(MYSQLI_ASSOC);
				$wordId = $row['id'];
				
				$sql = "insert into word_link (id_word, id_link) values (?, ?)";
				$stmt = $mysqli->prepare($sql);
				$stmt->bind_param("ii", $wordId, $uriId);
				$stmt->execute();
				#$sql = "insert into word_link (id_word, id_link) values (".$wordId.", ".$uriId.")";
				#$result = $mysqli->query($sql);	
			}
		} catch (Exception $e) {
				echo "Fehler bei Wort: " .$word;
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
		}
	}
	
	#(^[^\/]*|^.*\/[^\.]*(|\.(?:php|html|htm)))($|\?.*$)
	
	#die();
	#Links abspeichern
			foreach($links as $l) {
			try {
				#echo "<br>Link: $l";
				
				#Alter Filter
				#if (!str_contains($l, '.css') and !str_contains($l, '.xml') and !str_starts_with($l, 'javascript:' ) and !str_contains($l, '.dll') and !str_contains($l, '.aspx') and !str_contains($l, '.json') and !str_contains($l, '.ico') and !str_contains($l, '.png')) {
				
				#preg_match_all("'<p>(.*?)</p>|<h[1-9]>(.*?)</h[1-9]>'si", $this->markup, $words);
					
				if(preg_match("'(^[^\/]*|^.*\/[^\.]*(|\.(?:php|html|htm)))($|\?.*$)'", $l) and !str_contains($l, 'tel:+')  and !str_contains($l, 'javascript:') ) {
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
						
						if(str_contains($link, '/')) {
							$topLink = substr($link, 0, strpos($link, "/"));
							#echo "Fall '/' erkannt. Speichere ".$topLink.$l."<br>";
							$sql = "insert into link (uri, visited) values ('".$topLink.$l."', 0)";
							$result2 = $mysqli->query($sql);
						} else {
							#echo "Fall '/' erkannt. Speichere ".$link.$l."<br>";
							$sql = "insert into link (uri, visited) values ('".$link.$l."', 0)";
							$result2 = $mysqli->query($sql);						
						}					
						/*
						# Falls Link zu aktueller Seite führt wird der Link nicht gespeichert
						$lastSection = "/" . substr($link, strrpos($link, '/') + 1);					
						if($l == $lastSection) {
							continue;
						}					
						echo "<br>";
						echo $lastSection;
						echo $l;
						echo "<br>";
						echo "Fall '/' erkannt. Speichere ".$link.$l."<br>";
						$sql = "insert into link (uri, visited) values ('".$link.$l."', 0)";
						$result2 = $mysqli->query($sql);
						*/
					} elseif($l == "") {
					}
					elseif(str_starts_with($l, 'https://')) {
						$l = str_replace('https://', '', $l);
						#echo "Fall 'https://' erkannt. Speichere ".$l."<br>";
						$sql = "insert into link (uri, visited) values ('".$l."', 0)";
						$result2 = $mysqli->query($sql);
					} elseif(str_starts_with($l, 'http://')) {
						$l = str_replace('http://', '', $l);
						#echo "Fall 'http://' erkannt. Speichere ".$l."<br>";
						$sql = "insert into link (uri, visited) values ('".$l."', 0)";
						$result2 = $mysqli->query($sql);
					} else {
						#echo "Kein spezieller Fall erkannt. Speichere ".$link."/".$l."<br>";
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
#$sql = "select uri from link where time_stamp = '' or (now() - time_stamp)>'24:00:00'";
$sql = "select * from link where TIMEDIFF(now(),time_stamp) > '00:10:00' ";
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
$mysqli->close();
##############################################################################
?>
</body>
</html> 