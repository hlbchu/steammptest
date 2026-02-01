# SeaPay Webhook Configuration

## Webhook URL

**Main Webhook Handler:**
```
http://localhost/steamweb/public/webhook/seapay.php
```

For production, replace `localhost` with your domain:
```
https://yourdomain.com/steamweb/public/webhook/seapay.php
```

## Webhook Payload Format

SeaPay should POST JSON data with the following structure:

```json
{
  "transactionId": "TXN20260131123456",
  "amount": 100000,
  "status": "success",
  "timestamp": "2026-01-31 12:34:56",
  "userId": 5,
  "bankAccountId": 1,
  "reference": "ORDER-123",
  "message": "Payment successful"
}
```

### Required Fields:
- `transactionId` (string) - Unique transaction ID from SeaPay
- `amount` (number) - Amount in VND
- `status` (string) - One of: `success`, `pending`, `failed`, `completed`, `paid`, `cancelled`
- `timestamp` (string) - ISO format datetime

### Optional Fields:
- `userId` (number) - User ID for wallet updates
- `bankAccountId` (number) - Bank account ID receiving the payment
- `reference` (string) - Reference number for tracking
- `message` (string) - Additional message/note

## Status Mapping

SeaPay Status → Internal Status:
- `success`, `completed`, `paid` → `success`
- `pending`, `waiting` → `pending`
- `failed`, `error`, `cancelled` → `failed`

## Webhook Processing

When a successful webhook is received:

1. ✅ Create/Update `deposit_transactions` record
2. ✅ Update user wallet balance (if `userId` provided)
3. ✅ Update bank account `total_received` (via trigger)
4. ✅ Auto-switch bank if threshold reached:
   - Deactivate current bank (when `total_received >= max_amount_threshold`)
   - Activate next available bank with available capacity
5. ✅ Log all transactions in `logs/webhook.log`

## Logging

All webhook requests are logged to:
```
/logs/webhook.log
```

Check this file for:
- Webhook request details
- Transaction processing status
- Bank switching events
- Errors and exceptions

## Testing Webhook

You can test the webhook with curl:

```bash
curl -X POST http://localhost/steamweb/public/webhook/seapay.php \
  -H "Content-Type: application/json" \
  -d '{
    "transactionId": "TEST123",
    "amount": 50000,
    "status": "success",
    "timestamp": "2026-01-31 12:00:00",
    "userId": 1,
    "bankAccountId": 1
  }'
```

## Bank Rotation Logic

### Bank Configuration
- Bank 1 (VCB): `max_amount_threshold = 9900000` (9.9M)
- Bank 2 (TCB): `max_amount_threshold = 20000000` (20M)
- Bank 3 (MB): `max_amount_threshold = NULL` (Unlimited)

### Auto-Switch Process
1. When deposits reach bank's threshold, system:
   - Marks current bank as `is_active = FALSE`
   - Finds next bank with `is_active = FALSE` and capacity > 0
   - Marks next bank as `is_active = TRUE`
2. All incoming deposits go to the currently active bank
3. Progress bar shows usage (e.g., 95% of 9.9M = 9.405M received)

## Response Format

Webhook handler returns:

**Success (200):**
```json
{
  "success": true,
  "message": "Webhook processed successfully",
  "transactionId": "TXN20260131123456",
  "status": "success"
}
```

**Error (400/500):**
```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message"
}
```

## Admin Dashboard

View all webhook transactions in Admin Panel:
- Navigate to: `Admin > Quản Lý Thanh Toán`
- Tab 1: **Giao Dịch SeaPay** - View all transactions with search & filter
- Tab 2: **Lịch Sử Nạp Tiền** - View deposit history grouped by bank
