<?php
declare(strict_types=1);

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        handleGetRequest($action);
        break;
    case 'POST':
        handlePostRequest($action);
        break;
    case 'PUT':
        handlePutRequest();
        break;
    case 'DELETE':
        handleDeleteRequest();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        break;
}

function handleGetRequest(string $action): void
{
    switch ($action) {
        case 'comments':
            getComments();
            break;
        case 'assignment':
            getAssignment();
            break;
        default:
            getAllAssignments();
            break;
    }
}

function handlePostRequest(string $action): void
{
    switch ($action) {
        case 'comment':
            createComment();
            break;
        case 'assignment':
            createAssignment();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Bad Request']);
            break;
    }
}

function handlePutRequest(): void
{
    // Handle PUT requests (e.g., updating an assignment)
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
        return;
    }
    // Update assignment logic here
    echo json_encode(['success' => true, 'message' => 'Assignment updated successfully']);
}

function handleDeleteRequest(): void
{
    // Handle DELETE requests (e.g., deleting an assignment or comment)
    $data = $_GET;
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID required']);
        return;
    }
    // Delete assignment or comment logic here
    echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
}

function getAllAssignments(): void
{
    // Dummy assignments data
    $assignments = [
        ['id' => 1, 'title' => 'HTML & CSS Portfolio', 'due_date' => '2025-02-15', 'description' => 'Build a portfolio', 'files' => [], 'created_at' => '2023-01-01'],
        ['id' => 2, 'title' => 'JavaScript Interactivity', 'due_date' => '2025-03-01', 'description' => 'Add JS to the website', 'files' => [], 'created_at' => '2023-02-01']
    ];
    echo json_encode(['success' => true, 'data' => $assignments]);
}

function getAssignment(): void
{
    // Simulate fetching assignment by ID from database
    $id = $_GET['id'] ?? null;
    if ($id) {
        $assignment = [
            'id' => $id,
            'title' => 'HTML & CSS Portfolio',
            'due_date' => '2025-02-15',
            'description' => 'Build a portfolio.',
            'files' => []
        ];
        echo json_encode(['success' => true, 'data' => $assignment]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
    }
}

function getComments(): void
{
    // Simulate fetching comments for an assignment
    $assignmentId = $_GET['assignment_id'] ?? null;
    if ($assignmentId) {
        $comments = [
            ['id' => 1, 'author' => 'John Doe', 'text' => 'Can we use Flexbox?', 'created_at' => '2023-01-01'],
            ['id' => 2, 'author' => 'Jane Smith', 'text' => 'Is there a deadline?', 'created_at' => '2023-02-01']
        ];
        echo json_encode(['success' => true, 'data' => $comments]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
    }
}

function createAssignment(): void
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    $newAssignment = [
        'id' => rand(1, 1000), // Simulate new ID generation
        'title' => $data['title'],
        'description' => $data['description'],
        'due_date' => $data['due_date'],
        'files' => $data['files'] ?? [],
        'created_at' => date('Y-m-d H:i:s')
    ];
    echo json_encode(['success' => true, 'data' => $newAssignment]);
}

function createComment(): void
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    $newComment = [
        'id' => rand(1, 1000), // Simulate new comment ID generation
        'assignment_id' => $data['assignment_id'],
        'author' => $data['author'],
        'text' => $data['text'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    echo json_encode(['success' => true, 'data' => $newComment]);
}
?>