<?php
require 'database.php'; // Make sure to include your DB connection

// Retrieve assignments
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $sql = "SELECT * FROM assignments";
    $result = $db->query($sql);

    $assignments = $result->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $assignments]);
    exit;
}

// Add new assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date, files) VALUES (:title, :description, :due_date, :files)");
    $stmt->execute([
        ':title' => $data['title'],
        ':description' => $data['description'],
        ':due_date' => $data['due_date'],
        ':files' => implode(',', $data['files']),
    ]);

    echo json_encode(['success' => true, 'message' => 'Assignment added successfully']);
    exit;
}