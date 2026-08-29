<?php
/** POST /api/manpower/request_create.php — employer requests deployment from a manpower provider */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('employer');
$b = read_json_body();

$providerId = require_positive_int($b['provider_id'] ?? null, 'provider_id');
$location = trim((string)($b['location'] ?? ''));
$quantity = filter_var($b['quantity'] ?? null, FILTER_VALIDATE_INT);
if ($location === '' || $quantity === false || $quantity < 1) {
    json_error('location and a valid quantity are required.');
}

$pdo = getDbConnection();
$provider = $pdo->prepare("SELECT user_id FROM jp_users WHERE user_id = :id AND role = 'manpower'");
$provider->execute(['id' => $providerId]);
if (!$provider->fetch()) {
    json_error('Provider not found.', 404);
}

$employerName = $pdo->prepare('SELECT name FROM jp_users WHERE user_id = :id');
$employerName->execute(['id' => $me['user_id']]);
$clientName = $employerName->fetchColumn() ?: $me['name'];

$pdo->prepare(
    'INSERT INTO jp_manpower_deployment_requests (provider_id, employer_id, client_name, location, roles_needed, quantity, duration)
     VALUES (:pid, :eid, :client, :loc, :roles, :qty, :dur)'
)->execute([
    'pid' => $providerId, 'eid' => $me['user_id'], 'client' => $clientName, 'loc' => $location,
    'roles' => $b['roles_needed'] ?? null, 'qty' => $quantity, 'dur' => $b['duration'] ?? null,
]);
json_response(['success' => true]);
