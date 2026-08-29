<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_role('contractor');
$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM jp_contractor_received_bids WHERE contractor_id = :cid ORDER BY created_at DESC');
$stmt->execute(['cid' => $me['user_id']]);
json_response(['success' => true, 'bids' => $stmt->fetchAll()]);
