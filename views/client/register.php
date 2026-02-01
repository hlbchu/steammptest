<?php
/**
 * REGISTER PAGE - Đăng ký tài khoản mới
 */
session_start();

// Load config
require_once '../../config/config.php';
require_once '../../app/Helpers/functions.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - GAMES STORE</title>
    <!-- CSS Components -->
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/header.css">
    <link rel="stylesheet" href="../../public/assets/css/footer.css">
    <!-- SVG Icons -->
    <?php include '../../public/assets/svg/icons.svg'; ?>
    
    <style>
        .auth-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: calc(100vh - 400px);
        }

        .auth-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 450px);
        }

        .auth-box {
            width: 100%;
            max-width: 450px;
            background: rgba(15, 17, 32, 0.8);
            border: 1px solid rgba(0, 180, 216, 0.2);
            border-radius: 12px;
            padding: 40px;
            margin-top: 100px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h1 {
            font-size: 2rem;
            color: var(--text-primary);
            margin: 0 0 10px 0;
        }

        .auth-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(15, 17, 32, 0.6);
            border: 1px solid rgba(0, 180, 216, 0.2);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-color);
            background: rgba(15, 17, 32, 0.8);
            box-shadow: 0 0 15px rgba(0, 180, 216, 0.3);
        }

        .form-group input::placeholder {
            color: var(--text-secondary);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 0.8rem;
            padding: 8px 12px;
            border-radius: 6px;
            background: rgba(0, 180, 216, 0.1);
            color: var(--text-secondary);
            display: none;
        }

        .password-strength.show {
            display: block;
        }

        .password-strength.weak {
            background: rgba(255, 68, 68, 0.15);
            color: #ff7777;
        }

        .password-strength.medium {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }

        .password-strength.strong {
            background: rgba(76, 175, 80, 0.15);
            color: #4caf50;
        }

        .form-check {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 20px 0;
            font-size: 0.9rem;
        }

        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: var(--accent-color);
        }

        .form-check label {
            margin: 0;
            color: var(--text-secondary);
            line-height: 1.4;
        }

        .form-check a {
            color: var(--accent-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .form-check a:hover {
            text-decoration: underline;
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--accent-color), #0088aa);
            border: none;
            color: white;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 20px 0 15px 0;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 180, 216, 0.4);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .btn-register:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .auth-divider {
            text-align: center;
            color: var(--text-secondary);
            margin: 20px 0;
            font-size: 0.9rem;
        }

        .auth-divider span {
            position: relative;
            background: rgba(15, 17, 32, 0.8);
            padding: 0 10px;
        }

        .auth-divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: rgba(0, 180, 216, 0.2);
            z-index: -1;
        }

        .social-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .social-btn {
            padding: 10px;
            background: rgba(0, 180, 216, 0.1);
            border: 1px solid rgba(0, 180, 216, 0.2);
            color: var(--text-primary);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            background: rgba(0, 180, 216, 0.2);
            border-color: var(--accent-color);
        }

        .auth-footer {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: var(--accent-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: rgba(255, 68, 68, 0.15);
            border: 1px solid rgba(255, 68, 68, 0.3);
            color: #ff7777;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        @media (max-width: 768px) {
            .auth-box {
                max-width: 100%;
                padding: 30px 20px;
            }

            .auth-header h1 {
                font-size: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .social-login {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header Layout Include -->
    <?php include '../partials/header-layout.php'; ?>

    <!-- Main Content -->
    <main>
        <div class="auth-container">
            <div class="auth-wrapper">
                <div class="auth-box">
                    <div class="auth-header">
                        <h1>Đăng Ký</h1>
                        <p>Tạo tài khoản GAMES STORE mới</p>
                    </div>

                    <form id="registerForm">
                        <div class="error-message" id="errorMessage"></div>

                        <div class="form-group">
                            <label for="username">Tên đăng nhập</label>
                            <input type="text" id="username" name="username" placeholder="Chọn tên đăng nhập (4-20 ký tự)" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="your.email@example.com" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Mật khẩu</label>
                                <input type="password" id="password" name="password" placeholder="Tối thiểu 6 ký tự" required>
                                <div class="password-strength" id="passwordStrength"></div>
                            </div>
                            <div class="form-group">
                                <label for="password_confirm">Xác nhận mật khẩu</label>
                                <input type="password" id="password_confirm" name="password_confirm" placeholder="Nhập lại mật khẩu" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Số điện thoại (không bắt buộc)</label>
                            <input type="tel" id="phone" name="phone" placeholder="+84 xxx xxxx xxxx">
                        </div>

                        <div class="form-check">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">
                                Tôi đồng ý với <a href="#">Điều khoản dịch vụ</a> và <a href="#">Chính sách bảo mật</a> của GAMES STORE
                            </label>
                        </div>

                        <button type="submit" class="btn-register">Đăng Ký Tài Khoản</button>
                    </form>

                    <div class="auth-divider">
                        <span>Hoặc đăng ký với</span>
                    </div>

                    <div class="social-login">
                        <button type="button" class="social-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <use xlink:href="#icon-gamepad"></use>
                            </svg>
                            Google
                        </button>
                        <button type="button" class="social-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <use xlink:href="#icon-gamepad"></use>
                            </svg>
                            Facebook
                        </button>
                    </div>

                    <div class="auth-footer">
                        Đã có tài khoản? <a href="?page=login">Dăng nhậ p</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Layout Include -->
    <?php include '../partials/footer-layout.php'; ?>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthIndicator = document.getElementById('passwordStrength');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = getPasswordStrength(password);

            if (password.length > 0) {
                strengthIndicator.classList.add('show');
                strengthIndicator.className = 'password-strength show ' + strength.level;
                strengthIndicator.textContent = strength.text;
            } else {
                strengthIndicator.classList.remove('show');
            }
        });

        function getPasswordStrength(password) {
            let score = 0;
            
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^a-zA-Z0-9]/.test(password)) score++;

            if (score <= 2) return { level: 'weak', text: '❌ Mật khẩu yếu' };
            if (score <= 4) return { level: 'medium', text: '⚠️ Mật khẩu vừa' };
            return { level: 'strong', text: '✅ Mật khẩu mạnh' };
        }

        // Form validation & submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            const phone = document.getElementById('phone').value.trim();
            const terms = document.getElementById('terms').checked;
            const errorMsg = document.getElementById('errorMessage');
            const submitBtn = this.querySelector('button[type="submit"]');

            // Clear error
            errorMsg.classList.remove('show');

            // Validation
            if (!username || !email || !password || !passwordConfirm) {
                errorMsg.textContent = 'Vui lòng điền đầy đủ các trường bắt buộc';
                errorMsg.classList.add('show');
                return;
            }

            if (username.length < 4 || username.length > 20) {
                errorMsg.textContent = 'Tên đăng nhập phải có 4-20 ký tự';
                errorMsg.classList.add('show');
                return;
            }

            if (!/^[a-zA-Z0-9._-]+$/.test(username)) {
                errorMsg.textContent = 'Tên đăng nhập chỉ được chứa chữ, số, dấu chấm, gạch dưới, gạch ngang';
                errorMsg.classList.add('show');
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errorMsg.textContent = 'Email không hợp lệ';
                errorMsg.classList.add('show');
                return;
            }

            if (password.length < 6) {
                errorMsg.textContent = 'Mật khẩu phải có ít nhất 6 ký tự';
                errorMsg.classList.add('show');
                return;
            }

            if (password !== passwordConfirm) {
                errorMsg.textContent = 'Mật khẩu xác nhận không khớp';
                errorMsg.classList.add('show');
                return;
            }

            if (phone && !/^(\+84|0)[0-9]{8,9}$/.test(phone.replace(/\s/g, ''))) {
                errorMsg.textContent = 'Số điện thoại không hợp lệ';
                errorMsg.classList.add('show');
                return;
            }

            if (!terms) {
                errorMsg.textContent = 'Bạn phải đồng ý với điều khoản dịch vụ';
                errorMsg.classList.add('show');
                return;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang đăng ký...';

            // Send to server
            const formData = new FormData();
            formData.append('username', username);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('password_confirm', passwordConfirm);
            formData.append('phone', phone);
            formData.append('terms', terms ? 'on' : 'off');

            fetch('../../app/Controllers/AuthController.php?action=register', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    errorMsg.style.background = 'rgba(76, 175, 80, 0.15)';
                    errorMsg.style.borderColor = 'rgba(76, 175, 80, 0.3)';
                    errorMsg.style.color = '#90EE90';
                    errorMsg.textContent = data.message;
                    errorMsg.classList.add('show');
                    
                    // Redirect after 1.5 seconds
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    // Show error
                    errorMsg.style.background = 'rgba(255, 68, 68, 0.15)';
                    errorMsg.style.borderColor = 'rgba(255, 68, 68, 0.3)';
                    errorMsg.style.color = '#ff7777';
                    errorMsg.textContent = data.message;
                    errorMsg.classList.add('show');
                    
                    // Re-enable button
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Đăng Ký Tài Khoản';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMsg.textContent = 'Lỗi kết nối server, vui lòng thử lại';
                errorMsg.classList.add('show');
                
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Đăng Ký Tài Khoản';
            });
        });

        // Clear error message when user focuses on fields
        document.querySelectorAll('input[required]').forEach(input => {
            input.addEventListener('focus', function() {
                document.getElementById('errorMessage').classList.remove('show');
            });
        });
    </script>
</body>
</html>

