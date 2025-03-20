<?php
require_once 'connect_db.php';
session_start();

if (!isset($_GET['id'])) {
    die('Template ID not provided');
}

try {
    $stmt = $conn->prepare("SELECT * FROM templates WHERE TemplateID = ?");
    $stmt->execute([$_GET['id']]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        die('Template not found');
    }

    // Hiển thị nội dung HTML của template
    echo $template['HTMLContent'];

} catch(PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?> 