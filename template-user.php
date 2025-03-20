<?php
require_once 'connect_db.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

// Lấy template hiện tại nếu có
$templateID = $_GET['id'] ?? null;
if ($templateID) {
    $stmt = $pdo->prepare("
        SELECT * FROM templates 
        WHERE TemplateID = ? AND UserID = ?
    ");
    $stmt->execute([$templateID, $_SESSION['UserID']]);
    $currentTemplate = $stmt->fetch();
    
    if ($currentTemplate) {
        $templateContent = $currentTemplate['HTMLContent'];
        $templateStyles = json_decode($currentTemplate['Styles'], true);
    }
}

// Giả sử UserID được lưu trong session sau khi đăng nhập
$userID = $_SESSION['UserID'] ?? 1;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Designer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="C:\xampp\htdocs/template-user.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playball&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --secondary-color: #0ea5e9;
            --sidebar-bg: #ffffff;
            --border-color: #e5e7eb;
            --text-color: #1f2937;
            --text-secondary: #64748b;
            --bg-gray: #f9fafb;
            --bg-white: #ffffff;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --header-height: 60px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-white);
            color: var(--text-color);
            line-height: 1.5;
            height: 100vh;
            overflow: hidden;
        }

        .app-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Top Navigation */
        .top-nav {
            height: 48px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 16px;
            background: var(--bg-white);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .nav-menu {
            display: flex;
            margin-left: 24px;
            gap: 4px;
        }

        .dropdown-btn {
            padding: 6px 12px;
            background: none;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
        }

        .dropdown-btn:hover {
            background: var(--bg-gray);
        }

        .nav-title {
            margin: 0 auto;
            font-size: 13px;
        }

        .nav-actions {
            display: flex;
            gap: 8px;
        }

        .btn-share,
        .btn-preview {
            padding: 6px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--bg-white);
            font-size: 13px;
            cursor: pointer;
        }

        /* Toolbar */
        .toolbar {
            height: 40px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 8px;
            background: var(--bg-white);
        }

        .tool-group {
            display: flex;
            gap: 1px;
            background: var(--bg-gray);
            padding: 2px;
            border-radius: 4px;
        }

        .tool-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tool-btn.active {
            background: var(--bg-white);
            box-shadow: var(--shadow-sm);
        }

        .zoom-controls {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .zoom-btn {
            width: 24px;
            height: 24px;
            border: 1px solid var(--border-color);
            background: var(--bg-white);
            border-radius: 4px;
            cursor: pointer;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: grid;
            grid-template-columns: 280px minmax(400px, 1fr) 280px;
            height: calc(100vh - 88px);
            overflow: hidden;
        }

        /* Sidebars */
        .sidebar {
            background: var(--bg-white);
            width: 280px;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow-y: auto;
        }

        .right-sidebar {
            border-right: none;
            border-left: 1px solid var(--border-color);
        }

        /* Sidebar Tabs */
        .sidebar-tabs {
            display: flex;
            padding: 4px;
            gap: 2px;
            background: var(--bg-gray);
            border-radius: 6px;
            margin: 8px;
        }

        .tab-btn {
            flex: 1;
            padding: 6px 12px;
            border: none;
            background: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
        }

        .tab-btn.active {
            background: var(--bg-white);
            box-shadow: var(--shadow-sm);
        }

        /* Search */
        .search-container {
            padding: 8px;
        }

        .search-input {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 13px;
        }

        /* Elements Section */
        .elements-section {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }

        .elements-section h3 {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .element-list {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 10px;
            margin-bottom: 24px;
        }

        .element-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            cursor: move;
            transition: all 0.2s ease;
        }

        .element-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-1px);
        }

        .element-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--primary-color);
        }

        .element-item span {
            font-size: 13px;
            color: var(--text-color);
        }

        /* Canvas Area */
        .canvas-area {
            background: var(--bg-gray);
            overflow: auto;
            padding: 24px;
            position: relative;
            height: 100%;
            display: flex;
            justify-content: center;
        }

        .canvas {
            background: var(--bg-white);
            width: 800px;
            height: 100%;
            min-height: 800px;
            position: relative;
            box-shadow: var(--shadow-lg);
            border-radius: 8px;
            margin: 0 auto;
        }

        /* Style Editor */
        .style-editor {
            padding: 20px;
            height: 100%;
            overflow-y: auto;
        }

        .editor-tabs {
            display: flex;
            gap: 2px;
            background: var(--bg-gray);
            padding: 2px;
            border-radius: 4px;
            margin-bottom: 16px;
        }

        .editor-section {
            margin-bottom: 24px;
        }

        .editor-section h3 {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .input-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .input-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .input-field label {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .input-field input {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 13px;
        }

        /* Typography Controls */
        .font-select {
            width: 100%;
            padding: 8px;
            margin-bottom: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .font-controls {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }

        .font-btn {
            padding: 6px 12px;
            border: 1px solid var(--border-color);
            background: var(--bg-white);
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
        }

        .font-size {
            width: 60px;
            padding: 6px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 13px;
        }

        /* Color Controls */
        .color-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .color-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .color-field input {
            width: 100%;
            height: 32px;
            padding: 2px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .color-field label {
            font-size: 12px;
            color: #6b7280;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        /* Header Styles */
        .app-header {
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--primary-color);
        }

        .logo i {
            font-size: 24px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .save-status {
            font-size: 0.875rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary {
            background: #fff;
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .btn-secondary:hover {
            background: var(--bg-gray);
            border-color: #cbd5e1;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        /* Layout Elements Special Styling */
        .element-item[data-type="section"],
        .element-item[data-type="container"],
        .element-item[data-type="grid"] {
            background: #f8fafc;
        }

        /* Element Type Colors */
        .element-item[data-type="section"] i { color: #3b82f6; }
        .element-item[data-type="container"] i { color: #0ea5e9; }
        .element-item[data-type="grid"] i { color: #06b6d4; }
        .element-item[data-type="text"] i { color: #8b5cf6; }
        .element-item[data-type="heading"] i { color: #6366f1; }
        .element-item[data-type="button"] i { color: #ec4899; }
        .element-item[data-type="image"] i { color: #14b8a6; }
        .element-item[data-type="link"] i { color: #f59e0b; }

        /* Dragging State */
        .element-item.dragging {
            opacity: 0.5;
            transform: scale(0.95);
        }

        /* Component Items */
        .component-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .component-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: move;
            transition: all 0.2s ease;
        }

        .component-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .component-preview {
            width: 100%;
            height: 80px;
            background: var(--bg-gray);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .component-preview i {
            font-size: 24px;
            color: var(--primary-color);
        }

        .component-item span {
            font-size: 12px;
            color: var(--text-color);
            text-align: center;
        }

        /* Templates Grid */
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .template-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .template-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .template-info {
            padding: 12px;
        }

        .template-info h3 {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .template-info p {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .canvas-header {
            background: #fff;
            padding: 12px;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .canvas-tools {
            display: flex;
            gap: 12px;
        }

        .canvas-container {
            background: #fff;
            border-radius: 12px;
            min-height: calc(100vh - 180px);
            box-shadow: var(--shadow-lg);
            position: relative;
        }

        /* Design Elements */
        .design-element {
            position: absolute;
            min-width: 50px;
            min-height: 30px;
            cursor: move;
            background: white;
        }

        .design-element.selected {
            outline: 2px solid var(--primary-color);
        }

        /* Resizer Points */
        .resizer {
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            border: 1px solid white;
            position: absolute;
            border-radius: 50%;
            pointer-events: all;
            z-index: 100;
        }

        .resizer.nw { top: -4px; left: -4px; cursor: nw-resize; }
        .resizer.ne { top: -4px; right: -4px; cursor: ne-resize; }
        .resizer.sw { bottom: -4px; left: -4px; cursor: sw-resize; }
        .resizer.se { bottom: -4px; right: -4px; cursor: se-resize; }

        /* Selected Element Styles */
        .design-element.selected {
            outline: 2px solid var(--primary-color);
        }

        /* Resizer Styles */
        .resizer {
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            position: absolute;
            border-radius: 50%;
        }

        .resizer.nw {
            top: -4px;
            left: -4px;
            cursor: nw-resize;
        }

        .resizer.ne {
            top: -4px;
            right: -4px;
            cursor: ne-resize;
        }

        .resizer.sw {
            bottom: -4px;
            left: -4px;
            cursor: sw-resize;
        }

        .resizer.se {
            bottom: -4px;
            right: -4px;
            cursor: se-resize;
        }

        /* Element Type Specific Styles */
        .text-element {
            min-width: 100px;
            min-height: 24px;
            padding: 8px;
        }

        .heading-element {
            min-width: 200px;
            min-height: 32px;
            font-size: 24px;
            font-weight: bold;
            padding: 8px;
        }

        .button-element {
            min-width: 120px;
            min-height: 40px;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            cursor: pointer;
            padding: 8px 16px;
        }

        .container-element {
            min-width: 300px;
            min-height: 200px;
            border: 2px dashed var(--border-color);
            padding: 16px;
            background: rgba(0,0,0,0.02);
        }

        /* Editable Content Styles */
        [contenteditable="true"]:focus {
            outline: none;
        }

        /* Image Upload Placeholder */
        .image-element {
            min-width: 200px;
            min-height: 150px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed var(--border-color);
            cursor: pointer;
        }

        .image-element i {
            font-size: 48px;
            color: var(--text-secondary);
        }

        /* Thêm animation cho trạng thái lưu */
        @keyframes saving {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .save-status:not(:empty) {
            animation: saving 1.5s infinite;
        }

        /* Element Grid Layout */
        .element-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .element-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        /* Component Items */
        .component-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: move;
            background: white;
        }

        .component-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
        }

        .component-item span {
            font-size: 14px;
            font-weight: 500;
        }

        /* Templates Grid */
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            padding: 8px;
        }

        .template-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .template-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .template-preview {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .template-info {
            padding: 12px;
        }

        .template-info h4 {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .template-info p {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .nav-brand a span{
            font-family: "Playball", cursive;
            font-weight: 700;
            font-style: normal;
            font-size: 23px;
        }

        /* Make sure Bootstrap Icons are loaded */
        @import url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css");
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('canvas');
        const elementItems = document.querySelectorAll('.element-item');
        let selectedElement = null;

        // Xử lý kéo các elements
        elementItems.forEach(item => {
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragend', handleDragEnd);
        });

        // Xử lý thả vào canvas
        canvas.addEventListener('dragover', handleDragOver);
        canvas.addEventListener('drop', handleDrop);

        // Xử lý click để select element
        document.addEventListener('mousedown', function(e) {
            const element = e.target.closest('.design-element');
            if (element) {
                selectElement(element);
            } else if (!e.target.closest('.resizer')) {
                deselectAll();
            }
        });

        function selectElement(element) {
            deselectAll();
            selectedElement = element;
            element.classList.add('selected');
            updateStyleControls(element);
        }

        function deselectAll() {
            document.querySelectorAll('.design-element').forEach(el => {
                el.classList.remove('selected');
            });
            selectedElement = null;
        }

        function handleDragStart(e) {
            e.dataTransfer.setData('text/plain', this.querySelector('span').textContent);
        }

        function handleDragEnd(e) {}

        function handleDragOver(e) {
            e.preventDefault();
        }

        function handleDrop(e) {
            e.preventDefault();
            const elementType = e.dataTransfer.getData('text/plain').toLowerCase();
            
            // Tính toán vị trí tương đối trong canvas
            const canvasRect = canvas.getBoundingClientRect();
            const x = e.clientX - canvasRect.left;
            const y = e.clientY - canvasRect.top;
            
            const newElement = createNewElement(elementType);
            newElement.style.position = 'absolute';
            newElement.style.left = x + 'px';
            newElement.style.top = y + 'px';
            
            canvas.appendChild(newElement);
            makeElementDraggable(newElement);
            makeElementResizable(newElement);
            selectElement(newElement);
        }

        function createNewElement(type) {
            const element = document.createElement('div');
            element.className = `design-element ${type}-element`;
            element.style.position = 'absolute';
            element.style.minWidth = '100px';
            element.style.minHeight = '50px';

            switch(type) {
                case 'text':
                    element.contentEditable = true;
                    element.textContent = 'Double click to edit text';
                    element.style.padding = '8px';
                    break;
                case 'heading':
                    element.contentEditable = true;
                    element.textContent = 'Heading';
                    element.style.fontSize = '24px';
                    element.style.fontWeight = 'bold';
                    element.style.padding = '8px';
                    break;
                case 'section':
                    element.style.width = '100%';
                    element.style.height = '200px';
                    element.style.border = '2px dashed #e5e7eb';
                    break;
                case 'container':
                    element.style.width = '300px';
                    element.style.height = '200px';
                    element.style.border = '2px dashed #e5e7eb';
                    break;
                case 'grid':
                    element.style.width = '300px';
                    element.style.height = '200px';
                    element.style.display = 'grid';
                    element.style.gridTemplateColumns = 'repeat(2, 1fr)';
                    element.style.gap = '8px';
                    element.style.border = '2px dashed #e5e7eb';
                    break;
            }

            // Thêm các điểm resize
            addResizeHandles(element);
            
            return element;
        }

        function makeElementDraggable(element) {
            let isDragging = false;
            let currentX;
            let currentY;
            let initialX;
            let initialY;
            let xOffset = 0;
            let yOffset = 0;

            element.addEventListener('mousedown', dragStart);

            function dragStart(e) {
                if (e.target === element) {
                    initialX = e.clientX - xOffset;
                    initialY = e.clientY - yOffset;

                    isDragging = true;
                    document.addEventListener('mousemove', drag);
                    document.addEventListener('mouseup', dragEnd);
                }
            }

            function drag(e) {
                if (isDragging) {
                    e.preventDefault();
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;

                    xOffset = currentX;
                    yOffset = currentY;

                    setTranslate(currentX, currentY, element);
                }
            }

            function dragEnd(e) {
                initialX = currentX;
                initialY = currentY;
                isDragging = false;

                document.removeEventListener('mousemove', drag);
                document.removeEventListener('mouseup', dragEnd);
            }

            function setTranslate(xPos, yPos, el) {
                el.style.left = xPos + 'px';
                el.style.top = yPos + 'px';
            }
        }

        function makeElementResizable(element) {
            const resizers = element.querySelectorAll('.resizer');
            let isResizing = false;
            let currentResizer;

            resizers.forEach(resizer => {
                resizer.addEventListener('mousedown', initResize);
            });

            function initResize(e) {
                isResizing = true;
                currentResizer = e.target;

                let prevX = e.clientX;
                let prevY = e.clientY;

                window.addEventListener('mousemove', resize);
                window.addEventListener('mouseup', stopResize);

                function resize(e) {
                    if (!isResizing) return;

                    const rect = element.getBoundingClientRect();
                    const deltaX = e.clientX - prevX;
                    const deltaY = e.clientY - prevY;

                    if (currentResizer.classList.contains('se')) {
                        element.style.width = rect.width + deltaX + 'px';
                        element.style.height = rect.height + deltaY + 'px';
                    } else if (currentResizer.classList.contains('sw')) {
                        element.style.width = rect.width - deltaX + 'px';
                        element.style.height = rect.height + deltaY + 'px';
                        element.style.left = rect.left + deltaX + 'px';
                    } else if (currentResizer.classList.contains('ne')) {
                        element.style.width = rect.width + deltaX + 'px';
                        element.style.height = rect.height - deltaY + 'px';
                        element.style.top = rect.top + deltaY + 'px';
                    } else if (currentResizer.classList.contains('nw')) {
                        element.style.width = rect.width - deltaX + 'px';
                        element.style.height = rect.height - deltaY + 'px';
                        element.style.top = rect.top + deltaY + 'px';
                        element.style.left = rect.left + deltaX + 'px';
                    }

                    prevX = e.clientX;
                    prevY = e.clientY;
                }

                function stopResize() {
                    isResizing = false;
                    window.removeEventListener('mousemove', resize);
                    window.removeEventListener('mouseup', stopResize);
                }
            }
        }

        function addResizeHandles(element) {
            const positions = ['nw', 'ne', 'sw', 'se'];
            positions.forEach(pos => {
                const handle = document.createElement('div');
                handle.className = `resizer ${pos}`;
                handle.style.width = '10px';
                handle.style.height = '10px';
                handle.style.background = '#6366f1';
                handle.style.position = 'absolute';
                handle.style.border = '1px solid white';
                handle.style.borderRadius = '50%';
                
                if (pos.includes('n')) handle.style.top = '-5px';
                if (pos.includes('s')) handle.style.bottom = '-5px';
                if (pos.includes('w')) handle.style.left = '-5px';
                if (pos.includes('e')) handle.style.right = '-5px';
                
                element.appendChild(handle);
            });
        }

        // Thêm style cho các elements được select
        const style = document.createElement('style');
        style.textContent = `
            .design-element {
                position: absolute;
                cursor: move;
            }
            .design-element.selected {
                outline: 2px solid #6366f1;
            }
            .resizer {
                display: none;
                z-index: 100;
            }
            .design-element.selected .resizer {
                display: block;
            }
            .design-element[contenteditable="true"] {
                cursor: text;
            }
        `;
        document.head.appendChild(style);
    });
    </script>
</head>
<body>
    <!-- Main App Container -->
    <div class="app-container">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="nav-brand">
                <a href="index.php" style="text-decoration: none; color: inherit;"><span>Webs</span></a>
            </div>
            <div class="nav-menu">
                <div class="dropdown">
                    <button class="dropdown-btn">File</button>
                </div>
                <div class="dropdown">
                    <button class="dropdown-btn">Edit</button>
                </div>
                <div class="dropdown">
                    <button class="dropdown-btn">View</button>
                </div>
            </div>
            <div class="nav-title">Untitled</div>
            <div class="nav-actions">
                <button class="btn-share">Share</button>
                <button class="btn-preview">▶ Preview</button>
            </div>
        </nav>

        <!-- Tool Bar -->
        <div class="toolbar">
            <div class="tool-group">
                <button class="tool-btn active">✥</button>
                <button class="tool-btn">✋</button>
            </div>
            <div class="tool-group">
                <button class="tool-btn">Aa</button>
            </div>
            <div class="zoom-controls">
                <button class="zoom-btn">-</button>
                <span>100%</span>
                <button class="zoom-btn">+</button>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Left Sidebar -->
            <aside class="sidebar left-sidebar">
                <div class="sidebar-tabs">
                    <button class="tab-btn active">Elements</button>
                    <button class="tab-btn">Components</button>
                    <button class="tab-btn">Templates</button>
                </div>
                
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Tìm kiếm elements...">
                </div>

                <div class="elements-section">
                    <h3>LAYOUT ELEMENTS</h3>
                    <div class="element-list">
                        <div class="element-item" draggable="true">
                            <div class="element-icon">□</div>
                            <span>Section</span>
                        </div>
                        <div class="element-item" draggable="true">
                            <div class="element-icon">▣</div>
                            <span>Container</span>
                        </div>
                        <div class="element-item" draggable="true">
                            <div class="element-icon">▦</div>
                            <span>Grid</span>
                        </div>
                    </div>

                    <h3>BASIC ELEMENTS</h3>
                    <div class="element-list">
                        <div class="element-item" draggable="true">
                            <div class="element-icon">T</div>
                            <span>Text</span>
                        </div>
                        <div class="element-item" draggable="true">
                            <div class="element-icon">H1</div>
                            <span>Heading</span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Canvas Area -->
            <main class="canvas-area">
                <div id="canvas" class="canvas">
                    <!-- Canvas content -->
                </div>
            </main>

            <!-- Right Sidebar -->
            <aside class="sidebar right-sidebar">
                <div class="style-editor">
                    <div class="editor-tabs">
                        <button class="tab-btn active">Style</button>
                        <button class="tab-btn">Layout</button>
                        <button class="tab-btn">Advanced</button>
                    </div>

                    <div class="editor-section">
                        <h3>Dimensions</h3>
                        <div class="input-group">
                            <div class="input-field">
                                <input type="number" value="0">
                                <label>Width</label>
                            </div>
                            <div class="input-field">
                                <input type="number" value="0">
                                <label>Height</label>
                            </div>
                        </div>
                    </div>

                    <div class="editor-section">
                        <h3>Typography</h3>
                        <select class="font-select">
                            <option>Inter</option>
                        </select>
                        <div class="font-controls">
                            <button class="font-btn">B</button>
                            <button class="font-btn">I</button>
                            <button class="font-btn">U</button>
                            <input type="number" class="font-size" value="16">
                        </div>
                    </div>

                    <div class="editor-section">
                        <h3>Colors</h3>
                        <div class="color-inputs">
                            <div class="color-field">
                                <input type="color">
                                <label>Text Color</label>
                            </div>
                            <div class="color-field">
                                <input type="color">
                                <label>Background</label>
                            </div>
                        </div>
                    </div>

                    <div class="editor-section">
                        <h3>Spacing</h3>
                        <div class="input-group">
                            <div class="input-field">
                                <input type="number" value="0">
                                <label>Margin</label>
                            </div>
                            <div class="input-field">
                                <input type="number" value="0">
                                <label>Padding</label>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</body>
</html>