<?php
session_start();
require_once 'connect_db.php';

if(isset($_POST['register'])) {
    try {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Kiểm tra password match
        if($password !== $confirm_password) {
            $error = "Mật khẩu không khớp!";
        } else {
            // Kiểm tra username đã tồn tại
            $stmt = $conn->prepare("SELECT * FROM users WHERE Username = ?");
            $stmt->execute([$username]);
            if($stmt->rowCount() > 0) {
                $error = "Tên đăng nhập đã tồn tại!";
            } else {
                // Kiểm tra email đã tồn tại
                $stmt = $conn->prepare("SELECT * FROM users WHERE Email = ?");
                $stmt->execute([$email]);
                if($stmt->rowCount() > 0) {
                    $error = "Email đã được sử dụng!";
                } else {
                    // Thêm user mới
                    $stmt = $conn->prepare("INSERT INTO users (Username, Email, Password, Role) VALUES (?, ?, ?, 'User')");
                    if($stmt->execute([$username, $email, $password])) {
                        header("Location: login.php?registered=true");
                        exit();
                    } else {
                        $error = "Có lỗi xảy ra, vui lòng thử lại!";
                    }
                }
            }
        }
    } catch(PDOException $e) {
        $error = "Lỗi hệ thống: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - WebCraft Community</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playball&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #7c3aed;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --white: #ffffff;
            --error: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .register-container {
            width: 450px;
            margin: auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.1rem;
            z-index: 2;
            transition: color 0.3s ease;
        }

        .form-group input:focus + i {
            color: var(--primary);
        }

        .register-form input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: var(--text-primary);
            background: var(--white);
            position: relative;
            z-index: 1;
        }

        .register-form input:hover {
            border-color: #d1d5db;
        }

        .register-form input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .register-button {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            border: none;
            border-radius: 12px;
            color: var(--white);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .register-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
        }

        .login-link {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .login-link a:hover {
            color: var(--primary-dark);
        }

        .error-message {
            background: #fee2e2;
            color: var(--error);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .password-requirements {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
            padding-left: 3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .password-requirements i {
            font-size: 0.8rem;
            position: static;
            transform: none;
        }

        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        @keyframes inputFocus {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .register-form input:focus {
            animation: inputFocus 0.3s ease;
        }

        @media (max-width: 480px) {
            .register-container {
                width: 90%;
                padding: 2rem;
                margin: 1rem;
            }

            .logo {
                font-size: 2rem;
            }
        }

        .logo {
            font-family: "Playball", cursive;
            font-weight: 700;
            font-style: normal;
            font-size: 2.5rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo-section">
            <div class="logo">Webs</div>
        </div>
        
        <div class="register-header">
            <h1>Tạo tài khoản mới</h1>
            <p>Tham gia cộng đồng Webs ngay hôm nay</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="error-message">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form class="register-form" method="POST" action="">
            <div class="form-group">
                <i class="bi bi-person"></i>
                <input type="text" name="username" placeholder="Tên đăng nhập" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <i class="bi bi-envelope"></i>
                <input type="email" name="email" placeholder="Email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="Mật khẩu" required>
                <i class="bi bi-shield-lock" style="margin-top: -17px"></i>
                <div class="password-strength">
                    <div class="password-strength-bar"></div>
                </div>
                <div class="password-requirements">
                    <i class="bi bi-info-circle"></i>
                    Mật khẩu phải có ít nhất 8 ký tự
                </div>
            </div>

            <div class="form-group">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Xác nhận mật khẩu" required>
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <button type="submit" name="register" class="register-button">
                Đăng ký tài khoản
            </button>
            
            <div class="login-link">
                Đã có tài khoản? <a href="login.php">Đăng nhập</a>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('password').addEventListener('input', function(e) {
        const password = e.target.value;
        const strengthBar = document.querySelector('.password-strength-bar');
        
        let strength = 0;
        if(password.length >= 8) strength += 25;
        if(password.match(/[A-Z]/)) strength += 25;
        if(password.match(/[0-9]/)) strength += 25;
        if(password.match(/[^A-Za-z0-9]/)) strength += 25;
        
        strengthBar.style.width = strength + '%';
        
        if(strength <= 25) {
            strengthBar.style.background = '#dc2626';
        } else if(strength <= 50) {
            strengthBar.style.background = '#f59e0b';
        } else if(strength <= 75) {
            strengthBar.style.background = '#10b981';
        } else {
            strengthBar.style.background = '#059669';
        }
    });
    </script>
</body>
</html> 