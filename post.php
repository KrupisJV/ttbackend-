<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: Content-Type");

// Increase upload limits dynamically
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "tunetalk";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'POST' && isset($_POST['title'])) {
    // Create a new post
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $author = $conn->real_escape_string($_POST['author']);
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

    if ($file['size'] > 100 * 1024 * 1024) { // 100MB limit
        echo json_encode(["error" => "File size exceeds 100MB."]);
        http_response_code(400);
        exit();
    }

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $sql = "INSERT INTO posts (title, content, author, image, likes) VALUES ('$title', '$content', '$author', '$filePath', 0)";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["success" => "Post created successfully.", "image_url" => $filePath]);
        } else {
            echo json_encode(["error" => "Database error: " . $conn->error]);
        }
    } else {
        echo json_encode(["error" => "Failed to upload the file."]);
    }

} elseif ($requestMethod === 'DELETE') {
    // Delete a post
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $conn->real_escape_string($data['id']);

    $sql = "SELECT image FROM posts WHERE id = '$id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    if ($row && file_exists($row['image'])) {
        unlink($row['image']); // Delete the image file
    }

    $deleteSql = "DELETE FROM posts WHERE id = '$id'";
    if ($conn->query($deleteSql) === TRUE) {
        echo json_encode(["success" => "Post deleted successfully."]);
    } else {
        echo json_encode(["error" => "Database error: " . $conn->error]);
    }

} elseif ($requestMethod === 'GET') {
    // Fetch all posts
    $sql = "SELECT id, title, content, author, image, likes FROM posts";
    $result = $conn->query($sql);

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    echo json_encode($posts);

} elseif ($requestMethod === 'PATCH') {
    // Handle liking a post
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['post_id'])) {
        $postId = intval($data['post_id']);
        
        $query = "UPDATE posts SET likes = likes + 1 WHERE id = $postId";
        if ($conn->query($query)) {
            $result = $conn->query("SELECT likes FROM posts WHERE id = $postId");
            $row = $result->fetch_assoc();
            echo json_encode(["likes" => $row["likes"]]);
        } else {
            echo json_encode(["error" => "Failed to update likes"]);
        }
    } else {
        echo json_encode(["error" => "Invalid request"]);
    }
}

$conn->close();
?>
