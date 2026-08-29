<?php
/** POST /api/hire_requests/create.php — employer requests a freelancer offering or trainer course */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('employer');
$b = read_json_body();

$targetType = require_in_list((string)($b['target_type'] ?? ''), ['freelancer_offering', 'trainer_course'], 'target_type');
$targetId = require_positive_int($b['target_id'] ?? null, 'target_id');

$pdo = getDbConnection();
if ($targetType === 'freelancer_offering') {
    $stmt = $pdo->prepare("SELECT freelancer_id AS provider_id FROM jp_freelancer_offerings WHERE offering_id = :id AND review_status = 'Published'");
} else {
    $stmt = $pdo->prepare("SELECT trainer_id AS provider_id FROM jp_trainer_courses WHERE course_id = :id AND review_status = 'Published'");
}
$stmt->execute(['id' => $targetId]);
$target = $stmt->fetch();
if (!$target) {
    json_error('That listing is not available.', 404);
}

$pdo->prepare(
    'INSERT INTO jp_hire_requests (employer_id, target_type, target_id, provider_id, message)
     VALUES (:eid, :type, :tid, :pid, :msg)'
)->execute([
    'eid' => $me['user_id'], 'type' => $targetType, 'tid' => $targetId,
    'pid' => $target['provider_id'], 'msg' => trim((string)($b['message'] ?? '')),
]);

json_response(['success' => true, 'request_id' => (int) $pdo->lastInsertId()]);
