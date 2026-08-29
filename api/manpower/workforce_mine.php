<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_role('manpower');
$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM jp_manpower_workforce_pool WHERE provider_id = :pid ORDER BY created_at DESC');
$stmt->execute(['pid' => $me['user_id']]);
json_response(['success' => true, 'pool' => $stmt->fetchAll()]);
