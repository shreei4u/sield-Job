<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('manpower');
$b = read_json_body();
$id = require_positive_int($b['pool_id'] ?? null, 'pool_id');
$pdo = getDbConnection();
require_owned_row($pdo, 'jp_manpower_workforce_pool', 'pool_id', $id, 'provider_id', $me['user_id']);
$pdo->prepare('DELETE FROM jp_manpower_workforce_pool WHERE pool_id = :id')->execute(['id' => $id]);
json_response(['success' => true]);
