<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    $userID = $_SESSION['UserID'];
    
    // Lấy cài đặt giao diện
    $appearanceSettings = [
        'theme' => $_POST['theme'],
        'font_size' => $_POST['font_size']
    ];

    // Lưu cài đặt
    $stmt = $conn->prepare("UPDATE users SET AppearanceSettings = ? WHERE UserID = ?");
    $stmt->execute([json_encode($appearanceSettings), $userID]);

    $response['success'] = true;
    $response['message'] = 'Đã cập nhật giao diện';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response); 