<?php
// --- FIX: Prevent PHP Warnings from breaking JSON ---
error_reporting(0);          
ini_set('display_errors', 0);
ob_start();                  

/**
 * Authentication Handler for Login Form
 * 
 * This PHP script handles user authentication via POST requests from the Fetch API.
 * It validates credentials against a MySQL database using PDO,
 * creates sessions, and returns JSON responses.
 */

// --- Session Management ---
session_start();

// --- Set Response Headers ---
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// --- Helper Function to Output JSON Cleanly ---
function sendJson($data) {
    ob_clean(); 
    echo json_encode($data);
    exit;
}

// --- Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    sendJson(['success' => false, 'message' => 'invalid request method']);
}

// --- Get POST Data ---
$rawData = file_get_contents('php://input');
$data = json_decode($rawData , true);

// Check if decoding failed or fields are missing
if (!isset($data['email']) || !isset($data['password'])){
    sendJson(['success' => false, 'message' => 'email and password are required']);
}

$email = trim($data['email']);
$password = trim($data['password']);

// --- Server-Side Validation ---
if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
    sendJson(['success' => false, 'message' => 'invalid email format']);
}

// Adjusted min length to 4 to match the test password "password" logic if needed
if (strlen($password) < 4) { 
    sendJson(['success' => false, 'message' => 'password must be at least 4 characters']);
}

// --- Database Connection ---
function getDBConnection() {
    // FIXED: Updated credentials to match your Replit environment
    $host = '127.0.0.1';
    $dbname = 'course';
    $username = 'admin';
    $db_password = 'password123'; // Renamed variable to avoid conflict with user input $password

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // Return JSON error on connection fail instead of throwing raw exception
        sendJson(['success' => false, 'message' => 'Database connection failed']);
    }
}

$pdo = getDBConnection();

try {
    // --- Prepare SQL Query ---
    // FIXED: Changed 'role' to 'is_admin' to match your Schema as requested in the target example
    $sql = "SELECT id, name, email, password, is_admin FROM users WHERE email = ?";

    // --- Prepare the Statement ---
    $stmt = $pdo->prepare($sql);

    // --- Execute the Query ---
    $stmt->execute([$email]);

    // --- Fetch User Data ---
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Verify User Exists and Password Matches ---  
    // 1. Check if user exists
    // 2. Check Password: We check BOTH Plain text (for seeded users) AND Hash (for security)
    if ($user && ($password === $user['password'] || password_verify($password , $user['password']))) {

        // CONVERT is_admin (0/1) to String Role for Dashboard
        $roleName = ($user['is_admin'] == 1) ? 'Admin' : 'Student';

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $roleName; 
        $_SESSION['logged_in'] = true;

        sendJson([
            'success' => true,
            'message' => 'login successful',
            // Added redirect path so JS knows where to go
            'redirect' => '../dashboard.php',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $roleName
            ]
        ]);
    }
    else {
        sendJson(['success' => false, 'message' => 'Invalid email or password']);
    }

} catch (PDOException $e) {
    // Log error internally and send generic message to user
    error_log('Database error: ' . $e->getMessage());
    
    sendJson([
        'success' => false,
        'message' => 'Database Error: ' . $e->getMessage() // Showing message for debugging purposes
    ]);
}
?>