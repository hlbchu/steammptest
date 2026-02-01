<?php
/**
 * LOGIN PAGE - Đăng nhập tài khoản
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
    <title>Đăng Nhập - GAMES STORE</title>
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
            max-width: 400px;
            background: rgba(15, 17, 32, 0.8);
            border: 1px solid rgba(0, 180, 216, 0.2);
            border-radius: 12px;
            margin-top: 100px;
            padding: 40px;
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
            margin-bottom: 20px;
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

        .form-remember {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .form-remember input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }

        .form-remember a {
            color: var(--accent-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .form-remember a:hover {
            text-decoration: underline;
        }

        .btn-login {
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
            margin-bottom: 15px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 180, 216, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
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
                        <h1>Đăng Nhập</h1>
                        <p>Vào tài khoản GAMES STORE của bạn</p>
                    </div>

                    <form id="loginForm">
                        <div class="error-message" id="errorMessage"></div>

                        <div class="form-group">
                            <label for="username">Tên đăng nhập hoặc Email</label>
                            <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập hoặc email" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Mật khẩu</label>
                            <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                        </div>

                        <div class="form-remember">
                            <label>
                                <input type="checkbox" name="remember"> Nhớ tôi
                            </label>
                            <a href="#">Quên mật khẩu?</a>
                        </div>

                        <button type="submit" class="btn-login">Đăng Nhập</button>
                    </form>

                    <div class="auth-divider">
                        <span>Hoặc đăng nhập với</span>
                    </div>

                    <div class="social-login">
                        <button class="social-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <use xlink:href="#icon-gamepad"></use>
                            </svg>
                            Google
                        </button>
                        <button class="social-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <use xlink:href="#icon-gamepad"></use>
                            </svg>
                            Facebook
                        </button>
                    </div>

                    <div class="auth-footer">
                        Chưa có tài khoản? <a href="?page=register">Dăng ký ngay</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Layout Include -->
    <?php include '../partials/footer-layout.php'; ?>

    <script>
        // Form validation & submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const remember = document.querySelector('input[name="remember"]').checked;
            const errorMsg = document.getElementById('errorMessage');
            const submitBtn = this.querySelector('button[type="submit"]');

            // Validation
            if (!username || !password) {
                errorMsg.textContent = 'Vui lòng nhập tên đăng nhập/email và mật khẩu';
                errorMsg.classList.add('show');
                return;
            }

            if (password.length < 6) {
                errorMsg.textContent = 'Mật khẩu phải có ít nhất 6 ký tự';
                errorMsg.classList.add('show');
                return;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang đăng nhập...';

            // Send to server
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            if (remember) formData.append('remember', 'on');

            fetch('../../app/Controllers/AuthController.php?action=login', {
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
                    
                    // Redirect after 1 second
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    // Show error
                    errorMsg.style.background = 'rgba(255, 68, 68, 0.15)';
                    errorMsg.style.borderColor = 'rgba(255, 68, 68, 0.3)';
                    errorMsg.style.color = '#ff7777';
                    errorMsg.textContent = data.message;
                    errorMsg.classList.add('show');
                    
                    // Re-enable button
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Đăng Nhập';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMsg.textContent = 'Lỗi kết nối server, vui lòng thử lại';
                errorMsg.classList.add('show');
                
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Đăng Nhập';
            });
        });

        // Clear error message when user types
        document.getElementById('username').addEventListener('focus', function() {
            document.getElementById('errorMessage').classList.remove('show');
        });
        document.getElementById('password').addEventListener('focus', function() {
            document.getElementById('errorMessage').classList.remove('show');
        });
    </script>
</body>
</html>
