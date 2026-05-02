<?php
/**
 * Discussion Board API
 *
 * RESTful API for CRUD operations on discussion topics and their replies.
 * Uses PDO to interact with the MySQL database defined in schema.sql.
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../common/db.php';

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true) ?? [];

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$topicId = $_GET['topic_id'] ?? null;

// ============================================================================
// TOPICS FUNCTIONS
// ============================================================================

function getAllTopics(PDO $db): void
{
    $sql= "SELECT id, subject, message, author, created_at FROM topics";
    $params = [];

    if (!empty($_GET['search'])) {
        $search= $_GET['search'];
        $sql .= " WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search";
        $params['search'] = '%' . $search . '%'; 
    }

    $allowedSorts = ['subject', 'author', 'created_at'];
    $sort= in_array($_GET['sort'] ?? '', $allowedSorts) ? $_GET['sort'] : 'created_at';
    $order= in_array($_GET['order'] ?? '', ['asc', 'desc']) ? $_GET['order'] : 'desc';

    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $topics= $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'data' => $topics]);
}

function getTopicById(PDO $db, $id): void
{
    if (!isset($id) || !is_numeric($id)) {
        sendResponse(['success' => false, 'message' => 'Invalid or missing id parameter.'], 400); 
    }

    $stmt = $db->prepare("SELECT id, subject, message, author, created_at FROM topics WHERE id = ?");
    $stmt->execute([$id]);

    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($topic) {
        sendResponse(['success' => true, 'data' => $topic]);
    } else {
        sendResponse(['success' => false, 'message' => 'Topic not found.'], 404);
    }
}

function createTopic(PDO $db, array $data): void
{
    if (empty($data['subject']) || empty($data['message']) || empty($data['author'])) {
        sendResponse(['success' => false, 'message' => 'Missing required fields'], 400); 
    }

    $subject= sanitizeInput($data['subject']);
    $message= sanitizeInput($data['message']);
    $author= sanitizeInput($data['author']);

    $sql= 'INSERT INTO topics (subject, message, author) VALUES (?, ?, ?)';
    $stmt= $db->prepare($sql);

    if ($stmt->execute([$subject, $message, $author])) {
        sendResponse(['success' => true, 'message' => 'Topic created successfully', 'id' => $db->lastInsertId()], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create topic'], 500);
    }
}

function updateTopic(PDO $db, array $data): void
{
    if (empty($data['id']) || !is_numeric($data['id'])) {
        sendResponse(['success' => false, 'message' => 'Missing or invalid ID'], 400); 
    }

    $check= $db->prepare("SELECT id FROM topics WHERE id = ?");
    $check->execute([$data['id']]);
    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Topic not found'], 404);
    }

    $fields = [];
    $params = [];

    if (!empty($data['subject'])) {
        $fields[] = 'subject = ?';
        $params[] = sanitizeInput($data['subject']);
    }
    if (!empty($data['message'])) {
        $fields[] = 'message = ?';
        $params[] = sanitizeInput($data['message']);
    }

    if (empty($fields)) {
        sendResponse(['success' => false, 'message' => 'No updatable fields provided'], 400);
    }
    $params[]= $data['id'];

    $sql = 'UPDATE topics SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $db->prepare($sql);

    if ($stmt->execute($params)) {
        sendResponse(['success' => true, 'message' => 'Topic updated']);
    } else {
        sendResponse(['success' => false, 'message' => ' update Failed'], 500);
    }
}

function deleteTopic(PDO $db, $id): void
{
    if (!isset($id) || !is_numeric($id)) {
        sendResponse(['success' => false, 'message' => 'Invalid ID'], 400); 
    }

    $check= $db->prepare("SELECT id FROM topics WHERE id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Topic not found'], 404);
    }

    $stmt = $db->prepare("DELETE FROM topics WHERE id = ?");

    if ($stmt->execute([$id]))  {
        sendResponse(['success' => true, 'message' => 'Topic deleted']);
    } else {
        sendResponse(['success' => false, 'message' => 'Delete Failed'], 500);
    }
}

// ============================================================================
// REPLIES FUNCTIONS
// ============================================================================

function getRepliesByTopicId(PDO $db, $topicId): void
{
    if (!isset($topicId) || !is_numeric($topicId)) {
        sendResponse(['success' => false, 'message' => 'Invalid topic ID'], 400);
    }

    $stmt= $db->prepare("SELECT id, topic_id, text, author, created_at FROM replies WHERE topic_id = ? ORDER BY created_at ASC");
    $stmt->execute([$topicId]);

    $replies= $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $replies]);
}

function createReply(PDO $db, array $data): void
{
    if (empty($data['topic_id']) || empty($data['text']) || empty($data['author'])) {
        sendResponse(['success' => false, 'message' => 'Missing fields'], 400);
    }
    if (!is_numeric($data['topic_id'])) {
        sendResponse(['success' => false, 'message' => 'Invalid topic_id'], 400);
    }
    $check= $db->prepare("SELECT id FROM topics WHERE id = ?");
    $check->execute([$data['topic_id']]);

    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Topic not found'], 404);
    }
    $text= sanitizeInput($data['text']);
    $author= sanitizeInput($data['author']);

    $stmt= $db->prepare("INSERT INTO replies (topic_id, text, author) VALUES (?, ?, ?)");
    if ($stmt->execute([$data['topic_id'], $text, $author])) {
        $newid = $db->lastInsertId();
        
        $stmt = $db->prepare("SELECT id, topic_id, text, author, created_at FROM replies WHERE id = ?");
        $stmt->execute([$newid]);
        
        // THIS IS THE FIX! We added 'id' => $newid right here.
        sendResponse([
            'success' => true, 
            'message' => 'Reply added', 
            'id' => $newid, 
            'data' => $stmt->fetch(PDO::FETCH_ASSOC)
        ], 201);
    } else { 
        sendResponse(['success' => false, 'message' => 'Failed to add reply'], 500);
    }
}

function deleteReply(PDO $db, $replyId): void
{
    if (!isset($replyId) || !is_numeric($replyId)) {
        sendResponse(['success' => false, 'message' => 'Invalid ID'], 400);
    }
    
    $check = $db->prepare("SELECT id FROM replies WHERE id = ?");
    $check->execute([$replyId]);
    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Reply not found'], 404);
    }

    $stmt=$db->prepare("DELETE FROM replies WHERE id = ?");
    if ($stmt->execute([$replyId])) {
        sendResponse(['success' => true, 'message' => 'Reply deleted'], 200);
    } else {    
        sendResponse(['success' => false, 'message' => 'Failed to delete reply'], 500);
    }
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    if ($method === 'GET') {
        if ($action === 'replies') {
            getRepliesByTopicId($db, $topicId);
        } elseif ($id) {
             getTopicById($db, $id);
        } else {
            getAllTopics($db);
        }
    } elseif ($method === 'POST') {
        if ($action === 'reply') {
            createReply($db, $data);
        } else {
            createTopic($db, $data);
        }
    } elseif ($method === 'PUT') {
        updateTopic($db, $data);
    } elseif ($method === 'DELETE') {
        if ($action === 'delete_reply') {
            deleteReply($db, $id);
        } else  {
            deleteTopic($db, $id);
        }
    } else {
        sendResponse(['success' => false, 'message' => 'Method Not Allowed'], 405);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    sendResponse("Database error", 500);

} catch (Exception $e) {
    sendResponse($e->getMessage(), 500);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function sanitizeInput(string $data): string
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

