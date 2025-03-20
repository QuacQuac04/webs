<?php
require_once 'connect_db.php';
session_start();

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.Username as author 
        FROM templates t 
        JOIN users u ON t.UserID = u.UserID 
        ORDER BY t.CreatedDate DESC
    ");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($templates);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 