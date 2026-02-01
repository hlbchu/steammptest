<?php
/**
 * ADMIN CONTROLLER - Xử lý admin
 */

class AdminController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        
        // Kiểm tra quyền admin
        AuthMiddleware::checkAdmin();
    }
    
    /**
     * Dashboard
     */
    public function dashboard() {
        include '../views/admin/dashboard.php';
    }
    
    /**
     * Quản lý sản phẩm
     */
    public function products() {
        include '../views/admin/products.php';
    }
    
    /**
     * Quản lý người dùng
     */
    public function users() {
        include '../views/admin/users.php';
    }
    
    /**
     * Quản lý banner
     */
    public function banners() {
        include '../views/admin/banners.php';
    }
}
