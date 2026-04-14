# Danh sách API cần bổ sung cho Nuxt.js Migration

> Dựa trên so sánh giữa `sources/*.php` (web routes) và `api/v2/endpoints/*.php`

---

## ✅ Đã có sẵn (không cần làm gì)

| Tính năng | Cách dùng |
|---|---|
| **Saved Posts** | `posts.php` với `type=saved` → đã support |
| **Hashtag** | `posts.php` với `type=hashtag&hash=...` → đã support |
| **Reels** | `posts.php` với `filter_by=local_video&is_reel=only` → đã support |
| **Explore** | `fetch-recommended.php` + `get-general-data.php` |
| **Most Liked** | `most_liked.php` → có (1.7KB, đủ dùng) |
| **Albums** | `albums.php` + `get-user-albums.php` → đủ |

---

## ❌ Thực sự thiếu — Cần viết thêm endpoint

### 1. `GET /api/v2/endpoints/subscriptions.php`
**Web route**: `sources/subscriptions.php`  
**Chức năng**: Lấy danh sách người dùng mà user đang subscribe (monetization), và các subscription đang active.  
**Logic cần làm**:
- Query bảng `T_USER_MONETIZATION` theo `user_id`
- Trả về danh sách subscriber + thông tin gói

```php
// Tham số gợi ý:
POST: user_session_id (bắt buộc)
GET:  after_id (phân trang)

// Response:
{ "subscriptions": [...], "total": 123 }
```

---

### 2. `GET /api/v2/endpoints/open_to_work.php`
**Web route**: `sources/open_to_work_posts.php`  
**Chức năng**: Lấy danh sách bài đăng "Open To Work" (chỉ có khi `website_mode = linkedin`).  
**Logic cần làm**:
- Gọi hàm `Wo_GetOpenToWorkPosts()` có sẵn trong PHP
- Wrap thành JSON response

```php
// Tham số gợi ý:
POST: after_post_id (phân trang), limit

// Response:
{ "posts": [...] }
```

> ⚠️ Tính năng này chỉ hiện khi config `website_mode = linkedin`. Nếu site không dùng mode này thì **bỏ qua, không cần làm**.

---

### 3. `POST /api/v2/endpoints/switch_account.php`
**Web route**: `sources/switch-account.php`  
**Chức năng**: Chuyển giữa nhiều tài khoản đã đăng nhập trong cùng một session.  
**Vấn đề**: Tính năng này hiện dùng PHP `$_SESSION` — không tương thích với Nuxt SPA/SSR dùng JWT/cookie.  
**Giải pháp đề xuất**:

```
Option A: Bỏ qua tính năng này (đơn giản nhất)
Option B: Dùng cookie riêng lưu danh sách session_id, 
          khi switch thì re-authenticate với session_id tương ứng
```

---

### 4. `GET /api/v2/endpoints/monetization_user.php`
**Web route**: `sources/monetization.php`  
**Chức năng**: Lấy thông tin monetization của 1 user cụ thể (gói đang offer, giá, trạng thái subscription).  
**Logic**:
- Query `T_USER_MONETIZATION` theo `user_id` từ username
- Trả về danh sách gói + trạng thái đã subscribe hay chưa

```php
// Tham số:
POST: username (hoặc user_id)
POST: viewer_id (user đang xem để check đã sub chưa)

// Response:
{ "user": {...}, "monetizations": [...], "is_subscribed": true/false }
```

---

## ⚠️ Cần kiểm tra thêm (có thể đã đủ)

| Tính năng | Endpoint hiện có | Cần kiểm tra |
|---|---|---|
| **Videos / Watch** | `get-movies.php`, `most_watched.php` | Có filter theo `type=video` trong `posts.php` chưa? |
| **Games** | `games.php` (2.5KB) | Chỉ có list game, thiếu play/score endpoint? |
| **Site Pages** | Không có | Đây là CMS pages — có thể dùng Nuxt content hoặc tạo 1 endpoint mới |

---

## 📊 Tổng kết

| Loại | Số lượng |
|---|---|
| Route đã có API đầy đủ | **~80%** |
| Route dùng params ẩn trong API có sẵn | **~10%** (reels, saved, hashtag) |
| **Cần viết endpoint mới** | **3-4 endpoint** |
| Cần quyết định kiến trúc | **1** (switch-account) |

**Kết luận**: Chỉ cần viết thêm **3-4 PHP file nhỏ** (~1-2 ngày), sau đó toàn bộ backend đã sẵn sàng cho Nuxt.js.
