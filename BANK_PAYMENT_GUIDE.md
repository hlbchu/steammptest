# 📱 Bank Payment System with QR Code - Hướng Dẫn

## 🎯 Tổng Quan

Hệ thống thanh toán ngân hàng với mã QR cho phép:
- ✅ Quản lý tài khoản ngân hàng (Admin)
- ✅ Tạo & hiển thị mã QR thanh toán
- ✅ Người dùng chọn ngân hàng & nạp tiền
- ✅ Tự động cộng tiền vào ví khi thanh toán

---

## 📋 Cài Đặt & Cấu Hình

### 1. Database Migration

Chạy SQL migration để thêm QR code support:

```sql
-- Thêm cột QR code vào bank_accounts
ALTER TABLE `bank_accounts` 
ADD COLUMN `qr_code_data` LONGTEXT DEFAULT NULL COMMENT 'QR code data as base64 image' AFTER `icon_url`,
ADD COLUMN `qr_code_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL to QR code image' AFTER `qr_code_data`,
ADD COLUMN `qr_generated_at` DATETIME DEFAULT NULL COMMENT 'Thời gian tạo mã QR' AFTER `qr_code_url`;
```

**File**: `database/add-qr-code-to-banks.sql`

### 2. Tạo Folder Uploads

Tạo thư mục để lưu QR code:

```bash
mkdir -p public/uploads/qr
chmod 755 public/uploads/qr
```

### 3. API Endpoints

#### Admin Bank Management

**Controller**: `app/Controllers/AdminBankController.php`

| Method | Action | Mô Tả |
|--------|--------|-------|
| GET | get_gateways | Lấy danh sách payment gateway |
| POST | create_gateway | Tạo gateway mới |
| POST | update_gateway | Cập nhật gateway |
| POST | delete_gateway | Xóa gateway |
| GET | get_banks | Lấy danh sách tài khoản ngân hàng |
| POST | create_bank | Tạo tài khoản ngân hàng |
| POST | update_bank | Cập nhật tài khoản ngân hàng |
| POST | delete_bank | Xóa tài khoản ngân hàng |
| POST | generate_qr | Tạo mã QR cho ngân hàng |
| GET | get_qr | Lấy mã QR đã tạo |
| POST | reset_bank_total | Reset tổng tiền đã nhận |

#### User Deposit Management

**Controller**: `app/Controllers/DepositController.php`

| Method | Action | Mô Tả |
|--------|--------|-------|
| GET | get_active_banks | Lấy danh sách ngân hàng hoạt động |
| POST | create_deposit | Tạo yêu cầu nạp tiền |
| GET | get_user_deposits | Lấy lịch sử nạp tiền |
| POST | approve_deposit | Xác nhận nạp tiền (Admin) |
| POST | reject_deposit | Từ chối nạp tiền (Admin) |

---

## 🛠️ Hướng Dẫn Sử Dụng

### Cho Admin

#### 1. Quản Lý Payment Gateway

Truy cập: `/views/admin/banks.php`

**Các bước:**
1. Vào Tab "Payment Gateways"
2. Click "+ Thêm Gateway"
3. Nhập thông tin:
   - Tên Gateway (VD: SeaPay, PayPal)
   - Mã (VD: seapay, paypal)
   - API Key
   - API Secret
   - Webhook URL
4. Bật "Kích Hoạt"
5. Lưu

#### 2. Quản Lý Tài Khoản Ngân Hàng

**Các bước:**
1. Vào Tab "Tài Khoản Ngân Hàng"
2. Click "+ Thêm Tài Khoản Ngân Hàng"
3. Nhập thông tin:
   - Chọn Payment Gateway
   - Tên Ngân Hàng (VD: Vietcombank)
   - Mã Ngân Hàng (VD: VCB)
   - Số Tài Khoản
   - Chủ Tài Khoản
   - Icon URL (tuỳ chọn)
   - Mô Tả
   - Ngưỡng Tối Đa (nếu cần)
4. Bật "Kích Hoạt"
5. Lưu

#### 3. Tạo Mã QR

1. Trong bảng tài khoản ngân hàng, nhấp nút "Tạo" ở cột "Mã QR"
2. Xác nhận tạo mã QR
3. Mã QR sẽ được tạo từ Google Charts API
4. Nhấp "Xem" để xem mã QR đã tạo

#### 4. Xác Nhận Nạp Tiền

1. Vào "Quản Lý Thanh Toán" → Tab "Giao Dịch"
2. Xem danh sách các giao dịch chờ xử lý
3. Kiểm tra thông tin thanh toán
4. Click "Xác Nhận" để cộng tiền vào ví người dùng
5. Hoặc "Từ Chối" nếu giao dịch không hợp lệ

### Cho Người Dùng

#### 1. Nạp Tiền

Truy cập: `/views/client/deposit.php`

**Các bước:**
1. Đăng nhập vào tài khoản
2. Truy cập trang "Nạp Tiền"
3. Chọn **Ngân Hàng** từ danh sách bên trái
4. Nhập **Số Tiền Nạp** (tối thiểu 10,000 VND)
   - Hoặc click các nút Preset: 50K, 100K, 200K, 500K
5. Xem **Mã QR** ở bên phải
6. Xem thông tin **Tài Khoản Ngân Hàng**:
   - Tên Ngân Hàng
   - Số Tài Khoản
   - Chủ Tài Khoản

#### 2. Thanh Toán QR

**Các bước:**
1. Mở ứng dụng ngân hàng trên điện thoại
2. Chọn chức năng "Quét mã QR" hoặc "Chuyển tiền"
3. **Quét mã QR** hiển thị trên màn hình
4. Kiểm tra thông tin:
   - Số TK nhận
   - Số tiền
   - Chủ TK nhận
5. **Xác nhận** thanh toán
6. Hệ thống sẽ cập nhật tiền trong 1-2 phút (khi admin xác nhận)

#### 3. Kiểm Tra Lịch Sử

1. Truy cập "Lịch Sử Nạp Tiền"
2. Xem các giao dịch đã thực hiện:
   - Transaction ID
   - Số Tiền
   - Trạng Thái (Chờ xử lý / Thành công / Thất bại)
   - Thời Gian

---

## 📁 Cấu Trúc File

```
app/
├── Controllers/
│   ├── AdminBankController.php      # API quản lý ngân hàng (Admin)
│   └── DepositController.php        # API nạp tiền (User & Admin)
├── Helpers/
│   └── QRCodeHelper.php             # Helper tạo mã QR
│
views/
├── admin/
│   ├── banks.php                    # Trang quản lý ngân hàng
│   └── components/
│       └── banks-content.php        # Content component
│
├── client/
│   └── deposit.php                  # Trang nạp tiền
│
database/
└── add-qr-code-to-banks.sql         # Migration SQL

public/
└── uploads/
    └── qr/                          # Thư mục lưu mã QR
```

---

## 🔧 QR Code Helper Functions

**File**: `app/Helpers/QRCodeHelper.php`

### Các Hàm Chính

```php
// Tạo mã QR từ text
generateQRCodeImage($text, $filename)

// Tạo format VietQR
generateVietQRFormat($bankCode, $accountNumber, $accountHolder, $amount)

// Tạo mã QR Base64 cho ngân hàng
createBankQRCode($bank, $amount)

// Chuyển đổi thành Base64
generateQRBase64($text)

// Tải xuống mã QR
downloadQRCode($base64Data, $filename)
```

**Ví dụ sử dụng:**

```php
require_once 'Helpers/QRCodeHelper.php';

// Tạo mã QR cho tài khoản ngân hàng
$qrBase64 = createBankQRCode([
    'bank_code' => 'VCB',
    'account_number' => '1234567890',
    'account_holder' => 'NGUYEN VAN A',
], 50000); // amount tuỳ chọn

// Lưu vào database hoặc hiển thị
echo '<img src="' . $qrBase64 . '">';
```

---

## 🗄️ Database Schema

### bank_accounts (Thêm cột mới)

```sql
ALTER TABLE `bank_accounts` ADD COLUMN (
  `qr_code_data` LONGTEXT DEFAULT NULL COMMENT 'QR code as base64',
  `qr_code_url` VARCHAR(500) DEFAULT NULL COMMENT 'QR code URL',
  `qr_generated_at` DATETIME DEFAULT NULL COMMENT 'QR creation time'
);
```

### deposit_transactions

```
- id: ID giao dịch
- transaction_id: Mã giao dịch duy nhất
- user_id: ID người dùng
- bank_account_id: ID tài khoản ngân hàng
- amount: Số tiền nạp
- status: pending / success / failed
- notes: Ghi chú
- created_at: Thời gian tạo
- updated_at: Thời gian cập nhật
```

---

## 🔐 Security Notes

1. **Xác thực**: Chỉ admin mới có thể quản lý ngân hàng
2. **Validation**: Kiểm tra số tiền tối thiểu/tối đa
3. **Transaction**: Sử dụng database transaction cho approve_deposit
4. **Logging**: Ghi log tất cả giao dịch nạp tiền
5. **QR Rate Limiting**: Tạo mã QR mới mỗi lần thay đổi thông tin

---

## 🐛 Troubleshooting

### Mã QR không hiển thị
- Kiểm tra kết nối internet (Google Charts API cần internet)
- Kiểm tra quyền write trên folder `public/uploads/qr`
- Kiểm tra database có cột `qr_code_data` không

### Tiền không được cộng vào ví
- Kiểm tra status giao dịch có phải `pending` không
- Kiểm tra admin xác nhận bằng action `approve_deposit`
- Kiểm tra user account tồn tại trong database
- Kiểm tra cột `wallet_balance` trong bảng `users`

### QR code bị lỗi
- Sử dụng Google Charts API (không cần thư viện riêng)
- Nếu API không hoạt động, có thể cài composer + endroid/qr-code

---

## 📞 Support & Updates

Để cập nhật hoặc mở rộng:
- Thêm payment gateway mới: Update `create_gateway()`
- Thêm ngân hàng mới: Update `create_bank()`
- Tùy chỉnh QR format: Sửa `generateVietQRFormat()`
- Thêm xác thực OTP: Sửa `approve_deposit()`

---

**Tạo lúc**: 01/02/2026
**Version**: 1.0
**Status**: ✅ Ready for Production
