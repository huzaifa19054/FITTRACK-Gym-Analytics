<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
function verify_csrf()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Security check failed. Please refresh the page and try again.');
    }
}
function require_role($role)
{
    if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("Location: ../login.php");
        exit;
    }
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf();
