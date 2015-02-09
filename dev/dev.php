<?php

$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_URL, "http://www.yaplakal.com/forum3/st/0/topic610609.html");
curl_setopt($ch, CURLOPT_VERBOSE, true);
$html = curl_exec($ch);
$err = curl_error($ch);

var_dump($html, $err);