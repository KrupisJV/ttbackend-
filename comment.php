<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Enable error reporting and logging
// ini_set('display_errors', 1); // Show errors during development
// ini_set('log_errors', 1);
// error_reporting(E_ALL);

require_once 'db.php';

if (!$pdo) {
    error_log("Database connection failed");
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
if ($requestMethod === 'GET' ){
    $sql = "SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = :post_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["post_id" => $_GET["post_id"]]);
    $result = $stmt->fetchAll();
    echo json_encode($result);
} else {
    $data = json_decode(file_get_contents("php://input"), true);
    $postId = (int) $data['post_id'];
    $content = trim($data['content']);
    $userId = (int) $data['user_id'];

    // Log values to be inserted
    error_log("Attempting to insert comment: post_id=$postId, user_id=$userId, content=$content");

    try {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, content, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $content, $userId]);

        if ($stmt->rowCount()) {
            error_log("Comment inserted successfully.");
            echo json_encode(["success" => true, "message" => "Comment posted"]);
        } else {
            error_log("Insert failed: No rows affected.");
            echo json_encode(["success" => false, "error" => "Insert failed"]);
        }
    } catch (PDOException $e) {
        error_log("PDO Exception: " . $e->getMessage());
        echo json_encode(["success" => false, "error" => "Database error", "details" => $e->getMessage()]);
    }
}
?>
