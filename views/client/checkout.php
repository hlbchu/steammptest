<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$banks = [];
try {
    $db = new Database();
    $db->query("SELECT ba.id, ba.bank_name, ba.bank_code, ba.account_number, ba.account_holder, ba.icon_url, ba.description, pg.name AS gateway_name
               FROM bank_accounts ba
               LEFT JOIN payment_gateways pg ON ba.gateway_id = pg.id
               WHERE ba.is_active = 1 AND pg.is_active = 1
               ORDER BY ba.sort_order ASC, ba.id ASC");
    $banks = $db->fetchAll();
} catch (Throwable $e) {
    $banks = [];
}
?>
<!-- ==============================================
     CHECKOUT PAGE
     ============================================== -->

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Steam Web</title>
    <link rel="stylesheet" href="assets/style.css">
    <!-- SVG Icons -->
    <svg style="display: none;" xmlns="http://www.w3.org/2000/svg">
        <symbol id="icon-creditcard" viewBox="0 0 24 24"><path fill="currentColor" d="M20 8H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm0 12H4V10h16v10zm-10-3h6v2h-6z"/></symbol>
        <symbol id="icon-clipboard" viewBox="0 0 24 24"><path fill="currentColor" d="M19 2h-4.18C14.4.84 13.3 0 12 0c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm7 18H5V4h14v16z"/></symbol>
    </svg>
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main class="container">
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <use xlink:href="#icon-creditcard"></use>
                    </svg>
                    Thanh toán
                </h2>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- Checkout Form -->
                <div style="background: var(--tertiary-bg); border: 1px solid var(--border-color); border-radius: 4px; padding: 30px;">
                    
                    <!-- Step 1: Customer Info -->
                    <div style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid var(--border-color);">
                        <h3 style="color: var(--accent-color); margin-bottom: 20px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" style="display: inline; margin-right: 8px; vertical-align: middle;">
                                <use xlink:href="#icon-clipboard"></use>
                            </svg>
                            Thông tin khách hàng
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: bold;">Tên</label>
                                <input type="text" class="form-control" placeholder="Nguyễn Văn A" style="width: 100%; padding: 10px; background: var(--secondary-bg); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: bold;">Email</label>
                                <input type="email" class="form-control" placeholder="email@example.com" style="width: 100%; padding: 10px; background: var(--secondary-bg); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px;">
                            </div>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: bold;">Số điện thoại</label>
                            <input type="tel" class="form-control" placeholder="0987654321" style="width: 100%; padding: 10px; background: var(--secondary-bg); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px;">
                        </div>
                    </div>

                    <!-- Step 2: Payment Method -->
                    <div style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid var(--border-color);">
                        <h3 style="color: var(--accent-color); margin-bottom: 20px;">💰 Phương thức thanh toán</h3>
                        <div style="display: grid; gap: 10px;">
                            <label style="display: flex; align-items: center; padding: 15px; background: var(--secondary-bg); border: 2px solid var(--accent-color); border-radius: 4px; cursor: pointer;">
                                <input type="radio" name="payment" value="card" checked style="width: 18px; height: 18px; margin-right: 10px;">
                                <span style="color: var(--text-primary); font-weight: bold;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" style="display: inline; margin-right: 8px; vertical-align: middle;">
                                        <use xlink:href="#icon-creditcard"></use>
                                    </svg>
                                    Thẻ tín dụng / Thẻ ghi nợ
                                </span>
                            </label>
                            <label style="display: flex; align-items: center; padding: 15px; background: var(--secondary-bg); border: 1px solid var(--border-color); border-radius: 4px; cursor: pointer;">
                                <input type="radio" name="payment" value="bank" style="width: 18px; height: 18px; margin-right: 10px;">
                                <span style="color: var(--text-primary);">🏦 Chuyển khoản ngân hàng</span>
                            </label>
                            <label style="display: flex; align-items: center; padding: 15px; background: var(--secondary-bg); border: 1px solid var(--border-color); border-radius: 4px; cursor: pointer;">
                                <input type="radio" name="payment" value="ewallet" style="width: 18px; height: 18px; margin-right: 10px;">
                                <span style="color: var(--text-primary);">📱 Ví điện tử (Momo, ZaloPay)</span>
                            </label>
                        </div>

                        <div id="bank-transfer-panel" style="display: none; margin-top: 16px; padding: 16px; background: var(--secondary-bg); border: 1px solid var(--border-color); border-radius: 6px;">
                            <div style="font-weight: bold; color: var(--text-primary); margin-bottom: 12px;">Chọn ngân hàng để thanh toán</div>
                            <?php if (!empty($banks)): ?>
                                <div style="display: grid; gap: 10px;">
                                    <?php foreach ($banks as $index => $bank): ?>
                                        <div class="bank-option" data-bank-id="<?php echo (int)$bank['id']; ?>" data-bank-content="<?php echo htmlspecialchars($bank['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer; background: var(--tertiary-bg);">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <?php if (!empty($bank['icon_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($bank['icon_url']); ?>" alt="<?php echo htmlspecialchars($bank['bank_name']); ?>" style="width: 24px; height: 24px;">
                                                <?php endif; ?>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-primary);">
                                                        <?php echo htmlspecialchars($bank['bank_name']); ?>
                                                    </div>
                                                    <div style="font-size: 12px; color: var(--text-secondary);">
                                                        <?php echo htmlspecialchars($bank['gateway_name'] ?? ''); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (!empty($bank['account_number'])): ?>
                                                <div style="margin-top: 8px; font-size: 13px; color: var(--text-secondary);">
                                                    Số TK: <?php echo htmlspecialchars($bank['account_number']); ?> - CTK: <?php echo htmlspecialchars($bank['account_holder'] ?? ''); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div style="color: var(--text-secondary);">Chưa có ngân hàng khả dụng.</div>
                            <?php endif; ?>

                            <input type="hidden" id="selected-bank-id" value="<?php echo !empty($banks) ? (int)$banks[0]['id'] : ''; ?>">

                            <div style="margin-top: 14px;">
                                <label style="display: block; margin-bottom: 6px; color: var(--text-primary); font-weight: bold;">Nội dung thanh toán</label>
                                <div style="display: flex; gap: 8px;">
                                    <input type="text" id="bank-payment-content" readonly style="flex: 1; padding: 10px; background: var(--primary-bg); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px;" placeholder="Nội dung chuyển khoản sẽ hiển thị tại đây">
                                    <button type="button" id="copy-payment-content" style="padding: 10px 14px; background: var(--accent-color); color: var(--primary-bg); border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Copy</button>
                                </div>
                                <div style="margin-top: 6px; font-size: 12px; color: var(--text-secondary);">Nội dung này do admin cấu hình trong phần quản lý ngân hàng.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Details (shown when card is selected) -->
                    <div style="margin-bottom: 20px;">
                        <h3 style="color: var(--accent-color); margin-bottom: 20px;">🎫 Chi tiết thẻ</h3>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: bold;">Số thẻ</label>
                            <input type="text" class="form-control" placeholder="0000 0000 0000 0000" style="width: 100%; padding: 10px; background: var(--secondary-bg); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px;">
                        </div>
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: bold;">Ngày hết hạn</label>
                                <input type="text" class="form-control" placeholder="MM/YY" style="width: 100%; padding: 10px; background: var(--secondary-bg); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: bold;">CVV</label>
                                <input type="text" class="form-control" placeholder="000" style="width: 100%; padding: 10px; background: var(--secondary-bg); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 4px;">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Order Summary -->
                <div style="background: var(--tertiary-bg); border: 1px solid var(--border-color); border-radius: 4px; padding: 20px; height: fit-content;">
                    <h3 style="color: var(--accent-color); margin-bottom: 15px;">📦 Tóm tắt đơn hàng</h3>
                    
                    <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px;">
                        <div style="margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                                <span>Elden Ring</span>
                                <span>499.000₫</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                                <span>Baldur's Gate 3</span>
                                <span>699.000₫</span>
                            </div>
                        </div>
                    </div>

                    <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                            <span>Tổng tiền hàng:</span>
                            <span>1.198.000₫</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                            <span>Thuế:</span>
                            <span>0₫</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span>Phí vận chuyển:</span>
                            <span>0₫</span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; color: var(--success); margin-bottom: 20px;">
                        <span>Tổng cộng:</span>
                        <span>1.198.000₫</span>
                    </div>

                    <button style="width: 100%; padding: 12px; background: var(--accent-color); color: var(--primary-bg); border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-bottom: 10px;">✓ Xác nhận thanh toán</button>
                    <button style="width: 100%; padding: 12px; background: var(--tertiary-bg); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: 4px; cursor: pointer;">← Quay lại giỏ hàng</button>

                    <div style="background: rgba(40, 167, 69, 0.1); border: 1px solid var(--success); border-radius: 4px; padding: 12px; margin-top: 15px; font-size: 12px; color: var(--text-secondary);">
                        🔒 Thanh toán an toàn với SSL encryption
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <script>
        const paymentRadios = document.querySelectorAll('input[name="payment"]');
        const bankPanel = document.getElementById('bank-transfer-panel');
        const bankOptions = document.querySelectorAll('.bank-option');
        const selectedBankInput = document.getElementById('selected-bank-id');
        const paymentContentInput = document.getElementById('bank-payment-content');
        const copyPaymentContentBtn = document.getElementById('copy-payment-content');

        function updatePaymentPanel() {
            const selected = document.querySelector('input[name="payment"]:checked');
            if (!selected || !bankPanel) return;
            bankPanel.style.display = selected.value === 'bank' ? 'block' : 'none';
        }

        function selectBankOption(optionEl) {
            if (!optionEl) return;
            bankOptions.forEach(el => {
                el.style.borderColor = 'var(--border-color)';
                el.style.boxShadow = 'none';
            });
            optionEl.style.borderColor = 'var(--accent-color)';
            optionEl.style.boxShadow = '0 0 0 2px rgba(0,220,255,0.2)';

            const bankId = optionEl.getAttribute('data-bank-id');
            const content = optionEl.getAttribute('data-bank-content') || '';
            if (selectedBankInput) selectedBankInput.value = bankId || '';
            if (paymentContentInput) paymentContentInput.value = content;
        }

        paymentRadios.forEach(radio => radio.addEventListener('change', updatePaymentPanel));
        bankOptions.forEach(option => option.addEventListener('click', () => selectBankOption(option)));

        if (bankOptions.length > 0) {
            selectBankOption(bankOptions[0]);
        }
        updatePaymentPanel();

        copyPaymentContentBtn?.addEventListener('click', () => {
            if (!paymentContentInput) return;
            paymentContentInput.select();
            paymentContentInput.setSelectionRange(0, 99999);
            document.execCommand('copy');
        });
    </script>
</body>
</html>
