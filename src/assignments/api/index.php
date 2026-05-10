<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// اتصال قاعدة البيانات - غير هذه القيم حسب إعداداتك
// ============================================================
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'itcs333_project';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

// ============================================================
// GET: استرجاع البيانات
// ============================================================
if ($method === 'GET') {
    
    // جلب التعليقات لواجب محدد
    if ($action === 'comments' && isset($_GET['assignment_id'])) {
        $assignment_id = (int)$_GET['assignment_id'];
        $result = $conn->query("SELECT id, assignment_id, author, text, created_at FROM comments_assignment WHERE assignment_id = $assignment_id ORDER BY created_at ASC");
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $comments]);
        exit();
    }
    
    // جلب واجب واحد
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $result = $conn->query("SELECT * FROM assignments WHERE id = $id");
        if ($result && $row = $result->fetch_assoc()) {
            $row['files'] = json_decode($row['files'] ?? '[]', true);
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Assignment not found']);
        }
        exit();
    }
    
    // جلب جميع الواجبات (مع بحث اختياري)
    $sql = "SELECT * FROM assignments";
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $conn->real_escape_string($_GET['search']);
        $sql .= " WHERE title LIKE '%$search%' OR description LIKE '%$search%'";
    }
    $sql .= " ORDER BY due_date ASC";
    
    $result = $conn->query($sql);
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $row['files'] = json_decode($row['files'] ?? '[]', true);
        $assignments[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $assignments]);
    exit();
}

// ============================================================
// POST: إنشاء بيانات جديدة
// ============================================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // إضافة تعليق جديد
    if ($action === 'comment') {
        $assignment_id = (int)($input['assignment_id'] ?? 0);
        $author = $conn->real_escape_string($input['author'] ?? 'Anonymous');
        $text = $conn->real_escape_string($input['text'] ?? '');
        
        // التحقق من وجود الواجب
        $check = $conn->query("SELECT id FROM assignments WHERE id = $assignment_id");
        if ($check->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Assignment not found']);
            exit();
        }
        
        if (empty($text)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Comment text is required']);
            exit();
        }
        
        $sql = "INSERT INTO comments_assignment (assignment_id, author, text) VALUES ($assignment_id, '$author', '$text')";
        if ($conn->query($sql)) {
            http_response_code(201);
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit();
    }
    
    // إضافة واجب جديد
    $title = $conn->real_escape_string($input['title'] ?? '');
    $description = $conn->real_escape_string($input['description'] ?? '');
    $due_date = $conn->real_escape_string($input['due_date'] ?? '');
    $files = json_encode($input['files'] ?? []);
    
    // التحقق من الحقول المطلوبة
    if (empty($title)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Title is required']);
        exit();
    }
    if (empty($description)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Description is required']);
        exit();
    }
    if (empty($due_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Due date is required']);
        exit();
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD']);
        exit();
    }
    
    $sql = "INSERT INTO assignments (title, description, due_date, files) VALUES ('$title', '$description', '$due_date', '$files')";
    if ($conn->query($sql)) {
        http_response_code(201);
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

// ============================================================
// PUT: تحديث البيانات
// ============================================================
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    
    if ($id === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID is required']);
        exit();
    }
    
    $check = $conn->query("SELECT id FROM assignments WHERE id = $id");
    if ($check->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Assignment not found']);
        exit();
    }
    
    $updates = [];
    
    if (isset($input['title'])) {
        $updates[] = "title = '" . $conn->real_escape_string($input['title']) . "'";
    }
    if (isset($input['description'])) {
        $updates[] = "description = '" . $conn->real_escape_string($input['description']) . "'";
    }
    if (isset($input['due_date'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['due_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid date format']);
            exit();
        }
        $updates[] = "due_date = '" . $conn->real_escape_string($input['due_date']) . "'";
    }
    if (isset($input['files'])) {
        $updates[] = "files = '" . json_encode($input['files']) . "'";
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        exit();
    }
    
    $sql = "UPDATE assignments SET " . implode(', ', $updates) . " WHERE id = $id";
    if ($conn->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

// ============================================================
// DELETE: حذف البيانات
// ============================================================
if ($method === 'DELETE') {
    
    // حذف تعليق
    if ($action === 'delete_comment' && isset($_GET['comment_id'])) {
        $comment_id = (int)$_GET['comment_id'];
        $check = $conn->query("SELECT id FROM comments_assignment WHERE id = $comment_id");
        if ($check->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Comment not found']);
            exit();
        }
        
        $sql = "DELETE FROM comments_assignment WHERE id = $comment_id";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit();
    }
    
    // حذف واجب
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $check = $conn->query("SELECT id FROM assignments WHERE id = $id");
        if ($check->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Assignment not found']);
            exit();
        }
        
        // حذف التعليقات المرتبطة