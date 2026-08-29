<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_role('trainer');

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM jp_trainer_courses WHERE trainer_id = :tid ORDER BY created_at DESC');
$stmt->execute(['tid' => $me['user_id']]);
json_response(['success' => true, 'courses' => $stmt->fetchAll()]);
