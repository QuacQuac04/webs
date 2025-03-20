<?php
require_once 'connect_db.php';
require_once 'MBBankAPI.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$transactionID = $_GET['transaction_id'] ?? '';
$userID = $_SESSION['UserID'];

try {
    error_log("Starting payment check for transaction: " . $transactionID);

    // Kiểm tra giao dịch trong database
    $stmt = $conn->prepare("SELECT p.*, t.TemplateName, t.Price 
                           FROM purchases p 
                           JOIN templates t ON p.TemplateID = t.TemplateID 
                           WHERE p.TransactionID = ? AND p.UserID = ?");
    $stmt->execute([$transactionID, $userID]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        error_log("Purchase not found");
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy giao dịch']);
        exit;
    }

    // Nếu đã completed thì trả về luôn
    if ($purchase['Status'] === 'completed') {
        echo json_encode([
            'status' => 'completed',
            'message' => 'Thanh toán thành công',
            'template' => [
                'id' => $purchase['TemplateID'],
                'name' => $purchase['TemplateName']
            ]
        ]);
        exit;
    }

    // Kiểm tra với MBBank API
    $mbbank = new MBBankAPI();
    $result = $mbbank->checkTransaction($transactionID, $purchase['Price']);
    error_log("MBBank check result: " . json_encode($result));

    if ($result['status']) {
        // Bắt đầu transaction
        $conn->beginTransaction();

        try {
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
                'Thanh toán template: ' . $purchase['TemplateName']
            ]);

            $conn->commit();

            echo json_encode([
                'status' => 'completed',
                'message' => 'Thanh toán thành công',
                'template' => [
                    'id' => $purchase['TemplateID'],
                    'name' => $purchase['TemplateName']
                ]
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    } else {
        echo json_encode([
            'status' => 'pending',
            'message' => $result['message']
        ]);
    }

} catch (Exception $e) {
    error_log("Payment check error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Đang kiểm tra giao dịch...'
    ]);
} 