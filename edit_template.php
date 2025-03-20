<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: admin.php');
    exit();
}

try {
    $stmt = $conn->prepare("SELECT * FROM templates WHERE TemplateID = ?");
    $stmt->execute([$_GET['id']]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        header('Location: admin.php');
        exit();
    }
    
    if (isset($_POST['update_template'])) {
        try {
            $conn->beginTransaction();
            
            // Cập nhật thông tin cơ bản
            $stmt = $conn->prepare("UPDATE templates SET 
                                  TemplateName = ?, 
                                  Description = ?, 
                                  Category = ?, 
                                  Price = ? 
                                  WHERE TemplateID = ?");
            $stmt->execute([
                $_POST['template_name'],
                $_POST['description'],
                $_POST['category'],
                $_POST['price'],
                $_GET['id']
            ]);
            
            // Xử lý upload ảnh mới nếu có
            if ($_FILES['preview_image']['size'] > 0) {
                $targetDir = "uploads/templates/";
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                $previewImage = $targetDir . time() . '_' . basename($_FILES["preview_image"]["name"]);
                
                if (move_uploaded_file($_FILES["preview_image"]["tmp_name"], $previewImage)) {
                    // Xóa ảnh cũ nếu tồn tại
                    if (file_exists($template['PreviewImage'])) {
                        unlink($template['PreviewImage']);
                    }
                    
                    // Cập nhật đường dẫn ảnh mới
                    $stmt = $conn->prepare("UPDATE templates SET PreviewImage = ? WHERE TemplateID = ?");
                    $stmt->execute([$previewImage, $_GET['id']]);
                }
            }
            
            // Xử lý upload file HTML mới nếu có
            if ($_FILES['html_file']['size'] > 0) {
                $htmlContent = file_get_contents($_FILES["html_file"]["tmp_name"]);
                $stmt = $conn->prepare("UPDATE templates SET HTMLContent = ? WHERE TemplateID = ?");
                $stmt->execute([$htmlContent, $_GET['id']]);
            }
            
            // Xử lý upload file CSS mới nếu có
            if ($_FILES['css_file']['size'] > 0) {
                $cssContent = file_get_contents($_FILES["css_file"]["tmp_name"]);
                $stmt = $conn->prepare("UPDATE templates SET CSSContent = ? WHERE TemplateID = ?");
                $stmt->execute([$cssContent, $_GET['id']]);
            }

            // Xử lý upload file JS mới nếu có
            if ($_FILES['js_file']['size'] > 0) {
                $jsContent = file_get_contents($_FILES["js_file"]["tmp_name"]);
                $stmt = $conn->prepare("UPDATE templates SET JSContent = ? WHERE TemplateID = ?");
                $stmt->execute([$jsContent, $_GET['id']]);
            }
            
            $conn->commit();
            header('Location: admin.php?message=Template updated successfully!');
            exit();
            
        } catch(PDOException $e) {
            $conn->rollBack();
            $error = "Update failed: " . $e->getMessage();
        }
    }
    
} catch(PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Template</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f8f9fa; 
            padding: 40px; 
        }
        .edit-form {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        .preview-image {
            max-width: 200px;
            margin-top: 10px;
            border-radius: 8px;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="edit-form">
            <h2 class="mb-4">Edit Template</h2>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Template Name</label>
                    <input type="text" class="form-control" name="template_name" 
                           value="<?php echo htmlspecialchars($template['TemplateName']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4" required><?php 
                        echo htmlspecialchars($template['Description']); 
                    ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select class="form-control" name="category" required>
                        <option value="business" <?php echo $template['Category'] == 'business' ? 'selected' : ''; ?>>Business</option>
                        <option value="portfolio" <?php echo $template['Category'] == 'portfolio' ? 'selected' : ''; ?>>Portfolio</option>
                        <option value="ecommerce" <?php echo $template['Category'] == 'ecommerce' ? 'selected' : ''; ?>>E-commerce</option>
                        <option value="blog" <?php echo $template['Category'] == 'blog' ? 'selected' : ''; ?>>Blog</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input type="number" class="form-control" name="price" step="0.01" min="0" 
                           value="<?php echo htmlspecialchars($template['Price']); ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Preview Image</label>
                    <input type="file" class="form-control" name="preview_image" accept="image/*">
                    <?php if($template['PreviewImage']): ?>
                        <img src="<?php echo htmlspecialchars($template['PreviewImage']); ?>" 
                             class="preview-image" alt="Current preview">
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">HTML File</label>
                    <input type="file" class="form-control" name="html_file" accept=".html,.htm">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">CSS File</label>
                    <input type="file" class="form-control" name="css_file" accept=".css">
                    <?php if($template['CSSContent']): ?>
                        <small class="text-muted">File CSS hiện tại đã được tải lên</small>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">JavaScript File</label>
                    <input type="file" class="form-control" name="js_file" accept=".js">
                    <?php if($template['JSContent']): ?>
                        <small class="text-muted">File JavaScript hiện tại đã được tải lên</small>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="update_template" class="btn btn-primary">Update Template</button>
                    <a href="admin.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 