<?php
// BANNER SECTION COMPONENT
if (!defined('APP_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}
if (!class_exists('Database')) {
    require_once __DIR__ . '/../../config/database.php';
}

if (!isset($db)) {
    $db = new Database();
}

$slides = [];
$categories = [];

try {
    $db->query("SHOW TABLES LIKE 'banners'");
    $hasTable = (bool) $db->fetch();

    if ($hasTable) {
        $db->query("SHOW COLUMNS FROM banners LIKE 'position'");
        $hasPosition = (bool) $db->fetch();

        $select = $hasPosition
            ? "SELECT id, title, image_url, link_url, position, sort_order FROM banners WHERE is_active = 1 ORDER BY sort_order ASC, id DESC"
            : "SELECT id, title, image_url, link_url, sort_order FROM banners WHERE is_active = 1 ORDER BY sort_order ASC, id DESC";

        $db->query($select);
        $banners = $db->fetchAll();

        foreach ($banners as $banner) {
            $position = $banner['position'] ?? 'slide';
            if ($position === 'category') {
                $categories[] = $banner;
            } else {
                $slides[] = $banner;
            }
        }
    }
} catch (Throwable $e) {
    $slides = [];
    $categories = [];
}

$fallbackSlides = [
    ['title' => 'Counter-Strike 2', 'image_url' => 'https://cdn.cloudflare.steamstatic.com/steam/apps/730/header.jpg', 'link_url' => '#'],
    ['title' => 'Cyberpunk 2077', 'image_url' => 'https://cdn.cloudflare.steamstatic.com/steam/apps/1091500/header.jpg', 'link_url' => '#'],
    ['title' => 'Apex Legends', 'image_url' => 'https://cdn.cloudflare.steamstatic.com/steam/apps/1172470/header.jpg', 'link_url' => '#'],
    ['title' => 'Call of Duty', 'image_url' => 'https://cdn.cloudflare.steamstatic.com/steam/apps/1938090/header.jpg', 'link_url' => '#'],
];

$fallbackCategories = [
    ['title' => 'All Games', 'image_url' => 'https://cdn.cloudflare.steamstatic.com/steam/apps/730/header.jpg', 'link_url' => '#'],
    ['title' => 'Top Sales', 'image_url' => 'https://cdn.cloudflare.steamstatic.com/steam/apps/1091500/header.jpg', 'link_url' => '#'],
    ['title' => 'Ưu Tích', 'image_url' => 'https://cdn.cloudflare.steamstatic.com/steam/apps/1172470/header.jpg', 'link_url' => '#'],
    ['title' => 'Premium', 'image_url' => 'https://cdn.cloudflare.steamstatic.com/steam/apps/1938090/header.jpg', 'link_url' => '#'],
];

if (empty($slides)) {
    $slides = $fallbackSlides;
}
if (empty($categories)) {
    $categories = $fallbackCategories;
}

function resolveBannerUrl($url) {
    if (!$url) {
        return '';
    }
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    if (strpos($url, '/') === 0) {
        return $url;
    }
    if (strpos($url, 'public/') === 0) {
        return rtrim(APP_URL, '/') . '/' . $url;
    }
    return rtrim(APP_URL, '/') . '/public/uploads/banners/' . ltrim($url, '/');
}
?>

<section class="banner-section">
    <div class="banner-main" id="bannerMain">
        <?php foreach ($slides as $index => $slide): ?>
            <a href="<?php echo htmlspecialchars($slide['link_url'] ?? '#'); ?>" class="banner-slide-link <?php echo $index === 0 ? 'active' : ''; ?>">
                <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: url('<?php echo htmlspecialchars(resolveBannerUrl($slide['image_url'] ?? '')); ?>');">
                </div>
            </a>
        <?php endforeach; ?>
        <div class="banner-dots">
            <?php foreach ($slides as $index => $slide): ?>
                <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></span>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="banner-categories">
        <?php foreach ($categories as $category): ?>
            <a href="<?php echo htmlspecialchars($category['link_url'] ?? '#'); ?>" class="category-btn" style="background-image: linear-gradient(to top, rgba(0, 0, 0, 0.85), transparent 70%), url('<?php echo htmlspecialchars(resolveBannerUrl($category['image_url'] ?? '')); ?>');">
                <span><?php echo htmlspecialchars($category['title'] ?? ''); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
