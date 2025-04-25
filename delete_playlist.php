<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests for DELETE method
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: DELETE");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

// Ensure it's a DELETE request
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => true, "message" => "Only DELETE method is allowed"]);
    exit();
}

// Validate playlist ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => true, "message" => "Missing or invalid playlist ID"]);
    exit();
}

$playlistId = (int) $_GET['id']; // Cast to int for safety

// Database connection
$host = 'localhost';
$dbname = 'tunetalk';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: Check if playlist exists before deleting
    $checkStmt = $pdo->prepare("SELECT id FROM playlists WHERE id = :id");
    $checkStmt->execute([':id' => $playlistId]);

    if ($checkStmt->rowCount() === 0) {
        http_response_code(404); // Not Found
        echo json_encode(["error" => true, "message" => "Playlist not found"]);
        exit();
    }

    // Delete the playlist
    $deleteStmt = $pdo->prepare("DELETE FROM playlists WHERE id = :id");
    $deleteStmt->bindParam(':id', $playlistId, PDO::PARAM_INT);

    if ($deleteStmt->execute()) {
        echo json_encode(["error" => false, "message" => "Playlist deleted successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => true, "message" => "Failed to delete playlist"]);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("PDO Error: " . $e->getMessage()); // Log for debugging
    echo json_encode(["error" => true, "message" => "Database error occurred"]);
}
