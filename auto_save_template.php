<?php
require_once 'connect_db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Nhận dữ liệu JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $userID = $_SESSION['UserID'];
    $content = $data['content'];
    $styles = json_encode($data['styles']);
    $name = $data['name'];
    $lastModified = $data['lastModified'];

    // Kiểm tra xem template đã tồn tại chưa
    $stmt = $pdo->prepare("SELECT TemplateID FROM templates WHERE UserID = ? AND TemplateName = ?");
    $stmt->execute([$userID, $name]);
    $template = $stmt->fetch();

    if ($template) {
        // Cập nhật template hiện có
        $stmt = $pdo->prepare("
            UPDATE templates 
            SET HTMLContent = ?, 
                Styles = ?,
                LastModified = ?
            WHERE TemplateID = ?
        ");
        $stmt->execute([$content, $styles, $lastModified, $template['TemplateID']]);
    } else {
        // Tạo template mới
        $stmt = $pdo->prepare("
            INSERT INTO templates (UserID, TemplateName, HTMLContent, Styles, CreatedDate, LastModified)
            VALUES (?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$userID, $name, $content, $styles, $lastModified]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 