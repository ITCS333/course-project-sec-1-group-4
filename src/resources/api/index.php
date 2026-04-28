<?php
session_start();

header("Content-Type: application/json");

require_once "../../common/db.php";
$pdo = getDBConnection();

$action = $_GET["action"] ?? "";

function getInputData() {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        $data = $_POST;
    }

    return $data;

}

function requireAdmin() {
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] != 1) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Admin access required"
        ]);
        exit;
    }
}

try {
    if ($action === "list") {
        $stmt = $pdo->query("
            SELECT id, title, description, link
            FROM resources
            ORDER BY id DESC
        ");

        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "resources" => $resources
        ]);

        exit;
    }

    if ($action === "details") {
        $id = $_GET["id"] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Resource ID is required"
            ]);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT id, title, description, link
            FROM resources
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $resource = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resource) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Resource not found"
            ]);
            exit;
        }

        $commentsStmt = $pdo->prepare("
            SELECT id, resource_id, user_id, comment
            FROM comments_resource
            WHERE resource_id = ?
            ORDER BY id DESC
        ");
        $commentsStmt->execute([$id]);
        $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "resource" => $resource,
            "comments" => $comments
        ]);
        exit;
    }

    if ($action === "comment") {
        $data = getInputData();

        $resource_id = $data["resource_id"] ?? null;
        $comment = trim($data["comment"] ?? "");

        if (!$resource_id || $comment === "") {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Resource ID and comment are required"
            ]);
            exit;
        }

        $user_id = $_SESSION["user_id"] ?? 1;
        $stmt = $pdo->prepare("
            INSERT INTO comments_resource (resource_id, user_id, comment)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$resource_id, $user_id, $comment]);

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Comment added successfully"
        ]);
        exit;
    }

    if ($action === "create") {
        requireAdmin();

        $data = getInputData();

        $title = trim($data["title"] ?? "");
        $description = trim($data["description"] ?? "");
        $link = trim($data["link"] ?? "");

        if ($title === "" || $description === "" || $link === "") {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Title, description, and link are required"
            ]);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO resources (title, description, link)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$title, $description, $link]);
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Resource created successfully",
            "id" => $pdo->lastInsertId()
        ]);
        exit;
    }

    if ($action === "update") {
        requireAdmin();

        $data = getInputData();

        $id = $data["id"] ?? null;
        $title = trim($data["title"] ?? "");
        $description = trim($data["description"] ?? "");
        $link = trim($data["link"] ?? "");

        if (!$id || $title === "" || $description === "" || $link === "") {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID, title, description, and link are required"
            ]);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE resources
            SET title = ?, description = ?, link = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $link, $id]);

        echo json_encode([
            "success" => true,
            "message" => "Resource updated successfully"
        ]);
        exit;
    }

    if ($action === "delete") {
        requireAdmin();

        $data = getInputData();

        $id = $data["id"] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Resource ID is required"
            ]);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM comments_resource WHERE resource_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM resources WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            "success" => true,
            "message" => "Resource deleted successfully"
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid action"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error"
    ]);
}
?>