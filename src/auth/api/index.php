<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email'])) {
    echo json_encode(['success' => false]);
    exit;
}

if (!isset($data['password'])) {
    echo json_encode(['success' => false]);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false]);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false]);
    exit;
}

try {

    $db = getDBConnection();

    $stmt = $db->prepare("
        SELECT id, name, email, password, is_admin
        FROM users
        WHERE email = :email
    ");

    $stmt->execute(['email' => $email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false]);
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false]);
        exit;
    }

    $_SESSION['user_id'] = $user['id'];

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'is_admin' => $user['is_admin']
        ]
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false
    ]);

}
?>
