<?php
// spotify-token.php

// Your Spotify App's client credentials (replace these with actual values)
$client_id = '402c4e3af5554604802aa8bb055947e3';
$client_secret = '184c754436664e69a44e376d9e7dbbac';

// Step 1: Get the Spotify access token using Client Credentials Flow
$token_url = "https://accounts.spotify.com/api/token";

// Prepare the Authorization header using Base64 encoding of client_id and client_secret
$auth_header = base64_encode("$client_id:$client_secret");

$data = [
    'grant_type' => 'client_credentials',
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $auth_header,
    'Content-Type: application/x-www-form-urlencoded',
]);

$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    die('Error fetching access token');
}

$token_data = json_decode($response, true);
$access_token = $token_data['access_token'];

// Return the access token as a JSON response
header('Content-Type: application/json');
echo json_encode(['access_token' => $access_token]);
?>
