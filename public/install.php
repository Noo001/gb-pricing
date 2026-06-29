<?php

require_once __DIR__ . '/../src/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        flashMessage('error', 'Логин и пароль обязательны.');
    } else {
        $pdo = getDb();
        $stmt = $pdo->prepare('INSERT INTO users (login, password_hash, role) VALUES (?, ?, ?)');
        $stmt->execute([$login, password_hash($password, PASSWORD_DEFAULT), 'admin']);
        flashMessage('success', 'Администратор создан. Удалите install.php и войдите.');
    }
}

$userCount = getDb()->query('SELECT COUNT(*) FROM users')->fetchColumn();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Установка — GB Pricing</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Первоначальная установка</h1>

        <?php foreach (getFlashMessages() as $flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>

        <?php if ($userCount > 0): ?>
            <p>Администратор уже существует. Удалите файл <code>public/install.php</code>.</p>
        <?php else: ?>
            <form method="post">
                <label>Логин администратора</label>
                <input type="text" name="login" required>

                <label>Пароль</label>
                <input type="password" name="password" required>

                <button type="submit">Создать администратора</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
