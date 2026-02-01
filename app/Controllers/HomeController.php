<?php
/**
 * HOME CONTROLLER - Xử lý trang chủ
 */

class HomeController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Hiển thị trang chủ
     */
    public function index() {
        // Get hot products from database
        $hotProducts = [];
        $newProducts = [];
        
        if ($this->db) {
            try {
                // Get hot products
                $this->db->query("SELECT id, name, price, image, badge FROM products WHERE status = 'active' AND is_hot = 1 LIMIT 6");
                $hotProducts = $this->db->fetchAll();
                
                // Get new products
                $this->db->query("SELECT id, name, price, image, badge FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 6");
                $newProducts = $this->db->fetchAll();
            } catch (Exception $e) {
                // Fall back to empty arrays if database fails
                $hotProducts = [];
                $newProducts = [];
            }
        }
        
        // If no products from database, use sample data
        if (empty($hotProducts)) {
            $hotProducts = [
                ['id' => 1, 'name' => 'ELDEN RING', 'price' => '499.000₫', 'badge' => '-20%', 'image' => 'placeholder.jpg'],
                ['id' => 2, 'name' => 'Baldur\'s Gate 3', 'price' => '699.000₫', 'badge' => '-15%', 'image' => 'placeholder.jpg'],
                ['id' => 3, 'name' => 'Black Myth Wukong', 'price' => '899.000₫', 'badge' => '-25%', 'image' => 'placeholder.jpg'],
                ['id' => 4, 'name' => 'Starfield', 'price' => '599.000₫', 'badge' => '-10%', 'image' => 'placeholder.jpg'],
                ['id' => 5, 'name' => 'The Witcher 3', 'price' => '399.000₫', 'badge' => '-30%', 'image' => 'placeholder.jpg'],
                ['id' => 6, 'name' => 'Cyberpunk 2077', 'price' => '499.000₫', 'badge' => 'Hot', 'image' => 'placeholder.jpg'],
            ];
        }
        
        // Load view
        include '../views/client/index.php';
    }
}
