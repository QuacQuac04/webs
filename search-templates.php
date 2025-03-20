<?php
require_once 'connect_db.php';
session_start();

$currentUserId = $_SESSION['UserID'] ?? null;

try {
    $query = isset($_POST['query']) ? trim($_POST['query']) : '';
    
    if (empty($query)) {
        throw new Exception('Vui lòng nhập từ khóa tìm kiếm');
    }

    $sql = "SELECT t.*, u.Username,
            COALESCE(t.Likes, 0) as LikeCount,
            CASE WHEN tl.UserID IS NOT NULL THEN 1 ELSE 0 END as IsLiked
            FROM templates t
            INNER JOIN users u ON t.UserID = u.UserID
            LEFT JOIN template_likes tl ON t.TemplateID = tl.TemplateID 
                AND tl.UserID = :currentUserId
            WHERE t.Status = 'Approved'
            AND (t.TemplateName LIKE :query OR t.Description LIKE :query)
            ORDER BY t.CreatedDate DESC";
    
    $stmt = $conn->prepare($sql);
    $searchTerm = "%{$query}%";
    $stmt->execute([
        ':query' => $searchTerm,
        ':currentUserId' => $currentUserId
    ]);
    
    if ($stmt->rowCount() == 0) {
        echo '<div class="no-results">
                <i class="bi bi-search"></i>
                <p>Không tìm thấy kết quả nào cho "' . htmlspecialchars($query) . '"</p>
              </div>';
        exit;
    }
?>
<section class="templates">
    <div class="container">
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
                                <span><?php echo htmlspecialchars($row['Username']); ?></span>
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
    </div>
</section>
<?php
} catch(Exception $e) {
    echo "<div class='error-message'>" . $e->getMessage() . "</div>";
}
?>