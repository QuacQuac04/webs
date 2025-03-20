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
    
    // Lấy tất cả các cài đặt thông báo
    $notificationSettings = [
        'email' => [
            'news' => isset($_POST['email_news']),
            'security' => isset($_POST['email_security']),
            'marketing' => isset($_POST['email_marketing'])
        ],
        'push' => [
            'all' => isset($_POST['push_all']),
            'messages' => isset($_POST['push_messages'])
        ]
    ];

    // Lưu cài đặt dưới dạng JSON
    $stmt = $conn->prepare("UPDATE users SET NotificationSettings = ? WHERE UserID = ?");
    $stmt->execute([json_encode($notificationSettings), $userID]);

    $response['success'] = true;
    $response['message'] = 'Đã cập nhật cài đặt thông báo';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response); 