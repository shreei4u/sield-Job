<?php
/**
 * POST /api/register.php
 * Body (JSON): { "name", "email", "phone", "password", "role" }
 * role must be one of: jobseeker, employer, freelancer, trainer, manpower, contractor
 * ("admin" can never be created through this public endpoint — see seed_admin.php)
 */
require_once __DIR__ . '/bootstrap.php';

require_method('POST');
$body = read_json_body();

$name     = trim((string)($body['name'] ?? ''));
$email    = trim((string)($body['email'] ?? ''));
$phone    = trim((string)($body['phone'] ?? ''));
$password = (string)($body['password'] ?? '');
$role     = trim((string)($body['role'] ?? ''));

$allowedRoles = ['jobseeker', 'employer', 'freelancer', 'trainer', 'manpower', 'contractor'];

// --- Validation ---
if ($name === '' || strlen($name) > 150) {
    json_error('Name is required (max 150 characters).');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 180) {
    json_error('A valid email is required.');
}
if (!in_array($role, $allowedRoles, true)) {
    json_error('Invalid role.');
}
if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    json_error('Password must be at least 8 characters and include a letter and a number.');
}
if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) {
    json_error('Invalid phone number.');
}

$pdo = getDbConnection();

// --- Duplicate check ---
$check = $pdo->prepare('SELECT user_id FROM jp_users WHERE email = :email LIMIT 1');
$check->execute(['email' => $email]);
if ($check->fetch()) {
    json_error('An account with this email already exists.', 409);
}

// --- Create the account ---
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare(
        'INSERT INTO jp_users (name, email, phone, password_hash, role, created_at)
         VALUES (:name, :email, :phone, :hash, :role, NOW())'
    );
    $insert->execute([
        'name'  => $name,
        'email' => $email,
        'phone' => $phone !== '' ? $phone : null,
        'hash'  => $hash,
        'role'  => $role,
    ]);
    $userId = (int) $pdo->lastInsertId();

    // Every user starts on the Free plan
    $pdo->prepare('INSERT INTO jp_subscriptions (user_id, plan) VALUES (:uid, \'Free\')')
        ->execute(['uid' => $userId]);

    // Every new account shows up as a New lead in the admin CRM
    $pdo->prepare('INSERT INTO jp_crm_lead_status (user_id, status) VALUES (:uid, \'New\')')
        ->execute(['uid' => $userId]);

    $pdo->prepare('INSERT INTO jp_activity_log (description, related_user_id) VALUES (:d, :uid)')
        ->execute(['d' => "New {$role} registered: {$name}", 'uid' => $userId]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Registration failed: ' . $e->getMessage());
    json_error('Registration failed. Please try again.', 500);
}

// Log the user in immediately after registering
session_regenerate_id(true);
$_SESSION['user_id'] = $userId;
$_SESSION['name']    = $name;
$_SESSION['email']   = $email;
$_SESSION['role']    = $role;

json_response([
    'success' => true,
    'user'    => ['user_id' => $userId, 'name' => $name, 'email' => $email, 'role' => $role],
]);
