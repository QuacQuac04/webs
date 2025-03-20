<?php
require_once 'connect_db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateName = $_POST['templateName'] ?? '';
    $htmlContent = $_POST['htmlContent'] ?? '';
    $cssContent = $_POST['cssContent'] ?? '';
    $jsContent = $_POST['jsContent'] ?? '';
    
    try {
        $stmt = $conn->prepare("INSERT INTO templates (UserID, TemplateName, HTMLContent, CSSContent, JSContent, LastModified) 
                               VALUES (?, ?, ?, ?, ?, NOW())");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $templateName,
            $htmlContent,
            $cssContent,
            $jsContent
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Template đã được lưu thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}
?> 