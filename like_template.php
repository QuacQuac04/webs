<?php
// Đặt header ngay đầu file, trước khi có bất kỳ output nào
header('Content-Type: application/json');

require_once 'connect_db.php';
session_start();

// Enable error reporting nhưng không hiển thị trên browser
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

// Log request data
error_log("Request received: " . print_r($_POST, true));

if (!isset($_SESSION['UserID'])) {
    error_log("No user logged in");
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_id'])) {
    $templateId = (int)$_POST['template_id'];
    $userId = (int)$_SESSION['UserID'];
    
    error_log("Processing like - UserID: $userId, TemplateID: $templateId");
    
    try {
        $conn->beginTransaction();
        
        // Kiểm tra xem đã like chưa
        $stmt = $conn->prepare("SELECT COUNT(*) FROM template_likes WHERE UserID = ? AND TemplateID = ?");
        $stmt->execute([$userId, $templateId]);
        $exists = $stmt->fetchColumn();
        
        error_log("Existing like check result: " . ($exists ? 'Yes' : 'No'));
        
        if ($exists) {
            // Unlike
            $stmt = $conn->prepare("DELETE FROM template_likes WHERE UserID = ? AND TemplateID = ?");
            $stmt->execute([$userId, $templateId]);
            
            $stmt = $conn->prepare("UPDATE templates SET Likes = GREATEST(Likes - 1, 0) WHERE TemplateID = ?");
            $stmt->execute([$templateId]);
            
            $action = 'unliked';
        } else {
            // Like
            $stmt = $conn->prepare("INSERT INTO template_likes (UserID, TemplateID) VALUES (?, ?)");
            $stmt->execute([$userId, $templateId]);
            
            $stmt = $conn->prepare("UPDATE templates SET Likes = COALESCE(Likes, 0) + 1 WHERE TemplateID = ?");
            $stmt->execute([$templateId]);
            
            $action = 'liked';
        }
        
        // Lấy số like mới
        $stmt = $conn->prepare("SELECT COALESCE(Likes, 0) as likes FROM templates WHERE TemplateID = ?");
        $stmt->execute([$templateId]);
        $likes = $stmt->fetchColumn();
        
        error_log("New like count: $likes");
        
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'action' => $action,
            'likes' => $likes
        ]);
        
    } catch(PDOException $e) {
        $conn->rollBack();
        error_log("Database error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    error_log("Invalid request");
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}
exit;
?> 