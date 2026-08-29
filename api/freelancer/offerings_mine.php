<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_role('freelancer');

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM jp_freelancer_offerings WHERE freelancer_id = :fid ORDER BY created_at DESC');
$stmt->execute(['fid' => $me['user_id']]);
json_response(['success' => true, 'offerings' => $stmt->fetchAll()]);
