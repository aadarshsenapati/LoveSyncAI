<?php

$file = $_FILES["file"]["tmp_name"];
$vault_id = $_POST["vault_id"];

$ch = curl_init("http://127.0.0.1:5002/api/upload");

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
  "file" => new CURLFile($file),
  "vault_id" => $vault_id
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

echo "<h2>Upload Result:</h2>";
echo "<pre>$response</pre>";
?>
