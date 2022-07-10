<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');
date_default_timezone_set('CET');
set_time_limit(0);

$searchkeys = [
	'some_searchkey',
	'another_searchkey'
];

$url = "https://rule34.xxx/index.php?page=post&s=list&tags=".implode("+", $searchkeys);

function flushText(string $text) : void
{
  echo $text;
  echo PHP_EOL;
  ob_flush();
  flush();
}

function get_string_between($string, $start, $end) : string
{
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0)
        return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function getCurl($link) : string
{
  $mozillaheaders = [
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:101.0) Gecko/20100101 Firefox/101.0",
    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
    "Accept-Language: en-US,en;q=0.5",
    "Alt-Used: rule34.xxx",
    "Connection: keep-alive",
    "Upgrade-Insecure-Requests: 1",
    "Sec-Fetch-Dest: document",
    "Sec-Fetch-Mode: navigate",
    "Sec-Fetch-Site: same-origin",
    "Sec-Fetch-User: ?1",
    "TE: trailers"
  ];

  $c = curl_init();
  curl_setopt($c, CURLOPT_URL, $link);
  curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($c, CURLOPT_FOLLOWLOCATION, 0);
  curl_setopt($c, CURLOPT_TIMEOUT, 30);
  curl_setopt($c, CURLOPT_CAINFO, 'cacert.pem');
  curl_setopt($c, CURLOPT_HTTPHEADER,$mozillaheaders);
  $result = curl_exec($c);
  curl_close($c);
  return $result;
}

if (ob_get_level() == 0) ob_start();

$idNodes = [];

while (( count($idNodes) % 42 ) === 0) {
  $html = getCurl($url."&pid=".count($idNodes));
  if (!$html) {
    flushText("document not found"); exit;
  }
  $doc = new DOMDocument();
  $doc->loadHTML($html);
  $nodes = $doc->getElementById('post-list')->getElementsByTagName('div');
  for ($i=0; $i < $nodes->count(); $i++) {
    $node = $nodes->item($i);
    if ($node->attributes->item(0)->nodeName == "class" && $node->attributes->item(0)->nodeValue == "image-list" ) {
      $images = $node->getElementsByTagName("span");
      for ($j=0; $j < $images->count(); $j++) {
        $image = $images->item($j)->getElementsByTagName("a")->item(0)->attributes->getNamedItem('href')->nodeValue;
        $idNodes[] = substr($image, strpos($image, "&id=") + 4);
      }
      break;
    }
  }
}

for ($i=0; $i < count($idNodes); $i++) {
  flushText(($i+1)."/".count($idNodes));
  $pageurl = "https://rule34.xxx/index.php?page=post&s=view&id=".$idNodes[$i];
  $result = getCurl($pageurl);
  if ($result) {
    $imgelement = get_string_between($result,"<img alt=\"","\">");
    if ($imgelement) {
      $imglink = get_string_between($imgelement,"src=\"","\"");
      if ($imglink) {
        $result = getCurl($imglink);
        mkdir("images");
        file_put_contents( "images/".basename(get_string_between($imglink,".xxx//","?")), $result);
      }
    }
  }
}

flushText("done");

 ?>
