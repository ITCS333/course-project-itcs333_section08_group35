<?php
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
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
// After debugging TASK4301 fix
session_start();
header('Content-Type: application/json; charset=UTF-8');


// TODO: Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); // Added PUT and OPTIONS
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

try{
    $pdo = new PDO('mysql:host=localhost;dbname=course;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Ensure exceptions are thrown
} catch (Exception $e) {
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

$action = $_REQUEST['action'] ?? '';
$id     = $_GET['id'] ?? null;
$search = trim($_REQUEST['search'] ?? '');
$input  = json_decode(file_get_contents('php://input'), true);


// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    $stmt = $db->prepare("SELECT * FROM assignments ORDER BY due_date");
    $stmt->execute(); // Added execute call
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as &$row){
        $row['files'] = json_decode($row['files'], true);
    }
    unset($row); // Break reference

    sendResponse($data);
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
    $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
    // FIX: Use the passed argument $assignmentId, not the global $id
    $stmt->execute([$assignmentId]); 
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data){
        // FIX: Added 'true' to json_decode and removed trailing comma
        $data['files'] = json_decode($data['files'], true);
        sendResponse($data);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Assignment not found'], 404);
    }
    
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
    if(empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        sendResponse(['status' => 'error', 'message' => 'Title, description, and due date are required !'], 400);
    }
    

    //Fix 4315 
    // TODO: Sanitize input data
    $assignmentId = $data['assignment_id'];
    // Use session username if available, otherwise use input
    $author = isset($_SESSION['username']) ? $_SESSION['username'] : sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    
    // TODO: Validate due_date format
    $date = DateTime::createFromFormat('Y-m-d', $due_date);
    if (!$date || $date->format('Y-m-d') !== $due_date) {
        sendResponse(['status' => 'error', 'message' => 'Invalid due date format. Use YYYY-MM-DD.'], 400);
    }
    
    
    // TODO: Generate a unique assignment ID
    
    
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
            'files' => json_decode($files, true) // FIX: Decode to array for response
        ];

        sendResponse(["message" => "Assignment created successfully", "data" => $responseData], 201);
    } else {
        sendResponse(["error" => "Unable to create assignment"], 500);
    }
    
}


/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
    if (empty($data['id'])) {
        sendResponse(['status' => 'error', 'message' => 'Assignment ID is required'], 400);
    }
    
    
    // TODO: Store assignment ID in variable
    $id = $data['id'];
    
    
    // TODO: Check if assignment exists
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = ?");
    $checkStmt->execute([$id]);
    if ($checkStmt->rowCount() === 0) {
        sendResponse(['status' => 'error', 'message' => 'Assignment not found'], 404);
    }
    
    
    // TODO: Build UPDATE query dynamically based on provided fields
    $fieldsToUpdate = [];
    $params = [':id' => $id];
    
    // TODO: Check which fields are provided and add to SET clause
    if (isset($data['title'])) {
        $fieldsToUpdate[] = 'title = :title';
        $params[':title'] = $data['title'];
    }
    if (isset($data['description'])) {
        $fieldsToUpdate[] = 'description = :description';
        $params[':description'] = $data['description'];
    }
    if (isset($data['due_date'])) {
        $fieldsToUpdate[] = 'due_date = :due_date';
        $params[':due_date'] = $data['due_date'];
    }
    if (isset($data['files'])) {
        $fieldsToUpdate[] = 'files = :files';
        $params[':files'] = json_encode($data['files']);
    }
    
    // TODO: If no fields to update (besides updated_at), return 400 error
    if (empty($fieldsToUpdate)) {
        sendResponse(['status' => 'error', 'message' => 'No fields to update'], 400);
    }
    
    // TODO: Complete the UPDATE query
    $sql = "UPDATE assignments SET " . implode(', ', $fieldsToUpdate) . ", updated_at = NOW() WHERE id = :id";
    
    
    // TODO: Prepare the statement
    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters dynamically
    try{
        $stmt->execute($params);
        sendResponse(['message' => 'Assignment updated successfully']);
    } catch (PDOException $e){
        sendResponse(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()], 500);
    }
    
    
    // TODO: Execute the statement
    
    
    // TODO: Check if update was successful
    
    
    // TODO: If no rows affected, return appropriate message
    
}


/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        sendResponse(['status' => 'error', 'message' => 'Assignment ID is required'], 400);
    }
    
    
    // TODO: Check if assignment exists
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = ?");
    $checkStmt->execute([$assignmentId]);
    if ($checkStmt->rowCount() === 0) {
        sendResponse(['status' => 'error', 'message' => 'Assignment not found'], 404);
    }
    
    
    // TODO: Delete associated comments first (due to foreign key constraint)
    // FIX: Updated table name to comments_assignment
    $deleteCommentsStmt = $db->prepare("DELETE FROM comments_assignment WHERE assignment_id = ?");
    $deleteCommentsStmt->execute([$assignmentId]);
    
    // TODO: Prepare DELETE query for assignment
    // FIX: Changed ? to :id to match bindParam below
    $deleteAssignmentStmt = $db->prepare("DELETE FROM assignments WHERE id = :id"); 
    
    // TODO: Bind the :id parameter
    $deleteAssignmentStmt->bindParam(':id', $assignmentId);
    
    
    // TODO: Execute the statement
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
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
     if (empty($assignmentId)) {
        sendResponse(['status' => 'error', 'message' => 'Assignment ID is required'], 400);
    }
    
    try {
        // FIX: Updated table name to comments_assignment
        $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = ? ORDER BY created_at ASC");
        $stmt->execute([$assignmentId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
        sendResponse($comments);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }

    
}


/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(['status' => 'error', 'message' => 'Assignment ID, author, and text are required'], 400);
    }
    
    
    // TODO: Sanitize input data
    $assignmentId = $data['assignment_id'];
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    
    // TODO: Validate that text is not empty after trimming
    if (empty($text)) {
        sendResponse(['status' => 'error', 'message' => 'Comment text cannot be empty'], 400);
    }
    
    try{
    // TODO: Verify that the assignment exists
        $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = ?");
        $checkStmt->execute([$assignmentId]);
        if ($checkStmt->rowCount() === 0) {
            sendResponse(['status' => 'error', 'message' => 'Assignment not found'], 404);
        }
    
    
    // TODO: Prepare INSERT query for comment
    // FIX: Updated table name to comments_assignment
    $stmt = $db->prepare("INSERT INTO comments_assignment (assignment_id, author, text, created_at) VALUES (:aid, :author, :text, NOW())");
    
    
    // TODO: Bind all parameters
    $stmt->bindParam(':aid', $assignmentId);
    $stmt->bindParam(':author', $author);
    $stmt->bindParam(':text', $text);
    
    
    // TODO: Execute the statement
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



    
    
    // TODO: Get the ID of the inserted comment
    
    
    // TODO: Return success response with created comment data
    



/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
     if (empty($commentId)) {
        sendResponse(['status' => 'error', 'message' => 'Comment ID is required'], 400);
    }
    
    try{
    // TODO: Check if comment exists
    // FIX: Updated table name to comments_assignment
    $checkStmt = $db->prepare("SELECT id FROM comments_assignment WHERE id = ?");
        $checkStmt->execute([$commentId]);
        if ($checkStmt->rowCount() === 0) {
            sendResponse(['status' => 'error', 'message' => 'Comment not found'], 404);
        }
    
    
    // TODO: Prepare DELETE query
    // FIX: Changed ? to :id and table name to comments_assignment
     $stmt = $db->prepare("DELETE FROM comments_assignment WHERE id = :id"); 
    
    
    // TODO: Bind the :id parameter
    $stmt->bindParam(':id', $commentId);
    
    // FIX: Removed array argument from execute(), as parameters are already bound via bindParam
    if ($stmt->execute()) {
        sendResponse(['message' => 'Comment deleted successfully']);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Delete failed'], 500);
        }
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
    }
    
    
    // TODO: Execute the statement
    
    
    // TODO: Check if delete was successful
    
    
    // TODO: If delete failed, return 500 error
    



// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
    
    
    // TODO: Route based on HTTP method and resource type
    
    // $method and $resource are now defined at the top of the file
    if ($method === 'GET') {
        // TODO: Handle GET requests
        
        
        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
            if ($id) {
                getAssignmentById($pdo, $id);
            } else {
                getAllAssignments($pdo); }
            
        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
            $assignmentId = $_GET['assignment_id'] ?? null;
            getCommentsByAssignment($pdo, $assignmentId);
            
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['status' => 'error', 'message' => 'Invalid resource or missing parameters'], 400);
            
        }
        
    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)
        
        if ($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
             createAssignment($pdo, $input);
            
        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
                createComment($pdo, $input);
            
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['status' => 'error', 'message' => 'Invalid resource'], 400);
            
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            updateAssignment($pdo, $input);
            
        } else {
            // TODO: PUT not supported for other resources
             sendResponse(['status' => 'error', 'message' => 'PUT not supported for this resource'], 405);
            
        }
        
    } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests
        
        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
            $delId = $_GET['id'] ?? null;
            deleteAssignment($pdo, $delId);
            
        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
            $delId = $_GET['id'] ?? null;
            deleteComment($pdo, $delId);
            
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['status' => 'error', 'message' => 'Invalid resource'], 400);
            
        }
        
    } else {
        // TODO: Method not supported
        sendResponse(['status' => 'error', 'message' => 'Method Not Allowed'], 405);
        
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    sendResponse(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()], 500);
    
    
} catch (Exception $e) {
    // TODO: Handle general errors
    sendResponse(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()], 500);
    
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    
    
    // TODO: Ensure data is an array
    if (!is_array($data)) {
        $data = ['data' => $data];
    }
    
    
    // TODO: Echo JSON encoded data
    echo json_encode($data);
    
    
    // TODO: Exit to prevent further execution
    exit();
    
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
    $data = trim($data);
    
    
    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);
    
    
    // TODO: Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    
    // TODO: Return the sanitized data
    return $data;
    
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    // FIX: Use different variable for the object to avoid shadowing input string
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if ($d && $d->format('Y-m-d') === $date) {
        return true;
    }
    
    
    // TODO: Return true if valid, false otherwise
    return false;
    
}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
    // FIX: Simplified boolean return logic
    return in_array($value, $allowedValues);
}

?>
