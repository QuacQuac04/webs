<?php
session_start();
require_once 'connect_db.php';

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $templateName = $_POST['templateName'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    
    // Handle file upload
    $previewImage = '';
    if (isset($_FILES['previewImage']) && $_FILES['previewImage']['error'] == 0) {
        $uploadDir = 'uploads/previews/';
        $previewImage = $uploadDir . uniqid() . '_' . $_FILES['previewImage']['name'];
        move_uploaded_file($_FILES['previewImage']['tmp_name'], $previewImage);
    }

    // Handle HTML, CSS, JS content
    $htmlContent = $_POST['htmlContent'];
    $cssContent = $_POST['cssContent'];
    $jsContent = $_POST['jsContent'];

    try {
        $sql = "INSERT INTO Templates (UserID, TemplateName, Description, PreviewImage, Price, Category, HTMLContent, CSSContent, JSContent) 
                VALUES (:userID, :templateName, :description, :previewImage, :price, :category, :htmlContent, :cssContent, :jsContent)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':userID' => $_SESSION['UserID'],
            ':templateName' => $templateName,
            ':description' => $description,
            ':previewImage' => $previewImage,
            ':price' => $price,
            ':category' => $category,
            ':htmlContent' => $htmlContent,
            ':cssContent' => $cssContent,
            ':jsContent' => $jsContent
        ]);

        header('Location: my-designs.php?success=1');
        exit();
    } catch(PDOException $e) {
        $error = "Upload failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Template - Webs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #4f46e5;
            --background-color: #f9fafb;
            --border-color: #e5e7eb;
            --text-color: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.7;
            min-height: 100vh;
            padding: 2rem;
        }

        .upload-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            padding: 3rem;
        }

        h1 {
            text-align: center;
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.75rem;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .code-editor {
            font-family: 'Fira Code', monospace;
            height: 300px;
            background: #1e1e1e;
            color: #fff;
            padding: 1.25rem;
            border-radius: 12px;
            font-size: 0.95rem;
            line-height: 1.6;
            resize: vertical;
            border: none;
        }

        .file-upload {
            width: 100%;
        }

        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            background: #f3f4f6;
            border: 2px dashed var(--primary-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            background: #eef2ff;
            transform: translateY(-2px);
        }

        .file-upload-label i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .submit-btn {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .upload-container {
                padding: 1.5rem;
            }

            body {
                padding: 1rem;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1><i class="bi bi-cloud-upload"></i> Upload Template</h1>
        
        <?php if(isset($error)): ?>
            <div class="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label for="templateName">
                        <i class="bi bi-type"></i> Template Name
                    </label>
                    <input type="text" id="templateName" name="templateName" required placeholder="Enter a creative name for your template">
                </div>
                
                <div class="form-group">
                    <label for="category">
                        <i class="bi bi-folder"></i> Category
                    </label>
                    <select id="category" name="category" required>
                        <option value="">Choose a category</option>
                        <option value="business">Business</option>
                        <option value="education">Education</option>
                        <option value="shopping">Shopping</option>
                        <option value="portfolio">Portfolio</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="description">
                        <i class="bi bi-text-paragraph"></i> Description
                    </label>
                    <textarea id="description" name="description" rows="4" required placeholder="Write a detailed description of your template"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">
                        <i class="bi bi-tag"></i> Price
                    </label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required placeholder="Set your price">
                </div>
                
                <div class="form-group">
                    <label for="previewImage">
                        <i class="bi bi-image"></i> Preview Image
                    </label>
                    <div class="file-upload">
                        <label for="previewImage" class="file-upload-label">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <div>Drop your image here or click to browse</div>
                            <div style="font-size: 0.85rem; color: #666; margin-top: 0.5rem;">Supported formats: JPG, PNG</div>
                        </label>
                        <input type="file" id="previewImage" name="previewImage" accept="image/*" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="htmlContent">
                        <i class="bi bi-code-slash"></i> HTML Content
                    </label>
                    <textarea id="htmlContent" name="htmlContent" class="code-editor" required placeholder="<!-- Enter your HTML code here -->"></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label for="cssContent">
                        <i class="bi bi-brush"></i> CSS Content
                    </label>
                    <textarea id="cssContent" name="cssContent" class="code-editor" required placeholder="/* Enter your CSS code here */"></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label for="jsContent">
                        <i class="bi bi-braces"></i> JavaScript Content
                    </label>
                    <textarea id="jsContent" name="jsContent" class="code-editor" placeholder="// Enter your JavaScript code here (optional)"></textarea>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="bi bi-cloud-upload"></i> Upload Template
            </button>
        </form>
    </div>
</body>
</html>