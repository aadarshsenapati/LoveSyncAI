<form method="POST">

  <h2>Send Email 💌</h2>

  <input name="to" placeholder="Email">
  <input name="subject" placeholder="Subject">
  <textarea name="message"></textarea>

  <button>Send</button>

</form>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $data = [
    "to" => $_POST["to"],
    "subject" => $_POST["subject"],
    "message" => $_POST["message"]
  ];

  $ch = curl_init("http://127.0.0.1:5003/send-email");

  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
  ]);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  echo curl_exec($ch);
}
?>
