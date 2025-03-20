<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

if (isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    
    // Kiểm tra lỗi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Lỗi upload file']);
        exit;
    }

    // Kiểm tra loại file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)']);
        exit;
    }

    // Tạo thư mục nếu chưa tồn tại
    $upload_dir = 'uploads/avatars';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $file['name']);
    $uploadPath = $upload_dir . '/' . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        try {
            $stmt = $conn->prepare("UPDATE users SET Avatar = ? WHERE UserID = ?");
            $stmt->execute([$uploadPath, $_SESSION['UserID']]);
            
            echo json_encode([
                'success' => true,
                'avatar_url' => $uploadPath,
                'message' => 'Upload ảnh thành công'
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật database']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể lưu file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Không có file được upload']);
} 