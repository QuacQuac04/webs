document.addEventListener('DOMContentLoaded', function() {
    // Xử lý click cho dropdown toggles
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
        });
    });

    // Xử lý click cho nút đăng nhập
    const loginBtn = document.querySelector('.login-btn');
    if(loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            window.location.href = 'login.php';
        });
    }

    // Xử lý load more
    let page = 1;
    const loadMoreBtn = document.getElementById('loadMore');
    if(loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            page++;
            loadMoreTemplates(page);
        });
    }

    // Xử lý like template
    document.querySelectorAll('.btn-like').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const templateId = this.dataset.templateId;
            if (!templateId) {
                console.error('No template ID found');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('template_id', templateId);
                
                const response = await fetch('like_template.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Response:', data);
                
                if (data.status === 'success') {
                    // Cập nhật UI
                    const heartIcon = this.querySelector('i');
                    const likeCount = this.querySelector('.like-count');
                    
                    if (data.action === 'liked') {
                        this.classList.add('liked');
                        heartIcon.classList.remove('bi-heart');
                        heartIcon.classList.add('bi-heart-fill');
                    } else {
                        this.classList.remove('liked');
                        heartIcon.classList.remove('bi-heart-fill');
                        heartIcon.classList.add('bi-heart');
                    }
                    
                    if (likeCount) {
                        likeCount.textContent = data.likes;
                    }
                } else {
                    if (data.message.includes('đăng nhập')) {
                        window.location.href = 'login.php';
                    } else {
                        alert(data.message);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thực hiện thao tác');
            }
        });
    });

    // Xử lý filter templates
    const sortButton = document.getElementById('sortButton');
    const filterMenu = document.querySelector('.filter-menu');
    const filterOptions = document.querySelectorAll('.filter-option');
    const templateGrid = document.querySelector('.template-grid');
    
    // Toggle dropdown menu
    if (sortButton && filterMenu) {
        sortButton.addEventListener('click', function(e) {
            e.stopPropagation();
            filterMenu.classList.toggle('show');
        });

        // Đóng dropdown khi click ngoài
        document.addEventListener('click', function(e) {
            if (!filterMenu.contains(e.target) && !sortButton.contains(e.target)) {
                filterMenu.classList.remove('show');
            }
        });
    }

    // Xử lý khi chọn option
    filterOptions.forEach(option => {
        option.addEventListener('click', async function(e) {
            e.preventDefault();
            const sortType = this.dataset.sort;
            
            try {
                const response = await fetch('sort-templates.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `sort=${sortType}`
                });

                if (!response.ok) throw new Error('Network response was not ok');
                
                const html = await response.text();
                templateGrid.innerHTML = html;

                // Cập nhật active state
                filterOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');

                // Cập nhật text button
                const buttonText = this.querySelector('span').textContent;
                sortButton.querySelector('span').textContent = buttonText;
                
                // Đóng dropdown
                filterMenu.classList.remove('show');
                
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });

    // Xử lý tìm kiếm realtime
    document.getElementById('searchInput').addEventListener('input', debounce(async function(e) {
        const searchQuery = e.target.value.trim();
        const resultsContainer = document.querySelector('.main-content');
        
        if (searchQuery.length > 0) {
            try {
                const formData = new FormData();
                formData.append('query', searchQuery);
                
                const response = await fetch('search-templates.php', {
                    method: 'POST',
                    body: formData
                });
                
                const html = await response.text();
                resultsContainer.innerHTML = `
                    <div class="container">
                        <h2 class="search-title">Kết quả tìm kiếm cho "${searchQuery}"</h2>
                        ${html}
                    </div>
                `;
            } catch (error) {
                console.error('Search error:', error);
            }
        } else {
            // Khôi phục giao diện ban đầu
            location.reload();
        }
    }, 300));

    const menuToggle = document.querySelector('.menu-toggle');
    const body = document.body;

    // Toggle menu khi click button
    menuToggle.addEventListener('click', function() {
        body.classList.toggle('sidebar-open');
    });

    // Đóng menu khi nhấn ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && body.classList.contains('sidebar-open')) {
            body.classList.remove('sidebar-open');
        }
    });
});

function loadMoreTemplates(page) {
    fetch(`load-more-templates.php?page=${page}`)
        .then(response => response.text())
        .then(html => {
            const templateGrid = document.querySelector('.template-grid');
            templateGrid.insertAdjacentHTML('beforeend', html);
            
            // Thêm event listeners cho các nút like mới
            const newLikeButtons = templateGrid.querySelectorAll('.btn-like:not([data-initialized])');
            newLikeButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const templateId = this.dataset.id;
                    likeTemplate(templateId, this);
                });
                btn.setAttribute('data-initialized', 'true');
            });
        });
}

function likeTemplate(templateId, button) {
    fetch('like-template.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `template_id=${templateId}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const likeCount = button.closest('.template-card').querySelector('.bi-heart').nextSibling;
            likeCount.textContent = data.likes;
            button.classList.toggle('liked');
        }
    });
}

// Các hàm xử lý AJAX có thể được thêm vào đây

function searchTemplates(query) {
    const templateGrid = document.querySelector('.template-grid');
    
    fetch('search-templates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `query=${encodeURIComponent(query)}`
    })
    .then(response => response.text())
    .then(html => {
        templateGrid.innerHTML = html;
        
        // Cập nhật tiêu đề section nếu có kết quả
        const sectionTitle = document.querySelector('.section-header h2');
        if (query) {
            sectionTitle.textContent = `Kết quả tìm kiếm cho "${query}"`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        templateGrid.innerHTML = '<div class="error">Có lỗi xảy ra khi tìm kiếm. Vui lòng thử lại.</div>';
    });
}

function loadAllTemplates() {
    const templateGrid = document.querySelector('.template-grid');
    
    fetch('load-all-templates.php')
        .then(response => response.text())
        .then(html => {
            templateGrid.innerHTML = html;
            // Khôi phục tiêu đề gốc
            const sectionTitle = document.querySelector('.section-header h2');
            sectionTitle.textContent = 'Khám Phá Tác Phẩm Cộng Đồng';
        })
        .catch(error => {
            console.error('Error:', error);
            templateGrid.innerHTML = '<div class="error">Có lỗi xảy ra khi tải templates. Vui lòng thử lại.</div>';
        });
}

function applyFilter(filterType) {
    const templateGrid = document.querySelector('.template-grid');
    
    fetch('filter-templates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `filter=${encodeURIComponent(filterType)}`
    })
    .then(response => response.text())
    .then(html => {
        templateGrid.innerHTML = html;
    })
    .catch(error => {
        console.error('Error:', error);
        templateGrid.innerHTML = '<div class="error">Có lỗi xảy ra khi lọc templates. Vui lòng thử lại.</div>';
    });
}

// Hàm debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
