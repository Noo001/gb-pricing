<?php

require_once __DIR__ . '/../../src/auth.php';
requireRole('admin');

$pdo = getDb();

$stats = [
    'products' => $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'sellers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn(),
    'last_sync' => getSetting('last_sync_at', 'нет'),
];

$lastLogs = $pdo->query('SELECT * FROM sync_log ORDER BY id DESC LIMIT 5')->fetchAll();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка — GB Pricing</title>
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
        <h1>Обзор</h1>

        <?php foreach (getFlashMessages() as $flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?php foreach (explode("\n", $flash['message']) as $line): ?>
                    <?= nl2br(e(formatSyncError($line)), false) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="card">
            <p><strong>Товаров в каталоге:</strong> <?= (int) $stats['products'] ?></p>
            <p><strong>Продавцов:</strong> <?= (int) $stats['sellers'] ?></p>
            <p><strong>Последняя синхронизация:</strong> <?= e($stats['last_sync']) ?></p>

            <form method="post" action="/admin/sync.php" style="margin-top: 16px;">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                <button type="submit" class="btn">Запустить синхронизацию</button>
            </form>
        </div>

        <div class="card">
            <h2>Последние синхронизации</h2>
            <?php if (empty($lastLogs)): ?>
                <p>Пока не было синхронизаций.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Время</th>
                        <th>Статус</th>
                        <th>Товаров</th>
                        <th>Ошибка</th>
                    </tr>
                    <?php foreach ($lastLogs as $log): ?>
                        <tr>
                            <td><?= e($log['finished_at'] ?? $log['started_at']) ?></td>
                            <td><?= e($log['status']) ?></td>
                            <td><?= (int) $log['items_count'] ?></td>
                            <td><?= e(formatSyncError($log['error_message'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
