<?php
// Database connection settings
$host = 'localhost';
$db = 'tunetalk';
$user = 'root';
$pass = 'root';

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to create a new access token
function createAccessToken($user_id, $token, $expires_at) {
    global $pdo;
    $sql = "INSERT INTO access_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id, 'token' => $token, 'expires_at' => $expires_at]);
}

// Function to fetch all access tokens
function getAccessTokens() {
    global $pdo;
    $sql = "SELECT * FROM access_tokens";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to delete an access token by ID
function deleteAccessToken($id) {
    global $pdo;
    $sql = "DELETE FROM access_tokens WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
}

// Handle POST requests to create new tokens
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $user_id = $_POST['user_id'];
    $token = $_POST['token'];
    $expires_at = $_POST['expires_at'];
    createAccessToken($user_id, $token, $expires_at);
}

// Handle GET requests to delete tokens
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    deleteAccessToken($id);
}

// Fetch all access tokens
$tokens = getAccessTokens();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Token Management</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<h2>Access Tokens</h2>

<!-- Form to create a new access token -->
<form method="POST">
    <h3>Create Access Token</h3>
    <label>User ID:</label>
    <input type="number" name="user_id" required>
    <br>
    <label>Token:</label>
    <input type="text" name="token" required>
    <br>
    <label>Expires At:</label>
    <input type="datetime-local" name="expires_at" required>
    <br>
    <input type="hidden" name="action" value="create">
    <input type="submit" value="Create Token">
</form>

<!-- Display existing tokens -->
<table>
    <tr>
        <th>ID</th>
        <th>User ID</th>
        <th>Token</th>
        <th>Created At</th>
        <th>Expires At</th>
        <th>Action</th>
    </tr>
    <?php if ($tokens): ?>
        <?php foreach ($tokens as $token): ?>
            <tr>
                <td><?php echo htmlspecialchars($token['id']); ?></td>
                <td><?php echo htmlspecialchars($token['user_id']); ?></td>
                <td><?php echo htmlspecialchars($token['token']); ?></td>
                <td><?php echo htmlspecialchars($token['created_at']); ?></td>
                <td><?php echo htmlspecialchars($token['expires_at']); ?></td>
                <td>
                    <a href="?delete=<?php echo $token['id']; ?>" onclick="return confirm('Are you sure you want to delete this token?');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="6">No tokens found</td>
        </tr>
    <?php endif; ?>
</table>

</body>
</html>
