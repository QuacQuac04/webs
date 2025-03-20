<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $templateName = $_POST['template_name'];
        $description = $_POST['description'];
        $category = $_POST['category'];
        $userID = $_SESSION['UserID'];
        $status = $_SESSION['is_admin'] ? 'Approved' : 'Pending';

        // Xử lý upload preview image
        $previewImage = '';
        if (isset($_FILES['preview_image'])) {
            $targetDir = "uploads/previews/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $imageFileType = strtolower(pathinfo($_FILES["preview_image"]["name"], PATHINFO_EXTENSION));
            $previewImage = $targetDir . uniqid() . '.' . $imageFileType;
            
            move_uploaded_file($_FILES["preview_image"]["tmp_name"], $previewImage);
        }

        // Xử lý upload HTML file
        $htmlContent = '';
        if (isset($_FILES['html_file'])) {
            $htmlContent = file_get_contents($_FILES['html_file']['tmp_name']);
        }

        // Thêm template vào database
        $stmt = $conn->prepare("INSERT INTO templates (UserID, TemplateName, Description, PreviewImage, Category, Status, HTMLContent) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $userID,
            $templateName,
            $description,
            $previewImage,
            $category,
            $status,
            $htmlContent
        ]);

        header('Location: admin.php?message=Template uploaded successfully');
    } catch(PDOException $e) {
        header('Location: admin.php?error=' . urlencode($e->getMessage()));
    }
}
?> 