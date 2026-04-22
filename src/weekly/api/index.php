<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../common/db.php';
$db = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true) ?? [];

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$weekId = $_GET['week_id'] ?? null;
$commentId = $_GET['comment_id'] ?? null;


// ===================== HELPERS =====================

function sendResponse($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitize($str)
{
    return htmlspecialchars(strip_tags(trim($str)));
}


// ===================== WEEKS =====================

function getAllWeeks($db)
{
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'start_date';
    $order = $_GET['order'] ?? 'asc';

    $allowedSort = ['title', 'start_date'];
    if (!in_array($sort, $allowedSort)) $sort = 'start_date';

    $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

    $sql = "SELECT * FROM weeks";
    if (!empty($search)) {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
    }
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);

    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%");
    }

    $stmt->execute();
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($weeks as &$w) {
        $w['links'] = json_decode($w['links'], true) ?? [];
    }

    sendResponse(["success" => true, "data" => $weeks]);
}

function getWeekById($db, $id)
{
    if (!$id || !is_numeric($id)) {
        sendResponse(["success" => false, "message" => "Invalid ID"], 400);
    }

    $stmt = $db->prepare("SELECT * FROM weeks WHERE id=?");
    $stmt->execute([$id]);
    $week = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$week) {
        sendResponse(["success" => false, "message" => "Not found"], 404);
    }

    $week['links'] = json_decode($week['links'], true) ?? [];

    sendResponse(["success" => true, "data" => $week]);
}

function createWeek($db, $data)
{
    if (empty($data['title']) || empty($data['start_date'])) {
        sendResponse(["success" => false, "message" => "Missing fields"], 400);
    }

    if (!validateDate($data['start_date'])) {
        sendResponse(["success" => false, "message" => "Invalid date"], 400);
    }

    $title = sanitize($data['title']);
    $date = $data['start_date'];
    $desc = sanitize($data['description'] ?? '');
    $links = json_encode($data['links'] ?? []);

    $stmt = $db->prepare("INSERT INTO weeks(title,start_date,description,links) VALUES (?,?,?,?)");
    $stmt->execute([$title, $date, $desc, $links]);

    sendResponse(["success" => true, "id" => $db->lastInsertId()], 201);
}

function updateWeek($db, $data)
{
    if (empty($data['id'])) {
        sendResponse(["success" => false], 400);
    }

    $id = $data['id'];

    $stmt = $db->prepare("SELECT * FROM weeks WHERE id=?");
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        sendResponse(["success" => false], 404);
    }

    $fields = [];
    $values = [];

    if (isset($data['title'])) {
        $fields[] = "title=?";
        $values[] = sanitize($data['title']);
    }

    if (isset($data['start_date'])) {
        if (!validateDate($data['start_date'])) {
            sendResponse(["success" => false], 400);
        }
        $fields[] = "start_date=?";
        $values[] = $data['start_date'];
    }

    if (isset($data['description'])) {
        $fields[] = "description=?";
        $values[] = sanitize($data['description']);
    }

    if (isset($data['links'])) {
        $fields[] = "links=?";
        $values[] = json_encode($data['links']);
    }

    if (empty($fields)) {
        sendResponse(["success" => false], 400);
    }

    $values[] = $id;

    $sql = "UPDATE weeks SET " . implode(",", $fields) . " WHERE id=?";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    sendResponse(["success" => true]);
}

function deleteWeek($db, $id)
{
    if (!$id) sendResponse(["success" => false], 400);

    $stmt = $db->prepare("DELETE FROM weeks WHERE id=?");
    $stmt->execute([$id]);

    sendResponse(["success" => true]);
}


// ===================== COMMENTS =====================

function getComments($db, $weekId)
{
    $stmt = $db->prepare("SELECT * FROM comments_week WHERE week_id=? ORDER BY created_at ASC");
    $stmt->execute([$weekId]);

    sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment($db, $data)
{
    if (empty($data['week_id']) || empty($data['text'])) {
        sendResponse(["success" => false], 400);
    }

    $stmt = $db->prepare("INSERT INTO comments_week(week_id,author,text) VALUES (?,?,?)");
    $stmt->execute([$data['week_id'], $data['author'], $data['text']]);

    sendResponse([
        "success" => true,
        "id" => $db->lastInsertId(),
        "data" => $data
    ], 201);
}

function deleteComment($db, $id)
{
    $stmt = $db->prepare("DELETE FROM comments_week WHERE id=?");
    $stmt->execute([$id]);

    sendResponse(["success" => true]);
}


// ===================== ROUTER =====================

try {

    if ($method === "GET") {

        if ($action === "comments") {
            getComments($db, $weekId);
        } elseif ($id) {
            getWeekById($db, $id);
        } else {
            getAllWeeks($db);
        }

    } elseif ($method === "POST") {

        if ($action === "comment") {
            createComment($db, $data);
        } else {
            createWeek($db, $data);
        }

    } elseif ($method === "PUT") {
        updateWeek($db, $data);

    } elseif ($method === "DELETE") {

        if ($action === "delete_comment") {
            deleteComment($db, $commentId);
        } else {
            deleteWeek($db, $id);
        }

    } else {
        sendResponse(["success" => false], 405);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    sendResponse(["success" => false, "message" => "Server Error"], 500);
}
?>
