<!-- ==============================================
     SEARCH PAGE
     ============================================== -->

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm - Steam Web</title>
    <link rel="stylesheet" href="assets/style.css">
    <!-- SVG Icons -->
    <svg style="display: none;" xmlns="http://www.w3.org/2000/svg">
        <symbol id="icon-search" viewBox="0 0 24 24"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></symbol>
        <symbol id="icon-user" viewBox="0 0 24 24"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></symbol>
        <symbol id="icon-star" viewBox="0 0 24 24"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2l-2.81 6.63L2 9.24l5.46 4.73L5.82 21z"/></symbol>
    </svg>
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main class="container">
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <use xlink:href="#icon-search"></use>
                    </svg>
                    Kết quả tìm kiếm
                </h2>
            </div>

            <div class="game-grid">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <div class="game-card">
                        <div class="game-image">
                            <span>Game Image</span>
                        </div>
                        <div class="game-info">
                            <div class="game-name">Game Tìm kiếm #<?php echo $i; ?></div>
                            <div class="game-price">499.000₫</div>
                            <span class="game-badge">-15%</span>
                            <div class="game-status">
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24">
                                        <use xlink:href="#icon-user"></use>
                                    </svg>
                                    1.2k
                                </span>
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24">
                                        <use xlink:href="#icon-star"></use>
                                    </svg>
                                    4.5
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
