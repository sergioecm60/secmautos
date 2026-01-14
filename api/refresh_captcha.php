<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$captcha_data = generar_captcha();

json_response($captcha_data);
