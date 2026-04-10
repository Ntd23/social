<?php
require_once('assets/init.php');

if ($wo['loggedin'] == false) {
    header("Location: " . $wo['config']['site_url']);
    exit();
}

require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$call_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = intval($wo['user']['user_id']);
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

function Wo_GroupCallNormalizeMediaUrl($media)
{
    if (empty($media) || !is_string($media)) {
        return '';
    }
    $media = trim($media);
    if ($media === '') {
        return '';
    }
    if (filter_var($media, FILTER_VALIDATE_URL)) {
        return $media;
    }
    return Wo_GetMedia(ltrim($media, '/'));
}

$groupCall = Wo_GetGroupCallById($call_id);
if (empty($groupCall) || !Wo_IsGroupChatCallMember(!empty($groupCall['group_id']) ? $groupCall['group_id'] : 0, $user_id)) {
    header("Location: " . $redirectTarget);
    exit();
}

$groupSync = Wo_GetGroupCallSyncData($call_id, $user_id);
if (empty($groupSync) || empty($groupSync['call'])) {
    header("Location: " . $redirectTarget);
    exit();
}

$groupCall = $groupSync['call'];
if (!empty($groupCall['status']) && $groupCall['status'] !== 'active') {
    header("Location: " . $redirectTarget);
    exit();
}

$group = Wo_GroupTabData($groupCall['group_id'], false);
$isAudioCall = ($groupCall['call_type'] === 'audio');
$groupName = !empty($group['group_name']) ? $group['group_name'] : 'Group call';
$callStartedAt = !empty($groupCall['started_at']) ? intval($groupCall['started_at']) : time();
$serverNow = time();
$initialElapsedSeconds = max(0, $serverNow - $callStartedAt);

$userName = !empty($wo['user']['name']) ? $wo['user']['name'] : 'You';
$userAvatar = !empty($wo['user']['avatar']) ? Wo_GroupCallNormalizeMediaUrl($wo['user']['avatar']) : '';
$livekitWsUrl = Wo_GetLiveKitServerUrl();
$livekitConfigured = Wo_IsLiveKitAvailable();
$livekitToken = '';

if ($livekitConfigured) {
    $payload = array(
        'iss' => trim($wo['config']['livekit_api_key']),
        'sub' => 'groupcall_user_' . $user_id . '_' . substr(sha1(session_id() . '|' . $groupCall['room_name']), 0, 12),
        'nbf' => time() - 300,
        'exp' => time() + 3600,
        'name' => $userName,
        'metadata' => json_encode(array(
            'user_id' => (string) $user_id,
            'name' => $userName,
            'avatar' => $userAvatar,
            'group_id' => (string) $groupCall['group_id']
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'video' => array(
            'roomJoin' => true,
            'room' => $groupCall['room_name'],
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true
        )
    );
    $livekitToken = JWT::encode($payload, trim($wo['config']['livekit_api_secret']), 'HS256');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php echo htmlspecialchars($groupName); ?> | <?php echo $wo['config']['siteTitle']; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js"></script>
    <style>
        :root{--toolbar-height:128px;--toolbar-btn-size:86px;--toolbar-gap:14px;--toolbar-max-width:820px;--panel:#0d1120;--panel-soft:rgba(35,40,57,.88);--danger:#ef2f2f;--danger-shadow:rgba(239,47,47,.35);--accent:#7a88ff;--text:#f8fafc;--muted:#98a4c0;--border:rgba(148,163,184,.18)}
        *{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%;overflow:hidden;color:var(--text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:radial-gradient(circle at 50% 25%,rgba(96,110,255,.16) 0%,rgba(96,110,255,.06) 24%,rgba(7,10,20,0) 50%),linear-gradient(180deg,#090b12 0%,#0c1020 46%,#06070c 100%)}
        .gcall-shell{min-height:100vh;min-height:100dvh;padding:26px 20px calc(var(--toolbar-height) + 26px)}
        .gcall-top{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;padding-bottom:18px}
        .gcall-name{font-size:clamp(28px,4vw,44px);font-weight:600;line-height:1.05;letter-spacing:-.04em}
        .gcall-status{font-size:13px;letter-spacing:.16em;text-transform:uppercase;color:rgba(226,232,240,.74)}
        .gcall-timer{display:inline-flex;align-items:center;justify-content:center;min-width:112px;padding:12px 22px;border-radius:999px;background:rgba(111,126,255,.12);border:1px solid rgba(122,136,255,.3);backdrop-filter:blur(18px);color:#eef2ff;font-size:18px;font-weight:500;letter-spacing:.04em;box-shadow:0 18px 36px rgba(41,52,125,.18)}
        .gcall-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;align-content:start;height:calc(100vh - var(--toolbar-height) - 140px);height:calc(100dvh - var(--toolbar-height) - 140px);overflow:auto;padding-bottom:12px}
        .gcall-shell.audio-mode .gcall-grid{grid-template-columns:repeat(auto-fit,minmax(200px,1fr))}
        .gcall-tile{position:relative;min-height:220px;border-radius:30px;overflow:hidden;border:1px solid var(--border);background:linear-gradient(180deg,rgba(15,23,42,.88) 0%,rgba(17,24,39,.98) 100%);box-shadow:0 18px 40px rgba(2,6,23,.35)}
        .gcall-shell.audio-mode .gcall-tile{min-height:260px}
        .gcall-media{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:#020617}
        .gcall-media video{width:100%;height:100%;display:block;object-fit:cover;background:#020617}
        .gcall-avatar-wrap{position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:24px}
        .gcall-avatar-glow{position:absolute;inset:16%;border-radius:999px;background:radial-gradient(circle,rgba(111,126,255,.25) 0%,rgba(111,126,255,.05) 48%,rgba(111,126,255,0) 72%);filter:blur(14px)}
        .gcall-avatar{position:relative;width:min(52vw,160px);height:min(52vw,160px);border-radius:999px;background:linear-gradient(180deg,#8da0ff 0%,#5f6fe4 100%);padding:8px;box-shadow:0 20px 50px rgba(72,88,214,.26)}
        .gcall-avatar-inner{width:100%;height:100%;border-radius:999px;overflow:hidden;border:4px solid rgba(5,10,28,.9);background:linear-gradient(180deg,#f5f7fb 0%,#d6deeb 100%);display:flex;align-items:center;justify-content:center}
        .gcall-avatar-inner img{width:100%;height:100%;display:block;object-fit:cover}
        .gcall-avatar-fallback{font-size:52px;font-weight:700;color:#26324d;text-transform:uppercase}
        .gcall-label{position:absolute;left:12px;right:12px;bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:18px;background:rgba(15,23,42,.72);backdrop-filter:blur(10px)}
        .gcall-label-main{min-width:0}
        .gcall-label-name{font-size:15px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .gcall-label-sub{font-size:12px;color:rgba(226,232,240,.72)}
        .gcall-badges{display:flex;align-items:center;gap:8px;flex-shrink:0}
        .gcall-chip{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 10px;border-radius:999px;background:rgba(8,11,18,.78);font-size:11px;font-weight:600}
        .gcall-chip.local{background:rgba(90,104,255,.92)}
        .gcall-chip.warn{background:rgba(147,33,43,.96)}
        .gcall-chip.soft{background:rgba(71,85,105,.75)}
        .gcall-empty{display:flex;align-items:center;justify-content:center;height:100%;border-radius:28px;border:1px dashed rgba(148,163,184,.28);background:rgba(15,23,42,.3);color:var(--muted);font-size:15px}
        #custom-toolbar{position:fixed;left:50%;transform:translateX(-50%);bottom:max(18px,env(safe-area-inset-bottom));width:min(calc(100% - 28px),var(--toolbar-max-width));display:flex;align-items:center;justify-content:center;gap:var(--toolbar-gap);padding:14px 18px calc(14px + env(safe-area-inset-bottom));background:none;border:0;box-shadow:none;z-index:20}
        .call-btn{position:relative;flex:0 0 var(--toolbar-btn-size);width:var(--toolbar-btn-size);height:var(--toolbar-btn-size);min-width:var(--toolbar-btn-size);min-height:var(--toolbar-btn-size);border-radius:999px;border:0;background:var(--panel-soft);color:#fff;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:transform .15s ease,background .15s ease,box-shadow .15s ease;box-shadow:0 8px 20px rgba(0,0,0,.22);touch-action:manipulation;-webkit-tap-highlight-color:transparent}
        .call-btn.active{background:rgba(122,136,255,.16);box-shadow:0 0 0 1px rgba(123,140,255,.35) inset,0 10px 26px rgba(52,66,183,.22)}
        .call-btn.muted{background:rgba(147,33,43,.95)}
        .call-btn.muted::after{content:"";position:absolute;width:40px;height:4px;border-radius:999px;background:#fff;transform:rotate(-45deg);box-shadow:0 0 0 2px rgba(0,0,0,.06)}
        .btn-hangup{background:var(--danger);box-shadow:0 18px 34px var(--danger-shadow)}
        .call-btn svg{width:34px;height:34px;fill:currentColor}
        .gcall-audio-sink{position:absolute;width:0;height:0;overflow:hidden}
        .gcall-toast{position:fixed;left:50%;top:28px;transform:translateX(-50%);padding:12px 18px;border-radius:999px;background:rgba(8,11,18,.92);color:#fff;font-size:13px;box-shadow:0 16px 36px rgba(0,0,0,.28);border:1px solid rgba(255,255,255,.05);opacity:0;pointer-events:none;transition:opacity .18s ease;z-index:50}
        .gcall-toast.show{opacity:1}
        .gcall-modal{position:fixed;inset:0;display:none;align-items:flex-end;justify-content:center;background:rgba(2,6,23,.58);backdrop-filter:blur(10px);z-index:60}
        .gcall-modal.open{display:flex}
        .gcall-modal-card{width:min(100%,460px);max-height:min(70vh,640px);display:flex;flex-direction:column;background:#fff;color:#0f172a;border-radius:28px 28px 0 0;overflow:hidden}
        .gcall-modal-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #e4e7ec}
        .gcall-modal-title{font-size:18px;font-weight:700}
        .gcall-modal-close{border:0;background:#f2f4f7;color:#344054;width:38px;height:38px;border-radius:999px;cursor:pointer;font-size:20px}
        .gcall-member-list{padding:6px 20px 18px;overflow:auto}
        .gcall-member-row{display:flex;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid #f2f4f7}
        .gcall-member-row img{width:42px;height:42px;border-radius:999px;object-fit:cover}
        .gcall-member-name{font-size:15px;font-weight:600;color:#101828}
        .gcall-member-meta{font-size:12px;color:#667085}
        .gcall-member-row input{width:18px;height:18px}
        .gcall-modal-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:16px 20px;border-top:1px solid #e4e7ec}
        .gcall-action-btn{border:0;border-radius:999px;padding:12px 18px;font-size:14px;font-weight:600;cursor:pointer}
        .gcall-action-btn.secondary{background:#f2f4f7;color:#344054}
        .gcall-action-btn.primary{background:#4656d9;color:#fff}
        @media (max-width:768px){:root{--toolbar-height:114px;--toolbar-gap:8px}.gcall-shell{padding:18px 14px calc(var(--toolbar-height) + 20px)}.gcall-grid{gap:12px;height:calc(100vh - var(--toolbar-height) - 120px);height:calc(100dvh - var(--toolbar-height) - 120px);grid-template-columns:repeat(2,minmax(0,1fr))}.gcall-shell.audio-mode .gcall-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.gcall-tile{min-height:180px;border-radius:24px}.gcall-shell.audio-mode .gcall-tile{min-height:210px}.gcall-avatar{width:112px;height:112px}.gcall-avatar-fallback{font-size:38px}.gcall-label{left:10px;right:10px;bottom:10px;padding:9px 10px;border-radius:16px}.gcall-label-name{font-size:13px}#custom-toolbar{width:auto;max-width:calc(100% - 12px);justify-content:center;gap:var(--toolbar-gap);padding:8px 10px;background:rgba(9,14,26,.78);border:1px solid rgba(148,163,184,.14);box-shadow:0 18px 34px rgba(0,0,0,.3);backdrop-filter:blur(18px);border-radius:999px;bottom:max(10px,env(safe-area-inset-bottom))}.call-btn{flex:0 0 clamp(68px,20vw,84px);width:clamp(68px,20vw,84px);height:clamp(68px,20vw,84px);min-width:clamp(68px,20vw,84px);min-height:clamp(68px,20vw,84px)}.call-btn svg{width:30px;height:30px}}
    </style>
</head>
<body class="<?php echo $isAudioCall ? 'audio-mode' : ''; ?>">
    <div class="gcall-shell<?php echo $isAudioCall ? ' audio-mode' : ''; ?>">
        <div class="gcall-top">
            <div id="gcall-name" class="gcall-name"><?php echo htmlspecialchars($groupName); ?></div>
            <div id="gcall-status" class="gcall-status">Connecting securely</div>
            <div id="gcall-timer" class="gcall-timer">00:00</div>
        </div>
        <div id="gcall-grid" class="gcall-grid">
            <div class="gcall-empty">Connecting group call...</div>
        </div>
    </div>
    <div id="custom-toolbar">
        <button class="call-btn" id="btn-mic" title="Toggle microphone"><svg viewBox="0 0 24 24"><path d="M12,2A3,3 0 0,1 15,5V11A3,3 0 0,1 12,14A3,3 0 0,1 9,11V5A3,3 0 0,1 12,2M19,11C19,14.53 16.39,17.44 13,17.93V21H11V17.93C7.61,17.44 5,14.53 5,11H7A5,5 0 0,0 12,16A5,5 0 0,0 17,11H19Z"/></svg></button>
        <button class="call-btn" id="btn-speaker" title="Speaker output"><svg viewBox="0 0 24 24"><path d="M14,3.23V20.77C14,21.55 13.16,22.03 12.5,21.65L7,18H3C1.9,18 1,17.1 1,16V8C1,6.9 1.9,6 3,6H7L12.5,2.35C13.16,1.97 14,2.45 14,3.23M16.5,12C16.5,10.23 15.73,8.63 14.5,7.53V16.46C15.73,15.37 16.5,13.76 16.5,12M14.5,3.97V6.18C17.39,7.04 19.5,9.71 19.5,12.5C19.5,15.29 17.39,17.96 14.5,18.82V21.03C18.5,20.13 21.5,16.63 21.5,12.5C21.5,8.37 18.5,4.87 14.5,3.97Z"/></svg></button>
        <?php if (!$isAudioCall): ?>
        <button class="call-btn active" id="btn-cam" title="Toggle camera"><svg viewBox="0 0 24 24"><path d="M17,10.5V7A1,1 0 0,0 16,6H4A1,1 0 0,0 3,7V17A1,1 0 0,0 4,18H16A1,1 0 0,0 17,17V13.5L21,17.5V6.5L17,10.5Z"/></svg></button>
        <?php endif; ?>
        <button class="call-btn active" id="btn-add" title="Add members"><svg viewBox="0 0 24 24"><path d="M15,14C12.33,14 7,15.33 7,18V20H23V18C23,15.33 17.67,14 15,14M6,10V7H4V10H1V12H4V15H6V12H9V10M15,12A4,4 0 0,0 19,8A4,4 0 0,0 15,4A4,4 0 0,0 11,8A4,4 0 0,0 15,12Z"/></svg></button>
        <button class="call-btn btn-hangup" id="btn-hangup" title="Leave call"><svg viewBox="0 0 24 24"><path d="M12,9C10.4,9 8.85,9.25 7.4,9.72V12.82C7.4,13.22 7.17,13.56 6.84,13.72C5.86,14.21 4.97,14.84 4.18,15.57C4,15.75 3.75,15.86 3.5,15.86C3.2,15.86 2.95,15.74 2.77,15.56L0.29,13.08C0.11,12.9 0,12.65 0,12.38C0,12.1 0.11,11.85 0.29,11.67C3.34,8.77 7.46,7 12,7C16.54,7 20.66,8.77 23.71,11.67C23.89,11.85 24,12.1 24,12.38C24,12.65 23.89,12.9 23.71,13.08L21.23,15.56C21.05,15.74 20.8,15.86 20.5,15.86C20.25,15.86 20,15.75 19.82,15.57C19.03,14.84 18.14,14.21 17.16,13.72C16.83,13.56 16.6,13.22 16.6,12.82V9.72C15.15,9.25 13.6,9 12,9Z"/></svg></button>
    </div>
    <div id="gcall-toast" class="gcall-toast"></div>
    <div id="gcall-member-modal" class="gcall-modal">
        <div class="gcall-modal-card">
            <div class="gcall-modal-head">
                <div class="gcall-modal-title">Add members</div>
                <button type="button" id="gcall-member-close" class="gcall-modal-close">&times;</button>
            </div>
            <div id="gcall-member-list" class="gcall-member-list"></div>
            <div class="gcall-modal-actions">
                <button type="button" id="gcall-member-cancel" class="gcall-action-btn secondary">Close</button>
                <button type="button" id="gcall-member-submit" class="gcall-action-btn primary">Invite selected</button>
            </div>
        </div>
    </div>
    <div id="gcall-audio-sink" class="gcall-audio-sink"></div>
    <script>
        const redirectUrl = <?php echo json_encode($redirectTarget); ?>;
        const isAudioCall = <?php echo json_encode($isAudioCall); ?>;
        const groupId = <?php echo intval($groupCall['group_id']); ?>;
        const callId = <?php echo intval($groupCall['id']); ?>;
        const displayName = <?php echo json_encode($userName); ?>;
        const displayAvatar = <?php echo json_encode($userAvatar); ?>;
        const displayUserId = <?php echo intval($user_id); ?>;
        const wsUrl = <?php echo json_encode($livekitWsUrl); ?>;
        const token = <?php echo json_encode($livekitToken); ?>;
        const livekitConfigured = <?php echo json_encode($livekitConfigured); ?>;
        const serverNowAtRender = <?php echo intval($serverNow); ?>;
        const initialElapsedSeconds = <?php echo intval($initialElapsedSeconds); ?>;
        const initialParticipants = <?php echo json_encode(!empty($groupSync['participants']) ? $groupSync['participants'] : array()); ?>;
        const syncCallUrl = <?php echo json_encode($wo['config']['site_url'] . '/requests.php?f=sync_group_call&call_id=' . intval($groupCall['id'])); ?>;
        const joinCallUrl = <?php echo json_encode($wo['config']['site_url'] . '/requests.php?f=join_group_call&call_id=' . intval($groupCall['id'])); ?>;
        const leaveCallUrl = <?php echo json_encode($wo['config']['site_url'] . '/requests.php?f=leave_group_call&call_id=' . intval($groupCall['id'])); ?>;
        const addMembersUrl = <?php echo json_encode($wo['config']['site_url'] . '/requests.php?f=add_group_call_members&hash=' . (!empty($_SESSION['main_hash_id']) ? $_SESSION['main_hash_id'] : '')); ?>;
        const candidatesUrl = <?php echo json_encode($wo['config']['site_url'] . '/requests.php?f=get_group_call_candidates&group_id=' . intval($groupCall['group_id']) . '&call_id=' . intval($groupCall['id'])); ?>;
        const grid = document.getElementById('gcall-grid');
        const statusNode = document.getElementById('gcall-status');
        const timerNode = document.getElementById('gcall-timer');
        const nameNode = document.getElementById('gcall-name');
        const toastNode = document.getElementById('gcall-toast');
        const audioSink = document.getElementById('gcall-audio-sink');
        const memberModal = document.getElementById('gcall-member-modal');
        const memberList = document.getElementById('gcall-member-list');
        const btnMemberSubmit = document.getElementById('gcall-member-submit');
        const btnMic = document.getElementById('btn-mic');
        const btnSpeaker = document.getElementById('btn-speaker');
        const btnCam = document.getElementById('btn-cam');
        const btnAdd = document.getElementById('btn-add');
        const btnHangup = document.getElementById('btn-hangup');
        let room = null;
        let microphoneEnabled = true;
        let cameraEnabled = !isAudioCall;
        let speakerEnabled = true;
        let timerInterval = null;
        let syncInterval = null;
        let toastTimer = null;
        let isLeaving = false;
        let hasReportedLeave = false;
        let participantState = {};

        function getInitials(name) {
            const safe = String(name || '').trim();
            if (!safe) return '?';
            const words = safe.split(/\s+/).filter(Boolean);
            if (words.length === 1) return words[0].slice(0, 1).toUpperCase();
            return (words[0].slice(0, 1) + words[words.length - 1].slice(0, 1)).toUpperCase();
        }

        function showToast(message) {
            if (!toastNode) return;
            toastNode.textContent = message;
            toastNode.classList.add('show');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function () {
                toastNode.classList.remove('show');
            }, 2200);
        }

        function formatDuration(totalSeconds) {
            const safe = Math.max(0, parseInt(totalSeconds || 0, 10));
            const hours = Math.floor(safe / 3600);
            const minutes = Math.floor((safe % 3600) / 60);
            const seconds = safe % 60;
            if (hours > 0) {
                return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }
            return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        }

        function getCallDurationSeconds() {
            return Math.max(0, initialElapsedSeconds + Math.floor((Date.now() - (serverNowAtRender * 1000)) / 1000));
        }

        function startCallTimer() {
            timerNode.textContent = formatDuration(getCallDurationSeconds());
            if (timerInterval) return;
            timerInterval = setInterval(function () {
                timerNode.textContent = formatDuration(getCallDurationSeconds());
            }, 1000);
        }

        function setStatus(text) {
            if (statusNode) {
                statusNode.textContent = text;
            }
        }

        function getAttachedAudioElements() {
            return Array.prototype.slice.call(document.querySelectorAll('#gcall-audio-sink audio'));
        }

        async function applySpeakerMode(enabled) {
            const audioElements = getAttachedAudioElements();
            if (!audioElements.length) {
                showToast('No audio output to switch yet.');
                return;
            }
            let sinkApplied = false;
            for (let i = 0; i < audioElements.length; i += 1) {
                const element = audioElements[i];
                if (typeof element.setSinkId === 'function') {
                    try {
                        await element.setSinkId(enabled ? 'default' : 'communications');
                        sinkApplied = true;
                    } catch (err) {}
                }
            }
            if (!sinkApplied && typeof HTMLMediaElement !== 'undefined' && !('setSinkId' in HTMLMediaElement.prototype)) {
                showToast('This browser does not support audio output switching.');
            }
        }

        function parseParticipantMeta(participant, fallback) {
            let metadata = {};
            if (participant && participant.metadata) {
                try {
                    metadata = JSON.parse(participant.metadata);
                } catch (err) {}
            }
            return {
                user_id: parseInt(metadata.user_id || (fallback && fallback.user_id) || 0, 10),
                name: metadata.name || (participant && (participant.name || participant.identity)) || (fallback && fallback.name) || 'Participant',
                avatar: metadata.avatar || (fallback && fallback.avatar) || ''
            };
        }

        function mergeParticipant(participant) {
            const key = String(participant.user_id);
            const existing = participantState[key] || {};
            participantState[key] = Object.assign({}, existing, participant, {
                user_id: participant.user_id
            });
        }

        function ensureLocalParticipant() {
            mergeParticipant({
                user_id: displayUserId,
                name: displayName,
                avatar: displayAvatar,
                isLocal: true,
                joined: true,
                micMuted: !microphoneEnabled,
                cameraOff: isAudioCall ? true : !cameraEnabled
            });
        }

        function applyServerParticipants(participants) {
            const seen = {};
            (participants || []).forEach(function (participant) {
                const userId = parseInt(participant.user_id || 0, 10);
                if (!userId) return;
                const key = String(userId);
                seen[key] = true;
                mergeParticipant({
                    user_id: userId,
                    name: participant.name || 'Participant',
                    avatar: participant.avatar || '',
                    joined: true
                });
            });
            Object.keys(participantState).forEach(function (key) {
                if (participantState[key].isLocal) {
                    return;
                }
                if (!seen[key]) {
                    if (participantState[key].videoTrack && participantState[key].videoTrack.detach) {
                        participantState[key].videoTrack.detach().forEach(function (element) { element.remove(); });
                    }
                    if (participantState[key].audioElements) {
                        participantState[key].audioElements.forEach(function (element) { element.remove(); });
                    }
                    delete participantState[key];
                }
            });
        }

        function renderParticipantGrid() {
            const participants = Object.keys(participantState).map(function (key) {
                return participantState[key];
            }).sort(function (a, b) {
                if (!!a.isLocal !== !!b.isLocal) {
                    return a.isLocal ? -1 : 1;
                }
                return String(a.name || '').localeCompare(String(b.name || ''));
            });
            if (participants.length === 0) {
                grid.innerHTML = '<div class="gcall-empty">Waiting for participants...</div>';
                return;
            }
            grid.innerHTML = '';
            participants.forEach(function (participant) {
                const tile = document.createElement('div');
                tile.className = 'gcall-tile';
                const media = document.createElement('div');
                media.className = 'gcall-media';
                const shouldShowVideo = !isAudioCall && participant.videoTrack && participant.cameraOff !== true;
                if (shouldShowVideo) {
                    if (!participant.videoElement && participant.videoTrack && typeof participant.videoTrack.attach === 'function') {
                        participant.videoElement = participant.videoTrack.attach();
                    }
                    if (participant.videoElement) {
                        media.appendChild(participant.videoElement);
                    }
                } else {
                    const avatarWrap = document.createElement('div');
                    avatarWrap.className = 'gcall-avatar-wrap';
                    avatarWrap.innerHTML = '<div class="gcall-avatar-glow"></div><div class="gcall-avatar"><div class="gcall-avatar-inner">' + (participant.avatar ? '<img src="' + participant.avatar + '" alt="">' : '<div class="gcall-avatar-fallback">' + getInitials(participant.name) + '</div>') + '</div></div>';
                    media.appendChild(avatarWrap);
                }
                tile.appendChild(media);
                const label = document.createElement('div');
                label.className = 'gcall-label';
                let badges = '';
                if (participant.isLocal) {
                    badges += '<span class="gcall-chip local">You</span>';
                }
                if (participant.micMuted) {
                    badges += '<span class="gcall-chip warn">Mic off</span>';
                }
                if (!isAudioCall && participant.cameraOff) {
                    badges += '<span class="gcall-chip soft">Cam off</span>';
                }
                label.innerHTML = '<div class="gcall-label-main"><div class="gcall-label-name">' + (participant.name || 'Participant') + '</div><div class="gcall-label-sub">' + (participant.isLocal ? 'Connected from this device' : 'In this room') + '</div></div><div class="gcall-badges">' + badges + '</div>';
                tile.appendChild(label);
                grid.appendChild(tile);
            });
        }

        function updateParticipantFromPublications(participant, isLocal) {
            const meta = parseParticipantMeta(participant, isLocal ? {user_id: displayUserId, name: displayName, avatar: displayAvatar} : null);
            const userId = meta.user_id;
            if (!userId) return;
            const key = String(userId);
            const existing = participantState[key] || {};
            const next = Object.assign({}, existing, {
                user_id: userId,
                name: meta.name,
                avatar: meta.avatar,
                isLocal: !!isLocal,
                joined: true
            });
            let micMuted = false;
            let cameraOff = true;
            if (participant && participant.trackPublications) {
                participant.trackPublications.forEach(function (publication) {
                    const source = String(publication.source || '').toLowerCase();
                    const isAudio = publication.kind === 'audio' || source.indexOf('microphone') !== -1;
                    const isVideo = publication.kind === 'video' || source.indexOf('camera') !== -1;
                    if (isAudio) {
                        micMuted = publication.isMuted === true || (publication.track && publication.track.isMuted === true);
                    }
                    if (isVideo && !isAudioCall) {
                        cameraOff = !(publication.track) || publication.isMuted === true || (publication.track && publication.track.isMuted === true);
                    }
                });
            }
            next.micMuted = micMuted;
            next.cameraOff = cameraOff;
            participantState[key] = next;
        }

        function attachAudioTrack(track, userId) {
            const key = String(userId);
            if (!participantState[key]) {
                participantState[key] = {user_id: userId};
            }
            if (participantState[key].audioTrackSid && participantState[key].audioTrackSid === track.sid) {
                return;
            }
            if (participantState[key].audioElements) {
                participantState[key].audioElements.forEach(function (element) {
                    element.remove();
                });
            }
            if (!participantState[key].audioElements) {
                participantState[key].audioElements = [];
            }
            const element = track.attach();
            element.autoplay = true;
            element.playsInline = true;
            participantState[key].audioTrackSid = track.sid || '';
            participantState[key].audioElements.push(element);
            audioSink.appendChild(element);
        }

        function detachAudioTrack(track, userId) {
            const key = String(userId);
            if (!participantState[key]) {
                return;
            }
            track.detach().forEach(function (element) {
                element.remove();
            });
            participantState[key].audioElements = [];
            participantState[key].audioTrackSid = '';
        }

        function attachVideoTrack(track, userId) {
            const key = String(userId);
            if (!participantState[key]) {
                participantState[key] = {user_id: userId};
            }
            participantState[key].videoTrack = track;
            participantState[key].cameraOff = false;
            renderParticipantGrid();
        }

        function detachVideoTrack(track, userId) {
            const key = String(userId);
            if (!participantState[key]) {
                return;
            }
            track.detach().forEach(function (element) {
                element.remove();
            });
            participantState[key].videoTrack = null;
            participantState[key].videoElement = null;
            participantState[key].cameraOff = true;
            renderParticipantGrid();
        }

        function syncCallState() {
            return fetch(syncCallUrl + '&_t=' + encodeURIComponent(Date.now()), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (!data || data.status !== 200 || String(data.call_status || '') !== 'active') {
                    if (!isLeaving) {
                        window.location.href = redirectUrl;
                    }
                    return;
                }
                if (nameNode && data.group_name) {
                    nameNode.textContent = data.group_name;
                }
                setStatus((parseInt(data.participant_count || 0, 10) > 1) ? 'Group call in progress' : 'Waiting for members');
                applyServerParticipants(data.participants || []);
                ensureLocalParticipant();
                renderParticipantGrid();
            }).catch(function () {});
        }

        function startSyncLoop() {
            syncCallState();
            if (syncInterval) return;
            syncInterval = setInterval(syncCallState, 3000);
        }

        function joinCallSession() {
            return fetch(joinCallUrl + '&_t=' + encodeURIComponent(Date.now()), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                return !!(data && data.status === 200);
            }).catch(function () {
                return false;
            });
        }

        function reportLeave() {
            if (hasReportedLeave) {
                return Promise.resolve();
            }
            hasReportedLeave = true;
            try {
                return fetch(leaveCallUrl + '&_t=' + encodeURIComponent(Date.now()), {
                    method: 'GET',
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                }).catch(function () {});
            } catch (err) {
                return Promise.resolve();
            }
        }

        function closeMemberModal() {
            memberModal.classList.remove('open');
            memberList.innerHTML = '';
        }

        function openMemberModal() {
            btnMemberSubmit.disabled = false;
            fetch(candidatesUrl + '&_t=' + encodeURIComponent(Date.now()), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                memberList.innerHTML = '';
                if (!data || data.status !== 200 || !data.candidates || data.candidates.length === 0) {
                    btnMemberSubmit.disabled = true;
                    memberList.innerHTML = '<div class="gcall-member-row"><div><div class="gcall-member-name">No members available to invite</div><div class="gcall-member-meta">Everyone eligible is already in or already invited.</div></div></div>';
                    memberModal.classList.add('open');
                    return;
                }
                data.candidates.forEach(function (candidate) {
                    const row = document.createElement('label');
                    row.className = 'gcall-member-row';
                    row.innerHTML = '<input type="checkbox" value="' + candidate.user_id + '">' + (candidate.avatar ? '<img src="' + candidate.avatar + '" alt="">' : '<div style="width:42px;height:42px;border-radius:999px;display:flex;align-items:center;justify-content:center;background:#dbe4ff;color:#4656d9;font-weight:700;">' + getInitials(candidate.name) + '</div>') + '<div><div class="gcall-member-name">' + candidate.name + '</div><div class="gcall-member-meta">@' + (candidate.username || '') + '</div></div>';
                    memberList.appendChild(row);
                });
                memberModal.classList.add('open');
            }).catch(function () {
                showToast('Could not load group members.');
            });
        }

        async function connectRoom() {
            ensureLocalParticipant();
            renderParticipantGrid();
            if (!livekitConfigured) {
                setStatus('LiveKit is not configured');
                return;
            }
            if (!window.LivekitClient || !wsUrl || !token) {
                setStatus('Could not load LiveKit client');
                return;
            }
            const joined = await joinCallSession();
            if (!joined) {
                window.location.href = redirectUrl;
                return;
            }
            room = new LivekitClient.Room({adaptiveStream:true,dynacast:true});
            room.on(LivekitClient.RoomEvent.ParticipantConnected, function (participant) {
                updateParticipantFromPublications(participant, false);
                syncCallState();
                renderParticipantGrid();
            });
            room.on(LivekitClient.RoomEvent.ParticipantDisconnected, function (participant) {
                updateParticipantFromPublications(participant, false);
                syncCallState();
            });
            room.on(LivekitClient.RoomEvent.TrackSubscribed, function (track, publication, participant) {
                const meta = parseParticipantMeta(participant);
                if (!meta.user_id) return;
                if (track.kind === 'audio') {
                    attachAudioTrack(track, meta.user_id);
                } else if (track.kind === 'video') {
                    attachVideoTrack(track, meta.user_id);
                }
                updateParticipantFromPublications(participant, false);
                renderParticipantGrid();
            });
            room.on(LivekitClient.RoomEvent.TrackUnsubscribed, function (track, publication, participant) {
                const meta = parseParticipantMeta(participant);
                if (!meta.user_id) return;
                if (track.kind === 'audio') {
                    detachAudioTrack(track, meta.user_id);
                } else if (track.kind === 'video') {
                    detachVideoTrack(track, meta.user_id);
                }
                updateParticipantFromPublications(participant, false);
                renderParticipantGrid();
            });
            room.on(LivekitClient.RoomEvent.TrackMuted, function (publication, participant) {
                updateParticipantFromPublications(participant, participant === room.localParticipant);
                renderParticipantGrid();
            });
            room.on(LivekitClient.RoomEvent.TrackUnmuted, function (publication, participant) {
                updateParticipantFromPublications(participant, participant === room.localParticipant);
                renderParticipantGrid();
            });
            room.on(LivekitClient.RoomEvent.LocalTrackPublished, function (publication, participant) {
                updateParticipantFromPublications(participant || room.localParticipant, true);
                if (publication.track) {
                    if (publication.track.kind === 'video') {
                        attachVideoTrack(publication.track, displayUserId);
                    }
                }
                renderParticipantGrid();
            });
            room.on(LivekitClient.RoomEvent.LocalTrackUnpublished, function (publication) {
                if (!publication.track) return;
                if (publication.track.kind === 'video') {
                    detachVideoTrack(publication.track, displayUserId);
                }
                updateParticipantFromPublications(room.localParticipant, true);
                renderParticipantGrid();
            });
            room.on(LivekitClient.RoomEvent.Reconnecting, function () {
                setStatus('Reconnecting...');
            });
            room.on(LivekitClient.RoomEvent.Reconnected, function () {
                setStatus('Group call in progress');
                syncCallState();
            });
            room.on(LivekitClient.RoomEvent.Disconnected, function () {
                if (!isLeaving) {
                    setStatus('Disconnected');
                    syncCallState();
                }
            });
            try {
                await room.connect(wsUrl, token);
                await room.localParticipant.setMicrophoneEnabled(true);
                if (!isAudioCall) {
                    await room.localParticipant.setCameraEnabled(true);
                }
                updateParticipantFromPublications(room.localParticipant, true);
                room.localParticipant.trackPublications.forEach(function (publication) {
                    if (!publication.track) return;
                    if (publication.track.kind === 'video') {
                        attachVideoTrack(publication.track, displayUserId);
                    }
                });
                room.remoteParticipants.forEach(function (participant) {
                    updateParticipantFromPublications(participant, false);
                    participant.trackPublications.forEach(function (publication) {
                        if (!publication.track) return;
                        const meta = parseParticipantMeta(participant);
                        if (!meta.user_id) return;
                        if (publication.track.kind === 'audio') {
                            attachAudioTrack(publication.track, meta.user_id);
                        } else if (publication.track.kind === 'video') {
                            attachVideoTrack(publication.track, meta.user_id);
                        }
                    });
                });
                setStatus((initialParticipants && initialParticipants.length > 1) ? 'Group call in progress' : 'Waiting for members');
                renderParticipantGrid();
                startSyncLoop();
            } catch (error) {
                setStatus((error && error.message) ? error.message : 'Could not connect to LiveKit');
            }
        }

        document.getElementById('gcall-member-close').addEventListener('click', closeMemberModal);
        document.getElementById('gcall-member-cancel').addEventListener('click', closeMemberModal);
        memberModal.addEventListener('click', function (event) {
            if (event.target === memberModal) {
                closeMemberModal();
            }
        });

        btnMemberSubmit.addEventListener('click', function () {
            const selected = Array.prototype.slice.call(memberList.querySelectorAll('input[type="checkbox"]:checked')).map(function (checkbox) {
                return checkbox.value;
            });
            if (selected.length === 0) {
                closeMemberModal();
                return;
            }
            btnMemberSubmit.disabled = true;
            fetch(addMembersUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'call_id=' + encodeURIComponent(callId) + '&user_ids=' + encodeURIComponent(selected.join(','))
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (data && data.status === 200) {
                    showToast('Invitations sent.');
                    closeMemberModal();
                }
            }).catch(function () {
                showToast('Could not send invitations.');
            }).finally(function () {
                btnMemberSubmit.disabled = false;
            });
        });

        btnMic.classList.add('active');
        btnSpeaker.classList.add('active');

        btnMic.addEventListener('click', async function () {
            if (!room) return;
            microphoneEnabled = !microphoneEnabled;
            await room.localParticipant.setMicrophoneEnabled(microphoneEnabled);
            btnMic.classList.toggle('muted', !microphoneEnabled);
            btnMic.classList.toggle('active', microphoneEnabled);
            updateParticipantFromPublications(room.localParticipant, true);
            participantState[String(displayUserId)].micMuted = !microphoneEnabled;
            renderParticipantGrid();
        });

        btnSpeaker.addEventListener('click', async function () {
            speakerEnabled = !speakerEnabled;
            await applySpeakerMode(speakerEnabled);
            btnSpeaker.classList.toggle('active', speakerEnabled);
        });

        if (btnCam) {
            btnCam.addEventListener('click', async function () {
                if (!room) return;
                cameraEnabled = !cameraEnabled;
                await room.localParticipant.setCameraEnabled(cameraEnabled);
                btnCam.classList.toggle('muted', !cameraEnabled);
                btnCam.classList.toggle('active', cameraEnabled);
                participantState[String(displayUserId)].cameraOff = !cameraEnabled;
                renderParticipantGrid();
            });
        }

        btnAdd.addEventListener('click', function () {
            openMemberModal();
        });

        btnHangup.addEventListener('click', function () {
            isLeaving = true;
            reportLeave().finally(function () {
                if (room) {
                    try { room.disconnect(); } catch (err) {}
                }
                window.location.href = redirectUrl;
            });
        });

        window.addEventListener('beforeunload', function () {
            isLeaving = true;
            reportLeave();
        });

        applyServerParticipants(initialParticipants || []);
        ensureLocalParticipant();
        renderParticipantGrid();
        startCallTimer();
        connectRoom();
    </script>
</body>
</html>
