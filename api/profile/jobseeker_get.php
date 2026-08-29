<?php
/** GET /api/profile/jobseeker_get.php — own profile, or ?user_id= for admin/employer viewing a public one */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_login();

$pdo = getDbConnection();
if (!empty($_GET['user_id']) && $me['role'] !== 'jobseeker') {
    $userId = require_positive_int($_GET['user_id'], 'user_id');
} else {
    $userId = $me['user_id'];
}

$stmt = $pdo->prepare('SELECT * FROM jp_job_seeker_profiles WHERE user_id = :uid LIMIT 1');
$stmt->execute(['uid' => $userId]);
$profile = $stmt->fetch();

json_response(['success' => true, 'profile' => $profile ?: null]);
