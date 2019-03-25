<?php
require_once("common.php");  // where we set our config

// this is pretty basic and hacky as hell
// we basically open a connection to the Skylark mp3 stream,
// send the browser the mp3 content type header (with no other info)
// then just start realtime proxying the data to the browser


$stream = (@$_GET['stream']) ? $_GET['stream'] : "/othernet.mp3";
$icecast = $src = "http://".$skylark['domain'].":".$skylark['radport'].$stream;

header('Content-Type: audio/mpeg');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$icecast);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 500);
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
    echo $data;
    return strlen($data);
});
curl_exec($ch);
curl_close($ch);