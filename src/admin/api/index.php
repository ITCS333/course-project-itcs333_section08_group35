<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
require_once 'db.php'

// TODO: Get the PDO database connection
$db = getDBConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];


// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$input = file_get_contents('php://input');
$data = json_decode($input, true);


// TODO: Parse query parameters for filtering and searching
$queryParams = $_GET;

/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db) {
    global $queryParams;
    
    $sql = "SELECT id, student_id, name, email, created_at FROM students";
    $params = [];
    
    if (isset($queryParams['search']) && !empty($queryParams['search'])) {
        $searchTerm = '%' . $queryParams['search'] . '%';
        $sql .= " WHERE name LIKE :search OR student_id LIKE :search OR email LIKE :search";
        $params[':search'] = $searchTerm;
    }
    
    if (isset($queryParams['sort'])) {
        $allowedFields = ['name', 'student_id', 'email'];
        if (in_array($queryParams['sort'], $allowedFields)) {
            $sortField = $queryParams['sort'];
            $order = 'ASC';
            
            if (isset($queryParams['order']) && strtoupper($queryParams['order']) === 'DESC') {
                $order = 'DESC';
            }
            
            $sql .= " ORDER BY $sortField $order";
        }
    }
    
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    $students = $stmt->fetchAll();
    
    sendResponse(['success' => true, 'data' => $students]);
}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    $sql = "SELECT id, student_id, name, email, created_at FROM students WHERE student_id = :student_id";
    
        $stmt = $db->prepare($sql);
    $stmt->bindParam(':student_id', $studentId);
    
    $stmt->execute();

    $student = $stmt->fetch();
    
    if ($student) {
        sendResponse(['success' => true, 'data' => $student]);
    } else {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }
}


/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    if (!isset($data['student_id']) || !isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
        sendResponse(['success' => false, 'message' => 'Missing required fields'], 400);
    }
    
    // Sanitize input data
    $studentId = trim($data['student_id']);
    $name = trim($data['name']);
    $email = trim($data['email']);
    $password = $data['password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(['success' => false, 'message' => 'Invalid email format'], 400);
    }
    
    // Check if student_id or email already exists
    $checkSql = "SELECT id FROM students WHERE student_id = :student_id OR email = :email";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':student_id' => $studentId, ':email' => $email]);
    
    if ($checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Student ID or email already exists'], 409);
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO students (student_id, name, email, password) VALUES (:student_id, :name, :email, :password)";
    $stmt = $db->prepare($sql);
    
    $stmt->bindParam(':student_id', $studentId);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);
    
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Student created successfully'], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create student'], 500);
    }
    
}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    if (!isset($data['student_id'])) {
        sendResponse(['success' => false, 'message' => 'Student ID required'], 400);
    }
    
    $studentId = $data['student_id'];
    
    $checkSql = "SELECT id FROM students WHERE student_id = :student_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':student_id' => $studentId]);
    
    if (!$checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }
    
    $updateFields = [];
    $params = [':student_id' => $studentId];
    
    if (isset($data['name'])) {
        $updateFields[] = "name = :name";
        $params[':name'] = trim($data['name']);
    }
    
    if (isset($data['email'])) {
        $emailCheckSql = "SELECT id FROM students WHERE email = :email AND student_id != :student_id";
        $emailCheckStmt = $db->prepare($emailCheckSql);
        $emailCheckStmt->execute([':email' => $data['email'], ':student_id' => $studentId]);
        
        if ($emailCheckStmt->fetch()) {
            sendResponse(['success' => false, 'message' => 'Email already exists'], 409);
        }
        
        $updateFields[] = "email = :email";
        $params[':email'] = trim($data['email']);
    }
    
    if (empty($updateFields)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }
    
    $sql = "UPDATE students SET " . implode(', ', $updateFields) . " WHERE student_id = :student_id";
    
    $stmt = $db->prepare($sql);
    
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Student updated successfully']);
    } else {
        sendResponse(['success' => false, 'message' => 'No changes made'], 500);
    }
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    if (empty($studentId)) {
        sendResponse(['success' => false, 'message' => 'Student ID required'], 400);
    }
    
    $checkSql = "SELECT id FROM students WHERE student_id = :student_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':student_id' => $studentId]);
    
    if (!$checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }
    
    $sql = "DELETE FROM students WHERE student_id = :student_id";
    $stmt = $db->prepare($sql);
    
    $stmt->bindParam(':student_id', $studentId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Student deleted successfully']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete student'], 500);
    }
    
}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    if (!isset($data['student_id']) || !isset($data['current_password']) || !isset($data['new_password'])) {
        sendResponse(['success' => false, 'message' => 'Missing required fields'], 400);
    }
    
    if (strlen($data['new_password']) < 8) {
        sendResponse(['success' => false, 'message' => 'Password must be at least 8 characters'], 400);
    }
    
    $sql = "SELECT password FROM students WHERE student_id = :student_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':student_id' => $data['student_id']]);
    $result = $stmt->fetch();
    
    if (!$result) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }
    
    if (!password_verify($data['current_password'], $result['password'])) {
        sendResponse(['success' => false, 'message' => 'Current password incorrect'], 401);
    }
    
    $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
    
    $updateSql = "UPDATE students SET password = :password WHERE student_id = :student_id";
    $updateStmt = $db->prepare($updateSql);
    
    $updateStmt->execute([':password' => $hashedPassword, ':student_id' => $data['student_id']]);
    
    if ($updateStmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to update password'], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method
    
    if ($method === 'GET') {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
        if (isset($queryParams['student_id'])) {
            getStudentById($db, $queryParams['student_id']);
        } else {
            getStudents($db);
        }
    } elseif ($method === 'POST') {
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()
        if (isset($queryParams['action']) && $queryParams['action'] === 'change_password') {
            changePassword($db, $data);
        } else {
            createStudent($db, $data);
        }
    } elseif ($method === 'PUT') {
        // TODO: Call updateStudent()
        updateStudent($db, $data);
    } elseif ($method === 'DELETE') {
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
        $studentId = $queryParams['student_id'] ?? $data['student_id'] ?? null;
        deleteStudent($db, $studentId);
    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'method not allowed']);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'Database error'], 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
    sendResponse(['success' => false, 'message' => 'Server error'], 500);
}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);

    // TODO: Echo JSON encoded data
    echo json_encode($data);

    // TODO: Exit to prevent further execution
    exit;

}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    $data = trim($data);
    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);
    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data);   
    // Return sanitized data
    return $data;
}

?>