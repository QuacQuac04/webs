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
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_payment':
            $cardType = $_POST['card_type'];
            $cardNumber = $_POST['card_number'];
            $expiryDate = $_POST['expiry_date'];
            
            // Lưu chỉ 4 số cuối của thẻ
            $lastFourDigits = substr($cardNumber, -4);
            
            $stmt = $conn->prepare("INSERT INTO payment_methods (UserID, CardType, LastFourDigits, ExpiryDate) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userID, $cardType, $lastFourDigits, $expiryDate]);
            
            $response['message'] = 'Đã thêm phương thức thanh toán mới';
            break;

        case 'delete_payment':
            $paymentID = $_POST['payment_id'];
            
            $stmt = $conn->prepare("DELETE FROM payment_methods WHERE PaymentID = ? AND UserID = ?");
            $stmt->execute([$paymentID, $userID]);
            
            $response['message'] = 'Đã xóa phương thức thanh toán';
            break;

        case 'upgrade_plan':
            $newPlan = $_POST['plan'];
            
            $stmt = $conn->prepare("UPDATE users SET CurrentPlan = ? WHERE UserID = ?");
            $stmt->execute([$newPlan, $userID]);
            
            // Ghi lại lịch sử thanh toán
            $amount = ($newPlan == 'Premium') ? 199000 : 0;
            $stmt = $conn->prepare("INSERT INTO billing_history (UserID, Amount, Description, Status) VALUES (?, ?, ?, 'Success')");
            $stmt->execute([$userID, $amount, "Nâng cấp lên gói $newPlan"]);
            
            $response['message'] = 'Đã nâng cấp gói dịch vụ';
            break;

        default:
            throw new Exception('Hành động không hợp lệ');
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response); 