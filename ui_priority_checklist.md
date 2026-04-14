# UI checklist can lam truoc

## Muc tieu

Tai lieu nay chi tap trung vao **UI can lam truoc** cho qua trinh migration sang Nuxt.

Pham vi cua tai lieu:

- chi lam giao dien
- dung mock data
- chua can xu ly logic phuc tap
- chua can call API
- uu tien component dung chung truoc, page assembly sau

Tai lieu lien quan:

- [page_feature_audit.md](./page_feature_audit.md)
- [team_work_split.md](./team_work_split.md)

---

## Nguyen tac thuc hien

1. Lam `shared UI` truoc.
2. Lam `page skeleton` sau.
3. Moi page phai uu tien compose tu component da co, khong clone UI moi.
4. Chua can gan API, chi can dung du lieu gia lap on dinh.
5. Chua can lam day du interaction, chi can cover structure, state co ban va responsive.

---

## Dinh nghia muc do uu tien

- `P0`: bat buoc lam truoc, anh huong nhieu page
- `P1`: nen lam ngay sau P0, tai su dung nhieu
- `P2`: UI theo domain, co the lam song song sau khi co P0/P1
- `P3`: UI dac thu, lam sau

---

## Checklist tong theo thu tu

## Phase 1: Nen tang UI chung

### P0. App shell va layout

- [ ] `MainLayout`
- [ ] `GuestLayout`
- [ ] `ResponsiveContainer`
- [ ] `PageSection`
- [ ] `PageHeader`
- [ ] `TabsBar`
- [ ] `ModalShell`
- [ ] `DrawerShell`
- [ ] `DropdownMenu`
- [ ] `EmptyState`
- [ ] `LoadingSkeleton`
- [ ] `Toast UI placeholder`

### P0. Header va navigation

- [ ] `Header`
- [ ] `HeaderLogo`
- [ ] `HeaderSearchInput`
- [ ] `HeaderIconNav`
- [ ] `HeaderBadgePlaceholder`
- [ ] `HeaderUserMenu`
- [ ] `LeftSidebar`
- [ ] `SidebarMenuItem`
- [ ] `RightSidebar`
- [ ] `WidgetCard`

### P0. Form primitives

- [ ] `TextInput`
- [ ] `TextareaAutoResize`
- [ ] `SelectBox`
- [ ] `Checkbox`
- [ ] `RadioGroup`
- [ ] `ToggleSwitch`
- [ ] `DatePickerUI`
- [ ] `SearchInput`
- [ ] `TagInput`
- [ ] `PasswordInput`
- [ ] `Uploader`
- [ ] `MediaPreviewList`
- [ ] `FormSection`
- [ ] `SubmitBar`

### Ket qua can dat sau Phase 1

- co bo khung giao dien dung chung cho guest page va authenticated page
- co bo input/form controls de tat ca team dung chung
- co header, sidebar, modal, dropdown de lap page nhanh

---

## Phase 2: Feed Core UI

Day la nhom UI quan trong nhat vi duoc dung lai o rat nhieu trang.

### P0. Publisher

- [ ] `PublisherBox`
- [ ] `PublisherHeader`
- [ ] `PublisherTextarea`
- [ ] `PublisherToolbar`
- [ ] `AudienceSelector`
- [ ] `PublisherMediaPickerUI`
- [ ] `PublisherPollEditorUI`
- [ ] `PublisherFeelingPickerUI`
- [ ] `PublisherLocationInputUI`
- [ ] `PublisherProductInlineUI`
- [ ] `PublisherFooterActions`

### P0. Post

- [ ] `PostCard`
- [ ] `PostHeader`
- [ ] `PostAuthorMeta`
- [ ] `PostPrivacyBadge`
- [ ] `PostMenuUI`
- [ ] `PostBody`
- [ ] `PostTextBlock`
- [ ] `PostMediaGrid`
- [ ] `PostVideoBlock`
- [ ] `PostAudioBlock`
- [ ] `PostLinkPreview`
- [ ] `PostPollBlock`
- [ ] `PostAlbumBlock`
- [ ] `PostProductCard`
- [ ] `PostJobCard`
- [ ] `PostEventCard`
- [ ] `PostSharedCard`
- [ ] `PostFooter`
- [ ] `ReactionBar`
- [ ] `ReactionPicker`
- [ ] `PostStatsRow`

### P0. Comment

- [ ] `CommentComposer`
- [ ] `CommentList`
- [ ] `CommentItem`
- [ ] `CommentActions`
- [ ] `ReplyThread`
- [ ] `CommentSortBar`
- [ ] `LoadMoreCommentsButton`

### P1. Share va lightbox

- [ ] `ShareModal`
- [ ] `ShareTargetListUI`
- [ ] `LightboxViewer`
- [ ] `LightboxMediaNav`
- [ ] `LightboxCommentPanel`

### Ket qua can dat sau Phase 2

- lap duoc UI cho `home`, `profile timeline`, `group feed`, `page feed`
- co bo component core de cac team khac tai su dung

---

## Phase 3: 3 page mau de test he component

Khong lam full he thong ngay. Dung 3 page mau nay de kiem tra bo component da dung huong chua.

### P0. `/home`

- [ ] khung 3 cot
- [ ] story carousel placeholder
- [ ] publisher
- [ ] feed posts
- [ ] right widgets

### P0. `/@username`

- [ ] profile hero
- [ ] intro/about card
- [ ] tabs
- [ ] timeline feed

### P0. `/messages`

- [ ] conversations list
- [ ] message pane
- [ ] message composer
- [ ] info side panel

### Ket qua can dat sau Phase 3

- biet duoc bo component chung da du de lap page chua
- neu thieu component nao thi bo sung ngay truoc khi mo rong sang page khac

---

## Phase 4: Identity UI

### P1. Profile UI

- [ ] `ProfileHero`
- [ ] `ProfileCover`
- [ ] `ProfileAvatar`
- [ ] `ProfileMeta`
- [ ] `ProfileActionBar`
- [ ] `ProfileTabs`
- [ ] `ProfileIntroCard`
- [ ] `MutualFriendsBlock`
- [ ] `FriendsGrid`
- [ ] `FollowersList`
- [ ] `FollowingList`
- [ ] `PhotosGrid`
- [ ] `VideosGrid`
- [ ] `AlbumsGrid`
- [ ] `ProductsGridMini`

### P1. Settings UI

- [ ] `SettingsLayout`
- [ ] `SettingsNav`
- [ ] `SettingsSection`
- [ ] `GeneralSettingsFormUI`
- [ ] `ProfileSettingsFormUI`
- [ ] `PrivacySettingsUI`
- [ ] `AvatarSettingsUI`
- [ ] `DesignSettingsUI`
- [ ] `PasswordSettingsUI`
- [ ] `TwoFactorSettingsUI`
- [ ] `NotificationSettingsUI`
- [ ] `EmailSettingsUI`
- [ ] `SocialLinksSettingsUI`
- [ ] `BlockedUsersListUI`
- [ ] `SessionsListUI`
- [ ] `VerificationUploadUI`
- [ ] `DeleteAccountUI`
- [ ] `AddressesUI`
- [ ] `MonetizationSettingsUI`

---

## Phase 5: Messaging UI

### P1. Chat va messages

- [ ] `MessagesLayout`
- [ ] `ConversationList`
- [ ] `ConversationListItem`
- [ ] `ConversationSearchUI`
- [ ] `MessagePane`
- [ ] `MessageDayDivider`
- [ ] `MessageBubbleMine`
- [ ] `MessageBubbleOther`
- [ ] `MessageAttachmentBlock`
- [ ] `MessageComposer`
- [ ] `MessageComposerToolbar`
- [ ] `EmojiPickerShell`
- [ ] `TypingIndicatorUI`
- [ ] `ReadReceiptUI`
- [ ] `ChatHeader`
- [ ] `ConversationInfoPanel`
- [ ] `PinnedMessagesPanel`
- [ ] `ChatWidget`
- [ ] `FloatingChatWindow`
- [ ] `OnlineUsersRail`

### P1. Notifications UI

- [ ] `NotificationsDropdown`
- [ ] `NotificationList`
- [ ] `NotificationItem`

---

## Phase 6: Community UI

### P2. Group UI

- [ ] `CreateGroupFormUI`
- [ ] `GroupHero`
- [ ] `GroupMetaCard`
- [ ] `GroupActionBar`
- [ ] `GroupMembersList`
- [ ] `GroupAboutBlock`
- [ ] `GroupSettingsLayout`
- [ ] `GroupGeneralSettingsUI`
- [ ] `GroupMembersManagementUI`
- [ ] `GroupJoinRequestsUI`

### P2. Page UI

- [ ] `CreatePageFormUI`
- [ ] `PageHero`
- [ ] `PageMetaCard`
- [ ] `PageActionBar`
- [ ] `PageReviewsList`
- [ ] `PageAboutBlock`
- [ ] `PageSettingsLayout`
- [ ] `PageGeneralSettingsUI`
- [ ] `PageAdminsManagementUI`

### P2. Event UI

- [ ] `EventsTabsUI`
- [ ] `EventCard`
- [ ] `CreateEventFormUI`
- [ ] `EventHero`
- [ ] `EventDetailsBlock`
- [ ] `EventActionBar`
- [ ] `AttendeeList`

---

## Phase 7: Commerce UI

### P2. Marketplace

- [ ] `ProductCard`
- [ ] `ProductGrid`
- [ ] `ProductFiltersBar`
- [ ] `ProductDetailsMini`
- [ ] `ProductFormUI`
- [ ] `ProductGalleryUploader`
- [ ] `MyProductsList`

### P2. Checkout va orders

- [ ] `CheckoutLayout`
- [ ] `CheckoutSummary`
- [ ] `ShippingAddressFormUI`
- [ ] `PaymentMethodSelector`
- [ ] `OrderList`
- [ ] `OrderCard`
- [ ] `OrderDetailPanel`

### P2. Wallet / withdrawal / go-pro / funding

- [ ] `WalletSummary`
- [ ] `TransactionList`
- [ ] `WithdrawalFormUI`
- [ ] `GoProPlanCard`
- [ ] `FundingCard`
- [ ] `FundingDetailHero`
- [ ] `DonationPanel`

---

## Phase 8: Content Extensions UI

### P2. Stories / reels / live

- [ ] `StoryCarousel`
- [ ] `StoryCard`
- [ ] `StoryViewer`
- [ ] `StoryComposerUI`
- [ ] `ReelViewer`
- [ ] `ReelActionsRail`
- [ ] `LiveStreamLayout`
- [ ] `LiveChatPanel`

### P2. Blogs / watch / jobs

- [ ] `BlogCard`
- [ ] `BlogEditorUI`
- [ ] `BlogReaderLayout`
- [ ] `RelatedBlogsList`
- [ ] `VideoWatchLayout`
- [ ] `RelatedVideoList`
- [ ] `JobCard`
- [ ] `JobFiltersBar`
- [ ] `JobApplyFormUI`

### P3. Forum / games / directory

- [ ] `ForumSectionList`
- [ ] `ForumThreadList`
- [ ] `ForumThreadView`
- [ ] `ForumReplyComposer`
- [ ] `GamesTabsUI`
- [ ] `GameCard`
- [ ] `DirectoryCategoryGrid`

---

## Danh sach page skeleton can lap sau khi co shared UI

Danh sach nay chi la lap page bang component da co, chua can nghiep vu.

### P1. Auth pages

- [ ] `/welcome`
- [ ] `/register`
- [ ] `/forgot-password`

### P1. Core social pages

- [ ] `/home`
- [ ] `/@username`
- [ ] `/messages`
- [ ] `/search`
- [ ] `/saved-posts`
- [ ] `/explore`
- [ ] `/hashtag/{tag}`

### P2. Community pages

- [ ] `/create-group`
- [ ] `/g/{group_name}`
- [ ] `/group-setting/{group}`
- [ ] `/create-page`
- [ ] `/p/{page_name}`
- [ ] `/page-setting/{page}`
- [ ] `/events`
- [ ] `/events/create-event`
- [ ] `/events/{id}`

### P2. Commerce pages

- [ ] `/products`
- [ ] `/new-product`
- [ ] `/edit-product/{id}`
- [ ] `/my-products`
- [ ] `/checkout`
- [ ] `/orders`
- [ ] `/order/{id}`
- [ ] `/customer_order/{id}`
- [ ] `/wallet`
- [ ] `/withdrawal`
- [ ] `/go-pro`

### P2. Content pages

- [ ] `/story-content`
- [ ] `/status/create`
- [ ] `/reels`
- [ ] `/live`
- [ ] `/blogs`
- [ ] `/create-blog`
- [ ] `/read-blog/{slug}`
- [ ] `/watch`
- [ ] `/jobs`
- [ ] `/forum`
- [ ] `/directory`

---

## Moi UI item can dat toi thieu gi

Moi component UI lam ra nen co:

- [ ] desktop responsive
- [ ] mobile responsive
- [ ] hover / active / disabled state co ban
- [ ] empty state neu can
- [ ] loading skeleton neu la list lon
- [ ] dark mode neu project co support
- [ ] sample mock data de review

---

## Rule de tranh lam lai

### Neu gap UI trung nhau thi uu tien tai su dung

Vi du:

- card list dung chung mot style base
- form settings dung chung `SettingsSection`
- cards trong right sidebar dung chung `WidgetCard`
- list item cho messages, notifications, followers nen co pattern gan nhau

### Khong duoc lam theo cach sau

- moi page tu viet mot loai modal rieng
- moi page tu viet mot loai tabs rieng
- `PostCard` cho home khac `PostCard` cho profile
- `ProductCard` danh cho feed khac hoan toan `ProductCard` trong marketplace ma khong co ly do ro rang

---

## Thu tu lam viec khuyen nghi cho team UI

1. Layout + Header + Sidebar + Modal + Form primitives
2. Feed Core: Publisher + Post + Comment + Share + Lightbox
3. Lap 3 page mau: `/home`, `/@username`, `/messages`
4. Profile + Settings
5. Community
6. Commerce
7. Stories / Reels / Live / Blogs / Watch / Jobs
8. Forum / Games / Directory

---

## Definition of done cho pha UI

Mot page duoc coi la xong UI khi:

- da dung dung shared component
- da co layout on dinh o desktop va mobile
- da co mock data du de demo
- khong con hardcode linh tinh lam vo component reuse
- co the dua cho team logic vao gan data sau do

---

## Ghi chu cuoi

Neu can day nhanh tien do, co the chia:

- 1 nguoi lo `Foundation UI`
- 1 nguoi lo `Feed Core UI`
- 1 nguoi lo `Messages UI`
- 1 nguoi lo `Profile + Settings UI`
- 1 nguoi lo `Community + Commerce + Content pages` theo muc do uu tien

Tai lieu nay la checklist UI. Neu can buoc tiep theo, nen tao them:

- backlog task theo tung nguoi
- mapping `component -> pages su dung`
- mapping `page -> shared component can ghep`
