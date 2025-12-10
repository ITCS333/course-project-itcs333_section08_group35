<?php
/**
 * Course Resources API
 * 
 * This is a RESTful API that handles all CRUD operations for course resources 
 * and their associated comments/discussions.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: resources
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(255))
 *   - description (TEXT)
 *   - link (VARCHAR(500))
 *   - created_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - resource_id (INT, FOREIGN KEY references resources.id)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve resource(s) or comment(s)
 *   - POST: Create a new resource or comment
 *   - PUT: Update an existing resource
 *   - DELETE: Delete a resource or comment
 * 
 * Response Format: JSON
 * 
 * API Endpoints:
 *   Resources:
 *     GET    /api/resources.php                    - Get all resources
 *     GET    /api/resources.php?id={id}           - Get single resource by ID
 *     POST   /api/resources.php                    - Create new resource
 *     PUT    /api/resources.php                    - Update resource
 *     DELETE /api/resources.php?id={id}           - Delete resource
 * 
 *   Comments:
 *     GET    /api/resources.php?resource_id={id}&action=comments  - Get comments for resource
 *     POST   /api/resources.php?action=comment                    - Create new comment
 *     DELETE /api/resources.php?comment_id={id}&action=delete_comment - Delete comment
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? $_GET['id'] : null;
$resourceId = isset($_GET['resource_id']) ? $_GET['resource_id'] : null;
$commentId = isset($_GET['comment_id']) ? $_GET['comment_id'] : null;

function getAllResources($db) {
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $orderDir = isset($_GET['order']) ? $_GET['order'] : 'desc';

    $validColumns = ['title', 'created_at'];
    if (!in_array($sortBy, $validColumns)) {
        $sortBy = 'created_at';
    }
    
    $validOrders = ['asc', 'desc'];
    if (!in_array(strtolower($orderDir), $validOrders)) {
        $orderDir = 'desc';
    }

    $baseQuery = "SELECT * FROM resources";
    $params = [];
    
    if ($searchTerm) {
        $baseQuery = $baseQuery . " WHERE title LIKE ? OR description LIKE ?";
        $searchPattern = "%" . $searchTerm . "%";
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }
    
    $baseQuery = $baseQuery . " ORDER BY " . $sortBy . " " . $orderDir;

    $statement = $db->prepare($baseQuery);
    $statement->execute($params);
    $resultSet = $statement->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'data' => $resultSet]);
}

function getResourceById($db, $resourceId) {
    if (!is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
   
    $fetchQuery = "SELECT * FROM resources WHERE id = ?";
    $statement = $db->prepare($fetchQuery);
    $statement->execute([$resourceId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        sendResponse(['success' => true, 'data' => $row]);
    } else {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }
}

function createResource($db, $data) {
    $requiredFields = ['title', 'link'];
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (count($missing) > 0) {
        sendResponse(['success' => false, 'message' => 'Missing fields', 'missing' => $missing], 400);
    }

    $resTitle = trim($data['title']);
    $resDesc = isset($data['description']) ? trim($data['description']) : '';
    $resLink = trim($data['link']);

    if (filter_var($resLink, FILTER_VALIDATE_URL) === false) {
        sendResponse(['success' => false, 'message' => 'Invalid URL'], 400);
    }

    $insertQuery = "INSERT INTO resources (title, description, link) VALUES (?, ?, ?)";
    $statement = $db->prepare($insertQuery);
    $result = $statement->execute([$resTitle, $resDesc, $resLink]);
    
    if ($result) {
        $newId = $db->lastInsertId();
        sendResponse(['success' => true, 'message' => 'Resource created', 'id' => $newId], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create resource'], 500);
    }
}

function updateResource($db, $data) {
    if (empty($data['id'])) {
        sendResponse(['success' => false, 'message' => 'Resource ID required'], 400);
    }
    
    $resId = $data['id'];

    $checkQuery = "SELECT id FROM resources WHERE id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$resId]);
    
    if (!$checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }

    $updateCols = [];
    $updateVals = [];
    
    if (!empty($data['title'])) {
        $updateCols[] = "title = ?";
        $updateVals[] = trim($data['title']);
    }
    
    if (!empty($data['description'])) {
        $updateCols[] = "description = ?";
        $updateVals[] = trim($data['description']);
    }
    
    if (!empty($data['link'])) {
        if (filter_var($data['link'], FILTER_VALIDATE_URL) === false) {
            sendResponse(['success' => false, 'message' => 'Invalid URL'], 400);
        }
        $updateCols[] = "link = ?";
        $updateVals[] = trim($data['link']);
    }
    
    if (count($updateCols) === 0) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }

    $updateQuery = "UPDATE resources SET " . implode(", ", $updateCols) . " WHERE id = ?";
    $updateVals[] = $resId;
    
    $updateStmt = $db->prepare($updateQuery);
    
    if ($updateStmt->execute($updateVals)) {
        sendResponse(['success' => true, 'message' => 'Resource updated']);
    } else {
        sendResponse(['success' => false, 'message' => 'Update failed'], 500);
    }
}

function deleteResource($db, $resourceId) {
    if (!is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }

    $verifyQuery = "SELECT id FROM resources WHERE id = ?";
    $verifyStmt = $db->prepare($verifyQuery);
    $verifyStmt->execute([$resourceId]);
    
    if (!$verifyStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }

    try {
        $db->beginTransaction();
        
        $deleteCommentsQuery = "DELETE FROM comments_resource WHERE resource_id = ?";
        $deleteCommentsStmt = $db->prepare($deleteCommentsQuery);
        $deleteCommentsStmt->execute([$resourceId]);

        $deleteResourceQuery = "DELETE FROM resources WHERE id = ?";
        $deleteResourceStmt = $db->prepare($deleteResourceQuery);
        $deleteResourceStmt->execute([$resourceId]);

        $db->commit();
        sendResponse(['success' => true, 'message' => 'Resource deleted']);
    } catch (Exception $error) {
        $db->rollBack();
        sendResponse(['success' => false, 'message' => 'Delete failed'], 500);
    }
}

function getCommentsByResourceId($db, $resourceId) {
    if (!is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
    
    $fetchQuery = "SELECT * FROM comments_resource WHERE resource_id = ? ORDER BY created_at ASC";
    $statement = $db->prepare($fetchQuery);
    $statement->execute([$resourceId]);
    $commentsList = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(['success' => true, 'data' => $commentsList]);
}

function createComment($db, $data) {
    $requiredFields = ['resource_id', 'author', 'text'];
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (count($missing) > 0) {
        sendResponse(['success' => false, 'message' => 'Missing fields', 'missing' => $missing], 400);
    }
    
    if (!is_numeric($data['resource_id'])) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }

    $checkQuery = "SELECT id FROM resources WHERE id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$data['resource_id']]);
    
    if (!$checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }

    $commentAuthor = trim($data['author']);
    $commentText = trim($data['text']);

    $insertQuery = "INSERT INTO comments_resource (resource_id, author, text) VALUES (?, ?, ?)";
    $insertStmt = $db->prepare($insertQuery);
    
    if ($insertStmt->execute([$data['resource_id'], $commentAuthor, $commentText])) {
        $newCommentId = $db->lastInsertId();
        sendResponse(['success' => true, 'message' => 'Comment created', 'id' => $newCommentId], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create comment'], 500);
    }
}

function deleteComment($db, $commentId) {
    if (!is_numeric($commentId)) {
        sendResponse(['success' => false, 'message' => 'Invalid comment ID'], 400);
    }
    
    $verifyQuery = "SELECT id FROM comments_resource WHERE id = ?";
    $verifyStmt = $db->prepare($verifyQuery);
    $verifyStmt->execute([$commentId]);
    
    if (!$verifyStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Comment not found'], 404);
    }

    $deleteQuery = "DELETE FROM comments_resource WHERE id = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    
    if ($deleteStmt->execute([$commentId])) {
        sendResponse(['success' => true, 'message' => 'Comment deleted']);
    } else {
        sendResponse(['success' => false, 'message' => 'Delete failed'], 500);
    }
}

try {
    if ($method === 'GET') {
        if ($action === 'comments' && $resourceId) {
            getCommentsByResourceId($db, $resourceId);
        } elseif ($id) {
            getResourceById($db, $id);
        } else {
            getAllResources($db);
        }
    } elseif ($method === 'POST') {
        if ($action === 'comment') {
            createComment($db, $data);
        } else {
            createResource($db, $data);
        }
    } elseif ($method === 'PUT') {
        updateResource($db, $data);
    } elseif ($method === 'DELETE') {
        if ($action === 'delete_comment' && $commentId) {
            deleteComment($db, $commentId);
        } else {
            $targetId = $id;
            if (!$targetId && isset($data['id'])) {
                $targetId = $data['id'];
            }
            if ($targetId) {
                deleteResource($db, $targetId);
            } else {
                sendResponse(['success' => false, 'message' => 'Resource ID required'], 400);
            }
        }
    } else {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (PDOException $dbError) {
    error_log($dbError->getMessage());
    sendResponse(['success' => false, 'message' => 'Database error'], 500);
} catch (Exception $generalError) {
    error_log($generalError->getMessage());
    sendResponse(['success' => false, 'message' => 'Server error'], 500);
}


function sendResponse($responseData, $httpCode = 200) {
    http_response_code($httpCode);
    if (!is_array($responseData)) {
        $responseData = ['data' => $responseData];
    }
    echo json_encode($responseData);
    exit;
}

?>

