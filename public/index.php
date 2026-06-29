<?php

require_once __DIR__ . '/../src/auth.php';

$user = getCurrentUser();

if (!$user) {
    header('Location: /login.php');
    exit;
}

if ($user['role'] === 'admin') {
    header('Location: /admin/');
} else {
    header('Location: /seller/catalog.php');
}
exit;
