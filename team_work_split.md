# Phan chia cong viec migration Nuxt de tranh conflict

## Muc tieu

Tai lieu nay dung de chia viec cho team khi migration sang Nuxt theo huong:

- tach theo domain thay vi tach theo tung page roi rac
- xac dinh phan nao dung chung, phan nao doc lap
- giam conflict code, conflict API contract, conflict state management
- cho phep nhieu team lam song song nhung van ghep lai duoc

Tai lieu goc tham chieu: [page_feature_audit.md](./page_feature_audit.md)

---

## Nguyen tac chia viec

Khong chia theo tung route mot cach may moc, vi rat nhieu page dung chung:

- Header
- Publisher Box
- Post Component
- Comment Component
- Lightbox / Share Modal
- Chat / Notifications / badge realtime

Neu chia mot nguoi lam `/home`, mot nguoi lam `/profile`, mot nguoi lam `/group`, mot nguoi lam `/page` thi se dung nhau o:

- component feed
- store user hien tai
- store notifications / messages
- schema post / comment / media
- logic permission / privacy / reaction / share

Vi vay can chia theo domain co owner ro rang.

---

## Ban do phu thuoc tong quan

### Tang 1: Nen tang bat buoc phai co som

1. Auth, session, current user, route guard
2. Global layout, app shell, header
3. Shared data contracts cho post, comment, media, notification, message

### Tang 2: Shared components dung lai nhieu noi

1. Publisher Box
2. Post Component
3. Comment Component
4. Lightbox / Share Modal
5. Chat Widget

### Tang 3: Domain pages

1. Feed pages: home, profile timeline, page feed, group feed
2. Messaging pages
3. Community pages: groups, pages, events
4. Commerce pages: products, checkout, orders, wallet
5. Content extensions: stories, reels, live, blogs, jobs, forum

### Tang 4: Trang phu

1. search
2. hashtag
3. explore
4. saved-posts
5. memories
6. directory
7. cac trang tong hop khac

---

## Cac nhom cong viec de chia cho team

## Team 1: Foundation

### So huu

- auth / register / forgot password
- session va current-user store
- route middleware / guard
- app layout
- theme mode, language, global preferences
- `SC-1 Header`

### Route / man hinh phu trach

- `/welcome`
- `/register`
- `/forgot-password`
- phan khung dung chung cua app

### Component / module nen so huu

- `layouts/*`
- `middleware/*`
- `stores/auth.*`
- `stores/app.*`
- `stores/user.*`
- `components/header/*`
- `composables/useAuth*`
- `composables/useCurrentUser*`

### Phu thuoc

- can backend auth on dinh
- can quy uoc user/session schema som

### Team khac se phu thuoc vao Team 1

- tat ca team con lai

### Khong nen dung vao

- logic post / comment / chat body
- contract checkout / payment

---

## Team 2: Feed Core

### So huu

- `SC-2 Publisher Box`
- `SC-3 Post Component`
- `SC-4 Comment Component`
- `SC-5 Lightbox & Share Modal`
- shared contracts cho post/comment/reaction/share/media

### Route / man hinh phu trach

- phan feed trong `/home`
- timeline feed trong profile
- feed trong group/page
- saved posts, hashtag, explore, memories
- phan comment trong blog/watch neu co tai su dung

### Component / module nen so huu

- `components/feed/*`
- `components/post/*`
- `components/comment/*`
- `components/share/*`
- `components/lightbox/*`
- `stores/feed.*`
- `stores/post.*`
- `stores/comment.*`
- `composables/usePost*`
- `composables/useComment*`

### Phu thuoc

- current user tu Team 1
- can schema post/comment/media duoc chot truoc khi team khac tich hop

### Team khac se phu thuoc vao Team 2

- Team 4 Identity & Social Graph
- Team 5 Community
- Team 7 Content Extensions

### Khong nen dung vao

- chat realtime
- logic settings profile
- payment flow

---

## Team 3: Messaging & Realtime

### So huu

- `SC-6 Chat Widget`
- page `/messages`
- header message dropdown va notification badge realtime
- online presence
- typing, seen, unread count
- group chat, page chat, story reply qua message

### Route / man hinh phu trach

- `/messages`
- cac floating chat windows
- dropdown messages, dropdown notifications trong header

### Component / module nen so huu

- `components/chat/*`
- `components/notifications/*`
- `stores/chat.*`
- `stores/notifications.*`
- `stores/presence.*`
- `composables/useChat*`
- `composables/useNotifications*`
- `plugins/socket*` hoac `services/realtime/*`

### Phu thuoc

- current user tu Team 1
- event bus / realtime transport phai chot som

### Team khac se phu thuoc vao Team 3

- Team 1 cho badge header
- Team 7 cho story reply, live comment neu dung chung kenh

### Khong nen dung vao

- post/comment feed
- settings profile

---

## Team 4: Identity & Social Graph

### So huu

- profile user
- settings
- follow / unfollow
- add friend / unfriend
- block / report / poke / family / gifts
- profile media update
- privacy settings
- monetization user

### Route / man hinh phu trach

- `/@{username}`
- `/setting`
- `/poke`
- cac modal hoac panel lien quan profile relationship

### Component / module nen so huu

- `components/profile/*`
- `components/settings/*`
- `components/relationship/*`
- `stores/profile.*`
- `stores/settings.*`
- `stores/relationship.*`
- `composables/useProfile*`

### Phu thuoc

- app shell tu Team 1
- feed timeline nhung component tu Team 2

### Khong nen dung vao

- implementation ben trong Publisher / Post / Comment
- messaging store

---

## Team 5: Community

### So huu

- groups
- pages
- events
- create/edit/settings/admin/member management

### Route / man hinh phu trach

- `/create-group`
- `/g/{group_name}`
- `/group-setting/{group}`
- `/create-page`
- `/p/{page_name}`
- `/page-setting/{page}`
- `/events`
- `/events/create-event`
- `/events/{id}`

### Component / module nen so huu

- `components/group/*`
- `components/page/*`
- `components/event/*`
- `stores/group.*`
- `stores/page.*`
- `stores/event.*`
- `composables/useGroup*`
- `composables/usePage*`
- `composables/useEvent*`

### Phu thuoc

- Team 2 cung cap feed embed de dung lai
- Team 1 cung cap current user + permission co ban

### Luu y quan trong

- group/page/event chi nen compose lai feed component
- khong copy fork `Publisher`, `Post`, `Comment`

---

## Team 6: Commerce & Payments

### So huu

- marketplace
- create/edit product
- checkout
- orders
- wallet
- withdrawal
- go-pro
- funding

### Route / man hinh phu trach

- `/products`
- `/new-product`
- `/edit-product/{id}`
- `/my-products`
- `/checkout`
- `/orders`
- `/order/{id}`
- `/customer_order/{id}`
- `/wallet`
- `/withdrawal`
- `/go-pro`
- `/funding`
- `/create_funding`
- `/show_fund/{id}`

### Component / module nen so huu

- `components/market/*`
- `components/checkout/*`
- `components/wallet/*`
- `components/funding/*`
- `stores/product.*`
- `stores/checkout.*`
- `stores/wallet.*`
- `stores/payment.*`
- `composables/useCheckout*`
- `services/payment/*`

### Phu thuoc

- current user tu Team 1
- neu co product card trong post thi can contract voi Team 2

### Luu y quan trong

- abstraction payment gateway phai dat o mot tang chung
- khong de moi page tu goi Stripe / PayPal / Wallet theo kieu rieng

---

## Team 7: Content Extensions

### So huu

- stories
- reels
- live streaming
- blogs
- jobs
- forum
- games
- directory
- watch

### Route / man hinh phu trach

- `/story-content`
- `/status/create`
- `/reels`
- `/live`
- `/blogs`
- `/create-blog`
- `/read-blog/{slug}`
- `/jobs`
- `/forum`
- `/games`
- `/directory`
- `/watch`

### Component / module nen so huu

- `components/story/*`
- `components/reels/*`
- `components/live/*`
- `components/blog/*`
- `components/job/*`
- `components/forum/*`
- `components/watch/*`
- `stores/story.*`
- `stores/reels.*`
- `stores/live.*`

### Phu thuoc

- Team 2 cho reaction/comment/share/post rendering
- Team 3 neu co realtime message/live comment
- Team 1 cho auth/layout

### Luu y quan trong

- uu tien tai su dung reaction/comment/share contracts san co
- khong phat sinh them mot he post/comment rieng cho stories/blog/watch

---

## Ma tran phu thuoc giua cac team

| Team | Phu thuoc chinh | Team phu thuoc vao no |
|---|---|---|
| Team 1 Foundation | Backend auth, user/session schema | Tat ca |
| Team 2 Feed Core | Team 1 | Team 4, 5, 7 |
| Team 3 Messaging & Realtime | Team 1 | Team 1, 7 |
| Team 4 Identity & Social Graph | Team 1, 2 | it team khac phu thuoc truc tiep |
| Team 5 Community | Team 1, 2 | it team khac phu thuoc truc tiep |
| Team 6 Commerce & Payments | Team 1, mot phan Team 2 | Team 7 neu co funding/product embed |
| Team 7 Content Extensions | Team 1, 2, 3 | it team khac phu thuoc truc tiep |

---

## Nhung diem de conflict nhat can khoa som

## 1. User session va current user

Can chot:

- cach lay current user
- cach refresh token / session
- global auth guard
- shape cua user object

Neu khong chot som, Team 1, 2, 3, 4, 5, 6, 7 deu dung nhau.

## 2. Post schema

Can chot:

- post co nhung loai nao
- attachments media theo format nao
- counts, reactions, permissions, privacy
- product/job/event/blog/funding card duoc render theo field nao

Day la contract song con cho Team 2, 5, 6, 7.

## 3. Comment schema

Can chot:

- nesting
- pagination
- sort order
- pin / reaction / mention

## 4. Realtime event contract

Can chot:

- message new
- typing
- seen
- unread badge
- notification new

Day la contract song con cho Team 1 va Team 3.

## 5. Upload va media pipeline

Can chot:

- image/video/audio/file upload flow
- validation
- upload response shape
- caching / preview / progress

Day la diem chung cua Team 2, 4, 6, 7.

## 6. Search va autocomplete

Can chot:

- global search
- mention search
- conversation search
- product / group / user search

Neu khong tach service ro, rat de moi team tu lam mot kieu.

## 7. Payment abstraction

Can chot:

- payment method list
- request / response format cho checkout
- wallet recharge / spend
- error handling chung

Team 6 la owner, nhung Team 7 co the can tai su dung trong funding hoac go-pro.

---

## Thu tu trien khai de co the lam song song

## Phase 1: Chot nen tang

1. Team 1 chot auth, session, layout, header shell
2. Team 2 chot schema post/comment/media + skeleton feed components
3. Team 3 chot realtime transport, unread counts, chat store

### Dau ra cua Phase 1

- current-user store dung chung
- route guard
- app shell + header
- interfaces cho post/comment/message/notification
- convention folder, composable, store

## Phase 2: Build shared components

1. Team 2 hoan thien Publisher / Post / Comment / Share / Lightbox
2. Team 3 hoan thien Chat Widget + Messages foundation

### Dau ra cua Phase 2

- co the render duoc feed tren home/profile/group/page
- co the chat, hien badge, hien notifications

## Phase 3: Domain pages

1. Team 4 build profile + settings
2. Team 5 build group/page/event
3. Team 6 build commerce
4. Team 7 build stories/reels/live/blogs/jobs/forum/watch

## Phase 4: Trang phu va hardening

1. search
2. hashtag
3. explore
4. saved-posts
5. memories
6. directory
7. testing tich hop, cleanup, perf

---

## Cach chia branch de giam conflict

Khuyen nghi:

- 1 team = 1 nhanh domain chinh
- cac shared package/component co owner ro
- page team khong sua component team khac so huu neu khong co PR review cheo

### Vi du branch

- `feature/foundation-auth-layout`
- `feature/feed-core`
- `feature/messaging-realtime`
- `feature/profile-settings`
- `feature/community`
- `feature/commerce-payments`
- `feature/content-extensions`

### Rule review

- file trong `components/feed/*` phai do owner Team 2 review
- file trong `components/chat/*` hoac `stores/notifications.*` phai do owner Team 3 review
- file trong `services/payment/*` phai do owner Team 6 review
- file trong `stores/auth.*` phai do owner Team 1 review

---

## Ownership de xuat theo khu vuc code

| Khu vuc | Owner |
|---|---|
| `layouts/*`, `middleware/*`, `stores/auth.*`, `stores/app.*` | Team 1 |
| `components/header/*`, `components/layout/*` | Team 1 |
| `components/feed/*`, `components/post/*`, `components/comment/*`, `components/lightbox/*` | Team 2 |
| `stores/feed.*`, `stores/post.*`, `stores/comment.*` | Team 2 |
| `components/chat/*`, `components/notifications/*`, `stores/chat.*`, `stores/notifications.*` | Team 3 |
| `components/profile/*`, `components/settings/*`, `stores/profile.*`, `stores/settings.*` | Team 4 |
| `components/group/*`, `components/page/*`, `components/event/*` | Team 5 |
| `components/market/*`, `components/checkout/*`, `components/wallet/*`, `services/payment/*` | Team 6 |
| `components/story/*`, `components/reels/*`, `components/live/*`, `components/blog/*`, `components/job/*`, `components/forum/*` | Team 7 |

---

## Goi y chia nguoi neu team nho

Neu chi co 3 nguoi:

1. Nguoi A: Team 1 + Team 3
2. Nguoi B: Team 2
3. Nguoi C: Team 4 + Team 5 + Team 6 + Team 7, nhung phai consume component san co

Neu co 4 nguoi:

1. Nguoi A: Foundation + Header
2. Nguoi B: Feed Core
3. Nguoi C: Messaging + Realtime
4. Nguoi D: Tat ca domain pages con lai, lam theo thu tu uu tien

Neu co 5-7 nguoi:

- chia dung theo 7 team o tren la hop ly nhat

---

## Uu tien business de lam truoc

Neu can ra MVP som, uu tien:

1. Team 1 Foundation
2. Team 2 Feed Core
3. Team 4 Profile co ban
4. Team 3 Messaging co ban
5. Team 5 Group/Page co ban
6. Team 6 Marketplace co ban
7. Team 7 Stories/Reels/Live sau

### MVP social co ban gom

- login/register
- header
- home feed
- publisher
- post/comment/reaction/share
- profile
- messages co ban
- group/page co ban

---

## Ket luan

Muon tranh conflict thi can coi:

- `Foundation`
- `Feed Core`
- `Messaging & Realtime`

la 3 khoi trung tam, phai co owner ro rang va chot contract som.

Con lai cac page domain nhu profile, group, page, marketplace, stories, blogs, jobs nen dung lai cac khoi nay, khong tu tao bien the rieng.

Neu can su dung tai lieu nay de giao viec thuc te cho tung nguoi, buoc tiep theo nen tao them:

- bang backlog theo team
- danh sach file/folder du kien moi team se tao
- danh sach API endpoint moi team so huu
- checklist dependency truoc khi start
