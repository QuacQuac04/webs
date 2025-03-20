<?php
require_once 'connect_db.php';
session_start();

$currentUserId = $_SESSION['UserID'] ?? null;
$sortType = $_POST['sort'] ?? 'newest';

try {
    $sql = "SELECT t.*, u.Username,
            COALESCE(t.Likes, 0) as LikeCount,
            CASE WHEN tl.UserID IS NOT NULL THEN 1 ELSE 0 END as IsLiked
            FROM templates t
            INNER JOIN users u ON t.UserID = u.UserID
            LEFT JOIN template_likes tl ON t.TemplateID = tl.TemplateID 
                AND tl.UserID = :currentUserId
            WHERE t.Status = 'Approved'";
    
    switch($sortType) {
        case 'popular':
            $sql .= " ORDER BY t.Likes DESC, t.Views DESC";
            break;
        case 'trending':
            $sql .= " AND t.CreatedDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                     ORDER BY (t.Views + t.Likes) DESC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY t.CreatedDate DESC";
            break;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':currentUserId' => $currentUserId]);
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        include 'template-card.php';
    }
    
} catch(Exception $e) {
    echo "<div class='error-message'>Có lỗi xảy ra: " . $e->getMessage() . "</div>";
}
?>