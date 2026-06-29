<?php

require_once __DIR__ . '/db.php';

function e(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

function getSetting(string $key, $default = null) {
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function setSetting(string $key, $value): void {
    $pdo = getDb();
    $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value');
    $stmt->execute([$key, (string) $value]);
}

function getMarkupPercent(string $externalId, string $category): float {
    $pdo = getDb();

    $stmt = $pdo->prepare('SELECT percent FROM markup_rules WHERE scope = ? AND target = ? LIMIT 1');
    $stmt->execute(['product', $externalId]);
    $row = $stmt->fetch();
    if ($row) {
        return (float) $row['percent'];
    }

    $stmt = $pdo->prepare('SELECT percent FROM markup_rules WHERE scope = ? AND target = ? LIMIT 1');
    $stmt->execute(['category', $category]);
    $row = $stmt->fetch();
    if ($row) {
        return (float) $row['percent'];
    }

    return (float) getSetting('default_markup_percent', 0);
}

function calculatePrice(float $cost, float $percent): int {
    $raw = $cost * (1 + $percent / 100);
    $step = (int) getSetting('rounding_step', 100);
    if ($step <= 0) {
        $step = 100;
    }
    return (int) ceil($raw / $step) * $step;
}

function recalculateAllPrices(): void {
    $pdo = getDb();
    $stmt = $pdo->query('SELECT id, external_id, category, cost FROM products');
    $products = $stmt->fetchAll();

    $update = $pdo->prepare('UPDATE products SET price = ? WHERE id = ?');
    foreach ($products as $product) {
        $percent = getMarkupPercent($product['external_id'], $product['category']);
        $price = calculatePrice((float) $product['cost'], $percent);
        $update->execute([$price, $product['id']]);
    }
}

function flashMessage(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}
