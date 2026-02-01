# 📥 Hệ Thống Quản Lý Link Tải Game - Hướng Dẫn Sử Dụng

## Tổng Quan

Hệ thống này cho phép bạn quản lý các link tải game cho những khách hàng đã mua. Sau khi khách hàng hoàn tất thanh toán, họ sẽ có quyền truy cập vào các link tải game.

## 📊 Cấu Trúc Database

### 1. **game_downloads** - Lưu trữ link tải
```sql
- id: ID link
- product_id: ID game
- download_name: Tên bản tải (vd: "ELDEN RING - Full Game")
- download_url: Link tải chính
- file_size: Kích thước file (60GB, 100GB, etc)
- download_method: Phương thức tải (direct, torrent, mirror)
- mirror_url: Link dự phòng
- version: Phiên bản game
- system_requirements: Yêu cầu hệ thống
- description: Mô tả
- is_active: Kích hoạt/Vô hiệu
```

### 2. **user_game_downloads** - Quyền truy cập của người dùng
```sql
- user_id: ID người dùng
- product_id: ID game
- order_id: ID đơn hàng (liên kết thanh toán)
- game_download_id: ID link tải
- download_count: Số lần đã tải
- first_download_at: Lần tải đầu tiên
- last_download_at: Lần tải cuối cùng
- expires_at: Thời hạn truy cập (tùy chọn)
```

### 3. **download_logs** - Log mỗi lần tải
```sql
- user_id: Người dùng
- product_id: Game
- download_url: Link được tải
- ip_address: Địa chỉ IP
- user_agent: Thông tin thiết bị
- status: trạng thái (started, completed, failed, paused)
- bytes_downloaded: Dung lượng đã tải
- error_message: Lỗi nếu có
```

## 🎮 Cách Sử Dụng Admin Panel

### Truy cập trang quản lý:
```
/public/index.php?page=admin&section=game-downloads
```

### Các chức năng chính:

#### 1. **Thêm Link Tải Mới**
- Click nút "+ Thêm Link Tải"
- Điền thông tin:
  - **Game**: Chọn game từ danh sách
  - **Tên Tải**: VD "ELDEN RING - Full Game"
  - **Link Tải**: URL tải trực tiếp
  - **Kích Thước**: VD "60GB"
  - **Phương Thức**: Direct/Torrent/Mirror
  - **Link Dự Phòng**: Tùy chọn
  - **Phiên Bản**: VD "1.0"
  - **Yêu Cầu Hệ Thống**: Chi tiết cấu hình máy cần thiết
  - **Mô Tả**: Thông tin bổ sung
  - **Kích Hoạt Ngay**: Checkbox để bật ngay

#### 2. **Sửa Link Tải**
- Click nút "✎ Sửa" hàng tương ứng
- Chỉnh sửa thông tin (hay dùng để cập nhật URL nếu link cũ hỏng)
- Click "Lưu"

#### 3. **Xóa Link Tải**
- Click nút "✕ Xóa"
- Xác nhận xóa
- Link sẽ bị xóa (không thể phục hồi)

#### 4. **Xem Thống Kê**
- Click nút "📊" (thống kê)
- Xem số lượt tải, người dùng, tải thành công/thất bại

### Bộ Lọc:
- **Game**: Lọc theo game cụ thể
- **Trạng Thái**: Chỉ show link Kích Hoạt hoặc Vô Hiệu
- **Tìm Kiếm**: Tìm theo tên link, kích thước, phiên bản

## 🔗 Tích Hợp Với Hệ Thống

### Khi khách hàng mua game:

1. **Tạo order**: Lưu vào bảng `orders`
2. **Tạo order_items**: Chi tiết game đã mua
3. **Tạo user_game_downloads**: Tự động link user → game_downloads → order
4. **Bảo mật**: Kiểm tra user_game_downloads trước khi cho tải

### Ví dụ code (trong CartController hoặc CheckoutController):

```php
// Sau khi thanh toán thành công
$db->query("INSERT INTO user_game_downloads (
    user_id, product_id, order_id, game_download_id, is_active
) SELECT 
    ?, ?, ?, gd.id, 1
FROM game_downloads gd
WHERE gd.product_id = ? AND gd.is_active = 1
LIMIT 1");
```

## 📥 Cách Khách Hàng Tải Game

### Quy trình:
1. Khách hàng đăng nhập vào tài khoản
2. Vào mục "Tài khoản" hoặc "Tải Game Của Tôi"
3. Xem danh sách game đã mua
4. Click "Tải Game" để lấy link
5. Chọn phương thức tải (Direct/Mirror/Torrent)
6. Bắt đầu tải về

## 🔐 Bảo Mật

### Kiểm tra quyền truy cập:

```php
// Trước khi cho tải, kiểm tra:
$check = $db->query("SELECT * FROM user_game_downloads 
                    WHERE user_id = ? AND game_download_id = ? AND is_active = 1");

if (!$check->num_rows) {
    // Từ chối truy cập
    die('Bạn không có quyền tải game này');
}
```

### Rate Limiting:
```php
// Nên implement throttling để tránh lạm dụng
$lastDownload = // Kiểm tra last_download_at
if ($lastDownload > time() - 3600) {
    // Chờ 1 giờ trước khi tải lại
}
```

## 📊 Thống Kê & Báo Cáo

### Query lấy thống kê:

```sql
-- Tải nhiều nhất
SELECT p.name, COUNT(ugd.user_id) as total_users, SUM(ugd.download_count) as total_downloads
FROM user_game_downloads ugd
JOIN products p ON ugd.product_id = p.id
GROUP BY p.id
ORDER BY total_downloads DESC;

-- Lỗi tải:
SELECT COUNT(*) as failed, user_id, product_id
FROM download_logs
WHERE status = 'failed'
GROUP BY user_id, product_id;
```

## 🚀 Các Tính Năng Tương Lai

- [ ] Tích hợp thanh toán tự động
- [ ] Gửi link tải qua email
- [ ] Resume download (tiếp tục tải từ nơi dừng)
- [ ] Tải được 1 lần duy nhất
- [ ] Hết hạn quyền truy cập
- [ ] Download manager
- [ ] Antivirus scan file tải
- [ ] Bandwidth throttling

## ⚙️ Các Lệnh SQL Hữu Ích

### Cấp quyền tải cho user:
```sql
INSERT INTO user_game_downloads (user_id, product_id, order_id, game_download_id, is_active)
SELECT 2, 1, (SELECT MAX(id) FROM orders WHERE user_id = 2), gd.id, 1
FROM game_downloads gd
WHERE gd.product_id = 1 AND gd.is_active = 1
LIMIT 1;
```

### Hủy quyền tải:
```sql
UPDATE user_game_downloads 
SET is_active = 0 
WHERE user_id = 2 AND product_id = 1;
```

### Kiểm tra ai đã tải:
```sql
SELECT u.username, p.name, COUNT(dl.id) as download_count
FROM download_logs dl
JOIN users u ON dl.user_id = u.id
JOIN products p ON dl.product_id = p.id
GROUP BY dl.user_id, dl.product_id;
```

## 📞 Hỗ Trợ

Nếu có lỗi hoặc câu hỏi, kiểm tra:
- `download_logs` để xem chi tiết lỗi tải
- `download_links_history` để xem lịch sử thay đổi URL
- Log file server trong `logs/` folder
