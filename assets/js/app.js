// BAUST Exchange - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Image upload area
    initImageUpload();
    
    // Auto-dismiss alerts
    initAlertDismiss();
    
    // Search functionality
    initSearch();
    
    // Sidebar toggle functionality
    initSidebarToggle();
    
    // Right sidebar collapse toggle functionality
    initRightSidebarToggle();
});

// Sidebar Toggle Handler
function initSidebarToggle() {
    const toggleBtn = document.querySelector('#sidebarToggleBtn');
    if (!toggleBtn) return;

    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (window.innerWidth < 992) {
            const offcanvasEl = document.getElementById('sidebarOffcanvas');
            if (offcanvasEl && typeof bootstrap !== 'undefined') {
                let bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                if (!bsOffcanvas) {
                    bsOffcanvas = new bootstrap.Offcanvas(offcanvasEl);
                }
                bsOffcanvas.toggle();
            }
        } else {
            document.body.classList.toggle('sidebar-toggled');
            const isToggled = document.body.classList.contains('sidebar-toggled') ? '1' : '0';
            localStorage.setItem('sidebar_toggled', isToggled);
            document.cookie = "sidebar_toggled=" + isToggled + "; path=/; max-age=31536000";
        }
    });
}

// Right Sidebar Full Expansion & Toggle Handler
function initRightSidebarToggle() {
    const headerBtn = document.getElementById('headerRightSidebarToggleBtn');
    const panelBtn = document.getElementById('toggleRightSidebarBtn');

    // Restore saved preference
    const isHidden = localStorage.getItem('right_sidebar_hidden') === '1';
    if (isHidden) {
        document.body.classList.add('right-sidebar-hidden');
    }

    function toggleRightSidebar(e) {
        if (e) e.preventDefault();
        document.body.classList.toggle('right-sidebar-hidden');
        const hiddenNow = document.body.classList.contains('right-sidebar-hidden') ? '1' : '0';
        localStorage.setItem('right_sidebar_hidden', hiddenNow);
        document.cookie = "right_sidebar_hidden=" + hiddenNow + "; path=/; max-age=31536000";
    }

    if (headerBtn) {
        headerBtn.addEventListener('click', toggleRightSidebar);
    }
    if (panelBtn) {
        panelBtn.addEventListener('click', toggleRightSidebar);
    }
}

// Image Upload Handler
function initImageUpload() {
    const uploadArea = document.querySelector('.image-upload-area');
    const fileInput = document.querySelector('#images');
    const previewContainer = document.querySelector('#image-preview');

    if (!uploadArea || !fileInput) return;

    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        const maxFiles = 5;
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        for (let i = 0; i < Math.min(files.length, maxFiles); i++) {
            const file = files[i];

            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type: ' + file.name);
                continue;
            }

            if (file.size > maxSize) {
                alert('File too large: ' + file.name);
                continue;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                if (previewContainer) {
                    const preview = document.createElement('div');
                    preview.className = 'position-relative d-inline-block';
                    preview.innerHTML = `
                        <img src="${e.target.result}" class="image-preview" alt="Preview">
                        <button type="button" class="btn btn-danger btn-sm position-absolute" 
                                style="top: -5px; right: -5px; width: 20px; height: 20px; padding: 0; border-radius: 50%;"
                                onclick="this.parentElement.remove()">×</button>
                    `;
                    previewContainer.appendChild(preview);
                }
            };
            reader.readAsDataURL(file);
        }
    }
}

// Alert Auto-dismiss
function initAlertDismiss() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

// Search with debounce
function initSearch() {
    const searchInput = document.querySelector('#search-input');
    if (!searchInput) return;

    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const form = searchInput.closest('form');
            if (form) form.submit();
        }, 500);
    });
}

// AJAX Request Helper
function ajaxRequest(url, method, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                callback(null, JSON.parse(xhr.responseText));
            } else {
                callback(new Error('Request failed'));
            }
        }
    };
    xhr.send(data);
}

// Mark notification as read
function markNotificationRead(notificationId) {
    ajaxRequest('/BaustExchange/api/notifications.php', 'POST', 
        'action=mark_read&id=' + notificationId, function(err, response) {
        if (!err && response.success) {
            const element = document.querySelector('[data-notification-id="' + notificationId + '"]');
            if (element) element.classList.remove('unread');
        }
    });
}

// Mark message as read
function markMessageRead(messageId) {
    ajaxRequest('/BaustExchange/api/messages.php', 'POST',
        'action=mark_read&id=' + messageId, function(err, response) {
        if (!err && response.success) {
            const element = document.querySelector('[data-message-id="' + messageId + '"]');
            if (element) element.classList.remove('unread');
        }
    });
}

// Delete listing confirmation
function confirmDelete(listingId) {
    if (confirm('Are you sure you want to delete this listing? This action cannot be undone.')) {
        window.location.href = '/BaustExchange/my-listings.php?delete=' + listingId;
    }
}

// Update listing status
function updateListingStatus(listingId, status) {
    if (confirm('Are you sure you want to mark this listing as ' + status + '?')) {
        ajaxRequest('/BaustExchange/api/listings.php', 'POST',
            'action=update_status&id=' + listingId + '&status=' + status, function(err, response) {
            if (!err && response.success) {
                location.reload();
            } else {
                alert('Failed to update status');
            }
        });
    }
}

// Send request (exchange, rent, share, or purchase)
function sendRequest(listingId, transactionType) {
    const message = document.querySelector('#request-message')?.value;
    const offeredItem = document.querySelector('#offered-item')?.value;
    const rentDuration = document.querySelector('#rent-duration')?.value;
    const shareDuration = document.querySelector('#share-duration')?.value;
    const duration = rentDuration || shareDuration || '';

    if (!message && !offeredItem && !duration) {
        alert('Please enter a message');
        return;
    }

    let params = 'action=create&listing_id=' + listingId + '&message=' + encodeURIComponent(message) 
        + '&transaction_type=' + transactionType
        + '&duration=' + encodeURIComponent(duration);

    if (offeredItem) params += '&offered_item=' + encodeURIComponent(offeredItem);

    ajaxRequest('/BaustExchange/api/exchange-request.php', 'POST', params,
        function(err, response) {
            if (!err && response.success) {
                alert('Request sent successfully!');
                location.reload();
            } else {
                alert(response.message || 'Failed to send request');
            }
        }
    );
}

// Handle exchange request
function handleExchangeRequest(requestId, action) {
    if (confirm('Are you sure you want to ' + action + ' this request?')) {
        ajaxRequest('/BaustExchange/api/exchange-request.php', 'POST',
            'action=' + action + '&id=' + requestId, function(err, response) {
            if (!err && response.success) {
                location.reload();
            } else {
                alert(response.message || 'Failed to process request');
            }
        });
    }
}

// Handle rental request action (accept, reject, cancel, complete)
function handleRequest(requestId, action) {
    if (confirm('Are you sure you want to ' + action.replace('rent_', '') + ' this rental request?')) {
        ajaxRequest('/BaustExchange/api/exchange-request.php', 'POST',
            'action=' + action + '&id=' + requestId, function(err, response) {
            if (!err && response.success) {
                location.reload();
            } else {
                alert(response.message || 'Failed to process request');
            }
        });
    }
}

// Handle share request action (approve, reject, cancel, complete)
function handleShareRequest(requestId, action) {
    if (confirm('Are you sure you want to ' + action.replace('share_', '') + ' this share request?')) {
        ajaxRequest('/BaustExchange/api/exchange-request.php', 'POST',
            'action=' + action + '&id=' + requestId, function(err, response) {
            if (!err && response.success) {
                location.reload();
            } else {
                alert(response.message || 'Failed to process request');
            }
        });
    }
}

// Load more messages via AJAX
function loadMoreMessages(userId, lastMessageId) {
    ajaxRequest('/BaustExchange/api/messages.php?action=load_more&user_id=' + userId + '&last_id=' + lastMessageId, 'GET', null,
        function(err, response) {
            if (!err && response.messages) {
                const container = document.querySelector('.chat-messages');
                response.messages.forEach(msg => {
                    container.insertAdjacentHTML('afterbegin', createMessageHTML(msg));
                });
            }
        }
    );
}

function createMessageHTML(message) {
    const isSent = message.sender_id == document.querySelector('#current-user-id')?.value;
    return `
        <div class="chat-message ${isSent ? 'sent' : 'received'}">
            <div class="message-bubble">
                ${escapeHtml(message.message)}
                <small class="d-block mt-1 opacity-75">${formatTime(message.created_at)}</small>
            </div>
        </div>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Refresh notifications periodically
function refreshNotifications() {
    if (typeof refreshInterval !== 'undefined') return;
    
    window.refreshInterval = setInterval(() => {
        ajaxRequest('/BaustExchange/api/notifications.php?action=count', 'GET', null,
            function(err, response) {
                if (!err && response.count !== undefined) {
                    const badge = document.querySelector('.notification-count');
                    if (badge) {
                        badge.textContent = response.count;
                        badge.style.display = response.count > 0 ? 'inline' : 'none';
                    }
                }
            }
        );
    }, 60000); // Every minute
}

refreshNotifications();
