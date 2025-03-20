<?php
session_start();
header('Content-Type: application/json');

if(isset($_SESSION['user_id'])) {
    // Kết nối database và lấy thông tin user
    require_once 'connect_db.php';
    
    try {
        $stmt = $conn->prepare("SELECT Username, Email FROM users WHERE UserID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'loggedIn' => true,
            'username' => $user['Username'],
            'avatar' => 'images/avatars/' . $user['UserID'] . '.jpg', // Giả sử avatar được lưu theo UserID
        ]);
    } catch(PDOException $e) {
        echo json_encode([
            'loggedIn' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['loggedIn' => false]);
} 