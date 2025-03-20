<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit();
}

$userID = $_SESSION['UserID'];
$templateID = $_GET['template_id'] ?? null;

try {
    // Kiểm tra quyền truy cập
    $stmt = $conn->prepare("SELECT t.*, p.Status 
                           FROM templates t 
                           JOIN purchases p ON t.TemplateID = p.TemplateID 
                           WHERE t.TemplateID = ? AND p.UserID = ? AND p.Status = 'completed'");
    $stmt->execute([$templateID, $userID]);
    $template = $stmt->fetch();

    if (!$template) {
        die('Bạn không có quyền truy cập template này');
    }

    // Tạo ZIP file chứa source code
    $zipname = 'template_' . $templateID . '.zip';
    $zip = new ZipArchive();
    $zip->open($zipname, ZipArchive::CREATE);
    
    // Thêm các file vào ZIP
    $zip->addFromString('index.html', $template['HTMLContent']);
    $zip->addFromString('style.css', $template['CSSContent']);
    $zip->addFromString('script.js', $template['JSContent']);
    
    $zip->close();

    // Gửi file cho user download
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$zipname.'"');
    header('Content-Length: ' . filesize($zipname));
    readfile($zipname);
    
    // Xóa file zip sau khi download
    unlink($zipname);

} catch (Exception $e) {
    die('Có lỗi xảy ra: ' . $e->getMessage());
} 