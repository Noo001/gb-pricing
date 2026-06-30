<?php

require_once __DIR__ . '/../../src/auth.php';
requireRole('admin');

$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flashMessage('error', 'Недействительный CSRF-токен.');
    } elseif (isset($_POST['action']) && $_POST['action'] === 'create') {
        $scope = $_POST['scope'] ?? '';
        $target = trim($_POST['target'] ?? '');
        $percent = (float) ($_POST['percent'] ?? 0);

        if (!in_array($scope, ['category', 'product'], true)) {
            flashMessage('error', 'Некорректный тип наценки.');
        } elseif ($target === '') {
            flashMessage('error', 'Укажите категорию или ID товара.');
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO markup_rules (scope, target, percent) VALUES (?, ?, ?)');
                $stmt->execute([$scope, $target, $percent]);
                recalculateAllPrices();
                flashMessage('success', 'Накрутка добавлена. Цены пересчитаны.');
            } catch (Exception $e) {
                flashMessage('error', 'Такое правило уже существует.');
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM markup_rules WHERE id = ?')->execute([$id]);
            recalculateAllPrices();
            flashMessage('success', 'Накрутка удалена. Цены пересчитаны.');
        }
    }

    header('Location: /admin/markup.php');
    exit;
}

$rules = $pdo->query('SELECT * FROM markup_rules ORDER BY scope, target')->fetchAll();
$categories = $pdo->query('SELECT DISTINCT category FROM products ORDER BY category')->fetchAll(FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Накрутки — GB Pricing</title>
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
        <h1>Накрутки</h1>

        <?php foreach (getFlashMessages() as $flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>

        <div class="card">
            <h2>Накрутка по умолчанию</h2>
            <p>Задаётся в <a href="/admin/settings.php">настройках</a>. Сейчас: <strong><?= e(getSetting('default_markup_percent', 0)) ?>%</strong>.</p>
        </div>

        <div class="card">
            <h2>Добавить правило</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                <input type="hidden" name="action" value="create">

                <label>Тип</label>
                <select name="scope" id="scope">
                    <option value="category">Категория</option>
                    <option value="product">Товар (external_id)</option>
                </select>

                <label id="target-label">Категория</label>
                <input type="text" name="target" id="target" list="categories" required>
                <datalist id="categories">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>">
                    <?php endforeach; ?>
                </datalist>

                <label>Процент накрутки</label>
                <input type="number" step="0.01" name="percent" required>

                <button type="submit" class="btn">Добавить</button>
            </form>
        </div>

        <div class="card">
            <h2>Текущие правила</h2>
            <?php if (empty($rules)): ?>
                <p>Правил пока нет. Используется накрутка по умолчанию.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Тип</th>
                        <th>Цель</th>
                        <th>Накрутка</th>
                        <th></th>
                    </tr>
                    <?php foreach ($rules as $rule): ?>
                        <tr>
                            <td><?= e($rule['scope']) ?></td>
                            <td><?= e($rule['target']) ?></td>
                            <td><?= e($rule['percent']) ?>%</td>
                            <td>
                                <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $rule['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const scopeSelect = document.getElementById('scope');
        const targetLabel = document.getElementById('target-label');
        scopeSelect.addEventListener('change', () => {
            targetLabel.textContent = scopeSelect.value === 'category' ? 'Категория' : 'External ID товара';
        });
    </script>
</body>
</html>
