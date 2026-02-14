<?php

$file = $_FILES["file"]["tmp_name"];

$ch = curl_init("http://127.0.0.1:5001/analyze-zip");

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
  "file" => new CURLFile($file)
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

echo $response;
?>
