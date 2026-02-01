<?php
/**
 * DEPOSIT WEBHOOK CONTROLLER
 * Handles SeaPay payment callbacks and updates deposit status
 */

header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = new Database();

// Log webhook request
$webhookData = file_get_contents('php://input');
$webhookLog = fopen(dirname(__DIR__) . '/../database/webhook-log.txt', 'a');
fwrite($webhookLog, date('Y-m-d H:i:s') . " | " . $webhookData . "\n");
fclose($webhookLog);

try {
    $payload = json_decode($webhookData, true);

    if (!$payload) {
        throw new Exception('Invalid webhook payload');
    }

    // Get transaction code from webhook
    $transactionCode = $payload['transaction_code'] ?? null;
    $gatewayReference = $payload['gateway_reference'] ?? null;
    $status = strtolower($payload['status'] ?? '');
    $signature = $payload['signature'] ?? null;

    if (!$transactionCode) {
        throw new Exception('Missing transaction code');
    }

    // Get deposit transaction
    $db->query("SELECT dt.*, ba.gateway_id, pg.api_secret 
               FROM deposit_transactions dt
               LEFT JOIN bank_accounts ba ON dt.bank_account_id = ba.id
               LEFT JOIN payment_gateways pg ON dt.gateway_id = pg.id
               WHERE dt.transaction_code = :code");
    $db->bind(':code', $transactionCode);
    $transaction = $db->fetch();

    if (!$transaction) {
        throw new Exception('Transaction not found');
    }

    // Verify signature (if SeaPay signature validation)
    if ($signature && $transaction['api_secret']) {
        $payloadForSignature = $payload;
        unset($payloadForSignature['signature']);
        ksort($payloadForSignature);
        
        $signString = '';
        foreach ($payloadForSignature as $key => $value) {
            if ($value !== null && $value !== '') {
                $signString .= $key . '=' . $value . '&';
            }
        }
        $signString = rtrim($signString, '&');
        
        $expectedSignature = hash_hmac('sha256', $signString, $transaction['api_secret']);
        
        if ($signature !== $expectedSignature) {
            throw new Exception('Invalid signature');
        }
    }

    // Map status values
    $statusMap = [
        'success' => 'success',
        'paid' => 'success',
        'completed' => 'success',
        'pending' => 'pending',
        'processing' => 'pending',
        'failed' => 'failed',
        'error' => 'failed',
        'cancelled' => 'cancelled',
    ];

    $newStatus = $statusMap[$status] ?? $status;

    // Update deposit transaction
    $db->query("UPDATE deposit_transactions 
               SET status = :status, gateway_reference = :gateway_ref, updated_at = NOW()
               WHERE transaction_code = :code");
    $db->bind(':status', $newStatus);
    $db->bind(':gateway_ref', $gatewayReference);
    $db->bind(':code', $transactionCode);
    $db->execute();

    // If payment successful, update user wallet
    if ($newStatus === 'success') {
        $userId = $transaction['user_id'];
        $amount = $transaction['final_amount'];

        // Update or create wallet
        $db->query("SELECT id FROM wallets WHERE user_id = :user_id");
        $db->bind(':user_id', $userId);
        $wallet = $db->fetch();

        if ($wallet) {
            $db->query("UPDATE wallets SET balance = balance + :amount WHERE user_id = :user_id");
        } else {
            $db->query("INSERT INTO wallets (user_id, balance) VALUES (:user_id, :amount)");
        }
        $db->bind(':user_id', $userId);
        $db->bind(':amount', $amount);
        $db->execute();

        // Log transaction
        $db->query("INSERT INTO wallet_transactions 
                   (user_id, type, amount, reference_id, description) 
                   VALUES (:user_id, 'deposit', :amount, :ref_id, :desc)");
        $db->bind(':user_id', $userId);
        $db->bind(':amount', $amount);
        $db->bind(':ref_id', $transactionCode);
        $db->bind(':desc', 'Nạp tiền thành công qua ' . ($transaction['bank_name'] ?? 'SeaPay'));
        $db->execute();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Webhook processed successfully',
        'transaction_code' => $transactionCode,
        'status' => $newStatus,
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
