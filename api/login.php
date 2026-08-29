<?php
/**
 * POST /api/login.php
 * Body (JSON): { "email", "password" }
 *
 * Brute-force protection: 5 failed attempts for the same email+IP
 * within 15 minutes locks that combination out for 15 minutes.
 * Requires jp_login_attempts — import database/02_login_attempts_addon.sql first.
 */
require_once __DIR__ . '/bootstrap.php';

require_method('POST');
$body = read_json_body();

$email    = trim((string)($body['email'] ?? ''));
$password = (string)($body['password'] ?? '');
$ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    json_error('Email and password are required.');
}

$pdo = getDbConnection();

// --- Lockout check ---
$attempts = $pdo->prepare(
    "SELECT COUNT(*) FROM jp_login_attempts
     WHERE email = :email AND ip_address = :ip
       AND succeeded = 0 AND attempted_at > (NOW() - INTERVAL 15 MINUTE)"
);
$attempts->execute(['email' => $email, 'ip' => $ip]);
if ((int) $attempts->fetchColumn() >= 5) {
    json_error('Too many failed attempts. Try again in 15 minutes.', 429);
}

// --- Verify credentials ---
$stmt = $pdo->prepare('SELECT user_id, name, email, role, password_hash FROM jp_users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

$log = $pdo->prepare(
    'INSERT INTO jp_login_attempts (email, ip_address, succeeded) VALUES (:email, :ip, :ok)'
);

if (!$user || !password_verify($password, $user['password_hash'])) {
    $log->execute(['email' => $email, 'ip' => $ip, 'ok' => 0]);
    json_error('Invalid email or password.', 401); // same message either way — don't reveal which was wrong
}

$log->execute(['email' => $email, 'ip' => $ip, 'ok' => 1]);

// --- Establish session ---
session_regenerate_id(true); // prevent session fixation
$_SESSION['user_id'] = (int) $user['user_id'];
$_SESSION['name']    = $user['name'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = $user['role'];

json_response([
    'success' => true,
    'user'    => ['user_id' => (int) $user['user_id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
]);
