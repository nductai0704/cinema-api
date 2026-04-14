# CÀI GIT TRƯỚC KHI DEPLOY

**Lỗi**: `git not recognized` → Git chưa cài.

## 🔧 **Cài Git Windows (2 phút)**

### Cách 1: Download + Install
```
1. https://git-scm.com/download/windows → Download
2. Chạy file .exe → Next → Next → Install
3. Restart VSCode/Terminal
4. Test: git --version
```

### Cách 2: Chocolatey (nếu có)
```
choco install git
```

### Cách 3: Winget (Windows 11)
```
winget install --id Git.Git -e --source winget
```

## ✅ **Sau khi cài Git, chạy:**
```
git --version  → git version 2.x.x
git init
git add .
git commit -m "Cinema API"
```

**Done → Quay lại DEPLOY_ONLINE.md bước GitHub!**

**Ưu tiên**: Cài Git → git push → Railway deploy → URL online gửi frontend.
