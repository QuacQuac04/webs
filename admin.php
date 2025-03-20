<?php
require_once 'connect_db.php';
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}

// Xử lý upload template
if(isset($_POST['upload_template'])) {
    $templateName = $_POST['template_name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    
    // Xử lý upload ảnh preview
    $targetDir = "uploads/templates/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $previewImage = $targetDir . time() . '_' . basename($_FILES["preview_image"]["name"]);
    $htmlFile = $targetDir . time() . '_' . basename($_FILES["html_file"]["name"]);
    $cssFile = $targetDir . time() . '_' . basename($_FILES["css_file"]["name"]);
    $jsFile = $targetDir . time() . '_' . basename($_FILES["js_file"]["name"]);
    
    if(move_uploaded_file($_FILES["preview_image"]["tmp_name"], $previewImage) && 
       move_uploaded_file($_FILES["html_file"]["tmp_name"], $htmlFile)) {
        
        $htmlContent = file_get_contents($htmlFile);
        $cssContent = '';
        $jsContent = '';
        
        // Đọc nội dung file CSS nếu được upload
        if(!empty($_FILES["css_file"]["tmp_name"])) {
            if(move_uploaded_file($_FILES["css_file"]["tmp_name"], $cssFile)) {
                $cssContent = file_get_contents($cssFile);
            }
        }
        
        // Đọc nội dung file JS nếu được upload
        if(!empty($_FILES["js_file"]["tmp_name"])) {
            if(move_uploaded_file($_FILES["js_file"]["tmp_name"], $jsFile)) {
                $jsContent = file_get_contents($jsFile);
            }
        }
        
        try {
            $stmt = $conn->prepare("INSERT INTO templates (UserID, TemplateName, Description, Category, PreviewImage, HTMLContent, CSSContent, JSContent, Price, Status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$_SESSION['UserID'], $templateName, $description, $category, $previewImage, $htmlContent, $cssContent, $jsContent, $price]);
            $message = "Template uploaded successfully!";
            $messageType = "success";
        } catch(PDOException $e) {
            $message = "Upload failed: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Xử lý approve/reject/delete template
if(isset($_POST['action']) && isset($_POST['template_id'])) {
    $action = $_POST['action'];
    $templateId = $_POST['template_id'];
    
    try {
        switch($action) {
            case 'approve':
                $stmt = $conn->prepare("UPDATE templates SET Status = 'Approved', ApprovedDate = CURRENT_TIMESTAMP WHERE TemplateID = ?");
                $stmt->execute([$templateId]);
                $message = "Template approved successfully!";
                break;
                
            case 'reject':
                $stmt = $conn->prepare("UPDATE templates SET Status = 'Rejected' WHERE TemplateID = ?");
                $stmt->execute([$templateId]);
                $message = "Template rejected successfully!";
                break;
                
            case 'delete':
                // Bắt đầu transaction
                $conn->beginTransaction();
                
                // Xóa các bản ghi liên quan trong bảng purchases nếu có
                $stmt = $conn->prepare("DELETE FROM purchases WHERE TemplateID = ?");
                $stmt->execute([$templateId]);
                
                // Xóa các bản ghi liên quan trong bảng reviews nếu có
                $stmt = $conn->prepare("DELETE FROM reviews WHERE TemplateID = ?");
                $stmt->execute([$templateId]);
                
                // Xóa các bản ghi liên quan trong bảng community_designs
                $stmt = $conn->prepare("DELETE FROM community_designs WHERE TemplateID = ?");
                $stmt->execute([$templateId]);
                
                // Cuối cùng xóa template
                $stmt = $conn->prepare("DELETE FROM templates WHERE TemplateID = ?");
                $stmt->execute([$templateId]);
                
                // Commit transaction
                $conn->commit();
                $message = "Template deleted successfully!";
                break;
        }
        $messageType = "success";
    } catch(PDOException $e) {
        if($action === 'delete') {
            $conn->rollBack();
        }
        $message = "Action failed: " . $e->getMessage();
        $messageType = "error";
    }
}

// Lấy danh sách templates
$stmt = $conn->prepare("SELECT t.*, u.Username 
                       FROM templates t 
                       JOIN users u ON t.UserID = u.UserID 
                       ORDER BY t.CreatedDate DESC");
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            z-index: 100;
            background: #fff;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            margin-left: 240px;
            padding: 30px;
        }
        
        .nav-link {
            color: #333;
            padding: 12px 20px;
            margin: 4px 16px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background-color: var(--primary);
            color: white;
        }
        
        .nav-link i {
            margin-right: 10px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .template-img {
            height: 200px;
            object-fit: cover;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }s
        
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #ffd60a;
            color: #000;
        }
        
        .status-approved {
            background-color: #52b788;
            color: #fff;
        }
        
        .status-rejected {
            background-color: #e63946;
            color: #fff;
        }
        
        .upload-form {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        
        .nav.flex-column {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .nav-link.text-danger {
            color: #dc3545 !important;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .nav-link.text-danger:hover {
            background-color: #dc3545;
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }
        
        .nav-link.text-danger i {
            transition: transform 0.3s ease;
        }
        
        .nav-link.text-danger:hover i {
            transform: translateX(5px);
        }
        
        .sidebar .nav.flex-column {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        
        .nav-item.mt-auto {
            margin-top: auto !important;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="col-md-3 col-lg-2 d-md-block sidebar">
        <div class="position-sticky">
            <div class="text-center mb-4">
                <h4>Admin Panel</h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#" onclick="showSection('dashboard')">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('upload')">
                        <i class="bi bi-cloud-upload"></i> Upload Template
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('manage')">
                        <i class="bi bi-grid"></i> Manage Templates
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('payments')">
                        <i class="bi bi-credit-card"></i> Payment Approvals
                    </a>
                </li>
                <li class="nav-item mt-auto">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <?php if(isset($message)): ?>
            <div class="alert alert-<?php echo $messageType == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Dashboard Section -->
        <section id="dashboard" class="content-section">
            <h2 class="mb-4">Dashboard Overview</h2>
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Total Templates</h5>
                            <h2><?php echo count($templates); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Pending Review</h5>
                            <h2><?php echo count(array_filter($templates, function($t) { return $t['Status'] == 'Pending'; })); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Approved Templates</h5>
                            <h2><?php echo count(array_filter($templates, function($t) { return $t['Status'] == 'Approved'; })); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Upload Section -->
        <section id="upload" class="content-section" style="display: none;">
            <h2 class="mb-4">Upload New Template</h2>
            <div class="upload-form">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Template Name</label>
                        <input type="text" class="form-control" name="template_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-control" name="category" required>
                            <option value="">Select Category</option>
                            <option value="business">Business</option>
                            <option value="portfolio">Portfolio</option>
                            <option value="ecommerce">E-commerce</option>
                            <option value="blog">Blog</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" class="form-control" name="price" step="0.01" min="0" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Preview Image</label>
                        <input type="file" class="form-control" name="preview_image" accept="image/*" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">HTML File</label>
                        <input type="file" class="form-control" name="html_file" accept=".html,.htm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CSS File</label>
                        <input type="file" class="form-control" name="css_file" accept=".css">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">JavaScript File</label>
                        <input type="file" class="form-control" name="js_file" accept=".js">
                    </div>
                    <button type="submit" name="upload_template" class="btn btn-primary">
                        <i class="bi bi-cloud-upload"></i> Upload Template
                    </button>
                </form>
            </div>
        </section>

        <!-- Manage Templates Section -->
        <section id="manage" class="content-section" style="display: none;">
            <h2 class="mb-4">Manage Templates</h2>
            <div class="row g-4">
                <?php foreach($templates as $template): ?>
                <div class="col-md-3">
                    <div class="card">
                        <img src="<?php echo htmlspecialchars($template['PreviewImage']); ?>" 
                             class="template-img" 
                             alt="<?php echo htmlspecialchars($template['TemplateName']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($template['TemplateName']); ?></h5>
                            <p class="card-text small text-muted mb-2">
                                By <?php echo htmlspecialchars($template['Username']); ?> | 
                                <?php echo date('d M Y', strtotime($template['CreatedDate'])); ?>
                            </p>
                            <span class="status-badge status-<?php echo strtolower($template['Status']); ?>">
                                <?php echo $template['Status']; ?>
                            </span>
                            <div class="mt-3">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="template_id" value="<?php echo $template['TemplateID']; ?>">
                                    <?php if($template['Status'] == 'Pending'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-info btn-sm" 
                                            onclick="previewTemplate(<?php echo $template['TemplateID']; ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            onclick="editTemplate(<?php echo $template['TemplateID']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Payment Approvals Section -->
        <section id="payments" class="content-section" style="display: none;">
            <h2 class="mb-4">Payment Approvals</h2>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>User</th>
                            <th>Template</th>
                            <th>Amount</th>
                            <th>Proof</th>
                            <th>Submit Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("
                            SELECT pp.*, p.TransactionID, p.Amount, 
                                   u.Username, t.TemplateName
                            FROM payment_proofs pp
                            JOIN purchases p ON pp.PurchaseID = p.PurchaseID
                            JOIN users u ON p.UserID = u.UserID
                            JOIN templates t ON p.TemplateID = t.TemplateID
                            WHERE pp.Status = 'pending'
                            ORDER BY pp.SubmitDate DESC
                        ");
                        $stmt->execute();
                        $payments = $stmt->fetchAll();

                        foreach($payments as $payment):
                        ?>
                        <tr>
                            <td><?php echo $payment['TransactionID']; ?></td>
                            <td><?php echo $payment['Username']; ?></td>
                            <td><?php echo $payment['TemplateName']; ?></td>
                            <td><?php echo number_format($payment['Amount']); ?>đ</td>
                            <td>
                                <button type="button" 
                                        class="btn btn-primary btn-sm" 
                                        onclick="viewProof('<?php echo htmlspecialchars($payment['ImageProof']); ?>', '<?php echo htmlspecialchars($payment['TransactionID']); ?>')">
                                    <i class="bi bi-image"></i> Xem ảnh
                                </button>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($payment['SubmitDate'])); ?></td>
                            <td>
                                <button onclick="approvePayment(<?php echo $payment['ProofID']; ?>)" 
                                        class="btn btn-success btn-sm">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                                <button onclick="rejectPayment(<?php echo $payment['ProofID']; ?>)" 
                                        class="btn btn-danger btn-sm">
                                    <i class="bi bi-x-lg"></i> Reject
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(sectionId).style.display = 'block';
            
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }

        function previewTemplate(templateId) {
            window.open('preview_template.php?id=' + templateId, '_blank');
        }

        function editTemplate(templateId) {
            window.location.href = 'edit_template.php?id=' + templateId;
        }

        function approvePayment(proofId) {
            Swal.fire({
                title: 'Xác nhận duyệt?',
                text: 'Người dùng sẽ được cấp quyền tải template',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Duyệt',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#ef4444'
            }).then((result) => {
                if (result.isConfirmed) {
                    processPayment(proofId, 'approve');
                }
            });
        }

        function rejectPayment(proofId) {
            Swal.fire({
                title: 'Từ chối thanh toán?',
                text: 'Vui lòng nhập lý do',
                input: 'text',
                inputPlaceholder: 'Nhập lý do từ chối...',
                showCancelButton: true,
                confirmButtonText: 'Từ chối',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Vui lòng nhập lý do từ chối!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    processPayment(proofId, 'reject', result.value);
                }
            });
        }

        function processPayment(proofId, action, note = '') {
            // Hiển thị loading
            Swal.fire({
                title: 'Đang xử lý...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('process_payment_approval.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    proofId: proofId,
                    action: action,
                    note: note
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Thành công!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Lỗi!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Không thể xử lý yêu cầu',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            });
        }

        function viewProof(imageUrl, transactionId) {
            Swal.fire({
                title: `Bằng chứng thanh toán #${transactionId}`,
                imageUrl: imageUrl,
                imageWidth: 600,
                imageHeight: 'auto',
                imageAlt: 'Payment Proof',
                showCloseButton: true,
                showConfirmButton: false,
                width: 800,
                customClass: {
                    image: 'img-fluid',
                    popup: 'swal-wide'
                }
            });
        }

        // Thêm style cho SweetAlert2
        const style = document.createElement('style');
        style.textContent = `
            .swal-wide {
                max-width: 800px !important;
            }
            .swal2-popup img {
                max-width: 100%;
                height: auto;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            .swal2-title {
                color: #333;
                font-size: 1.5rem;
                padding: 1rem;
            }
            .swal2-close:focus {
                box-shadow: none;
            }
        `;
        document.head.appendChild(style);

        // Show dashboard by default
        document.addEventListener('DOMContentLoaded', function() {
            showSection('dashboard');
        });
    </script>
</body>
</html>