<?php
/**
 * Weekly Course Breakdown API
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Adjust the path as necessary for your file structure
// require_once '../config/Database.php'; 

// TODO: Get the PDO database connection
// For the purpose of this file, I will assume the Database class exists.
// If you don't have a class, you can create the PDO connection directly here.
try {
    // Example using a Database class:
    // $database = new Database();
    // $db = $database->getConnection();
    
    // OR Direct connection (Replace placeholders with your credentials):
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

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$data = json_decode(file_get_contents("php://input"), true);

// TODO: Parse query parameters
$resource = isset($_GET['resource']) ? $_GET['resource'] : 'weeks';


// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

function getAllWeeks($db) {
    // TODO: Initialize variables for search, sort, and order from query parameters
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'start_date';
    $order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'ASC';

    // TODO: Start building the SQL query
    $query = "SELECT week_id, title, start_date, description, links, created_at FROM weeks";
    
    // TODO: Check if search parameter exists
    if ($search) {
        $query .= " WHERE title LIKE :search OR description LIKE :search";
    }
    
    // TODO: Check if sort parameter exists (Validation)
    $allowedSorts = ['title', 'start_date', 'created_at', 'week_id'];
    if (!in_array($sort, $allowedSorts)) {
        $sort = 'start_date';
    }
    
    // TODO: Check if order parameter exists (Validation)
    if (!in_array($order, ['ASC', 'DESC'])) {
        $order = 'ASC';
    }
    
    // TODO: Add ORDER BY clause to the query
    $query .= " ORDER BY $sort $order";
    
    // TODO: Prepare the SQL query using PDO
    $stmt = $db->prepare($query);
    
    // TODO: Bind parameters if using search
    if ($search) {
        $searchTerm = "%{$search}%";
        $stmt->bindValue(':search', $searchTerm);
    }
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Process each week's links field
    foreach ($weeks as &$week) {
        if (!empty($week['links'])) {
            $week['links'] = json_decode($week['links'], true);
        } else {
            $week['links'] = [];
        }
    }
    
    // TODO: Return JSON response with success status and data
    sendResponse($weeks);
}

function getWeekById($db, $weekId) {
    // TODO: Validate that week_id is provided
    if (!$weekId) {
        sendError("Missing week_id", 400);
        return;
    }
    
    // TODO: Prepare SQL query to select week by week_id
    $query = "SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = :week_id LIMIT 1";
    $stmt = $db->prepare($query);
    
    // TODO: Bind the week_id parameter
    $stmt->bindParam(':week_id', $weekId);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch the result
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Check if week exists
    if ($week) {
        // Decode the links JSON
        $week['links'] = json_decode($week['links'], true);
        sendResponse($week);
    } else {
        sendError("Week not found", 404);
    }
}

function createWeek($db, $data) {
    // TODO: Validate required fields
    if (empty($data['week_id']) || empty($data['title']) || empty($data['start_date']) || empty($data['description'])) {
        sendError("Missing required fields (week_id, title, start_date, description)", 400);
        return;
    }
    
    // TODO: Sanitize input data
    $week_id = sanitizeInput($data['week_id']);
    $title = sanitizeInput($data['title']);
    $start_date = sanitizeInput($data['start_date']);
    $description = sanitizeInput($data['description']);
    
    // TODO: Validate start_date format
    if (!validateDate($start_date)) {
        sendError("Invalid date format. Use YYYY-MM-DD", 400);
        return;
    }
    
    // TODO: Check if week_id already exists
    $checkQuery = "SELECT id FROM weeks WHERE week_id = :week_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':week_id', $week_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        sendError("Week ID already exists", 409);
        return;
    }
    
    // TODO: Handle links array
    $links = isset($data['links']) && is_array($data['links']) ? json_encode($data['links']) : json_encode([]);
    
    // TODO: Prepare INSERT query
    $query = "INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (:week_id, :title, :start_date, :description, :links)";
    $stmt = $db->prepare($query);
    
    // TODO: Bind parameters
    $stmt->bindParam(':week_id', $week_id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':links', $links);
    
    // TODO: Execute the query
    if ($stmt->execute()) {
        $data['links'] = json_decode($links, true); // decode back for response
        sendResponse($data, 201);
    } else {
        sendError("Failed to create week", 500);
    }
}

function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided
    if (empty($data['week_id'])) {
        sendError("Missing week_id for update", 400);
        return;
    }
    
    $week_id = sanitizeInput($data['week_id']);
    
    // TODO: Check if week exists
    $checkQuery = "SELECT id FROM weeks WHERE week_id = :week_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':week_id', $week_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        sendError("Week not found", 404);
        return;
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    $fields = [];
    $params = [];
    
    // TODO: Check which fields are provided and add to SET clauses
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
    
    // TODO: If no fields to update, return error response
    if (empty($fields)) {
        sendError("No fields provided for update", 400);
        return;
    }
    
    // TODO: Add updated_at timestamp to SET clauses
    $fields[] = "updated_at = NOW()";
    
    // TODO: Build the complete UPDATE query
    $query = "UPDATE weeks SET " . implode(", ", $fields) . " WHERE week_id = ?";
    $params[] = $week_id; // Add week_id as the last parameter
    
    // TODO: Prepare the query
    $stmt = $db->prepare($query);
    
    // TODO: Execute the query with bindings
    if ($stmt->execute($params)) {
        sendResponse(["message" => "Week updated successfully", "week_id" => $week_id]);
    } else {
        sendError("Failed to update week", 500);
    }
}

function deleteWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    if (!$weekId) {
        sendError("Missing week_id", 400);
        return;
    }
    
    // TODO: Check if week exists
    $checkQuery = "SELECT id FROM weeks WHERE week_id = :week_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':week_id', $weekId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        sendError("Week not found", 404);
        return;
    }
    
    // TODO: Delete associated comments first (referential integrity)
    $delCommentsQuery = "DELETE FROM comments WHERE week_id = :week_id";
    $delCommentsStmt = $db->prepare($delCommentsQuery);
    $delCommentsStmt->bindParam(':week_id', $weekId);
    $delCommentsStmt->execute();
    
    // TODO: Prepare DELETE query for week
    $query = "DELETE FROM weeks WHERE week_id = :week_id";
    $stmt = $db->prepare($query);
    
    // TODO: Bind the week_id parameter
    $stmt->bindParam(':week_id', $weekId);
    
    // TODO: Execute the query
    if ($stmt->execute()) {
        sendResponse(["message" => "Week and associated comments deleted successfully"]);
    } else {
        sendError("Failed to delete week", 500);
    }
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    if (!$weekId) {
        sendError("Missing week_id", 400);
        return;
    }
    
    // TODO: Prepare SQL query to select comments for the week
    $query = "SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = :week_id ORDER BY created_at ASC";
    $stmt = $db->prepare($query);
    
    // TODO: Bind the week_id parameter
    $stmt->bindParam(':week_id', $weekId);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return JSON response
    sendResponse($comments);
}

function createComment($db, $data) {
    // TODO: Validate required fields
    if (empty($data['week_id']) || empty($data['author']) || empty($data['text'])) {
        sendError("Missing required fields (week_id, author, text)", 400);
        return;
    }
    
    // TODO: Sanitize input data
    $week_id = sanitizeInput($data['week_id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    // TODO: Validate that text is not empty
    if ($text === '') {
        sendError("Comment text cannot be empty", 400);
        return;
    }
    
    // TODO: Check if the week exists
    $checkQuery = "SELECT id FROM weeks WHERE week_id = :week_id";
    $stmtCheck = $db->prepare($checkQuery);
    $stmtCheck->bindParam(':week_id', $week_id);
    $stmtCheck->execute();
    
    if ($stmtCheck->rowCount() == 0) {
        sendError("Week ID does not exist", 404);
        return;
    }
    
    // TODO: Prepare INSERT query
    $query = "INSERT INTO comments (week_id, author, text) VALUES (:week_id, :author, :text)";
    $stmt = $db->prepare($query);
    
    // TODO: Bind parameters
    $stmt->bindParam(':week_id', $week_id);
    $stmt->bindParam(':author', $author);
    $stmt->bindParam(':text', $text);
    
    // TODO: Execute the query
    if ($stmt->execute()) {
        $data['id'] = $db->lastInsertId();
        sendResponse($data, 201);
    } else {
        sendError("Failed to create comment", 500);
    }
}

function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided
    if (!$commentId) {
        sendError("Missing comment id", 400);
        return;
    }
    
    // TODO: Check if comment exists
    $checkQuery = "SELECT id FROM comments WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $commentId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        sendError("Comment not found", 404);
        return;
    }
    
    // TODO: Prepare DELETE query
    $query = "DELETE FROM comments WHERE id = :id";
    $stmt = $db->prepare($query);
    
    // TODO: Bind the id parameter
    $stmt->bindParam(':id', $commentId);
    
    // TODO: Execute the query
    if ($stmt->execute()) {
        sendResponse(["message" => "Comment deleted successfully"]);
    } else {
        sendError("Failed to delete comment", 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // ========== WEEKS ROUTES ==========
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
            // Get week_id from query param OR body
            $weekId = isset($_GET['week_id']) ? $_GET['week_id'] : (isset($data['week_id']) ? $data['week_id'] : null);
            deleteWeek($db, $weekId);
            
        } else {
            // Unsupported method
            header("HTTP/1.1 405 Method Not Allowed");
            echo json_encode(['message' => 'Method Not Allowed']);
        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        
        if ($method === 'GET') {
            $weekId = isset($_GET['week_id']) ? $_GET['week_id'] : null;
            getCommentsByWeek($db, $weekId);
            
        } elseif ($method === 'POST') {
            createComment($db, $data);
            
        } elseif ($method === 'DELETE') {
             // Get id from query param OR body
             $commentId = isset($_GET['id']) ? $_GET['id'] : (isset($data['id']) ? $data['id'] : null);
             deleteComment($db, $commentId);
            
        } else {
            header("HTTP/1.1 405 Method Not Allowed");
            echo json_encode(['message' => 'Method Not Allowed']);
        }
    }
    
    // ========== INVALID RESOURCE ==========
    else {
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }
    
} catch (PDOException $e) {
    // Handle database errors
    // error_log($e->getMessage()); // Uncomment for server logging
    sendError("Database error occurred: " . $e->getMessage(), 500);
    
} catch (Exception $e) {
    // Handle general errors
    sendError("An unexpected error occurred", 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

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