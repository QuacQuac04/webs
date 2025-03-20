<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập']);
    exit();
}

$userID = $_SESSION['UserID'];
$templateId = $_POST['templateId'] ?? null;
$cardNumber = $_POST['cardNumber'] ?? '';
$expiry = $_POST['expiry'] ?? '';
$cvv = $_POST['cvv'] ?? '';
$cardHolder = $_POST['cardHolder'] ?? '';
$saveCard = $_POST['saveCard'] ?? false;

try {
    // Bắt đầu transaction
    $conn->beginTransaction();

    // Lấy thông tin template
    $stmt = $conn->prepare("SELECT * FROM templates WHERE TemplateID = ?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();

    if (!$template) {
        throw new Exception('Template không tồn tại');
    }

    // Xử lý thanh toán (tích hợp với cổng thanh toán thực tế ở đây)
    
    // Lưu thông tin thẻ nếu người dùng chọn
    if ($saveCard) {
        $lastFourDigits = substr($cardNumber, -4);
        $expiryDate = date('Y-m-d', strtotime('01/'.$expiry));
        
        $stmt = $conn->prepare("INSERT INTO payment_methods (UserID, CardType, LastFourDigits, ExpiryDate) 
                               VALUES (?, 'Credit Card', ?, ?)");
        $stmt->execute([$userID, $lastFourDigits, $expiryDate]);
    }

    // Lưu lịch sử giao dịch
    $stmt = $conn->prepare("INSERT INTO purchases (UserID, TemplateID, Amount) VALUES (?, ?, ?)");
    $stmt->execute([$userID, $templateId, $template['Price']]);

    // Lưu lịch sử thanh toán
    $stmt = $conn->prepare("INSERT INTO billing_history (UserID, Amount, Description, Status) 
                           VALUES (?, ?, ?, 'Completed')");
    $stmt->execute([
        $userID, 
        $template['Price'],
        'Mua template: ' . $template['TemplateName']
    ]);

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Thanh toán thành công'
    ]);

} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>