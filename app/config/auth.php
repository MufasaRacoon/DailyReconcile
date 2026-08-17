<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 1800);

session_start();

if (isset($_SESSION['LAST_ACTIVITY']) &&
    time() - $_SESSION['LAST_ACTIVITY'] > 1800) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['LAST_ACTIVITY'] = time();


function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function requireAuthMenu() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: ../../login.php');
        exit;
    }
}

function requireRole(string $role)
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        die('Accès refusé');
    }
}

function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

