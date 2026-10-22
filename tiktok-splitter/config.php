<?php
session_start();
// === À REMPLIR (developers.tiktok.com > ton app) ===
const TIKTOK_CLIENT_KEY    = 'TON_CLIENT_KEY';
const TIKTOK_CLIENT_SECRET = 'TON_CLIENT_SECRET';
const REDIRECT_URI         = 'https://TON-SITE.com/callback.php'; // doit être identique dans l'app TikTok
const FFMPEG  = 'ffmpeg';
const FFPROBE = 'ffprobe';
const YTDLP   = 'yt-dlp';
define('WORK_DIR', __DIR__ . '/work');
if (!is_dir(WORK_DIR)) mkdir(WORK_DIR, 0775, true);

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
