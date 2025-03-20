<?php
session_start();
require_once 'connect_db.php';

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    exit('Invalid data');
}

try {
    $stmt = $conn->prepare("
        INSERT INTO design_webs_ai 
        (UserID, UserMessage, AIResponse, HTMLCode, CSSCode) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $_SESSION['UserID'],
        $data['userMessage'],
        $data['aiResponse'],
        $data['htmlCode'],
        $data['cssCode']
    ]);
    
    echo json_encode(['success' => true, 'chatId' => $conn->lastInsertId()]);
} catch(PDOException $e) {
    http_response_code(500);
    error_log("Lỗi lưu chat: " . $e->getMessage());
    echo json_encode(['error' => 'Lỗi lưu chat']);
}
?> 