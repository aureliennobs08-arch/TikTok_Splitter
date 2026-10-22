<?php
require 'config.php';
function fail($title, $hint) {
    echo '<!DOCTYPE html><meta charset="utf-8"><link rel="stylesheet" href="style.css"><body><main><div class="card err"><h2>❌ '
        . htmlspecialchars($title) . '</h2><p>' . nl2br(htmlspecialchars($hint)) . '</p><a class="btn" href="index.php">Retour</a></div></main></body>';
    exit;
}
if (isset($_GET['error'])) fail('TikTok a refusé la connexion', ($_GET['error'] ?? '') . "\n" . ($_GET['error_description'] ?? '') .
    "\n\nCauses fréquentes : ton compte n'est pas ajouté comme « testeur » (Sandbox / Target users), ou le scope video.publish n'est pas activé.");
if (empty($_GET['code'])) fail('Pas de code reçu', "Aucun code d'autorisation dans l'URL.");
if (($_GET['state'] ?? '') !== ($_SESSION['oauth_state'] ?? 'x'))
    fail('Session perdue (state invalide)', "Ouvre le site et la connexion sur EXACTEMENT le même domaine (avec ou sans www, même http/https).\nVérifie aussi que les cookies sont activés.");

$ch = curl_init('https://open.tiktokapis.com/v2/oauth/token/');
curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS => http_build_query(['client_key' => TIKTOK_CLIENT_KEY, 'client_secret' => TIKTOK_CLIENT_SECRET,
        'code' => $_GET['code'], 'grant_type' => 'authorization_code', 'redirect_uri' => REDIRECT_URI])]);
$res = json_decode(curl_exec($ch), true);
if (empty($res['access_token'])) fail('Échange du token impossible',
    json_encode($res, JSON_UNESCAPED_UNICODE) . "\n\nVérifie client_key / client_secret dans config.php et que REDIRECT_URI est identique à celui déclaré chez TikTok.");
$_SESSION['tt'] = ['access_token' => $res['access_token'], 'open_id' => $res['open_id'], 'expires_at' => time() + $res['expires_in']];
header('Location: index.php');
