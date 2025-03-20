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
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $twoFactor = isset($_POST['two_factor']) ? 1 : 0;

    // Kiểm tra mật khẩu hiện tại
    $stmt = $conn->prepare("SELECT Password FROM users WHERE UserID = ?");
    $stmt->execute([$userID]);
    $user = $stmt->fetch();

    if (!password_verify($currentPassword, $user['Password'])) {
        throw new Exception('Mật khẩu hiện tại không đúng');
    }

    // Kiểm tra mật khẩu mới
    if ($newPassword !== $confirmPassword) {
        throw new Exception('Mật khẩu mới không khớp');
    }

    if (strlen($newPassword) < 6) {
        throw new Exception('Mật khẩu mới phải có ít nhất 6 ký tự');
    }

    // Cập nhật mật khẩu và trạng thái 2FA
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET Password = ?, TwoFactorEnabled = ? WHERE UserID = ?");
    $stmt->execute([$hashedPassword, $twoFactor, $userID]);

    $response['success'] = true;
    $response['message'] = 'Cập nhật bảo mật thành công';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response); 