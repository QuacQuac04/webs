<?php
session_start();
require_once 'connect_db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit();
}

$userID = $_SESSION['UserID'];

// Lấy thông tin user bao gồm avatar
function getUserInfo($userID) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT Username, Avatar FROM users WHERE UserID = ?");
        $stmt->execute([$userID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Lỗi lấy thông tin user: " . $e->getMessage());
        return null;
    }
}

$userInfo = getUserInfo($userID);
$userAvatar = $userInfo['Avatar'] ?? 'images/default-avatar.png';
// Avatar cho AI - sử dụng một ảnh cố định
$aiAvatar = 'avatar_ai.png'; // Ảnh AI assistant chuyên nghiệp

// Hàm lấy lịch sử chat
function getChatHistory($userID) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT * FROM design_webs_ai 
            WHERE UserID = ? 
            ORDER BY CreatedAt DESC
        ");
        $stmt->execute([$userID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Lỗi lấy lịch sử chat: " . $e->getMessage());
        return [];
    }
}

// Lấy lịch sử chat trước khi render HTML
$chatHistory = getChatHistory($userID);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Webs Designer</title>
    <style>
        :root {
            --primary-color: #0ea5e9;
            --secondary-color: #0284c7;
            --bg-color: #f8fafc;
            --text-color: #1e293b;
            --border-color: #e2e8f0;
            --hover-color: #f1f5f9;
            --success-color: #22c55e;
            --code-bg: #1e293b;
            --code-text: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            line-height: 1.5;
        }

        .chat-container {
            display: flex;
            height: 100vh;
            background: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid var(--border-color);
            padding: 24px;
            display: flex;
            flex-direction: column;
        }

        .new-chat-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(14, 165, 233, 0.2);
        }

        .new-chat-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-1px);
        }

        .new-chat-btn svg {
            width: 20px;
            height: 20px;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 32px;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }

        .message {
            display: flex;
            padding: 24px;
            gap: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            transition: all 0.2s;
        }

        .message:hover {
            background: var(--hover-color);
        }

        .message.ai {
            background: var(--bg-color);
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
        }

        .message-content {
            flex: 1;
            font-size: 15px;
            line-height: 1.6;
            white-space: pre-wrap;
            max-width: 100%;
            overflow-x: auto;
        }

        .code-block {
            margin: 20px 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            background: var(--code-bg);
        }

        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: #2d3748;
            border-bottom: 1px solid #4a5568;
        }

        .code-lang {
            font-weight: 600;
            color: #a0aec0;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        .copy-btn {
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 500;
            color: #a0aec0;
            background: #374151;
            border: 1px solid #4a5568;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            background: #4a5568;
            color: white;
        }

        .copy-btn.copied {
            background: var(--success-color);
            color: white;
            border-color: var(--success-color);
        }

        pre {
            margin: 0;
            padding: 20px;
            overflow-x: auto;
            font-size: 14px;
            line-height: 1.6;
        }

        code {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            color: var(--code-text);
        }

        .input-container {
            padding: 24px;
            background: white;
            border-top: 1px solid var(--border-color);
        }

        .input-box {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            gap: 12px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        textarea {
            flex: 1;
            border: none;
            padding: 8px;
            font-size: 15px;
            min-height: 40px;
            resize: none;
            font-family: inherit;
        }

        textarea:focus {
            outline: none;
        }

        .send-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .send-btn:hover {
            background: var(--secondary-color);
        }

        .preview-btn {
            position: fixed;
            top: 24px;
            right: 24px;
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.2);
            transition: all 0.2s;
        }

        .preview-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-1px);
        }

        .preview-btn svg {
            width: 18px;
            height: 18px;
        }

        /* Dark mode cho code blocks */
        .language-html, .language-css {
            color: #e2e8f0;
        }

        /* Scrollbar styles */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
                padding: 16px;
            }

            .chat-messages {
                padding: 16px;
            }

            .message {
                padding: 16px;
                gap: 16px;
            }

            .preview-btn {
                top: auto;
                bottom: 90px;
                right: 16px;
            }
        }

        /* Thêm CSS cho avatar */
        .message {
            display: flex;
            gap: 16px;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .message-avatar {
            flex-shrink: 0;
        }

        .avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .message.ai {
            background: var(--bg-color);
        }

        .message-content {
            flex: 1;
            overflow-x: auto;
        }

        /* Style cho history items */
        .history-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .history-item:hover {
            background: var(--hover-color);
        }

        .history-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .history-content {
            flex: 1;
        }

        .history-message {
            font-size: 14px;
            color: var(--text-color);
            margin-bottom: 4px;
        }

        .history-date {
            font-size: 12px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="sidebar">
            <button class="new-chat-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Chat
            </button>
            
            <div class="chat-history">
                <?php foreach ($chatHistory as $chat): ?>
                    <div class="history-item" onclick="loadChat(<?php echo $chat['ChatID']; ?>)">
                        <img src="<?php echo $aiAvatar; ?>" alt="AI" class="history-avatar">
                        <div class="history-content">
                            <span class="history-message"><?php echo htmlspecialchars(substr($chat['UserMessage'], 0, 30)); ?>...</span>
                            <span class="history-date"><?php echo date('d/m/Y H:i', strtotime($chat['CreatedAt'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="main-content">
            <div class="chat-messages" id="chatMessages">
            </div>
            <div class="input-container">
                <div class="input-box">
                    <textarea 
                        id="userInput" 
                        placeholder="Nhập yêu cầu thiết kế website của bạn..."
                        rows="1"
                    ></textarea>
                    <button class="send-btn" onclick="sendMessage()">Gửi</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const userInput = document.getElementById('userInput');
        
        function formatCodeResponse(text) {
            let formattedResponse = text;
            
            // Format HTML code block
            const htmlMatch = text.match(/```html([\s\S]*?)```/);
            if (htmlMatch) {
                const htmlCode = htmlMatch[1].trim();
                formattedResponse = formattedResponse.replace(/```html([\s\S]*?)```/, `
                    <div class="code-block">
                        <div class="code-header">
                            <span class="code-lang">HTML</span>
                            <button class="copy-btn" onclick="copyCode(this, 'html')">Copy HTML</button>
                        </div>
                        <pre><code class="language-html">${escapeHtml(htmlCode)}</code></pre>
                    </div>
                `);
            }
            
            // Format CSS code block
            const cssMatch = text.match(/```css([\s\S]*?)```/);
            if (cssMatch) {
                const cssCode = cssMatch[1].trim();
                formattedResponse = formattedResponse.replace(/```css([\s\S]*?)```/, `
                    <div class="code-block">
                        <div class="code-header">
                            <span class="code-lang">CSS</span>
                            <button class="copy-btn" onclick="copyCode(this, 'css')">Copy CSS</button>
                        </div>
                        <pre><code class="language-css">${escapeHtml(cssCode)}</code></pre>
                    </div>
                `);
            }
            
            return formattedResponse;
        }

        function copyCode(button, type) {
            const codeBlock = button.closest('.code-block').querySelector('code');
            const code = codeBlock.innerText;
            
            navigator.clipboard.writeText(code).then(() => {
                button.textContent = 'Copied!';
                button.classList.add('copied');
                
                setTimeout(() => {
                    button.textContent = `Copy ${type.toUpperCase()}`;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy code');
            });
        }

        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message) return;

            const API_KEY = 'AIzaSyCHbLp0eA2HkcXSXsnziNDuJ2QBPiPG8yw';
            
            // Cải thiện prompt để nhận được code đầy đủ
            const prompt = `Là một web developer expert, hãy tạo code HTML và CSS hoàn chỉnh cho yêu cầu sau: "${message}".
            
            Yêu cầu trả về:
            1. Code HTML đầy đủ (bao gồm cả thẻ doctype, html, head, body)
            2. Code CSS hoàn chỉnh cho tất cả các thành phần
            3. Đảm bảo responsive
            4. Có comments giải thích
            
            Format trả về:
            \`\`\`html
            <!-- HTML code here -->
            \`\`\`
            
            \`\`\`css
            /* CSS code here */
            \`\`\`
            
            Giải thích ngắn gọn về code.`;

            addMessage(message, false);
            userInput.value = '';

            const loadingDiv = addMessage('Đang tạo code...', true);

            try {
                const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${API_KEY}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        contents: [{
                            parts: [{
                                text: prompt
                            }]
                        }],
                        generationConfig: {
                            temperature: 0.7,
                            maxOutputTokens: 8192, // Tăng giới hạn token
                            topP: 1,
                            topK: 40
                        }
                    })
                });

                const data = await response.json();
                loadingDiv.remove();
                
                if (data.error) {
                    console.error('API Error:', data.error);
                    addMessage(`Lỗi: ${data.error.message}`, true);
                    return;
                }
                
                if (data.candidates && data.candidates[0].content.parts[0].text) {
                    const aiResponse = data.candidates[0].content.parts[0].text;
                    
                    // Tách HTML và CSS code từ response
                    const htmlMatch = aiResponse.match(/```html([\s\S]*?)```/);
                    const cssMatch = aiResponse.match(/```css([\s\S]*?)```/);
                    
                    const htmlCode = htmlMatch ? htmlMatch[1].trim() : '';
                    const cssCode = cssMatch ? cssMatch[1].trim() : '';
                    
                    // Lưu vào database
                    const saveResult = await fetch('save_chat.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            userMessage: message,
                            aiResponse: aiResponse,
                            htmlCode: htmlCode,
                            cssCode: cssCode
                        })
                    });
                    
                    if (!saveResult.ok) {
                        console.error('Lỗi lưu chat');
                    }
                    
                    // Format và hiển thị response
                    const formattedResponse = formatCodeResponse(aiResponse);
                    addMessage(formattedResponse, true);
                    addPreviewButton();
                } else {
                    addMessage('Xin lỗi, tôi không thể tạo code.', true);
                }
            } catch (error) {
                console.error('Fetch Error:', error);
                loadingDiv.remove();
                addMessage(`Lỗi: ${error.message}`, true);
            }
        }

        function addPreviewButton() {
            // Xóa nút preview cũ nếu có
            const existingPreviewBtn = document.querySelector('.preview-btn');
            if (existingPreviewBtn) {
                existingPreviewBtn.remove();
            }

            const previewBtn = document.createElement('button');
            previewBtn.className = 'preview-btn';
            previewBtn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Preview
            `;
            previewBtn.onclick = showPreview;
            document.body.appendChild(previewBtn);
        }

        function showPreview() {
            const htmlCode = document.querySelector('.language-html')?.innerText || '';
            const cssCode = document.querySelector('.language-css')?.innerText || '';

            const previewWindow = window.open('', 'Preview', 'width=1024,height=768');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <style>${cssCode}</style>
                    </head>
                    <body>${htmlCode}</body>
                </html>
            `);
            previewWindow.document.close();
        }

        function addMessage(content, isAI) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isAI ? 'ai' : 'user'}`;
            
            // Sử dụng avatar tương ứng cho user và AI
            const avatar = isAI ? '<?php echo $aiAvatar; ?>' : '<?php echo $userAvatar; ?>';
            
            messageDiv.innerHTML = `
                <div class="message-avatar">
                    <img src="${avatar}" alt="${isAI ? 'AI' : 'User'}" class="avatar-img">
                </div>
                <div class="message-content">
                    ${isAI ? formatCodeResponse(content) : content}
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            return messageDiv;
        }

        userInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        userInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
</body>
</html>