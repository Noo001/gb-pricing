<?php

require_once __DIR__ . '/../../src/auth.php';
requireRole('seller');

$pdo = getDb();

$search = trim($_GET['search'] ?? '');
$brand = trim($_GET['brand'] ?? '');
$category = trim($_GET['category'] ?? '');
$subcategory = trim($_GET['subcategory'] ?? '');
$country = trim($_GET['country'] ?? '');

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = "name ILIKE ?";
    $params[] = '%' . $search . '%';
}
if ($brand !== '') {
    $where[] = "brand = ?";
    $params[] = $brand;
}
if ($category !== '') {
    $where[] = "category = ?";
    $params[] = $category;
}
if ($subcategory !== '') {
    $where[] = "subcategory = ?";
    $params[] = $subcategory;
}
if ($country !== '') {
    $where[] = "country = ?";
    $params[] = $country;
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT * FROM products WHERE {$whereSql} ORDER BY brand, category, name LIMIT ? OFFSET ?");
$stmt->execute([...$params, $perPage, $offset]);
$products = $stmt->fetchAll();

$brands = $pdo->query('SELECT DISTINCT brand FROM products ORDER BY brand')->fetchAll(PDO::FETCH_COLUMN);
$categories = $pdo->query('SELECT DISTINCT category FROM products ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
$subcategories = $pdo->query('SELECT DISTINCT subcategory FROM products ORDER BY subcategory')->fetchAll(PDO::FETCH_COLUMN);
$countries = $pdo->query('SELECT DISTINCT country FROM products ORDER BY country')->fetchAll(PDO::FETCH_COLUMN);

function buildQuery(array $changes): string {
    $params = array_merge($_GET, $changes);
    return '?' . http_build_query($params);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Каталог — GB Pricing</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <strong>GB Pricing — Каталог</strong>
            <div>
                <a href="/seller/catalog.php">Каталог</a>
                <a href="/logout.php">Выход</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Каталог товаров</h1>

        <div class="card">
            <form method="get">
                <div class="filters">
                    <div>
                        <label>Поиск</label>
                        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Название товара">
                    </div>
                    <div>
                        <label>Бренд</label>
                        <select name="brand">
                            <option value="">Все</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= e($b) ?>" <?= $brand === $b ? 'selected' : '' ?>><?= e($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Категория</label>
                        <select name="category">
                            <option value="">Все</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Подкатегория</label>
                        <select name="subcategory">
                            <option value="">Все</option>
                            <?php foreach ($subcategories as $s): ?>
                                <option value="<?= e($s) ?>" <?= $subcategory === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Страна</label>
                        <select name="country">
                            <option value="">Все</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= e($c) ?>" <?= $country === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn">Применить</button>
                <a href="/seller/catalog.php" class="btn btn-secondary">Сбросить</a>
            </form>
        </div>

        <p>Всего: <strong><?= $total ?></strong> товаров</p>

        <div class="card">
            <?php if (empty($products)): ?>
                <p>Товары не найдены.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Бренд</th>
                        <th>Категория</th>
                        <th>Подкатегория</th>
                        <th>Название</th>
                        <th>Страна</th>
                        <th>Цена</th>
                    </tr>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= e($product['brand']) ?></td>
                            <td><?= e($product['category']) ?></td>
                            <td><?= e($product['subcategory']) ?></td>
                            <td><?= e($product['name']) ?></td>
                            <td><?= e($product['country']) ?></td>
                            <td class="price"><?= number_format((int) $product['price'], 0, '', ' ') ?> ₽</td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div style="margin-top: 20px;">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php if ($p === $page): ?>
                            <strong style="margin-right: 8px;"><?= $p ?></strong>
                        <?php else: ?>
                            <a href="<?= e(buildQuery(['page' => $p])) ?>" style="margin-right: 8px;"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
