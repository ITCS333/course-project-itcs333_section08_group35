<?php
session_start();

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $host = 'localhost';
    $db_name = 'course_db';
    $username = 'root';
    $password = '';
    $db = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['message' => 'Connection Error: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

$data = json_decode(file_get_contents("php://input"), true);

$resource = isset($_GET['resource']) ? $_GET['resource'] : 'weeks';

function getAllWeeks($db) {
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'start_date';
    $order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'ASC';

    $query = "SELECT week_id, title, start_date, description, links, created_at FROM weeks";
    
    if ($search) {
        $query .= " WHERE title LIKE :search OR description LIKE :search";
    }
    
    $allowedSorts = ['title', 'start_date', 'created_at', 'week_id'];
    if (!in_array($sort, $allowedSorts)) {
        $sort = 'start_date';
    }
    
    if (!in_array($order, ['ASC', 'DESC'])) {
        $order = 'ASC';
    }
    
    $query .= " ORDER BY $sort $order";
    
    $stmt = $db->prepare($query);
    
    if ($search) {
        $searchTerm = "%{$search}%";
        $stmt->bindValue(':search', $searchTerm);
    }
    
    $stmt->execute();
    
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($weeks as &$week) {
        if (!empty($week['links'])) {
            $week['links'] = json_decode($week['links'], true);
        } else {
            $week['links'] = [];
        }
    }
    
    sendResponse($weeks);
}

function getWeekById($db, $weekId) {
    if (!$weekId) {
        sendError("Missing week_id", 400);
        return;
    }
    
    $query = "SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = :week_id LIMIT 1";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':week_id', $weekId);
    
    $stmt->execute();
    
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($week) {
        $week['links'] = json_decode($week['links'], true);
        sendResponse($week);
    } else {
        sendError("Week not found", 404);
    }
}

function createWeek($db, $data) {
    if (empty($data['week_id']) || empty($data['title']) || empty($data['start_date']) || empty($data['description'])) {
        sendError("Missing required fields (week_id, title, start_date, description)", 400);
        return;
    }
    
    $week_id = sanitizeInput($data['week_id']);
    $title = sanitizeInput($data['title']);
    $start_date = sanitizeInput($data['start_date']);
    $description = sanitizeInput($data['description']);
    
    if (!validateDate($start_date)) {
        sendError("Invalid date format. Use YYYY-MM-DD", 400);
        return;
    }
    
     $checkQuery = "SELECT id FROM weeks WHERE week_id = :week_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':week_id', $week_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        sendError("Week ID already exists", 409);
        return;
    }
    
     $links = isset($data['links']) && is_array($data['links']) ? json_encode($data['links']) : json_encode([]);
    
    $query = "INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (:week_id, :title, :start_date, :description, :links)";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':week_id', $week_id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':links', $links);
    
    if ($stmt->execute()) {
        $data['links'] = json_decode($links, true); // decode back for response
        sendResponse($data, 201);
    } else {
        sendError("Failed to create week", 500);
    }
}

function updateWeek($db, $data) {
    if (empty($data['week_id'])) {
        sendError("Missing week_id for update", 400);
        return;
    }
    
    $week_id = sanitizeInput($data['week_id']);
    
    $checkQuery = "SELECT id FROM weeks WHERE week_id = :week_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':week_id', $week_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        sendError("Week not found", 404);
        return;
    }
    
    $fields = [];
    $params = [];
    
    if (isset($data['title'])) {
        $fields[] = "title = ?";
        $params[] = sanitizeInput($data['title']);
    }
    if (isset($data['start_date'])) {
        if (!validateDate($data['start_date'])) {
            sendError("Invalid date format", 400);
            return;
        }
        $fields[] = "start_date = ?";
        $params[] = sanitizeInput($data['start_date']);
    }
    if (isset($data['description'])) {
        $fields[] = "description = ?";
        $params[] = sanitizeInput($data['description']);
    }
    if (isset($data['links']) && is_array($data['links'])) {
        $fields[] = "links = ?";
        $params[] = json_encode($data['links']);
    }
    
    if (empty($fields)) {
        sendError("No fields provided for update", 400);
        return;
    }
    
    $fields[] = "updated_at = NOW()";
    
    $query = "UPDATE weeks SET " . implode(", ", $fields) . " WHERE week_id = ?";
    $params[] = $week_id; 
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($params)) {
        sendResponse(["message" => "Week updated successfully", "week_id" => $week_id]);
    } else {
        sendError("Failed to update week", 500);
    }
}

function deleteWeek($db, $weekId) {
    if (!$weekId) {
        sendError("Missing week_id", 400);
        return;
    }
    
    $checkQuery = "SELECT id FROM weeks WHERE week_id = :week_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':week_id', $weekId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        sendError("Week not found", 404);
        return;
    }
    
    $delCommentsQuery = "DELETE FROM comments WHERE week_id = :week_id";
    $delCommentsStmt = $db->prepare($delCommentsQuery);
    $delCommentsStmt->bindParam(':week_id', $weekId);
    $delCommentsStmt->execute();
    
    $query = "DELETE FROM weeks WHERE week_id = :week_id";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':week_id', $weekId);
    
    if ($stmt->execute()) {
        sendResponse(["message" => "Week and associated comments deleted successfully"]);
    } else {
        sendError("Failed to delete week", 500);
    }
}



function getCommentsByWeek($db, $weekId) {
    if (!$weekId) {
        sendError("Missing week_id", 400);
        return;
    }
    
    $query = "SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = :week_id ORDER BY created_at ASC";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':week_id', $weekId);
    
    $stmt->execute();
    
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse($comments);
}

function createComment($db, $data) {
    if (empty($data['week_id']) || empty($data['author']) || empty($data['text'])) {
        sendError("Missing required fields (week_id, author, text)", 400);
        return;
    }
    
    $week_id = sanitizeInput($data['week_id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    if ($text === '') {
        sendError("Comment text cannot be empty", 400);
        return;
    }
    
    $checkQuery = "SELECT id FROM weeks WHERE week_id = :week_id";
    $stmtCheck = $db->prepare($checkQuery);
    $stmtCheck->bindParam(':week_id', $week_id);
    $stmtCheck->execute();
    
    if ($stmtCheck->rowCount() == 0) {
        sendError("Week ID does not exist", 404);
        return;
    }
    
    $query = "INSERT INTO comments (week_id, author, text) VALUES (:week_id, :author, :text)";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':week_id', $week_id);
    $stmt->bindParam(':author', $author);
    $stmt->bindParam(':text', $text);
    
    if ($stmt->execute()) {
        $data['id'] = $db->lastInsertId();
        sendResponse($data, 201);
    } else {
        sendError("Failed to create comment", 500);
    }
}

function deleteComment($db, $commentId) {
    if (!$commentId) {
        sendError("Missing comment id", 400);
        return;
    }
    
    $checkQuery = "SELECT id FROM comments WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $commentId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        sendError("Comment not found", 404);
        return;
    }
    
    $query = "DELETE FROM comments WHERE id = :id";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':id', $commentId);
    
    if ($stmt->execute()) {
        sendResponse(["message" => "Comment deleted successfully"]);
    } else {
        sendError("Failed to delete comment", 500);
    }
}



try {
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            if (isset($_GET['week_id'])) {
                getWeekById($db, $_GET['week_id']);
            } else {
                getAllWeeks($db);
            }
            
        } elseif ($method === 'POST') {
            createWeek($db, $data);
            
        } elseif ($method === 'PUT') {
            updateWeek($db, $data);
            
        } elseif ($method === 'DELETE') {
            $weekId = isset($_GET['week_id']) ? $_GET['week_id'] : (isset($data['week_id']) ? $data['week_id'] : null);
            deleteWeek($db, $weekId);
            
        } else {
            header("HTTP/1.1 405 Method Not Allowed");
            echo json_encode(['message' => 'Method Not Allowed']);
        }
    }
    
    elseif ($resource === 'comments') {
        
        if ($method === 'GET') {
            $weekId = isset($_GET['week_id']) ? $_GET['week_id'] : null;
            getCommentsByWeek($db, $weekId);
            
        } elseif ($method === 'POST') {
            createComment($db, $data);
            
        } elseif ($method === 'DELETE') {
             $commentId = isset($_GET['id']) ? $_GET['id'] : (isset($data['id']) ? $data['id'] : null);
             deleteComment($db, $commentId);
            
        } else {
            header("HTTP/1.1 405 Method Not Allowed");
            echo json_encode(['message' => 'Method Not Allowed']);
        }
    }
    
    else {
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }
    
} catch (PDOException $e) {
    sendError("Database error occurred: " . $e->getMessage(), 500);
    
} catch (Exception $e) {
    sendError("An unexpected error occurred", 500);
}


function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function sendError($message, $statusCode = 400) {
    $response = ['success' => false, 'error' => $message];
    sendResponse($response, $statusCode);
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isValidSortField($field, $allowedFields) {
    return in_array($field, $allowedFields);
}

?>