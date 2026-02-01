<?php
/**
 * QR Code Helper Functions
 * Tạo mã QR cho thanh toán ngân hàng
 */

/**
 * Generate QR code image sử dụng Google Charts API hoặc local library
 * @param string $text - Text to encode in QR
 * @param string $filename - Output filename
 * @return bool
 */
function generateQRCodeImage($text, $filename = null) {
    if (!$filename) {
        $filename = 'qr_' . time() . '_' . md5($text) . '.png';
    }

    $qrDir = dirname(__DIR__) . '/public/uploads/qr';
    
    // Create directory if not exists
    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0755, true);
    }

    $filepath = $qrDir . '/' . $filename;

    // Try using Google Charts API first
    try {
        $encodedText = urlencode($text);
        $apiUrl = "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl={$encodedText}&choe=UTF-8";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $qrImage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $qrImage) {
            file_put_contents($filepath, $qrImage);
            return $filepath;
        }
    } catch (Throwable $e) {
        // Fallback to alternative method
    }

    // Fallback: Use local QR library (if available)
    if (function_exists('qr_generateQR')) {
        return qr_generateQR($text, $filepath);
    }

    // If all fails, throw error
    throw new RuntimeException('Không thể tạo mã QR. Vui lòng thử lại sau.');
}

/**
 * Generate VietQR format string for bank transfer
 * Format: vietqr://pay/{bankCode}/{accountNumber}?amount={amount}&addInfo={addInfo}&accountName={accountName}
 */
function generateVietQRFormat($bankCode, $accountNumber, $accountHolder, $amount = null) {
    // Basic VietQR format
    $qrString = "vietqr://pay/{$bankCode}/{$accountNumber}";
    
    if ($accountHolder) {
        $accountHolder = str_replace(' ', '%20', $accountHolder);
        $qrString .= "?accountName={$accountHolder}";
    }
    
    if ($amount) {
        $qrString .= (strpos($qrString, '?') ? '&' : '?') . "amount={$amount}";
    }

    return $qrString;
}

/**
 * Generate QR data as base64 image
 */
function generateQRBase64($text) {
    try {
        $filepath = generateQRCodeImage($text);
        $imageData = file_get_contents($filepath);
        return 'data:image/png;base64,' . base64_encode($imageData);
    } catch (Throwable $e) {
        throw new RuntimeException('Lỗi tạo mã QR: ' . $e->getMessage());
    }
}

/**
 * Create QR code for bank account
 */
function createBankQRCode($bank, $amount = null) {
    // Use VietQR format
    $qrText = generateVietQRFormat(
        $bank['bank_code'],
        $bank['account_number'],
        $bank['account_holder'],
        $amount
    );

    return generateQRBase64($qrText);
}

/**
 * Download QR code file
 */
function downloadQRCode($base64Data, $filename = 'qr_code.png') {
    // Extract base64 data
    $imageData = str_replace('data:image/png;base64,', '', $base64Data);
    $imageData = base64_decode($imageData);

    // Set headers
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($imageData));

    echo $imageData;
    exit;
}
