<?php
session_start();
include "api/supabase.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $data = [
        "partner_name" => $_POST["name"],
        "user_email" => $_SESSION["user"]
    ];

    supabaseRequest(
        "/rest/v1/relationships",
        "POST",
        $data
    );
}
?>

<h2>Relationships ❤️</h2>

<form method="POST">
  <input name="name" placeholder="Partner Name">
  <button>Add</button>
</form>
