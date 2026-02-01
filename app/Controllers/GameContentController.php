<?php
/**
 * Game Content Controller - Hiển thị nội dung chi tiết game
 */

class GameContentController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function view($id = 2) {
        // Get game data
        if ($this->db) {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $product = null;
        }

        if (!$product) {
            $product = [
                'id' => $id,
                'name' => 'Sản phẩm không tồn tại',
                'content' => 'Không tìm thấy nội dung cho sản phẩm này.',
                'description' => '',
                'gallery' => '',
                'price' => 0,
                'rating' => 0,
                'status' => 'active',
                'image' => ''
            ];
        }

        // Parse gallery images
        $galleryImages = [];
        if (!empty($product['gallery'])) {
            $lines = preg_split('/[\r\n]+/', $product['gallery']);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $parts = (stripos($line, 'data:image') === 0)
                    ? [$line]
                    : preg_split('/\s*,\s*/', $line);

                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part === '') {
                        continue;
                    }

                    if (stripos($part, 'data:image') === 0) {
                        if (stripos($part, 'base64,') !== false) {
                            $galleryImages[] = $part;
                        }
                        continue;
                    }

                    if (preg_match('/^https?:\/\//i', $part)) {
                        $galleryImages[] = $part;
                        continue;
                    }

                    if (preg_match('/^\/9j/i', $part)) {
                        $galleryImages[] = 'data:image/jpeg;base64,' . $part;
                        continue;
                    }

                    if (preg_match('/^iVBOR/i', $part)) {
                        $galleryImages[] = 'data:image/png;base64,' . $part;
                        continue;
                    }

                    if (preg_match('/^R0lGOD/i', $part)) {
                        $galleryImages[] = 'data:image/gif;base64,' . $part;
                        continue;
                    }

                    $galleryImages[] = $part;
                }
            }
        }

        // Get main image
        $imageUrl = (filter_var($product['image'], FILTER_VALIDATE_URL)) 
            ? $product['image'] 
            : ($product['image'] ? 'assets/images/' . $product['image'] : 'assets/images/placeholder.jpg');

        // Load view
        require_once dirname(dirname(__DIR__)) . '/views/client/game-content-view.php';
    }
}
?>
