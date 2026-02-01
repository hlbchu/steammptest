# Sounds Directory

## Hướng dẫn thêm âm thanh

### File cần có:
- **som_matricula-464025.mp3** - Âm thanh khi thêm vào giỏ hàng

### Cách thêm:
1. Copy file âm thanh `som_matricula-464025.mp3` vào thư mục này
2. Reload trang web
3. Click nút "Thêm giỏ hàng" để test

### Format hỗ trợ:
- MP3 (khuyến nghị)
- OGG
- WAV

### Tải âm thanh mẫu:
Bạn có thể tải âm thanh miễn phí từ:
- https://freesound.org/
- https://mixkit.co/free-sound-effects/
- https://pixabay.com/sound-effects/

### Thêm âm thanh khác:
Chỉnh sửa file `/public/assets/js/sound-manager.js`:
```javascript
soundManager.loadSound('click', '/steamweb/public/assets/sounds/click.mp3');
soundManager.loadSound('success', '/steamweb/public/assets/sounds/success.mp3');
```

Sử dụng:
```javascript
soundManager.play('click');
soundManager.play('success');
```
