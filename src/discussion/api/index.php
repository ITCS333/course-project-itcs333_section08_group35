<?php

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'Database.php';

// Initialize session data if not exists
if (!isset($_SESSION['user_data'])) {
    $_SESSION['user_data'] = array();
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$resource = isset($_GET['resource']) ? $_GET['resource'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';
$topicId = isset($_GET['topic_id']) ? $_GET['topic_id'] : '';

function getAllTopics($db) {
    $sql = "SELECT topic_id, subject, message, author, 
            DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at 
            FROM topics";
    
    $params = array();
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $_GET['search'];
        $sql .= " WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
    
    $allowedSort = ['subject', 'author', 'created_at'];
    $sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort) ? $_GET['sort'] : 'created_at';
    
    $allowedOrder = ['asc', 'desc'];
    $order = isset($_GET['order']) && in_array(strtolower($_GET['order']), $allowedOrder) ? $_GET['order'] : 'DESC';
    
    $sql .= " ORDER BY $sort $order";
    
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse([
        'success' => true,
        'data' => $topics,
        'count' => count($topics)
    ], 200);
}

function getTopicById($db, $topicId) {
    if (empty($topicId)) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID is required'
        ], 400);
        return;
    }
    
    $sql = "SELECT topic_id, subject, message, author, 
            DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at 
            FROM topics 
            WHERE topic_id = :topic_id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':topic_id', $topicId);
    $stmt->execute();
    
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($topic) {
        sendResponse([
            'success' => true,
            'data' => $topic
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Topic not found'
        ], 404);
    }
}

function createTopic($db, $data) {
    if (empty($data['topic_id']) || empty($data['subject']) || 
        empty($data['message']) || empty($data['author'])) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields: topic_id, subject, message, author'
        ], 400);
        return;
    }
    
    $topicId = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = sanitizeInput($data['author']);
    
    $checkSql = "SELECT topic_id FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':topic_id', $topicId);
    $checkStmt->execute();
    
    if ($checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID already exists'
        ], 409);
        return;
    }
    
    $sql = "INSERT INTO topics (topic_id, subject, message, author) 
            VALUES (:topic_id, :subject, :message, :author)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':topic_id', $topicId);
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':author', $author);
    
    if ($stmt->execute()) {
        sendResponse([
            'success' => true,
            'message' => 'Topic created successfully',
            'topic_id' => $topicId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create topic'
        ], 500);
    }
}

function updateTopic($db, $data) {
    if (empty($data['topic_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID is required'
        ], 400);
        return;
    }
    
    $topicId = sanitizeInput($data['topic_id']);
    
    $checkSql = "SELECT topic_id FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':topic_id', $topicId);
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Topic not found'
        ], 404);
        return;
    }
    
    $updates = array();
    $params = array(':topic_id' => $topicId);
    
    if (isset($data['subject']) && !empty($data['subject'])) {
        $updates[] = "subject = :subject";
        $params[':subject'] = sanitizeInput($data['subject']);
    }
    
    if (isset($data['message']) && !empty($data['message'])) {
        $updates[] = "message = :message";
        $params[':message'] = sanitizeInput($data['message']);
    }
    
    if (empty($updates)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields to update'
        ], 400);
        return;
    }
    
    $sql = "UPDATE topics SET " . implode(', ', $updates) . " WHERE topic_id = :topic_id";
    
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        sendResponse([
            'success' => true,
            'message' => 'Topic updated successfully'
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update topic'
        ], 500);
    }
}

function deleteTopic($db, $topicId) {
    if (empty($topicId)) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID is required'
        ], 400);
        return;
    }
    
    $checkSql = "SELECT topic_id FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':topic_id', $topicId);
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Topic not found'
        ], 404);
        return;
    }
    
    $deleteRepliesSql = "DELETE FROM replies WHERE topic_id = :topic_id";
    $deleteRepliesStmt = $db->prepare($deleteRepliesSql);
    $deleteRepliesStmt->bindParam(':topic_id', $topicId);
    $deleteRepliesStmt->execute();
    
    $deleteTopicSql = "DELETE FROM topics WHERE topic_id = :topic_id";
    $deleteTopicStmt = $db->prepare($deleteTopicSql);
    $deleteTopicStmt->bindParam(':topic_id', $topicId);
    
    if ($deleteTopicStmt->execute()) {
        sendResponse([
            'success' => true,
            'message' => 'Topic deleted successfully'
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete topic'
        ], 500);
    }
}

function getRepliesByTopicId($db, $topicId) {
    if (empty($topicId)) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID is required'
        ], 400);
        return;
    }
    
    $sql = "SELECT reply_id, topic_id, text, author, 
            DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at 
            FROM replies 
            WHERE topic_id = :topic_id 
            ORDER BY created_at ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':topic_id', $topicId);
    $stmt->execute();
    
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse([
        'success' => true,
        'data' => $replies,
        'count' => count($replies)
    ], 200);
}

function createReply($db, $data) {
    if (empty($data['reply_id']) || empty($data['topic_id']) || 
        empty($data['text']) || empty($data['author'])) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields: reply_id, topic_id, text, author'
        ], 400);
        return;
    }
    
    $replyId = sanitizeInput($data['reply_id']);
    $topicId = sanitizeInput($data['topic_id']);
    $text = sanitizeInput($data['text']);
    $author = sanitizeInput($data['author']);
    
    $checkTopicSql = "SELECT topic_id FROM topics WHERE topic_id = :topic_id";
    $checkTopicStmt = $db->prepare($checkTopicSql);
    $checkTopicStmt->bindParam(':topic_id', $topicId);
    $checkTopicStmt->execute();
    
    if (!$checkTopicStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Parent topic does not exist'
        ], 404);
        return;
    }
    
    $checkReplySql = "SELECT reply_id FROM replies WHERE reply_id = :reply_id";
    $checkReplyStmt = $db->prepare($checkReplySql);
    $checkReplyStmt->bindParam(':reply_id', $replyId);
    $checkReplyStmt->execute();
    
    if ($checkReplyStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Reply ID already exists'
        ], 409);
        return;
    }
    
    $sql = "INSERT INTO replies (reply_id, topic_id, text, author) 
            VALUES (:reply_id, :topic_id, :text, :author)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':reply_id', $replyId);
    $stmt->bindParam(':topic_id', $topicId);
    $stmt->bindParam(':text', $text);
    $stmt->bindParam(':author', $author);
    
    if ($stmt->execute()) {
        sendResponse([
            'success' => true,
            'message' => 'Reply created successfully',
            'reply_id' => $replyId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create reply'
        ], 500);
    }
}

function deleteReply($db, $replyId) {
    if (empty($replyId)) {
        sendResponse([
            'success' => false,
            'message' => 'Reply ID is required'
        ], 400);
        return;
    }
    
    $checkSql = "SELECT reply_id FROM replies WHERE reply_id = :reply_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':reply_id', $replyId);
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Reply not found'
        ], 404);
        return;
    }
    
    $sql = "DELETE FROM replies WHERE reply_id = :reply_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':reply_id', $replyId);
    
    if ($stmt->execute()) {
        sendResponse([
            'success' => true,
            'message' => 'Reply deleted successfully'
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete reply'
        ], 500);
    }
}

try {
    if (!isValidResource($resource)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource. Use: topics or replies'
        ], 400);
    }
    
    if ($resource === 'topics') {
        switch ($method) {
            case 'GET':
                if (!empty($id)) {
                    getTopicById($db, $id);
                } else {
                    getAllTopics($db);
                }
                break;
                
            case 'POST':
                createTopic($db, $data);
                break;
                
            case 'PUT':
                updateTopic($db, $data);
                break;
                
            case 'DELETE':
                $deleteId = !empty($id) ? $id : (isset($data['topic_id']) ? $data['topic_id'] : '');
                deleteTopic($db, $deleteId);
                break;
                
            default:
                sendResponse([
                    'success' => false,
                    'message' => 'Method not allowed'
                ], 405);
        }
    }
    
    if ($resource === 'replies') {
        switch ($method) {
            case 'GET':
                getRepliesByTopicId($db, $topicId);
                break;
                
            case 'POST':
                createReply($db, $data);
                break;
                
            case 'DELETE':
                $deleteId = !empty($id) ? $id : (isset($data['reply_id']) ? $data['reply_id'] : '');
                deleteReply($db, $deleteId);
                break;
                
            default:
                sendResponse([
                    'success' => false,
                    'message' => 'Method not allowed'
                ], 405);
        }
    }
    
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    
    sendResponse([
        'success' => false,
        'message' => 'Database error occurred'
    ], 500);
    
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    
    sendResponse([
        'success' => false,
        'message' => 'An error occurred'
    ], 500);
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

function sanitizeInput($data) {
    if (!is_string($data)) {
        return $data;
    }
    
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

function isValidResource($resource) {
    $allowedResources = ['topics', 'replies'];
    return in_array($resource, $allowedResources);
}

?>