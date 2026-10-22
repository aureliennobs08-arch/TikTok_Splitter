<?php
require 'config.php';
$job = preg_replace('/[^a-f0-9]/', '', $_GET['job'] ?? ''); $f = basename($_GET['file'] ?? '');
$p = WORK_DIR . "/$job/$f";
if (!preg_match('/^part_\d+\.mp4$/', $f) || !is_file($p)) { http_response_code(404); exit; }
header('Content-Type: video/mp4'); header('Content-Length: ' . filesize($p)); readfile($p);
