<?php
require_once 'connect_db.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit();
}

// Lấy thông tin user
$userID = $_SESSION['UserID'];
$stmt = $conn->prepare("SELECT * FROM users WHERE UserID = ?");
$stmt->execute([$userID]);
$user = $stmt->fetch();

// Xử lý xóa template
if (isset($_POST['delete_template'])) {
    $templateId = $_POST['template_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM templates WHERE TemplateID = ? AND UserID = ?");
        $stmt->execute([$templateId, $userID]);
        header('Location: my-designs.php');
        exit();
    } catch(PDOException $e) {
        $error = "Lỗi khi xóa template";
    }
}

// Xử lý cập nhật template
if (isset($_POST['update_template'])) {
    $templateId = $_POST['template_id'];
    $templateName = $_POST['template_name'];
    $description = $_POST['description'];
    
    try {
        $stmt = $conn->prepare("UPDATE templates SET TemplateName = ?, Description = ?, LastModified = CURRENT_TIMESTAMP WHERE TemplateID = ? AND UserID = ?");
        $stmt->execute([$templateName, $description, $templateId, $userID]);
        header('Location: my-designs.php');
        exit();
    } catch(PDOException $e) {
        $error = "Lỗi khi cập nhật template";
    }
}

// Thêm CSS cho modal
echo "<style>
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
    }

    .modal-content {
        background: white;
        width: 90%;
        max-width: 600px;
        margin: 50px auto;
        padding: 20px;
        border-radius: 8px;
        position: relative;
    }

    .close-modal {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .form-group textarea {
        min-height: 100px;
    }

    .modal-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .modal-buttons button {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .save-btn {
        background: #0066ff;
        color: white;
    }

    .cancel-btn {
        background: #ddd;
    }
</style>";

// Thêm HTML cho modal
echo "<div id='editModal' class='modal'>
    <div class='modal-content'>
        <span class='close-modal'>&times;</span>
        <h2>Chỉnh sửa template</h2>
        <form id='editForm' method='POST'>
            <input type='hidden' name='template_id' id='edit_template_id'>
            <div class='form-group'>
                <label>Tên template</label>
                <input type='text' name='template_name' id='edit_template_name' required>
            </div>
            <div class='form-group'>
                <label>Mô tả</label>
                <textarea name='description' id='edit_description'></textarea>
            </div>
            <div class='modal-buttons'>
                <button type='button' class='cancel-btn' onclick='closeModal()'>Hủy</button>
                <button type='submit' name='update_template' class='save-btn'>Lưu</button>
            </div>
        </form>
    </div>
</div>";

// Thêm JavaScript
echo "<script>
    function openEditModal(templateId, templateName, description) {
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_template_id').value = templateId;
        document.getElementById('edit_template_name').value = templateName;
        document.getElementById('edit_description').value = description;
    }

    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function deleteTemplate(templateId) {
        if (confirm('Bạn có chắc muốn xóa template này?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type='hidden' name='template_id' value='${templateId}'>
                <input type='hidden' name='delete_template' value='1'>
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Đóng modal khi click bên ngoài
    window.onclick = function(event) {
        if (event.target == document.getElementById('editModal')) {
            closeModal();
        }
    }

    // Đóng modal khi click nút close
    document.querySelector('.close-modal').onclick = closeModal;
</script>";

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thiết kế của tôi - Webs</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playball&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .designs-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px 40px;
        }

        .designs-header {
            margin-bottom: 30px;
        }

        .designs-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .designs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .design-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .design-card:hover {
            transform: translateY(-4px);
        }

        .design-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .design-info {
            padding: 16px;
        }

        .design-title {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #1a1a1a;
        }

        .design-date {
            font-size: 14px;
            color: #666;
        }

        .design-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-edit {
            background: #0066ff;
            color: white;
        }

        .btn-delete {
            background: #ff3333;
            color: white;
        }

        .no-designs {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        /* Thêm CSS cho header */
        .main-header {
            background: white;
            padding: 16px 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-button {
            text-decoration: none;
            color: #0066ff;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: "Playball", cursive;
            font-weight: 700;
            font-style: normal;
            font-size: 2rem;
            color: #3b82f6;
        }

        .back-button:hover {
            color: #0052cc;
        }
        
    </style>
</head>
<body>
    <!-- Thêm header vào đây -->
    <header class="main-header">
        <div class="header-container">
            <a href="index.php" class="back-button">
                Webs
            </a>
        </div>
    </header>
    <div class="designs-container">
        <div class="designs-header">
            <h1>Thiết kế của tôi</h1>
        </div>
        
        <div class="designs-grid">
            <?php
            // Lấy danh sách templates của user
            $stmt = $conn->prepare("SELECT * FROM templates WHERE UserID = ? ORDER BY CreatedDate DESC");
            $stmt->execute([$userID]);
            $designs = $stmt->fetchAll();

            if (count($designs) > 0) {
                foreach ($designs as $design) {
                    ?>
                    <div class="design-card">
                        <img src="<?php echo htmlspecialchars($design['PreviewImage'] ?? 'images/default-preview.png'); ?>" alt="Preview" class="design-preview">
                        <div class="design-info">
                            <h3 class="design-title"><?php echo htmlspecialchars($design['TemplateName']); ?></h3>
                            <div class="design-date">
                                <?php echo date('d/m/Y', strtotime($design['CreatedDate'])); ?>
                            </div>
                            <div class="design-actions">
                                <button onclick="openEditModal(
                                    <?php echo $design['TemplateID']; ?>, 
                                    '<?php echo addslashes($design['TemplateName']); ?>', 
                                    '<?php echo addslashes($design['Description'] ?? ''); ?>'
                                )" class="btn btn-edit">Chỉnh sửa</button>
                                <button onclick="deleteTemplate(<?php echo $design['TemplateID']; ?>)" 
                                        class="btn btn-delete">Xóa</button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-designs">Bạn chưa có thiết kế nào</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>