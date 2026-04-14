# Spec chi tiết: Trang & Chức năng — Nuxt.js Migration

> **Mục đích**: Danh sách cụ thể từng trang, từng chức năng để build lại bằng Nuxt.js.
> **Tổng**: 46 trang | 233 chức năng | 12 shared components

---

# SHARED COMPONENTS (dùng chung nhiều trang)

Cần build trước vì hầu hết trang đều dùng lại.

---

## SC-1. Header (Thanh điều hướng trên cùng)
**Template gốc:** `header/loggedin-header.phtml` (38.7KB) + `header/notifecation.phtml` (49KB)

| # | Chức năng | API |
|---|---|---|
| 1 | Logo + link về trang chủ | — |
| 2 | Search bar (tìm users, pages, groups, hashtags) | `search.php` |
| 3 | Search gợi ý realtime (autocomplete dropdown) | `search.php` |
| 4 | Icon Home + badge | — |
| 5 | Icon Messages + badge số tin chưa đọc | `get_chats.php` |
| 6 | Dropdown Messages (danh sách chat mới nhất) | `get_chats.php` |
| 7 | Icon Notifications + badge | `notifications.php` |
| 8 | Dropdown Notifications (danh sách thông báo) | `notifications.php` |
| 9 | Icon Group Chat requests | `group_chat.php` |
| 10 | Dropdown Follow Requests | `follow-request-action.php` |
| 11 | User avatar + dropdown menu cá nhân | `get-user-data.php` |
| 12 | Menu: Profile, Settings, Night mode toggle, Logout | — |
| 13 | Night mode toggle (switch dark/light) | `update-user-data.php` |
| 14 | Language switcher | — |
| 15 | "Go Pro" button (nếu chưa pro) | — |

**Tổng: 15 chức năng**

---

## SC-2. Publisher Box (Ô đăng bài)
**Template gốc:** `story/publisher-box.phtml` (112.2KB)
**Dùng ở:** Home, Timeline, Group, Page

| # | Chức năng | API |
|---|---|---|
| 1 | Textarea nhập nội dung bài (auto-resize) | — |
| 2 | Mention bạn bè (@username autocomplete) | `search.php` |
| 3 | Upload ảnh (multi) | `new_post.php` |
| 4 | Upload video | `new_post.php` |
| 5 | Upload custom video thumbnail | `new_post.php` |
| 6 | Record audio (ghi âm) | `new_post.php` |
| 7 | Chọn Feeling/Activity (cảm xúc) | — |
| 8 | Sub-categories: Feeling, Traveling, Watching, Playing, Listening | — |
| 9 | Add Location (Google Maps / Leaflet autocomplete) | — |
| 10 | Tag bạn bè | `get-friends.php` |
| 11 | Tạo Poll (câu hỏi + nhiều đáp án, thêm/xóa đáp án) | `new_post.php` |
| 12 | Tạo Album ảnh | `new_post.php` |
| 13 | Colored post (chọn màu background, text color) | `new_post.php` |
| 14 | GIF search & insert (Giphy) | `new_post.php` |
| 15 | Sticker search & insert | `new_post.php` |
| 16 | Sell Product (inline product form: tên, giá, ảnh, category) | `create-product.php` |
| 17 | AI generate text | `ai.php` |
| 18 | Chọn Privacy (Public / Friends / Only Me) | — |
| 19 | Go Live button (tạo live stream) | `live.php` |
| 20 | Post as Page (nếu đang ở page mình quản lý) | `new_post.php` |
| 21 | Đăng job (LinkedIn mode) | `job.php` |
| 22 | Submit đăng bài | `new_post.php` |

**Tổng: 22 chức năng**

---

## SC-3. Post Component (1 bài viết trong feed)
**Template gốc:** `story/includes/header.phtml` (87.5KB) + `story/includes/footer.phtml` (34.9KB) + `story/includes/post-layout.phtml` (22.5KB)

### Post Header (thao tác của chủ bài)
| # | Chức năng | API |
|---|---|---|
| 1 | Avatar + tên tác giả (link tới profile) | — |
| 2 | Thời gian đăng (relative: "2 giờ trước") | — |
| 3 | Privacy icon (biểu tượng public/friends/only me) | — |
| 4 | Pro badge (hiện badge nếu user pro) | — |
| 5 | Verified badge | — |
| 6 | Edit post (mở editor inline) | `post-actions.php` (edit) |
| 7 | Delete post | `post-actions.php` (delete) |
| 8 | Mark as Sold (nếu post product) | `post-actions.php` |
| 9 | Edit product link | — |
| 10 | Disable/Enable comments | `post-actions.php` |
| 11 | Pin/Unpin post | `post-actions.php` |
| 12 | Boost post (quảng cáo) | `ads.php` |
| 13 | Change post privacy (dropdown: public/friends/only me) | `post-actions.php` |
| 14 | Save/Unsave post | `post-actions.php` (save) |
| 15 | Report post | `report_comment.php` |
| 16 | Hide post | `hide_post.php` |
| 17 | Copy link | — |
| 18 | Translate post | — (client-side Google Translate) |

### Post Body (hiển thị nội dung)
| # | Chức năng | API |
|---|---|---|
| 19 | Text content + "Read more" expand | — |
| 20 | Single photo + lightbox | — |
| 21 | Multi photos grid (2, 3, 4, 5+) + lightbox | — |
| 22 | Video player (Plyr) | — |
| 23 | Audio player | — |
| 24 | YouTube/Vimeo embed | — |
| 25 | Link preview (OG meta: title, description, image) | — |
| 26 | Colored post (background gradient + text) | — |
| 27 | Map embed (location post) | — |
| 28 | File attachment | — |
| 29 | Poll display + vote | `vote_up.php` |
| 30 | Album display (grid) | — |
| 31 | Product card (name, price, image, buy button) | — |
| 32 | Job card (title, location, salary, apply button) | — |
| 33 | Funding card (title, progress bar, donate button) | — |
| 34 | Event card (name, date, location, interested/going) | — |
| 35 | Blog card (title, thumbnail, read more) | — |
| 36 | Offer card | — |
| 37 | Shared post (bài chia sẻ, hiển thị post gốc bên trong) | — |
| 38 | Live comment (live streaming post) | — |

### Post Footer (tương tác)
| # | Chức năng | API |
|---|---|---|
| 39 | Like count | `get-reactions.php` |
| 40 | Comment count | — |
| 41 | Share count | — |
| 42 | Like/Unlike button | `post-actions.php` (like) |
| 43 | Reactions (6 loại: Like, Love, Haha, Wow, Sad, Angry) — hover popup | `post-actions.php` |
| 44 | Wonder/Dislike button | `post-actions.php` (wonder) |
| 45 | Share button → open share modal | — |
| 46 | Comment section toggle | — |

**Tổng: 46 chức năng**

---

## SC-4. Comment Component
**Template gốc:** `comment/content.phtml` (35.1KB) + `comment/replies-content.phtml` (15.8KB)

| # | Chức năng | API |
|---|---|---|
| 1 | Viết comment (text) | `comments.php` |
| 2 | Upload ảnh trong comment | `comments.php` |
| 3 | GIF trong comment | `comments.php` |
| 4 | Sticker trong comment | `comments.php` |
| 5 | Emoji picker trong comment | — |
| 6 | Mention (@username) trong comment | `search.php` |
| 7 | Like comment | `comments.php` (like) |
| 8 | Reactions trên comment (6 loại) | `comments.php` |
| 9 | Reply comment (nested) | `comments.php` |
| 10 | Edit comment | `comments.php` (edit) |
| 11 | Delete comment | `comments.php` (delete) |
| 12 | Report comment | `report_comment.php` |
| 13 | Load more comments | `comments.php` |
| 14 | Sort comments (Top / Latest) | — |
| 15 | Pin comment | `comments.php` |

**Tổng: 15 chức năng**

---

## SC-5. Lightbox & Share Modal
**Template gốc:** `lightbox/content.phtml` (54.2KB) + `lightbox/share_post.phtml` (72.4KB)

| # | Chức năng | API |
|---|---|---|
| 1 | Xem ảnh full-screen | — |
| 2 | Navigate (prev/next) trong album | — |
| 3 | Zoom ảnh | — |
| 4 | Download ảnh | — |
| 5 | Like/React ảnh | `post-actions.php` |
| 6 | Comment trên ảnh | `comments.php` |
| 7 | Share ảnh | — |
| 8 | Share modal: Chia sẻ lên timeline | `new_post.php` |
| 9 | Share modal: Chia sẻ tới group | `new_post.php` |
| 10 | Share modal: Chia sẻ tới page | `new_post.php` |
| 11 | Share modal: Copy link | — |
| 12 | Share modal: Share to external (Facebook, Twitter, WhatsApp, Telegram, Email) | — |

**Tổng: 12 chức năng**

---

## SC-6. Chat Widget (nổi ở mọi trang)
**Template gốc:** `chat/chat-tab.phtml` (43.3KB)

| # | Chức năng | API |
|---|---|---|
| 1 | Online friends list | `is-active.php` |
| 2 | Mở chat window (floating) | — |
| 3 | Gửi tin nhắn text | `send-message.php` |
| 4 | Gửi ảnh | `send-message.php` |
| 5 | Gửi file | `send-message.php` |
| 6 | Gửi sticker | `send-message.php` |
| 7 | Gửi GIF | `send-message.php` |
| 8 | Typing indicator | `set-chat-typing-status.php` |
| 9 | Close/minimize chat | — |
| 10 | Xem nhiều chat cùng lúc | — |

**Tổng: 10 chức năng**

---

# CÁC TRANG CHÍNH

---

## P-01. `/welcome` — Đăng nhập
**Route Nuxt:** `pages/welcome.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Form đăng nhập (email/username + password) | `auth.php` |
| 2 | Remember me checkbox | — (cookie) |
| 3 | Link quên mật khẩu | — |
| 4 | Link đăng ký | — |
| 5 | Đăng nhập bằng Facebook | `social-login.php` |
| 6 | Đăng nhập bằng Google | `social-login.php` |
| 7 | Đăng nhập bằng Twitter | `social-login.php` |
| 8 | Đăng nhập bằng LinkedIn | `social-login.php` |
| 9 | Đăng nhập bằng VKontakte | `social-login.php` |
| 10 | Đăng nhập bằng Instagram | `social-login.php` |
| 11 | Đăng nhập bằng TikTok | `social-login.php` |
| 12 | Hiển thị lỗi validation | — |

**Tổng: 12 chức năng**

---

## P-02. `/register` — Đăng ký
**Route Nuxt:** `pages/register.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Form: First name, Last name | `create-account.php` |
| 2 | Form: Email | `create-account.php` |
| 3 | Form: Password + Confirm password | `create-account.php` |
| 4 | Form: Gender (select) | `create-account.php` |
| 5 | Form: Birthday (date picker) | `create-account.php` |
| 6 | Custom fields (dynamic, từ admin config) | `create-account.php` |
| 7 | ReCaptcha | — |
| 8 | Terms & Conditions checkbox | — |
| 9 | Referral code (nếu có) | `create-account.php` |
| 10 | Submit đăng ký | `create-account.php` |
| 11 | Validation errors hiển thị | — |

**Tổng: 11 chức năng**

---

## P-03. `/forgot-password` — Quên mật khẩu
**Route Nuxt:** `pages/forgot-password.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Nhập email/phone | `send-reset-password-email.php` |
| 2 | Gửi mã xác nhận | `send-reset-password-email.php` |
| 3 | Nhập mã xác nhận | `reset_password.php` |
| 4 | Đặt mật khẩu mới | `reset_password.php` |
| 5 | Xác nhận SMS (nếu dùng phone) | `active_account_sms.php` |

**Tổng: 5 chức năng**

---

## P-04. `/home` — Trang chủ / News Feed
**Route Nuxt:** `pages/index.vue` hoặc `pages/home.vue`
**Components dùng:** SC-2 (Publisher), SC-3 (Post), SC-4 (Comment), SC-6 (Chat Widget)

| # | Chức năng riêng trang này | API |
|---|---|---|
| 1 | Story carousel (danh sách stories bạn bè) | `get-stories.php` |
| 2 | Tạo story mới (link) | — |
| 3 | Filter feed by type (All, Text, Photos, Video, Music) | `posts.php` |
| 4 | Feed ordering (All / People I Follow) | `update-user-data.php` |
| 5 | Greeting message (Good morning/afternoon/evening) | — |
| 6 | Announcement banner (admin announcement) | — |
| 7 | Infinite scroll load more posts | `posts.php` |
| 8 | "New posts" notification button | `posts.php` |
| 9 | Left sidebar: Menu shortcuts (22 items) | — |
| 10 | Right sidebar: Suggested users | `get-user-suggestions.php` |
| 11 | Right sidebar: Suggested pages | — |
| 12 | Right sidebar: Suggested groups | — |
| 13 | Right sidebar: Sponsored posts | `get-promoted-post.php` |
| 14 | Right sidebar: Birthday list | `get_friends_birthday.php` |
| 15 | Right sidebar: Online users | `is-active.php` |
| 16 | Create password modal (nếu social login lần đầu) | — |

**Tổng: 16 chức năng + SC-2 (22) + SC-3 (46) + SC-4 (15) + SC-6 (10) = 109 chức năng tổng**

---

## P-05. `/@{username}` — User Profile / Timeline
**Route Nuxt:** `pages/[username].vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Cover photo (hiển thị) | `get-user-data.php` |
| 2 | Upload/Change cover photo | `update-user-data.php` |
| 3 | Reposition cover photo (drag) | `update-user-data.php` |
| 4 | Avatar (hiển thị) | `get-user-data.php` |
| 5 | Upload/Change avatar + crop | `update-user-data.php` |
| 6 | Reset avatar to default | `reset_avatar.php` |
| 7 | Tên, bio, location, workplace | `get-user-data.php` |
| 8 | Follow / Unfollow button | `follow-user.php` |
| 9 | Add Friend / Unfriend button | `follow-user.php` |
| 10 | Message button (mở chat) | — |
| 11 | Block user | `block-user.php` |
| 12 | Report user | `report_user.php` |
| 13 | Poke user | `poke.php` |
| 14 | Send Gift | `gift.php` |
| 15 | Add to Family | `add-to-family.php` |
| 16 | Tab: Timeline (posts feed) | `posts.php` |
| 17 | Tab: About (thông tin chi tiết) | `get-user-data.php` |
| 18 | Tab: Followers (danh sách) | `get-user-data.php` (followers) |
| 19 | Tab: Following (danh sách) | `get-user-data.php` (following) |
| 20 | Tab: Friends | `get-friends.php` |
| 21 | Tab: Photos (tất cả ảnh) | `get-user-data.php` |
| 22 | Tab: Videos | `get-user-data.php` |
| 23 | Tab: Liked Pages | `get-user-data.php` (liked_pages) |
| 24 | Tab: Joined Groups | `get-user-data.php` (joined_groups) |
| 25 | Tab: Albums | `get-user-albums.php` |
| 26 | Tab: Products (sản phẩm đang bán) | `get-products.php` |
| 27 | Tab: Family members | `get-user-data.php` (family) |
| 28 | Profile completion progress bar | — |
| 29 | Monetization tiers (xem & subscribe) | `monetization-user.php` |
| 30 | Publisher box (nếu profile của mình) | SC-2 |
| 31 | Mutual friends hiển thị | — |

**Tổng: 31 chức năng**

---

## P-06. `/messages` — Tin nhắn (Full page)
**Route Nuxt:** `pages/messages.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Danh sách conversations (chat list) | `get_chats.php` |
| 2 | Search contacts/conversations | `search.php` |
| 3 | Mở conversation | `get_user_messages.php` |
| 4 | Gửi text message | `send-message.php` |
| 5 | Gửi ảnh | `send-message.php` |
| 6 | Gửi video | `send-message.php` |
| 7 | Gửi file đính kèm | `send-message.php` |
| 8 | Gửi audio (record) | `send-message.php` |
| 9 | Gửi sticker | `send-message.php` |
| 10 | Gửi GIF | `send-message.php` |
| 11 | Emoji picker | — |
| 12 | Reply message (quote) | `send-message.php` |
| 13 | Forward message | `forward_message.php` |
| 14 | React to message (emoji) | `react_message.php` |
| 15 | Pin message | `pin_message.php` |
| 16 | Favorite message | `fav_message.php` |
| 17 | Delete message (cho mình / cho tất cả) | `delete_message.php` |
| 18 | Delete conversation | `delete-conversation.php` |
| 19 | Archive conversation | `get_archived_chats.php` |
| 20 | Change chat color | `change-chat-color.php` |
| 21 | Typing indicator | `set-chat-typing-status.php` |
| 22 | Online/offline status | `is-active.php` |
| 23 | Read receipts (seen) | `read_chats.php` |
| 24 | Tab: Group Chat (tạo nhóm, quản lý members) | `group_chat.php` |
| 25 | Tab: Page Chat (chat từ fanpage) | `page_chat.php` |
| 26 | Mute conversation | `mute.php` |
| 27 | Block user từ chat | `block-user.php` |
| 28 | Video/Audio call button | `agora.php` / Live |
| 29 | Search trong conversation | — |
| 30 | Load older messages (scroll up) | `get_user_messages.php` |
| 31 | Pinned chats list | `get_pin_chats.php` |

**Tổng: 31 chức năng**

---

## P-07. `/reels` — Short Videos
**Route Nuxt:** `pages/reels.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Full-screen vertical video feed | `posts.php` (is_reel=only) |
| 2 | Swipe/scroll qua video tiếp theo | `posts.php` |
| 3 | Like / Unlike reel | `post-actions.php` |
| 4 | Comment reel | `comments.php` |
| 5 | Share reel | — |
| 6 | Follow user từ reel | `follow-user.php` |
| 7 | User info overlay (avatar, tên, follow btn) | — |
| 8 | Mute/unmute audio | — |
| 9 | Save reel | `post-actions.php` (save) |
| 10 | View reel by user (`/reels/{id}/{user}`) | `posts.php` |

**Tổng: 10 chức năng**

---

## P-08. `/story-content` — Xem Stories
**Route Nuxt:** `pages/story-content.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Hiển thị story (ảnh/video/text) full screen | `get_story_by_id.php` |
| 2 | Auto-advance (chuyển story tự động) | — |
| 3 | Tap left/right chuyển story | — |
| 4 | Progress bar trên mỗi story | — |
| 5 | React to story | `react_story.php` |
| 6 | Reply to story (text input) | `send-message.php` |
| 7 | Xem story views (người đã xem) | `get_story_views.php` |
| 8 | Mute story của user | `mute_story.php` |
| 9 | Delete story (nếu của mình) | `delete-story.php` |
| 10 | Pause/Resume story | — |

**Tổng: 10 chức năng**

---

## P-09. `/status/create` — Tạo Story
**Route Nuxt:** `pages/status/create.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Upload ảnh story | `create-story.php` |
| 2 | Upload video story | `create-story.php` |
| 3 | Text story (chọn font, background color) | `create-story.php` |
| 4 | Preview trước khi đăng | — |
| 5 | Submit đăng story | `create-story.php` |

**Tổng: 5 chức năng**

---

## P-10. `/products` — Marketplace
**Route Nuxt:** `pages/products/index.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Danh sách sản phẩm (grid cards) | `get-products.php` |
| 2 | Filter theo category | `get-products.php` |
| 3 | Filter theo sub-category | `get-products.php` |
| 4 | Infinite scroll / pagination | `get-products.php` |
| 5 | Link tới trang tạo sản phẩm | — |
| 6 | Link tới "Sản phẩm của tôi" | — |

**Tổng: 6 chức năng**

---

## P-11. `/new-product` — Tạo sản phẩm
**Route Nuxt:** `pages/new-product.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Tên sản phẩm | `create-product.php` |
| 2 | Mô tả | `create-product.php` |
| 3 | Giá | `create-product.php` |
| 4 | Currency (select) | `create-product.php` |
| 5 | Category (select) | `create-product.php` |
| 6 | Sub-category (select, dynamic theo category) | `create-product.php` |
| 7 | Condition (New / Used) | `create-product.php` |
| 8 | Upload ảnh (multi, tối đa ~10) | `create-product.php` |
| 9 | Location (Google Maps autocomplete) | — |
| 10 | Product type | `create-product.php` |
| 11 | Inventory/Stock | `create-product.php` |
| 12 | Submit tạo sản phẩm | `create-product.php` |
| 13 | Validation errors | — |

**Tổng: 13 chức năng**

---

## P-12. `/edit-product/{id}` — Sửa sản phẩm
**Route Nuxt:** `pages/edit-product/[id].vue`
**Tương tự P-11** + thêm:

| # | Chức năng thêm | API |
|---|---|---|
| 1 | Pre-fill dữ liệu cũ | `get-products.php` |
| 2 | Xóa ảnh cũ | `edit-product.php` |
| 3 | Submit cập nhật | `edit-product.php` |

**Tổng: 13 + 3 = 16 chức năng**

---

## P-13. `/my-products` — Sản phẩm của tôi
**Route Nuxt:** `pages/my-products.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Danh sách sản phẩm mình đăng | `market.php` |
| 2 | Link edit từng sản phẩm | — |
| 3 | Xóa sản phẩm | `post-actions.php` |

**Tổng: 3 chức năng**

---

## P-14. `/checkout` — Thanh toán
**Route Nuxt:** `pages/checkout.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Hiển thị sản phẩm đang mua (ảnh, tên, giá) | — |
| 2 | Form địa chỉ giao hàng | `address.php` |
| 3 | Chọn phương thức thanh toán (Stripe) | `stripe.php` |
| 4 | Chọn PayPal | `checkout.php` |
| 5 | Chọn Razorpay | `razorpay.php` |
| 6 | Chọn Paystack | `paystack.php` |
| 7 | Chọn CashFree | `cashfree.php` |
| 8 | Thanh toán bằng Wallet | `wallet.php` |
| 9 | Submit thanh toán | `checkout.php` |
| 10 | Hiển thị lỗi/thành công | — |

**Tổng: 10 chức năng**

---

## P-15. `/orders` — Đơn hàng (người mua)
**Route Nuxt:** `pages/orders.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Danh sách đơn hàng | `market.php` |
| 2 | Trạng thái: Pending / Completed / Cancelled | — |
| 3 | Link xem chi tiết | — |

**Tổng: 3 chức năng**

---

## P-16. `/order/{id}` — Chi tiết đơn hàng
**Route Nuxt:** `pages/order/[id].vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Thông tin sản phẩm | `market.php` |
| 2 | Thông tin người bán | — |
| 3 | Địa chỉ giao hàng | — |
| 4 | Trạng thái đơn | — |
| 5 | Đánh dấu đã nhận hàng | `market.php` |

**Tổng: 5 chức năng**

---

## P-17. `/customer_order/{id}` — Đơn hàng (người bán)
**Route Nuxt:** `pages/customer-order/[id].vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Thông tin đơn hàng khách đặt | `market.php` |
| 2 | Thông tin người mua | — |
| 3 | Cập nhật trạng thái (shipped/completed) | `market.php` |

**Tổng: 3 chức năng**

---

## P-18. `/create-group` — Tạo nhóm
**Route Nuxt:** `pages/create-group.vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Tên nhóm | `create-group.php` |
| 2 | Mô tả | `create-group.php` |
| 3 | Category (select) | `create-group.php` |
| 4 | Privacy (Public / Private / Secret) | `create-group.php` |
| 5 | Custom URL | `create-group.php` |
| 6 | Submit tạo nhóm | `create-group.php` |

**Tổng: 6 chức năng**

---

## P-19. `/g/{group_name}` — Trang nhóm
**Route Nuxt:** `pages/g/[name].vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Cover photo nhóm | `get-group-data.php` |
| 2 | Avatar nhóm | `get-group-data.php` |
| 3 | Tên, mô tả, category, member count | `get-group-data.php` |
| 4 | Join / Leave group button | `join-group.php` |
| 5 | Invite members | `invitation.php` |
| 6 | Members list | `get_group_members.php` |
| 7 | Publisher box (SC-2) | — |
| 8 | Posts feed (SC-3) | `posts.php` (group_id) |
| 9 | About tab | — |
| 10 | Link setting (admin) | — |

**Tổng: 10 chức năng**

---

## P-20. `/group-setting/{group}` — Cài đặt nhóm
**Route Nuxt:** `pages/group-setting/[name].vue`

| # | Chức năng | API |
|---|---|---|
| 1 | General: Tên, mô tả, category | `update-group-data.php` |
| 2 | Privacy: Public/Private/Secret | `update-group-data.php` |
| 3 | Avatar upload | `update-group-data.php` |
| 4 | Cover upload | `update-group-data.php` |
| 5 | Members management (danh sách) | `get_group_members.php` |
| 6 | Kick member | `delete_group_member.php` |
| 7 | Promote/Demote admin | `make_group_admin.php` |
| 8 | Join requests (accept/reject) | `join-group.php` |
| 9 | Privileges settings | `update_privileges.php` |
| 10 | Analytics (views, members growth) | — |
| 11 | Delete group | `delete_group.php` |

**Tổng: 11 chức năng**

---

## P-21. `/create-page` — Tạo trang
**Route Nuxt:** `pages/create-page.vue`

| # | Chức năng | API |
|---|---|---|
| 1-5 | Tương tự create-group (tên, mô tả, category, URL, submit) | `create-page.php` |

**Tổng: 5 chức năng**

---

## P-22. `/p/{page_name}` — Trang fanpage
**Route Nuxt:** `pages/p/[name].vue`

| # | Chức năng | API |
|---|---|---|
| 1 | Cover, avatar, tên, info | `get-page-data.php` |
| 2 | Like / Unlike page | `like-page.php` |
| 3 | Rate/Review page (1-5 sao) | `rate_page.php` |
| 4 | Publisher box (SC-2) | — |
| 5 | Posts feed (SC-3) | `posts.php` (page_id) |
| 6 | About, Members, Reviews tabs | — |
| 7 | Message page (chat) | `page_chat.php` |

**Tổng: 7 chức năng**

---

## P-23. `/page-setting/{page}` — Cài đặt page
**Route Nuxt:** `pages/page-setting/[name].vue`
**Tương tự P-20** nhưng cho page.

| # | Chức năng | API |
|---|---|---|
| 1-8 | General, avatar, cover, admins, verification, analytics, delete | `update-page-data.php` |

**Tổng: 8 chức năng**

---

## P-24. `/blogs` — Danh sách blogs
| # | Chức năng | API |
|---|---|---|
| 1 | Danh sách blog (cards) | `blogs.php` |
| 2 | Pagination | `blogs.php` |

**Tổng: 2 chức năng**

---

## P-25. `/create-blog` — Viết blog
| # | Chức năng | API |
|---|---|---|
| 1 | Title | `blogs.php` |
| 2 | WYSIWYG Editor (rich text content) | `blogs.php` |
| 3 | Category select | `blogs.php` |
| 4 | Tags | `blogs.php` |
| 5 | Thumbnail upload | `blogs.php` |
| 6 | Submit | `blogs.php` |

**Tổng: 6 chức năng**

---

## P-26. `/read-blog/{slug}` — Đọc blog
| # | Chức năng | API |
|---|---|---|
| 1 | Hiển thị nội dung blog (HTML) | `get-blog-by-id.php` |
| 2 | Author info (avatar, tên, link profile) | — |
| 3 | Ngày đăng, view count | — |
| 4 | Like/React blog post | `post-actions.php` |
| 5 | Comments (SC-4) | `comments.php` |
| 6 | Share | — |
| 7 | Related blogs | `blogs.php` |

**Tổng: 7 chức năng**

---

## P-27. `/events` — Sự kiện
| # | Chức năng | API |
|---|---|---|
| 1 | Tab: Upcoming | `get-events.php` |
| 2 | Tab: My Events | `get-events.php` |
| 3 | Tab: Going | `get-events.php` |
| 4 | Tab: Invited | `get-events.php` |
| 5 | Tab: Interested | `get-events.php` |
| 6 | Tab: Past | `get-events.php` |
| 7 | Create event button/link | — |

**Tổng: 7 chức năng**

---

## P-28. `/events/create-event` — Tạo sự kiện
| # | Chức năng | API |
|---|---|---|
| 1 | Tên event | `create-event.php` |
| 2 | Mô tả | `create-event.php` |
| 3 | Location | `create-event.php` |
| 4 | Start date/time | `create-event.php` |
| 5 | End date/time | `create-event.php` |
| 6 | Cover image upload | `create-event.php` |
| 7 | Submit | `create-event.php` |

**Tổng: 7 chức năng**

---

## P-29. `/events/{id}` — Chi tiết sự kiện
| # | Chức năng | API |
|---|---|---|
| 1 | Thông tin event (cover, tên, date, location) | `get_event_by_id.php` |
| 2 | Going button | `go-to-event.php` |
| 3 | Interested button | `interest-event.php` |
| 4 | Not Interested | `go-to-event.php` |
| 5 | Invite friends | `invitation.php` |
| 6 | Danh sách attendees | — |
| 7 | Edit / Delete (owner) | `events.php` |

**Tổng: 7 chức năng**

---

## P-30. `/jobs` — Việc làm
| # | Chức năng | API |
|---|---|---|
| 1 | Danh sách jobs | `job.php` |
| 2 | Filter theo category | `job.php` |
| 3 | Filter theo location | `job.php` |
| 4 | Apply job (form: tên, email, phone, mô tả, CV upload) | `job.php` |
| 5 | Đăng job mới (employer) | `job.php` |
| 6 | Chi tiết job (title, salary, description, location) | `job.php` |

**Tổng: 6 chức năng**

---

## P-31. `/funding` + `/create_funding` + `/show_fund/{id}` — Crowdfunding
| # | Chức năng | API |
|---|---|---|
| 1 | Danh sách funding campaigns | `funding.php` |
| 2 | Tạo funding (title, description, goal amount, image) | `funding.php` |
| 3 | Xem chi tiết (progress bar, mô tả) | `funding.php` |
| 4 | Donate (chọn amount + payment) | `funding.php` / `wallet.php` |
| 5 | Donors list | `funding.php` |
| 6 | Edit/Delete funding | `funding.php` |

**Tổng: 6 chức năng**

---

## P-32. `/live` — Live Streaming
| # | Chức năng | API |
|---|---|---|
| 1 | Tạo live stream (Agora/LiveKit/Millicast) | `live.php` |
| 2 | Live video player | — |
| 3 | Live chat (realtime comments) | `live.php` |
| 4 | Viewer count | `live.php` |
| 5 | Reactions realtime | — |
| 6 | End live button | `live.php` |
| 7 | Join live stream (viewer) | `live.php` |

**Tổng: 7 chức năng**

---

## P-33. `/watch` — Xem Video
| # | Chức năng | API |
|---|---|---|
| 1 | Video player (Plyr) | `posts.php` |
| 2 | Video info (title, views, date) | — |
| 3 | Comments (SC-4) | `comments.php` |
| 4 | Related videos | `posts.php` |
| 5 | Like / Share | `post-actions.php` |

**Tổng: 5 chức năng**

---

## P-34. `/setting` — Cài đặt (22 sub-pages)
**Route Nuxt:** `pages/setting/index.vue` + `pages/setting/[page].vue`

### General (15.7KB)
| 1 | Username | 2 | Email | 3 | Phone | 4 | Birthday | 5 | Gender | 6 | Country | 7 | Website |

### Profile (17.6KB)
| 8 | Bio | 9 | Workplace | 10 | School | 11 | Relationship | 12 | City/Hometown |

### Privacy (13.7KB)
| 13 | Who sees posts | 14 | Who can follow | 15 | Search visibility | 16 | Who sees friends | 17 | Who sees birthday | 18 | Status online/offline |

### Avatar (4.7KB)
| 19 | Upload & crop avatar |

### Design (6.6KB)
| 20 | Night mode | 21 | Theme color |

### Password (7.6KB)
| 22 | Current password | 23 | New password | 24 | Confirm password |

### Two-Factor (21.2KB)
| 25 | Enable/disable 2FA | 26 | QR code authenticator | 27 | Backup codes |

### Notifications (14.4KB)
| 28 | Toggle per type (likes, comments, follows, messages, mentions...) — ~15 toggles |

### Email Notifications (9KB)
| 29 | Toggle email notifications per type |

### Social Links (4.4KB)
| 30 | Facebook URL | 31 | Twitter URL | 32 | LinkedIn URL | 33 | Instagram URL | 34 | YouTube URL |

### Blocked Users (1.4KB)
| 35 | Danh sách blocked | 36 | Unblock button |

### Sessions (2.5KB)
| 37 | Active sessions list | 38 | Terminate session |

### Verification (16.8KB)
| 39 | Upload documents | 40 | Submit verification request |

### Delete Account (2KB)
| 41 | Password confirm | 42 | Delete account button |

### Payments (11.1KB)
| 43 | Payment methods management |

### Monetization (3.4KB)
| 44 | Create monetization plan | 45 | Set price | 46 | Enable/disable |

### Invitation Links (6.5KB)
| 47 | Generate invite link | 48 | Copy link | 49 | View invitees |

### Affiliates (10.4KB)
| 50 | Referral link | 51 | Earnings dashboard |

### My Info (10.4KB)
| 52 | Download personal data |

### My Points (6.6KB)
| 53 | Points balance | 54 | Points history |

### Addresses (3.6KB)
| 55 | Add/edit shipping addresses |

### Experience + Certifications + Projects (LinkedIn mode)
| 56 | Add work experience | 57 | Add certifications | 58 | Add projects |

**Tổng: 58 chức năng**

---

## P-35 → P-46. Các trang phụ (đơn giản)

| # | Trang | Route Nuxt | Chức năng | Số CN |
|---|---|---|---|---|
| P-35 | `/search` | `pages/search.vue` | Tìm kiếm users/pages/groups/posts, filter by type | 3 |
| P-36 | `/hashtag/{tag}` | `pages/hashtag/[tag].vue` | Hiển thị posts chứa hashtag | 2 |
| P-37 | `/explore` | `pages/explore.vue` | Recommended posts/users/pages | 3 |
| P-38 | `/saved-posts` | `pages/saved-posts.vue` | Danh sách bài đã save, unsave button | 2 |
| P-39 | `/poke` | `pages/poke.vue` | Danh sách poke, poke lại button | 2 |
| P-40 | `/memories` | `pages/memories.vue` | "Ngày này năm trước" posts, share lại | 2 |
| P-41 | `/games` | `pages/games.vue` | List games (my/new/popular tabs), play game | 4 |
| P-42 | `/go-pro` | `pages/go-pro.vue` | So sánh packages, chọn plan, thanh toán | 5 |
| P-43 | `/forum` | `pages/forum/index.vue` | Sections list, threads, post reply, search | 8 |
| P-44 | `/directory` | `pages/directory/index.vue` | 12 sub-categories (users/pages/groups/market/events...) | 3 |
| P-45 | `/wallet` | `pages/wallet.vue` | Xem số dư, nạp tiền, gửi tiền | 3 |
| P-46 | `/withdrawal` | `pages/withdrawal.vue` | Yêu cầu rút tiền, lịch sử, payment info | 4 |

**Tổng trang phụ: 41 chức năng**

---

# 📊 TỔNG KẾT CUỐI

| Hạng mục | Số lượng |
|---|---|
| **Shared Components** | 6 components, **120 chức năng** |
| **Trang chính (P-01 → P-34)** | 34 trang, **~272 chức năng** (bao gồm shared) |
| **Trang phụ (P-35 → P-46)** | 12 trang, **41 chức năng** |
| **TỔNG CỘNG** | **46 trang, 233 chức năng unique** |

> **Lưu ý**: Shared Components (SC-1 đến SC-6) được tính 1 lần dù dùng ở nhiều trang.
> Trên thực tế trang Home có ~109 chức năng vì dùng nhiều SC.
