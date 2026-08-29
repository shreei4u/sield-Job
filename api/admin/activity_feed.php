<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
require_role('admin');

$pdo = getDbConnection();
$stmt = $pdo->query('SELECT * FROM jp_activity_log ORDER BY created_at DESC LIMIT 50');
json_response(['success' => true, 'activity' => $stmt->fetchAll()]);
