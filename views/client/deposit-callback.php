<?php
/**
 * DEPOSIT CALLBACK PAGE
 * User returns here after completing payment on SeaPay
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

$transactionId = (int)($_GET['transaction_id'] ?? 0);

if ($transactionId <= 0) {
    header('Location: deposit.php');
    exit();
}

// Get transaction details
$db->query("SELECT * FROM deposit_transactions WHERE id = :id AND user_id = :user_id");
$db->bind(':id', $transactionId);
$db->bind(':user_id', $userId);
$transaction = $db->fetch();

if (!$transaction) {
    header('Location: deposit.php');
    exit();
}

$status = $transaction['status'];
$amount = $transaction['amount'];
$finalAmount = $transaction['final_amount'];
$transactionCode = $transaction['transaction_code'];

// Determine page content based on status
$isSuccess = $status === 'success';
$isPending = $status === 'pending';
$isFailed = $status === 'failed';

// Get updated wallet balance
$db->query("SELECT balance FROM wallets WHERE user_id = :user_id");
$db->bind(':user_id', $userId);
$wallet = $db->fetch();
$walletBalance = (float)($wallet['balance'] ?? 0);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isSuccess ? 'Nạp tiền thành công' : 'Kết quả nạp tiền'; ?> - GAMES STORE</title>
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <style>
        body {
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .result-container {
            background: white;
            border-radius: 12px;
            padding: 50px 40px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        .result-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: fadeInDown 0.6s ease-out;
        }

        .result-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .result-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .result-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 600;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
            text-align: right;
        }

        .wallet-balance {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .balance-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .balance-amount {
            font-size: 32px;
            font-weight: bold;
        }

        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
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

        .status-success .result-icon {
            color: #4caf50;
        }

        .status-success .result-title {
            color: #4caf50;
        }

        .status-pending .result-icon {
            color: #ff9800;
        }

        .status-pending .result-title {
            color: #ff9800;
        }

        .status-failed .result-icon {
            color: #f44336;
        }

        .status-failed .result-title {
            color: #f44336;
        }

        .alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .result-container {
                padding: 30px 20px;
            }

            .result-title {
                font-size: 22px;
            }

            .result-icon {
                font-size: 60px;
            }

            .button-group {
                grid-template-columns: 1fr;
            }

            .btn-secondary {
                grid-column: auto;
            }
        }
    </style>
</head>
<body>
    <div class="result-container status-<?php echo $status; ?>">
        <div class="result-icon">
            <?php if ($isSuccess): ?>
                ✅
            <?php elseif ($isPending): ?>
                ⏳
            <?php else: ?>
                ❌
            <?php endif; ?>
        </div>

        <h1 class="result-title">
            <?php if ($isSuccess): ?>
                Nạp tiền thành công!
            <?php elseif ($isPending): ?>
                Đang xử lý giao dịch
            <?php else: ?>
                Nạp tiền thất bại
            <?php endif; ?>
        </h1>

        <p class="result-message">
            <?php if ($isSuccess): ?>
                Tiền đã được thêm vào tài khoản của bạn. Bạn có thể sử dụng ngay để mua game.
            <?php elseif ($isPending): ?>
                Giao dịch của bạn đang được xử lý. Vui lòng chờ trong vài phút để kiểm tra kết quả.
            <?php else: ?>
                Giao dịch không thành công. Vui lòng kiểm tra thông tin và thử lại.
            <?php endif; ?>
        </p>

        <div class="result-details">
            <div class="detail-row">
                <span class="detail-label">Mã giao dịch:</span>
                <span class="detail-value" style="font-family: monospace; font-size: 12px;"><?php echo $transactionCode; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Số tiền:</span>
                <span class="detail-value"><?php echo number_format($amount, 0, ',', '.'); ?> đ</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phí:</span>
                <span class="detail-value"><?php echo number_format($transaction['fee'], 0, ',', '.'); ?> đ</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tổng cộng:</span>
                <span class="detail-value"><?php echo number_format($finalAmount, 0, ',', '.'); ?> đ</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Trạng thái:</span>
                <span class="detail-value">
                    <?php 
                    $statusMap = [
                        'success' => '✓ Thành công',
                        'pending' => '⏳ Chờ xử lý',
                        'failed' => '✗ Thất bại',
                        'cancelled' => '⊘ Hủy'
                    ];
                    echo $statusMap[$status] ?? ucfirst($status);
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Thời gian:</span>
                <span class="detail-value"><?php echo date('d/m/Y H:i:s', strtotime($transaction['created_at'])); ?></span>
            </div>
        </div>

        <?php if ($isSuccess): ?>
            <div class="wallet-balance">
                <div class="balance-label">Số dư tài khoản hiện tại</div>
                <div class="balance-amount"><?php echo number_format($walletBalance, 0, ',', '.'); ?> đ</div>
            </div>
        <?php endif; ?>

        <?php if ($isPending): ?>
            <div class="alert show">
                <strong>Thông báo:</strong> Hệ thống sẽ tự động cập nhật khi SeaPay xác nhận thanh toán. Vui lòng không đóng trình duyệt.
            </div>
        <?php endif; ?>

        <div class="button-group">
            <a href="<?php echo BASE_URL; ?>/views/client/deposit.php" class="btn btn-secondary">← Quay lại nạp tiền</a>
            <a href="<?php echo BASE_URL; ?>/views/client/profile.php" class="btn btn-primary">Xem tài khoản →</a>
        </div>
    </div>
</body>
</html>
