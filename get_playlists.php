<?php
// CORS Headers
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Content-Type: application/json");

// Load database connection
require 'db.php';

try {
    // Fetch all playlists from the database
    $stmt = $pdo->query("SELECT * FROM playlists ORDER BY id DESC");
    $playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($playlists);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>
