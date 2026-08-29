<?php
/** GET /api/manpower/browse.php — employer browses available workforce across all providers */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
require_role('employer');

$pdo = getDbConnection();
$stmt = $pdo->prepare(
    "SELECT w.*, u.name AS provider_name FROM jp_manpower_workforce_pool w
     JOIN jp_users u ON u.user_id = w.provider_id
     WHERE w.is_available = 1 ORDER BY w.created_at DESC LIMIT 100"
);
$stmt->execute();
json_response(['success' => true, 'pool' => $stmt->fetchAll()]);
