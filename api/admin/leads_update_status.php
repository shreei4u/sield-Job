<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('admin');
$b = read_json_body();

$userId = require_positive_int($b['user_id'] ?? null, 'user_id');
$status = require_in_list((string)($b['status'] ?? ''), ['New', 'Contacted', 'Converted', 'Lost'], 'status');

$pdo = getDbConnection();
$pdo->prepare(
    'INSERT INTO jp_crm_lead_status (user_id, status, updated_by, updated_at) VALUES (:uid, :status, :admin, NOW())
     ON DUPLICATE KEY UPDATE status = VALUES(status), updated_by = VALUES(updated_by), updated_at = NOW()'
)->execute(['uid' => $userId, 'status' => $status, 'admin' => $me['user_id']]);
json_response(['success' => true]);
