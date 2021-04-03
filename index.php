<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Webcrawler</title>
</head>
<body>

<?php

error_reporting(E_ERROR | E_PARSE);
ini_set('max_execution_time', '1000'); //1000 seconds = 16 minutes

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
			preg_match_all("'<p>(.*?)</p>|<h[1-9]>(.*?)</h[1-9]>'si", $this->markup, $words);
			return !empty($words[1]) ? $words[1] : FALSE;
		}
	}
}


##################################################################################
#	Crawler
##################################################################################

function linkCrawler($link, $mysqli) {
	#echo "Neuer Link<br>";
	##################################################################################
	#	Wörter in DB speichern
	##################################################################################
	try {
		$crawl = new Crawler('https://'.$link);
		$htmlWords = $crawl->get('words');
		
		#Leere Elemente entfernen
		$htmlWords = array_filter($htmlWords);
		
		#HTML-Strings in einzelne Wörter trennen und in Wörter-Array schreiben
		$wordArray = [];
		$stopWords = ["and", "the", "of", "to", "einer", "eine", "eines", "einem", "einen", "der",
						"die", "das", "dass", "daß", "du", "er", "sie", "es", "was", "wer", "wie",
						"wir", "und", "oder", "ohne", "mit", "am", "im", "in", "aus", "auf", "ist",
						"sein", "war", "wird", "ihr", "ihre", "ihres", "ihnen", "ihrer", "als", "für",
						"von", "mit", "dich", "dir", "mich", "mir", "mein", "sein", "kein", "durch",
						"wegen", "wird", "sich", "bei", "beim", "noch", "den", "dem", "zu", "zur",
						"zum", "auf", "ein", "auch", "werden", "an", "des", "sein", "sind", "vor",
						"nicht", "sehr", "um", "unsere", "ohne", "so", "da", "nur", "diese", "dieser",
						"diesem", "dieses", "nach", "über", "mehr", "hat", "bis", "uns", "unser",
						"unserer", "unserem", "unsers", "euch", "euers", "euer", "eurem", "ihr",
						"ihres", "ihrer", "ihrem", "alle", "vom"];
		$badChars = ["#", ",", ";", ".", "nbsp", "(", ")", "&", "<", ">", "\"", "quot", "“", "”", "›", "''", "/", "!", "?", ":"];
		foreach ($htmlWords as $text) {
			$text = strip_tags($text); #HTML-Tags werden entfernt
			$text = preg_replace('/\s+/', ' ', $text); #Anhäufung von Leerzeichen entfernen
			$text = str_replace( $badChars, '', $text); #Unerwünschte Zeichen entfernen
			$arrayFillWords = explode (" ", $text); #Wörter werden bei Leerzeichen getrennt
			
			foreach ($arrayFillWords as $word) {
				if(!$word == "" and !in_array(strtolower($word), $stopWords)) {
					array_push($wordArray, $word);
				}
			}
		}
		
		#Link-ID abfragen
		$sql = "select id from link where uri = '".$link."'";
		$result = $mysqli->query($sql);
		$row = $result->fetch_array(MYSQLI_ASSOC);
		$uriId = $row['id'];

		#Wörter in der DB speichern und mit dem Link verbinden
		foreach ($wordArray as $word) {
			try {
				#echo "Neues Wort<br>";
				#Prüfen ob Wort bereits in DB
				$sql = "select * from word where word = ?";
				$stmt = $mysqli->prepare($sql);
				$stmt->bind_param("s", $word);
				$stmt->execute();
				$result = $stmt->get_result();
				$row_cnt = $result->num_rows;
				#$row = $result->fetch_assoc();
				
				if (!$result) {
					echo "<br>Fehler beim Prüfen ob Wort bereits in DB. Das Wort lautet: " .$word;
					continue;			
				}
				
				#Wort noch nicht in DB
				if(!$row_cnt > 0){
					#Wort in DB speichern
					$sql = "insert into word (word) values (?)";
					$stmt = $mysqli->prepare($sql);
					$stmt->bind_param("s", $word);
					$stmt->execute();
				}
									
				#Verlinkung zur URL erstellen
				$sql = "select id from word where word = ?";
				$stmt = $mysqli->prepare($sql);
				$stmt->bind_param("s", $word);
				$stmt->execute();
				$result = $stmt->get_result();				
				if (!$result) {
					echo "<br>Fehler beim Abfragen der Wort-Id. Das Wort lautet: " .$word;
					continue;			
				}
				$row = $result->fetch_assoc();
				if (empty($row)) {
					echo "<br>Fehler beim Abfragen der Wort-Id. Das Wort lautet: " .$word;
					continue;			
				}			
				$wordId = $row['id'];
				
				$sql = "insert into word_link (id_word, id_link) values (?, ?)";
				$stmt = $mysqli->prepare($sql);
				$stmt->bind_param("ii", $wordId, $uriId);
				$stmt->execute();
				
			} catch (Exception $e) {
					echo "Fehler bei Wort: " .$word;
					echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
			}
		}
		
		
		
		##################################################################################
		#	Links in DB speichern
		##################################################################################
		
		$links = $crawl->get('links');
		#echo $link ."<br>";
		
		foreach($links as $l) {
			try {
				#echo "<br>Link: $l";			
				#Alter Filter
				#if (!str_contains($l, '.css') and !str_contains($l, '.xml') and !str_starts_with($l, 'javascript:' ) and !str_contains($l, '.dll') and !str_contains($l, '.aspx') and !str_contains($l, '.json') and !str_contains($l, '.ico') and !str_contains($l, '.png')) {
				
				#Nur sinnvolle Links abspeichern
				if(preg_match("'(^[^\/]*|^.*\/[^\.]*(|\.(?:php|html|htm)))($|\?.*$)'", $l) and !str_contains($l, 'tel:+')  and !str_contains($l, 'javascript:') and !str_contains($l, '@')) {
					#Domain überprüfen
					if(str_starts_with($l, 'https://') or str_starts_with($l, 'http://')) {
						$sql = "select * from domain";
						$domains = $mysqli->query($sql);
						$gooddomain = 0;
						while ($row = $domains->fetch_array(MYSQLI_ASSOC)) {
							if(str_contains($l, $row['domain'])) {
								$gooddomain = 1;
							}
						}
						if($gooddomain == 0) {
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
					
					# Je nach Link-Typ anderes Vorgehen zum abspeichern verwenden
					if(str_starts_with($l, '/')) {
						
						if(str_contains($link, '/')) {
							$topLink = substr($link, 0, strpos($link, "/"));
							#echo "Fall '/' erkannt. Speichere ".$topLink.$l."<br>";
							$sql = "insert into link (uri, visited) values ('".$topLink.$l."', 0)";
							$result = $mysqli->query($sql);
						} else {
							#echo "Fall '/' erkannt. Speichere ".$link.$l."<br>";
							$sql = "insert into link (uri, visited) values ('".$link.$l."', 0)";
							$result = $mysqli->query($sql);						
						}
					} elseif($l == "") {
					} elseif(str_starts_with($l, 'https://')) {
						$l = str_replace('https://', '', $l);
						#echo "Fall 'https://' erkannt. Speichere ".$l."<br>";
						$sql = "insert into link (uri, visited) values ('".$l."', 0)";
						$result = $mysqli->query($sql);
					} elseif(str_starts_with($l, 'http://')) {
						$l = str_replace('http://', '', $l);
						#echo "Fall 'http://' erkannt. Speichere ".$l."<br>";
						$sql = "insert into link (uri, visited) values ('".$l."', 0)";
						$result = $mysqli->query($sql);
					} else {
						#echo "Kein spezieller Fall erkannt. Speichere ".$link."/".$l."<br>";
						$sql = "insert into link (uri, visited) values ('".$link."/".$l."', 0)";
						$result = $mysqli->query($sql);
					}					
				}
			} catch (Exception $e) {
				echo "Link abspeichern. Fehler bei Link: " .$l;
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
			}
		}
	} catch(Error $e) {
		echo "Fehler beim crawlen von " .$link. "<br>";
	}
}


##################################################################################
#	Worker
##################################################################################

$mysqli = new mysqli("localhost", "root", "", "webcrawler");
$sql = "select * from link where TIMEDIFF(now(),time_stamp) > '00:10:00' or time_stamp is NULL";
if (!$result = $mysqli->query($sql)) {
	echo "Worker: Fehler beim Abfragen der zu crawlenden Links";
} else {
	while ($row = $result->fetch_array(MYSQLI_ASSOC)) {		
		linkCrawler($row['uri'], $mysqli);
		$sql = "UPDATE link
				SET time_stamp = now()
				where uri = '".$row['uri']."'";
		$result2 = $mysqli->query($sql);
	}		
}
$result->close();
$mysqli->close();
?>
</body>
</html> 