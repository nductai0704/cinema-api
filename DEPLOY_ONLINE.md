# HƯỚNG DẪN DEPLOY API ONLINE (MIỄN PHÍ + DỄ NHẤT)

## 🎯 **Cách 1: Railway.app (Khuyên dùng - 30s setup)**

### Bước 1: Tạo GitHub Repo
```
1. Vào github.com → New repository → "cinema-api"
2. Terminal VSCode:
   git init
   git add .
   git commit -m "Initial API"
   git branch -M main
   git remote add origin https://github.com/TEN_GITHUB/cinema-api.git
   git push -u origin main
```

### Bước 2: Deploy Railway
```
1. Đăng ký railway.app (GitHub login)
2. "New Project" → Deploy from GitHub repo → Chọn "cinema-api"
3. Railway tự deploy → 2 phút xong
4. Lấy URL: https://cinema-api-production.up.railway.app
```

### Bước 3: Setup Database + Env
```
Railway dashboard → Variables:
DATABASE_URL=postgresql://... (Railway tạo sẵn)
APP_KEY=base64:... (chạy local: php artisan key:generate → copy APP_KEY)
APP_ENV=production
APP_DEBUG=false

→ Redeploy
```

**Base URL mới**: `https://cinema-api-production.up.railway.app/api/v1/`

---

## 🎯 **Cách 2: Render.com (Laravel optimized)**

```
1. render.com → New → Web Service → GitHub repo
2. Build: "composer install"
3. Start: "php artisan serve --host=0.0.0.0 --port=$PORT" 
4. Add vars: APP_KEY, DATABASE_URL (PostgreSQL add-on)
```

---

## 🎯 **Cách 3: Vercel (Laravel adapter)**

```
1. vercel.com → Import GitHub
2. Framework: "Other"
3. Build: vercel-laravel/setup
```

---

## ✅ **Sau Deploy - Gửi Frontend**
```
API URL: https://cinema-api-production.up.railway.app/api/v1/
Docs: [paste API_VIETNAM.md]
Test: https://cinema-api-production.up.railway.app/api/v1/movies
```

**Lệnh Git ngay**:
```
git remote -v  (check remote)
git push origin main
```

**Railway link**: https://railway.app/new (mở → connect GitHub → done!)**

**Ưu tiên Railway**: Free tier 5$/tháng, auto DB, 1-click deploy.
