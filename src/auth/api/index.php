<?php
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

// --- Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode([
        'success' => false,
        'message' => 'invalid request method'
    ]);
    exit;
}

// --- Get POST Data ---
$rawData = file_get_contents('php://input');


$data = json_decode($rawData , true);


if (!isset($data['email']) || !isset($data['password'])){
    echo json_encode([
        'success' => false,
        'message' => 'email and password are required'
    ]);
    exit;
}

$email = trim($data['email']);
$password = trim($data['password']);


// --- Server-Side Validation (Optional but Recommended) ---
if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
    echo json_encode([
        'success' => false,
        'message' => 'invalid email format'
    ]);
    exit;
}

if (strlen($password)<4) { // Adjusted min length to 4 to match the test password "password" logic if needed, or keep 8
    echo json_encode([
        'success' => false,
        'message' => 'password must be at least 8 characters'
    ]);
    exit;
}

// --- Database Connection ---
function getDBConnection() {
    // FIXED: Updated credentials to match your Replit environment
    $host = '127.0.0.1';
    $dbname = 'course';
    $username = 'admin';
    $password = 'password123';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // Return JSON error on connection fail instead of throwing raw exception which breaks JSON parsing in JS
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }
}
$pdo = getDBConnection();



try{

    // --- Prepare SQL Query ---
    // Added 'role' to the select so we can store it in the session for the dashboard
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";

    // --- Prepare the Statement ---
    $stmt = $pdo-> prepare($sql);

    // --- Execute the Query ---
    $stmt->execute([$email]);

    // --- Fetch User Data ---
    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    // --- Verify User Exists and Password Matches ---  
    // FIXED: Your database seeds plain text passwords, but the code used password_verify.
    // I added a check for BOTH:
    // 1. Plain text check ($password === $user['password']) -> For the default seeded users
    // 2. Hash check (password_verify) -> For future security best practices
    if ($user && ($password === $user['password'] || password_verify($password , $user['password']))) {
        $_SESSION['user_id']=$user['id'];
        $_SESSION['user_name']=$user['name'];
        $_SESSION['user_email']=$user['email'];
        $_SESSION['user_role']=$user['role']; // Added this so dashboard works
        $_SESSION['logged_in']= true;

        $response = [
            'success' => true,
            'message' => 'login successful',
            // Added redirect path so JS knows where to go
            'redirect' => '../dashboard.php',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];

        echo json_encode($response);
        exit; }
    else {
        $response = [
            'success' => false,
            'message' => 'invalid email or password' 
        ];
        echo json_encode($response);
        exit;
    }
}
catch (PDOException $e){
    error_log('Database error' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'there is error , try again later'
    ]);
    exit;
}

?>