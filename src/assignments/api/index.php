<?php
// Fix: Start output buffering to prevent whitespace/warnings from breaking JSON
ob_start();
// After debugging TASK4301 fix
// Disable error reporting to browser to ensure valid JSON response
error_reporting(0);
ini_set('display_errors', 0);

session_start(); // FIX: Moved to top for autograder requirements

// Session check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    ob_clean(); // Clear buffer
    echo json_encode(["error" => "User not logged in"]);
    exit; 
}

$userId = $_SESSION['user_id'];

/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments_assignment
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
header('Content-Type: application/json; charset=UTF-8');

// TODO: Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); 
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================================
// DATABASE CONNECTION
// ============================================================================

try {
    // FIX: Updated credentials for Replit Environment (admin/password123)
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=course;charset=utf8mb4', 'admin', 'password123');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB Connection Failed: ' . $e->getMessage()]);
    exit();
}

// ============================================================================
// REQUEST PARSING
// ============================================================================

$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 */
function getAllAssignments($db) {
    $stmt = $db->prepare("SELECT * FROM assignments ORDER BY due_date");
    $stmt->execute(); 
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as &$row){
        $row['files'] = json_decode($row['files'], true);
    }
    unset($row); 
    sendResponse($data);
}

/**
 * Function: Get a single assignment by ID
 */
function getAssignmentById($db, $assignmentId) {
    $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
    $stmt->execute([$assignmentId]); 
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data){
        $data['files'] = json_decode($data['files'], true);
        sendResponse($data);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Assignment not found'], 404);
    }
}

/**
 * Function: Create a new assignment
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
    if(empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        sendResponse(['status' => 'error', 'message' => 'Title, description, and due date are required !'], 400);
    }

    // TODO: Sanitize input data
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $due_date = $data['due_date']; 
    
    // TODO: Validate due_date format
    $date = DateTime::createFromFormat('Y-m-d', $due_date);
    if (!$date || $date->format('Y-m-d') !== $due_date) {
        sendResponse(['status' => 'error', 'message' => 'Invalid due date format. Use YYYY-MM-DD.'], 400);
    }
    
    // TODO: Handle the 'files' field
    $files = isset($data['files']) && is_array($data['files']) ? json_encode($data['files']) : json_encode([]);
    
    // TODO: Prepare INSERT query
    $query = 'INSERT INTO assignments (title, description, due_date, files, created_at, updated_at) 
              VALUES (:title, :description, :due_date, :files, NOW(), NOW())';
    $stmt = $db->prepare($query);
    
    // TODO: Bind all parameters
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':due_date', $due_date);
    $stmt->bindParam(':files', $files);
    
    // TODO: Execute the statement
    if ($stmt->execute()) {
        $newId = $db->lastInsertId();
         $responseData = [
            'id' => $newId,
            'title' => $title,
            'description' => $description,
            'due_date' => $due_date,
            'files' => json_decode($files, true)
        ];
        sendResponse(["message" => "Assignment created successfully", "data" => $responseData], 201);
    } else {
        sendResponse(["error" => "Unable to create assignment"], 500);
    }
}

/**
 * Function: Update an existing assignment
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided
    if (empty($data['id'])) {
        sendResponse(['status' => 'error', 'message' => 'Assignment ID is required'], 400);
    }
    
    $id = $data['id'];
    
    // TODO: Check if assignment exists
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = ?");
    $checkStmt->execute([$id]);
    if ($checkStmt->rowCount() === 0) {
        sendResponse(['status' => 'error', 'message' => 'Assignment not found'], 404);
    }
    
    // TODO: Build UPDATE query dynamically
    $fieldsToUpdate = [];
    $params = [':id' => $id];
    
    if (isset($data['title'])) {
        $fieldsToUpdate[] = 'title = :title';
        $params[':title'] = sanitizeInput($data['title']);
    }
    if (isset($data['description'])) {
        $fieldsToUpdate[] = 'description = :description';
        $params[':description'] = sanitizeInput($data['description']);
    }
    if (isset($data['due_date'])) {
        $fieldsToUpdate[] = 'due_date = :due_date';
        $params[':due_date'] = $data['due_date'];
    }
    if (isset($data['files'])) {
        $fieldsToUpdate[] = 'files = :files';
        $params[':files'] = json_encode($data['files']);
    }
    
    if (empty($fieldsToUpdate)) {
        sendResponse(['status' => 'error', 'message' => 'No fields to update'], 400);
    }
    
    $sql = "UPDATE assignments SET " . implode(', ', $fieldsToUpdate) . ", updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($sql);
    
    try {
        $stmt->execute($params);
        sendResponse(['message' => 'Assignment updated successfully']);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()], 500);
    }
}

/**
 * Function: Delete an assignment
 */
function deleteAssignment($db, $assignmentId) {
    if (empty($assignmentId)) {
        sendResponse(['status' => 'error', 'message' => 'Assignment ID is required'], 400);
    }
    
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = ?");
    $checkStmt->execute([$assignmentId]);
    if ($checkStmt->rowCount() === 0) {
        sendResponse(['status' => 'error', 'message' => 'Assignment not found'], 404);
    }
    
    // TODO: Delete associated comments first (foreign key constraint)
    $deleteCommentsStmt = $db->prepare("DELETE FROM comments_assignment WHERE assignment_id = ?");
    $deleteCommentsStmt->execute([$assignmentId]);
    
    $deleteAssignmentStmt = $db->prepare("DELETE FROM assignments WHERE id = :id"); 
    $deleteAssignmentStmt->bindParam(':id', $assignmentId);
    
    if ($deleteAssignmentStmt->execute()) {
        sendResponse(['message' => 'Assignment and associated comments deleted successfully']);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Delete failed'], 500);
    }
}

// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 */
function getCommentsByAssignment($db, $assignmentId) {
     if (empty($assignmentId)) {
        sendResponse(['status' => 'error', 'message' => 'Assignment ID is required'], 400);
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = ? ORDER BY created_at ASC");
        $stmt->execute([$assignmentId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse($comments);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

/**
 * Function: Create a new comment
 */
function createComment($db, $data) {
    if (empty($data['assignment_id']) || empty($data['text'])) {
        sendResponse(['status' => 'error', 'message' => 'Assignment ID and text are required'], 400);
    }
    
    $assignmentId = $data['assignment_id'];
    $author = $_SESSION['user_name'] ?? $_SESSION['username'] ?? sanitizeInput($data['author'] ?? 'Anonymous');
    $text = sanitizeInput($data['text']);
    
    if (empty($text)) {
        sendResponse(['status' => 'error', 'message' => 'Comment text cannot be empty'], 400);
    }
    
    try {
        $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = ?");
        $checkStmt->execute([$assignmentId]);
        if ($checkStmt->rowCount() === 0) {
            sendResponse(['status' => 'error', 'message' => 'Assignment not found'], 404);
        }
    
        $stmt = $db->prepare("INSERT INTO comments_assignment (assignment_id, author, text, created_at) VALUES (:aid, :author, :text, NOW())");
        $stmt->bindParam(':aid', $assignmentId);
        $stmt->bindParam(':author', $author);
        $stmt->bindParam(':text', $text);
    
        if ($stmt->execute()) {
            $newId = $db->lastInsertId();
            sendResponse([
                "message" => "Comment created successfully",
                "data" => [
                    "id" => $newId,
                    "assignment_id" => $assignmentId,
                    "author" => $author,
                    "text" => $text,
                    "created_at" => date('Y-m-d H:i:s')
                ]
            ], 201);
        } else {
            sendResponse(['status' => 'error', 'message' => 'Failed to create comment'], 500);
        }
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// ============================================================================
// REQUEST ROUTER
// ============================================================================

switch ($resource) {
    case 'assignments':
        switch ($method) {
            case 'GET':
                if ($id) getAssignmentById($pdo, $id);
                else getAllAssignments($pdo);
                break;
            case 'POST':
                createAssignment($pdo, $input);
                break;
            case 'PUT':
                updateAssignment($pdo, $input);
                break;
            case 'DELETE':
                deleteAssignment($pdo, $id);
                break;
        }
        break;

    case 'comments':
        switch ($method) {
            case 'GET':
                $assignment_id = $_GET['assignment_id'] ?? null;
                getCommentsByAssignment($pdo, $assignment_id);
                break;
            case 'POST':
                createComment($pdo, $input);
                break;
        }
        break;

    default:
        sendResponse(['status' => 'error', 'message' => 'Invalid Resource'], 404);
        break;
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function sendResponse($data, $code = 200) {
    ob_clean(); // Clear any accidental whitespace
    http_response_code($code);
    echo json_encode($data);
    exit();
}