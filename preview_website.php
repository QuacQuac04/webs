<?php
require_once 'connect_db.php';
session_start();

// Lấy template ID từ URL
$templateId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$templateId) {
    header('Location: index.php');
    exit();
}

// Lấy thông tin template
$stmt = $conn->prepare("SELECT t.*, u.Username, u.Avatar FROM templates t 
                       JOIN users u ON t.UserID = u.UserID 
                       WHERE t.TemplateID = ?");
$stmt->execute([$templateId]);
$template = $stmt->fetch();

if (!$template) {
    header('Location: index.php');
    exit();
}

// Cập nhật lượt xem
$stmt = $conn->prepare("UPDATE templates SET Views = Views + 1 WHERE TemplateID = ?");
$stmt->execute([$templateId]);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($template['TemplateName']); ?> - Webs</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #0066ff;
            --secondary: #5900ff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        .preview-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .template-info {
            flex: 1;
        }

        .template-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .template-meta {
            display: flex;
            align-items: center;
            gap: 24px;
            color: #64748b;
            font-size: 14px;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .author-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .preview-actions {
            display: flex;
            gap: 16px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
        }

        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }

        .preview-frame {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .preview-iframe {
            width: 100%;
            height: 800px;
            border: none;
        }

        .template-stats {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .template-description {
            margin: 24px 0;
            color: #64748b;
            line-height: 1.6;
        }

        .template-actions {
            display: flex;
            gap: 16px;
            margin-top: 20px;
        }

        .btn-customize {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-customize i {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-header">
            <div class="template-info">
                <h1 class="template-title"><?php echo htmlspecialchars($template['TemplateName']); ?></h1>
                <div class="template-meta">
                    <div class="author-info">
                        <img src="<?php echo htmlspecialchars($template['Avatar']); ?>" alt="Avatar" class="author-avatar">
                        <span><?php echo htmlspecialchars($template['Username']); ?></span>
                    </div>
                    <div class="template-stats">
                        <div class="stat-item">
                            <i class="bi bi-eye"></i>
                            <span><?php echo number_format($template['Views']); ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="bi bi-calendar"></i>
                            <span><?php echo date('d/m/Y', strtotime($template['CreatedDate'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="preview-actions">
                <a href="#" class="btn btn-outline">
                    <i class="bi bi-heart"></i>
                    Yêu thích
                </a>
                <a href="edit-design.php?id=<?php echo $template['TemplateID']; ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i>
                    Tùy chỉnh mẫu này
                </a>
                <?php if (isset($_SESSION['UserID'])): ?>
                    <a href="payment.php?template_id=<?php echo $template['TemplateID']; ?>" class="btn btn-primary">
                        <i class="bi bi-cart"></i>
                        Mua ngay <?php echo number_format($template['Price']); ?>đ
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Đăng nhập để mua
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($template['Description']): ?>
        <div class="template-description">
            <?php echo nl2br(htmlspecialchars($template['Description'])); ?>
        </div>
        <?php endif; ?>

        <div class="preview-frame">
            <iframe src="render-template.php?id=<?php echo $template['TemplateID']; ?>" 
                    class="preview-iframe" 
                    frameborder="0"
                    width="100%"
                    height="800px">
            </iframe>
        </div>
    </div>
</body>
</html>