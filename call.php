<?php
// ----------------------------------------------------
// 1. NẠP HỆ SINH THÁI WOWONDER
// ----------------------------------------------------
require_once('assets/init.php');

// Nếu chưa đăng nhập thì tự động đá về trang chủ
if ($wo['loggedin'] == false) {
    header("Location: " . $wo['config']['site_url']);
    exit();
}

// Gọi thư viện JWT
require_once 'vendor/autoload.php';
use Firebase\JWT\JWT;

// Tắt cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Lấy Tên Phòng chuẩn
$roomRequest = isset($_GET['room']) ? $_GET['room'] : rand(100, 999);
$roomRequest = is_string($roomRequest) ? trim($roomRequest) : $roomRequest;

// Nếu nhận JWT token (thường có dấu "."), map về room_name để 2 bên vào đúng 1 phòng
if (is_string($roomRequest) && strpos($roomRequest, '.') !== false) {
    $token = Wo_Secure($roomRequest);
    $roomFromToken = '';
    if (isset($db)) {
        $call = $db->where('access_token', $token)->orWhere('access_token_2', $token)->getOne(T_AUDIO_CALLES, array('room_name'));
        if (empty($call)) {
            $call = $db->where('access_token', $token)->orWhere('access_token_2', $token)->getOne(T_VIDEOS_CALLES, array('room_name'));
        }
        if (empty($call)) {
            $call = $db->where('access_token', $token)->getOne(T_AGORA, array('room_name'));
        }
        if (!empty($call) && !empty($call['room_name'])) {
            $roomFromToken = $call['room_name'];
        }
    }
    if (!empty($roomFromToken)) {
        $roomRequest = $roomFromToken;
    }
}

$roomName = "wowonder" . md5($roomRequest);

$is_audio = (isset($_GET['type']) && $_GET['type'] == 'audio') ? 'true' : 'false';
$redirectTarget = $wo['config']['site_url'];
if (!empty($_GET['return_url'])) {
    $returnRequest = urldecode($_GET['return_url']);
    if (strpos($returnRequest, $wo['config']['site_url']) === 0) {
        $redirectTarget = $returnRequest;
    }
}
if ($redirectTarget == $wo['config']['site_url'] && !empty($_SERVER['HTTP_REFERER'])) {
    $refererRequest = $_SERVER['HTTP_REFERER'];
    if (strpos($refererRequest, $wo['config']['site_url']) === 0) {
        $redirectTarget = $refererRequest;
    }
}

// Thông tin user
$user_name = $wo['user']['name'];

$user_id = $wo['user']['user_id'];
$callMeta = array(
    'id' => (!empty($_GET['id']) ? intval($_GET['id']) : 0),
    'type' => ((isset($_GET['type']) && $_GET['type'] == 'audio') ? 'audio' : 'video'),
    'provider' => (!empty($_GET['provider']) ? Wo_Secure($_GET['provider']) : 'twilio')
);
if (empty($callMeta['id']) && !empty($roomRequest)) {
    $room_name = Wo_Secure($roomRequest);
    $query = mysqli_query($sqlConnect, "SELECT `id`, 'audio' AS `type`, 'twilio' AS `provider` FROM " . T_AUDIO_CALLES . " WHERE `room_name` = '{$room_name}' AND (`from_id` = '{$user_id}' OR `to_id` = '{$user_id}') LIMIT 1");
    if (mysqli_num_rows($query) == 0) {
        $query = mysqli_query($sqlConnect, "SELECT `id`, 'video' AS `type`, 'twilio' AS `provider` FROM " . T_VIDEOS_CALLES . " WHERE `room_name` = '{$room_name}' AND (`from_id` = '{$user_id}' OR `to_id` = '{$user_id}') LIMIT 1");
    }
    if (mysqli_num_rows($query) == 0) {
        $query = mysqli_query($sqlConnect, "SELECT `id`, `type`, 'agora' AS `provider` FROM " . T_AGORA . " WHERE `room_name` = '{$room_name}' AND (`from_id` = '{$user_id}' OR `to_id` = '{$user_id}') LIMIT 1");
    }
    if (mysqli_num_rows($query) > 0) {
        $callMeta = mysqli_fetch_assoc($query);
    }
}
if (!empty($callMeta['id'])) {
    $call_id = intval($callMeta['id']);
    $call_type = ($callMeta['type'] == 'audio') ? 'audio' : 'video';
    $call_provider = (!empty($callMeta['provider']) ? $callMeta['provider'] : 'twilio');
    $call_claim_id = Wo_GetCallSessionClaim($user_id);
    if ($call_provider == 'agora') {
        $call_status_query = mysqli_query($sqlConnect, "SELECT `id`, `from_id`, `to_id`, `active`, `declined`, `status`, `called` FROM " . T_AGORA . " WHERE `id` = '{$call_id}' LIMIT 1");
    }
    else if ($call_type == 'audio') {
        $call_status_query = mysqli_query($sqlConnect, "SELECT `id`, `from_id`, `to_id`, `active`, `declined`, `status`, `called` FROM " . T_AUDIO_CALLES . " WHERE `id` = '{$call_id}' LIMIT 1");
    }
    else {
        $call_status_query = mysqli_query($sqlConnect, "SELECT `id`, `from_id`, `to_id`, `active`, `declined`, `status`, `called` FROM " . T_VIDEOS_CALLES . " WHERE `id` = '{$call_id}' LIMIT 1");
    }
    if (!empty($call_status_query) && mysqli_num_rows($call_status_query) > 0) {
        $call_status_row = mysqli_fetch_assoc($call_status_query);
        $call_status = isset($call_status_row['status']) ? $call_status_row['status'] : '';
        $call_declined = intval(!empty($call_status_row['declined']) ? $call_status_row['declined'] : 0);
        $call_active = intval(!empty($call_status_row['active']) ? $call_status_row['active'] : 0);
        $call_claimed_by = intval(!empty($call_status_row['called']) ? $call_status_row['called'] : 0);
        $is_caller_join = (intval($call_status_row['from_id']) === intval($user_id));
        $is_receiver_join = (intval($call_status_row['to_id']) === intval($user_id));
        $is_final_status = in_array($call_status, array('declined', 'cancelled', 'no_answer', 'missed', 'ended'));
        if ((!$is_caller_join && !$is_receiver_join) || $call_declined === 1 || $is_final_status) {
            header("Location: " . $redirectTarget);
            exit();
        }
        if ($is_receiver_join && ($call_active !== 1 || $call_status !== 'answered' || $call_claimed_by !== $call_claim_id)) {
            header("Location: " . $redirectTarget);
            exit();
        }
    }
}
$avatar = !empty($wo['user']['avatar']) ? Wo_GetMedia($wo['user']['avatar']) : '';

$avatarHost = !empty($avatar) ? parse_url($avatar, PHP_URL_HOST) : '';
if (!empty($avatarHost)) {
    $avatarHost = strtolower(trim($avatarHost, '[]'));
    if ($avatarHost === 'localhost' || $avatarHost === '::1' || substr($avatarHost, -5) === '.test' || substr($avatarHost, -6) === '.local' || preg_match('/^(127\.|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $avatarHost)) {
        $avatar = '';
    }
}

$jitsiUser = [
    "name" => $user_name,
    "id" => $user_id,
    "affiliation" => "member",
    "moderator" => false
];

if (!empty($avatar)) {
    $jitsiUser["avatar"] = $avatar;
}

// Sinh Token JWT Tự động khớp với cấu hình VPS
$app_id = "vnseea_app";
$app_secret = "VnseeaJitsiSecret_2026_X9mP4qL7tN2vK8sR5wY1uD6hC3zF0a";

$payload = [
    "context" => [
        "user" => $jitsiUser
    ],
    "aud" => "jitsi",
    "iss" => $app_id,
    "sub" => "jitsi.vnseea.vn",
    "room" => $roomName, // Pass đúng Tên Phòng để Server CHẤP NHẬN
    "iat" => time(),
    "nbf" => time() - 300, // Chống lệch giờ Server
    "exp" => time() + 3600
];
?>
<?php
$jwt = JWT::encode($payload, $app_secret, 'HS256');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đang gọi... | <?php echo $wo['config']['siteTitle']; ?></title>
    <script src="https://jitsi.vnseea.vn/libs/external_api.min.js"></script>
    <style>
        :root { --toolbar-height: 70px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; background: #111; overflow: hidden; }
        
        /* Jitsi iframe chiếm toàn màn hình nhưng để chỗ cho nút cúp máy */
        #jitsi-container { 
            width: 100vw; 
            height: calc(100vh - var(--toolbar-height));
            height: calc(100dvh - var(--toolbar-height));
        }
        
        /* Thanh điều khiển custom bên dưới - giống Zalo */
        #custom-toolbar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: var(--toolbar-height);
            background: #1a1a2e;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            padding-left: max(16px, env(safe-area-inset-left));
            padding-right: max(16px, env(safe-area-inset-right));
            padding-bottom: max(8px, env(safe-area-inset-bottom));
            z-index: 9999;
        }
        
        .call-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s, opacity 0.15s;
        }
        .call-btn:hover { transform: scale(1.1); }
        .call-btn:active { transform: scale(0.95); }
        
        .btn-mic { background: #2d2d44; }
        .btn-mic.muted { background: #e74c3c; }
        .btn-cam { background: #2d2d44; }
        .btn-cam.muted { background: #e74c3c; }
        .btn-hangup { background: #e74c3c; width: 56px; height: 56px; }
        
        .call-btn svg { fill: white; width: 24px; height: 24px; }

        @media (max-width: 768px) {
            :root { --toolbar-height: 64px; }
            #custom-toolbar { gap: 18px; }
            .call-btn { width: 46px; height: 46px; }
            .btn-hangup { width: 52px; height: 52px; }
            .call-btn svg { width: 22px; height: 22px; }
        }
    </style>
</head>
<body>
    <div id="jitsi-container"></div>
    
    <!-- THANH NÚT CUSTOM GIỐNG ZALO -->
    <div id="custom-toolbar">
        <button class="call-btn btn-mic" id="btn-mic" title="Tắt/Bật mic">
            <svg viewBox="0 0 24 24"><path d="M12,2A3,3 0 0,1 15,5V11A3,3 0 0,1 12,14A3,3 0 0,1 9,11V5A3,3 0 0,1 12,2M19,11C19,14.53 16.39,17.44 13,17.93V21H11V17.93C7.61,17.44 5,14.53 5,11H7A5,5 0 0,0 12,16A5,5 0 0,0 17,11H19Z"/></svg>
        </button>
        <button class="call-btn btn-switch-cam" id="btn-switch-cam" title="Đổi camera trước/sau">
            <svg viewBox="0 0 24 24"><path d="M7,7H9L10,5H14L15,7H17A3,3 0 0,1 20,10V16A3,3 0 0,1 17,19H7A3,3 0 0,1 4,16V10A3,3 0 0,1 7,7M12,9A4,4 0 0,0 8,13A4,4 0 0,0 12,17A4,4 0 0,0 16,13A4,4 0 0,0 12,9M12,11A2,2 0 0,1 14,13A2,2 0 0,1 12,15A2,2 0 0,1 10,13A2,2 0 0,1 12,11M6,12H7.5V10.5L9.5,12.5L7.5,14.5V13H6V12M18,12V13H16.5V14.5L14.5,12.5L16.5,10.5V12H18Z"/></svg>
        </button>
        <button class="call-btn btn-hangup" id="btn-hangup" title="Cúp máy">
            <svg viewBox="0 0 24 24"><path d="M12,9C10.4,9 8.85,9.25 7.4,9.72V12.82C7.4,13.22 7.17,13.56 6.84,13.72C5.86,14.21 4.97,14.84 4.18,15.57C4,15.75 3.75,15.86 3.5,15.86C3.2,15.86 2.95,15.74 2.77,15.56L0.29,13.08C0.11,12.9 0,12.65 0,12.38C0,12.1 0.11,11.85 0.29,11.67C3.34,8.77 7.46,7 12,7C16.54,7 20.66,8.77 23.71,11.67C23.89,11.85 24,12.1 24,12.38C24,12.65 23.89,12.9 23.71,13.08L21.23,15.56C21.05,15.74 20.8,15.86 20.5,15.86C20.25,15.86 20,15.75 19.82,15.57C19.03,14.84 18.14,14.21 17.16,13.72C16.83,13.56 16.6,13.22 16.6,12.82V9.72C15.15,9.25 13.6,9 12,9Z"/></svg>
        </button>
        <button class="call-btn btn-cam" id="btn-cam" title="Tắt/Bật camera">
            <svg viewBox="0 0 24 24"><path d="M17,10.5V7A1,1 0 0,0 16,6H4A1,1 0 0,0 3,7V17A1,1 0 0,0 4,18H16A1,1 0 0,0 17,17V13.5L21,17.5V6.5L17,10.5Z"/></svg>
        </button>
    </div>
    
    <script>
        const domain = 'jitsi.vnseea.vn';
        const redirectUrl = <?php echo json_encode($redirectTarget); ?>;
        const isAudioCall = <?php echo $is_audio; ?>;
        const callMeta = <?php echo json_encode($callMeta); ?>;
        const closeCallUrl = <?php echo json_encode($wo['config']['site_url'] . '/requests.php'); ?>;
        const options = {
            roomName: <?php echo json_encode($roomName); ?>,
            width: '100%',
            height: '100%',
            parentNode: document.querySelector('#jitsi-container'),
            jwt: <?php echo json_encode($jwt); ?>,
            userInfo: {
                displayName: <?php echo json_encode($user_name); ?>,
                email: ''
            },
            configOverwrite: {
                prejoinPageEnabled: false,
                prejoinConfig: { enabled: false },
                requireDisplayName: false,
                disableProfile: true,
                hideConferenceSubject: true,
                hideConferenceTimer: false,
                disableDeepLinking: true,
                connectionIndicators: { disabled: true },
                startAudioOnly: false,
                startWithVideoMuted: isAudioCall,
                startWithAudioMuted: false,
                disableRemoteMute: true,
                remoteVideoMenu: { disabled: true },
                disableKick: true,
                doNotStoreRoom: true,
                useHostPageLocalStorage: true,
                disableFilmstripAutohiding: true,
                disableSelfView: false,
                filmstrip: {
                    disableStageFilmstrip: false
                },
                notifications: [],
                // Ẩn TOÀN BỘ toolbar Jitsi (cách mới cho bản Jitsi hiện tại)
                toolbarButtons: [],
                defaultLogoUrl: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
                logoImageUrl: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
            },
            interfaceConfigOverwrite: {
                // CHỈ MIC + CAM, KHÔNG CÓ HANGUP (hangup do nút custom lo)
                TOOLBAR_BUTTONS: [],
                TOOLBAR_ALWAYS_VISIBLE: false,
                SHOW_JITSI_WATERMARK: false,
                SHOW_WATERMARK_FOR_GUESTS: false,
                SHOW_BRAND_WATERMARK: false,
                SHOW_POWERED_BY: false,
                SHOW_PROMOTIONAL_CLOSE_PAGE: false,
                HIDE_INVITE_MORE_HEADER: true,
                DISABLE_JOIN_LEAVE_NOTIFICATIONS: true,
                CONNECTION_INDICATOR_DISABLED: true,
                FILM_STRIP_MAX_HEIGHT: 140,
                SETTINGS_SECTIONS: [],
                VIDEO_QUALITY_LABEL_DISABLED: true
            }
        };

        const api = new JitsiMeetExternalAPI(domain, options);
        let conferenceJoinedAt = 0;
        let callEndReported = false;
        let callLogPromotedToVideo = false;
        let filmstripVisible = null;
        const preferMobileSelfView = window.matchMedia('(max-width: 768px)').matches;
        let allowManualTileView = false;

        function reportCallEnd(forcedStatus) {
            if (callEndReported || !callMeta || !callMeta.id) {
                return Promise.resolve();
            }
            callEndReported = true;
            const duration = conferenceJoinedAt > 0 ? Math.max(0, Math.floor((Date.now() - conferenceJoinedAt) / 1000)) : 0;
            const status = forcedStatus || (conferenceJoinedAt > 0 ? 'ended' : 'no_answer');
            const url = closeCallUrl + '?f=close_call&id=' + encodeURIComponent(callMeta.id) + '&call_type=' + encodeURIComponent(callMeta.type || (isAudioCall ? 'audio' : 'video')) + '&provider=' + encodeURIComponent(callMeta.provider || 'twilio') + '&status=' + encodeURIComponent(status) + '&duration=' + encodeURIComponent(duration);
            try {
                return fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).catch(function () {});
            } catch (err) {}
            return Promise.resolve();
        }

        function redirectAfterCallEnd(forcedStatus, delay) {
            reportCallEnd(forcedStatus).finally(function () {
                setTimeout(function() {
                    window.location.href = redirectUrl;
                }, delay || 0);
            });
        }

        function promoteCallLogToVideo() {
            if (!isAudioCall || callLogPromotedToVideo || !callMeta || !callMeta.id) {
                return;
            }
            callLogPromotedToVideo = true;
            const url = closeCallUrl + '?f=set_call_type&id=' + encodeURIComponent(callMeta.id) + '&source_call_type=audio&display_call_type=video&provider=' + encodeURIComponent(callMeta.provider || 'twilio');
            try {
                fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).catch(function () {});
            } catch (err) {}
        }

        // Trạng thái Mic và Cam
        let micMuted = false;
        let camMuted = isAudioCall;
        let enforceVideoMuteTimer = null;
        let userWantsVideo = !isAudioCall;

        const enforceVideoMute = () => {
            if (!isAudioCall || userWantsVideo) {
                return;
            }
            api.isVideoMuted().then(muted => {
                if (!muted) {
                    api.executeCommand('toggleVideo');
                }
            });
        };

        const stopEnforceVideoMute = () => {
            if (enforceVideoMuteTimer) {
                clearInterval(enforceVideoMuteTimer);
                enforceVideoMuteTimer = null;
            }
        };

        const shouldKeepSelfViewVisible = () => preferMobileSelfView && (!isAudioCall || userWantsVideo);

        const ensurePreferredVideoLayout = () => {
            if (!shouldKeepSelfViewVisible() || allowManualTileView) {
                return;
            }
            api.executeCommand('setTileView', false);
            if (filmstripVisible === false) {
                api.executeCommand('toggleFilmStrip');
            }
        };

        const startEnforceVideoMute = () => {
            if (!isAudioCall || userWantsVideo || enforceVideoMuteTimer) {
                return;
            }
            const startedAt = Date.now();
            enforceVideoMute();
            enforceVideoMuteTimer = setInterval(() => {
                if (userWantsVideo) {
                    stopEnforceVideoMute();
                    return;
                }
                if (Date.now() - startedAt > 30000) {
                    stopEnforceVideoMute();
                    return;
                }
                enforceVideoMute();
            }, 700);
        };

        if (camMuted) {
            document.getElementById('btn-cam').classList.add('muted');
        }

        // NÚT MIC
        document.getElementById('btn-mic').addEventListener('click', function() {
            api.executeCommand('toggleAudio');
            micMuted = !micMuted;
            this.classList.toggle('muted', micMuted);
        });

        // NÚT CAMERA
        document.getElementById('btn-cam').addEventListener('click', function() {
            if (isAudioCall) {
                if (camMuted) {
                    userWantsVideo = true;
                    stopEnforceVideoMute();
                    promoteCallLogToVideo();
                } else {
                    userWantsVideo = false;
                }
            }
            api.executeCommand('toggleVideo');
            camMuted = !camMuted;
            this.classList.toggle('muted', camMuted);
            if (!camMuted) {
                setTimeout(ensurePreferredVideoLayout, 300);
            }
            if (isAudioCall && !userWantsVideo) {
                startEnforceVideoMute();
            }
        });

        // NÚT CHUYỂN CAMERA TRƯỚC/SAU (mobile)
        document.getElementById('btn-switch-cam').addEventListener('click', function() {
            api.executeCommand('toggleCamera');
        });

        // NÚT CÚP MÁY — GỌI HANGUP TRƯỚC RỒI REDIRECT
        document.getElementById('btn-hangup').addEventListener('click', function() {
            redirectAfterCallEnd('ended', 350);
            // Gọi hangup để Jitsi báo cho bên kia biết cuộc gọi đã kết thúc
            api.executeCommand('hangup');
            // Đợi 500ms cho Jitsi kịp gửi tín hiệu rồi redirect
        });

        // Đồng bộ trạng thái mic/cam từ Jitsi
        api.addListener('audioMuteStatusChanged', function(data) {
            micMuted = data.muted;
            document.getElementById('btn-mic').classList.toggle('muted', micMuted);
        });
        api.addListener('videoMuteStatusChanged', function(data) {
            camMuted = data.muted;
            document.getElementById('btn-cam').classList.toggle('muted', camMuted);
            if (!camMuted) {
                setTimeout(ensurePreferredVideoLayout, 300);
            }
            if (isAudioCall && !camMuted && !userWantsVideo) {
                enforceVideoMute();
            }
        });
        api.addListener('filmstripDisplayChanged', function (event) {
            filmstripVisible = !!(event && event.visible);
            if (shouldKeepSelfViewVisible() && !filmstripVisible) {
                setTimeout(function () {
                    if (filmstripVisible === false) {
                        api.executeCommand('toggleFilmStrip');
                    }
                }, 100);
            }
        });
        api.addListener('tileViewChanged', function (event) {
            if (event && event.enabled) {
                allowManualTileView = true;
            } else if (event && !event.enabled) {
                allowManualTileView = false;
            }
            if (shouldKeepSelfViewVisible() && !allowManualTileView && event && event.enabled) {
                setTimeout(function () {
                    api.executeCommand('setTileView', false);
                }, 100);
            }
        });

        // Khi bị kick hoặc phòng bị đóng bởi người khác
        api.addListener('readyToClose', function () {
            redirectAfterCallEnd((conferenceJoinedAt > 0 ? 'ended' : 'no_answer'), 0);
        });
        api.addListener('videoConferenceLeft', function () {
            redirectAfterCallEnd((conferenceJoinedAt > 0 ? 'ended' : 'no_answer'), 0);
        });
        // Với audio call, chỉ giữ local camera tắt mặc định; không ép conference vào audio-only
        api.addListener('videoConferenceJoined', function () {
            conferenceJoinedAt = Date.now();
            setTimeout(ensurePreferredVideoLayout, 600);
            if (isAudioCall) {
                startEnforceVideoMute();
            }
        });
        api.addListener('participantJoined', function () {
            setTimeout(ensurePreferredVideoLayout, 250);
        });

        // ZALO-STYLE: Gọi 1-1, bên kia thoát → mình cũng thoát ngay
        api.addListener('participantLeft', function () {
            redirectAfterCallEnd((conferenceJoinedAt > 0 ? 'ended' : 'no_answer'), 250);
            api.executeCommand('hangup');
        });
        window.addEventListener('beforeunload', function () {
            reportCallEnd();
        });
    </script>
</body>
</html>


