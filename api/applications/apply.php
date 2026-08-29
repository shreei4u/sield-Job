<?php
/** POST /api/applications/apply.php — job seeker applies to a job */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('jobseeker');
$b = read_json_body();

$jobId = require_positive_int($b['job_id'] ?? null, 'job_id');
$pdo = getDbConnection();

$job = $pdo->prepare("SELECT job_id FROM jp_job_postings WHERE job_id = :id AND review_status = 'Published' AND open_status = 'Open'");
$job->execute(['id' => $jobId]);
if (!$job->fetch()) {
    json_error('This job is not open for applications.', 404);
}

$dup = $pdo->prepare('SELECT application_id FROM jp_job_applications WHERE job_id = :jid AND jobseeker_id = :uid');
$dup->execute(['jid' => $jobId, 'uid' => $me['user_id']]);
if ($dup->fetch()) {
    json_error('You already applied to this job.', 409);
}

$pdo->prepare('INSERT INTO jp_job_applications (job_id, jobseeker_id) VALUES (:jid, :uid)')
    ->execute(['jid' => $jobId, 'uid' => $me['user_id']]);

json_response(['success' => true]);
