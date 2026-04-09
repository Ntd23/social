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

$roomRequest = isset($_GET['room']) ? $_GET['room'] : rand(100, 999);
$roomRequest = is_string($roomRequest) ? trim($roomRequest) : $roomRequest;
$user_id = intval($wo['user']['user_id']);

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

$isAudioCall = (isset($_GET['type']) && $_GET['type'] == 'audio');
$callType = $isAudioCall ? 'audio' : 'video';
$roomName = 'wowonder' . md5($roomRequest);
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

function Wo_LiveKitNormalizeMediaUrl($media)
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

function Wo_LiveKitParseServerUrl($value)
{
    if (empty($value) || !is_string($value)) {
        return '';
    }
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (strpos($value, '://') === false) {
        $value = 'wss://' . $value;
    }
    $parts = @parse_url($value);
    if (empty($parts['host'])) {
        return '';
    }
    $scheme = !empty($parts['scheme']) ? strtolower($parts['scheme']) : 'wss';
    if ($scheme === 'https') {
        $scheme = 'wss';
    } else if ($scheme === 'http') {
        $scheme = 'ws';
    }
    $host = $parts['host'];
    if (!empty($parts['port'])) {
        $host .= ':' . intval($parts['port']);
    }
    $path = !empty($parts['path']) ? rtrim($parts['path'], '/') : '';
    return $scheme . '://' . $host . $path;
}

$callMeta = array(
    'id' => (!empty($_GET['id']) ? intval($_GET['id']) : 0),
    'type' => $callType,
    'provider' => 'livekit'
);

$callSource = false;
if (!empty($callMeta['id'])) {
    $callSource = Wo_GetCallSourceById($callMeta['id'], $callType);
}
if (empty($callSource) && !empty($roomRequest)) {
    $callSource = Wo_GetCallSourceByRoomName($roomRequest, $user_id, $callType);
}
if (!empty($callSource)) {
    $callMeta['id'] = intval($callSource['id']);
    $callMeta['type'] = !empty($callSource['call_type']) ? $callSource['call_type'] : $callType;
    $callMeta['provider'] = 'livekit';
    $callType = $callMeta['type'];
    $isAudioCall = ($callType === 'audio');
}

if (!empty($callSource)) {
    $call_status = isset($callSource['status']) ? $callSource['status'] : '';
    $call_declined = intval(!empty($callSource['declined']) ? $callSource['declined'] : 0);
    $call_active = intval(!empty($callSource['active']) ? $callSource['active'] : 0);
    $call_claimed_by = intval(!empty($callSource['called']) ? $callSource['called'] : 0);
    $call_claim_id = Wo_GetCallSessionClaim($user_id);
    $is_caller_join = (intval($callSource['from_id']) === $user_id);
    $is_receiver_join = (intval($callSource['to_id']) === $user_id);
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
$callLogPayload = array();
if (!empty($callSource)) {
    $callLogPayload = Wo_GetCallLogPayload(
        intval($callMeta['id']),
        $callType,
        (!empty($callSource['provider']) ? $callSource['provider'] : 'livekit'),
        intval(!empty($callSource['from_id']) ? $callSource['from_id'] : 0),
        intval(!empty($callSource['to_id']) ? $callSource['to_id'] : 0)
    );
}
$callStartedAt = !empty($callLogPayload['started_at']) ? intval($callLogPayload['started_at']) : 0;
$serverNow = time();
$initialElapsedSeconds = ($callStartedAt > 0) ? max(0, $serverNow - $callStartedAt) : 0;

$user_name = $wo['user']['name'];
$avatar = !empty($wo['user']['avatar']) ? Wo_LiveKitNormalizeMediaUrl($wo['user']['avatar']) : '';
$avatarHost = !empty($avatar) ? parse_url($avatar, PHP_URL_HOST) : '';
if (!empty($avatarHost)) {
    $avatarHost = strtolower(trim($avatarHost, '[]'));
    if ($avatarHost === 'localhost' || $avatarHost === '::1' || substr($avatarHost, -5) === '.test' || substr($avatarHost, -6) === '.local' || preg_match('/^(127\.|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $avatarHost)) {
        $avatar = '';
    }
}

$livekitWsUrl = Wo_LiveKitParseServerUrl(!empty($wo['config']['livekit_host']) ? $wo['config']['livekit_host'] : '');
$livekitApiKey = !empty($wo['config']['livekit_api_key']) ? trim($wo['config']['livekit_api_key']) : '';
$livekitApiSecret = !empty($wo['config']['livekit_api_secret']) ? trim($wo['config']['livekit_api_secret']) : '';
$livekitConfigured = ($livekitWsUrl !== '' && $livekitApiKey !== '' && $livekitApiSecret !== '');
$livekitIdentity = 'user_' . $user_id . '_' . substr(sha1(session_id() . '|' . $roomName), 0, 12);
$livekitToken = '';

if ($livekitConfigured) {
    $payload = array(
        'iss' => $livekitApiKey,
        'sub' => $livekitIdentity,
        'nbf' => time() - 300,
        'exp' => time() + 3600,
        'name' => $user_name,
        'metadata' => json_encode(array(
            'user_id' => (string) $user_id,
            'name' => $user_name,
            'avatar' => $avatar
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'video' => array(
            'roomJoin' => true,
            'room' => $roomName,
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true
        )
    );
    $livekitToken = JWT::encode($payload, $livekitApiSecret, 'HS256');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dang goi... | <?php echo $wo['config']['siteTitle']; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js"></script>
    <style>
        :root{--toolbar-height:118px;--panel:#0d1120;--panel-soft:rgba(35,40,57,.88);--danger:#ef2f2f;--danger-shadow:rgba(239,47,47,.35);--accent:#7a88ff;--text:#f8fafc;--muted:#98a4c0;--border:rgba(148,163,184,.18)}
        *{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%;color:var(--text);overflow:hidden;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:radial-gradient(circle at 50% 35%,rgba(96,110,255,.16) 0%,rgba(96,110,255,.06) 22%,rgba(7,10,20,0) 48%),linear-gradient(180deg,#090b12 0%,#0c1020 46%,#06070c 100%)}
        .lk-shell{min-height:100vh;min-height:100dvh;padding-bottom:var(--toolbar-height);position:relative}
        .lk-stage{position:absolute;inset:0;display:block;height:100vh;height:100dvh;overflow:hidden;background:radial-gradient(circle at 50% 20%,rgba(122,136,255,.16) 0%,rgba(11,14,24,0) 34%),linear-gradient(180deg,#0b1020 0%,#090c16 100%)}
        .lk-stage::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(5,8,16,.34) 0%,rgba(5,8,16,.08) 24%,rgba(5,8,16,.08) 56%,rgba(5,8,16,.66) 100%);pointer-events:none;z-index:1}
        .lk-tile{position:relative;overflow:hidden;border-radius:24px;border:1px solid var(--border);background:linear-gradient(180deg,rgba(15,23,42,.88) 0%,rgba(17,24,39,.98) 100%);min-height:240px;display:flex;align-items:center;justify-content:center;box-shadow:0 18px 40px rgba(2,6,23,.35)}
        .lk-tile video{width:100%;height:100%;display:block;object-fit:cover;background:#020617}
        body:not(.lk-audio-mode) .lk-stage .lk-tile{position:absolute;inset:0;min-height:100vh;min-height:100dvh;height:100%;border:0;border-radius:0;box-shadow:none;background:#020617}
        body:not(.lk-audio-mode) .lk-stage .lk-label{display:none}
        .lk-self{position:fixed;top:32px;right:32px;width:min(26vw,210px);height:min(34vw,286px);z-index:14;border-radius:34px;overflow:hidden;border:2px solid rgba(255,255,255,.26);background:rgba(15,23,42,.54);box-shadow:0 24px 40px rgba(2,6,23,.34);backdrop-filter:blur(12px)}
        .lk-self .lk-tile{position:absolute;inset:0;min-height:100%;width:100%;height:100%;border:0;border-radius:0;box-shadow:none;background:transparent}
        .lk-self video{width:100%;height:100%;object-fit:cover}
        .lk-self .lk-label{display:none}
        .lk-empty,.lk-error{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;text-align:center;padding:24px;color:var(--muted);font-size:16px;background:rgba(15,23,42,.6)}
        .lk-error{color:#fecaca;background:rgba(127,29,29,.56)}
        .lk-label{position:absolute;left:12px;right:12px;bottom:12px;display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:rgba(15,23,42,.74);font-size:13px;color:var(--text);backdrop-filter:blur(10px)}
        .lk-badge{width:9px;height:9px;border-radius:999px;background:#22c55e}
        .lk-mute-indicator{position:absolute;top:14px;right:14px;width:40px;height:40px;border-radius:999px;background:rgba(127,29,29,.96);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 12px 24px rgba(15,23,42,.28);border:1px solid rgba(255,255,255,.14);z-index:2}
        .lk-mute-indicator svg{width:20px;height:20px;fill:currentColor}
        .lk-hidden{display:none!important}
        .lk-video-ui,.lk-audio-ui,.lk-audio-sink,.lk-toast{display:none}
        body:not(.lk-audio-mode) .lk-video-ui{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:space-between;padding:42px 24px calc(var(--toolbar-height) + 34px);z-index:12;pointer-events:none}
        .lk-video-top{display:flex;justify-content:center}
        .lk-video-meta{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;max-width:min(90vw,520px)}
        .lk-video-name{font-size:clamp(34px,4.2vw,56px);font-weight:600;line-height:1.02;letter-spacing:-.04em;text-shadow:0 16px 40px rgba(2,6,23,.44)}
        .lk-video-status{color:rgba(226,232,240,.82);font-size:15px;letter-spacing:.22em;text-transform:uppercase}
        .lk-video-timer{display:inline-flex;align-items:center;justify-content:center;min-width:112px;padding:16px 28px;border-radius:999px;background:rgba(111,126,255,.12);border:1px solid rgba(122,136,255,.3);backdrop-filter:blur(18px);color:#eef2ff;font-size:20px;font-weight:500;letter-spacing:.04em;box-shadow:0 18px 36px rgba(41,52,125,.18);margin-top:10px}
        .lk-remote-mute-note{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:24px;margin-top:8px;color:rgba(248,250,252,.88);font-size:14px;font-weight:500;letter-spacing:.01em}
        .lk-remote-mute-note svg{width:16px;height:16px;fill:currentColor}
        .lk-video-poster{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;z-index:0;pointer-events:none}
        .lk-video-poster-inner{display:flex;flex-direction:column;align-items:center;gap:18px}
        .lk-video-avatar-wrap{position:relative;width:min(38vw,340px);height:min(38vw,340px);display:flex;align-items:center;justify-content:center}
        .lk-video-avatar-wrap .lk-audio-glow{inset:-34px}
        .lk-video-avatar-wrap .lk-audio-ring:before{inset:-26px}
        .lk-video-avatar-wrap .lk-audio-ring:after{inset:-52px}
        .lk-video-avatar{position:relative;width:100%;height:100%;padding:12px;border-radius:999px;background:linear-gradient(180deg,#8da0ff 0%,#5f6fe4 100%);box-shadow:0 28px 56px rgba(72,88,214,.3)}
        .lk-video-avatar-inner{width:100%;height:100%;border-radius:999px;overflow:hidden;border:4px solid rgba(5,10,28,.88);background:linear-gradient(180deg,#f5f7fb 0%,#d6deeb 100%);display:flex;align-items:center;justify-content:center}
        .lk-video-avatar-inner img{width:100%;height:100%;object-fit:cover;display:block}
        .lk-video-avatar-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:110px;font-weight:700;color:#26324d;text-transform:uppercase}
        .lk-video-avatar-wrap .lk-mute-indicator{top:20px;right:20px;width:56px;height:56px}
        .lk-video-avatar-wrap .lk-mute-indicator svg{width:26px;height:26px}
        body.lk-audio-mode .lk-stage,body.lk-audio-mode .lk-self{display:none!important}
        body.lk-audio-mode .lk-audio-ui{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:space-between;padding:18px 20px 0}
        .lk-audio-center{flex:1;display:flex;align-items:center;justify-content:center;padding:24px 0 10px}
        .lk-audio-hero{width:min(100%,460px);display:flex;flex-direction:column;align-items:center;text-align:center;gap:18px}
        .lk-audio-avatar-wrap{position:relative;width:250px;height:250px;display:flex;align-items:center;justify-content:center}
        .lk-audio-glow{position:absolute;inset:-28px;border-radius:999px;background:radial-gradient(circle,rgba(111,126,255,.22) 0%,rgba(111,126,255,.06) 40%,rgba(111,126,255,0) 68%);filter:blur(14px)}
        .lk-audio-ring,.lk-audio-ring:before,.lk-audio-ring:after{content:"";position:absolute;border-radius:999px;inset:0;border:1px solid rgba(104,119,255,.16)}
        .lk-audio-ring:before{inset:-22px;border-color:rgba(86,100,237,.14)}
        .lk-audio-ring:after{inset:-44px;border-color:rgba(69,84,214,.1)}
        .lk-audio-avatar{position:relative;width:100%;height:100%;padding:10px;border-radius:999px;background:linear-gradient(180deg,#8da0ff 0%,#5f6fe4 100%);box-shadow:0 20px 50px rgba(72,88,214,.26)}
        .lk-audio-avatar-inner{width:100%;height:100%;border-radius:999px;overflow:hidden;background:linear-gradient(180deg,#f5f7fb 0%,#d6deeb 100%);border:4px solid rgba(5,10,28,.9);display:flex;align-items:center;justify-content:center}
        .lk-audio-avatar-inner img{width:100%;height:100%;object-fit:cover;display:block}
        .lk-audio-avatar-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:78px;font-weight:700;color:#26324d;text-transform:uppercase}
        .lk-audio-avatar-wrap .lk-mute-indicator{top:20px;right:20px;width:52px;height:52px}
        .lk-audio-avatar-wrap .lk-mute-indicator svg{width:24px;height:24px}
        .lk-audio-name{font-size:clamp(38px,6vw,64px);font-weight:600;line-height:1.02;letter-spacing:-.04em;text-shadow:0 8px 28px rgba(2,6,23,.34)}
        .lk-audio-status{color:var(--muted);font-size:15px;letter-spacing:.04em;text-transform:uppercase}
        .lk-audio-timer{color:var(--accent);font-size:22px;font-weight:500;letter-spacing:.08em}
        .lk-audio-sub{color:rgba(226,232,240,.72);font-size:14px}
        .lk-audio-sink{position:absolute;width:0;height:0;overflow:hidden}
        #custom-toolbar{position:fixed;left:50%;transform:translateX(-50%);bottom:max(18px,env(safe-area-inset-bottom));min-height:200px;display:flex;align-items:center;justify-content:center;gap:22px;padding:16px 30px;background:none;border:0;box-shadow:none;z-index:20}
        .call-btn{position:relative;width:100px;height:100px;border-radius:999px;border:0;background:var(--panel-soft);color:#fff;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:transform .15s ease,background .15s ease,box-shadow .15s ease;box-shadow:0 8px 20px rgba(0,0,0,.22)}
        .call-btn.active{background:rgba(122,136,255,.16);box-shadow:0 0 0 1px rgba(123,140,255,.35) inset,0 10px 26px rgba(52,66,183,.22)}
        .call-btn.muted{background:rgba(147,33,43,.95)}
        .call-btn.muted::after{content:"";position:absolute;width:40px;height:4px;border-radius:999px;background:#fff;transform:rotate(-45deg);box-shadow:0 0 0 2px rgba(0,0,0,.06)}
        .btn-hangup{width:150px;height:150px;background:var(--danger);box-shadow:0 18px 34px var(--danger-shadow)}
        .call-btn svg{width:34px;height:34px;fill:currentColor}
        .lk-toast{position:fixed;left:50%;top:28px;transform:translateX(-50%);padding:12px 18px;border-radius:999px;background:rgba(8,11,18,.92);color:#fff;font-size:13px;box-shadow:0 16px 36px rgba(0,0,0,.28);border:1px solid rgba(255,255,255,.05);opacity:0;pointer-events:none;transition:opacity .18s ease;z-index:40}
        .lk-toast.show{opacity:1;display:block}
        @media (max-width:768px){:root{--toolbar-height:128px}.lk-video-ui{padding:24px 14px calc(var(--toolbar-height) + 30px)}.lk-video-name{font-size:clamp(30px,9vw,46px)}.lk-video-status{font-size:13px;letter-spacing:.18em}.lk-video-timer{min-width:96px;padding:14px 24px;font-size:18px}.lk-video-avatar-wrap{width:220px;height:220px}.lk-video-avatar-fallback{font-size:78px}.lk-self{top:22px;right:14px;width:132px;height:176px;border-radius:28px}.lk-audio-ui{padding:14px 14px 0}.lk-audio-avatar-wrap{width:210px;height:210px}.lk-audio-name{font-size:clamp(34px,9vw,52px)}#custom-toolbar{width:calc(100% - 18px);gap:16px;padding:14px 16px;min-height:200px}.call-btn{width:150px;height:150px}.btn-hangup{width:150px;height:150px}.call-btn svg{width:30px;height:30px}}
    </style>
</head>
<body>
    <div class="lk-shell">
        <div id="lk-stage" class="lk-stage">
            <div id="lk-empty" class="lk-tile"><div class="lk-empty">Dang ket noi...</div></div>
        </div>
        <div id="lk-video-ui" class="lk-video-ui">
            <div class="lk-video-top">
                <div class="lk-video-meta">
                    <div id="lk-video-name" class="lk-video-name">Dang ket noi</div>
                    <div id="lk-video-status" class="lk-video-status">Dang cho nguoi kia tham gia</div>
                    <div id="lk-video-timer" class="lk-video-timer">00:00</div>
                    <div id="lk-video-mute-note" class="lk-remote-mute-note lk-hidden"><svg viewBox="0 0 24 24"><path d="M19 11H17.91C17.7 12.49 16.97 13.81 15.91 14.82L17.33 16.24C18.67 14.91 19.54 13.08 19.73 11M4.27 3L3 4.27L9.01 10.28V11A3 3 0 0 0 12 14C12.23 14 12.45 13.97 12.66 13.92L14.31 15.57C13.61 15.84 12.83 16 12 16A5 5 0 0 1 7 11H5A7 7 0 0 0 11 17.93V21H13V17.93C14.08 17.78 15.08 17.42 15.97 16.9L19.73 20.66L21 19.39L4.27 3M12 3A3 3 0 0 1 15 6V9.18L11.12 5.3C11.39 5.11 11.68 5 12 5A1 1 0 0 1 13 6V11A1 1 0 0 1 12.7 11.71L15.66 14.67C16.5 13.76 17 12.54 17 11V6A5 5 0 0 0 12 1C11.14 1 10.33 1.2 9.61 1.56L11.08 3.03C11.37 3 11.68 3 12 3Z"/></svg><span id="lk-video-mute-text">Nguoi kia dang tat mic</span></div>
                </div>
            </div>
            <div id="lk-video-poster" class="lk-video-poster">
                <div class="lk-video-poster-inner">
                    <div class="lk-video-avatar-wrap">
                        <div class="lk-audio-glow"></div>
                        <div class="lk-audio-ring"></div>
                        <div class="lk-video-avatar">
                            <div class="lk-video-avatar-inner">
                                <img id="lk-video-avatar-img" src="" alt="" class="lk-hidden">
                                <div id="lk-video-avatar-fallback" class="lk-video-avatar-fallback">?</div>
                            </div>
                        </div>
                        <div id="lk-video-mute-indicator" class="lk-mute-indicator lk-hidden" aria-label="Mic da tat"><svg viewBox="0 0 24 24"><path d="M19 11H17.91C17.7 12.49 16.97 13.81 15.91 14.82L17.33 16.24C18.67 14.91 19.54 13.08 19.73 11M4.27 3L3 4.27L9.01 10.28V11A3 3 0 0 0 12 14C12.23 14 12.45 13.97 12.66 13.92L14.31 15.57C13.61 15.84 12.83 16 12 16A5 5 0 0 1 7 11H5A7 7 0 0 0 11 17.93V21H13V17.93C14.08 17.78 15.08 17.42 15.97 16.9L19.73 20.66L21 19.39L4.27 3M12 3A3 3 0 0 1 15 6V9.18L11.12 5.3C11.39 5.11 11.68 5 12 5A1 1 0 0 1 13 6V11A1 1 0 0 1 12.7 11.71L15.66 14.67C16.5 13.76 17 12.54 17 11V6A5 5 0 0 0 12 1C11.14 1 10.33 1.2 9.61 1.56L11.08 3.03C11.37 3 11.68 3 12 3Z"/></svg></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="lk-self" class="lk-self lk-hidden"></div>
        <div id="lk-audio-ui" class="lk-audio-ui">
            <div class="lk-audio-center">
                <div class="lk-audio-hero">
                    <div class="lk-audio-avatar-wrap">
                        <div class="lk-audio-glow"></div>
                        <div class="lk-audio-ring"></div>
                        <div class="lk-audio-avatar">
                            <div class="lk-audio-avatar-inner">
                                <img id="lk-audio-avatar-img" src="" alt="" class="lk-hidden">
                                <div id="lk-audio-avatar-fallback" class="lk-audio-avatar-fallback">?</div>
                            </div>
                        </div>
                        <div id="lk-audio-mute-indicator" class="lk-mute-indicator lk-hidden" aria-label="Mic da tat"><svg viewBox="0 0 24 24"><path d="M19 11H17.91C17.7 12.49 16.97 13.81 15.91 14.82L17.33 16.24C18.67 14.91 19.54 13.08 19.73 11M4.27 3L3 4.27L9.01 10.28V11A3 3 0 0 0 12 14C12.23 14 12.45 13.97 12.66 13.92L14.31 15.57C13.61 15.84 12.83 16 12 16A5 5 0 0 1 7 11H5A7 7 0 0 0 11 17.93V21H13V17.93C14.08 17.78 15.08 17.42 15.97 16.9L19.73 20.66L21 19.39L4.27 3M12 3A3 3 0 0 1 15 6V9.18L11.12 5.3C11.39 5.11 11.68 5 12 5A1 1 0 0 1 13 6V11A1 1 0 0 1 12.7 11.71L15.66 14.67C16.5 13.76 17 12.54 17 11V6A5 5 0 0 0 12 1C11.14 1 10.33 1.2 9.61 1.56L11.08 3.03C11.37 3 11.68 3 12 3Z"/></svg></div>
                    </div>
                    <div id="lk-audio-name" class="lk-audio-name">Dang ket noi</div>
                    <div id="lk-audio-status" class="lk-audio-status">Dang cho nguoi kia tham gia</div>
                    <div id="lk-audio-timer" class="lk-audio-timer">00:00</div>
                    <div id="lk-audio-mute-note" class="lk-remote-mute-note lk-hidden"><svg viewBox="0 0 24 24"><path d="M19 11H17.91C17.7 12.49 16.97 13.81 15.91 14.82L17.33 16.24C18.67 14.91 19.54 13.08 19.73 11M4.27 3L3 4.27L9.01 10.28V11A3 3 0 0 0 12 14C12.23 14 12.45 13.97 12.66 13.92L14.31 15.57C13.61 15.84 12.83 16 12 16A5 5 0 0 1 7 11H5A7 7 0 0 0 11 17.93V21H13V17.93C14.08 17.78 15.08 17.42 15.97 16.9L19.73 20.66L21 19.39L4.27 3M12 3A3 3 0 0 1 15 6V9.18L11.12 5.3C11.39 5.11 11.68 5 12 5A1 1 0 0 1 13 6V11A1 1 0 0 1 12.7 11.71L15.66 14.67C16.5 13.76 17 12.54 17 11V6A5 5 0 0 0 12 1C11.14 1 10.33 1.2 9.61 1.56L11.08 3.03C11.37 3 11.68 3 12 3Z"/></svg><span id="lk-audio-mute-text">Nguoi kia dang tat mic</span></div>
                </div>
            </div>
            <div id="lk-audio-sink" class="lk-audio-sink"></div>
        </div>
    </div>
    <div id="custom-toolbar">
        <?php if ($isAudioCall) { ?>
        <button class="call-btn" id="btn-mic" title="Tat/Bat mic"><svg viewBox="0 0 24 24"><path d="M12,2A3,3 0 0,1 15,5V11A3,3 0 0,1 12,14A3,3 0 0,1 9,11V5A3,3 0 0,1 12,2M19,11C19,14.53 16.39,17.44 13,17.93V21H11V17.93C7.61,17.44 5,14.53 5,11H7A5,5 0 0,0 12,16A5,5 0 0,0 17,11H19Z"/></svg></button>
        <button class="call-btn" id="btn-speaker" title="Loa ngoai"><svg viewBox="0 0 24 24"><path d="M14,3.23V20.77C14,21.55 13.16,22.03 12.5,21.65L7,18H3C1.9,18 1,17.1 1,16V8C1,6.9 1.9,6 3,6H7L12.5,2.35C13.16,1.97 14,2.45 14,3.23M16.5,12C16.5,10.23 15.73,8.63 14.5,7.53V16.46C15.73,15.37 16.5,13.76 16.5,12M14.5,3.97V6.18C17.39,7.04 19.5,9.71 19.5,12.5C19.5,15.29 17.39,17.96 14.5,18.82V21.03C18.5,20.13 21.5,16.63 21.5,12.5C21.5,8.37 18.5,4.87 14.5,3.97Z"/></svg></button>
        <button class="call-btn btn-hangup" id="btn-hangup" title="Cup may"><svg viewBox="0 0 24 24"><path d="M12,9C10.4,9 8.85,9.25 7.4,9.72V12.82C7.4,13.22 7.17,13.56 6.84,13.72C5.86,14.21 4.97,14.84 4.18,15.57C4,15.75 3.75,15.86 3.5,15.86C3.2,15.86 2.95,15.74 2.77,15.56L0.29,13.08C0.11,12.9 0,12.65 0,12.38C0,12.1 0.11,11.85 0.29,11.67C3.34,8.77 7.46,7 12,7C16.54,7 20.66,8.77 23.71,11.67C23.89,11.85 24,12.1 24,12.38C24,12.65 23.89,12.9 23.71,13.08L21.23,15.56C21.05,15.74 20.8,15.86 20.5,15.86C20.25,15.86 20,15.75 19.82,15.57C19.03,14.84 18.14,14.21 17.16,13.72C16.83,13.56 16.6,13.22 16.6,12.82V9.72C15.15,9.25 13.6,9 12,9Z"/></svg></button>
        <?php } else { ?>
        <button class="call-btn" id="btn-mic" title="Tat/Bat mic"><svg viewBox="0 0 24 24"><path d="M12,2A3,3 0 0,1 15,5V11A3,3 0 0,1 12,14A3,3 0 0,1 9,11V5A3,3 0 0,1 12,2M19,11C19,14.53 16.39,17.44 13,17.93V21H11V17.93C7.61,17.44 5,14.53 5,11H7A5,5 0 0,0 12,16A5,5 0 0,0 17,11H19Z"/></svg></button>
        <button class="call-btn" id="btn-speaker" title="Loa ngoai"><svg viewBox="0 0 24 24"><path d="M14,3.23V20.77C14,21.55 13.16,22.03 12.5,21.65L7,18H3C1.9,18 1,17.1 1,16V8C1,6.9 1.9,6 3,6H7L12.5,2.35C13.16,1.97 14,2.45 14,3.23M16.5,12C16.5,10.23 15.73,8.63 14.5,7.53V16.46C15.73,15.37 16.5,13.76 16.5,12M14.5,3.97V6.18C17.39,7.04 19.5,9.71 19.5,12.5C19.5,15.29 17.39,17.96 14.5,18.82V21.03C18.5,20.13 21.5,16.63 21.5,12.5C21.5,8.37 18.5,4.87 14.5,3.97Z"/></svg></button>
        <button class="call-btn<?php echo $isAudioCall ? ' lk-hidden' : ''; ?>" id="btn-cam" title="Tat/Bat camera"><svg viewBox="0 0 24 24"><path d="M17,10.5V7A1,1 0 0,0 16,6H4A1,1 0 0,0 3,7V17A1,1 0 0,0 4,18H16A1,1 0 0,0 17,17V13.5L21,17.5V6.5L17,10.5Z"/></svg></button>
        <button class="call-btn<?php echo $isAudioCall ? ' lk-hidden' : ''; ?>" id="btn-flip-cam" title="Xoay camera"><svg viewBox="0 0 24 24"><path d="M4 6H16C17.1 6 18 6.9 18 8V10H16V8H4V16H16V14H18V16C18 17.1 17.1 18 16 18H4C2.9 18 2 17.1 2 16V8C2 6.9 2.9 6 4 6M20 5V8H17L20.5 11.5L24 8H21V5H20M20.5 12.5L17 16H20V19H21V16H24L20.5 12.5M9.5 9L13 12L9.5 15V13H6V11H9.5V9Z"/></svg></button>
        <button class="call-btn btn-hangup" id="btn-hangup" title="Cup may"><svg viewBox="0 0 24 24"><path d="M12,9C10.4,9 8.85,9.25 7.4,9.72V12.82C7.4,13.22 7.17,13.56 6.84,13.72C5.86,14.21 4.97,14.84 4.18,15.57C4,15.75 3.75,15.86 3.5,15.86C3.2,15.86 2.95,15.74 2.77,15.56L0.29,13.08C0.11,12.9 0,12.65 0,12.38C0,12.1 0.11,11.85 0.29,11.67C3.34,8.77 7.46,7 12,7C16.54,7 20.66,8.77 23.71,11.67C23.89,11.85 24,12.1 24,12.38C24,12.65 23.89,12.9 23.71,13.08L21.23,15.56C21.05,15.74 20.8,15.86 20.5,15.86C20.25,15.86 20,15.75 19.82,15.57C19.03,14.84 18.14,14.21 17.16,13.72C16.83,13.56 16.6,13.22 16.6,12.82V9.72C15.15,9.25 13.6,9 12,9Z"/></svg></button>
        <?php } ?>
    </div>
    <div id="lk-toast" class="lk-toast"></div>
    <script>
        const redirectUrl = <?php echo json_encode($redirectTarget); ?>;
        const isAudioCall = <?php echo json_encode($isAudioCall); ?>;
        const callMeta = <?php echo json_encode($callMeta); ?>;
        const syncCallTimerUrl = <?php echo json_encode($wo['config']['site_url'] . '/requests.php'); ?>;
        const callStartedAt = <?php echo json_encode($callStartedAt); ?>;
        const serverNowAtRender = <?php echo json_encode($serverNow); ?>;
        const initialElapsedSeconds = <?php echo json_encode($initialElapsedSeconds); ?>;
        const closeCallUrl = <?php echo json_encode($wo['config']['site_url'] . '/requests.php'); ?>;
        const wsUrl = <?php echo json_encode($livekitWsUrl); ?>;
        const token = <?php echo json_encode($livekitToken); ?>;
        const livekitConfigured = <?php echo json_encode($livekitConfigured); ?>;
        const displayName = <?php echo json_encode($user_name); ?>;
        const popupStorageKey = 'wo_active_livekit_call';
        const stage = document.getElementById('lk-stage');
        const videoUi = document.getElementById('lk-video-ui');
        const videoName = document.getElementById('lk-video-name');
        const videoStatus = document.getElementById('lk-video-status');
        const videoTimer = document.getElementById('lk-video-timer');
        const videoPoster = document.getElementById('lk-video-poster');
        const videoAvatarImg = document.getElementById('lk-video-avatar-img');
        const videoAvatarFallback = document.getElementById('lk-video-avatar-fallback');
        const videoMuteIndicator = document.getElementById('lk-video-mute-indicator');
        const videoMuteNote = document.getElementById('lk-video-mute-note');
        const videoMuteText = document.getElementById('lk-video-mute-text');
        const selfPreview = document.getElementById('lk-self');
        const audioUi = document.getElementById('lk-audio-ui');
        const audioSink = document.getElementById('lk-audio-sink');
        const audioAvatarImg = document.getElementById('lk-audio-avatar-img');
        const audioAvatarFallback = document.getElementById('lk-audio-avatar-fallback');
        const audioMuteIndicator = document.getElementById('lk-audio-mute-indicator');
        const audioMuteNote = document.getElementById('lk-audio-mute-note');
        const audioMuteText = document.getElementById('lk-audio-mute-text');
        const audioName = document.getElementById('lk-audio-name');
        const audioStatus = document.getElementById('lk-audio-status');
        const audioTimer = document.getElementById('lk-audio-timer');
        const toast = document.getElementById('lk-toast');
        const btnMic = document.getElementById('btn-mic');
        const btnSpeaker = document.getElementById('btn-speaker');
        const btnCam = document.getElementById('btn-cam');
        const btnFlipCam = document.getElementById('btn-flip-cam');
        const btnHangup = document.getElementById('btn-hangup');
        const clientPageLoadedAt = Date.now();
        const hasServerCallStart = (parseInt(callStartedAt || 0, 10) > 0 && parseInt(serverNowAtRender || 0, 10) >= parseInt(callStartedAt || 0, 10));
        const syncedElapsedAtLoad = hasServerCallStart ? Math.max(0, parseInt(initialElapsedSeconds || 0, 10)) : 0;
        let room = null;
        let conferenceJoinedAt = 0;
        let callEndReported = false;
        let callLogPromotedToVideo = false;
        let cameraEnabled = !isAudioCall;
        let microphoneEnabled = true;
        let speakerEnabled = true;
        let toastTimer = null;
        let timerInterval = null;
        let timerSyncInterval = null;
        let presenceInterval = null;
        let remoteDisconnectTimer = null;
        let currentAudioParticipantIdentity = '';
        let currentFacingMode = 'user';

        function getMuteIndicatorMarkup() {
            return '<div class="lk-mute-indicator lk-hidden" data-role="mute-indicator" aria-label="Mic da tat"><svg viewBox="0 0 24 24"><path d="M19 11H17.91C17.7 12.49 16.97 13.81 15.91 14.82L17.33 16.24C18.67 14.91 19.54 13.08 19.73 11M4.27 3L3 4.27L9.01 10.28V11A3 3 0 0 0 12 14C12.23 14 12.45 13.97 12.66 13.92L14.31 15.57C13.61 15.84 12.83 16 12 16A5 5 0 0 1 7 11H5A7 7 0 0 0 11 17.93V21H13V17.93C14.08 17.78 15.08 17.42 15.97 16.9L19.73 20.66L21 19.39L4.27 3M12 3A3 3 0 0 1 15 6V9.18L11.12 5.3C11.39 5.11 11.68 5 12 5A1 1 0 0 1 13 6V11A1 1 0 0 1 12.7 11.71L15.66 14.67C16.5 13.76 17 12.54 17 11V6A5 5 0 0 0 12 1C11.14 1 10.33 1.2 9.61 1.56L11.08 3.03C11.37 3 11.68 3 12 3Z"/></svg></div>';
        }

        if (isAudioCall) {
            document.body.classList.add('lk-audio-mode');
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

        function startCallTimer() {
            if (timerInterval) return;
            const currentDuration = formatDuration(getCallDurationSeconds());
            if (audioTimer) {
                audioTimer.textContent = currentDuration;
            }
            if (videoTimer) {
                videoTimer.textContent = currentDuration;
            }
            timerInterval = window.setInterval(function () {
                if (conferenceJoinedAt > 0) {
                    const liveDuration = formatDuration(getCallDurationSeconds());
                    if (audioTimer) {
                        audioTimer.textContent = liveDuration;
                    }
                    if (videoTimer) {
                        videoTimer.textContent = liveDuration;
                    }
                }
            }, 1000);
        }

        function getCallDurationSeconds() {
            if (conferenceJoinedAt <= 0) {
                return 0;
            }
            return Math.max(0, Math.floor((Date.now() - conferenceJoinedAt) / 1000));
        }

        function syncConferenceJoinedAtFromServer(elapsedSeconds) {
            const safeElapsed = Math.max(0, parseInt(elapsedSeconds || 0, 10));
            if (safeElapsed <= 0) {
                return;
            }
            conferenceJoinedAt = Date.now() - (safeElapsed * 1000);
            if (audioTimer) {
                audioTimer.textContent = formatDuration(safeElapsed);
            }
            if (videoTimer) {
                videoTimer.textContent = formatDuration(safeElapsed);
            }
        }

        function setConferenceJoinedAt() {
            if (hasServerCallStart) {
                conferenceJoinedAt = clientPageLoadedAt - (syncedElapsedAtLoad * 1000);
                return;
            }
            conferenceJoinedAt = Date.now();
        }

        function stopTimerSync() {
            if (timerSyncInterval) {
                clearInterval(timerSyncInterval);
                timerSyncInterval = null;
            }
        }

        function syncTimerWithServer() {
            if (!callMeta || !callMeta.id) {
                return;
            }
            const url = syncCallTimerUrl + '?f=sync_call_timer&id=' + encodeURIComponent(callMeta.id) + '&call_type=' + encodeURIComponent(callMeta.type || (isAudioCall ? 'audio' : 'video')) + '&provider=' + encodeURIComponent(callMeta.provider || 'livekit') + '&_t=' + encodeURIComponent(Date.now());
            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (!data || data.status !== 200) {
                    return;
                }
                const serverElapsed = Math.max(0, parseInt(data.elapsed || 0, 10));
                if (serverElapsed > 0 && (conferenceJoinedAt <= 0 || Math.abs(getCallDurationSeconds() - serverElapsed) > 1)) {
                    syncConferenceJoinedAtFromServer(serverElapsed);
                }
                if (conferenceJoinedAt > 0 && data.in_call !== true && String(data.call_status || '') !== 'answered') {
                    redirectAfterCallEnd('ended', 0);
                }
            }).catch(function () {});
        }

        function startTimerSync() {
            if (timerSyncInterval || !callMeta || !callMeta.id) {
                return;
            }
            syncTimerWithServer();
            timerSyncInterval = window.setInterval(syncTimerWithServer, 8000);
        }

        function getParticipantMeta(participant, isSelf) {
            let metadata = {};
            if (participant && participant.metadata) {
                try {
                    metadata = JSON.parse(participant.metadata);
                } catch (err) {}
            }
            const name = (participant && (participant.name || participant.identity)) || metadata.name || (isSelf ? displayName : 'Nguoi dang goi');
            return {
                name: name || (isSelf ? displayName : 'Nguoi dang goi'),
                avatar: metadata.avatar || ''
            };
        }

        function isAudioPublication(publication) {
            if (!publication) {
                return false;
            }
            if (publication.source && String(publication.source).toLowerCase().indexOf('microphone') !== -1) {
                return true;
            }
            return publication.kind === 'audio';
        }

        function isParticipantMicMuted(participant) {
            if (!participant || !participant.trackPublications) {
                return false;
            }
            let hasAudioTrack = false;
            let muted = false;
            participant.trackPublications.forEach(function (publication) {
                if (!isAudioPublication(publication)) {
                    return;
                }
                hasAudioTrack = true;
                if (publication.isMuted === true || (publication.track && publication.track.isMuted === true)) {
                    muted = true;
                }
            });
            return hasAudioTrack ? muted : false;
        }

        function buildMuteNoticeText(name) {
            const safeName = String(name || '').trim();
            return (safeName || 'Nguoi kia') + ' dang tat mic';
        }

        function setAudioHeroMuteState(isMuted, participantName) {
            if (audioMuteIndicator) {
                audioMuteIndicator.classList.add('lk-hidden');
            }
            if (audioMuteText) {
                audioMuteText.textContent = buildMuteNoticeText(participantName);
            }
            if (audioMuteNote) {
                audioMuteNote.classList.toggle('lk-hidden', !isMuted);
            }
        }

        function setVideoHeroMuteState(isMuted, participantName) {
            if (videoMuteIndicator) {
                videoMuteIndicator.classList.add('lk-hidden');
            }
            if (videoMuteText) {
                videoMuteText.textContent = buildMuteNoticeText(participantName);
            }
            if (videoMuteNote) {
                videoMuteNote.classList.toggle('lk-hidden', !isMuted);
            }
        }

        function updateVideoPosterVisibility() {
            if (!videoPoster || isAudioCall) {
                return;
            }
            const hasRemoteVideo = stage.querySelector('.lk-tile video') !== null;
            videoPoster.classList.toggle('lk-hidden', hasRemoteVideo);
        }

        function updateParticipantMuteIndicator(participant, isSelf) {
            const isMuted = isParticipantMicMuted(participant);
            const participantMeta = getParticipantMeta(participant, isSelf);
            const participantName = participantMeta.name || 'Nguoi kia';
            if (!isSelf && isAudioCall && participant && participant.identity && participant.identity === currentAudioParticipantIdentity) {
                setAudioHeroMuteState(isMuted, participantName);
            }
            if (!isSelf && !isAudioCall && participant && participant.identity && participant.identity === currentAudioParticipantIdentity) {
                setVideoHeroMuteState(isMuted, participantName);
            }
        }

        function getInitials(name) {
            const safe = String(name || '').trim();
            if (!safe) return '?';
            const words = safe.split(/\s+/).filter(Boolean);
            if (words.length === 1) {
                return safe.slice(0, 1).toUpperCase();
            }
            return (words[0].slice(0, 1) + words[words.length - 1].slice(0, 1)).toUpperCase();
        }

        function getStoredPopupState() {
            try {
                const raw = localStorage.getItem(popupStorageKey);
                return raw ? JSON.parse(raw) : {};
            } catch (err) {
                return {};
            }
        }

        function syncPopupState(extra) {
            try {
                const nextState = Object.assign({}, getStoredPopupState(), extra || {}, {
                    id: callMeta && callMeta.id ? callMeta.id : 0,
                    callType: callMeta && callMeta.type ? callMeta.type : (isAudioCall ? 'audio' : 'video'),
                    provider: callMeta && callMeta.provider ? callMeta.provider : 'livekit',
                    callUrl: window.location.href,
                    popupName: 'wowonder_active_call_window',
                    inPopup: !!window.opener,
                    lastSeen: Date.now()
                });
                localStorage.setItem(popupStorageKey, JSON.stringify(nextState));
            } catch (err) {}
        }

        function clearPopupState() {
            try {
                localStorage.removeItem(popupStorageKey);
            } catch (err) {}
        }

        function startPresenceHeartbeat() {
            syncPopupState({status: conferenceJoinedAt > 0 ? 'connected' : 'connecting'});
            if (presenceInterval) return;
            presenceInterval = window.setInterval(function () {
                syncPopupState({status: conferenceJoinedAt > 0 ? 'connected' : 'connecting'});
            }, 2000);
        }

        function stopPresenceHeartbeat() {
            if (presenceInterval) {
                clearInterval(presenceInterval);
                presenceInterval = null;
            }
            stopTimerSync();
        }

        function clearRemoteDisconnectTimer() {
            if (remoteDisconnectTimer) {
                clearTimeout(remoteDisconnectTimer);
                remoteDisconnectTimer = null;
            }
        }

        function scheduleRemoteDisconnectCheck() {
            clearRemoteDisconnectTimer();
            remoteDisconnectTimer = window.setTimeout(function () {
                remoteDisconnectTimer = null;
                if (room && room.remoteParticipants && room.remoteParticipants.size === 0 && conferenceJoinedAt > 0) {
                    redirectAfterCallEnd('ended', 120);
                }
            }, 900);
        }

        function setAudioHeroParticipant(participant, isSelf) {
            const meta = getParticipantMeta(participant, isSelf);
            if (!isSelf && participant && participant.identity) {
                currentAudioParticipantIdentity = participant.identity;
            }
            const display = !isSelf ? meta.name : (currentAudioParticipantIdentity ? audioName.textContent : meta.name);
            const heroStatus = conferenceJoinedAt > 0 ? 'Dang trong cuoc goi' : (!isSelf ? 'Dang ket noi bao mat' : 'Dang cho nguoi kia tham gia');
            if (audioName) {
                audioName.textContent = display || 'Dang ket noi';
            }
            if (audioStatus) {
                audioStatus.textContent = heroStatus;
            }
            if (videoName) {
                videoName.textContent = display || 'Dang ket noi';
            }
            if (videoStatus) {
                videoStatus.textContent = heroStatus;
            }
            syncPopupState({remoteName: !isSelf ? (display || meta.name) : (getStoredPopupState().remoteName || '')});
            if (meta.avatar) {
                if (audioAvatarImg) {
                    audioAvatarImg.src = meta.avatar;
                    audioAvatarImg.classList.remove('lk-hidden');
                }
                if (audioAvatarFallback) {
                    audioAvatarFallback.classList.add('lk-hidden');
                }
                if (videoAvatarImg) {
                    videoAvatarImg.src = meta.avatar;
                    videoAvatarImg.classList.remove('lk-hidden');
                }
                if (videoAvatarFallback) {
                    videoAvatarFallback.classList.add('lk-hidden');
                }
            } else {
                if (audioAvatarFallback) {
                    audioAvatarFallback.textContent = getInitials(display || meta.name);
                    audioAvatarFallback.classList.remove('lk-hidden');
                }
                if (audioAvatarImg) {
                    audioAvatarImg.classList.add('lk-hidden');
                    audioAvatarImg.removeAttribute('src');
                }
                if (videoAvatarFallback) {
                    videoAvatarFallback.textContent = getInitials(display || meta.name);
                    videoAvatarFallback.classList.remove('lk-hidden');
                }
                if (videoAvatarImg) {
                    videoAvatarImg.classList.add('lk-hidden');
                    videoAvatarImg.removeAttribute('src');
                }
            }
            if (!isSelf) {
                setAudioHeroMuteState(isParticipantMicMuted(participant), display || meta.name);
                setVideoHeroMuteState(isParticipantMicMuted(participant), display || meta.name);
            } else {
                setAudioHeroMuteState(false, '');
                setVideoHeroMuteState(false, '');
            }
        }

        function showToast(message) {
            if (!toast) return;
            toast.textContent = message;
            toast.classList.add('show');
            clearTimeout(toastTimer);
            toastTimer = window.setTimeout(function () {
                toast.classList.remove('show');
            }, 2200);
        }

        function getAttachedAudioElements() {
            return Array.prototype.slice.call(document.querySelectorAll('#lk-audio-sink audio, .lk-tile audio'));
        }

        async function applySpeakerMode(enabled) {
            const audioElements = getAttachedAudioElements();
            if (!audioElements.length) {
                showToast('Chua co am thanh de chuyen loa.');
                return;
            }
            let sinkApplied = false;
            for (let i = 0; i < audioElements.length; i += 1) {
                const element = audioElements[i];
                element.volume = 1;
                if (typeof element.setSinkId === 'function') {
                    try {
                        await element.setSinkId(enabled ? 'default' : 'communications');
                        sinkApplied = true;
                    } catch (err) {}
                }
            }
            if (!sinkApplied && typeof HTMLMediaElement !== 'undefined' && !('setSinkId' in HTMLMediaElement.prototype)) {
                showToast('Trinh duyet nay khong ho tro chuyen loa ngoai.');
            }
        }

        function getLocalCameraTrack() {
            if (!room || !room.localParticipant) {
                return null;
            }
            let cameraTrack = null;
            room.localParticipant.videoTrackPublications.forEach(function (publication) {
                if (!cameraTrack && publication && publication.track) {
                    cameraTrack = publication.track;
                }
            });
            return cameraTrack;
        }

        async function flipCameraFacing() {
            const cameraTrack = getLocalCameraTrack();
            if (!cameraTrack || typeof cameraTrack.restartTrack !== 'function') {
                showToast('Thiet bi nay khong ho tro xoay camera.');
                return;
            }
            const nextFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
            try {
                await cameraTrack.restartTrack({
                    facingMode: { ideal: nextFacingMode }
                });
                currentFacingMode = nextFacingMode;
                showToast(nextFacingMode === 'environment' ? 'Da chuyen sang camera sau' : 'Da chuyen sang camera truoc');
            } catch (err) {
                try {
                    await cameraTrack.restartTrack({
                        facingMode: nextFacingMode
                    });
                    currentFacingMode = nextFacingMode;
                    showToast(nextFacingMode === 'environment' ? 'Da chuyen sang camera sau' : 'Da chuyen sang camera truoc');
                } catch (restartErr) {
                    showToast('Khong the xoay camera tren thiet bi nay.');
                }
            }
        }

        function reportCallEnd(forcedStatus) {
            if (callEndReported || !callMeta || !callMeta.id) return Promise.resolve();
            callEndReported = true;
            const duration = getCallDurationSeconds();
            const status = forcedStatus || (conferenceJoinedAt > 0 ? 'ended' : 'no_answer');
            const url = closeCallUrl + '?f=close_call&id=' + encodeURIComponent(callMeta.id) + '&call_type=' + encodeURIComponent(callMeta.type || (isAudioCall ? 'audio' : 'video')) + '&provider=' + encodeURIComponent(callMeta.provider || 'livekit') + '&status=' + encodeURIComponent(status) + '&duration=' + encodeURIComponent(duration);
            try {
                return fetch(url, {method:'GET',credentials:'same-origin',keepalive:true,headers:{'X-Requested-With':'XMLHttpRequest'}}).catch(function(){});
            } catch (err) {}
            return Promise.resolve();
        }

        function redirectAfterCallEnd(forcedStatus, delay) {
            reportCallEnd(forcedStatus).finally(function () {
                stopPresenceHeartbeat();
                clearPopupState();
                setTimeout(function () {
                    if (window.opener && !window.opener.closed) {
                        try { window.opener.focus(); } catch (err) {}
                        try { window.close(); } catch (err) {}
                        if (!window.closed) {
                            window.location.href = redirectUrl;
                        }
                        return;
                    }
                    window.location.href = redirectUrl;
                }, delay || 0);
            });
        }

        function promoteCallLogToVideo() {
            if (!isAudioCall || callLogPromotedToVideo || !callMeta || !callMeta.id) return;
            callLogPromotedToVideo = true;
            const url = closeCallUrl + '?f=set_call_type&id=' + encodeURIComponent(callMeta.id) + '&source_call_type=audio&display_call_type=video&provider=' + encodeURIComponent(callMeta.provider || 'livekit');
            try {
                fetch(url, {method:'GET',credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}}).catch(function(){});
            } catch (err) {}
        }

        function tileId(identity, isSelf) {
            return (isSelf ? 'lk-self-' : 'lk-remote-') + String(identity || (isSelf ? 'self' : 'remote')).replace(/[^a-z0-9_-]/gi, '_');
        }

        function ensureTile(identity, label, isSelf) {
            const target = isSelf ? selfPreview : stage;
            let tile = document.getElementById(tileId(identity, isSelf));
            if (tile) return tile;
            tile = document.createElement('div');
            tile.className = 'lk-tile';
            tile.id = tileId(identity, isSelf);
            tile.innerHTML = '<div class="lk-label"><span class="lk-badge"></span><span>' + label + '</span></div>';
            if (isSelf) {
                selfPreview.classList.remove('lk-hidden');
                selfPreview.innerHTML = '';
            } else {
                const empty = document.getElementById('lk-empty');
                if (empty) empty.remove();
            }
            target.appendChild(tile);
            return tile;
        }

        function attachTrack(track, participant, isSelf) {
            const identity = participant && participant.identity ? participant.identity : (isSelf ? 'self' : 'remote');
            const participantMeta = getParticipantMeta(participant, isSelf);
            const label = isSelf ? (displayName || 'Ban') : (participantMeta.name || identity || 'Khach');
            const tile = ensureTile(identity, label, isSelf);
            const element = track.attach();
            element.setAttribute('data-track-sid', track.sid || '');
            if (track.kind === 'video') {
                // Keep a single rendered video per tile to avoid split/duplicate frames
                // when LiveKit republishes or restarts the camera track.
                tile.querySelectorAll('video').forEach(function (video) {
                    video.remove();
                });
                tile.prepend(element);
                if (!isSelf) {
                    updateVideoPosterVisibility();
                }
            } else {
                element.classList.add('lk-hidden');
                audioSink.appendChild(element);
                if (!isSelf) {
                    setAudioHeroParticipant(participant, false);
                }
            }
            updateParticipantMuteIndicator(participant, isSelf);
        }

        function detachTrack(track) {
            track.detach().forEach(function (element) { element.remove(); });
            if (track && track.kind === 'video') {
                updateVideoPosterVisibility();
            }
        }

        function setError(message) {
            stage.innerHTML = '<div class="lk-tile"><div class="lk-error">' + message + '</div></div>';
        }

        async function connectRoom() {
            if (!livekitConfigured) {
                setError('Chua cau hinh du thong tin LiveKit trong admin.');
                return;
            }
            if (!window.LivekitClient || !wsUrl || !token) {
                setError('Khong tai duoc LiveKit client.');
                return;
            }
            room = new LivekitClient.Room({adaptiveStream:true,dynacast:true});
            room.on(LivekitClient.RoomEvent.TrackSubscribed, function (track, publication, participant) { attachTrack(track, participant, false); });
            room.on(LivekitClient.RoomEvent.TrackUnsubscribed, function (track) { detachTrack(track); });
            room.on(LivekitClient.RoomEvent.TrackMuted, function (publication, participant) {
                if (isAudioPublication(publication)) {
                    updateParticipantMuteIndicator(participant, participant === room.localParticipant);
                }
            });
            room.on(LivekitClient.RoomEvent.TrackUnmuted, function (publication, participant) {
                if (isAudioPublication(publication)) {
                    updateParticipantMuteIndicator(participant, participant === room.localParticipant);
                }
            });
            room.on(LivekitClient.RoomEvent.ParticipantConnected, function (participant) {
                clearRemoteDisconnectTimer();
                if (isAudioCall && audioStatus) {
                    audioStatus.textContent = 'Dang trong cuoc goi';
                }
                setAudioHeroParticipant(participant, false);
                updateParticipantMuteIndicator(participant, false);
            });
            room.on(LivekitClient.RoomEvent.LocalTrackPublished, function (publication, participant) {
                if (publication.track) attachTrack(publication.track, participant || room.localParticipant, true);
                updateParticipantMuteIndicator(participant || room.localParticipant, true);
            });
            room.on(LivekitClient.RoomEvent.LocalTrackUnpublished, function (publication) {
                if (publication.track) detachTrack(publication.track);
            });
            room.on(LivekitClient.RoomEvent.ParticipantDisconnected, function () {
                setAudioHeroMuteState(false, '');
                setVideoHeroMuteState(false, '');
                if (conferenceJoinedAt > 0 && room && room.remoteParticipants && room.remoteParticipants.size === 0) {
                    scheduleRemoteDisconnectCheck();
                }
            });
            room.on(LivekitClient.RoomEvent.Disconnected, function () {
                redirectAfterCallEnd((conferenceJoinedAt > 0 ? 'ended' : 'no_answer'), 0);
            });
            room.on(LivekitClient.RoomEvent.Reconnecting, function () { btnHangup.disabled = true; });
            room.on(LivekitClient.RoomEvent.Reconnected, function () { btnHangup.disabled = false; });
            try {
                syncPopupState({status: 'connecting'});
                await room.connect(wsUrl, token);
                setConferenceJoinedAt();
                startCallTimer();
                startPresenceHeartbeat();
                startTimerSync();
                setAudioHeroParticipant(room.localParticipant, true);
                await room.localParticipant.setMicrophoneEnabled(true);
                await room.localParticipant.setCameraEnabled(!isAudioCall);
                room.localParticipant.trackPublications.forEach(function (publication) {
                    if (publication.track) attachTrack(publication.track, room.localParticipant, true);
                });
                updateParticipantMuteIndicator(room.localParticipant, true);
                room.remoteParticipants.forEach(function (participant) {
                    participant.trackPublications.forEach(function (publication) {
                        if (publication.track) attachTrack(publication.track, participant, false);
                    });
                    updateParticipantMuteIndicator(participant, false);
                });
                if (!room.remoteParticipants.size && isAudioCall) {
                    audioStatus.textContent = 'Dang cho nguoi kia tham gia';
                }
            } catch (error) {
                setError((error && error.message) ? error.message : 'can not connect LiveKit.');
            }
        }

        btnMic.addEventListener('click', async function () {
            if (!room) return;
            microphoneEnabled = !microphoneEnabled;
            await room.localParticipant.setMicrophoneEnabled(microphoneEnabled);
            btnMic.classList.toggle('muted', !microphoneEnabled);
            btnMic.classList.toggle('active', microphoneEnabled);
            showToast(microphoneEnabled ? 'microphone turned on' : 'microphone turned off');
        });

        if (btnCam) {
            btnCam.addEventListener('click', async function () {
                if (!room) return;
                cameraEnabled = !cameraEnabled;
                if (cameraEnabled && isAudioCall) promoteCallLogToVideo();
                await room.localParticipant.setCameraEnabled(cameraEnabled);
                btnCam.classList.toggle('muted', !cameraEnabled);
                btnCam.classList.toggle('active', cameraEnabled);
                if (!cameraEnabled) {
                    selfPreview.innerHTML = '';
                    selfPreview.classList.add('lk-hidden');
                }
                showToast(cameraEnabled ? 'Camera turned on' : 'Camera turned off');
            });
        }

        if (btnFlipCam) {
            btnFlipCam.addEventListener('click', async function () {
                if (!room) return;
                if (!cameraEnabled) {
                    showToast('Hay bat camera truoc khi xoay.');
                    return;
                }
                await flipCameraFacing();
            });
        }

        btnSpeaker.addEventListener('click', async function () {
            speakerEnabled = !speakerEnabled;
            await applySpeakerMode(speakerEnabled);
            btnSpeaker.classList.toggle('active', speakerEnabled);
            showToast(speakerEnabled ? 'Speakerphone is on' : 'Speakerphone is off');
        });

        btnHangup.addEventListener('click', function () {
            if (room) {
                try { room.disconnect(); } catch (err) {}
            }
            redirectAfterCallEnd('ended', 150);
        });

        window.addEventListener('beforeunload', function () {
            stopPresenceHeartbeat();
            clearPopupState();
            reportCallEnd();
        });
        btnMic.classList.add('active');
        btnSpeaker.classList.add('active');
        syncPopupState({status: 'opening'});
        connectRoom();
    </script>
</body>
</html>
