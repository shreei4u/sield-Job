<?php
/** POST /api/jobs/close.php — employer opens/closes their own job */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('employer');
$b = read_json_body();

$jobId = require_positive_int($b['job_id'] ?? null, 'job_id');
$status = require_in_list((string)($b['open_status'] ?? ''), ['Open', 'Closed'], 'open_status');

$pdo = getDbConnection();
require_owned_row($pdo, 'jp_job_postings', 'job_id', $jobId, 'employer_id', $me['user_id']);

$stmt = $pdo->prepare(
    'UPDATE jp_job_postings SET open_status = :status, closed_at = IF(:status2 = "Closed", NOW(), NULL) WHERE job_id = :id'
);
$stmt->execute(['status' => $status, 'status2' => $status, 'id' => $jobId]);

json_response(['success' => true]);
