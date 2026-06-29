<?php

require_once __DIR__ . '/../../src/auth.php';
requireRole('admin');

$pdo = getDb();
$logs = $pdo->query('SELECT * FROM sync_log ORDER BY id DESC LIMIT 50')->fetchAll();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Лог синхронизаций — GB Pricing</title>
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
        <h1>Лог синхронизаций</h1>

        <div class="card">
            <?php if (empty($logs)): ?>
                <p>Пока не было синхронизаций.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Начало</th>
                        <th>Окончание</th>
                        <th>Статус</th>
                        <th>Товаров</th>
                        <th>Ошибка</th>
                    </tr>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= e($log['started_at']) ?></td>
                            <td><?= e($log['finished_at'] ?? '-') ?></td>
                            <td><?= e($log['status']) ?></td>
                            <td><?= (int) $log['items_count'] ?></td>
                            <td><?= e($log['error_message'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
