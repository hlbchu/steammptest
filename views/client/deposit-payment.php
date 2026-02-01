<?php
/**
 * DEPOSIT PAYMENT PROCESSING PAGE
 * Handles payment initiation and verification with SeaPay
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = new Database();
$userId = $_SESSION['user_id'];

// Get parameters
$bankId = (int)($_GET['bank_id'] ?? 0);
$amount = (int)($_GET['amount'] ?? 0);
$note = trim($_GET['note'] ?? '');

// Validation
if ($bankId <= 0 || $amount < 10000) {
    header('Location: deposit.php?error=invalid_params');
    exit();
}

// Get bank info
$db->query("SELECT ba.*, pg.code as gateway_code, pg.api_key, pg.api_secret, pg.webhook_url 
           FROM bank_accounts ba 
           LEFT JOIN payment_gateways pg ON ba.gateway_id = pg.id 
           WHERE ba.id = :id AND ba.is_active = 1");
$db->bind(':id', $bankId);
$bank = $db->fetch();

if (!$bank || $bank['gateway_code'] !== 'seapay') {
    header('Location: deposit.php?error=invalid_bank');
    exit();
}

// Create deposit transaction record
$transactionCode = 'DEP' . date('YmdHis') . rand(1000, 9999);
$fee = round($amount * 0.015); // 1.5% fee
$finalAmount = $amount + $fee;

$db->query("INSERT INTO deposit_transactions 
           (user_id, bank_account_id, gateway_id, amount, fee, final_amount, status, transaction_code, notes)
           VALUES (:user_id, :bank_id, :gateway_id, :amount, :fee, :final_amount, 'pending', :code, :note)");
$db->bind(':user_id', $userId);
$db->bind(':bank_id', $bankId);
$db->bind(':gateway_id', $bank['gateway_id']);
$db->bind(':amount', $amount);
$db->bind(':fee', $fee);
$db->bind(':final_amount', $finalAmount);
$db->bind(':code', $transactionCode);
$db->bind(':note', $note ?: null);
$db->execute();

$transactionId = $db->lastInsertId();

// Get user info
$db->query("SELECT email, username FROM users WHERE id = :id");
$db->bind(':id', $userId);
$user = $db->fetch();

/**
 * SEAPAY INTEGRATION
 * Document: https://seapay.vn/api-documentation
 */

// SeaPay API Configuration
$seapayConfig = [
    'api_key' => $bank['api_key'] ?? '',
    'api_secret' => $bank['api_secret'] ?? '',
    'webhook_url' => $bank['webhook_url'] ?? (BASE_URL . '/app/Controllers/DepositWebhookController.php'),
    'merchant_id' => $bank['bank_code'] ?? 'GAMES_STORE',
];

// SeaPay request parameters
$seapayRequest = [
    'merchant_id' => $seapayConfig['merchant_id'],
    'transaction_code' => $transactionCode,
    'amount' => $finalAmount,
    'currency' => 'VND',
    'bank_code' => $bank['bank_code'],
    'account_number' => $bank['account_number'],
    'description' => 'Nạp tiền vào tài khoản',
    'customer_name' => $user['username'],
    'customer_email' => $user['email'],
    'return_url' => BASE_URL . '/views/client/deposit-callback.php?transaction_id=' . $transactionId,
    'webhook_url' => $seapayConfig['webhook_url'],
    'timestamp' => time(),
];

// Generate signature for SeaPay
$signature = generateSeapaySignature($seapayRequest, $seapayConfig['api_secret']);

function generateSeapaySignature($data, $apiSecret) {
    ksort($data);
    $signString = '';
    foreach ($data as $key => $value) {
        if ($value !== null && $value !== '') {
            $signString .= $key . '=' . $value . '&';
        }
    }
    $signString = rtrim($signString, '&');
    return hash_hmac('sha256', $signString, $apiSecret);
}

$seapayRequest['signature'] = $signature;

// Prepare payment redirect
$paymentUrl = 'https://api.seapay.vn/payment/redirect';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận thanh toán - GAMES STORE</title>
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .payment-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 90%;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .payment-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-weight: 600;
        }

        .info-value {
            color: #333;
            font-weight: 600;
        }

        .amount-highlight {
            font-size: 20px;
            color: #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
            user-select: none;
        }

        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn {
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            grid-column: 1 / -1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .terms {
            font-size: 12px;
            color: #999;
            text-align: center;
            margin-top: 15px;
        }

        .terms a {
            color: #667eea;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h1>🔐 Xác Nhận Thanh Toán</h1>

        <div class="payment-info">
            <div class="info-row">
                <span class="info-label">Ngân hàng:</span>
                <span class="info-value"><?php echo htmlspecialchars($bank['bank_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Số tiền:</span>
                <span class="info-value amount-highlight"><?php echo number_format($amount, 0, ',', '.'); ?> đ</span>
            </div>
            <div class="info-row">
                <span class="info-label">Phí giao dịch:</span>
                <span class="info-value"><?php echo number_format($fee, 0, ',', '.'); ?> đ (1.5%)</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tổng cộng:</span>
                <span class="info-value amount-highlight"><?php echo number_format($finalAmount, 0, ',', '.'); ?> đ</span>
            </div>
            <div class="info-row">
                <span class="info-label">Mã giao dịch:</span>
                <span class="info-value" style="font-family: monospace; font-size: 12px;"><?php echo $transactionCode; ?></span>
            </div>
        </div>

        <form id="payment-form" method="POST" action="<?php echo htmlspecialchars($paymentUrl); ?>">
            <?php foreach ($seapayRequest as $key => $value): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
            <?php endforeach; ?>

            <div class="checkbox-group">
                <input type="checkbox" id="agree-terms" required>
                <label for="agree-terms">Tôi đồng ý với <a href="#" target="_blank">điều khoản dịch vụ</a> và <a href="#" target="_blank">chính sách bảo mật</a></label>
            </div>

            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="goBack()">← Quay lại</button>
                <button type="submit" class="btn btn-primary">Tiếp tục thanh toán →</button>
            </div>

            <div class="loading" id="loading-indicator">
                <div class="spinner"></div>
                <p>Đang chuyển hướng đến SeaPay...</p>
            </div>
        </form>

        <div class="terms">
            <p>🛡️ Thanh toán an toàn qua SeaPay | Giao dịch được mã hóa SSL</p>
        </div>
    </div>

    <script>
    function goBack() {
        window.location.href = '<?php echo BASE_URL; ?>/views/client/deposit.php';
    }

    document.getElementById('payment-form').addEventListener('submit', function() {
        document.getElementById('loading-indicator').classList.add('show');
    });
    </script>
</body>
</html>
