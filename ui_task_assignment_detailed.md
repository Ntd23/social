# Bảng phân công chi tiết UI cho team

## Mục tiêu

Tài liệu này dùng để giao việc trực tiếp cho người làm UI trong giai đoạn đầu của migration sang Nuxt.

Phạm vi của tài liệu:

- chỉ tập trung vào UI
- dùng mock data
- chưa cần xử lý nghiệp vụ
- chưa cần call API
- ưu tiên làm component dùng chung trước, lắp page sau

Tài liệu liên quan:

- [page_feature_audit.md](./page_feature_audit.md)
- [team_work_split.md](./team_work_split.md)
- [ui_priority_checklist.md](./ui_priority_checklist.md)

---

## Cách dùng tài liệu này

Mỗi mục công việc có:

- `Owner`: người hoặc nhóm phụ trách
- `Task`: việc cần làm
- `Deliverables`: đầu ra mong muốn
- `Pages dùng`: những trang sẽ dùng lại UI này
- `Phụ thuộc`: cần có gì trước khi bắt đầu
- `Ưu tiên`: mức độ quan trọng
- `Có thể làm song song`: có hay không
- `Ghi chú`: giới hạn hoặc lưu ý để tránh conflict

---

## Quy ước vai trò

Trong tài liệu này tạm chia 5 vai trò UI để dễ giao việc:

- `UI-1`: Foundation UI
- `UI-2`: Feed Core UI
- `UI-3`: Messaging UI
- `UI-4`: Identity + Community UI
- `UI-5`: Commerce + Content UI

Nếu team ít người thì có thể gộp vai trò lại, nhưng vẫn nên giữ ownership theo cụm để tránh đụng nhau.

---

## Giai đoạn 1: Foundation UI

### 1. Thiết lập app shell

- `Owner`: UI-1
- `Task`: Làm bộ khung layout chung cho toàn ứng dụng
- `Deliverables`:
  - `MainLayout`
  - `GuestLayout`
  - `ResponsiveContainer`
  - `PageSection`
  - `PageHeader`
- `Pages dùng`:
  - toàn bộ trang guest
  - toàn bộ trang sau đăng nhập
- `Phụ thuộc`: không
- `Ưu tiên`: P0
- `Có thể làm song song`: có
- `Ghi chú`: phải chốt spacing, grid, max-width, cách chia 1 cột, 2 cột, 3 cột ngay từ đầu

### 2. Header và điều hướng toàn cục

- `Owner`: UI-1
- `Task`: Làm header và các khối điều hướng dùng chung
- `Deliverables`:
  - `Header`
  - `HeaderLogo`
  - `HeaderSearchInput`
  - `HeaderIconNav`
  - `HeaderBadgePlaceholder`
  - `HeaderUserMenu`
  - `LeftSidebar`
  - `SidebarMenuItem`
  - `RightSidebar`
  - `WidgetCard`
- `Pages dùng`:
  - `/home`
  - `/@username`
  - `/messages`
  - `/search`
  - `/explore`
  - group/page/event/product pages
- `Phụ thuộc`: layout chung
- `Ưu tiên`: P0
- `Có thể làm song song`: có
- `Ghi chú`: chỉ làm UI shell, badge để placeholder, dropdown dùng dữ liệu giả

### 3. Primitive dùng chung

- `Owner`: UI-1
- `Task`: Làm bộ component cơ bản để các nhóm khác dùng lại
- `Deliverables`:
  - `TextInput`
  - `TextareaAutoResize`
  - `SelectBox`
  - `Checkbox`
  - `RadioGroup`
  - `ToggleSwitch`
  - `DatePickerUI`
  - `SearchInput`
  - `TagInput`
  - `PasswordInput`
  - `ModalShell`
  - `DrawerShell`
  - `DropdownMenu`
  - `EmptyState`
  - `LoadingSkeleton`
  - `FormSection`
  - `SubmitBar`
- `Pages dùng`: gần như toàn bộ hệ thống
- `Phụ thuộc`: không
- `Ưu tiên`: P0
- `Có thể làm song song`: có
- `Ghi chú`: phải có state cơ bản như default, hover, focus, disabled, error, empty

### 4. Uploader UI dùng chung

- `Owner`: UI-1
- `Task`: Làm lớp giao diện upload dùng lại cho post, profile, product, story, blog
- `Deliverables`:
  - `Uploader`
  - `MediaPreviewList`
  - `ImagePreviewCard`
  - `VideoPreviewCard`
  - `FilePreviewCard`
- `Pages dùng`:
  - publisher
  - profile avatar/cover
  - story create
  - product form
  - blog create
- `Phụ thuộc`: primitive dùng chung
- `Ưu tiên`: P0
- `Có thể làm song song`: có
- `Ghi chú`: chưa cần upload thật, chỉ cần trạng thái chọn file và preview mock

---

## Giai đoạn 2: Feed Core UI

### 5. Publisher Box

- `Owner`: UI-2
- `Task`: Làm giao diện ô đăng bài dùng chung cho home, profile, group, page
- `Deliverables`:
  - `PublisherBox`
  - `PublisherHeader`
  - `PublisherTextarea`
  - `PublisherToolbar`
  - `AudienceSelector`
  - `PublisherMediaPickerUI`
  - `PublisherPollEditorUI`
  - `PublisherFeelingPickerUI`
  - `PublisherLocationInputUI`
  - `PublisherProductInlineUI`
  - `PublisherFooterActions`
- `Pages dùng`:
  - `/home`
  - `/@username`
  - `/g/{group_name}`
  - `/p/{page_name}`
- `Phụ thuộc`:
  - `TextInput`
  - `TextareaAutoResize`
  - `Uploader`
  - `DropdownMenu`
  - `ModalShell`
- `Ưu tiên`: P0
- `Có thể làm song song`: có
- `Ghi chú`: chưa cần mention thật, poll add/remove chỉ cần mock interaction cơ bản

### 6. Post UI dùng chung

- `Owner`: UI-2
- `Task`: Làm giao diện post card và các biến thể nội dung trong feed
- `Deliverables`:
  - `PostCard`
  - `PostHeader`
  - `PostAuthorMeta`
  - `PostPrivacyBadge`
  - `PostMenuUI`
  - `PostBody`
  - `PostTextBlock`
  - `PostMediaGrid`
  - `PostVideoBlock`
  - `PostAudioBlock`
  - `PostLinkPreview`
  - `PostPollBlock`
  - `PostAlbumBlock`
  - `PostProductCard`
  - `PostJobCard`
  - `PostEventCard`
  - `PostSharedCard`
  - `PostFooter`
  - `PostStatsRow`
  - `ReactionBar`
  - `ReactionPicker`
- `Pages dùng`:
  - `/home`
  - `/@username`
  - `/g/{group_name}`
  - `/p/{page_name}`
  - `/saved-posts`
  - `/hashtag/{tag}`
  - `/explore`
  - `/watch`
- `Phụ thuộc`:
  - layout
  - dropdown
  - modal
- `Ưu tiên`: P0
- `Có thể làm song song`: có
- `Ghi chú`: ưu tiên làm 1 `PostCard` thống nhất, không tách post home và post profile thành 2 kiểu riêng

### 7. Comment UI dùng chung

- `Owner`: UI-2
- `Task`: Làm giao diện comment và reply thread
- `Deliverables`:
  - `CommentComposer`
  - `CommentList`
  - `CommentItem`
  - `CommentActions`
  - `ReplyThread`
  - `CommentSortBar`
  - `LoadMoreCommentsButton`
- `Pages dùng`:
  - post feed
  - blog detail
  - watch
  - lightbox
- `Phụ thuộc`:
  - primitive form
  - uploader
- `Ưu tiên`: P0
- `Có thể làm song song`: có
- `Ghi chú`: chưa cần nested logic đầy đủ, nhưng phải render được comment cha và reply

### 8. Share modal và lightbox

- `Owner`: UI-2
- `Task`: Làm các lớp UI phục vụ xem media và chia sẻ
- `Deliverables`:
  - `ShareModal`
  - `ShareTargetListUI`
  - `LightboxViewer`
  - `LightboxMediaNav`
  - `LightboxCommentPanel`
- `Pages dùng`:
  - post feed
  - albums
  - watch
  - blog share
- `Phụ thuộc`:
  - modal shell
  - comment UI
- `Ưu tiên`: P1
- `Có thể làm song song`: có
- `Ghi chú`: chỉ cần UI chuyển ảnh và panel phải, chưa cần logic gallery thật

---

## Giai đoạn 3: 3 trang mẫu để test bộ component

### 9. Trang `/home`

- `Owner`: UI-1 + UI-2
- `Task`: Lắp trang home từ các component đã có
- `Deliverables`:
  - khung 3 cột
  - story carousel placeholder
  - publisher
  - feed cards
  - right widgets
- `Pages dùng`: `/home`
- `Phụ thuộc`:
  - layout
  - header/sidebar
  - publisher/post/comment
- `Ưu tiên`: P0
- `Có thể làm song song`: không hoàn toàn
- `Ghi chú`: đây là trang kiểm tra quan trọng nhất, nếu ráp còn cấn thì phải sửa component gốc chứ không vá riêng cho home

### 10. Trang `/@username`

- `Owner`: UI-2 + UI-4
- `Task`: Lắp trang profile từ component dùng chung và component profile riêng
- `Deliverables`:
  - `ProfileHero`
  - `ProfileIntroCard`
  - `ProfileTabs`
  - timeline feed
- `Pages dùng`: `/@username`
- `Phụ thuộc`:
  - layout chung
  - feed core
- `Ưu tiên`: P0
- `Có thể làm song song`: có
- `Ghi chú`: timeline phải dùng lại `PublisherBox` và `PostCard` đã có

### 11. Trang `/messages`

- `Owner`: UI-3
- `Task`: Làm bộ UI nhắn tin full page
- `Deliverables`:
  - `MessagesLayout`
  - `ConversationList`
  - `MessagePane`
  - `MessageComposer`
  - `ConversationInfoPanel`
- `Pages dùng`: `/messages`
- `Phụ thuộc`:
  - layout
  - primitive
- `Ưu tiên`: P0
- `Có thể làm song song`: có
- `Ghi chú`: chưa cần realtime thật, nhưng bố cục phải chịu được dữ liệu dài và nhiều trạng thái

---

## Giai đoạn 4: Identity UI

### 12. Profile hero và thông tin người dùng

- `Owner`: UI-4
- `Task`: Làm cụm UI đầu trang profile
- `Deliverables`:
  - `ProfileHero`
  - `ProfileCover`
  - `ProfileAvatar`
  - `ProfileMeta`
  - `ProfileActionBar`
  - `MutualFriendsBlock`
- `Pages dùng`:
  - `/@username`
- `Phụ thuộc`:
  - layout
  - tabs
- `Ưu tiên`: P1
- `Có thể làm song song`: có
- `Ghi chú`: phải tính cả profile của mình và profile của người khác

### 13. Các tab profile

- `Owner`: UI-4
- `Task`: Làm UI cho các tab trong profile
- `Deliverables`:
  - `FriendsGrid`
  - `FollowersList`
  - `FollowingList`
  - `PhotosGrid`
  - `VideosGrid`
  - `AlbumsGrid`
  - `ProductsGridMini`
- `Pages dùng`:
  - `/@username`
- `Phụ thuộc`:
  - `ProfileHero`
  - `TabsBar`
- `Ưu tiên`: P1
- `Có thể làm song song`: có
- `Ghi chú`: thống nhất style card/list, không để mỗi tab một kiểu spacing khác nhau

### 14. Settings layout và navigation

- `Owner`: UI-4
- `Task`: Làm khung chung cho khu vực cài đặt
- `Deliverables`:
  - `SettingsLayout`
  - `SettingsNav`
  - `SettingsSection`
- `Pages dùng`:
  - `/setting`
- `Phụ thuộc`:
  - layout
  - primitive form
- `Ưu tiên`: P1
- `Có thể làm song song`: có
- `Ghi chú`: mục tiêu là 1 layout dùng cho toàn bộ các màn settings con

### 15. Settings forms

- `Owner`: UI-4
- `Task`: Làm UI cho các nhóm setting chính
- `Deliverables`:
  - `GeneralSettingsFormUI`
  - `ProfileSettingsFormUI`
  - `PrivacySettingsUI`
  - `AvatarSettingsUI`
  - `DesignSettingsUI`
  - `PasswordSettingsUI`
  - `TwoFactorSettingsUI`
  - `NotificationSettingsUI`
  - `EmailSettingsUI`
  - `SocialLinksSettingsUI`
  - `BlockedUsersListUI`
  - `SessionsListUI`
  - `VerificationUploadUI`
  - `DeleteAccountUI`
  - `AddressesUI`
  - `MonetizationSettingsUI`
- `Pages dùng`:
  - `/setting`
- `Phụ thuộc`:
  - settings layout
  - primitive form
  - uploader
- `Ưu tiên`: P1
- `Có thể làm song song`: có
- `Ghi chú`: không cần làm hết mọi micro-interaction, nhưng phải chia section rõ để sau này gắn data dễ

---

## Giai đoạn 5: Messaging UI

### 16. Danh sách hội thoại và panel tin nhắn

- `Owner`: UI-3
- `Task`: Làm đầy đủ vùng list và vùng chat chính
- `Deliverables`:
  - `ConversationList`
  - `ConversationListItem`
  - `ConversationSearchUI`
  - `MessagePane`
  - `MessageDayDivider`
  - `MessageBubbleMine`
  - `MessageBubbleOther`
  - `MessageAttachmentBlock`
- `Pages dùng`:
  - `/messages`
- `Phụ thuộc`:
  - layout
  - primitive
- `Ưu tiên`: P1
- `Có thể làm song song`: có
- `Ghi chú`: cần mock đủ case text, ảnh, file, audio, reply

### 17. Composer và các panel phụ

- `Owner`: UI-3
- `Task`: Làm vùng nhập tin nhắn và các phần phụ trợ
- `Deliverables`:
  - `MessageComposer`
  - `MessageComposerToolbar`
  - `EmojiPickerShell`
  - `TypingIndicatorUI`
  - `ReadReceiptUI`
  - `ChatHeader`
  - `ConversationInfoPanel`
  - `PinnedMessagesPanel`
- `Pages dùng`:
  - `/messages`
  - chat widget
- `Phụ thuộc`:
  - primitive
  - modal/drawer
- `Ưu tiên`: P1
- `Có thể làm song song`: có
- `Ghi chú`: typing và seen chỉ cần UI placeholder

### 18. Chat widget và notifications dropdown

- `Owner`: UI-3
- `Task`: Làm UI nổi dùng lại ở nhiều trang
- `Deliverables`:
  - `ChatWidget`
  - `FloatingChatWindow`
  - `OnlineUsersRail`
  - `NotificationsDropdown`
  - `NotificationList`
  - `NotificationItem`
- `Pages dùng`:
  - mọi trang sau đăng nhập
- `Phụ thuộc`:
  - header
  - message UI cơ bản
- `Ưu tiên`: P1
- `Có thể làm song song`: có
- `Ghi chú`: phải thống nhất style với header, không làm như một module tách rời về mặt thị giác

---

## Giai đoạn 6: Community UI

### 19. Group UI

- `Owner`: UI-4
- `Task`: Làm UI cho create group, group detail, group settings
- `Deliverables`:
  - `CreateGroupFormUI`
  - `GroupHero`
  - `GroupMetaCard`
  - `GroupActionBar`
  - `GroupMembersList`
  - `GroupAboutBlock`
  - `GroupSettingsLayout`
  - `GroupGeneralSettingsUI`
  - `GroupMembersManagementUI`
  - `GroupJoinRequestsUI`
- `Pages dùng`:
  - `/create-group`
  - `/g/{group_name}`
  - `/group-setting/{group}`
- `Phụ thuộc`:
  - layout
  - tabs
  - feed core để nhúng feed
- `Ưu tiên`: P2
- `Có thể làm song song`: có
- `Ghi chú`: phần feed trong group không làm lại, chỉ embed từ Team Feed

### 20. Page UI

- `Owner`: UI-4
- `Task`: Làm UI cho create page, page detail, page settings
- `Deliverables`:
  - `CreatePageFormUI`
  - `PageHero`
  - `PageMetaCard`
  - `PageActionBar`
  - `PageReviewsList`
  - `PageAboutBlock`
  - `PageSettingsLayout`
  - `PageGeneralSettingsUI`
  - `PageAdminsManagementUI`
- `Pages dùng`:
  - `/create-page`
  - `/p/{page_name}`
  - `/page-setting/{page}`
- `Phụ thuộc`:
  - layout
  - tabs
  - feed core
- `Ưu tiên`: P2
- `Có thể làm song song`: có
- `Ghi chú`: page detail và group detail nên dùng chung pattern khung đầu trang khi có thể

### 21. Event UI

- `Owner`: UI-4
- `Task`: Làm UI cho list event, create event, event detail
- `Deliverables`:
  - `EventsTabsUI`
  - `EventCard`
  - `CreateEventFormUI`
  - `EventHero`
  - `EventDetailsBlock`
  - `EventActionBar`
  - `AttendeeList`
- `Pages dùng`:
  - `/events`
  - `/events/create-event`
  - `/events/{id}`
- `Phụ thuộc`:
  - primitive form
  - layout
- `Ưu tiên`: P2
- `Có thể làm song song`: có
- `Ghi chú`: card event nên tái dùng được trong feed nếu cần

---

## Giai đoạn 7: Commerce UI

### 22. Marketplace listing và card sản phẩm

- `Owner`: UI-5
- `Task`: Làm giao diện danh sách sản phẩm và filter
- `Deliverables`:
  - `ProductCard`
  - `ProductGrid`
  - `ProductFiltersBar`
  - `ProductDetailsMini`
  - `MyProductsList`
- `Pages dùng`:
  - `/products`
  - `/my-products`
- `Phụ thuộc`:
  - layout
  - primitive
- `Ưu tiên`: P2
- `Có thể làm song song`: có
- `Ghi chú`: nên có 1 biến thể card chuẩn, chỉ thêm modifier nếu xuất hiện trong feed

### 23. Form tạo và sửa sản phẩm

- `Owner`: UI-5
- `Task`: Làm form UI cho tạo và sửa sản phẩm
- `Deliverables`:
  - `ProductFormUI`
  - `ProductGalleryUploader`
- `Pages dùng`:
  - `/new-product`
  - `/edit-product/{id}`
- `Phụ thuộc`:
  - primitive form
  - uploader
- `Ưu tiên`: P2
- `Có thể làm song song`: có
- `Ghi chú`: cần đủ field layout để sau này nối dữ liệu thật không bị sửa lại nhiều

### 24. Checkout và orders

- `Owner`: UI-5
- `Task`: Làm UI cho thanh toán và đơn hàng
- `Deliverables`:
  - `CheckoutLayout`
  - `CheckoutSummary`
  - `ShippingAddressFormUI`
  - `PaymentMethodSelector`
  - `OrderList`
  - `OrderCard`
  - `OrderDetailPanel`
- `Pages dùng`:
  - `/checkout`
  - `/orders`
  - `/order/{id}`
  - `/customer_order/{id}`
- `Phụ thuộc`:
  - primitive form
  - layout
- `Ưu tiên`: P2
- `Có thể làm song song`: có
- `Ghi chú`: payment selector chỉ là UI, chưa cần xử lý gateway

### 25. Wallet, withdrawal, go-pro, funding

- `Owner`: UI-5
- `Task`: Làm các màn hình tài chính phụ trợ
- `Deliverables`:
  - `WalletSummary`
  - `TransactionList`
  - `WithdrawalFormUI`
  - `GoProPlanCard`
  - `FundingCard`
  - `FundingDetailHero`
  - `DonationPanel`
- `Pages dùng`:
  - `/wallet`
  - `/withdrawal`
  - `/go-pro`
  - `/funding`
  - `/create_funding`
  - `/show_fund/{id}`
- `Phụ thuộc`:
  - layout
  - primitive form
- `Ưu tiên`: P2
- `Có thể làm song song`: có
- `Ghi chú`: nên thống nhất ngôn ngữ thiết kế tài chính, đừng để ví và funding trông như 2 hệ khác nhau

---

## Giai đoạn 8: Content UI

### 26. Stories, reels, live

- `Owner`: UI-5
- `Task`: Làm các UI media-first
- `Deliverables`:
  - `StoryCarousel`
  - `StoryCard`
  - `StoryViewer`
  - `StoryComposerUI`
  - `ReelViewer`
  - `ReelActionsRail`
  - `LiveStreamLayout`
  - `LiveChatPanel`
- `Pages dùng`:
  - `/story-content`
  - `/status/create`
  - `/reels`
  - `/live`
- `Phụ thuộc`:
  - uploader
  - reaction/comment UI nếu nhúng lại
- `Ưu tiên`: P2
- `Có thể làm song song`: có
- `Ghi chú`: đây là nhóm dễ bị làm “khác hệ”, nên vẫn phải bám token và component base chung

### 27. Blogs, watch, jobs

- `Owner`: UI-5
- `Task`: Làm UI cho content detail và content listing
- `Deliverables`:
  - `BlogCard`
  - `BlogEditorUI`
  - `BlogReaderLayout`
  - `RelatedBlogsList`
  - `VideoWatchLayout`
  - `RelatedVideoList`
  - `JobCard`
  - `JobFiltersBar`
  - `JobApplyFormUI`
- `Pages dùng`:
  - `/blogs`
  - `/create-blog`
  - `/read-blog/{slug}`
  - `/watch`
  - `/jobs`
- `Phụ thuộc`:
  - layout
  - form primitives
  - comment UI cho blog/watch
- `Ưu tiên`: P2
- `Có thể làm song song`: có
- `Ghi chú`: blog detail và watch detail nên giữ cùng logic layout nội dung + sidebar liên quan

### 28. Forum, games, directory

- `Owner`: UI-5
- `Task`: Làm các trang phụ ít ưu tiên hơn
- `Deliverables`:
  - `ForumSectionList`
  - `ForumThreadList`
  - `ForumThreadView`
  - `ForumReplyComposer`
  - `GamesTabsUI`
  - `GameCard`
  - `DirectoryCategoryGrid`
- `Pages dùng`:
  - `/forum`
  - `/games`
  - `/directory`
- `Phụ thuộc`:
  - layout
  - primitive
- `Ưu tiên`: P3
- `Có thể làm song song`: có
- `Ghi chú`: làm sau cùng, không nên chặn tiến độ của core social pages

---

## Danh sách trang nên lắp ngay sau khi có component

### Nhóm bắt buộc phải có sớm

- `Owner`: UI-1, UI-2, UI-3, UI-4
- `Task`: Lắp các trang xương sống của hệ thống
- `Deliverables`:
  - `/welcome`
  - `/register`
  - `/forgot-password`
  - `/home`
  - `/@username`
  - `/messages`
- `Pages dùng`: chính các page trên
- `Phụ thuộc`:
  - foundation UI
  - feed UI
  - messaging UI
  - profile hero UI
- `Ưu tiên`: P0
- `Có thể làm song song`: một phần
- `Ghi chú`: nếu các page này chưa ổn thì chưa nên mở rộng mạnh sang page phụ

### Nhóm mở rộng mức 2

- `Owner`: UI-4, UI-5
- `Task`: Lắp các trang domain quan trọng
- `Deliverables`:
  - `/g/{group_name}`
  - `/p/{page_name}`
  - `/products`
  - `/checkout`
  - `/events`
  - `/blogs`
  - `/watch`
- `Pages dùng`: chính các page trên
- `Phụ thuộc`:
  - các component domain tương ứng
- `Ưu tiên`: P2
- `Có thể làm song song`: có
- `Ghi chú`: tuyệt đối ưu tiên ráp bằng component có sẵn, không custom lại layout trừ khi thật sự cần

---

## Quy tắc để giao việc mà không bị conflict

### 1. Mỗi cụm shared UI chỉ có một owner chính

Ví dụ:

- `Header`, `Layout`, `Primitive` do UI-1 giữ
- `Publisher`, `Post`, `Comment` do UI-2 giữ
- `Messages`, `ChatWidget`, `NotificationsDropdown` do UI-3 giữ

### 2. Không sửa component của owner khác nếu chưa thống nhất

Nếu cần thay đổi:

- mở issue hoặc note
- thống nhất API props
- rồi mới sửa

### 3. Page không được tự đẻ component clone

Ví dụ không nên làm:

- `HomePostCard.vue`
- `ProfilePostCard.vue`
- `GroupPostCard.vue`

trong khi bản chất đều là `PostCard`.

### 4. Component nào dùng từ 3 page trở lên thì phải đưa vào shared

Ví dụ:

- tabs
- dropdown
- widget card
- settings section
- list item

### 5. Chỉ page assembly mới được làm ở cuối

Thứ tự đúng:

1. primitive
2. shared component
3. page assembly

Không làm ngược.

---

## Tiêu chí hoàn thành cho từng task UI

Một task UI được coi là xong khi:

- có bản desktop ổn
- có bản mobile ổn
- có mock data đủ để review
- có state cơ bản: default, hover, active, disabled, empty nếu cần
- không hardcode theo đúng một page duy nhất nếu nó là shared component
- có thể tái sử dụng để lắp vào page khác mà không phải sửa lớn

---

## Gợi ý giao việc theo số người thực tế

### Nếu có 3 người

1. Người A: UI-1 Foundation + một phần settings
2. Người B: UI-2 Feed Core
3. Người C: UI-3 Messaging + UI-4 Profile + UI-5 các page còn lại theo thứ tự ưu tiên

### Nếu có 4 người

1. Người A: Foundation
2. Người B: Feed Core
3. Người C: Messaging
4. Người D: Profile + Community + Commerce + Content

### Nếu có 5 người trở lên

Nên chia đúng theo 5 vai trò trong tài liệu này.

---

## Thứ tự triển khai đề xuất

1. UI-1 xong layout, header, primitive
2. UI-2 xong publisher, post, comment
3. UI-3 xong messages layout cơ bản
4. Lắp `/home`, `/@username`, `/messages`
5. UI-4 làm profile + settings + group/page/event
6. UI-5 làm marketplace + checkout + blogs/watch/stories/reels
7. Sau cùng mới làm forum, games, directory

---

## Kết luận

Nếu mục tiêu là làm nhanh nhưng không vỡ cấu trúc, thì nên giao việc theo cụm component như trên, không giao kiểu:

- người 1 làm full home
- người 2 làm full profile
- người 3 làm full group

vì cách đó gần như chắc chắn sẽ sinh ra nhiều UI trùng chức năng nhưng khác implementation.

Tài liệu này nên được dùng như backlog giao việc ban đầu cho team UI. Sau đó có thể tách tiếp thành:

- file theo từng owner
- checklist trạng thái `todo / doing / review / done`
- mapping `component -> file path -> owner`
