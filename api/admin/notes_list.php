<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
require_role('admin');

$userId = require_positive_int($_GET['user_id'] ?? null, 'user_id');
$pdo = getDbConnection();
$stmt = $pdo->prepare(
    'SELECT n.*, a.name AS created_by_name FROM jp_crm_notes n
     LEFT JOIN jp_users a ON a.user_id = n.created_by
     WHERE n.user_id = :uid ORDER BY n.created_at DESC'
);
$stmt->execute(['uid' => $userId]);
json_response(['success' => true, 'notes' => $stmt->fetchAll()]);
