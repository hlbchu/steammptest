<?php
/* ==============================================
   PRODUCT MODEL - Mô hình dữ liệu cho sản phẩm
   ============================================== */

class Product {
    private $db;
    private $table = 'products';

    // Properties
    public $id;
    public $name;
    public $category_id;
    public $description;
    public $price;
    public $discount;
    public $stock;
    public $image;
    public $status;
    public $created_at;
    public $updated_at;

    /**
     * Constructor - Khởi tạo kết nối database
     * @param $db - Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Lấy tất cả sản phẩm
     * @param int $limit - Số bản ghi tối đa
     * @param int $offset - Vị trí bắt đầu
     * @return array
     */
    public function getAll($limit = 20, $offset = 0) {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Lấy sản phẩm theo ID
     * @param int $id - ID sản phẩm
     * @return array|null
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Lấy sản phẩm hot (theo đánh dấu hot hoặc đang giảm giá)
     * @param int $limit - Số sản phẩm
     * @return array
     */
    public function getHotProducts($limit = 12) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE status = 'active' 
                    AND (is_hot = 1 OR (sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price))
                  ORDER BY is_hot DESC, created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Lấy sản phẩm mới (theo ngày tạo)
     * @param int $limit - Số sản phẩm
     * @return array
     */
    public function getNewProducts($limit = 12) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE status = 'active' 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Tìm kiếm sản phẩm
     * @param string $keyword - Từ khóa
     * @return array
     */
    public function search($keyword) {
                $query = "SELECT * FROM " . $this->table . " 
                                    WHERE status = 'active'
                                        AND (name LIKE :keyword OR description LIKE :keyword)
                                    ORDER BY created_at DESC
                                    LIMIT 50";
        
        $stmt = $this->db->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->bindParam(':keyword', $keyword);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Thêm sản phẩm mới
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (name, category_id, description, price, discount, stock, image, status, created_at, updated_at)
                  VALUES
                  (:name, :category_id, :description, :price, :discount, :stock, :image, :status, NOW(), NOW())";
        
        $stmt = $this->db->prepare($query);

        // Bind values
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':discount', $this->discount);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':image', $this->image);
        $stmt->bindParam(':status', $this->status);

        return $stmt->execute();
    }

    /**
     * Cập nhật sản phẩm
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET
                    name = :name,
                    category_id = :category_id,
                    description = :description,
                    price = :price,
                    discount = :discount,
                    stock = :stock,
                    image = :image,
                    status = :status,
                    updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);

        // Bind values
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':discount', $this->discount);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':image', $this->image);
        $stmt->bindParam(':status', $this->status);

        return $stmt->execute();
    }

    /**
     * Xóa sản phẩm
     * @param int $id - ID sản phẩm
     * @return bool
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Lấy tổng số sản phẩm
     * @return int
     */
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return (int)$result['total'];
    }

    /**
     * Lấy sản phẩm theo danh mục
     * @param int $category_id - ID danh mục
     * @param int $limit - Số sản phẩm
     * @return array
     */
    public function getByCategory($category_id, $limit = 20) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE category_id = :category_id AND status = 'active'
                  ORDER BY created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Kiểm tra sản phẩm còn kho
     * @param int $id - ID sản phẩm
     * @return bool
     */
    public function isInStock($id) {
        $query = "SELECT stock FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result && (int)$result['stock'] > 0;
    }

    /**
     * Giảm số lượng kho sau khi bán
     * @param int $id - ID sản phẩm
     * @param int $quantity - Số lượng mua
     * @return bool
     */
    public function reduceStock($id, $quantity) {
        $query = "UPDATE " . $this->table . " 
                  SET stock = stock - :quantity 
                  WHERE id = :id AND stock >= :quantity";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    /**
     * Tính giá cuối cùng sau giảm giá
     * @param float $price - Giá gốc
     * @param float $discount - Phần trăm giảm giá
     * @return float
     */
    public static function calculateFinalPrice($price, $discount = 0) {
        return $price - ($price * $discount / 100);
    }
}
