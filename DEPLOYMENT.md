# Hướng dẫn Deploy lên AApanel

## Các bước chuẩn bị

### 1. Build Assets (Quan trọng!)

Trước khi deploy, bạn **PHẢI** build assets:

```bash
npm install
npm run build
```

Lệnh này sẽ tạo các file trong `public/build/` với:
- `manifest.json` - File mapping assets
- `assets/` - Thư mục chứa CSS và JS đã được build và minify

### 2. Cấu hình .env trên server

Đảm bảo file `.env` trên server có các cấu hình sau:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# ... các cấu hình khác
```

**QUAN TRỌNG:**
- `APP_ENV` phải là `production` để Laravel sử dụng built assets
- `APP_DEBUG` phải là `false` trong production

### 3. Upload files lên server

Upload các file và thư mục sau:
- Toàn bộ project (trừ `node_modules`, `.git`)
- **Đặc biệt**: Phải có thư mục `public/build/` với:
  - `manifest.json`
  - `assets/` (chứa CSS và JS đã build)

### 4. Cấu hình Document Root

Trong AApanel, cấu hình Document Root trỏ đến thư mục `public`:
```
/path/to/your/project/public
```

### 5. Set permissions (QUAN TRỌNG!)

**Trên AApanel, chạy các lệnh sau:**

```bash
cd /www/wwwroot/keki.snacksoft.net

# Set ownership (thay 'www' bằng user của web server)
chown -R www:www storage bootstrap/cache

# Set permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Hoặc nếu dùng với AApanel, thử:
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 777 storage/framework
chmod -R 777 storage/logs
```

**Nếu vẫn lỗi, kiểm tra:**

1. Kiểm tra user của web server:
```bash
ps aux | grep php-fpm
# Hoặc
ps aux | grep nginx
```

2. Set ownership cho đúng user:
```bash
# Ví dụ nếu user là 'www'
chown -R www:www storage bootstrap/cache

# Hoặc nếu user là 'www-data'
chown -R www-data:www-data storage bootstrap/cache
```

3. Kiểm tra SELinux (nếu có):
```bash
# Kiểm tra
getenforce

# Nếu là Enforcing, có thể cần tắt tạm thời hoặc set context
chcon -R -t httpd_sys_rw_content_t storage/
chcon -R -t httpd_sys_rw_content_t bootstrap/cache/
```

### 6. Fix Permissions (QUAN TRỌNG - Nếu gặp lỗi Permission denied)

**Trên server, chạy các lệnh sau:**

```bash
cd /www/wwwroot/keki.snacksoft.net

# Cách 1: Sử dụng script tự động
chmod +x fix-permissions.sh
bash fix-permissions.sh

# Cách 2: Chạy thủ công
# Tìm user của web server
ps aux | grep php-fpm | grep -v grep

# Set ownership (thay 'www' bằng user bạn vừa tìm thấy)
chown -R www:www storage bootstrap/cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod -R 777 storage/framework
chmod -R 777 storage/framework/views
chmod -R 777 storage/framework/cache
chmod -R 777 storage/framework/sessions
chmod -R 777 storage/logs
```

### 7. Chạy các lệnh Laravel

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Xử lý lỗi Permission Denied

**Lỗi:**
```
file_put_contents(.../storage/framework/views/...): Failed to open stream: Permission denied
```

**Giải pháp:**

1. **Kiểm tra user của web server:**
```bash
ps aux | grep php-fpm | head -1
```

2. **Set ownership:**
```bash
# Ví dụ nếu user là 'www'
chown -R www:www storage bootstrap/cache
chown -R www:www /www/wwwroot/keki.snacksoft.net
```

3. **Set permissions:**
```bash
chmod -R 775 storage bootstrap/cache
chmod -R 777 storage/framework
```

4. **Clear và rebuild cache:**
```bash
php artisan config:clear
php artisan view:clear
php artisan config:cache
php artisan view:cache
```

## Kiểm tra sau khi deploy

### Kiểm tra assets đã được load

1. Mở Developer Tools (F12)
2. Vào tab Network
3. Reload trang
4. Kiểm tra:
   - CSS và JS files phải có đường dẫn như: `/build/assets/app-xxxxx.css`
   - **KHÔNG** được có đường dẫn như: `http://localhost:5173` hoặc `https://[::1]:5173`

### Nếu vẫn thấy lỗi localhost:5173

**Nguyên nhân:**
- `APP_ENV` trong `.env` không phải `production`
- Hoặc không có file `public/build/manifest.json`

**Giải pháp:**
1. Kiểm tra `.env`: `APP_ENV=production`
2. Chạy lại: `npm run build`
3. Upload lại thư mục `public/build/`
4. Chạy: `php artisan config:clear && php artisan config:cache`

## Lưu ý

- **KHÔNG** chạy `npm run dev` trên production server
- Chỉ dùng `npm run build` để build assets trước khi deploy
- File `public/build/` phải được commit vào git hoặc upload lên server

