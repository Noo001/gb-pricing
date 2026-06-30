<?php

// Настройки сессии до её старта
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => '/',
    'domain' => $cookieParams['domain'],
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function getCurrentUser(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT id, login, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function requireAuth(): array {
    $user = getCurrentUser();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}

function requireRole(string $role): array {
    $user = requireAuth();
    if ($user['role'] !== $role) {
        header('Location: /');
        exit;
    }
    return $user;
}

function login(string $login, string $password): bool {
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT id, login, role, password_hash FROM users WHERE login = ?');
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        return true;
    }

    return false;
}

function logout(): void {
    unset($_SESSION['user_id'], $_SESSION['csrf_token'], $_SESSION['flash']);
    session_regenerate_id(true);
}
