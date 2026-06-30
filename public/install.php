<?php

require_once __DIR__ . '/../src/auth.php';

$userCount = (int) getDb()->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userCount === 0) {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        flashMessage('error', 'Логин и пароль обязательны.');
    } elseif (mb_strlen($password) < 6) {
        flashMessage('error', 'Пароль должен быть не короче 6 символов.');
    } else {
        $pdo = getDb();
        $stmt = $pdo->prepare('INSERT INTO users (login, password_hash, role) VALUES (?, ?, ?)');
        $stmt->execute([$login, password_hash($password, PASSWORD_DEFAULT), 'admin']);

        $deleted = @unlink(__FILE__);
        $message = 'Администратор создан. ';
        $message .= $deleted
            ? 'Файл install.php удалён автоматически. Перейдите на <a href="/login.php">страницу входа</a>.'
            : 'Удалите файл <code>public/install.php</code> вручную, затем <a href="/login.php">войдите</a>.';

        flashMessage('success', $message);
        header('Location: /login.php');
        exit;
    }
}

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
            <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
        <?php endforeach; ?>

        <?php if ($userCount > 0): ?>
            <div class="alert alert-error">
                Администратор уже существует. Удалите файл <code>public/install.php</code> с сервера.
            </div>
        <?php else: ?>
            <form method="post">
                <label>Логин администратора</label>
                <input type="text" name="login" required autofocus>

                <label>Пароль</label>
                <input type="password" name="password" required minlength="6">
                <p class="small">Минимум 6 символов.</p>

                <button type="submit">Создать администратора</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
