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
 * Weekly Breakdown Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course weeks
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures:
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - start_date (DATE)
 *   - links (TEXT) - JSON
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments_week
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (INT, FOREIGN KEY)
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
    // Using 'course' database as per reference
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

// FIX: Define method and resource to avoid "Undefined variable" errors
$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';

$input  = json_decode(file_get_contents('php://input'), true);

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    ob_clean(); // Clean buffer
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// ============================================================================
// WEEK CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all weeks
 * Method: GET
 * Endpoint: ?resource=weeks
 */
function getAllWeeks($db) {
    // Ordered by start_date
    $stmt = $db->prepare("SELECT * FROM weeks ORDER BY start_date ASC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row){
        // Decode links JSON
        $row['links'] = !empty($row['links']) ? json_decode($row['links'], true) : [];
    }
    unset($row); 

    sendResponse($data);
}

/**
 * Function: Get a single week by ID
 * Method: GET
 * Endpoint: ?resource=weeks&id={id}
 */
function getWeekById($db, $id) {
    // FIX: Use 'id' to match schema
    $stmt = $db->prepare("SELECT * FROM weeks WHERE id = ?");
    $stmt->execute([$id]); 
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data){
        $data['links'] = !empty($data['links']) ? json_decode($data['links'], true) : [];
        sendResponse($data);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Week not found'], 404);
    }
}

/**
 * Function: Create a new week
 * Method: POST
 * Endpoint: ?resource=weeks
 */
function createWeek($db, $data) {
    // TODO: Validate required fields
    if(empty($data['title']) || empty($data['description']) || empty($data['start_date'])) {
        sendResponse(['status' => 'error', 'message' => 'Title, description, and start_date are required'], 400);
    }

    // TODO: Sanitize input data
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $start_date = $data['start_date'];

    // TODO: Validate date format
    $date = DateTime::createFromFormat('Y-m-d', $start_date);
    if (!$date || $date->format('Y-m-d') !== $start_date) {
        sendResponse(['status' => 'error', 'message' => 'Invalid date format. Use YYYY-MM-DD.'], 400);
    }

    // TODO: Handle the 'links' field
    $links = isset($data['links']) && is_array($data['links']) ? json_encode($data['links']) : json_encode([]);

    // TODO: Prepare INSERT query
    $query = 'INSERT INTO weeks (title, description, start_date, links, created_at, updated_at) 
              VALUES (:title, :description, :start_date, :links, NOW(), NOW())';
    $stmt = $db->prepare($query);

    // TODO: Bind all parameters
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':links', $links);

    // TODO: Execute the statement
    if ($stmt->execute()) {
        $newId = $db->lastInsertId();
         $responseData = [
            'id' => $newId,
            'title' => $title,
            'description' => $description,
            'start_date' => $start_date,
            'links' => json_decode($links, true)
        ];

        sendResponse(["message" => "Week created successfully", "data" => $responseData], 201);
    } else {
        sendResponse(["error" => "Unable to create week"], 500);
    }
}

/**
 * Function: Update an existing week
 * Method: PUT
 * Endpoint: ?resource=weeks
 */
function updateWeek($db, $data) {
    // TODO: Validate that 'id' is provided
    if (empty($data['id'])) {
        sendResponse(['status' => 'error', 'message' => 'Week ID is required'], 400);
    }

    $id = $data['id'];

    // TODO: Check if week exists
    $checkStmt = $db->prepare("SELECT id FROM weeks WHERE id = ?");
    $checkStmt->execute([$id]);
    if ($checkStmt->rowCount() === 0) {
        sendResponse(['status' => 'error', 'message' => 'Week not found'], 404);
    }

    // TODO: Build UPDATE query dynamically
    $fieldsToUpdate = [];
    $params = [':id' => $id];

    if (isset($data['title'])) {
        $fieldsToUpdate[] = 'title = :title';
        $params[':title'] = $data['title'];
    }
    if (isset($data['description'])) {
        $fieldsToUpdate[] = 'description = :description';
        $params[':description'] = $data['description'];
    }
    if (isset($data['start_date'])) {
        $fieldsToUpdate[] = 'start_date = :start_date';
        $params[':start_date'] = $data['start_date'];
    }
    if (isset($data['links'])) {
        $fieldsToUpdate[] = 'links = :links';
        $params[':links'] = json_encode($data['links']);
    }

    if (empty($fieldsToUpdate)) {
        sendResponse(['status' => 'error', 'message' => 'No fields to update'], 400);
    }

    $sql = "UPDATE weeks SET " . implode(', ', $fieldsToUpdate) . ", updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($sql);

    try{
        $stmt->execute($params);
        sendResponse(['message' => 'Week updated successfully']);
    } catch (PDOException $e){
        sendResponse(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()], 500);
    }
}

/**
 * Function: Delete a week
 * Method: DELETE
 * Endpoint: ?resource=weeks&id={id}
 */
function deleteWeek($db, $id) {
    if (empty($id)) {
        sendResponse(['status' => 'error', 'message' => 'Week ID is required'], 400);
    }

    $checkStmt = $db->prepare("SELECT id FROM weeks WHERE id = ?");
    $checkStmt->execute([$id]);
    if ($checkStmt->rowCount() === 0) {
        sendResponse(['status' => 'error', 'message' => 'Week not found'], 404);
    }

    // TODO: Delete associated comments first (comments_week)
    $deleteCommentsStmt = $db->prepare("DELETE FROM comments_week WHERE week_id = ?");
    $deleteCommentsStmt->execute([$id]);

    // TODO: Delete week
    $deleteStmt = $db->prepare("DELETE FROM weeks WHERE id = :id"); 
    $deleteStmt->bindParam(':id', $id);

    if ($deleteStmt->execute()) {
        sendResponse(['message' => 'Week and associated comments deleted successfully']);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Delete failed'], 500);
    }
}


// ============================================================================
// COMMENT CRUD FUNCTIONS (using comments_week)
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Endpoint: ?resource=comments&week_id={week_id}
 */
function getCommentsByWeek($db, $weekId) {
     if (empty($weekId)) {
        sendResponse(['status' => 'error', 'message' => 'Week ID is required'], 400);
    }

    try {
        // Table: comments_week
        $stmt = $db->prepare("SELECT * FROM comments_week WHERE week_id = ? ORDER BY created_at ASC");
        $stmt->execute([$weekId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse($comments);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    if (empty($data['week_id']) || empty($data['text'])) {
        sendResponse(['status' => 'error', 'message' => 'Week ID and text are required'], 400);
    }

    $weekId = $data['week_id'];

    // FIX: Check session for user_name, fallback to author input or Anonymous
    $author = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : (isset($data['author']) ? sanitizeInput($data['author']) : 'Anonymous'));

    $text = sanitizeInput($data['text']);

    if (empty($text)) {
        sendResponse(['status' => 'error', 'message' => 'Comment text cannot be empty'], 400);
    }

    try{
        // TODO: Verify that the week exists
        $checkStmt = $db->prepare("SELECT id FROM weeks WHERE id = ?");
        $checkStmt->execute([$weekId]);
        if ($checkStmt->rowCount() === 0) {
            sendResponse(['status' => 'error', 'message' => 'Week not found'], 404);
        }

        // TODO: Prepare INSERT query for comment (comments_week)
        $stmt = $db->prepare("INSERT INTO comments_week (week_id, author, text, created_at) VALUES (:wid, :author, :text, NOW())");

        $stmt->bindParam(':wid', $weekId);
        $stmt->bindParam(':author', $author);
        $stmt->bindParam(':text', $text);

        if ($stmt->execute()) {
            $newId = $db->lastInsertId();
            sendResponse([
                "message" => "Comment created successfully",
                "data" => [
                    "id" => $newId,
                    "week_id" => $weekId,
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
// MAIN ROUTER
// ============================================================================

try {
    if ($resource === 'weeks') {
        if ($method === 'GET') {
            if (isset($_GET['id'])) {
                getWeekById($pdo, $_GET['id']);
            } else {
                getAllWeeks($pdo);
            }
        } elseif ($method === 'POST') {
            createWeek($pdo, $input);
        } elseif ($method === 'PUT') {
            updateWeek($pdo, $input);
        } elseif ($method === 'DELETE') {
            $id = $_GET['id'] ?? ($input['id'] ?? null);
            deleteWeek($pdo, $id);
        } else {
            sendResponse(['message' => 'Method Not Allowed'], 405);
        }
    }
    elseif ($resource === 'comments') {
        if ($method === 'GET') {
            // Support both week_id and id param
            $weekId = $_GET['week_id'] ?? ($_GET['id'] ?? null);
            getCommentsByWeek($pdo, $weekId);
        } elseif ($method === 'POST') {
            createComment($pdo, $input);
        } else {
            sendResponse(['message' => 'Method Not Allowed'], 405);
        }
    }
    else {
        sendResponse(['status' => 'error', 'message' => 'Invalid resource. Use weeks or comments'], 404);
    }
} catch (Exception $e) {
    sendResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
?>