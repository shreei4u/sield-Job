<?php
/**
 * POST /api/admin/review_update.php
 * Body: { "entity": "job|freelancer_offering|trainer_course|jobseeker_profile", "id": <id>, "status": "Published|Rejected" }
 * Generic approve/reject for every module that has a review_status column.
 */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
require_role('admin');
$b = read_json_body();

$entityMap = [
    'job'                => ['table' => 'jp_job_postings',        'idCol' => 'job_id'],
    'freelancer_offering' => ['table' => 'jp_freelancer_offerings', 'idCol' => 'offering_id'],
    'trainer_course'      => ['table' => 'jp_trainer_courses',      'idCol' => 'course_id'],
    'jobseeker_profile'   => ['table' => 'jp_job_seeker_profiles',  'idCol' => 'profile_id'],
];

$entity = (string)($b['entity'] ?? '');
require_in_list($entity, array_keys($entityMap), 'entity');
$id = require_positive_int($b['id'] ?? null, 'id');
$status = require_in_list((string)($b['status'] ?? ''), ['Published', 'Rejected'], 'status');

$map = $entityMap[$entity];
$pdo = getDbConnection();
$stmt = $pdo->prepare("UPDATE {$map['table']} SET review_status = :status WHERE {$map['idCol']} = :id");
$stmt->execute(['status' => $status, 'id' => $id]);

if ($stmt->rowCount() === 0) {
    json_error('Not found.', 404);
}
json_response(['success' => true]);
