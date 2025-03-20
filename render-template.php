<?php
require_once 'connect_db.php';

// Lấy template ID từ URL
$templateId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$templateId) {
    die('Template không tồn tại');
}

// Lấy thông tin template và nội dung
$stmt = $conn->prepare("SELECT HTMLContent, CSSContent, JSContent, Styles FROM templates WHERE TemplateID = ?");
$stmt->execute([$templateId]);
$template = $stmt->fetch();

if (!$template) {
    die('Template không tồn tại');
}

// Decode JSON styles nếu có
$customStyles = '';
if (!empty($template['Styles'])) {
    $stylesArray = json_decode($template['Styles'], true);
    if (is_array($stylesArray)) {
        foreach ($stylesArray as $selector => $properties) {
            $customStyles .= $selector . ' {';
            foreach ($properties as $property => $value) {
                $customStyles .= $property . ': ' . $value . ';';
            }
            $customStyles .= '}';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Template</title>
    
    <!-- CSS mặc định cho các thẻ cơ bản -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }
        
        img {
            max-width: 100%;
            height: auto;
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }
    </style>

    <!-- CSS của template -->
    <style>
        <?php 
        // CSS mặc định từ database
        echo $template['CSSContent']; 
        
        // Custom styles từ JSON
        echo $customStyles;
        ?>
    </style>

    <!-- Thêm các CSS framework phổ biến -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- HTML Content -->
    <?php echo $template['HTMLContent']; ?>

    <!-- JavaScript frameworks phổ biến -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript của template -->
    <script>
        <?php echo $template['JSContent']; ?>
    </script>
</body>
</html> 