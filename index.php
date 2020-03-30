<?php
require_once("common.php"); // where we set our config

$uri = $_SERVER['REQUEST_URI'];

// if the uri requested contains /index.php, strip it out. this is due to the iframe
if (preg_match("/^\/index\.php/",$uri)) $uri = str_replace("/index.php","/",$uri);

/*
// if the uri requested contains FS/get, url_decode it
if (preg_match("/^\/FS/get",$uri)) {
	$uris = preg_split("/get/",$uri);
	$uri = $uris[0]."get".url_decode($uris[1]);
	unset($uris);
	$uri = str_replace("/index.php","/",$uri);
}
print_r($uri);
*/

// build skylark request url
$src = "http://".$skylark['domain'].":".$skylark['wwwport'].$uri;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $src);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // get content
curl_setopt($ch, CURLOPT_HEADER, 1); // get headers
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8); // timeout in seconds
                                             // (keep this short, otherwise it may crash/lag webserver if you have
											 // a lot of hits trying to connect to a dead Dreamcatcher)

// proxy all the headers the browser sends (incl cookies)
$outheader = [];
foreach (getallheaders() as $n => $v) {
	$v = str_replace($_SERVER['HTTP_HOST'],$skylark['domain'],$v);
	if (substr($n,0,4) === "Sec-" ||
		substr($n,0,8) === "Upgrade-" ||
		$n == "Origin") {
			continue;
	}
	$outheader[] = $n.": ".$v;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//	$post = preparePostFields(json_decode(file_get_contents('php://input')));
	$post = file_get_contents('php://input');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $outheader);

// send request to skylark
$response = curl_exec($ch);

// get header size
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

// get status code (yeah yeah its in the headers but this is easier)
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// close curl
curl_close($ch);

// split headers and body
// headers go into an array and we remove last two (which are blank)
$headers = substr($response, 0, $header_size);
$headers = preg_split("/\n/",$headers);
$body = substr($response, $header_size);
for ($i=0;$i<2;$i++) array_pop($headers);

// add our outgoing header.. don't be a dick, let the user know
// (if they are techie enough) they are using a proxy
$headers[] = "X-Content-Proxy: Othernet-Online Proxy Project (C) 2019-2020 Zefie Networks";

if (@$http_code) {
	// I don't like modifying Othernet content but we have to do the following
	// to make certain things work

	$modified_content = false; // log if we modified anything or not

	// Needed to get radio to work, otherwise tries to access port 8090 on webserver
	if ($uri === "/packages/skylark/Radio/combined.js") {
		$body = str_replace(":8090/","/radio.php?stream=",$body);
		$modified_content = true;
	}

	// This bit prevents users from accessing the script outside of the iframe
	// (and thus prevents curcumventing auto-login)
	if ($uri === "/") {
		$body = str_replace("<head>","<head>\n<script>\nif(window.self === window.top) { window.location = 'https://".$_SERVER['HTTP_HOST']."/' }\n</script>",$body);
		$modified_content = true;
	}

	foreach ($headers as $h) {
		// replace any reference to the Skylark's address with ours before sending to the browser
		$h = str_replace($skylark['domain'],$_SERVER['HTTP_HOST'],$h);

		// if we modified content, then the Content-Length probably changed, so we recalculate it
		if (preg_match("/Content-Length/",$h) && $modified_content)	$h = "Content-Length: ".strlen($body);
		// send header to browser
		header($h);
	}
	// send content to browser
	echo $body;
}
