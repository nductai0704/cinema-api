# Tài liệu API Rạp Phim (Tiếng Việt)

## http://localhost:8000/api/v1/ là gì?
Đây là **BASE URL** (địa chỉ gốc) của API khi chạy local:
- `localhost:8000`: Server Laravel chạy trên cổng 8000 (php artisan serve)
- `api`: Prefix Laravel cho API routes
- `v1`: Version API (prefix trong routes/api.php)

Ví dụ full URL: `http://localhost:8000/api/v1/movies`

## Chạy server để test:
```bash
cd q:/Cinema_API
php artisan serve
```
Truy cập browser: http://localhost:8000/api/v1/cinemas

## Các endpoints:

### Public (không cần login)
```
GET  /api/v1/cinemas              - Danh sách rạp
GET  /api/v1/cinemas/{id}         - Chi tiết rạp
GET  /api/v1/movies               - Danh sách phim  
GET  /api/v1/movies/{id}          - Chi tiết phim
GET  /api/v1/movies/{id}/showtimes - Lịch chiếu
GET  /api/v1/showtimes/{id}       - Chi tiết suất chiếu
GET  /api/v1/showtimes/{id}/seats - Ghế ngồi suất
GET  /api/v1/showtimes/{id}/availability - Ghế trống
GET  /api/v1/combos               - Combo
GET  /api/v1/genres               - Thể loại
```

### Auth (login trước)
```
POST /api/v1/login                - Đăng nhập lấy token
{
  \"email\": \"test@test.com\",
  \"password\": \"123456\"
}
```

Sau đó dùng header `Authorization: Bearer {token}`

### Đưa cho Frontend:
1. **Gửi API_VIETNAM.md** này
2. **Base URL**: `http://localhost:8000/api/v1/`
3. **CORS**: Frontend thêm origin vào bootstrap/app.php (xem hướng dẫn)
4. **Repo/Git**: Push code lên GitHub share link
5. **Postman collection** nếu cần (test trước)

**Test ngay**: `php artisan serve` → browser http://localhost:8000/api/v1/movies
