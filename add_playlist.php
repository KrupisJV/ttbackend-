<?php
// CORS Headers
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Enable error reporting for development (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'error' => 'Only POST requests are allowed.'
    ]);
    exit;
}

// Load database connection
require 'db.php';

// Get JSON payload
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Validate input
if (!isset($data['title'], $data['url'], $data['owner'], $data['cover'])) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: title, url, owner, or cover'
    ]);
    exit;
}

try {
    // Prepare and execute insert
    $stmt = $pdo->prepare("INSERT INTO playlists (title, url, owner, cover) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $data['title'],
        $data['url'],
        $data['owner'],
        $data['cover']
    ]);

    // Return success
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'title' => $data['title'],
        'url' => $data['url'],
        'owner' => $data['owner'],
        'cover' => $data['cover']
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>
