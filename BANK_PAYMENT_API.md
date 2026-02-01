# 🔌 Bank Payment System - API Reference

## Base URLs

```
Admin API:    /app/Controllers/AdminBankController.php
Deposit API:  /app/Controllers/DepositController.php
```

---

## 🏦 AdminBankController API

### 1. Get All Payment Gateways

```
GET /app/Controllers/AdminBankController.php?action=get_gateways
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "SeaPay",
      "code": "seapay",
      "api_key": "sk_...",
      "api_secret": "secret_...",
      "webhook_url": "https://example.com/webhook",
      "is_active": 1,
      "sort_order": 0,
      "created_at": "2026-02-01 10:00:00"
    }
  ]
}
```

---

### 2. Create Payment Gateway

```
POST /app/Controllers/AdminBankController.php?action=create_gateway
```

**Parameters:**
```
name (required):        Gateway name
code (required):        Gateway code (seapay, paypal, etc.)
api_key (optional):     API Key
api_secret (optional):  API Secret
webhook_url (optional): Webhook URL
sort_order (optional):  Sort priority (default: 0)
is_active (optional):   Active status (checkbox)
```

**Example:**
```bash
curl -X POST \
  "http://localhost/app/Controllers/AdminBankController.php?action=create_gateway" \
  -d "name=SeaPay" \
  -d "code=seapay" \
  -d "api_key=sk_live_123456" \
  -d "is_active=1"
```

**Response:**
```json
{
  "success": true,
  "message": "Thêm payment gateway thành công"
}
```

---

### 3. Update Payment Gateway

```
POST /app/Controllers/AdminBankController.php?action=update_gateway
```

**Parameters:** (same as create + id)
```
id (required):  Gateway ID
name:          New name
code:          New code
...
```

---

### 4. Delete Payment Gateway

```
POST /app/Controllers/AdminBankController.php?action=delete_gateway
```

**Parameters:**
```
id (required):  Gateway ID
```

---

### 5. Get Bank Accounts

```
GET /app/Controllers/AdminBankController.php?action=get_banks&gateway_id=1
```

**Parameters:**
```
gateway_id (optional):  Filter by gateway ID
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "gateway_id": 1,
      "bank_name": "Vietcombank",
      "bank_code": "VCB",
      "account_number": "1234567890",
      "account_holder": "NGUYEN VAN A",
      "icon_url": "https://...",
      "description": "Tài khoản chính",
      "total_received": 1500000,
      "max_amount_threshold": 9900000,
      "is_active": 1,
      "sort_order": 0,
      "qr_code_data": "data:image/png;base64,...",
      "qr_code_url": "/uploads/qr/bank_1.png",
      "qr_generated_at": "2026-02-01 10:00:00",
      "gateway_name": "SeaPay"
    }
  ]
}
```

---

### 6. Create Bank Account

```
POST /app/Controllers/AdminBankController.php?action=create_bank
```

**Parameters:**
```
gateway_id (required):          Gateway ID
bank_name (required):           Bank name
bank_code (required):           Bank code
account_number (required):      Account number
account_holder (required):      Account holder name
icon_url (optional):            Icon URL
description (optional):         Description
max_amount_threshold (optional): Max amount before switching bank
sort_order (optional):          Sort priority
is_active (optional):           Active status
```

**Example:**
```bash
curl -X POST \
  "http://localhost/app/Controllers/AdminBankController.php?action=create_bank" \
  -d "gateway_id=1" \
  -d "bank_name=Vietcombank" \
  -d "bank_code=VCB" \
  -d "account_number=1234567890" \
  -d "account_holder=NGUYEN VAN A" \
  -d "is_active=1"
```

---

### 7. Update Bank Account

```
POST /app/Controllers/AdminBankController.php?action=update_bank
```

**Parameters:** (same as create + id)

---

### 8. Delete Bank Account

```
POST /app/Controllers/AdminBankController.php?action=delete_bank
```

**Parameters:**
```
id (required):  Bank account ID
```

---

### 9. Generate QR Code

```
POST /app/Controllers/AdminBankController.php?action=generate_qr
```

**Parameters:**
```
id (required):  Bank account ID
```

**Response:**
```json
{
  "success": true,
  "message": "Tạo mã QR thành công",
  "qr_data": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgA..."
}
```

**Notes:**
- Sử dụng Google Charts API để tạo QR code
- Kết quả được lưu dưới dạng Base64 data URL
- QR code được lưu vào database

---

### 10. Get QR Code

```
GET /app/Controllers/AdminBankController.php?action=get_qr&id=1
```

**Parameters:**
```
id (required):  Bank account ID
```

**Response:**
```json
{
  "success": true,
  "qr_data": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgA..."
}
```

---

### 11. Reset Bank Total

```
POST /app/Controllers/AdminBankController.php?action=reset_bank_total
```

**Parameters:**
```
id (required):  Bank account ID
```

**Response:**
```json
{
  "success": true,
  "message": "Reset số tiền đã nhận thành công"
}
```

---

## 💳 DepositController API

### 1. Get Active Banks (Public)

```
GET /app/Controllers/DepositController.php?action=get_active_banks
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "bank_name": "Vietcombank",
      "bank_code": "VCB",
      "account_number": "1234567890",
      "account_holder": "NGUYEN VAN A",
      "icon_url": "https://...",
      "qr_code_data": "data:image/png;base64,...",
      "description": "Tài khoản chính"
    }
  ]
}
```

**Notes:**
- Không cần xác thực
- Chỉ trả về ngân hàng hoạt động
- Bao gồm mã QR đã tạo

---

### 2. Create Deposit (User)

```
POST /app/Controllers/DepositController.php?action=create_deposit
```

**Parameters:**
```
bank_id (required):  Bank account ID
amount (required):   Deposit amount (VND)
```

**Requirements:**
- User phải đăng nhập
- Amount: min 10,000, max 100,000,000

**Response:**
```json
{
  "success": true,
  "message": "Yêu cầu nạp tiền đã được tạo",
  "transaction_id": "DEP-20260201103000-A1B2C3D4",
  "amount": 50000
}
```

---

### 3. Get User Deposit History

```
GET /app/Controllers/DepositController.php?action=get_user_deposits
```

**Requirements:**
- User phải đăng nhập

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "transaction_id": "DEP-20260201103000-A1B2C3D4",
      "amount": 50000,
      "status": "pending",
      "created_at": "2026-02-01 10:30:00",
      "bank_name": "Vietcombank",
      "bank_code": "VCB"
    }
  ]
}
```

**Status Values:**
- `pending` - Chờ xác nhận
- `success` - Đã thành công
- `failed` - Bị từ chối

---

### 4. Approve Deposit (Admin)

```
POST /app/Controllers/DepositController.php?action=approve_deposit
```

**Parameters:**
```
transaction_id (required):  Transaction ID
```

**Requirements:**
- User phải là admin
- Transaction phải ở trạng thái `pending`

**Response:**
```json
{
  "success": true,
  "message": "Xác nhận nạp tiền thành công",
  "user_id": 5,
  "amount": 50000
}
```

**Auto Actions:**
- Cập nhật transaction status → `success`
- Cộng tiền vào `users.wallet_balance`
- Cập nhật `bank_accounts.total_received`
- Ghi log trong `transaction_logs`

---

### 5. Reject Deposit (Admin)

```
POST /app/Controllers/DepositController.php?action=reject_deposit
```

**Parameters:**
```
transaction_id (required):  Transaction ID
reason (optional):          Rejection reason
```

**Requirements:**
- User phải là admin
- Transaction phải ở trạng thái `pending`

**Response:**
```json
{
  "success": true,
  "message": "Từ chối nạp tiền thành công"
}
```

---

## 🔐 Authentication & Authorization

### Admin Actions
- Require: `$_SESSION['logged_in'] === true` AND `$_SESSION['role'] === 'admin'`
- Error Response: `403 Forbidden`

```json
{
  "success": false,
  "message": "Không có quyền truy cập"
}
```

### User Actions
- Require: `$_SESSION['logged_in'] === true`
- Lấy `user_id` từ `$_SESSION['user_id']`

---

## 📊 Error Handling

### Common Error Response

```json
{
  "success": false,
  "message": "Error description"
}
```

### HTTP Status Codes
- `200` - Success
- `400` - Bad Request / Validation Error
- `403` - Forbidden (Unauthorized)
- `500` - Server Error

---

## 🧪 Testing Examples

### JavaScript Fetch

```javascript
// Get banks
fetch('/app/Controllers/DepositController.php?action=get_active_banks')
  .then(r => r.json())
  .then(data => console.log(data));

// Create deposit
const formData = new FormData();
formData.append('bank_id', 1);
formData.append('amount', 50000);

fetch('/app/Controllers/DepositController.php?action=create_deposit', {
  method: 'POST',
  body: formData
})
  .then(r => r.json())
  .then(data => console.log(data));
```

### cURL

```bash
# Get banks
curl "http://localhost/app/Controllers/DepositController.php?action=get_active_banks"

# Generate QR
curl -X POST \
  "http://localhost/app/Controllers/AdminBankController.php?action=generate_qr" \
  -d "id=1"

# Create deposit
curl -X POST \
  "http://localhost/app/Controllers/DepositController.php?action=create_deposit" \
  -d "bank_id=1" \
  -d "amount=50000" \
  -b "PHPSESSID=your_session_id"
```

---

## 📝 Notes

- Tất cả request đều trả về JSON
- Sử dụng POST cho thay đổi dữ liệu
- Sử dụng GET cho lấy dữ liệu
- Session cookie được gửi tự động
- QR code sử dụng Google Charts API (cần internet)

---

**Last Updated**: 01/02/2026
**Version**: 1.0
