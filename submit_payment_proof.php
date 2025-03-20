<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

try {
    if (!isset($_FILES['payment_proof']) || !isset($_POST['transaction_id'])) {
        throw new Exception('Missing required data');
    }

    $transactionID = $_POST['transaction_id'];
    
    // Kiểm tra giao dịch
    $stmt = $conn->prepare("SELECT PurchaseID FROM purchases WHERE TransactionID = ? AND UserID = ?");
    $stmt->execute([$transactionID, $_SESSION['UserID']]);
    $purchase = $stmt->fetch();

    if (!$purchase) {
        throw new Exception('Invalid transaction');
    }

    // Xử lý upload ảnh
    $targetDir = "uploads/payment_proofs/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . '_' . basename($_FILES["payment_proof"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["payment_proof"]["tmp_name"], $targetFile)) {
        // Lưu thông tin vào database
        $stmt = $conn->prepare("INSERT INTO payment_proofs (PurchaseID, ImageProof) VALUES (?, ?)");
        $stmt->execute([$purchase['PurchaseID'], $targetFile]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Đã gửi xác nhận thanh toán'
        ]);
    } else {
        throw new Exception('Failed to upload file');
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 