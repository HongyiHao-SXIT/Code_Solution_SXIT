<?php

declare(strict_types=1);

/**
 * Authentication helpers: login, logout, session checks.
 */

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function loginUser(array $user): void
{
    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_account'] = $user['account'];
    $_SESSION['user_role'] = $user['role'] ?? 'student';
}

function logoutUser(): void
{
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            ['expires' => time() - 42000] + $params,
        );
    }
    session_destroy();
}

function isLoggedIn(): bool
{
    startSession();
    return isset($_SESSION['user_id']);
}

function currentUserId(): ?int
{
    startSession();
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function currentUserAccount(): ?string
{
    startSession();
    return $_SESSION['user_account'] ?? null;
}

function currentUserRole(): string
{
    startSession();
    return $_SESSION['user_role'] ?? 'student';
}

function isAdmin(): bool
{
    return currentUserRole() === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        echo "Access denied. Admin only.";
        exit;
    }
}