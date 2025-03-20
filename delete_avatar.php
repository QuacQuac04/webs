<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET Avatar = 'images/default-avatar.png' WHERE UserID = ?");
$result = $stmt->execute([$_SESSION['UserID']]);

echo json_encode(['success' => $result]); 