<?php
// callback.php

session_start();

$client_id = 'YOUR_CLIENT_ID';
$client_secret = 'YOUR_CLIENT_SECRET';
$redirect_uri = 'http://localhost/callback.php';

// Check for state parameter to prevent CSRF
if ($_GET['state'] !== $_SESSION['state']) {
    die('State mismatch. CSRF attack detected.');
}

// Retrieve the authorization code
$code = $_GET['code'];

// Exchange authorization code for access token
$token_url = "https://accounts.spotify.com/api/token";
$post_fields = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'client_id' => $client_id,
    'client_secret' => $client_secret,
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    die('Error fetching access token');
}

$data = json_decode($response, true);
$access_token = $data['access_token'];
$_SESSION['access_token'] = $access_token;

// Redirect to playlists page
header('Location: playlists.php');
exit();
