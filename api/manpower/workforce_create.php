<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('manpower');
$b = read_json_body();

$roleType = trim((string)($b['role_type'] ?? ''));
$count = filter_var($b['worker_count'] ?? 1, FILTER_VALIDATE_INT);
if ($roleType === '' || $count === false || $count < 1) {
    json_error('role_type and a valid worker_count are required.');
}
$skillLevel = $b['skill_level'] ?? null;
if ($skillLevel !== null) {
    require_in_list($skillLevel, ['Skilled', 'Semi-Skilled', 'Unskilled'], 'skill_level');
}

$pdo = getDbConnection();
$pdo->prepare(
    'INSERT INTO jp_manpower_workforce_pool (provider_id, role_type, skill_level, experience, worker_count, is_available)
     VALUES (:pid, :role, :skill, :exp, :count, 1)'
)->execute([
    'pid' => $me['user_id'], 'role' => $roleType, 'skill' => $skillLevel,
    'exp' => $b['experience'] ?? null, 'count' => $count,
]);
json_response(['success' => true, 'pool_id' => (int) $pdo->lastInsertId()]);
