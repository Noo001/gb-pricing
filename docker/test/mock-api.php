<?php

// Мок API RM Group для локального тестирования
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$method = $body['method'] ?? '';

if ($method === 'pricelist.get') {
    echo json_encode([
        'method' => 'pricelist.get',
        'result' => [
            [
                'items' => [
                    [
                        'brand' => 'Apple',
                        'category' => 'iPhone',
                        'subcategory' => 'iPhone 16 Pro Max',
                        'id' => 'P-99887799',
                        'name' => '16 Pro Max 256 Black',
                        'country' => 'US',
                        'cost' => 100000,
                    ],
                    [
                        'brand' => 'Apple',
                        'category' => 'iPhone',
                        'subcategory' => 'iPhone 16 Pro',
                        'id' => 'P-11223344',
                        'name' => '16 Pro 128 White',
                        'country' => 'EU',
                        'cost' => 85000,
                    ],
                    [
                        'brand' => 'Samsung',
                        'category' => 'Galaxy',
                        'subcategory' => 'S24 Ultra',
                        'id' => 'P-55667788',
                        'name' => 'S24 Ultra 256 Gray',
                        'country' => 'CN',
                        'cost' => 75000,
                    ],
                ],
            ],
        ],
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown method']);
