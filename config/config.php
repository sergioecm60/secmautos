<?php
return [
    'app_env' => getenv('APP_ENV') ?: 'development',
    'app_url' => getenv('APP_URL') ?: 'http://localhost:8081',
    'encryption_key' => getenv('APP_ENCRYPTION_KEY') ?: base64_encode(random_bytes(32)),
    
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: 3306,
        'name' => getenv('DB_NAME') ?: 'secmautos',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
    
    'session' => [
        'timeout' => (int)(getenv('SESSION_TIMEOUT') ?: 1800),
    ],
    
    'mail' => [
        'enabled' => getenv('MAIL_ENABLED') === 'true',
        'host' => getenv('MAIL_HOST') ?: '',
        'port' => getenv('MAIL_PORT') ?: 587,
        'user' => getenv('MAIL_USER') ?: '',
        'pass' => getenv('MAIL_PASS') ?: '',
        'from' => getenv('MAIL_FROM') ?: 'noreply@secmautos.com',
    ],
];
