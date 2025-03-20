<?php
session_start();
require_once 'connect_db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

// Xử lý cập nhật thông tin
if (isset($_POST['update_profile'])) {
    try {
        $email = $_POST['email'];
        $bio = $_POST['bio'];
        
        // Xử lý upload avatar
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['avatar']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                $newname = 'avatar_' . $_SESSION['UserID'] . '.' . $filetype;
                $upload_dir = 'uploads/avatars/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $newname)) {
                    $avatar_path = $upload_dir . $newname;
                    
                    // Cập nhật đường dẫn avatar trong database
                    $stmt = $conn->prepare("UPDATE users SET Avatar = ? WHERE UserID = ?");
                    $stmt->execute([$avatar_path, $_SESSION['UserID']]);
                    
                    $_SESSION['avatar'] = $avatar_path;
                }
            }
        }
        
        // Cập nhật thông tin cơ bản
        $stmt = $conn->prepare("UPDATE users SET Email = ?, Bio = ? WHERE UserID = ?");
        $stmt->execute([$email, $bio, $_SESSION['UserID']]);
        
        $success_message = "Cập nhật thông tin thành công!";
    } catch(PDOException $e) {
        $error_message = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý upload ảnh riêng
if(isset($_FILES['avatar'])) {
    try {
        $file = $_FILES['avatar'];
        
        if($file['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $file['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            // Kiểm tra định dạng file
            if(!in_array($filetype, $allowed)) {
                throw new Exception('Chỉ chấp nhận file ảnh định dạng: jpg, jpeg, png, gif');
            }
            
            // Kiểm tra kích thước file (giới hạn 5MB)
            if($file['size'] > 5 * 1024 * 1024) {
                throw new Exception('Kích thước file không được vượt quá 5MB');
            }
            
            // Tạo tên file mới
            $newname = 'avatar_' . $_SESSION['UserID'] . '_' . time() . '.' . $filetype;
            $upload_dir = 'uploads/avatars/';
            
            // Tạo thư mục nếu chưa tồn tại
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Di chuyển file
            if(move_uploaded_file($file['tmp_name'], $upload_dir . $newname)) {
                $avatar_path = $upload_dir . $newname;
                
                // Cập nhật database
                $stmt = $conn->prepare("UPDATE users SET Avatar = ? WHERE UserID = ?");
                $stmt->execute([$avatar_path, $_SESSION['UserID']]);
                
                // Cập nhật session
                $_SESSION['avatar'] = $avatar_path;
                
                // Trả về response cho Ajax
                echo json_encode(['success' => true, 'avatar_url' => $avatar_path]);
                exit;
            } else {
                throw new Exception('Không thể upload file. Vui lòng thử lại.');
            }
        }
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Lấy thông tin user
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE UserID = ?");
    $stmt->execute([$_SESSION['UserID']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Lỗi: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Cá Nhân - <?php echo htmlspecialchars($user['Username']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playball&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --secondary: #6366f1;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-main: #f1f5f9;
            --bg-card: #ffffff;
            --gradient: linear-gradient(135deg, #3b82f6, #6366f1);
            --shadow: 0 4px 20px rgba(148, 163, 184, 0.1);
            --border-color: #e2e8f0;
            --hover-shadow: 0 10px 30px rgba(148, 163, 184, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--bg-main);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* Header & Navigation */
        .nav-header {
            background: var(--bg-card);
            padding: 1.2rem 2rem;
            box-shadow: var(--shadow);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
            animation: slideDown 0.5s ease;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            text-decoration: none;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: translateY(-2px);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary);
            background: var(--bg-main);
            transform: translateY(-2px);
        }

        .nav-link.active {
            color: var(--primary);
            background: var(--bg-main);
        }

        .nav-link.logout {
            color: #ef4444;
            border: 2px solid #ef4444;
        }

        .nav-link.logout:hover {
            color: white;
            background: #ef4444;
        }

        /* Main Container */
        .profile-container {
            max-width: 1400px;
            margin: 6rem auto 3rem;
            padding: 0 2rem;
        }

        /* Cover Section */
        .profile-header {
            position: relative;
            margin-bottom: 2rem;
        }

        .cover-photo {
            height: 400px;
            background: var(--gradient);
            border-radius: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .cover-photo:hover {
            transform: scale(1.01);
            box-shadow: var(--hover-shadow);
        }

        .cover-photo::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }

        /* Profile Info Section */
        .profile-info {
            display: flex;
            margin-top: -100px;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }

        .avatar-section {
            margin-right: 3rem;
        }

        .avatar-wrapper {
            position: relative;
            width: 220px;
            height: 220px;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: var(--hover-shadow);
            background: var(--bg-card);
            padding: 8px;
            transition: all 0.3s ease;
        }

        .avatar-wrapper:hover {
            transform: translateY(-5px);
        }

        .profile-avatar {
            width: 100%;
            height: 100%;
            border-radius: 20px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .avatar-upload {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            background: var(--bg-card);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--primary);
            box-shadow: var(--shadow);
        }

        .avatar-upload:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .profile-details {
            flex: 1;
            background: var(--bg-card);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .profile-name {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .profile-username {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        /* Stats Grid */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .stat-item {
            background: var(--bg-main);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .stat-item:hover {
            transform: translateY(-8px);
            border-color: var(--primary);
            background: var(--bg-card);
            box-shadow: var(--hover-shadow);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Main Content Grid */
        .profile-content {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        /* Sidebar */
        .profile-sidebar {
            background: var(--bg-card);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .bio-text {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 1.1rem;
        }

        /* Main Content Area */
        .profile-main {
            background: var(--bg-card);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            display: block;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 1.5rem;
            border: 2px solid var(--border-color);
            border-radius: 20px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: var(--bg-main);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }

        .btn-update {
            width: 100%;
            padding: 1.5rem;
            border: none;
            border-radius: 20px;
            background: var(--gradient);
            color: white;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }

        .btn-update::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: translateX(-100%);
            transition: 0.5s;
        }

        .btn-update:hover::after {
            transform: translateX(100%);
        }

        /* Recent Activities */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 2rem;
            padding: 2rem;
            background: var(--bg-main);
            border-radius: 20px;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .activity-item:hover {
            transform: translateX(10px);
            background: white;
            box-shadow: var(--hover-shadow);
            border-left-color: var(--primary);
        }

        .activity-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: var(--gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .activity-time {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                order: 2;
            }
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 0 1rem;
                margin-top: 5rem;
            }
            
            .cover-photo {
                height: 250px;
            }
            
            .profile-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
                margin-top: -80px;
            }
            
            .avatar-section {
                margin-right: 0;
                margin-bottom: 2rem;
            }
            
            .avatar-wrapper {
                width: 150px;
                height: 150px;
            }
            
            .profile-name {
                font-size: 2rem;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
            }
        }

        /* Thêm animation cho các phần tử */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive header */
        @media (max-width: 768px) {
            .nav-header {
                padding: 1rem;
            }

            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
                gap: 1rem;
            }

            .nav-link {
                font-size: 1rem;
                padding: 0.5rem 0.8rem;
            }
        }

        /* Sidebar styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 80px;
            background: #ffffff;
            border-right: 1px solid #eaeaea;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .menu-item {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }

        .menu-item a {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px;
            width: 90%;
            text-decoration: none;
            color: #666;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .menu-item a:hover {
            background: #f5f5f5;
            color: #6366f1;
            transform: translateY(-2px);
        }

        .menu-item a.active {
            color: #6366f1;
            background: #f0f0ff;
        }

        .menu-item i {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .menu-item span {
            font-size: 12px;
            text-align: center;
        }

        /* Style cho nút Tạo */
        .create-btn {
            color: #6366f1 !important;
            margin-bottom: 20px;
        }

        .create-btn i {
            font-size: 28px;
        }

        /* Hover effect */
        .menu-item a:hover {
            background: rgba(99, 102, 241, 0.1);
        }

        /* Active state */
        .menu-item a.active {
            position: relative;
        }

        .menu-item a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            background: #6366f1;
            border-radius: 0 3px 3px 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                bottom: 0;
                top: auto;
                width: 100%;
                height: 60px;
                flex-direction: row;
                padding: 0;
                justify-content: space-around;
                border-top: 1px solid #eaeaea;
            }

            .menu-item {
                margin: 0;
                width: auto;
            }

            .menu-item a {
                padding: 8px;
            }

            .menu-item span {
                font-size: 10px;
            }

            .menu-item i {
                font-size: 20px;
                margin-bottom: 2px;
            }

            .menu-item a.active::before {
                left: 50%;
                top: 0;
                transform: translateX(-50%);
                width: 20px;
                height: 3px;
            }
        }

        /* Điều chỉnh main content để không bị che bởi sidebar */
        main {
            margin-left: 80px;
            padding: 20px;
        }

        @media (max-width: 768px) {
            main {
                margin-left: 0;
                margin-bottom: 60px;
            }
        }

        .logo {
            font-family: "Playball", cursive;
            font-weight: 700;
            font-style: normal;
            font-size: 2rem;
            color: #3b82f6;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header class="nav-header">
        <div class="nav-container">
            <a href="/" class="logo">Webs</a>
            <nav class="nav-links">
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="bi bi-house-door"></i> Trang chủ
                </a>
                <a href="template-user.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'template-user.php' ? 'active' : ''; ?>">
                    <i class="bi bi-grid"></i> Thiết kế
                </a>
                <a href="logout.php" class="nav-link logout">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </a>
            </nav>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-header">
            <div class="cover-photo"></div>
            <div class="profile-info">
                <div class="avatar-wrapper">
                    <img src="<?php echo htmlspecialchars($user['Avatar'] ?? 'images/default-avatar.png'); ?>" 
                         alt="Avatar" class="profile-avatar">
                    <label for="avatar-input" class="avatar-upload">
                        <i class="bi bi-camera"></i>
                    </label>
                    <input type="file" id="avatar-input" name="avatar" 
                           accept="image/jpeg,image/png,image/gif" style="display: none">
                </div>
                <div class="profile-details">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['Username']); ?></h1>
                    <div class="profile-username">@<?php echo htmlspecialchars($user['Username']); ?></div>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Thiết kế</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Người theo dõi</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Đang theo dõi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-content">
            <div class="profile-sidebar">
                <h2 class="section-title">Thông tin cá nhân</h2>
                <div class="bio-text">
                    <?php echo htmlspecialchars($user['Bio'] ?? 'Chưa có thông tin giới thiệu.'); ?>
                </div>
            </div>

            <div class="profile-main">
                <h2 class="section-title">Cập nhật thông tin</h2>
                
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($error_message)): ?>
                    <div class="alert alert-error">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div id="upload-message" class="alert" style="display: none;"></div>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Giới thiệu bản thân</label>
                        <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($user['Bio'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" name="update_profile" class="btn-update">
                        Cập nhật thông tin
                    </button>
                </form>

                <div class="recent-activities">
                    <h2 class="section-title">Hoạt động gần đây</h2>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-title">Đã tạo thiết kế mới</div>
                            <div class="activity-time">2 giờ trước</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const avatarInput = document.getElementById('avatar-input');
        const profileAvatar = document.querySelector('.profile-avatar');
        const uploadMessage = document.getElementById('upload-message');
        
        avatarInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Kiểm tra kích thước file
                if (file.size > 5 * 1024 * 1024) {
                    showMessage('Kích thước file không được vượt quá 5MB', 'error');
                    return;
                }
                
                // Tạo form data
                const formData = new FormData();
                formData.append('avatar', file);
                
                // Hiển thị preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    profileAvatar.src = e.target.result;
                }
                reader.readAsDataURL(file);
                
                // Upload file
                fetch('profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Cập nhật ảnh đại diện thành công!', 'success');
                        profileAvatar.src = data.avatar_url;
                    } else {
                        showMessage(data.error, 'error');
                    }
                })
                .catch(error => {
                    showMessage('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
                    console.error('Error:', error);
                });
            }
        });
        
        function showMessage(message, type) {
            uploadMessage.textContent = message;
            uploadMessage.className = 'alert alert-' + (type === 'success' ? 'success' : 'error');
            uploadMessage.style.display = 'block';
            
            // Ẩn message sau 3 giây
            setTimeout(() => {
                uploadMessage.style.display = 'none';
            }, 3000);
        }
    });
    </script>
</body>
</html>