<?php
require 'config.php';
set_time_limit(0);
$mode = $_POST['mode'] ?? 'count';
$parts = (int)($_POST['parts'] ?? 0);
$secs = (float)($_POST['seconds'] ?? 0);
$url = trim($_POST['url'] ?? '');
$job = bin2hex(random_bytes(8));
$dir = WORK_DIR . "/$job";
mkdir($dir, 0775, true);

if ($url !== '') {
    // --- Mode lien YouTube ---
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
    if (!preg_match('/(^|\.)(youtube\.com|youtu\.be)$/', $host)) json_out(['error' => 'Lien YouTube invalide.'], 400);
    $src = "$dir/source.mp4";
    $cmd = sprintf('%s -f %s --merge-output-format mp4 --no-playlist -o %s %s 2>&1',
        YTDLP, escapeshellarg('bv*[ext=mp4]+ba[ext=m4a]/b[ext=mp4]/b'), escapeshellarg($src), escapeshellarg($url));
    exec($cmd, $log, $code);
    if ($code !== 0 || !file_exists($src)) json_out(['error' => 'Téléchargement échoué : ' . end($log)], 500);
} else {
    // --- Mode upload ---
    if (empty($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) json_out(['error' => 'Aucun fichier ni lien (ou upload échoué).'], 400);
    $ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4', 'mov', 'mkv', 'avi', 'webm'])) json_out(['error' => 'Format non supporté.'], 400);
    $src = "$dir/source.$ext";
    move_uploaded_file($_FILES['video']['tmp_name'], $src);
}

$duration = (float)trim(shell_exec(FFPROBE . ' -v error -show_entries format=duration -of csv=p=0 ' . escapeshellarg($src)));
if ($duration <= 0) json_out(['error' => 'Impossible de lire la durée de la vidéo.'], 500);

if ($mode === 'duration') {
    if ($secs < 1) json_out(['error' => 'Durée par partie invalide.'], 400);
    $parts = (int)ceil($duration / $secs); $len = $secs;
} else { $len = $duration / max(1, $parts); }
if ($parts < 1 || $parts > 100) json_out(['error' => "Nombre de parties invalide ($parts, max 100)."], 400);
$files = [];
for ($i = 0; $i < $parts; $i++) {
    $out = "part_" . ($i + 1) . ".mp4";
    $cmd = sprintf('%s -y -ss %.3f -i %s -t %.3f -c:v libx264 -preset veryfast -crf 23 -c:a aac -movflags +faststart %s 2>&1',
        FFMPEG, $i * $len, escapeshellarg($src), $len, escapeshellarg("$dir/$out"));
    exec($cmd, $log, $code);
    if ($code !== 0 || !file_exists("$dir/$out")) json_out(['error' => "FFmpeg a échoué sur la partie " . ($i + 1)], 500);
    $files[] = ['name' => $out, 'size' => filesize("$dir/$out"), 'start' => $i * $len, 'end' => min($duration, ($i + 1) * $len)];
}
unlink($src);
json_out(['job' => $job, 'duration' => $duration, 'files' => $files]);
