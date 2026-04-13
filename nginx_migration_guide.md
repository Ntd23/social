# Hướng dẫn cấu hình Nginx sau khi chuyển UI sang Nuxt.js

## Kiến trúc sau migration

```
Internet
    │
    ▼
Nginx (port 443/80) — vnseea.vn
    │
    ├──► /admincp, /admin-cp, /admin-panel   ──► PHP-FPM (giữ nguyên)
    ├──► /api-v2.php, /api/...               ──► PHP-FPM (giữ nguyên)
    ├──► /*.php                               ──► PHP-FPM (giữ nguyên)
    ├──► /upload/, /themes/, /assets/ (static)──► Nginx serve trực tiếp
    │
    └──► Tất cả user routes còn lại          ──► Nuxt.js (port 3000)
```

---

## Bước 1: Cài đặt và chạy Nuxt bằng PM2

```bash
# Cài pnpm nếu chưa có
npm install -g pnpm

# Trên server, vào thư mục Nuxt
cd /home/vnseea/social/nuxt-app

# Install dependencies
pnpm install

# Build production (SSR mode - mặc định của Nuxt)
pnpm build
# → Output: .output/server/index.mjs  (Node.js SSR server)
# → Output: .output/public/           (static assets)

# Cài PM2 nếu chưa có
npm install -g pm2

# Chạy Nuxt SSR server với PM2
pm2 start .output/server/index.mjs \
    --name "nuxt-social" \
    --env production \
    -- --host 127.0.0.1 --port 3000

# Hoặc dùng pm2 ecosystem file (khuyến nghị)
pm2 start ecosystem.config.cjs

# Auto-start khi server reboot
pm2 startup
pm2 save
```

**File `ecosystem.config.cjs`** (tạo trong thư mục `nuxt-app/`):
```js
module.exports = {
    apps: [{
        name: 'nuxt-social',
        script: '.output/server/index.mjs',
        instances: 1,          // Tăng lên nếu server nhiều CPU
        exec_mode: 'fork',     // Dùng 'cluster' nếu instances > 1
        env_production: {
            NODE_ENV: 'production',
            HOST: '127.0.0.1',
            PORT: 3000,
            NITRO_HOST: '127.0.0.1',
            NITRO_PORT: 3000,
        }
    }]
}
```

> **Kiểm tra Nuxt SSR đang chạy:**
> ```bash
> pm2 status
> curl http://localhost:3000  # Phải trả về HTML đầy đủ (SSR rendered)
> ```

---

## Bước 2: Config Nginx mới

Thay toàn bộ nội dung file nginx config (thường ở `/etc/nginx/sites-available/vnseea.vn` hoặc `/etc/nginx/conf.d/vnseea.vn.conf`) bằng nội dung sau:

```nginx
# ─────────────────────────────────────────────────────────
# Upstream Nuxt.js
# ─────────────────────────────────────────────────────────
upstream nuxt_app {
    server 127.0.0.1:3000;
    keepalive 64;
}

# ─────────────────────────────────────────────────────────
# HTTP → HTTPS redirect
# ─────────────────────────────────────────────────────────
server {
    server_name vnseea.vn www.vnseea.vn mail.vnseea.vn webmail.vnseea.vn admin.vnseea.vn;
    client_max_body_size 512M;
    listen 80;
    listen [::]:80;
    root /home/vnseea/social;

    access_log /var/log/virtualmin/vnseea.vn_access_log;
    error_log /var/log/virtualmin/vnseea.vn_error_log;

    location ^~ /.well-known/acme-challenge/ {
        root /home/vnseea/social;
        default_type "text/plain";
        try_files $uri =404;
    }

    if ($host = webmail.vnseea.vn) {
        return 301 https://vnseea.vn:20000$request_uri;
    }

    if ($host = admin.vnseea.vn) {
        return 301 https://vnseea.vn:10000$request_uri;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

# ─────────────────────────────────────────────────────────
# HTTPS - Main server
# ─────────────────────────────────────────────────────────
server {
    server_name vnseea.vn www.vnseea.vn mail.vnseea.vn webmail.vnseea.vn admin.vnseea.vn;
    client_max_body_size 512M;
    listen 443 ssl;
    listen [::]:443 ssl;
    root /home/vnseea/social;
    index index.php index.html index.htm;

    access_log /var/log/virtualmin/vnseea.vn_access_log;
    error_log /var/log/virtualmin/vnseea.vn_error_log;

    ssl_certificate /etc/letsencrypt/live/vnseea.vn/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/vnseea.vn/privkey.pem;

    # ── Webmail / Admin subdomain redirect ─────────────────
    if ($host = webmail.vnseea.vn) {
        return 301 https://vnseea.vn:20000$request_uri;
    }
    if ($host = admin.vnseea.vn) {
        return 301 https://vnseea.vn:10000$request_uri;
    }

    # ── ACME challenge ──────────────────────────────────────
    location ^~ /.well-known/ {
        try_files $uri /;
    }

    # ═══════════════════════════════════════════════════════
    # BLOCK 1: BẢO VỆ - Deny truy cập thư mục nhạy cảm
    # ═══════════════════════════════════════════════════════
    location ~ /cache {
        deny all;
        return 404;
    }
    location /sources {
        deny all;
        return 404;
    }
    location /nodejs {
        deny all;
        return 404;
    }

    # ═══════════════════════════════════════════════════════
    # BLOCK 2: STATIC FILES - Nginx serve trực tiếp (không qua PHP hay Nuxt)
    # ═══════════════════════════════════════════════════════
    location /upload/ {
        alias /home/vnseea/social/upload/;
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location /themes/ {
        alias /home/vnseea/social/themes/;
        expires 7d;
        try_files $uri =404;
    }

    # Nuxt static assets (_nuxt/ folder)
    location /_nuxt/ {
        alias /home/vnseea/social/nuxt-app/.output/public/_nuxt/;
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # ═══════════════════════════════════════════════════════
    # BLOCK 3: PHP ADMIN - Giữ nguyên PHP
    # ═══════════════════════════════════════════════════════
    location = /admincp {
        rewrite ^(.*)$ /admincp.php last;
    }
    location /admincp {
        rewrite ^/admincp/(.*)$ /admincp.php?page=$1 last;
    }
    location /admin {
        rewrite ^/admin-cp$ /admincp.php last;
        rewrite ^/admin-cp/(.*)$ /admincp.php?page=$1 last;
        rewrite ^/admin/ads/edit/(\d+)(/?|)$ /index.php?link1=manage-ads&id=$1 last;
    }
    location /adminPages/ {
        alias /home/vnseea/social/admin-panel/;
    }

    # ═══════════════════════════════════════════════════════
    # BLOCK 4: PHP API - Giữ nguyên PHP
    # ═══════════════════════════════════════════════════════
    location = /api-v2.php {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/177434245169607.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $document_root;
    }

    # Route /api/* → api-v2.php (giữ pattern cũ để mobile app không bị break)
    location /api {
        rewrite ^/api(/?|)$ /api-v2.php last;
        rewrite ^/api/([^\/]+)(\/|)$ /api-v2.php?type=$1 last;
    }

    # Các PHP file đặc biệt cần giữ
    location ~ ^/(ajax_loading|requests|login-with|call|call_livekit|call_group_livekit|call_jitsi|cron-job|OneSignalSDKWorker)\.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/177434245169607.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $document_root;
    }

    # Tất cả PHP files còn lại
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/177434245169607.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $document_root;
    }

    # ═══════════════════════════════════════════════════════
    # BLOCK 5: NUXT - Tất cả user routes → Nuxt.js
    # ═══════════════════════════════════════════════════════
    location / {
        proxy_pass http://nuxt_app;
        proxy_http_version 1.1;

        # Headers cần thiết
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Upgrade           $http_upgrade;
        proxy_set_header Connection        "upgrade";

        # Cookie pass-through (quan trọng cho auth)
        proxy_set_header Cookie            $http_cookie;
        proxy_pass_header Set-Cookie;

        # Timeout
        proxy_connect_timeout 60s;
        proxy_send_timeout    60s;
        proxy_read_timeout    60s;

        # Buffer
        proxy_buffering    on;
        proxy_buffer_size  128k;
        proxy_buffers      4 256k;
    }

    rewrite /awstats/awstats.pl /cgi-bin/awstats.pl;
}
```

---

## Bước 3: Cấu hình Nuxt để gọi API đúng

> **Lưu ý**: Nuxt 3 dùng **Vite làm bundler mặc định** — không cần cài thêm package nào. Chỉ cần config trong `nuxt.config.ts`.

Trong `nuxt-app/nuxt.config.ts`:

```typescript
export default defineNuxtConfig({
    // SSR = true là mặc định, nhưng ghi rõ cho chắc
    ssr: true,

    runtimeConfig: {
        // Biến chỉ dùng phía server (không lộ ra client)
        apiSecret: process.env.API_SECRET ?? '',

        public: {
            // Biến dùng được cả server lẫn client
            apiBase: process.env.API_BASE ?? 'https://vnseea.vn',
        }
    },

    // SSR: Khi server-side render, Nuxt gọi PHP qua internal network
    // Không có CORS vì gọi từ server → server (cùng máy)
    routeRules: {
        // API calls từ SSR context: gọi thẳng PHP-FPM (không qua Nginx)
        // Client-side (sau hydration): gọi qua domain bình thường
    },

    nitro: {
        // Cần thiết khi chạy sau reverse proxy
        preset: 'node-server',
    },

    // ─── Vite config (Nuxt 3 dùng Vite làm bundler mặc định) ───
    vite: {
        // Tối ưu build
        build: {
            // Tăng warning limit nếu có chunk lớn
            chunkSizeWarningLimit: 1000,

            rollupOptions: {
                output: {
                    // Tách vendor chunk riêng để cache browser tốt hơn
                    manualChunks: {
                        'vendor-vue': ['vue', 'vue-router', '@vueuse/core'],
                        'vendor-pinia': ['pinia', '@pinia/nuxt'],
                    }
                }
            }
        },

        // Dev server (chỉ dùng khi local development)
        server: {
            // Cho phép Hot Module Replacement qua Nginx (local)
            hmr: {
                protocol: 'wss',
                host: 'localhost',
            }
        },

        // Optimize deps thường dùng trong dự án
        optimizeDeps: {
            include: [
                'pinia',
                '@vueuse/core',
            ]
        },

        // CSS
        css: {
            preprocessorOptions: {
                // Nếu dùng SCSS globals
                // scss: {
                //     additionalData: `@use "~/assets/scss/variables" as *;`
                // }
            }
        }
    }
})
```

**File `.env`** (tạo trong `nuxt-app/`):
```env
API_BASE=https://vnseea.vn
NITRO_HOST=127.0.0.1
NITRO_PORT=3000
```

Trong composable:
```typescript
// composables/useApi.ts
export const useApi = () => {
    const config = useRuntimeConfig()

    // useFetch tự động dùng đúng base URL cho cả SSR lẫn CSR
    const call = (action: string, body: Record<string, any>) =>
        $fetch(`${config.public.apiBase}/api-v2.php`, {
            method: 'POST',
            body: { ...body },
            query: { action },
            credentials: 'include', // gửi kèm cookie
        })

    return { call }
}
```

---

## Bước 4: Xử lý Auth (Session Cookie)

Vì Nuxt và PHP cùng domain `vnseea.vn`, cookie hoạt động tự nhiên:

```typescript
// Khi login thành công từ Nuxt:
const { data } = await $fetch('/api-v2.php', {
    method: 'POST',
    body: { action: 'auth', username, password }
})

// Lưu user_session vào cookie
const cookie = useCookie('user_session', {
    maxAge: 60 * 60 * 24 * 30, // 30 ngày
    sameSite: 'lax',
    secure: true,  // vì đang dùng HTTPS
})
cookie.value = data.user_session

// Mỗi lần gọi API:
const session = useCookie('user_session')
await $fetch('/api-v2.php', {
    method: 'POST',
    body: {
        action: 'get-user-data',
        user_session: session.value,
        ...
    }
})
```

---

## Bước 5: Reload Nginx

```bash
# Kiểm tra config trước
nginx -t

# Reload (không downtime)
nginx -s reload

# Kiểm tra SSR thực sự hoạt động (HTML đầy đủ trong source)
curl -s https://vnseea.vn | grep "<title>"
# Phải thấy title tag ngay trong HTML, không phải empty (CSR sẽ empty)
```

---

## Thứ tự triển khai an toàn (không downtime)

```
1. [Server] Upload source Nuxt vào /home/vnseea/social/nuxt-app/

2. [Server] Install dependencies và build:
           cd /home/vnseea/social/nuxt-app
           pnpm install
           pnpm build

3. [Server] Tạo file ecosystem.config.cjs và chạy PM2:
           pm2 start ecosystem.config.cjs --env production
           pm2 status  ← kiểm tra status = online
           curl http://localhost:3000  ← phải trả về HTML

4. [Server] Backup nginx config cũ:
           cp /etc/nginx/sites-available/vnseea.vn \
              /etc/nginx/sites-available/vnseea.vn.bak.$(date +%Y%m%d)

5. [Server] Thay config Nginx mới (xem BLOCK 2 bên trên)

6. [Server] Test config:
           nginx -t  ← PHẢI PASS trước khi reload

7. [Server] Reload Nginx (không downtime):
           nginx -s reload

8. [Browser] Kiểm tra:
           https://vnseea.vn          → Nuxt SSR render (xem page source có HTML) ✅
           https://vnseea.vn/admincp  → PHP admin panel ✅
           https://vnseea.vn/api-v2.php → JSON {"api_status":...} ✅
           https://vnseea.vn/upload/... → File tĩnh load được ✅
```

> **Rollback nếu lỗi:**
> ```bash
> # Khôi phục nginx config cũ
> cp /etc/nginx/sites-available/vnseea.vn.bak.YYYYMMDD \
>    /etc/nginx/sites-available/vnseea.vn
> nginx -s reload
>
> # Stop Nuxt nếu cần
> pm2 stop nuxt-social
> ```

---

## Lưu ý quan trọng

### 1. pnpm và Node.js version
```bash
# Đảm bảo Node.js >= 18
node -v

# Nếu cần nâng Node.js dùng nvm
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
nvm install 20
nvm use 20
```

### 2. PM2 phải chạy trước khi Nginx reload
Nếu Nuxt chưa chạy mà Nginx đã proxy → user thấy `502 Bad Gateway`.

### 3. Upload files
`/upload/` được serve trực tiếp bởi Nginx (không qua Nuxt hay PHP) → **nhanh hơn**, nhưng cần đảm bảo Nginx có quyền đọc thư mục upload.

```bash
chown -R www-data:www-data /home/vnseea/social/upload
chmod -R 755 /home/vnseea/social/upload
```

### 4. Deploy lại Nuxt sau khi có code mới
```bash
cd /home/vnseea/social/nuxt-app
git pull                        # hoặc upload file mới
pnpm install                    # nếu có package mới
pnpm build                      # rebuild
pm2 restart nuxt-social         # restart (có downtime ngắn ~1-2s)
# Hoặc zero-downtime:
pm2 reload nuxt-social
```

### 5. WebSocket cho Chat/Live
Nếu Nuxt dùng WebSocket (chat real-time), config Nginx đã có:
```nginx
proxy_set_header Upgrade    $http_upgrade;
proxy_set_header Connection "upgrade";
```
→ Hoạt động sẵn, không cần thêm gì.

### 6. Node.js socket server (nodejs/ folder)
Server Node.js hiện tại của WoWonder (`/nodejs/`) vẫn chạy độc lập. Nếu cần expose qua domain, thêm location riêng:
```nginx
location /socket.io/ {
    proxy_pass http://127.0.0.1:3002;  # port nodejs server
    proxy_http_version 1.1;
    proxy_set_header Upgrade    $http_upgrade;
    proxy_set_header Connection "upgrade";
}
```

### 7. SEO / Crawler
Nuxt **SSR** render HTML đầy đủ phía server trước khi gửi cho browser → Google crawler đọc được content ngay → SEO tốt hơn SPA/CSR. Không cần cấu hình thêm gì trong Nginx.
