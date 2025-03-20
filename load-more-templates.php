<?php
require_once 'connect_db.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8; // Số template trên mỗi trang
$offset = ($page - 1) * $limit;

try {
    $sql = "SELECT cd.*, u.Username, t.TemplateName 
            FROM community_designs cd
            INNER JOIN users u ON cd.UserID = u.UserID 
            INNER JOIN templates t ON cd.TemplateID = t.TemplateID
            ORDER BY cd.CreatedDate DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        ?>
        <div class="template-card">
            <img src="<?php echo htmlspecialchars($row['PreviewImage']); ?>" 
                 alt="<?php echo htmlspecialchars($row['DesignName']); ?>">
            <div class="template-info">
                <h3><?php echo htmlspecialchars($row['DesignName']); ?></h3>
                <p><?php echo htmlspecialchars($row['Description']); ?></p>
                <div class="template-meta">
                    <span class="author">
                        <i class="bi bi-person"></i>
                        <?php echo htmlspecialchars($row['Username']); ?>
                    </span>
                    <div class="meta-stats">
                        <span>
                            <i class="bi bi-eye"></i>
                            <?php echo number_format($row['Views']); ?>
                        </span>
                        <span>
                            <i class="bi bi-heart"></i>
                            <?php echo number_format($row['Likes']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="template-overlay">
                <div class="overlay-buttons">
                    <a href="template-detail.php?id=<?php echo $row['DesignID']; ?>" 
                       class="btn-view">Xem chi tiết</a>
                    <button class="btn-like" data-id="<?php echo $row['DesignID']; ?>">
                        <i class="bi bi-heart"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
} catch(PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?> 