<?php
/** GET /api/hire_requests/incoming.php — freelancer/trainer sees requests addressed to them */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_role('freelancer', 'trainer');

$pdo = getDbConnection();
$stmt = $pdo->prepare(
    'SELECT r.*, u.name AS employer_name, u.email AS employer_email
     FROM jp_hire_requests r JOIN jp_users u ON u.user_id = r.employer_id
     WHERE r.provider_id = :pid ORDER BY r.created_at DESC'
);
$stmt->execute(['pid' => $me['user_id']]);
json_response(['success' => true, 'requests' => $stmt->fetchAll()]);
