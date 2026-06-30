<?php

require_once __DIR__ . '/../../src/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flashMessage('error', 'Недействительный CSRF-токен.');
    } else {
        $apiBaseUrl = rtrim($_POST['api_base_url'] ?? '', '/');
        $apiToken = trim($_POST['api_token'] ?? '');
        $defaultMarkup = (float) ($_POST['default_markup_percent'] ?? 0);
        $roundingStep = (int) ($_POST['rounding_step'] ?? 100);
        $syncInterval = (int) ($_POST['sync_interval_minutes'] ?? 15);

        if ($apiBaseUrl === '' || !filter_var($apiBaseUrl, FILTER_VALIDATE_URL)) {
            flashMessage('error', 'Укажите корректный URL API.');
        } elseif ($apiToken === '') {
            flashMessage('error', 'API-токен не может быть пустым.');
        } elseif ($roundingStep <= 0) {
            flashMessage('error', 'Шаг округления должен быть больше 0.');
        } else {
            setSetting('api_base_url', $apiBaseUrl);
            setSetting('api_token', $apiToken);
            setSetting('default_markup_percent', $defaultMarkup);
            setSetting('rounding_step', $roundingStep);
            setSetting('sync_interval_minutes', $syncInterval);

            recalculateAllPrices();
            flashMessage('success', 'Настройки сохранены. Цены пересчитаны.');
        }
    }

    header('Location: /admin/settings.php');
    exit;
}

$settings = [
    'api_base_url' => getSetting('api_base_url', 'https://api-c.rmgroup.website'),
    'api_token' => getSetting('api_token', ''),
    'default_markup_percent' => getSetting('default_markup_percent', 15),
    'rounding_step' => getSetting('rounding_step', 100),
    'sync_interval_minutes' => getSetting('sync_interval_minutes', 15),
];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Настройки — GB Pricing</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <strong>GB Pricing — Админка</strong>
            <div>
                <a href="/admin/">Главная</a>
                <a href="/admin/settings.php">Настройки</a>
                <a href="/admin/users.php">Продавцы</a>
                <a href="/admin/markup.php">Накрутки</a>
                <a href="/admin/sync_log.php">Лог</a>
                <a href="/logout.php">Выход</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Настройки</h1>

        <?php foreach (getFlashMessages() as $flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
        <?php endforeach; ?>

        <div class="card">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">

                <label>Базовый URL API</label>
                <input type="url" name="api_base_url" value="<?= e($settings['api_base_url']) ?>" required>

                <label>API-токен</label>
                <input type="password" name="api_token" value="<?= e($settings['api_token']) ?>" required autocomplete="off">
                <p class="small">Токен, который прислали по почте. Хранится в базе в открытом виде.</p>

                <label>Накрутка по умолчанию (%)</label>
                <input type="number" step="0.01" name="default_markup_percent" value="<?= e($settings['default_markup_percent']) ?>" required>

                <label>Шаг округления (руб.)</label>
                <input type="number" name="rounding_step" value="<?= e($settings['rounding_step']) ?>" required>
                <p class="small">По умолчанию 100 — округление до сотен рублей вверх.</p>

                <label>Интервал синхронизации (минут)</label>
                <input type="number" name="sync_interval_minutes" value="<?= e($settings['sync_interval_minutes']) ?>" required>
                <p class="small">Используется для информации. Реальный интервал задаётся в cron на хостинге.</p>

                <button type="submit" class="btn">Сохранить</button>
            </form>
        </div>
    </div>
</body>
</html>
