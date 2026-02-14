<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $data = [
    "couple_name" => $_POST["name"]
  ];

  $ch = curl_init("http://127.0.0.1:5002/api/vault/create");

  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
  ]);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  echo curl_exec($ch);
}
?>

<form method="POST">
  <input name="name" placeholder="Couple Name">
  <button>Create Vault</button>
</form>
