<?php

require_once __DIR__ . '/../src/auth.php';

if (getCurrentUser()) {
    header('Location: /');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login($login, $password)) {
        header('Location: /');
        exit;
    }

    $error = 'Неверный логин или пароль.';
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход — GB Pricing</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="card login-box">
            <h1>Вход</h1>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <label>Логин</label>
                <input type="text" name="login" required autofocus>

                <label>Пароль</label>
                <input type="password" name="password" required>

                <button type="submit" class="btn">Войти</button>
            </form>
        </div>
    </div>
</body>
</html>
