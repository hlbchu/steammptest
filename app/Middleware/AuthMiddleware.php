<?php
/**
 * MIDDLEWARE - Kiểm tra quyền truy cập
 */

class AuthMiddleware {
    
    /**
     * Kiểm tra đã đăng nhập chưa
     */
    public static function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?page=login');
            exit;
        }
    }
    
    /**
     * Kiểm tra quyền admin
     */
    public static function checkAdmin() {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: ?page=home');
            exit;
        }
    }
    
    /**
     * Kiểm tra đã đăng nhập (redirect về home)
     */
    public static function checkGuest() {
        if (isset($_SESSION['user_id'])) {
            header('Location: ?page=home');
            exit;
        }
    }
}
