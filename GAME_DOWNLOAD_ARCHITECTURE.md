# 📥 Game Download System - Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     SteamWeb Game Store                       │
└─────────────────────────────────────────────────────────────┘

                    KHÁCH HÀNG (Customer)
                           ▼
                    THANH TOÁN (Payment)
                           ▼
         ┌──────────────────────────────────────┐
         │   TẠO ĐƠNHÀNG (Create Order)         │
         │   - orders table                      │
         │   - order_items table                 │
         └──────────────────────────────────────┘
                           ▼
         ┌──────────────────────────────────────┐
         │  CẤP QUYỀN TẢI (Grant Access)        │
         │  - user_game_downloads table          │
         │  - Link user → game → download_link   │
         └──────────────────────────────────────┘
                           ▼
         ┌──────────────────────────────────────┐
         │  KHÁCH HÀNG TẢI GAME (Download)      │
         │  - download_logs table                │
         │  - Log IP, device, status, errors     │
         └──────────────────────────────────────┘
```

## Database Schema

```
┌──────────────────────────┐
│   products (Games)       │
├──────────────────────────┤
│ id (PK)                  │
│ name                     │
│ price                    │
│ ...                      │
└────────────┬─────────────┘
             │
             │ 1:N
             ▼
┌──────────────────────────────────┐
│   game_downloads                 │ ◄─── Tạo admin
├──────────────────────────────────┤
│ id (PK)                          │
│ product_id (FK → products)       │
│ download_name                    │
│ download_url ⭐ LINK TẢI         │
│ file_size (60GB, 100GB, etc)     │
│ download_method (direct/torrent) │
│ version (1.0, 2.0, etc)          │
│ system_requirements              │
│ mirror_url (dự phòng)            │
│ is_active                        │
└────────────┬─────────────────────┘
             │
             │ 1:1
             ▼
┌──────────────────────────────────────┐
│   user_game_downloads                │
├──────────────────────────────────────┤
│ id (PK)                              │
│ user_id (FK → users) - Ai           │
│ product_id (FK → products)           │
│ order_id (FK → orders) - Thanh toán │
│ game_download_id (FK) - Link tải    │
│ download_count (Số lần tải)         │
│ first_download_at                    │
│ last_download_at                     │
│ expires_at (Tùy chọn)               │
│ is_active                            │
└────────────┬─────────────────────────┘
             │
             │ 1:N
             ▼
┌──────────────────────────────────┐
│   download_logs                  │
├──────────────────────────────────┤
│ id (PK)                          │
│ user_id (FK)                     │
│ product_id (FK)                  │
│ download_url                     │
│ ip_address (192.168.1.1)         │
│ user_agent (Browser info)        │
│ status (started/completed/failed)│
│ bytes_downloaded                 │
│ error_message                    │
│ created_at                       │
└──────────────────────────────────┘

┌──────────────────────────────────┐
│  download_links_history          │
├──────────────────────────────────┤
│ id (PK)                          │
│ game_download_id (FK)            │
│ old_url (URL cũ)                 │
│ new_url (URL mới)                │
│ reason (Lý do thay đổi)          │
│ changed_by (Admin user id)       │
│ created_at                       │
└──────────────────────────────────┘
```

## Admin Panel Flow

```
┌────────────────────────────────────────┐
│  Admin Panel                           │
│  /admin?section=game-downloads         │
└────────┬─────────────────────────────┬─┘
         │                             │
    ┌────▼─────────┐          ┌────────▼──────┐
    │ Danh Sách Link│          │  Bộ Lọc       │
    │ (Bảng)        │          │ - Game        │
    ├───────────────┤          │ - Trạng thái  │
    │ ID            │          │ - Tìm kiếm    │
    │ Game          │          └───────────────┘
    │ Tên Tải       │
    │ Kích Thước    │
    │ Phương Thức   │
    │ Phiên Bản     │
    │ Lượt Tải      │
    │ Người Dùng    │
    │ Trạng Thái    │
    │ Hành Động:    │
    │ [✎] [✕] [📊]│
    └────┬──────┬──┬──────┐
         │      │  │      │
    ┌────▼─┐ ┌──▼──┐ │  ┌──▼────┐
    │ Sửa  │ │ Xóa │ │  │Thống kê
    │Modal │ │      │ │  │Modal
    └──────┘ └─────┘ │  └────────┘
                      │
                  ┌───▼────┐
                  │ Thêm   │
                  │Modal   │
                  └────────┘
```

## Form Thêm/Sửa Link

```
┌─────────────────────────────────────────┐
│ Modal: Thêm/Sửa Link Tải Game          │
├─────────────────────────────────────────┤
│                                         │
│ Game *              [Chọn game ▼]       │
│ Tên Tải *           [ELDEN RING...]     │
│                                         │
│ Link Tải *          [https://...]       │
│ Kích Thước          [60GB]              │
│                                         │
│ Phương Thức *       [Direct ▼]          │
│                 • Direct                │
│                 • Torrent               │
│                 • Mirror                │
│ Phiên Bản           [1.0]               │
│                                         │
│ Link Dự Phòng       [https://...]       │
│                                         │
│ Yêu Cầu Hệ Thống                       │
│ ┌─────────────────────────────────────┐│
│ │Windows 10/11 64bit                  ││
│ │Intel i5-10600K, RTX 2080            ││
│ │60GB SSD, 16GB RAM                   ││
│ └─────────────────────────────────────┘│
│                                         │
│ Mô Tả                                  │
│ ┌─────────────────────────────────────┐│
│ │Full game installer with all updates ││
│ └─────────────────────────────────────┘│
│                                         │
│ ☑ Kích hoạt ngay                       │
│                                         │
│                  [Hủy]  [Lưu]          │
└─────────────────────────────────────────┘
```

## Workflow: User Mua Game

```
User Mua Game
    │
    ▼
┌─────────────────────────────┐
│ 1. Thêm vào Giỏ Hàng       │
│    (cart table)             │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ 2. Thanh Toán               │
│    (Tạo order)              │
│    (create order_items)     │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ 3. Cấp Quyền Tải            │
│    (insert user_game        │
│     _downloads)             │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ 4. User Tải Game            │
│    (log vào download_logs)  │
└─────────────────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ 5. Thống Kê                 │
│    (Query download_logs,    │
│     user_game_downloads)    │
└─────────────────────────────┘
```

## API Endpoints

```
GET  /public/index.php?page=admin&section=game-downloads
     → Display admin interface

POST /public/index.php?action=admin_game_download_add
     → Add new download link
     Params: product_id, download_name, download_url, file_size, ...

POST /public/index.php?action=admin_game_download_update
     → Update download link
     Params: id, product_id, download_name, download_url, ...

POST /public/index.php?action=admin_game_download_delete
     → Delete download link
     Params: id

POST /public/index.php?action=admin_game_download_detail
     → Get download details (for modal edit)
     Params: id
     Returns: JSON

POST /public/index.php?action=admin_game_download_stats
     → Get download statistics
     Params: id
     Returns: JSON {total_downloads, total_users, completed_downloads, failed_downloads}
```

## Security Flow

```
┌─────────────────────────────────┐
│ User yêu cầu tải game           │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ 1. Kiểm tra đăng nhập           │
│    $_SESSION['logged_in']       │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ 2. Query user_game_downloads    │
│    WHERE user_id = ?            │
│    AND product_id = ?           │
│    AND is_active = 1            │
└────────────┬────────────────────┘
             │
        ┌────┴────┐
        │          │
    YES │          │ NO
       ▼           ▼
   ┌────────┐  ┌────────────┐
   │Cho tải │  │Từ chối      │
   │↓       │  │Error: 403  │
   │Trả link│  │Forbidden   │
   └────────┘  └────────────┘
```

## Statistics Query

```sql
SELECT 
    p.name as 'Game',
    COUNT(DISTINCT ugd.user_id) as 'Unique Users',
    SUM(ugd.download_count) as 'Total Downloads',
    COUNT(DISTINCT dl.id) as 'Download Attempts',
    SUM(CASE WHEN dl.status = 'completed' THEN 1 ELSE 0 END) as 'Completed',
    SUM(CASE WHEN dl.status = 'failed' THEN 1 ELSE 0 END) as 'Failed',
    MAX(dl.created_at) as 'Last Download'
FROM products p
LEFT JOIN user_game_downloads ugd ON p.id = ugd.product_id
LEFT JOIN download_logs dl ON ugd.user_id = dl.user_id AND ugd.product_id = dl.product_id
GROUP BY p.id
ORDER BY total_downloads DESC;
```

---

**System Architecture Diagram**  
Created: January 31, 2026  
SteamWeb Game Store
