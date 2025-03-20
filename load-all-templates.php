<?php
require_once 'connect_db.php';

try {
    $sql = "SELECT t.*, u.Username 
            FROM templates t
            INNER JOIN users u ON t.UserID = u.UserID 
            WHERE t.Status = 'Approved' 
            ORDER BY t.CreatedDate DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
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
                            <?php echo number_format($row['Views'] ?? 0); ?>
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