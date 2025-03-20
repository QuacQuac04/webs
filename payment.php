<?php
require_once 'connect_db.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit();
}

$userID = $_SESSION['UserID'];
$templateID = isset($_GET['template_id']) ? $_GET['template_id'] : null;

// Kiểm tra xem người dùng đã mua template này chưa
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM purchases 
    WHERE UserID = ? AND TemplateID = ? AND Status = 'completed'
");
$stmt->execute([$userID, $templateID]);
$existingPurchase = $stmt->fetch();

if ($existingPurchase['count'] > 0) {
    die('Bạn đã mua template này rồi');
}

// Lấy thông tin template
$stmt = $conn->prepare("SELECT t.*, u.Username FROM templates t 
                       JOIN users u ON t.UserID = u.UserID 
                       WHERE t.TemplateID = ?");
$stmt->execute([$templateID]);
$template = $stmt->fetch();

// Thêm ngay sau khi lấy template ID
if (!$templateID) {
    die('Template ID không hợp lệ');
}

// Thêm sau khi query template
if (!$template) {
    die('Không tìm thấy template');
}

// Tạo mã giao dịch ngẫu nhiên
$transactionID = uniqid('PAY_');

// Thêm đoạn code này sau khi tạo $transactionID
try {
    // Lưu thông tin giao dịch vào bảng purchases với trạng thái pending
    $stmt = $conn->prepare("INSERT INTO purchases (UserID, TemplateID, TransactionID, Amount, Status) 
                           VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $userID,
        $templateID,
        $transactionID,
        $template['Price']
    ]);

    if (!$stmt->rowCount()) {
        die('Không thể tạo giao dịch');
    }
} catch (Exception $e) {
    die('Lỗi: ' . $e->getMessage());
}

// Lấy phương thức thanh toán đã lưu
$stmt = $conn->prepare("SELECT * FROM payment_methods WHERE UserID = ?");
$stmt->execute([$userID]);
$savedCards = $stmt->fetchAll();

// Thêm vào đầu file payment.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Webs</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #0066ff;
            --secondary: #5900ff;
            --success: #10b981;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.5;
        }

        .payment-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }

        .payment-details {
            grid-column: 2; /* Đặt vào cột thứ 2 */
        }

        .payment-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }

        .template-preview {
            text-align: center;
            margin-bottom: 30px;
        }

        .template-preview img {
            max-width: 100%;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .payment-info {
            border-top: 2px solid var(--border);
            padding-top: 20px;
        }

        .price-tag {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 20px 0;
            text-align: center;
        }

        .qr-section {
            text-align: center;
            padding: 30px;
            background: #f8fafc;
            border-radius: 16px;
            margin: 20px 0;
        }

        .qr-code {
            max-width: 300px;
            margin: 20px auto;
        }

        .bank-info {
            text-align: left;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .bank-info p {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed var(--border);
        }

        .bank-info p:last-child {
            border-bottom: none;
        }

        .copy-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-left: 10px;
        }

        .status-section {
            text-align: center;
            margin-top: 30px;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: #fff;
            border-radius: 100px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .status-dot {
            width: 12px;
            height: 12px;
            background: #fbbf24;
            border-radius: 50%;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0% { opacity: 0.4; }
            50% { opacity: 1; }
            100% { opacity: 0.4; }
        }

        .timer {
            font-size: 1.2rem;
            font-weight: 600;
            margin-top: 15px;
            color: var(--text-secondary);
        }

        .proof-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
        }

        .text-muted {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 4px;
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: var(--secondary);
        }

        @media (max-width: 768px) {
            .payment-grid {
                grid-template-columns: 1fr;
            }
            
            .payment-details {
                grid-column: 1;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-grid">
            <div class="payment-card">
                <div class="template-preview">
                    <img src="<?php echo htmlspecialchars($template['PreviewImage']); ?>" 
                         alt="<?php echo htmlspecialchars($template['TemplateName']); ?>">
                    <h2><?php echo htmlspecialchars($template['TemplateName']); ?></h2>
                    <p>Thiết kế bởi <?php echo htmlspecialchars($template['Username']); ?></p>
                </div>
                <div class="payment-info">
                    <div class="price-tag">
                        <?php echo number_format($template['Price']); ?>đ
                    </div>
                </div>
            </div>

            <div class="payment-details">
                <div class="payment-card">
                    <h2>Quét mã để thanh toán</h2>
                    <div class="qr-section">
                        <?php
                        $amount = $template['Price'];
                        $content = $transactionID;
                        $vietqrUrl = "https://img.vietqr.io/image/MB-0383116204-compact.png?amount={$amount}&addInfo={$content}&accountName=DINH%20VAN%20HIEU";
                        ?>
                        
                        <img src="<?php echo htmlspecialchars($vietqrUrl); ?>" 
                             alt="QR Code" 
                             class="qr-code">
                        
                        <div class="bank-info">
                            <p>
                                <span>Số tài khoản:</span>
                                <span>
                                    0383116204
                                    <button class="copy-button" onclick="copyText('0383116204')">Copy</button>
                                </span>
                            </p>
                            <p>
                                <span>Chủ tài khoản:</span>
                                <span>DINH VAN HIEU</span>
                            </p>
                            <p>
                                <span>Ngân hàng:</span>
                                <span>MB BANK</span>
                            </p>
                            <p>
                                <span>Nội dung chuyển khoản:</span>
                                <span>
                                    <?php echo $transactionID; ?>
                                    <button class="copy-button" onclick="copyText('<?php echo $transactionID; ?>')">Copy</button>
                                </span>
                            </p>
                        </div>

                        <div class="status-section">
                            <div class="status-indicator">
                                <div class="status-dot"></div>
                                <span>Đang chờ thanh toán</span>
                            </div>
                            <div class="timer" id="timer">15:00</div>
                        </div>

                        <div class="proof-section">
                            <h3>Xác nhận thanh toán</h3>
                            <form id="paymentProofForm" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="transaction_id" value="<?php echo $transactionID; ?>">
                                <div class="form-group">
                                    <label class="form-label">Upload ảnh chuyển khoản</label>
                                    <input type="file" class="form-control" name="payment_proof" accept="image/*" required>
                                    <small class="text-muted">Vui lòng chụp màn hình hoặc ảnh biên lai chuyển khoản</small>
                                </div>
                                <button type="submit" class="submit-btn">
                                    <i class="bi bi-upload"></i>
                                    Xác nhận thanh toán
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function copyText(text) {
        navigator.clipboard.writeText(text)
            .then(() => {
                Swal.fire({
                    title: 'Đã sao chép!',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            })
            .catch(err => console.error('Lỗi khi sao chép:', err));
    }

    // Đếm ngược 15 phút
    let timeLeft = 15 * 60;
    const timerElement = document.getElementById('timer');

    const timer = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            clearInterval(timer);
            timerElement.textContent = 'Hết hạn';
            Swal.fire({
                title: 'Hết thời gian thanh toán',
                text: 'Vui lòng tạo giao dịch mới',
                icon: 'warning',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.reload();
            });
        }
        timeLeft--;
    }, 1000);

    // Thay thế phần checkPaymentStatus trong file payment.php
    function checkPaymentStatus() {
        console.log('Checking payment status...');
        fetch('check_payment.php?transaction_id=<?php echo $transactionID; ?>')
            .then(response => response.json())
            .then(data => {
                console.log('Payment check response:', data);
                if (data.status === 'completed') {
                    clearInterval(timer);
                    clearInterval(checkInterval);
                    Swal.fire({
                        title: 'Thanh toán thành công!',
                        text: 'Đang chuẩn bị tải template...',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        window.location.href = `download.php?template_id=<?php echo $templateID; ?>`;
                    });
                } else if (data.status === 'error') {
                    console.log('Payment check error:', data.message);
                    // Không hiển thị lỗi cho người dùng, chỉ log
                } else {
                    console.log('Payment still pending:', data.message);
                }
            })
            .catch(error => {
                console.log('Connection error, will retry...', error);
                // Không hiển thị lỗi cho người dùng
            });
    }

    // Giảm tần suất kiểm tra xuống 5 giây
    const checkInterval = setInterval(checkPaymentStatus, 5000);

    // Kiểm tra ngay khi trang load xong
    document.addEventListener('DOMContentLoaded', checkPaymentStatus);

    // Dừng kiểm tra khi rời trang
    window.addEventListener('beforeunload', () => {
        clearInterval(checkInterval);
        clearInterval(timer);
    });

    document.getElementById('paymentProofForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('submit_payment_proof.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    title: 'Đã gửi xác nhận!',
                    text: 'Chúng tôi sẽ xử lý trong thời gian sớm nhất',
                    icon: 'success',
                    showConfirmButton: true
                }).then(() => {
                    window.location.href = 'index.php';
                });
            } else {
                Swal.fire({
                    title: 'Có lỗi!',
                    text: data.message,
                    icon: 'error'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Có lỗi!',
                text: 'Không thể gửi xác nhận thanh toán',
                icon: 'error'
            });
        });
    });
    </script>
</body>
</html>