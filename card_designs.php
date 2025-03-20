<?php
require_once 'connect_db.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit();
}

// Lấy danh sách template đã mua
$stmt = $conn->prepare("
    SELECT t.*, p.Status as PaymentStatus, p.PurchaseID
    FROM templates t
    JOIN purchases p ON t.TemplateID = p.TemplateID
    WHERE p.UserID = ? AND p.Status = 'completed'
    ORDER BY p.CreatedAt DESC
");
$stmt->execute([$_SESSION['UserID']]);
$purchases = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates đã mua - Webs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0066ff;
            --secondary: #5900ff;
            --success: #10b981;
            --background: #f8fafc;
        }

        body {
            background-color: var(--background);
            font-family: 'Inter', sans-serif;
        }

        .template-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .template-img {
            height: 250px;
            object-fit: cover;
            width: 100%;
        }

        .template-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .template-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .template-description {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .download-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .download-btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .page-header {
            margin-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="page-header">
            <h2 class="mb-0">Templates đã mua</h2>
        </div>
        
        <?php if (empty($purchases)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox fs-1"></i>
            <h3 class="mt-3">Chưa có template nào</h3>
            <p>Bạn chưa mua template nào. Hãy khám phá các template của chúng tôi!</p>
            <a href="templates.php" class="btn btn-primary">Xem Templates</a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach($purchases as $purchase): ?>
            <div class="col-md-4">
                <div class="template-card">
                    <img src="<?php echo htmlspecialchars($purchase['PreviewImage']); ?>" 
                         class="template-img" 
                         alt="<?php echo htmlspecialchars($purchase['TemplateName']); ?>">
                    
                    <div class="template-content">
                        <h3 class="template-title">
                            <?php echo htmlspecialchars($purchase['TemplateName']); ?>
                        </h3>
                        <p class="template-description">
                            <?php echo htmlspecialchars($purchase['Description']); ?>
                        </p>
                        
                        <a href="download.php?template_id=<?php echo $purchase['TemplateID']; ?>" 
                           class="download-btn text-decoration-none">
                            <i class="bi bi-download"></i>
                            Tải xuống template
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
