# STEAMWEB - Cửa hàng Game

## Cấu trúc dự án

```
steamweb/
├── app/
│   ├── Controllers/     # Logic điều hướng
│   ├── Models/          # Tương tác Database
│   ├── Helpers/         # Hàm tiện ích
│   └── Middleware/      # Bảo mật, xác thực
├── config/
│   ├── database.php     # Kết nối DB
│   └── config.php       # Cấu hình
├── public/
│   ├── assets/          # CSS, JS
│   ├── uploads/         # Upload files
│   └── index.php        # Entry point
├── views/
│   ├── client/          # Giao diện khách
│   ├── admin/           # Giao diện admin
│   └── partials/        # Header, Footer
├── .env                 # Biến môi trường
├── .gitignore
└── .htaccess
```

## Cài đặt

1. Copy thư mục vào `C:\xampp\htdocs\`
2. Tạo database `steamweb` trong phpMyAdmin
3. Import file SQL (nếu có)
4. Truy cập: `http://localhost/steamweb/public`

## Thông tin Database

- Host: localhost
- Database: steamweb
- User: root
- Password: (trống)

Sửa trong `config/database.php` nếu khác.
