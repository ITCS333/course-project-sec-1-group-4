<?php

declare(strict_types=1);

header('Content-Type: application/json');

// ─── Simple SQLite-backed storage ───────────────────────────────────────────
$dbPath = __DIR__ . '/assignments.sqlite';
$db     = new SQLite3($dbPath);
$db->enableExceptions(true);

// Create tables
$db->exec('
  CREATE TABLE IF NOT EXISTS assignments (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    title       TEXT    NOT NULL,
    description TEXT    NOT NULL,
    due_date    TEXT    NOT NULL,
    files       TEXT    NOT NULL DEFAULT "[]",
    created_at  TEXT    NOT NULL DEFAULT (datetime("now"))
  );
  CREATE TABLE IF NOT EXISTS comments (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    assignment_id INTEGER NOT NULL,
    author        TEXT    NOT NULL DEFAULT "Student",
    text          TEXT    NOT NULL,
    created_at    TEXT    NOT NULL DEFAULT (datetime("now")),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id)
  );
');

// Seed data if empty
$count = $db->querySingle('SELECT COUNT(*) FROM assignments');
if ($count === 0) {
    $seed = [
        [
            'title'       => 'HTML & CSS Portfolio',
            'description' => 'Build a personal portfolio using HTML and CSS.',
            'due_date'    => '2025-02-15',
            'files'       => json_encode(['https://example.com/brief.pdf']),
        ],
        [
            'title'       => 'JavaScript Interactivity',
            'description' => 'Add interactivity to your portfolio using JavaScript.',
            'due_date'    => '2025-03-01',
            'files'       => json_encode([]),
        ],
    ];
    $stmt = $db->prepare('
        INSERT INTO assignments (title, description, due_date, files)
        VALUES (:title, :description, :due_date, :files)
    ');
    foreach ($seed as $s) {
        $stmt->bindValue(':title',       $s['title']);
        $stmt->bindValue(':description', $s['description']);
        $stmt->bindValue(':due_date',    $s['due_date']);
        $stmt->bindValue(':files',       $s['files']);
        $stmt->execute();
        $stmt->reset();
    }
}

// ─── Helpers ────────────────────────────────────────────────────────────────
function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function validateDate(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function rowToAssignment(array $row): array
{
    $row['files'] = json_decode($row['files'], true) ?? [];
    return $row;
}

// ─── Route ──────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

// ── GET ─────────────────────────────────────────────────────────────────────
if ($method === 'GET') {

    // GET ?action=comments&assignment_id=X
    if ($action === 'comments') {
        $assignmentId = (int)($_GET['assignment_id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM comments WHERE assignment_id = :aid ORDER BY id');
        $stmt->bindValue(':aid', $assignmentId, SQLITE3_INTEGER);
        $res  = $stmt->execute();
        $rows = [];
        while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $r;
        }
        respond(200, ['success' => true, 'data' => $rows]);
    }

    // GET ?id=X
    if (isset($_GET['id'])) {
        $id   = (int)$_GET['id'];
        $stmt = $db->prepare('SELECT * FROM assignments WHERE id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res  = $stmt->execute();
        $row  = $res->fetchArray(SQLITE3_ASSOC);
        if (!$row) {
            respond(404, ['success' => false, 'error' => 'Not found']);
        }
        respond(200, ['success' => true, 'data' => rowToAssignment($row)]);
    }

    // GET ?search=X
    if (isset($_GET['search'])) {
        $q    = '%' . $_GET['search'] . '%';
        $stmt = $db->prepare('
            SELECT * FROM assignments
            WHERE title LIKE :q OR description LIKE :q
            ORDER BY id
        ');
        $stmt->bindValue(':q', $q);
        $res  = $stmt->execute();
        $rows = [];
        while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = rowToAssignment($r);
        }
        respond(200, ['success' => true, 'data' => $rows]);
    }

    // GET all
    $res  = $db->query('SELECT * FROM assignments ORDER BY id');
    $rows = [];
    while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = rowToAssignment($r);
    }
    respond(200, ['success' => true, 'data' => $rows]);
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($method === 'POST') {

    // POST ?action=comment
    if ($action === 'comment') {
        $assignmentId = (int)($input['assignment_id'] ?? 0);
        $author       = trim($input['author'] ?? 'Student');
        $text         = trim($input['text'] ?? '');

        if (empty($text)) {
            respond(400, ['success' => false, 'error' => 'text is required']);
        }

        // Check assignment exists
        $stmt = $db->prepare('SELECT id FROM assignments WHERE id = :id');
        $stmt->bindValue(':id', $assignmentId, SQLITE3_INTEGER);
        $res  = $stmt->execute();
        if (!$res->fetchArray()) {
            respond(404, ['success' => false, 'error' => 'Assignment not found']);
        }

        $stmt = $db->prepare('
            INSERT INTO comments (assignment_id, author, text)
            VALUES (:aid, :author, :text)
        ');
        $stmt->bindValue(':aid',    $assignmentId, SQLITE3_INTEGER);
        $stmt->bindValue(':author', $author);
        $stmt->bindValue(':text',   $text);
        $stmt->execute();
        $newId = $db->lastInsertRowID();

        $stmt = $db->prepare('SELECT * FROM comments WHERE id = :id');
        $stmt->bindValue(':id', $newId, SQLITE3_INTEGER);
        $row  = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        respond(201, ['success' => true, 'id' => $newId, 'data' => $row]);
    }

    // POST create assignment
    $title       = trim($input['title']       ?? '');
    $description = trim($input['description'] ?? '');
    $due_date    = trim($input['due_date']    ?? '');
    $files       = $input['files'] ?? [];

    if (empty($title))       respond(400, ['success' => false, 'error' => 'title is required']);
    if (empty($description)) respond(400, ['success' => false, 'error' => 'description is required']);
    if (empty($due_date))    respond(400, ['success' => false, 'error' => 'due_date is required']);
    if (!validateDate($due_date)) respond(400, ['success' => false, 'error' => 'Invalid date format']);

    $stmt = $db->prepare('
        INSERT INTO assignments (title, description, due_date, files)
        VALUES (:title, :desc, :due, :files)
    ');
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':desc',  $description);
    $stmt->bindValue(':due',   $due_date);
    $stmt->bindValue(':files', json_encode(array_values((array)$files)));
    $stmt->execute();
    $newId = $db->lastInsertRowID();

    respond(201, ['success' => true, 'id' => $newId]);
}

// ── PUT ──────────────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) respond(400, ['success' => false, 'error' => 'id is required']);

    // Check exists
    $stmt = $db->prepare('SELECT * FROM assignments WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $row  = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if (!$row) respond(404, ['success' => false, 'error' => 'Not found']);

    if (isset($input['due_date']) && !validateDate($input['due_date'])) {
        respond(400, ['success' => false, 'error' => 'Invalid date format']);
    }

    $title       = $input['title']       ?? $row['title'];
    $description = $input['description'] ?? $row['description'];
    $due_date    = $input['due_date']    ?? $row['due_date'];
    $files       = isset($input['files']) ? json_encode(array_values((array)$input['files'])) : $row['files'];

    $stmt = $db->prepare('
        UPDATE assignments
        SET title = :title, description = :desc, due_date = :due, files = :files
        WHERE id = :id
    ');
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':desc',  $description);
    $stmt->bindValue(':due',   $due_date);
    $stmt->bindValue(':files', $files);
    $stmt->bindValue(':id',    $id, SQLITE3_INTEGER);
    $stmt->execute();

    respond(200, ['success' => true]);
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {

    // DELETE ?action=delete_comment&comment_id=X
    if ($action === 'delete_comment') {
        $commentId = (int)($_GET['comment_id'] ?? 0);
        $stmt = $db->prepare('SELECT id FROM comments WHERE id = :id');
        $stmt->bindValue(':id', $commentId, SQLITE3_INTEGER);
        if (!$stmt->execute()->fetchArray()) {
            respond(404, ['success' => false, 'error' => 'Comment not found']);
        }
        $stmt = $db->prepare('DELETE FROM comments WHERE id = :id');
        $stmt->bindValue(':id', $commentId, SQLITE3_INTEGER);
        $stmt->execute();
        respond(200, ['success' => true]);
    }

    // DELETE ?id=X
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare('SELECT id FROM assignments WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    if (!$stmt->execute()->fetchArray()) {
        respond(404, ['success' => false, 'error' => 'Not found']);
    }
    $db->prepare('DELETE FROM comments    WHERE assignment_id = :id')->execute() ;
    $stmt = $db->prepare('DELETE FROM assignments WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    respond(200, ['success' => true]);
}

// ── Unsupported method ───────────────────────────────────────────────────────
respond(405, ['success' => false, 'error' => 'Method not allowed']);
