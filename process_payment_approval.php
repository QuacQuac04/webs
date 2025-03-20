<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['proofId']) || !isset($data['action'])) {
        throw new Exception('Missing required data');
    }

    $proofId = $data['proofId'];
    $action = $data['action'];
    $note = $data['note'] ?? '';

    $conn->beginTransaction();

    // Lấy thông tin proof và purchase
    $stmt = $conn->prepare("
        SELECT pp.*, p.UserID, p.TemplateID, p.TransactionID, p.Amount
        FROM payment_proofs pp
        JOIN purchases p ON pp.PurchaseID = p.PurchaseID
        WHERE pp.ProofID = ?
    ");
    $stmt->execute([$proofId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        throw new Exception('Payment proof not found');
    }

    // Cập nhật trạng thái proof
    $stmt = $conn->prepare("
        UPDATE payment_proofs 
        SET Status = ?, AdminNote = ?, SubmitDate = CURRENT_TIMESTAMP
        WHERE ProofID = ?
    ");
    $stmt->execute([
        $action === 'approve' ? 'approved' : 'rejected',
        $note,
        $proofId
    ]);

    if ($action === 'approve') {
        // Kiểm tra xem người dùng đã mua template này chưa
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM purchases 
            WHERE UserID = ? AND TemplateID = ? AND Status = 'completed'
        ");
        $stmt->execute([$payment['UserID'], $payment['TemplateID']]);
        $existingPurchase = $stmt->fetch();

        if ($existingPurchase['count'] > 0) {
            throw new Exception('User has already purchased this template');
        }

        // Cập nhật trạng thái purchase
        $stmt = $conn->prepare("
            UPDATE purchases 
            SET Status = 'completed', UpdatedAt = CURRENT_TIMESTAMP
            WHERE PurchaseID = ?
        ");
        $stmt->execute([$payment['PurchaseID']]);

        // Thêm vào billing_history
        $stmt = $conn->prepare("
            INSERT INTO billing_history (UserID, Amount, Description, Status)
            VALUES (?, ?, ?, 'Completed')
        ");
        $stmt->execute([
            $payment['UserID'],
            $payment['Amount'],
            'Payment approved for transaction: ' . $payment['TransactionID']
        ]);
    }

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => $action === 'approve' ? 'Payment approved successfully' : 'Payment rejected'
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 