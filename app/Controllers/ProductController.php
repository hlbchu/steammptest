<?php
/**
 * PRODUCT CONTROLLER - Xử lý sản phẩm (Client)
 */

require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

class ProductController {
    private $db;
    private $productModel;

    public function __construct($db) {
        $this->db = $db;
        $this->ensureDbConnection();
        $this->productModel = new Product($this->db);
    }

    private function ensureDbConnection() {
        if (!($this->db instanceof PDO)) {
            $database = new Database();
            $this->db = $database->connect();
        }
    }

    /**
     * Hiển thị danh sách sản phẩm
     */
    public function index() {
        $products = $this->productModel->getAll(20, 0);
        include '../views/client/products.php';
    }

    /**
     * Hiển thị chi tiết sản phẩm
     */
    public function detail($id) {
        if (empty($id)) {
            header('Location: ?page=home');
            exit;
        }

        $stmt = $this->db->prepare("SELECT p.*, c.name AS category_name
                                    FROM products p
                                    LEFT JOIN categories c ON p.category_id = c.id
                                    WHERE p.id = :id
                                    LIMIT 1");
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            header('Location: ?page=home');
            exit;
        }

            include __DIR__ . '/../../views/client/product-detail.php';
    }

    /**
     * Tìm kiếm sản phẩm
     */
    public function search($keyword) {
        $products = $this->productModel->search($keyword);
        include '../views/client/search.php';
    }

    /**
     * Sản phẩm hot
     */
    public function hot() {
        $products = $this->productModel->getHotProducts(24);
        include '../views/client/hot.php';
    }

    /**
     * Sản phẩm mới
     */
    public function new() {
        $products = $this->productModel->getNewProducts(24);
        include '../views/client/new.php';
    }
}
