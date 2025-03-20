<?php
require_once 'connect_db.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $conn->prepare("INSERT INTO templates (UserID, TemplateName, Description, Content, Styles) VALUES (?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $data['userID'],
        $data['name'],
        $data['description'] ?? '',
        $data['content'],
        json_encode($data['styles'])
    ]);

    echo json_encode(['success' => true, 'message' => 'Template saved successfully']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 