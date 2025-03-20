<?php 
session_start();
// Xóa hoặc comment các dòng debug sau
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";

$currentUserId = $_SESSION['UserID'] ?? null;

require_once 'connect_db.php';

// Query để lấy templates và trạng thái like
$sql = "SELECT t.*, u.Username, 
        COALESCE(t.Likes, 0) as LikeCount,
        CASE WHEN tl.UserID IS NOT NULL THEN 1 ELSE 0 END as IsLiked
        FROM templates t
        INNER JOIN users u ON t.UserID = u.UserID 
        LEFT JOIN template_likes tl ON t.TemplateID = tl.TemplateID 
            AND tl.UserID = :currentUserId
        WHERE t.Status = 'Approved'
        ORDER BY t.CreatedDate DESC";

$stmt = $conn->prepare($sql);
$stmt->execute(['currentUserId' => $currentUserId]);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webs Community - Tạo Website Bằng Mô Tả</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playball&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <!-- Sidebar Menu -->
    <div class="sidebar-menu">
        <div class="sidebar-header">
            <a href="/" class="logo">Webs</a>
        </div>
        <div class="sidebar-content">
            <nav class="sidebar-nav">
                <a href="/" class="nav-item active">
                    <i class="bi bi-house-door"></i>
                    <span>Trang chủ</span>
                </a>
                <a href="index.php" class="nav-item">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Mẫu thiết kế</span>
                </a>
                <a href="my-designs.php" class="nav-item">
                    <i class="bi bi-folder2"></i>
                    <span>Thiết kế của tôi</span>
                </a>
                <a href="design_website_ai.php" class="nav-item">
                    <i class="bi bi-robot"></i>
                    <span>AI Design</span>
                </a>
                <div class="nav-divider"></div>
                <a href="/trash" class="nav-item">
                    <i class="bi bi-trash"></i>
                    <span>Thùng rác</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="bi bi-gear"></i>
                    <span>Cài đặt</span>
                </a>
                <div class="nav-divider"></div>
                <a href="/help" class="nav-item">
                    <i class="bi bi-question-circle"></i>
                    <span>Trợ giúp</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Header -->
        <header>
            <div class="top-header">
                <div class="container">
                    <!-- Left section -->
                    <div class="header-left">
                        <button class="menu-toggle">
                            <i class="bi bi-list"></i>
                        </button>
                        <a href="/" class="logo">Webs</a>
                    </div>

                    <!-- Center section - Search -->
                    <div class="header-center">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="searchInput" placeholder="Tìm kiếm mẫu website...">
                        </div>
                    </div>

                    <!-- Right section -->
                    <div class="header-right">
                        <button class="btn-design">
                            <a href="template-user.php">
                                <i class="bi bi-pencil"></i>
                                <span>Thiết kế</span>
                            </a>
                        </button>

                        <?php if(isset($_SESSION['UserID'])): ?>
                            <div class="user-menu">
                                <div class="user-info">
                                    <img src="<?php echo htmlspecialchars($_SESSION['avatar'] ?? 'images/default-avatar.png'); ?>" alt="Avatar" class="avatar">
                                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                    <i class="bi bi-chevron-down"></i>
                                </div>
                                <div class="dropdown-menu">
                                    <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                                        <a href="admin.php"><i class="bi bi-speedometer2"></i> Admin Dashboard</a>
                                        <div class="dropdown-divider"></div>
                                    <?php endif; ?>
                                    <a href="profile.php"><i class="bi bi-person"></i>Thông tin tài khoản</a>
                                    <a href="my-designs.php"><i class="bi bi-grid"></i>Thiết kế của bạn</a>
                                    <a href="card_designs.php"><i class="bi bi-credit-card"></i>Đã thanh toán</a>
                                    <a href="my-upload.php"><i class="bi bi-upload"></i>Upload mẫu</a>
                                    <a href="settings.php"><i class="bi bi-gear"></i>Cài đặt</a>
                                    <div class="dropdown-divider"></div>
                                    <a href="logout.php" class="logout"><i class="bi bi-box-arrow-right"></i>Đăng xuất</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="auth-buttons">
                                <a href="login.php" class="btn-login">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    <span>Đăng nhập</span>
                                </a>
                                <a href="register.php" class="btn-register">
                                    <i class="bi bi-person-plus"></i>
                                    <span>Đăng ký</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="main-content">
            <section class="hero">
                <div class="container">
                    <h1>Bạn muốn thiết kế gì?</h1>
                    <div class="design-categories">
                        <a href="" class="category-item">
                            <div class="category-icon">
                                <i class="bi bi-building"></i>
                            </div>
                            <span>Doanh nghiệp</span>
                        </a>
                        <a href="" class="category-item">
                            <div class="category-icon">
                                <i class="bi bi-mortarboard"></i>
                            </div>
                            <span>Giáo dục</span>
                        </a>
                        <a href="" class="category-item">
                            <div class="category-icon">
                                <i class="bi bi-cart3"></i>
                            </div>
                            <span>Shopping</span>
                        </a>
                        <a href="" class="category-item">
                            <div class="category-icon">
                                <i class="bi bi-controller"></i>
                            </div>
                            <span>Games</span>
                        </a>
                        <a href="" class="category-item">
                            <div class="category-icon">
                                <i class="bi bi-people"></i>
                            </div>
                            <span>Mạng xã hội</span>
                        </a>
                        <a href="" class="category-item">
                            <div class="category-icon">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <span>Travel</span>
                        </a>
                        <a href="" class="category-item">
                            <div class="category-icon">
                                <i class="bi bi-palette"></i>
                            </div>
                            <span>Design</span>
                        </a>
                        <a href="" class="category-item">
                            <div class="category-icon">
                                <i class="bi bi-grid"></i>
                            </div>
                            <span>Xem thêm</span>
                        </a>
                    </div>
                </div>
            </section>

            <section class="features">
                <div class="container">
                    <div class="feature-grid">
                        <a href="design_website_ai.php" class="feature-card" style="text-decoration: none; color: inherit;">
                            <div class="feature-icon">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div class="feature-content">
                                <h3>Tạo Website Bằng AI</h3>
                                <p>Chỉ cần mô tả ý tưởng, AI sẽ giúp bạn tạo website</p>
                            </div>
                        </a>
                        <a href="template-user.php" class="feature-card" style="text-decoration: none; color: inherit;">
                            <div class="feature-icon">
                                <i class="bi bi-collection"></i>
                            </div>
                            <div class="feature-content">
                                <h3>Thư Viện Mẫu</h3>
                                <p>Hàng nghìn mẫu website do cộng đồng đóng góp</p>
                            </div>
                        </a>
                        <a href="template-user.php" class="feature-card" style="text-decoration: none; color: inherit;">
                            <div class="feature-icon">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <div class="feature-content">
                                <h3>Tùy Chỉnh Dễ Dàng</h3>
                                <p>Chỉnh sửa trực quan với công cụ kéo thả</p>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            <section class="templates">
                <div class="container">
                    <div class="section-header">
                        <h2>Khám Phá Tác Phẩm Cộng Đồng</h2>
                        <div class="template-filters">
                            <div class="filter-dropdown">
                                <button class="filter-button" id="sortButton">
                                    <i class="bi bi-funnel"></i>
                                    <span>Sắp xếp theo</span>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                <div class="filter-menu" id="filterMenu">
                                    <a href="#" class="filter-option" data-sort="newest">
                                        <i class="bi bi-clock-history"></i>
                                        <span>Mới nhất</span>
                                    </a>
                                    <a href="#" class="filter-option" data-sort="popular">
                                        <i class="bi bi-graph-up"></i>
                                        <span>Phổ biến nhất</span>
                                    </a>
                                    <a href="#" class="filter-option" data-sort="trending">
                                        <i class="bi bi-arrow-up-right"></i>
                                        <span>Xu hướng</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-grid">
                        <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="template-card">
                                <div class="template-thumbnail">
                                    <img src="<?php echo htmlspecialchars($row['PreviewImage']); ?>" 
                                         alt="<?php echo htmlspecialchars($row['TemplateName']); ?>">
                                    <div class="template-overlay">
                                        <a href="preview_website.php?id=<?php echo $row['TemplateID']; ?>" class="btn-preview">
                                            <i class="bi bi-eye"></i>
                                            <span>Xem trực tiếp</span>
                                        </a>
                                        <button class="btn-like <?php echo ($row['IsLiked'] ? 'liked' : ''); ?>" 
                                                data-template-id="<?php echo $row['TemplateID']; ?>">
                                            <i class="bi bi-heart<?php echo ($row['IsLiked'] ? '-fill' : ''); ?>"></i>
                                            <span class="like-count"><?php echo $row['LikeCount']; ?></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="template-info">
                                    <h3 class="template-name"><?php echo htmlspecialchars($row['TemplateName']); ?></h3>
                                    <p class="template-description"><?php echo htmlspecialchars($row['Description']); ?></p>
                                    <div class="template-meta">
                                        <div class="template-author">
                                            <i class="bi bi-person"></i>
                                            <?php echo htmlspecialchars($row['Username']); ?>
                                        </div>
                                        <div class="template-stats">
                                            <span><i class="bi bi-eye"></i> <?php echo $row['Views'] ?? 0; ?></span>
                                            <span><i class="bi bi-heart"></i> <?php echo $row['LikeCount']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Thêm nút load more -->
                    <div class="load-more" style="display:flex; text-align: center; justify-content: center; margin-top: 20px; padding:20px">
                        <button class="btn-load-more" id="loadMore" style="background-color:#4f46e5; color: white; padding: 12px 20px; border-radius: 20px; cursor: pointer; border: none; font-weight: 600;">Xem thêm</button>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <!-- Chatbot -->
    <script src="https://www.gstatic.com/dialogflow-console/fast/messenger/bootstrap.js?v=1"></script>
    <df-messenger
    chat-title="Webs-support!"
    agent-id="0a2a51f3-c92b-4c61-ab01-547f0c944a4d"
    language-code="vi"
    ></df-messenger>


    <script>
    // Thêm JavaScript để xử lý dropdown menu trên mobile
    document.addEventListener('DOMContentLoaded', function() {
        const userInfo = document.querySelector('.user-info');
        const dropdownMenu = document.querySelector('.dropdown-menu');
        
        if (userInfo && dropdownMenu) {
            userInfo.addEventListener('click', function(e) {
                e.preventDefault();
                dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
            });

            // Đóng dropdown khi click bên ngoài
            document.addEventListener('click', function(e) {
                if (!userInfo.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.style.display = 'none';
                }
            });
        }
    });
    </script>
    <script src="/script.js"></script>
</body>
</html>