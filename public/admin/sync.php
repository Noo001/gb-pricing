<?php

require_once __DIR__ . '/../../src/auth.php';
requireRole('admin');

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flashMessage('error', 'Недействительный CSRF-токен.');
    } else {
        $scriptPath = realpath(__DIR__ . '/../../cron/sync.php');
        if ($scriptPath === false) {
            flashMessage('error', 'Не найден скрипт синхронизации.');
        } else {
            $output = [];
            $returnCode = 0;
            exec('php ' . escapeshellarg($scriptPath) . ' 2>&1', $output, $returnCode);

            $messageType = $returnCode === 0 ? 'success' : 'error';
            $message = implode("\n", $output);
            flashMessage($messageType, $message);
        }
    }

    header('Location: /admin/');
    exit;
}

header('Location: /admin/');
exit;
