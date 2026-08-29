<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('contractor');
$b = read_json_body();

$id = require_positive_int($b['bid_id'] ?? null, 'bid_id');
$status = require_in_list((string)($b['status'] ?? ''), ['Accepted', 'Rejected'], 'status');

$pdo = getDbConnection();
require_owned_row($pdo, 'jp_contractor_received_bids', 'bid_id', $id, 'contractor_id', $me['user_id']);
$pdo->prepare('UPDATE jp_contractor_received_bids SET status = :s WHERE bid_id = :id')
    ->execute(['s' => $status, 'id' => $id]);
json_response(['success' => true]);
