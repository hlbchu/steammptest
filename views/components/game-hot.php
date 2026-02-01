<!-- GAME HOT SECTION COMPONENT -->
<section class="section">
    <div class="section-header">
        <h2 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24">
                <use xlink:href="#icon-fire"></use>
            </svg>
            Game Hot
        </h2>
        <a href="#" class="view-more">Thêm 
            <svg width="14" height="14" viewBox="0 0 24 24">
                <use xlink:href="#icon-arrow-right"></use>
            </svg>
        </a>
    </div>
    <div class="game-grid">
        <?php 
        foreach ($games_hot as $game): 
        ?>
            <div class="game-card" data-id="<?php echo $game['id']; ?>" data-detail-url="?page=product&id=<?php echo $game['id']; ?>">
                <div class="game-image">
                    <?php 
                    // Xác định URL hình ảnh
                    if (filter_var($game['image'], FILTER_VALIDATE_URL)) {
                        $imageSrc = $game['image'];
                    } else {
                        $imageSrc = '/steamweb/public/assets/images/' . $game['image'];
                    }
                    
                    $price = (float)$game['price'];
                    $salePrice = isset($game['sale_price']) && $game['sale_price'] > 0 ? (float)$game['sale_price'] : null;
                    $finalPrice = $salePrice ?? $price;
                    $discountPercent = ($salePrice && $price > 0) ? round((1 - ($salePrice / $price)) * 100) : 0;
                    $badge = $discountPercent > 0 ? "-{$discountPercent}%" : 'Hot';
                    ?>
                    <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22120%22%3E%3Crect fill=%22%23171a21%22 width=%22200%22 height=%22120%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 fill=%22%2366c0f4%22 font-family=%22Arial%22 font-size=%2214%22 text-anchor=%22middle%22 dy=%22.3em%22%3EGame Image%3C/text%3E%3C/svg%3E'">
                    <span class="game-badge badge-sale"><?php echo htmlspecialchars($badge); ?></span>
                </div>
                <div class="game-info">
                    <h3 class="game-name"><?php echo htmlspecialchars($game['name']); ?></h3>
                    <div class="game-price-row">
                        <?php if ($salePrice): ?>
                        <span class="game-price-old"><?php echo number_format($price, 0, ',', '.'); ?>₫</span>
                        <?php endif; ?>
                        <span class="game-price"><?php echo number_format($finalPrice, 0, ',', '.'); ?>₫</span>
                    </div>
                    <button class="btn-buy" data-add-to-cart data-product-id="<?php echo $game['id']; ?>" data-quantity="1">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <use xlink:href="#icon-cart"></use>
                        </svg>
                        Thêm giỏ hàng
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
