<?php
require 'config.php';
$tt = $_SESSION['tt'] ?? null;
if (!$tt || $tt['expires_at'] < time()) json_out(['connected' => false]);
$ch = curl_init('https://open.tiktokapis.com/v2/user/info/?fields=display_name,avatar_url');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tt['access_token']]]);
$r = json_decode(curl_exec($ch), true);
json_out(['connected' => true, 'name' => $r['data']['user']['display_name'] ?? 'Compte TikTok', 'avatar' => $r['data']['user']['avatar_url'] ?? '']);
