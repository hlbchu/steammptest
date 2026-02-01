<?php
/**
 * Admin Navigation Menu Controller
 * Quản lý các mục menu điều hướng
 */

class AdminNavMenuController {
    
    /**
     * Get all navigation menu items
     */
    public function index($db) {
        header('Content-Type: application/json');
        
        try {
            $db->query("SELECT * FROM nav_menu ORDER BY display_order ASC, id ASC");
            $menus = $db->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $menus
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách menu: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Create new menu item
     */
    public function create($db) {
        header('Content-Type: application/json');
        
        try {
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $slug = $this->generateSlug($name);
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $parent_id = (!empty($_POST['parent_id']) && (int)$_POST['parent_id'] > 0) ? (int)$_POST['parent_id'] : NULL;
            
            // Validation
            if (empty($name)) {
                throw new Exception('Tên menu không được để trống');
            }
            
            // Check if slug already exists
            $db->query("SELECT id FROM nav_menu WHERE slug = :slug");
            $db->bind(':slug', $slug);
            if ($db->fetch()) {
                throw new Exception('Menu với slug này đã tồn tại');
            }
            
            // Insert
            $db->query("INSERT INTO nav_menu (parent_id, name, slug, icon, text_color, link, display_order, is_active, is_protected) 
                       VALUES (:parent_id, :name, :slug, :icon, :text_color, :link, :display_order, :is_active, :is_protected)");
            $db->bind(':parent_id', $parent_id);
            $db->bind(':name', $name);
            $db->bind(':slug', $slug);
            $db->bind(':icon', $icon);
            $db->bind(':text_color', $_POST['text_color'] ?? '#ffffff');
            $db->bind(':link', $_POST['link'] ?? '');
            $db->bind(':display_order', $display_order);
            $db->bind(':is_active', $is_active);
            $db->bind(':is_protected', isset($_POST['is_protected']) ? 1 : 0);
            $db->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Thêm menu thành công!'
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
     * Update menu item
     */
    public function update($db) {
        header('Content-Type: application/json');
        
        try {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $parent_id = (!empty($_POST['parent_id']) && (int)$_POST['parent_id'] > 0) ? (int)$_POST['parent_id'] : NULL;
            
            // Validation
            if ($id <= 0) {
                throw new Exception('ID không hợp lệ');
            }
            
            if (empty($name)) {
                throw new Exception('Tên menu không được để trống');
            }
            
            // Check if menu exists
            $db->query("SELECT slug, is_protected FROM nav_menu WHERE id = :id");
            $db->bind(':id', $id);
            $menu = $db->fetch();
            
            if (!$menu) {
                throw new Exception('Menu không tồn tại');
            }
            
            // Xử lý is_protected: Trang chủ (slug=index) luôn là protected
            $is_protected = $menu['is_protected'];
            if ($menu['slug'] === 'index') {
                // Trang chủ luôn protected, không cho phép thay đổi
                $is_protected = 1;
            } else {
                // Các menu khác cho phép bật/tắt protection
                $is_protected = isset($_POST['is_protected']) ? 1 : 0;
            }
            
            // Generate new slug
            $slug = $this->generateSlug($name);
            
            // Check if new slug conflicts with other menus
            $db->query("SELECT id FROM nav_menu WHERE slug = :slug AND id != :id");
            $db->bind(':slug', $slug);
            $db->bind(':id', $id);
            if ($db->fetch()) {
                throw new Exception('Menu với slug này đã tồn tại');
            }
            
            // Update
            $db->query("UPDATE nav_menu 
                       SET parent_id = :parent_id, name = :name, slug = :slug, icon = :icon, text_color = :text_color, 
                           link = :link, display_order = :display_order, is_active = :is_active, 
                           is_protected = :is_protected 
                       WHERE id = :id");
            $db->bind(':parent_id', $parent_id);
            $db->bind(':name', $name);
            $db->bind(':slug', $slug);
            $db->bind(':icon', $icon);
            $db->bind(':text_color', $_POST['text_color'] ?? '#ffffff');
            $db->bind(':link', $_POST['link'] ?? '');
            $db->bind(':display_order', $display_order);
            $db->bind(':is_active', $is_active);
            $db->bind(':is_protected', $is_protected);
            $db->bind(':id', $id);
            $db->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật menu thành công!'
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
     * Delete menu item
     */
    public function delete($db) {
        header('Content-Type: application/json');
        
        try {
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID không hợp lệ');
            }
            
            // Check if menu is protected
            $db->query("SELECT is_protected, name FROM nav_menu WHERE id = :id");
            $db->bind(':id', $id);
            $menu = $db->fetch();
            
            if (!$menu) {
                throw new Exception('Menu không tồn tại');
            }
            
            if ($menu['is_protected']) {
                throw new Exception('Không thể xóa menu "' . $menu['name'] . '" vì nó được bảo vệ');
            }
            
            // Delete
            $db->query("DELETE FROM nav_menu WHERE id = :id");
            $db->bind(':id', $id);
            $db->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Xóa menu thành công!'
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
     * Toggle active status
     */
    public function toggleActive($db) {
        header('Content-Type: application/json');
        
        try {
            $id = (int)($_POST['id'] ?? 0);
            $is_active = (int)($_POST['is_active'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID không hợp lệ');
            }
            
            $db->query("UPDATE nav_menu SET is_active = :is_active WHERE id = :id");
            $db->bind(':is_active', $is_active);
            $db->bind(':id', $id);
            $db->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công!'
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
     * Generate URL slug from name
     */
    private function generateSlug($name) {
        // Convert Vietnamese to ASCII
        $name = $this->removeVietnameseAccents($name);
        // Convert to lowercase and replace spaces with hyphens
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        return $slug;
    }
    
    /**
     * Remove Vietnamese accents
     */
    private function removeVietnameseAccents($str) {
        $vietnameseMap = array(
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
            'đ'=>'d',
            'À'=>'A','Á'=>'A','Ạ'=>'A','Ả'=>'A','Ã'=>'A','Â'=>'A','Ầ'=>'A','Ấ'=>'A','Ậ'=>'A','Ẩ'=>'A','Ẫ'=>'A','Ă'=>'A','Ằ'=>'A','Ắ'=>'A','Ặ'=>'A','Ẳ'=>'A','Ẵ'=>'A',
            'È'=>'E','É'=>'E','Ẹ'=>'E','Ẻ'=>'E','Ẽ'=>'E','Ê'=>'E','Ề'=>'E','Ế'=>'E','Ệ'=>'E','Ể'=>'E','Ễ'=>'E',
            'Ì'=>'I','Í'=>'I','Ị'=>'I','Ỉ'=>'I','Ĩ'=>'I',
            'Ò'=>'O','Ó'=>'O','Ọ'=>'O','Ỏ'=>'O','Õ'=>'O','Ô'=>'O','Ồ'=>'O','Ố'=>'O','Ộ'=>'O','Ổ'=>'O','Ỗ'=>'O','Ơ'=>'O','Ờ'=>'O','Ớ'=>'O','Ợ'=>'O','Ở'=>'O','Ỡ'=>'O',
            'Ù'=>'U','Ú'=>'U','Ụ'=>'U','Ủ'=>'U','Ũ'=>'U','Ư'=>'U','Ừ'=>'U','Ứ'=>'U','Ự'=>'U','Ử'=>'U','Ữ'=>'U',
            'Ỳ'=>'Y','Ý'=>'Y','Ỵ'=>'Y','Ỷ'=>'Y','Ỹ'=>'Y',
            'Đ'=>'D'
        );
        return strtr($str, $vietnameseMap);
    }

    /**
     * Get submenus for a parent menu
     */
    public function getSubmenus($db) {
        header('Content-Type: application/json');
        
        try {
            $parent_id = (int)($_GET['parent_id'] ?? 0);
            
            if ($parent_id <= 0) {
                throw new Exception('ID không hợp lệ');
            }
            
            $db->query("SELECT id, name, link, icon, text_color, display_order FROM nav_menu WHERE parent_id = :parent_id ORDER BY display_order ASC, id ASC");
            $db->bind(':parent_id', $parent_id);
            $submenus = $db->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $submenus
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
     * Create submenu
     */
    public function createSubmenu($db) {
        header('Content-Type: application/json');
        
        try {
            $parent_id = (int)($_POST['parent_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $link = trim($_POST['link'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $text_color = trim($_POST['text_color'] ?? '#ffffff');
            $display_order = (int)($_POST['display_order'] ?? 0);
            
            if ($parent_id <= 0 || empty($name) || empty($link)) {
                throw new Exception('Thông tin không đủ');
            }
            
            $slug = $this->generateSlug($name);
            
            // Check if parent exists
            $db->query("SELECT id FROM nav_menu WHERE id = :id");
            $db->bind(':id', $parent_id);
            if (!$db->fetch()) {
                throw new Exception('Menu cha không tồn tại');
            }
            
            // Insert with icon and text_color
            $db->query("INSERT INTO nav_menu (parent_id, name, slug, link, icon, text_color, display_order, is_active) 
                       VALUES (:parent_id, :name, :slug, :link, :icon, :text_color, :display_order, 1)");
            $db->bind(':parent_id', $parent_id);
            $db->bind(':name', $name);
            $db->bind(':slug', $slug);
            $db->bind(':link', $link);
            $db->bind(':icon', $icon);
            $db->bind(':text_color', $text_color);
            $db->bind(':display_order', $display_order);
            $db->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Thêm trang mục con thành công!'
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
     * Update submenu
     */
    public function updateSubmenu($db) {
        header('Content-Type: application/json');
        
        try {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $link = trim($_POST['link'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $text_color = trim($_POST['text_color'] ?? '#ffffff');
            $display_order = (int)($_POST['display_order'] ?? 0);
            
            if ($id <= 0 || empty($name) || empty($link)) {
                throw new Exception('Thông tin không đủ');
            }
            
            $slug = $this->generateSlug($name);
            
            // Update with icon and text_color
            $db->query("UPDATE nav_menu SET name = :name, slug = :slug, link = :link, icon = :icon, text_color = :text_color, display_order = :display_order WHERE id = :id");
            $db->bind(':icon', $icon);
            $db->bind(':text_color', $text_color);
            $db->bind(':display_order', $display_order);
            $db->bind(':name', $name);
            $db->bind(':slug', $slug);
            $db->bind(':link', $link);
            $db->bind(':id', $id);
            $db->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật trang mục con thành công!'
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
     * Delete submenu
     */
    public function deleteSubmenu($db) {
        header('Content-Type: application/json');
        
        try {
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID không hợp lệ');
            }
            
            $db->query("DELETE FROM nav_menu WHERE id = :id AND parent_id IS NOT NULL");
            $db->bind(':id', $id);
            $db->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Xóa trang mục con thành công!'
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
