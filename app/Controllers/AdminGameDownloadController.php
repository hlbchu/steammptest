<?php
/**
 * ADMIN GAME DOWNLOAD CONTROLLER - Quản lý link tải game
 */

class AdminGameDownloadController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        
        // Kiểm tra quyền admin
        AuthMiddleware::checkAdmin();
    }
    
    /**
     * Hiển thị danh sách download links
     */
    public function index() {
        // Lấy tất cả game downloads
        $downloads = $this->getAllDownloads();
        
        // Lấy tất cả sản phẩm (games)
        $products = $this->getAllProducts();
        
        include '../views/admin/game-downloads.php';
    }
    
    /**
     * Lấy tất cả game downloads
     */
    public function getAllDownloads() {
        $query = "SELECT 
                    gd.*,
                    p.name as product_name,
                    p.id as product_id,
                    COUNT(DISTINCT ugd.user_id) as total_users,
                    SUM(ugd.download_count) as total_downloads
                  FROM game_downloads gd
                  LEFT JOIN products p ON gd.product_id = p.id
                  LEFT JOIN user_game_downloads ugd ON gd.id = ugd.game_download_id
                  GROUP BY gd.id
                  ORDER BY gd.product_id ASC, gd.created_at DESC";
        
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy tất cả sản phẩm
     */
    public function getAllProducts() {
        $query = "SELECT id, name, slug FROM products WHERE status = 'active' ORDER BY name ASC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy thông tin chi tiết download
     */
    public function getDownloadDetail($id) {
        $query = "SELECT gd.*, p.name as product_name 
                  FROM game_downloads gd
                  LEFT JOIN products p ON gd.product_id = p.id
                  WHERE gd.id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Thêm link tải game mới
     */
    public function addDownload() {
        header('Content-Type: application/json');
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $download_name = isset($_POST['download_name']) ? trim($_POST['download_name']) : '';
        $download_url = isset($_POST['download_url']) ? trim($_POST['download_url']) : '';
        $file_size = isset($_POST['file_size']) ? trim($_POST['file_size']) : '';
        $download_method = isset($_POST['download_method']) ? trim($_POST['download_method']) : 'direct';
        $mirror_url = isset($_POST['mirror_url']) ? trim($_POST['mirror_url']) : null;
        $version = isset($_POST['version']) ? trim($_POST['version']) : '1.0';
        $system_requirements = isset($_POST['system_requirements']) ? trim($_POST['system_requirements']) : null;
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        
        // Validate
        if (!$product_id || !$download_name || !$download_url) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
            return;
        }
        
        // Check product exists
        $checkQuery = "SELECT id FROM products WHERE id = ?";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bind_param('i', $product_id);
        $checkStmt->execute();
        if (!$checkStmt->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
            return;
        }
        
        // Insert download
        $query = "INSERT INTO game_downloads (
                    product_id, download_name, download_url, file_size, 
                    download_method, mirror_url, version, system_requirements, 
                    description, is_active
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param(
            'isssssss',
            $product_id, $download_name, $download_url, $file_size,
            $download_method, $mirror_url, $version, $system_requirements, $description
        );
        
        if ($stmt->execute()) {
            $new_id = $this->db->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Thêm link tải game thành công',
                'id' => $new_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $this->db->error]);
        }
    }
    
    /**
     * Cập nhật link tải game
     */
    public function updateDownload() {
        header('Content-Type: application/json');
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $download_name = isset($_POST['download_name']) ? trim($_POST['download_name']) : '';
        $download_url = isset($_POST['download_url']) ? trim($_POST['download_url']) : '';
        $file_size = isset($_POST['file_size']) ? trim($_POST['file_size']) : '';
        $download_method = isset($_POST['download_method']) ? trim($_POST['download_method']) : 'direct';
        $mirror_url = isset($_POST['mirror_url']) ? trim($_POST['mirror_url']) : null;
        $version = isset($_POST['version']) ? trim($_POST['version']) : '1.0';
        $system_requirements = isset($_POST['system_requirements']) ? trim($_POST['system_requirements']) : null;
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
        
        // Validate
        if (!$id || !$product_id || !$download_name || !$download_url) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
            return;
        }
        
        // Get old URL for history
        $oldDownload = $this->getDownloadDetail($id);
        $old_url = $oldDownload ? $oldDownload['download_url'] : null;
        
        // Update download
        $query = "UPDATE game_downloads SET 
                    product_id = ?, download_name = ?, download_url = ?,
                    file_size = ?, download_method = ?, mirror_url = ?,
                    version = ?, system_requirements = ?, description = ?,
                    is_active = ?
                  WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param(
            'isssssssi',
            $product_id, $download_name, $download_url, $file_size,
            $download_method, $mirror_url, $version, $system_requirements, $description,
            $is_active, $id
        );
        
        if ($stmt->execute()) {
            // Lưu vào history nếu URL thay đổi
            if ($old_url && $old_url !== $download_url) {
                $this->logDownloadUrlChange($id, $old_url, $download_url);
            }
            
            echo json_encode(['success' => true, 'message' => 'Cập nhật link tải game thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $this->db->error]);
        }
    }
    
    /**
     * Xóa link tải game
     */
    public function deleteDownload() {
        header('Content-Type: application/json');
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            return;
        }
        
        $query = "DELETE FROM game_downloads WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Xóa link tải game thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $this->db->error]);
        }
    }
    
    /**
     * Lấy thống kê download của 1 game
     */
    public function getDownloadStats($download_id) {
        $query = "SELECT 
                    COUNT(DISTINCT user_id) as total_users,
                    SUM(download_count) as total_downloads,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_downloads,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_downloads
                  FROM download_logs 
                  WHERE game_download_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $download_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Lưu lịch sử thay đổi URL
     */
    private function logDownloadUrlChange($game_download_id, $old_url, $new_url) {
        $query = "INSERT INTO download_links_history (
                    game_download_id, old_url, new_url, reason, changed_by, created_at
                  ) VALUES (?, ?, ?, ?, ?, NOW())";
        
        $reason = 'Admin update';
        $admin_id = $_SESSION['user_id'] ?? null;
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('isssi', $game_download_id, $old_url, $new_url, $reason, $admin_id);
        $stmt->execute();
    }
}
?>
