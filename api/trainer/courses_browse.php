<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
require_login();

$pdo = getDbConnection();
$stmt = $pdo->prepare(
    "SELECT c.*, u.name AS trainer_name FROM jp_trainer_courses c
     JOIN jp_users u ON u.user_id = c.trainer_id
     WHERE c.review_status = 'Published' ORDER BY c.created_at DESC LIMIT 100"
);
$stmt->execute();
json_response(['success' => true, 'courses' => $stmt->fetchAll()]);
