# HƯỚNG DẪN CHI TIẾT: Đưa API cho Frontend Developer

## 1. **API đang chạy sẵn** (đang chạy `php artisan serve`)
```
Base URL: http://127.0.0.1:8000/api/v1/
```
**Test ngay**: Mở browser → http://127.0.0.1:8000/api/v1/movies ← thấy JSON phim

## 2. **Cách đưa TOÀN BỘ API**

### **Cách 1: Share local (test nhanh)**
```
Gửi cho frontend:
- URL: http://127.0.0.1:8000/api/v1/
- Tài liệu: Copy `API_VIETNAM.md`
- Giữ máy chạy: php artisan serve (đừng tắt)

Frontend gọi trực tiếp từ URL này (CORS đã setup nếu cần)
```

### **Cách 2: GitHub (chuyên nghiệp)**
```
git init  (nếu chưa)
git add .
git commit -m "Cinema API complete"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/cinema-api.git
git push -u origin main
```
**Gửi cho frontend**:
```
Repo: https://github.com/YOUR_USERNAME/cinema-api
Cách chạy:
1. git clone [link]
2. composer install  
3. cp .env.example .env
4. php artisan key:generate
5. php artisan migrate --seed
6. php artisan serve
Base URL: http://localhost:8000/api/v1/
```

### **Cách 3: Deploy online (production)**
```
**Railway/Heroku free**:
1. Push GitHub
2. Kết nối Railway.app → Deploy tự động
3. Lấy URL: https://your-app.railway.app/api/v1/

**Gửi**: URL + API_VIETNAM.md
```

## 3. **Frontend cần gì từ bạn?**
✅ **Base URL** (đã có)
✅ **Endpoints list** (`API_VIETNAM.md`)  
✅ **Auth flow** (login → token → Bearer)
✅ **CORS** (setup trong bootstrap/app.php)
❌ **Source code** (không cần đưa backend code)

## 4. **Bước tiếp theo NGAY BÂY GIỜ**
```
1. Test API: Browser → http://127.0.0.1:8000/api/v1/showtimes
2. Copy `API_VIETNAM.md` gửi frontend qua Zalo/Email
3. Nói: "Dùng URL này + docs này để connect nhé"
```

**Xong!** Frontend chỉ cần gọi HTTP requests theo docs.

**Demo test**: `curl http://127.0.0.1:8000/api/v1/cinemas`
