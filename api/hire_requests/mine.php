<?php
/** GET /api/hire_requests/mine.php — employer tracks requests they've sent */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_role('employer');

$pdo = getDbConnection();
$stmt = $pdo->prepare(
    'SELECT r.*, u.name AS provider_name FROM jp_hire_requests r
     JOIN jp_users u ON u.user_id = r.provider_id
     WHERE r.employer_id = :eid ORDER BY r.created_at DESC'
);
$stmt->execute(['eid' => $me['user_id']]);
json_response(['success' => true, 'requests' => $stmt->fetchAll()]);
