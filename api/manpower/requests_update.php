<?php
/** POST /api/manpower/requests_update.php — provider updates a request's status */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('manpower');
$b = read_json_body();

$id = require_positive_int($b['request_id'] ?? null, 'request_id');
$status = require_in_list((string)($b['status'] ?? ''), ['New', 'In Progress', 'Fulfilled', 'Cancelled'], 'status');

$pdo = getDbConnection();
require_owned_row($pdo, 'jp_manpower_deployment_requests', 'request_id', $id, 'provider_id', $me['user_id']);
$pdo->prepare('UPDATE jp_manpower_deployment_requests SET status = :s WHERE request_id = :id')
    ->execute(['s' => $status, 'id' => $id]);
json_response(['success' => true]);
