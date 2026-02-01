/**
 * PROFILE.JS - JavaScript cho trang thông tin tài khoản
 */

document.addEventListener('DOMContentLoaded', function() {
    // Tab navigation
    const navItems = document.querySelectorAll('.nav-item');
    const tabPanes = document.querySelectorAll('.tab-pane');

    function switchTab(targetTab) {
        if (!targetTab) return;
        
        // Remove active class from all nav items
        navItems.forEach(nav => nav.classList.remove('active'));
        
        // Add active class to target item
        navItems.forEach(nav => {
            if (nav.getAttribute('data-tab') === targetTab) {
                nav.classList.add('active');
            }
        });
        
        // Hide all tab panes
        tabPanes.forEach(pane => pane.classList.remove('active'));
        
        // Show target tab pane
        const targetPane = document.getElementById(targetTab);
        if (targetPane) {
            targetPane.classList.add('active');
        }
    }

    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetTab = this.getAttribute('data-tab');
            switchTab(targetTab);
        });
    });

    // Handle hash on page load
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById(hash)) {
        switchTab(hash);
    }

    // Edit profile functionality
    const btnEdit = document.getElementById('btnEdit');
    const btnCancel = document.getElementById('btnCancel');
    const profileForm = document.getElementById('profileForm');
    const formActions = profileForm.querySelector('.form-actions');
    const editableInputs = profileForm.querySelectorAll('input[name]:not([type="text"]:first-child)');

    if (btnEdit) {
        btnEdit.addEventListener('click', function() {
            // Enable all inputs except username
            editableInputs.forEach(input => {
                input.disabled = false;
            });
            
            // Show form actions
            formActions.style.display = 'flex';
            
            // Hide edit button
            this.style.display = 'none';
        });
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', function() {
            // Disable all inputs
            editableInputs.forEach(input => {
                input.disabled = true;
            });
            
            // Hide form actions
            formActions.style.display = 'none';
            
            // Show edit button
            btnEdit.style.display = 'inline-flex';
            
            // Reset form
            profileForm.reset();
            
            // Hide message
            document.getElementById('profileMessage').style.display = 'none';
        });
    }

    // Profile form submission
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageDiv = document.getElementById('profileMessage');
            const formData = new FormData(this);
            
            // TODO: Send to server
            fetch('../../app/Controllers/ProfileController.php?action=update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'message success';
                    messageDiv.textContent = data.message;
                    
                    // Disable inputs and hide actions
                    setTimeout(() => {
                        editableInputs.forEach(input => input.disabled = true);
                        formActions.style.display = 'none';
                        btnEdit.style.display = 'inline-flex';
                    }, 1500);
                } else {
                    messageDiv.className = 'message error';
                    messageDiv.textContent = data.message;
                }
                messageDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Có lỗi xảy ra, vui lòng thử lại';
                messageDiv.style.display = 'block';
            });
        });
    }

    // Password form submission
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageDiv = document.getElementById('passwordMessage');
            const currentPassword = this.querySelector('[name="current_password"]').value;
            const newPassword = this.querySelector('[name="new_password"]').value;
            const confirmPassword = this.querySelector('[name="confirm_password"]').value;
            
            // Validation
            if (newPassword.length < 6) {
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Mật khẩu mới phải có ít nhất 6 ký tự';
                messageDiv.style.display = 'block';
                return;
            }
            
            if (newPassword !== confirmPassword) {
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Mật khẩu xác nhận không khớp';
                messageDiv.style.display = 'block';
                return;
            }
            
            const formData = new FormData(this);
            
            // TODO: Send to server
            fetch('../../app/Controllers/ProfileController.php?action=changePassword', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'message success';
                    messageDiv.textContent = data.message;
                    
                    // Reset form
                    setTimeout(() => {
                        passwordForm.reset();
                    }, 1500);
                } else {
                    messageDiv.className = 'message error';
                    messageDiv.textContent = data.message;
                }
                messageDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Có lỗi xảy ra, vui lòng thử lại';
                messageDiv.style.display = 'block';
            });
        });
    }
});
