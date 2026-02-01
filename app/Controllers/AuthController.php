<?php
/**
 * AuthController - Xử lý đăng nhập, đăng ký, đăng xuất
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config and database
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

class AuthController {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Đăng ký tài khoản mới
     */
    public function register() {
        header('Content-Type: application/json');

        try {
            // Get POST data
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';
            $phone = trim($_POST['phone'] ?? '');
            $terms = $_POST['terms'] ?? '';

            // Validation
            if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
                throw new Exception('Vui lòng điền đầy đủ các trường bắt buộc');
            }

            if (strlen($username) < 4 || strlen($username) > 20) {
                throw new Exception('Tên đăng nhập phải có 4-20 ký tự');
            }

            if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
                throw new Exception('Tên đăng nhập chỉ được chứa chữ, số, dấu chấm, gạch dưới, gạch ngang');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email không hợp lệ');
            }

            if (strlen($password) < 6) {
                throw new Exception('Mật khẩu phải có ít nhất 6 ký tự');
            }

            if ($password !== $password_confirm) {
                throw new Exception('Mật khẩu xác nhận không khớp');
            }

            if (!empty($phone) && !preg_match('/^(\+84|0)[0-9]{8,9}$/', str_replace(' ', '', $phone))) {
                throw new Exception('Số điện thoại không hợp lệ');
            }

            if ($terms !== 'on') {
                throw new Exception('Bạn phải đồng ý với điều khoản dịch vụ');
            }

            // Check if username exists
            $this->db->query("SELECT id FROM users WHERE username = :username");
            $this->db->bind(':username', $username);
            if ($this->db->fetch()) {
                throw new Exception('Tên đăng nhập đã tồn tại');
            }

            // Check if email exists
            $this->db->query("SELECT id FROM users WHERE email = :email");
            $this->db->bind(':email', $email);
            if ($this->db->fetch()) {
                throw new Exception('Email đã được sử dụng');
            }

            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $this->db->query("
                INSERT INTO users (username, email, password_hash, phone, role, status, created_at, updated_at) 
                VALUES (:username, :email, :password_hash, :phone, 'user', 'active', NOW(), NOW())
            ");
            $this->db->bind(':username', $username);
            $this->db->bind(':email', $email);
            $this->db->bind(':password_hash', $password_hash);
            $this->db->bind(':phone', !empty($phone) ? $phone : null);
            
            if (!$this->db->execute()) {
                throw new Exception('Lỗi khi tạo tài khoản, vui lòng thử lại');
            }

            // Get new user ID
            $user_id = $this->conn->lastInsertId();

            // Create user profile
            $this->db->query("
                INSERT INTO user_profiles (user_id, vip_level, total_deposit, monthly_deposit) 
                VALUES (:user_id, 0, 0.00, 0.00)
            ");
            $this->db->bind(':user_id', $user_id);
            $this->db->execute();

            // Create wallet
            $this->db->query("
                INSERT INTO wallets (user_id, balance, updated_at) 
                VALUES (:user_id, 0.00, NOW())
            ");
            $this->db->bind(':user_id', $user_id);
            $this->db->execute();

            // Auto login after registration
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'user';
            $_SESSION['logged_in'] = true;

            echo json_encode([
                'success' => true,
                'message' => 'Đăng ký thành công! Đang chuyển hướng...',
                'redirect' => '../../index.php'
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Đăng nhập
     */
    public function login() {
        header('Content-Type: application/json');

        try {
            // Get POST data
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);

            // Validation
            if (empty($username) || empty($password)) {
                throw new Exception('Vui lòng nhập tên đăng nhập/email và mật khẩu');
            }

            if (strlen($password) < 6) {
                throw new Exception('Mật khẩu phải có ít nhất 6 ký tự');
            }

            // Find user by username or email
            $this->db->query("
                SELECT id, username, email, password_hash, role, status 
                FROM users 
                WHERE username = :username OR email = :email
            ");
            $this->db->bind(':username', $username);
            $this->db->bind(':email', $username);
            $user = $this->db->fetch();

            if (!$user) {
                throw new Exception('Tên đăng nhập hoặc mật khẩu không đúng');
            }

            // Check if account is banned
            if ($user['status'] === 'banned') {
                throw new Exception('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ');
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                throw new Exception('Tên đăng nhập hoặc mật khẩu không đúng');
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            // Remember me cookie (7 days)
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 7), '/');
                // TODO: Store token in database for security
            }

            // Merge guest cart into user cart (if any)
            if (!empty($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {
                foreach ($_SESSION['guest_cart'] as $guestProductId => $guestQuantity) {
                    $guestProductId = (int)$guestProductId;
                    $guestQuantity = (int)$guestQuantity;

                    if ($guestProductId <= 0 || $guestQuantity <= 0) {
                        continue;
                    }

                    // Ensure product exists and is active
                    $this->db->query("SELECT id FROM products WHERE id = :product_id AND status = 'active'");
                    $this->db->bind(':product_id', $guestProductId);
                    if (!$this->db->fetch()) {
                        continue;
                    }

                    // Check if product already in cart
                    $this->db->query("SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id");
                    $this->db->bind(':user_id', $user['id']);
                    $this->db->bind(':product_id', $guestProductId);
                    $cartItem = $this->db->fetch();

                    if ($cartItem) {
                        $newQuantity = $cartItem['quantity'] + $guestQuantity;
                        $this->db->query("UPDATE cart SET quantity = :quantity, updated_at = NOW() WHERE user_id = :user_id AND product_id = :product_id");
                        $this->db->bind(':quantity', $newQuantity);
                        $this->db->bind(':user_id', $user['id']);
                        $this->db->bind(':product_id', $guestProductId);
                        $this->db->execute();
                    } else {
                        $this->db->query("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
                        $this->db->bind(':user_id', $user['id']);
                        $this->db->bind(':product_id', $guestProductId);
                        $this->db->bind(':quantity', $guestQuantity);
                        $this->db->execute();
                    }
                }

                unset($_SESSION['guest_cart']);
            }

            // Redirect based on role
            $redirect = ($user['role'] === 'admin') 
                ? '../../views/admin/dashboard.php' 
                : '../../index.php';

            echo json_encode([
                'success' => true,
                'message' => 'Đăng nhập thành công! Đang chuyển hướng...',
                'redirect' => $redirect,
                'user' => [
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Đăng xuất
     */
    public function logout() {
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        
        // Clear remember token cookie
        setcookie('remember_token', '', time() - 3600, '/');

        // Redirect to homepage
        header('Location: ../../index.php');
        exit();
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return self::isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    /**
     * Get current user data
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'role' => $_SESSION['role'] ?? 'user'
        ];
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logout')) {
    $controller = new AuthController();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'register':
            $controller->register();
            break;
        case 'login':
            $controller->login();
            break;
        case 'logout':
            $controller->logout();
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }
}
