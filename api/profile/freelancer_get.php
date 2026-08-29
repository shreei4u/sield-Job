<?php
/** GET /api/profile/freelancer_get.php — own profile, or ?user_id= for anyone browsing */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_login();

$userId = !empty($_GET['user_id']) ? require_positive_int($_GET['user_id'], 'user_id') : $me['user_id'];

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM jp_freelancer_profiles WHERE user_id = :uid LIMIT 1');
$stmt->execute(['uid' => $userId]);

json_response(['success' => true, 'profile' => $stmt->fetch() ?: null]);
