<?php
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Methods: *");
// header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");


ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

require_once 'db.php';

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'POST' && isset($_POST['title'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author = $_POST['author_id'];
    $file = $_FILES['image'];

    if (empty($title) || empty($content) || empty($author) || empty($file)) {
        echo json_encode(["error" => "Please fill in all fields, including an image."]);
        http_response_code(400);
        exit();
    }

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_" . basename($file['name']);
    $filePath = $uploadDir . $fileName;

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(["error" => "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed."]);
        http_response_code(400);
        exit();
    }

    if ($file['size'] > 100 * 1024 * 1024) {
        echo json_encode(["error" => "File size exceeds 100MB."]);
        http_response_code(400);
        exit();
    }

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $sql = "INSERT INTO posts (title, content, author_id, image, likes) VALUES (:title, :content, :author_id, :image, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['title' => $title, 'content' => $content, 'author_id' => $author, 'image' => $filePath]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => "Post created successfully.", "image_url" => $filePath]);
        } else {
            echo json_encode(["error" => "Database error: " . $pdo->errorInfo()[2]]);
        }
    } else {
        echo json_encode(["error" => "Failed to upload the file."]);
    }

} elseif ($requestMethod === 'DELETE') {
    // Delete a post
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $pdo->quote($data['id']);

    $sql = "SELECT image FROM posts WHERE id = $id";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch();

    if ($row && file_exists($row['image'])) {
        unlink($row['image']);
    }

    $deleteSql = "DELETE FROM posts WHERE id = $id";
    if ($pdo->exec($deleteSql)) {
        echo json_encode(["success" => "Post deleted successfully."]);
    } else {
        echo json_encode(["error" => "Database error: " . $pdo->errorInfo()[2]]);
    }

} elseif ($requestMethod === 'GET') {

    $sql = "SELECT id, title, content, author_id, image, likes FROM posts";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([]);

    $posts = [];
    while ($row = $stmt->fetch()) {
        $posts[] = $row;
    }

    echo json_encode($posts);

} elseif ($requestMethod === 'PATCH') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['post_id'])) {
        $postId = intval($data['post_id']);

        $checkPostQuery = "SELECT id FROM posts WHERE id = :post_id";
        $stmt = $pdo->prepare($checkPostQuery);
        $stmt->execute(['post_id' => $postId]);

        if ($stmt->rowCount() > 0) {
            $query = "UPDATE posts SET likes = likes + 1 WHERE id = :post_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['post_id' => $postId]);

            $stmt = $pdo->prepare("SELECT likes FROM posts WHERE id = :post_id");
            $stmt->execute(['post_id' => $postId]);
            $row = $stmt->fetch();

            echo json_encode(["likes" => $row["likes"]]);
        } else {
            echo json_encode(["error" => "Post not found"]);
        }
    } else {
        echo json_encode(["error" => "Invalid request"]);
    }
}
?>
