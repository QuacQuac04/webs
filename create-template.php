<form id="templateForm">
    <div class="form-group">
        <label for="templateName">Tên Template:</label>
        <input type="text" id="templateName" name="templateName" required>
    </div>
    
    <div class="form-group">
        <label for="htmlContent">HTML:</label>
        <textarea id="htmlContent" name="htmlContent" rows="10"></textarea>
    </div>
    
    <div class="form-group">
        <label for="cssContent">CSS:</label>
        <textarea id="cssContent" name="cssContent" rows="10"></textarea>
    </div>
    
    <div class="form-group">
        <label for="jsContent">JavaScript:</label>
        <textarea id="jsContent" name="jsContent" rows="10"></textarea>
    </div>
    
    <div class="preview-container">
        <h3>Preview:</h3>
        <iframe id="previewFrame"></iframe>
    </div>
    
    <button type="submit">Lưu Template</button>
</form>

<script>
// Preview realtime
function updatePreview() {
    const html = document.getElementById('htmlContent').value;
    const css = document.getElementById('cssContent').value;
    const js = document.getElementById('jsContent').value;
    
    const preview = document.getElementById('previewFrame').contentDocument;
    preview.open();
    preview.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <style>${css}</style>
        </head>
        <body>
            ${html}
            <script>${js}<\/script>
        </body>
        </html>
    `);
    preview.close();
}

// Thêm event listeners cho các textarea
['htmlContent', 'cssContent', 'jsContent'].forEach(id => {
    document.getElementById(id).addEventListener('input', updatePreview);
});
</script> 