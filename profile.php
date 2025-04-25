<?php
// Handle preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

// General CORS and response headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// DB connection
$host = 'ftp.kantanudarznieciba.lv';
$db = 'tunetalk';
$user = 'krupis@tune.kantans.com';
$pass = 'WhMdovFD9ljH';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // GET profile by user_id
        $userId = $_GET['user_id'] ?? null;

        if ($userId) {
            $stmt = $conn->prepare("SELECT * FROM profiles WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $profile = $result->fetch_assoc();

            echo json_encode([
                "success" => true,
                "profile" => $profile ?: null
            ]);
        } else {
            echo json_encode(["success" => false, "error" => "Missing user_id"]);
        }
        break;

    case 'POST':
        // Create or update profile
        $data = json_decode(file_get_contents("php://input"), true);

        $userId = $data['user_id'] ?? null;
        $avatar = $data['avatar'] ?? null;
        $bio = $data['bio'] ?? null;
        $caption = $data['caption'] ?? null;

        if (!$userId) {
            echo json_encode(["success" => false, "error" => "Missing user_id"]);
            break;
        }

        // Check if profile exists
        $check = $conn->prepare("SELECT id FROM profiles WHERE user_id = ?");
        $check->bind_param("i", $userId);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // Update existing profile
            $stmt = $conn->prepare("UPDATE profiles SET avatar = ?, bio = ?, caption = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $avatar, $bio, $caption, $userId);
        } else {
            // Insert new profile
            $stmt = $conn->prepare("INSERT INTO profiles (user_id, avatar, bio, caption) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $userId, $avatar, $bio, $caption);
        }

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Profile saved"]);
        } else {
            echo json_encode(["success" => false, "error" => "Profile save failed"]);
        }

        break;
}

$conn->close();
?>
