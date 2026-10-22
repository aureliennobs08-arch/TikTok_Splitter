<?php
require 'config.php';
$_SESSION['oauth_state'] = bin2hex(random_bytes(16));
header('Location: https://www.tiktok.com/v2/auth/authorize/?' . http_build_query([
    'client_key'    => TIKTOK_CLIENT_KEY,
    'scope'         => 'user.info.basic,video.publish',
    'response_type' => 'code',
    'redirect_uri'  => REDIRECT_URI,
    'state'         => $_SESSION['oauth_state'],
]));
