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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt tài khoản - Webs</title>
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

        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px 40px;
        }

        .settings-header {
            margin-bottom: 30px;
            padding-top: 0;
        }

        .settings-header h1 {
            margin-top: 0;
            font-size: 28px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }

        .settings-nav {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            margin: 4px 0;
            border-radius: 8px;
            color: #666;
            text-decoration: none;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background: #f0f0f0;
            color: #333;
        }

        .nav-item.active {
            background: #0066ff;
            color: white;
        }

        .nav-item i {
            margin-right: 12px;
            font-size: 18px;
        }

        .settings-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .settings-section {
            margin-bottom: 40px;
        }

        .settings-section h2 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1a1a1a;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4a4a4a;
        }

        .form-control {
            width: 100%;
            padding: 10px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: #0066ff;
            outline: none;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #0066ff;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #0052cc;
        }

        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .upload-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* Thêm styles mới */
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .notification-group {
            margin-bottom: 30px;
        }

        .notification-group h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #666;
        }

        .theme-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }

        .theme-option input[type="radio"] {
            display: none;
        }

        .theme-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .theme-box i {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .theme-option input[type="radio"]:checked + .theme-box {
            border-color: #0066ff;
            background: #f0f7ff;
        }

        .billing-info {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .current-plan {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .plan-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        .plan-name {
            font-size: 18px;
            font-weight: 500;
        }

        .payment-methods {
            margin-top: 20px;
        }

        .payment-list {
            margin: 15px 0;
        }

        .payment-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .billing-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .billing-table th,
        .billing-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }

        .status.success {
            background: #e6f4ea;
            color: #1e7e34;
        }

        /* Header Styles */
        .main-header {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 56px;
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
            font-size: 1.2rem;
            transition: color 0.2s;
        }

        .logo:hover {
            color: var(--primary-color);
        }

        .logo i {
            font-size: 1.4rem;
            margin-right: 8px;
        }

        .header-right {
            display: flex;
            align-items: center;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            border-radius: 20px;
            background-color: var(--hover-bg);
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .user-menu:hover {
            background-color: var(--border-color);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .username {
            font-weight: 500;
            color: var(--text-color);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .username {
                display: none;
            }
            
            .user-menu {
                padding: 4px;
            }
            
            .settings-container {
                padding-top: 70px;
            }
            
            .header-container {
                height: 48px;
            }
        }

        .logo span{
            font-family: "Playball", cursive;
            font-weight: 700;
            font-style: normal;
            font-size: 2rem;
            color: #3b82f6;
        }

    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="header-left">
                <a href="index.php" class="logo">
                    <span>Webs</span>
                </a>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <img src="<?php echo htmlspecialchars($user['Avatar'] ?? 'images/default-avatar.png'); ?>" alt="Avatar" class="user-avatar">
                    <span class="username"><?php echo htmlspecialchars($user['Username']); ?></span>
                </div>
            </div>
        </div>
    </header>
    <div class="settings-container">
        <div class="settings-header">
            <h1>Cài đặt tài khoản</h1>
        </div>
        
        <div class="settings-grid">
            <div class="settings-nav">
                <a href="#profile" class="nav-item active">
                    <i class="bi bi-person"></i>
                    Thông tin cá nhân
                </a>
                <a href="#security" class="nav-item">
                    <i class="bi bi-shield-lock"></i>
                    Bảo mật
                </a>
                <a href="#notifications" class="nav-item">
                    <i class="bi bi-bell"></i>
                    Thông báo
                </a>
                <a href="#appearance" class="nav-item">
                    <i class="bi bi-palette"></i>
                    Giao diện
                </a>
                <a href="#billing" class="nav-item">
                    <i class="bi bi-credit-card"></i>
                    Thanh toán
                </a>
            </div>

            <div class="settings-content">
                <div class="settings-section" id="profile-section">
                    <h2>Thông tin cá nhân</h2>
                    
                    <div class="avatar-upload">
                        <div class="avatar-preview">
                            <img src="<?php echo htmlspecialchars($user['Avatar'] ?? 'images/default-avatar.png'); ?>" alt="Avatar" id="avatarPreview">
                        </div>
                        <div class="upload-buttons">
                            <input type="file" id="avatarInput" style="display: none" accept="image/*">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('avatarInput').click()">Thay đổi ảnh</button>
                            <button type="button" class="btn" onclick="deleteAvatar()">Xóa ảnh</button>
                        </div>
                    </div>

                    <form action="update_profile.php" method="POST" id="profileForm">
                        <div class="form-group">
                            <label for="username">Tên người dùng</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                value="<?php echo htmlspecialchars($user['Username']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control"
                                value="<?php echo htmlspecialchars($user['Email']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="bio">Giới thiệu</label>
                            <textarea id="bio" name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($user['Bio'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </form>
                </div>

                <div class="settings-section" id="security-section" style="display: none;">
                    <h2>Bảo mật</h2>
                    <form id="securityForm" action="update_security.php" method="POST">
                        <div class="form-group">
                            <label for="current_password">Mật khẩu hiện tại</label>
                            <input type="password" id="current_password" name="current_password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="new_password">Mật khẩu mới</label>
                            <input type="password" id="new_password" name="new_password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Xác nhận mật khẩu mới</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="two_factor" id="two_factor">
                                Bật xác thực hai yếu tố
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật bảo mật</button>
                    </form>
                </div>

                <div class="settings-section" id="notifications-section" style="display: none;">
                    <h2>Thông báo</h2>
                    <form id="notificationsForm" action="update_notifications.php" method="POST">
                        <div class="notification-group">
                            <h3>Email</h3>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="email_news" checked>
                                    Nhận thông báo về tin tức và cập nhật
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="email_security" checked>
                                    Thông báo bảo mật và đăng nhập
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="email_marketing">
                                    Nhận email marketing và khuyến mãi
                                </label>
                            </div>
                        </div>
                        
                        <div class="notification-group">
                            <h3>Ứng dụng</h3>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="push_all" checked>
                                    Bật thông báo đẩy
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="push_messages" checked>
                                    Thông báo tin nhắn mới
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Lưu cài đặt</button>
                    </form>
                </div>

                <div class="settings-section" id="appearance-section" style="display: none;">
                    <h2>Giao diện</h2>
                    <form id="appearanceForm" action="update_appearance.php" method="POST">
                        <div class="form-group">
                            <label>Chế độ hiển thị</label>
                            <div class="theme-options">
                                <label class="theme-option">
                                    <input type="radio" name="theme" value="light" checked>
                                    <span class="theme-box light">
                                        <i class="bi bi-sun"></i>
                                        Sáng
                                    </span>
                                </label>
                                <label class="theme-option">
                                    <input type="radio" name="theme" value="dark">
                                    <span class="theme-box dark">
                                        <i class="bi bi-moon"></i>
                                        Tối
                                    </span>
                                </label>
                                <label class="theme-option">
                                    <input type="radio" name="theme" value="system">
                                    <span class="theme-box system">
                                        <i class="bi bi-display"></i>
                                        Theo hệ thống
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="font_size">Cỡ chữ</label>
                            <select id="font_size" name="font_size" class="form-control">
                                <option value="small">Nhỏ</option>
                                <option value="medium" selected>Vừa</option>
                                <option value="large">Lớn</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Lưu cài đặt</button>
                    </form>
                </div>

                <div class="settings-section" id="billing-section" style="display: none;">
                    <h2>Thanh toán</h2>
                    <div class="billing-info">
                        <div class="current-plan">
                            <h3>Gói hiện tại</h3>
                            <div class="plan-details">
                                <span class="plan-name">Gói Free</span>
                                <button class="btn btn-primary">Nâng cấp</button>
                            </div>
                        </div>

                        <div class="payment-methods">
                            <h3>Phương thức thanh toán</h3>
                            <div class="payment-list">
                                <div class="payment-item">
                                    <i class="bi bi-credit-card"></i>
                                    <span>Visa ****4589</span>
                                    <button class="btn">Xóa</button>
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="addPaymentMethod()">
                                <i class="bi bi-plus"></i> Thêm phương thức
                            </button>
                        </div>

                        <div class="billing-history">
                            <h3>Lịch sử thanh toán</h3>
                            <table class="billing-table">
                                <thead>
                                    <tr>
                                        <th>Ngày</th>
                                        <th>Mô tả</th>
                                        <th>Số tiền</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>01/01/2024</td>
                                        <td>Gói Premium - Tháng 1</td>
                                        <td>199.000đ</td>
                                        <td><span class="status success">Thành công</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thêm div thông báo -->
    <div id="notification" style="display: none; position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 1000;"></div>

    <!-- Đặt script ở cuối body -->
    <script>
    // Xử lý upload ảnh
    document.getElementById('avatarInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('avatar', file);

            fetch('upload_avatar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('avatarPreview').src = data.avatar_url + '?t=' + new Date().getTime();
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(error => {
                showNotification('Có lỗi xảy ra khi upload ảnh', 'error');
            });
        }
    });

    // Xử lý xóa ảnh
    function deleteAvatar() {
        fetch('delete_avatar.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('avatarPreview').src = 'images/default-avatar.png';
                showNotification('Đã xóa ảnh đại diện', 'success');
            } else {
                showNotification(data.message || 'Không thể xóa ảnh', 'error');
            }
        })
        .catch(error => {
            showNotification('Có lỗi xảy ra khi xóa ảnh', 'error');
        });
    }

    // Xử lý form submit
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message || 'Cập nhật thất bại', 'error');
            }
        })
        .catch(error => {
            showNotification('Có lỗi xảy ra khi cập nhật thông tin', 'error');
        });
    });

    // Xử lý form bảo mật
    document.getElementById('securityForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('update_security.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message, 'error');
            }
        });
    });

    // Xử lý form thông báo
    document.getElementById('notificationsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('update_notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message, 'error');
            }
        });
    });

    // Xử lý form giao diện
    document.getElementById('appearanceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('update_appearance.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // Áp dụng thay đổi giao diện ngay lập tức
                applyTheme(formData.get('theme'));
                applyFontSize(formData.get('font_size'));
            } else {
                showNotification(data.message, 'error');
            }
        });
    });

    // Hàm hiển thị thông báo
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.style.display = 'block';
        notification.style.background = type === 'success' ? '#4CAF50' : '#f44336';
        notification.style.color = 'white';

        // Tự động ẩn sau 3 giây
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }

    // Xử lý chuyển đổi tab
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            
            // Xóa active class từ tất cả nav items
            document.querySelectorAll('.nav-item').forEach(nav => {
                nav.classList.remove('active');
            });
            
            // Thêm active class cho tab được chọn
            this.classList.add('active');
            
            // Ẩn tất cả các section
            document.querySelectorAll('.settings-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Hiện section được chọn
            document.getElementById(targetId + '-section').style.display = 'block';
        });
    });

    // Hàm thêm phương thức thanh toán
    function addPaymentMethod() {
        // Hiển thị modal hoặc form thêm thẻ
        // Sau khi người dùng nhập thông tin, gửi request
        const formData = new FormData();
        formData.append('action', 'add_payment');
        formData.append('card_type', 'Visa');  // Lấy từ form
        formData.append('card_number', '****4589');  // Lấy từ form
        formData.append('expiry_date', '2025-12');  // Lấy từ form
        
        fetch('update_billing.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // Cập nhật UI để hiển thị thẻ mới
                updatePaymentMethods();
            } else {
                showNotification(data.message, 'error');
            }
        });
    }

    // Hàm áp dụng theme
    function applyTheme(theme) {
        document.body.className = theme;
        // Thêm logic để thay đổi màu sắc theo theme
    }

    // Hàm áp dụng cỡ chữ
    function applyFontSize(size) {
        document.documentElement.style.fontSize = size;
    }

    // Hàm cập nhật danh sách phương thức thanh toán
    function updatePaymentMethods() {
        // Gọi API để lấy danh sách mới và cập nhật UI
    }
    </script>
</body>
</html>