<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Session</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #1a1d2e; color: #fff; }
        .info { background: #2a2d3e; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .label { color: #00b4d8; font-weight: bold; }
        .value { color: #90ee90; }
        .admin { color: #ffd700; }
        .user { color: #ff7777; }
        a { color: #00b4d8; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Session Debug Info</h1>
    
    <div class="info">
        <div class="label">Logged In:</div>
        <div class="value"><?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'YES' : 'NO'; ?></div>
    </div>

    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
    <div class="info">
        <div class="label">User ID:</div>
        <div class="value"><?php echo $_SESSION['user_id'] ?? 'N/A'; ?></div>
    </div>

    <div class="info">
        <div class="label">Username:</div>
        <div class="value"><?php echo $_SESSION['username'] ?? 'N/A'; ?></div>
    </div>

    <div class="info">
        <div class="label">Email:</div>
        <div class="value"><?php echo $_SESSION['email'] ?? 'N/A'; ?></div>
    </div>

    <div class="info">
        <div class="label">Role:</div>
        <div class="<?php echo ($_SESSION['role'] ?? 'user') === 'admin' ? 'admin' : 'user'; ?>">
            <?php echo $_SESSION['role'] ?? 'user'; ?>
            <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
                ✓ Admin Menu sẽ hiển thị
            <?php else: ?>
                ✗ Cần role = 'admin' để thấy menu admin
            <?php endif; ?>
        </div>
    </div>

    <div class="info">
        <div class="label">All Session Data:</div>
        <pre style="color: #87ceeb;"><?php print_r($_SESSION); ?></pre>
    </div>
    <?php else: ?>
    <div class="info">
        <p>Bạn chưa đăng nhập. <a href="views/client/login.php">Đăng nhập tại đây</a></p>
    </div>
    <?php endif; ?>

    <hr style="margin: 30px 0; border-color: #444;">
    
    <h2>Actions</h2>
    <p><a href="views/client/index.php">← Về trang chủ</a></p>
    <p><a href="views/client/login.php">Đăng nhập</a></p>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
    <p><a href="app/Controllers/AuthController.php?action=logout" style="color: #ff7777;">Đăng xuất</a></p>
    <?php endif; ?>
</body>
</html>
