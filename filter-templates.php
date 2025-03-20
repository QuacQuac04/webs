<?php
require_once 'connect_db.php';

try {
    $filter = isset($_POST['filter']) ? $_POST['filter'] : 'newest';
    
    // Xây dựng câu truy vấn SQL dựa trên bộ lọc
    $sql = "SELECT t.*, u.Username, COALESCE(t.Views, 0) as ViewCount 
            FROM templates t
            INNER JOIN users u ON t.UserID = u.UserID 
            WHERE t.Status = 'Approved'";
    
    switch($filter) {
        case 'popular':
            $sql .= " ORDER BY ViewCount DESC, t.CreatedDate DESC";
            break;
        case 'trending':
            // Các template có nhiều lượt xem trong 7 ngày qua
            $sql .= " AND t.CreatedDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                     ORDER BY ViewCount DESC, t.CreatedDate DESC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY t.CreatedDate DESC";
            break;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo '<div class="no-results">
                <i class="bi bi-search"></i>
                <p>Không có mẫu template nào</p>
              </div>';
        exit;
    }

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        ?>
        <div class="template-card">
            <img src="<?php echo htmlspecialchars($row['PreviewImage']); ?>" 
                 alt="<?php echo htmlspecialchars($row['TemplateName']); ?>">
            <div class="template-info">
                <h3><?php echo htmlspecialchars($row['TemplateName']); ?></h3>
                <p><?php echo htmlspecialchars($row['Description']); ?></p>
                <div class="template-meta">
                    <span class="author">
                        <i class="bi bi-person"></i>
                        <?php echo htmlspecialchars($row['Username']); ?>
                    </span>
                    <div class="meta-stats">
                        <span>
                            <i class="bi bi-eye"></i>
                            <?php echo number_format($row['ViewCount']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="template-overlay">
                <div class="overlay-buttons">
                    <a href="template-detail.php?id=<?php echo $row['TemplateID']; ?>" 
                       class="btn-view">Xem chi tiết</a>
                </div>
            </div>
        </div>
        <?php
    }

} catch(PDOException $e) {
    echo "<div class='error-message'>Lỗi database: " . $e->getMessage() . "</div>";
}
?> 