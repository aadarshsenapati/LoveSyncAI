<?php

$SUPABASE_URL = "https://oovgmrbpoetqsdzcfpco.supabase.co";
$SUPABASE_KEY = "sb_publishable_GzUiG1RJQVwYYjwaafUyug_48rAvTB7";

function supabaseRequest($endpoint, $method = "GET", $data = null) {

    global $SUPABASE_URL, $SUPABASE_KEY;

    $ch = curl_init($SUPABASE_URL . $endpoint);

    $headers = [
        "apikey: $SUPABASE_KEY",
        "Authorization: Bearer $SUPABASE_KEY",
        "Content-Type: application/json"
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method == "POST") {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
?>
