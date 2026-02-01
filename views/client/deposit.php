<?php
/**
 * USER DEPOSIT PAGE - Nạp tiền vào tài khoản
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

// Get user info
$db->query("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $userId);
$user = $db->fetch();

// Get available banks using stored procedure (auto-select based on total_received)
try {
    $db->query("CALL sp_get_available_bank()");
    $availableBank = $db->fetch();
    
    // If no bank available from procedure, get all active banks
    if (!$availableBank) {
        $db->query("SELECT ba.*, pg.name as gateway_name, pg.code as gateway_code 
                   FROM bank_accounts ba 
                   LEFT JOIN payment_gateways pg ON ba.gateway_id = pg.id 
                   WHERE ba.is_active = 1 AND pg.is_active = 1 
                   ORDER BY ba.sort_order ASC");
        $banks = $db->fetchAll();
    } else {
        // Use the recommended bank from stored procedure
        $db->query("SELECT ba.*, pg.name as gateway_name, pg.code as gateway_code 
                   FROM bank_accounts ba 
                   LEFT JOIN payment_gateways pg ON ba.gateway_id = pg.id 
                   WHERE ba.id = :bank_id");
        $db->bind(':bank_id', $availableBank['id']);
        $recommendedBank = $db->fetch();
        
        // Also get other active banks for user choice
        $db->query("SELECT ba.*, pg.name as gateway_name, pg.code as gateway_code 
                   FROM bank_accounts ba 
                   LEFT JOIN payment_gateways pg ON ba.gateway_id = pg.id 
                   WHERE ba.is_active = 1 AND pg.is_active = 1 
                   ORDER BY ba.sort_order ASC");
        $banks = $db->fetchAll();
    }
} catch (Exception $e) {
    // Fallback to all active banks if procedure fails
    $db->query("SELECT ba.*, pg.name as gateway_name, pg.code as gateway_code 
               FROM bank_accounts ba 
               LEFT JOIN payment_gateways pg ON ba.gateway_id = pg.id 
               WHERE ba.is_active = 1 AND pg.is_active = 1 
               ORDER BY ba.sort_order ASC");
    $banks = $db->fetchAll();
}

// Only show BIDV bank for deposit method
$banks = array_values(array_filter($banks, function($b) {
    return strtoupper(trim($b['bank_code'] ?? '')) === 'BIDV';
}));
if (isset($recommendedBank) && strtoupper(trim($recommendedBank['bank_code'] ?? '')) !== 'BIDV') {
    $recommendedBank = null;
}

// Get user's wallet balance
$db->query("SELECT balance FROM wallets WHERE user_id = :user_id");
$db->bind(':user_id', $userId);
$wallet = $db->fetch();
$balance = (float)($wallet['balance'] ?? 0);

// Get user's completed deposit history only
$db->query("SELECT dt.amount, dt.status, dt.created_at, ba.bank_name
           FROM deposit_transactions dt
           LEFT JOIN bank_accounts ba ON dt.bank_account_id = ba.id
           WHERE dt.user_id = :user_id AND dt.status = 'success'
           ORDER BY dt.created_at DESC
           LIMIT 10");
$db->bind(':user_id', $userId);
$depositHistory = $db->fetchAll();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nạp Tiền - GAMES STORE</title>
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/header.css">
    <link rel="stylesheet" href="../../public/assets/css/footer.css">
    <style>
        /* Hide wallet balance in header on deposit page only */
        header .wallet-info {
            display: none !important;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .deposit-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .deposit-form-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateZ(0);
        }

        .deposit-history-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateZ(0);
        }


        h2 {
            margin-top: 0;
            margin-bottom: 25px;
            color: #333;
            font-size: 24px;
        }

        .wallet-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }

        .wallet-info p {
            margin: 5px 0;
            opacity: 0.9;
        }

        .wallet-balance {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .bank-list {
            margin-bottom: 20px;
        }

        .method-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .method-item {
            padding: 14px 16px;
            border: 2px solid #eee;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
            will-change: transform;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .method-item:hover {
            border-color: #667eea;
            background: #f9f8ff;
            transform: translateY(-1px);
        }

        .method-item.selected {
            border-color: #667eea;
            background: #f9f8ff;
        }

        .method-item.disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .method-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }

        .method-desc {
            font-size: 13px;
            color: #777;
        }

        .method-icon {
            width: 32px;
            height: 32px;
            flex: 0 0 32px;
        }

        .method-content {
            display: flex;
            flex-direction: column;
        }

        .method-sub {
            margin-top: 16px;
            padding: 12px;
            border: 1px dashed #e5e5e5;
            border-radius: 6px;
            background: #fafafa;
        }

        .method-sub-title {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 10px;
        }

        .amount-presets {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .preset-btn {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            will-change: transform;
        }

        .preset-btn:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-1px);
        }

        .hidden {
            display: none !important;
        }

        .bank-item {
            padding: 15px;
            border: 2px solid #eee;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 10px;
            will-change: transform;
        }

        .bank-item:hover {
            border-color: #667eea;
            background: #f9f8ff;
            transform: translateY(-1px);
        }

        .bank-item.selected {
            border-color: #667eea;
            background: #f9f8ff;
        }

        .bank-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .bank-details {
            font-size: 14px;
            color: #666;
            margin-top: 8px;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            content-visibility: auto;
            contain-intrinsic-size: 400px;
        }

        .history-table thead {
            background: #f5f5f5;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .history-table th {
            font-weight: 600;
            color: #333;
        }

        .status-success {
            color: #4caf50;
            font-weight: 600;
        }


        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            display: block;
        }

        .alert.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            display: block;
        }

        .qr-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
        }

        .qr-modal.show {
            display: flex;
        }

        .qr-modal-content {
            background: #fff;
            border-radius: 10px;
            padding: 24px;
            width: 90%;
            max-width: 520px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            position: relative;
        }

        .qr-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .qr-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .qr-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin: 16px 0;
        }

        .qr-box img {
            max-width: 240px;
            width: 100%;
            height: auto;
        }

        .qr-info {
            font-size: 14px;
            color: #333;
            background: #f7f7f9;
            border: 1px solid #eee;
            padding: 10px 12px;
            border-radius: 6px;
            width: 100%;
        }

        .qr-info strong {
            color: #222;
        }

        .qr-actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .success-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3001;
        }

        .success-modal.show {
            display: flex;
        }

        .success-modal-content {
            background: #fff;
            border-radius: 12px;
            padding: 28px 24px;
            width: 90%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 12px 32px rgba(0,0,0,0.25);
        }

        .success-title {
            font-size: 20px;
            font-weight: 700;
            color: #2dce89;
            margin-top: 10px;
        }

        .success-desc {
            font-size: 14px;
            color: #666;
            margin-top: 6px;
        }

        .success-check {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 4px solid #2dce89;
            margin: 0 auto;
            position: relative;
            animation: popIn 0.35s ease-out;
        }

        .success-check::after {
            content: '';
            position: absolute;
            width: 26px;
            height: 48px;
            border-right: 6px solid #2dce89;
            border-bottom: 6px solid #2dce89;
            transform: rotate(45deg);
            left: 30px;
            top: 16px;
            animation: drawTick 0.4s ease-out 0.1s forwards;
            opacity: 0;
        }

        @keyframes popIn {
            0% { transform: scale(0.6); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes drawTick {
            0% { opacity: 0; transform: rotate(45deg) scale(0.4); }
            100% { opacity: 1; transform: rotate(45deg) scale(1); }
        }

        .btn-copy {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: #fff;
            cursor: pointer;
        }

        .bank-recommended-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
            vertical-align: middle;
        }

        .bank-threshold-info {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .deposit-wrapper {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
                animation: none !important;
            }
        }
    </style>
</head>
<body>
    <?php include '../partials/header-layout.php'; ?>

    <div class="profile-container">
        <h1>💰 Nạp Tiền Vào Tài Khoản</h1>

        <div class="wallet-info">
            <p>Số dư hiện tại</p>
            <div class="wallet-balance"><?php echo number_format($balance, 0, ',', '.'); ?> đ</div>
            <p>Thêm tiền để mua game</p>
        </div>

        <div id="alert-message" class="alert"></div>

        <div class="deposit-wrapper">
            <div class="deposit-form-section">
                <h2>Chọn phương thức nạp tiền</h2>
                
                <form id="deposit-form">
                    <div class="form-group">
                        <label>Phương thức nạp tiền</label>
                        <div class="method-list" id="method-list">
                            <div class="method-item selected" data-method="bank_transfer">
                                <img class="method-icon" src="<?php echo BASE_URL; ?>/public/assets/svg/payment/bank-transfer.svg" alt="Bank">
                                <div class="method-content">
                                    <div class="method-title">Chuyển khoản ngân hàng</div>
                                    <div class="method-desc">Quét QR / chuyển khoản thủ công</div>
                                </div>
                            </div>
                            <div class="method-item disabled" data-method="balance">
                                <img class="method-icon" src="<?php echo BASE_URL; ?>/public/assets/svg/payment/balance.svg" alt="Balance">
                                <div class="method-content">
                                    <div class="method-title">Balance</div>
                                    <div class="method-desc">Bảo trì</div>
                                </div>
                            </div>
                            <div class="method-item disabled" data-method="card_topup">
                                <img class="method-icon" src="<?php echo BASE_URL; ?>/public/assets/svg/payment/card-topup.svg" alt="Card">
                                <div class="method-content">
                                    <div class="method-title">Nạp thẻ</div>
                                    <div class="method-desc">Bảo trì</div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="selected-method" value="bank_transfer">
                    </div>

                    <div class="method-sub" id="method-sub-bank">
                        <div class="method-sub-title">Ngân hàng hỗ trợ</div>
                        <div class="bank-list" id="bank-list">
                        <?php if (!empty($banks)): ?>
                            <?php foreach ($banks as $index => $bank): ?>
                                <?php 
                                $isRecommended = isset($recommendedBank) && $recommendedBank['id'] == $bank['id'];
                                $isSelected = $isRecommended || $index === 0;
                                ?>
                                <div class="bank-item <?php echo $isSelected ? 'selected' : ''; ?>" data-bank-id="<?php echo $bank['id']; ?>">
                                    <div class="bank-name">
                                        <?php if ($bank['icon_url']): ?>
                                            <img src="<?php echo htmlspecialchars($bank['icon_url']); ?>" loading="lazy" decoding="async" width="24" height="24" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 8px;">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($bank['bank_name']); ?>
                                        <?php if ($isRecommended): ?>
                                            <span class="bank-recommended-badge">✓ Được đề xuất</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color: #999; font-size: 13px;">BIDV</div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>Hiện chưa có phương thức nạp tiền</p>
                            </div>
                        <?php endif; ?>
                        </div>
                    </div>

                    <input type="hidden" id="selected-bank-id" value="<?php echo isset($recommendedBank) ? $recommendedBank['id'] : (isset($banks[0]) ? $banks[0]['id'] : ''); ?>">

                    <div class="form-group">
                        <label>Số tiền nạp (VNĐ)</label>
                        <input type="number" id="amount" name="amount" min="10000" step="1000" placeholder="Nhập số tiền (tối thiểu 10.000 đ)" required>
                        <div class="amount-presets">
                            <button type="button" class="preset-btn" onclick="setAmount(100000)">100.000 đ</button>
                            <button type="button" class="preset-btn" onclick="setAmount(200000)">200.000 đ</button>
                            <button type="button" class="preset-btn" onclick="setAmount(500000)">500.000 đ</button>
                            <button type="button" class="preset-btn" onclick="setAmount(1000000)">1.000.000 đ</button>
                            <button type="button" class="preset-btn" onclick="setAmount(2000000)">2.000.000 đ</button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Tiếp tục thanh toán</button>
                </form>
            </div>

            <div class="deposit-history-section">
                <h2>Lịch sử nạp tiền</h2>

                <?php if (!empty($depositHistory)): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngân hàng</th>
                                <th>Ngày</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($depositHistory as $transaction): ?>
                                <tr>
                                    <td style="font-weight: 600;">
                                        <?php echo number_format($transaction['amount'], 0, ',', '.'); ?> đ
                                    </td>
                                    <td>
                                        <span class="status-success">✓ Thành công</span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['bank_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Chưa có lịch sử giao dịch nào</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <?php include '../partials/footer-layout.php'; ?>

    <div class="qr-modal" id="qr-modal">
        <div class="qr-modal-content">
            <div class="qr-modal-header">
                <h3>Mã QR thanh toán</h3>
                <button class="qr-close" onclick="closeQrModal()">&times;</button>
            </div>
            <div class="qr-box">
                <img id="qr-image" src="" alt="QR Code">
                <div id="qr-empty" class="empty-state" style="display:none;">Ngân hàng chưa có mã QR</div>
            </div>
            <div class="qr-info">
                <div><strong>Ngân hàng:</strong> <span id="qr-bank-name">-</span></div>
                <div><strong>Số TK:</strong> <span id="qr-account-number">-</span></div>
                <div><strong>Chủ TK:</strong> <span id="qr-account-holder">-</span></div>
                <div><strong>Nội dung:</strong> <span id="qr-transfer-content">-</span> 
                    <button type="button" class="btn-copy" onclick="copyTransferContent()">Copy</button>
                </div>
                <div><strong>Số tiền:</strong> <span id="qr-amount">-</span></div>
            </div>
            <div class="qr-actions">
                <button type="button" class="btn-submit" onclick="closeQrModal()">Đóng</button>
            </div>
        </div>
    </div>

    <div class="success-modal" id="success-modal">
        <div class="success-modal-content">
            <div class="success-check"></div>
            <div class="success-title">Thanh toán thành công</div>
            <div class="success-desc">Tiền đã được cộng vào tài khoản của bạn</div>
            <div class="qr-actions" style="justify-content: center;">
                <button type="button" class="btn-submit" onclick="closeSuccessModal()">Đóng</button>
            </div>
        </div>
    </div>

    <audio id="success-sound" preload="auto" src="<?php echo BASE_URL; ?>/public/assets/sounds/som_matricula-464025.mp3"></audio>

    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const banks = <?php echo json_encode($banks); ?>;
    let selectedMethod = 'bank_transfer';
    const userId = <?php echo (int)$userId; ?>;
    let depositPoller = null;
    let lastDepositCheckAt = null;

    const bankListEl = document.getElementById('bank-list');
    const methodListEl = document.getElementById('method-list');

    function selectBank(bankId, element) {
        document.querySelectorAll('.bank-item').forEach(el => {
            el.classList.remove('selected');
        });
        element.classList.add('selected');
        document.getElementById('selected-bank-id').value = bankId;
    }

    function selectMethod(method, element) {
        if (element.classList.contains('disabled')) {
            return;
        }

        selectedMethod = method;
        document.getElementById('selected-method').value = method;

        document.querySelectorAll('.method-item').forEach(el => {
            el.classList.remove('selected');
        });
        element.classList.add('selected');

        const bankSub = document.getElementById('method-sub-bank');
        if (method === 'bank_transfer') {
            bankSub.classList.remove('hidden');
        } else {
            bankSub.classList.add('hidden');
        }
    }

    if (bankListEl) {
        bankListEl.addEventListener('click', function(e) {
            const item = e.target.closest('.bank-item');
            if (!item) return;
            const bankId = item.getAttribute('data-bank-id');
            if (bankId) {
                selectBank(bankId, item);
            }
        });
    }

    if (methodListEl) {
        methodListEl.addEventListener('click', function(e) {
            const item = e.target.closest('.method-item');
            if (!item) return;
            const method = item.getAttribute('data-method');
            if (method) {
                selectMethod(method, item);
            }
        });
    }

    function setAmount(value) {
        document.getElementById('amount').value = value;
    }

    document.getElementById('deposit-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const bankId = document.getElementById('selected-bank-id').value;
        const amount = parseInt(document.getElementById('amount').value);

        if (selectedMethod !== 'bank_transfer') {
            showAlert('Phương thức này chưa hỗ trợ', 'error');
            return;
        }

        if (!bankId) {
            showAlert('Vui lòng chọn ngân hàng', 'error');
            return;
        }

        if (amount < 10000) {
            showAlert('Số tiền tối thiểu là 10.000 đ', 'error');
            return;
        }

        // Find selected bank
        const selectedBank = banks.find(b => b.id == bankId);
        if (!selectedBank) {
            showAlert('Ngân hàng không hợp lệ', 'error');
            return;
        }

        showQrModal(selectedBank, amount);
        startDepositPolling(selectedBank, amount);
    });

    function showQrModal(bank, amount) {
        const modal = document.getElementById('qr-modal');
        const qrImage = document.getElementById('qr-image');
        const qrEmpty = document.getElementById('qr-empty');

        const rawContent = bank.description && bank.description.trim()
            ? bank.description.trim()
            : 'NAPTIEN';
        const hasPlaceholder = rawContent.includes('{user_id}');
        const transferContent = hasPlaceholder
            ? rawContent.replace('{user_id}', userId)
            : `${rawContent}${userId}`;

        document.getElementById('qr-bank-name').textContent = bank.bank_name || '-';
        document.getElementById('qr-account-number').textContent = bank.account_number || '-';
        document.getElementById('qr-account-holder').textContent = bank.account_holder || '-';
        document.getElementById('qr-transfer-content').textContent = transferContent;
        document.getElementById('qr-amount').textContent = amount.toLocaleString('vi-VN') + ' đ';

        if (bank.bank_code && bank.account_number) {
            const bankCode = encodeURIComponent(bank.bank_code);
            const accountNumber = encodeURIComponent(bank.account_number);
            const addInfo = encodeURIComponent(transferContent);
            const accountName = encodeURIComponent(bank.account_holder || '');
            const qrUrl = `https://img.vietqr.io/image/${bankCode}-${accountNumber}-compact2.png?amount=${amount}&addInfo=${addInfo}&accountName=${accountName}`;
            qrImage.src = qrUrl;
            qrImage.style.display = 'block';
            qrEmpty.style.display = 'none';
        } else {
            qrImage.style.display = 'none';
            qrEmpty.style.display = 'block';
        }

        modal.classList.add('show');
    }

    function closeQrModal() {
        document.getElementById('qr-modal').classList.remove('show');
        stopDepositPolling();
    }

    function startDepositPolling(bank, amount) {
        stopDepositPolling();
        lastDepositCheckAt = Date.now();

        depositPoller = setInterval(() => {
            const params = new URLSearchParams({
                action: 'check_deposit_success',
                amount: String(amount),
                bank_id: String(bank.id),
                since: String(lastDepositCheckAt)
            });

            fetch(`${BASE_URL}/app/Controllers/DepositController.php?${params.toString()}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.success && data.is_paid) {
                        stopDepositPolling();
                        closeQrModal();
                        showSuccessModal();
                    }
                })
                .catch(() => {});
        }, 4000);
    }

    function stopDepositPolling() {
        if (depositPoller) {
            clearInterval(depositPoller);
            depositPoller = null;
        }
    }

    function showSuccessModal() {
        const modal = document.getElementById('success-modal');
        modal.classList.add('show');
        const sound = document.getElementById('success-sound');
        if (sound) {
            sound.currentTime = 0;
            sound.play().catch(() => {});
        }
    }

    function closeSuccessModal() {
        document.getElementById('success-modal').classList.remove('show');

    function copyTransferContent() {
        const content = document.getElementById('qr-transfer-content').textContent || '';
        if (!content) return;
        navigator.clipboard.writeText(content)
            .then(() => showAlert('Đã copy nội dung nạp tiền', 'success'))
            .catch(() => showAlert('Không thể copy', 'error'));
    }

    function showAlert(message, type) {
        const alertEl = document.getElementById('alert-message');
        alertEl.textContent = message;
        alertEl.className = 'alert ' + type;
        alertEl.style.display = 'block';
        setTimeout(() => {
            alertEl.style.display = 'none';
        }, 5000);
    }
    </script>
</body>
</html>
