<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$captcha = generar_captcha();

echo json_encode($captcha);
