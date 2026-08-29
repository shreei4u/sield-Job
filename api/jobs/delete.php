<?php
/** POST /api/jobs/delete.php — employer deletes their own posting */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('employer');
$b = read_json_body();

$jobId = require_positive_int($b['job_id'] ?? null, 'job_id');
$pdo = getDbConnection();
require_owned_row($pdo, 'jp_job_postings', 'job_id', $jobId, 'employer_id', $me['user_id']);

$pdo->prepare('DELETE FROM jp_job_postings WHERE job_id = :id')->execute(['id' => $jobId]);
json_response(['success' => true]);
