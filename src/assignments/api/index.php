<?php
include('../config/db.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET['id'])) {
        $query = "SELECT * FROM assignments";
        $result = mysqli_query($conn, $query);
        $assignments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $assignments[] = $row;
        }
        echo json_encode($assignments);
    } else {
        $id = $_GET['id'];
        $query = "SELECT * FROM assignments WHERE id = $id";
        $result = mysqli_query($conn, $query);
        $assignment = mysqli_fetch_assoc($result);
        echo json_encode($assignment);
    }
} 

elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $title = $data['title'];
    $description = $data['description'];
    $fileLink = $data['file_link'];
    $dueDate = $data['due_date'];

    $query = "INSERT INTO assignments (title, description, file_link, due_date) 
              VALUES ('$title', '$description', '$fileLink', '$dueDate')";
    if (mysqli_query($conn, $query)) {
        echo json_encode(["message" => "Assignment added successfully"]);
    } else {
        echo json_encode(["error" => "Error adding assignment"]);
    }
} 

elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $id = $_GET['id'];
    $query = "DELETE FROM assignments WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        echo json_encode(["message" => "Assignment deleted successfully"]);
    } else {
        echo json_encode(["error" => "Error deleting assignment"]);
    }
}
?>