<?php
// --- FIX: Prevent PHP Warnings from breaking JSON (From Reference) ---
error_reporting(0);          
ini_set('display_errors', 0);
ob_start();                  

session_start(); 

// --- Set Response Headers ---
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle Preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Helper Function to Output JSON Cleanly (From Reference) ---
function sendJson($data, $code = 200) {
    ob_clean(); 
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// --- Database Connection (From Reference) ---
// We embed this directly to avoid "Failed opening required" errors
function getDBConnection() {
    $host = '127.0.0.1';
    $dbname = 'course';
    $username = 'admin';
    $db_password = 'password123'; 

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database connection failed']);
    }
}

$pdo = getDBConnection();

// --- Get Input Data ---
$input = file_get_contents("php://input");
$data = json_decode($input, true);
// Handle cases where body is empty
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = [];
}

// --- API Logic ---

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? $_GET['id'] : null;
$resourceId = isset($_GET['resource_id']) ? $_GET['resource_id'] : null;
$commentId = isset($_GET['comment_id']) ? $_GET['comment_id'] : null;

// --- Functions ---

function getAllResources($pdo) {
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $orderDir = isset($_GET['order']) ? $_GET['order'] : 'desc';

    // Whitelist columns to prevent SQL injection
    $validColumns = ['title', 'created_at'];
    if (!in_array($sortBy, $validColumns)) $sortBy = 'created_at';
    
    $validOrders = ['asc', 'desc'];
    if (!in_array(strtolower($orderDir), $validOrders)) $orderDir = 'desc';

    $sql = "SELECT * FROM resources";
    $params = [];
    
    if ($searchTerm) {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
    }
    
    $sql .= " ORDER BY $sortBy $orderDir";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultSet = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendJson(['success' => true, 'data' => $resultSet]);
}

function getResourceById($pdo, $id) {
    if (!is_numeric($id)) sendJson(['success' => false, 'message' => 'Invalid ID'], 400);
   
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        sendJson(['success' => true, 'data' => $row]);
    } else {
        sendJson(['success' => false, 'message' => 'Not found'], 404);
    }
}

function createResource($pdo, $data) {
    if (empty($data['title']) || empty($data['link'])) {
        sendJson(['success' => false, 'message' => 'Missing title or link'], 400);
    }

    $link = trim($data['link']);
    if (!filter_var($link, FILTER_VALIDATE_URL)) {
        sendJson(['success' => false, 'message' => 'Invalid URL'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO resources (title, description, link) VALUES (?, ?, ?)");
    if ($stmt->execute([trim($data['title']), trim($data['description'] ?? ''), $link])) {
        sendJson(['success' => true, 'message' => 'Created', 'id' => $pdo->lastInsertId()], 201);
    } else {
        sendJson(['success' => false, 'message' => 'Creation failed'], 500);
    }
}

function updateResource($pdo, $data) {
    if (empty($data['id'])) sendJson(['success' => false, 'message' => 'ID required'], 400);
    
    $fields = [];
    $params = [];
    
    if (!empty($data['title'])) { $fields[] = "title = ?"; $params[] = trim($data['title']); }
    if (!empty($data['description'])) { $fields[] = "description = ?"; $params[] = trim($data['description']); }
    if (!empty($data['link'])) { 
        if (!filter_var($data['link'], FILTER_VALIDATE_URL)) sendJson(['success' => false, 'message' => 'Invalid URL'], 400);
        $fields[] = "link = ?"; $params[] = trim($data['link']); 
    }
    
    if (empty($fields)) sendJson(['success' => false, 'message' => 'No fields to update'], 400);
    
    $params[] = $data['id'];
    $sql = "UPDATE resources SET " . implode(", ", $fields) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        sendJson(['success' => true, 'message' => 'Updated']);
    } else {
        sendJson(['success' => false, 'message' => 'Update failed'], 500);
    }
}

function deleteResource($pdo, $id) {
    if (!is_numeric($id)) sendJson(['success' => false, 'message' => 'Invalid ID'], 400);

    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM comments_resource WHERE resource_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM resources WHERE id = ?")->execute([$id]);
        $pdo->commit();
        sendJson(['success' => true, 'message' => 'Deleted']);
    } catch (Exception $e) {
        $pdo->rollBack();
        sendJson(['success' => false, 'message' => 'Delete failed'], 500);
    }
}

function getComments($pdo, $resourceId) {
    if (!is_numeric($resourceId)) sendJson(['success' => false, 'message' => 'Invalid ID'], 400);
    $stmt = $pdo->prepare("SELECT * FROM comments_resource WHERE resource_id = ? ORDER BY created_at ASC");
    $stmt->execute([$resourceId]);
    sendJson(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment($pdo, $data) {
    if (empty($data['resource_id']) || empty($data['author']) || empty($data['text'])) {
        sendJson(['success' => false, 'message' => 'Missing fields'], 400);
    }
    $stmt = $pdo->prepare("INSERT INTO comments_resource (resource_id, author, text) VALUES (?, ?, ?)");
    if ($stmt->execute([$data['resource_id'], trim($data['author']), trim($data['text'])])) {
        sendJson(['success' => true, 'message' => 'Comment added', 'id' => $pdo->lastInsertId()], 201);
    } else {
        sendJson(['success' => false, 'message' => 'Failed to add comment'], 500);
    }
}

function deleteComment($pdo, $id) {
    if (!is_numeric($id)) sendJson(['success' => false, 'message' => 'Invalid ID'], 400);
    $stmt = $pdo->prepare("DELETE FROM comments_resource WHERE id = ?");
    if ($stmt->execute([$id])) {
        sendJson(['success' => true, 'message' => 'Comment deleted']);
    } else {
        sendJson(['success' => false, 'message' => 'Delete failed'], 500);
    }
}

// --- Routing ---

try {
    if ($method === 'GET') {
        if ($action === 'comments' && $resourceId) getComments($pdo, $resourceId);
        elseif ($id) getResourceById($pdo, $id);
        else getAllResources($pdo);
    } 
    elseif ($method === 'POST') {
        if ($action === 'comment') createComment($pdo, $data);
        else createResource($pdo, $data);
    } 
    elseif ($method === 'PUT') {
        updateResource($pdo, $data);
    } 
    elseif ($method === 'DELETE') {
        if ($action === 'delete_comment' && $commentId) deleteComment($pdo, $commentId);
        elseif ($id || isset($data['id'])) deleteResource($pdo, $id ?? $data['id']);
        else sendJson(['success' => false, 'message' => 'ID required'], 400);
    } 
    else {
        sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    // Log error internally and send generic JSON message
    error_log($e->getMessage());
    sendJson(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}

?>