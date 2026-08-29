<?php
/**
 * api/bootstrap.php
 * Included by every endpoint in /api. Sets up a secure session,
 * hides PHP errors from the response, and provides small JSON helpers.
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');   // never leak stack traces / paths to the browser
ini_set('log_errors', '1');

require_once __DIR__ . '/../config/db_config.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,     // site is HTTPS-only (ems.shieldinfrasolutions.in)
    'httponly' => true,     // JS can't read the session cookie
    'samesite' => 'Lax',    // basic CSRF mitigation for state-changing POSTs
]);
session_start();

header('Content-Type: application/json; charset=utf-8');

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function json_error(string $message, int $status = 400): void {
    json_response(['success' => false, 'error' => $message], $status);
}

function require_method(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        json_error('Method not allowed', 405);
    }
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    if (!is_array($data)) {
        json_error('Invalid JSON body', 400);
    }
    return $data;
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'user_id' => (int) $_SESSION['user_id'],
        'name'    => $_SESSION['name'] ?? '',
        'email'   => $_SESSION['email'] ?? '',
        'role'    => $_SESSION['role'] ?? '',
    ];
}

function require_login(): array {
    $user = current_user();
    if ($user === null) {
        json_error('Not logged in', 401);
    }
    return $user;
}

function require_role(string ...$roles): array {
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        json_error('Forbidden for this role', 403);
    }
    return $user;
}

// --- Small shared helpers used across the full API ---

function require_get_or_post(): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
        json_error('Method not allowed', 405);
    }
}

function require_in_list(string $value, array $allowed, string $fieldName): string {
    if (!in_array($value, $allowed, true)) {
        json_error("Invalid {$fieldName}.");
    }
    return $value;
}

function require_positive_int($value, string $fieldName): int {
    $v = filter_var($value, FILTER_VALIDATE_INT);
    if ($v === false || $v <= 0) {
        json_error("Invalid {$fieldName}.");
    }
    return $v;
}

// Confirms $userId owns the row identified by $idColumn = $idValue in $table
// via $ownerColumn, or ends the request with 403/404. Returns the row.
function require_owned_row(PDO $pdo, string $table, string $idColumn, int $idValue, string $ownerColumn, int $userId): array {
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$idColumn} = :id LIMIT 1");
    $stmt->execute(['id' => $idValue]);
    $row = $stmt->fetch();
    if (!$row) {
        json_error('Not found.', 404);
    }
    if ((int) $row[$ownerColumn] !== $userId) {
        json_error('Forbidden.', 403);
    }
    return $row;
}
