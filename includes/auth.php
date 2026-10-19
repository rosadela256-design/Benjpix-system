<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_staff(): array
{
    $user = current_user();
    if (!$user) {
        // require_staff() is only ever called from staff/*.php, so go up one level.
        header('Location: ../login.php');
        exit;
    }
    return $user;
}

/** Attempts a staff login by email. Returns true and regenerates the session on success. */
function attempt_login(string $email, string $password): bool
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, display_name, email, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        usleep(300000); // slow down brute-force guessing
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'           => (int)$row['id'],
        'display_name' => $row['display_name'],
        'email'        => $row['email'],
        'role'         => $row['role'],
    ];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}
