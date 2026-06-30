<?php
// Webhook для автодеплоя с GitHub.
// GitHub: Settings -> Webhooks -> Add webhook
// Payload URL: http://pricing.salevrn.ru/deploy.php?token=YOUR_TOKEN
// Content type: application/json
// Events: Just the push event

$config = require __DIR__ . '/../src/config.php';
$deployToken = $config['deploy_token'] ?? '';

if ($deployToken === '' || !isset($_GET['token']) || $_GET['token'] !== $deployToken) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$projectDir = '/home/m/mastak97/pricing.salevrn.ru';
putenv('HOME=' . $projectDir);
chdir($projectDir);

echo "Pulling from GitHub...\n";
echo shell_exec('/usr/local/bin/git pull origin main 2>&1');

echo "\nDeploying public/ to public_html/...\n";
echo shell_exec('cp -r public/* public_html/ 2>&1');
echo shell_exec('cp public/.htaccess public_html/ 2>&1');

echo "\nDone.\n";
