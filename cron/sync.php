<?php

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    http_response_code(403);
    exit('CLI only');
}

function logMessage(string $message): void {
    $date = date('Y-m-d H:i:s');
    fwrite(STDOUT, "[{$date}] {$message}\n");
}

function sanitizeToken(string $token): string {
    return str_replace(["\r", "\n"], '', trim($token));
}

$pdo = getDb();

$running = $pdo->query("SELECT id FROM sync_log WHERE status = 'running' ORDER BY id DESC LIMIT 1")->fetch();
if ($running) {
    logMessage('Синхронизация уже выполняется. Выход.');
    exit(0);
}

$baseUrl = rtrim(getSetting('api_base_url', 'https://api-c.rmgroup.website'), '/');
$token = sanitizeToken(getSetting('api_token', ''));

if ($baseUrl === '' || $token === '') {
    logMessage('Не заданы настройки API. Синхронизация отменена.');
    exit(1);
}

$syncStartedAt = date('Y-m-d H:i:s');

$logStmt = $pdo->prepare("INSERT INTO sync_log (started_at, status) VALUES (CURRENT_TIMESTAMP, 'running') RETURNING id");
$logStmt->execute();
$logId = (int) $logStmt->fetchColumn();

$itemsCount = 0;
$errorMessage = null;

try {
    logMessage('Запрос каталога...');

    $payload = json_encode(['method' => 'pricelist.get']);

    $ch = curl_init($baseUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Auth: ' . $token,
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('CURL error: ' . $curlError);
    }

    if ($httpCode !== 200) {
        throw new Exception('HTTP ' . $httpCode . ': ' . substr($response, 0, 500));
    }

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data['result']) || !is_array($data['result'])) {
        throw new Exception('Некорректный ответ API: ' . substr($response, 0, 500));
    }

    $groups = $data['result'];
    $items = [];
    foreach ($groups as $group) {
        if (!empty($group['items']) && is_array($group['items'])) {
            foreach ($group['items'] as $item) {
                $items[] = $item;
            }
        }
    }

    $itemsCount = count($items);
    logMessage("Получено товаров: {$itemsCount}");

    if ($itemsCount === 0) {
        throw new Exception('API вернуло пустой каталог. Синхронизация отменена, чтобы не удалить существующие товары.');
    }

    $pdo->beginTransaction();

    $insert = $pdo->prepare('
        INSERT INTO products (external_id, brand, category, subcategory, name, country, cost, synced_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ON CONFLICT (external_id, country) DO UPDATE SET
            brand = EXCLUDED.brand,
            category = EXCLUDED.category,
            subcategory = EXCLUDED.subcategory,
            name = EXCLUDED.name,
            cost = EXCLUDED.cost,
            synced_at = CURRENT_TIMESTAMP
    ');

    foreach ($items as $item) {
        $externalId = (string) ($item['id'] ?? '');
        $country = (string) ($item['country'] ?? '');

        if ($externalId === '') {
            logMessage('Пропущен товар без external_id.');
            continue;
        }

        $insert->execute([
            $externalId,
            $item['brand'] ?? '',
            $item['category'] ?? '',
            $item['subcategory'] ?? '',
            $item['name'] ?? '',
            $country,
            $item['cost'] ?? 0,
        ]);
    }

    recalculateAllPrices();

    $deleteStmt = $pdo->prepare('DELETE FROM products WHERE synced_at < ?');
    $deleteStmt->execute([$syncStartedAt]);
    $deleted = $deleteStmt->rowCount();
    logMessage("Удалено устаревших товаров: {$deleted}");

    setSetting('last_sync_at', date('Y-m-d H:i:s'));

    $pdo->commit();

    $pdo->prepare("UPDATE sync_log SET finished_at = CURRENT_TIMESTAMP, items_count = ?, status = 'success', error_message = NULL WHERE id = ?")
        ->execute([$itemsCount, $logId]);

    logMessage('Синхронизация завершена успешно.');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $errorMessage = $e->getMessage();
    logMessage('Ошибка: ' . $errorMessage);

    $pdo->prepare("UPDATE sync_log SET finished_at = CURRENT_TIMESTAMP, items_count = ?, status = 'error', error_message = ? WHERE id = ?")
        ->execute([$itemsCount, $errorMessage, $logId]);

    exit(1);
}
