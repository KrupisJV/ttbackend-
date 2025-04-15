<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "tunetalk";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input["username_or_email"]) || !isset($input["password"])) {
        http_response_code(400);
        echo json_encode(["message" => "Username or email and password are required."]);
        exit;
    }

    $username_or_email = sanitize_input($input["username_or_email"]);
    $password = sanitize_input($input["password"]);

    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $stmt->store_result();

    // Check if the user exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $email, $hashed_password);
        $stmt->fetch();

        // Check if the password is correct
        if (password_verify($password, $hashed_password)) {
            // Generate a new access token
            $token = bin2hex(random_bytes(16)); // Generating a random token
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expiration time

            // Store the token in the database
            $token_stmt = $conn->prepare("INSERT INTO access_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $token_stmt->bind_param("iss", $id, $token, $expires_at);
            if ($token_stmt->execute()) {
                // Send the response with the access token
                http_response_code(200); // OK
                echo json_encode([
                    "message" => "Login successful",
                    "user" => [
                        "user_id" => $id,
                        "username" => $username,
                        "email" => $email,
                        "access_token" => $token,
                        "expires_at" => $expires_at
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to generate access token."]);
            }
            $token_stmt->close();
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Invalid password"]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["message" => "User not found"]);
    }

    $stmt->close();
    $conn->close();
}
?>
