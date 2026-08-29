<?php
/** POST /api/hire_requests/respond.php — freelancer/trainer accepts or declines */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('freelancer', 'trainer');
$b = read_json_body();

$id = require_positive_int($b['request_id'] ?? null, 'request_id');
$status = require_in_list((string)($b['status'] ?? ''), ['Accepted', 'Declined'], 'status');

$pdo = getDbConnection();
require_owned_row($pdo, 'jp_hire_requests', 'request_id', $id, 'provider_id', $me['user_id']);

$pdo->prepare('UPDATE jp_hire_requests SET status = :status, updated_at = NOW() WHERE request_id = :id')
    ->execute(['status' => $status, 'id' => $id]);
json_response(['success' => true]);
