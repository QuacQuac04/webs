<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$transactionID = $_POST['transaction_id'] ?? '';
$userID = $_SESSION['UserID'];

try {
    // Kiểm tra giao dịch
    $stmt = $conn->prepare("SELECT p.*, t.Price 
                           FROM purchases p 
                           JOIN templates t ON p.TemplateID = t.TemplateID 
                           WHERE p.TransactionID = ? AND p.UserID = ? AND p.Status = 'pending'");
    $stmt->execute([$transactionID, $userID]);
    $purchase = $stmt->fetch();

    if (!$purchase) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Không tìm thấy giao dịch hoặc giao dịch đã hoàn tất'
        ]);
        exit;
    }

    // TODO: Ở đây sẽ tích hợp API ngân hàng để kiểm tra giao dịch
    // Giả sử đã kiểm tra với ngân hàng và thanh toán thành công

    // Cập nhật trạng thái thanh toán
    $stmt = $conn->prepare("UPDATE purchases 
                           SET Status = 'completed', 
                               UpdatedAt = CURRENT_TIMESTAMP 
                           WHERE TransactionID = ? AND UserID = ?");
    $stmt->execute([$transactionID, $userID]);

    // Thêm vào billing_history
    $stmt = $conn->prepare("INSERT INTO billing_history 
                           (UserID, Amount, Description, Status) 
                           VALUES (?, ?, ?, 'Completed')");
    $stmt->execute([
        $userID,
        $purchase['Price'],
        'Thanh toán template ID: ' . $purchase['TemplateID']
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Xác nhận thanh toán thành công'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
} 