<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $bio = $_POST['bio'] ?? '';

    // Kiểm tra username và email có bị trùng không
    $check = $conn->prepare("SELECT UserID FROM users WHERE (Username = ? OR Email = ?) AND UserID != ?");
    $check->execute([$username, $email, $_SESSION['UserID']]);
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username hoặc email đã tồn tại']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET Username = ?, Email = ?, Bio = ? WHERE UserID = ?");
    $result = $stmt->execute([$username, $email, $bio, $_SESSION['UserID']]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cập nhật thất bại']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
} 