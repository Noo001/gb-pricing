<?php
return [
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '5432',
        'database' => $_ENV['DB_NAME'] ?? 'gb_pricing',
        'username' => $_ENV['DB_USER'] ?? 'gb_pricing_user',
        'password' => $_ENV['DB_PASSWORD'] ?? 'your_db_password',
        'sslmode' => $_ENV['DB_SSLMODE'] ?? 'require',
    ],
    // Токен для webhook автодеплоя (public/deploy.php?token=...)
    'deploy_token' => $_ENV['DEPLOY_TOKEN'] ?? '',
];
