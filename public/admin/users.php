<?php

require_once __DIR__ . '/../../src/auth.php';
requireRole('admin');

$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flashMessage('error', 'Недействительный CSRF-токен.');
    } elseif (isset($_POST['action']) && $_POST['action'] === 'create') {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'seller';

        if ($login === '' || $password === '') {
            flashMessage('error', 'Логин и пароль обязательны.');
        } elseif (!in_array($role, ['admin', 'seller'], true)) {
            flashMessage('error', 'Некорректная роль.');
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO users (login, password_hash, role) VALUES (?, ?, ?)');
                $stmt->execute([$login, password_hash($password, PASSWORD_DEFAULT), $role]);
                flashMessage('success', 'Пользователь создан.');
            } catch (PDOException $e) {
                flashMessage('error', 'Такой логин уже существует.');
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flashMessage('success', 'Пользователь удалён.');
        }
    }

    header('Location: /admin/users.php');
    exit;
}

$users = $pdo->query('SELECT id, login, role, created_at FROM users ORDER BY id DESC')->fetchAll();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Пользователи — GB Pricing</title>
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
        <h1>Пользователи</h1>

        <?php foreach (getFlashMessages() as $flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>

        <div class="card">
            <h2>Добавить пользователя</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                <input type="hidden" name="action" value="create">

                <label>Логин</label>
                <input type="text" name="login" required>

                <label>Пароль</label>
                <input type="password" name="password" required>

                <label>Роль</label>
                <select name="role">
                    <option value="seller">Продавец</option>
                    <option value="admin">Администратор</option>
                </select>

                <button type="submit" class="btn">Создать</button>
            </form>
        </div>

        <div class="card">
            <h2>Список пользователей</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Логин</th>
                    <th>Роль</th>
                    <th>Создан</th>
                    <th></th>
                </tr>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= (int) $user['id'] ?></td>
                        <td><?= e($user['login']) ?></td>
                        <td><?= e($user['role']) ?></td>
                        <td><?= e($user['created_at']) ?></td>
                        <td>
                            <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
                                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                <button type="submit" class="btn btn-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
