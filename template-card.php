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