<?php
require 'config.php';
set_time_limit(0);
$in = json_decode(file_get_contents('php://input'), true);
$tt = $_SESSION['tt'] ?? null;
if (!$tt || $tt['expires_at'] < time()) json_out(['error' => 'Non connecté à TikTok (ou session expirée). Reconnecte-toi.'], 401);

$job = preg_replace('/[^a-f0-9]/', '', $in['job'] ?? '');
$file = basename($in['file'] ?? '');
$path = WORK_DIR . "/$job/$file";
if (!preg_match('/^part_\d+\.mp4$/', $file) || !is_file($path)) json_out(['error' => 'Fichier introuvable.'], 404);
$size = filesize($path);
if ($size > 64 * 1024 * 1024) json_out(['error' => 'Partie > 64 Mo : augmente le nombre de parties.'], 400);

function tt_post($url, $token, $body) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $token", 'Content-Type: application/json; charset=UTF-8'],
        CURLOPT_POSTFIELDS => json_encode($body)]);
    return json_decode(curl_exec($ch), true);
}

// 1) Initialisation
$init = tt_post('https://open.tiktokapis.com/v2/post/publish/video/init/', $tt['access_token'], [
    'post_info' => [
        'title' => mb_substr($in['title'] ?? '', 0, 2200),
        'privacy_level' => $in['privacy'] ?? 'SELF_ONLY',
        'disable_duet' => !empty($in['opts']['noDuet']), 'disable_comment' => !empty($in['opts']['noComment']), 'disable_stitch' => !empty($in['opts']['noStitch']),
    ],
    'source_info' => ['source' => 'FILE_UPLOAD', 'video_size' => $size, 'chunk_size' => $size, 'total_chunk_count' => 1],
]);
if (($init['error']['code'] ?? 'ok') !== 'ok') json_out(['error' => 'Init TikTok : ' . ($init['error']['message'] ?? 'inconnue')], 400);
$uploadUrl = $init['data']['upload_url'];
$publishId = $init['data']['publish_id'];

// 2) Envoi du fichier (1 seul chunk)
$fh = fopen($path, 'rb');
$ch = curl_init($uploadUrl);
curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_UPLOAD => true, CURLOPT_INFILE => $fh, CURLOPT_INFILESIZE => $size,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: video/mp4', "Content-Length: $size", 'Content-Range: bytes 0-' . ($size - 1) . "/$size"]]);
curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
fclose($fh);
if ($http >= 300) json_out(['error' => "Upload vers TikTok échoué (HTTP $http)."], 500);

// 3) Statut
sleep(3);
$st = tt_post('https://open.tiktokapis.com/v2/post/publish/status/fetch/', $tt['access_token'], ['publish_id' => $publishId]);
json_out(['publish_id' => $publishId, 'status' => $st['data']['status'] ?? 'PROCESSING']);
