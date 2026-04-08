<?php
/**
 * Authentication Handler for Login Form
 */

// --- Session Management ---
session_start();

// --- Set Response Headers ---
header('Content-Type: application/json');

// --- Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// --- Get POST Data ---
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required'
    ]);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

// --- Server-Side Validation ---
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters'
    ]);
    exit;
}

// --- Database Connection ---
// عدلي هذا السطر حسب اسم ومكان ملف الاتصال الحقيقي عندكم
require_once '../common/db.php';

try {
    $db = getDBConnection();

    // --- Prepare SQL Query ---
    $sql = "SELECT id, name, email, password, is_admin FROM users WHERE email = :email";

    // --- Prepare the Statement ---
    $stmt = $db->prepare($sql);

    // --- Execute the Query ---
    $stmt->execute(['email' => $email]);

    // --- Fetch User Data ---
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Verify User Exists and Password Matches ---
    if ($user && password_verify($password, $user['password'])) {

        // --- Handle Successful Authentication ---
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['logged_in'] = true;

        $response = [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'is_admin' => $user['is_admin']
            ]
        ];

        echo json_encode($response);
        exit;
    }

    // --- Handle Failed Authentication ---
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email or password'
    ]);
    exit;

} catch (PDOException $e) {
    // --- Catch PDO exceptions ---
    error_log($e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
    exit;
}
?>
