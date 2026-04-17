<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../../common/db.php";

$db = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? null;
$order = $_GET['order'] ?? "asc";


function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);

    if ($statusCode < 400) {
        echo json_encode(["success" => true, "data" => $data]);
    } else {
        echo json_encode(["success" => false, "message" => $data]);
    }

    exit;
}


function getUsers($db) {

    global $search, $sort, $order;

    $allowedSort = ["name","email","is_admin"];

    $sql = "SELECT id,name,email,is_admin,created_at FROM users";

    if ($search) {
        $sql .= " WHERE name LIKE :search OR email LIKE :search";
    }

    if (in_array($sort,$allowedSort)) {
        $sql .= " ORDER BY $sort " . ($order === "desc" ? "DESC":"ASC");
    }

    $stmt = $db->prepare($sql);

    if ($search) {
        $stmt->bindValue(":search","%$search%");
    }

    $stmt->execute();

    sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
}


function getUserById($db,$id){

    $stmt=$db->prepare(
        "SELECT id,name,email,is_admin,created_at
         FROM users
         WHERE id=:id"
    );

    $stmt->execute(["id"=>$id]);

    $user=$stmt->fetch(PDO::FETCH_ASSOC);

    if(!$user){
        sendResponse("User not found",404);
    }

    sendResponse($user);
}


function createUser($db,$data){

    if(empty($data["name"])||
       empty($data["email"])||
       empty($data["password"])){

        sendResponse("Missing fields",400);
    }

    if(!filter_var($data["email"],FILTER_VALIDATE_EMAIL)){
        sendResponse("Invalid email",400);
    }

    if(strlen($data["password"])<8){
        sendResponse("Password too short",400);
    }

    $stmt=$db->prepare(
        "SELECT id FROM users WHERE email=:email"
    );

    $stmt->execute(["email"=>$data["email"]]);

    if($stmt->fetch()){
        sendResponse("Email exists",409);
    }

    $hash=password_hash($data["password"],PASSWORD_DEFAULT);

    $is_admin=$data["is_admin"]??0;

    $stmt=$db->prepare(
        "INSERT INTO users(name,email,password,is_admin)
         VALUES(:name,:email,:password,:is_admin)"
    );

    $stmt->execute([
        "name"=>$data["name"],
        "email"=>$data["email"],
        "password"=>$hash,
        "is_admin"=>$is_admin
    ]);

    sendResponse($db->lastInsertId(),201);
}


function updateUser($db,$data){

    if(empty($data["id"])){
        sendResponse("Missing id",400);
    }

    $fields=[];
    $params=["id"=>$data["id"]];

    if(isset($data["name"])){
        $fields[]="name=:name";
        $params["name"]=$data["name"];
    }

    if(isset($data["email"])){

        if(!filter_var($data["email"],FILTER_VALIDATE_EMAIL)){
            sendResponse("Invalid email",400);
        }

        $check=$db->prepare(
            "SELECT id FROM users
             WHERE email=:email
             AND id!=:id"
        );

        $check->execute([
            "email"=>$data["email"],
            "id"=>$data["id"]
        ]);

        if($check->fetch()){
            sendResponse("Email exists",409);
        }

        $fields[]="email=:email";
        $params["email"]=$data["email"];
    }

    if(isset($data["is_admin"])){
        $fields[]="is_admin=:is_admin";
        $params["is_admin"]=$data["is_admin"];
    }

    if(empty($fields)){
        sendResponse("Nothing to update",400);
    }

    $sql="UPDATE users SET "
        .implode(",",$fields)
        ." WHERE id=:id";

    $stmt=$db->prepare($sql);

    $stmt->execute($params);

    sendResponse("Updated");
}


function deleteUser($db,$id){

    if(!$id){
        sendResponse("Missing id",400);
    }

    $stmt=$db->prepare(
        "DELETE FROM users WHERE id=:id"
    );

    $stmt->execute(["id"=>$id]);

    sendResponse("Deleted");
}


function changePassword($db,$data){

    if(empty($data["id"])||
       empty($data["current_password"])||
       empty($data["new_password"])){

        sendResponse("Missing fields",400);
    }

    if(strlen($data["new_password"])<8){
        sendResponse("Password too short",400);
    }

    $stmt=$db->prepare(
        "SELECT password FROM users WHERE id=:id"
    );

    $stmt->execute(["id"=>$data["id"]]);

    $user=$stmt->fetch(PDO::FETCH_ASSOC);

    if(!$user){
        sendResponse("User not found",404);
    }

    if(!password_verify(
        $data["current_password"],
        $user["password"]
    )){
        sendResponse("Wrong password",401);
    }

    $hash=password_hash(
        $data["new_password"],
        PASSWORD_DEFAULT
    );

    $stmt=$db->prepare(
        "UPDATE users SET password=:password WHERE id=:id"
    );

    $stmt->execute([
        "password"=>$hash,
        "id"=>$data["id"]
    ]);

    sendResponse("Password updated");
}



try{

    if($method==="GET"){

        if($id){
            getUserById($db,$id);
        }else{
            getUsers($db);
        }

    }

    elseif($method==="POST"){

        if($action==="change_password"){
            changePassword($db,$data);
        }else{
            createUser($db,$data);
        }

    }

    elseif($method==="PUT"){
        updateUser($db,$data);
    }

    elseif($method==="DELETE"){
        deleteUser($db,$id);
    }

    else{
        sendResponse("Method not allowed",405);
    }

}

catch(PDOException $e){
    error_log($e->getMessage());
    sendResponse("Database error",500);
}

catch(Exception $e){
    sendResponse($e->getMessage(),500);
}
