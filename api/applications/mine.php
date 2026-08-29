<?php
/** GET /api/applications/mine.php — job seeker's own applications with job details */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_role('jobseeker');

$pdo = getDbConnection();
$stmt = $pdo->prepare(
    'SELECT a.application_id, a.status, a.applied_at, j.job_id, j.title, j.company_name, j.location
     FROM jp_job_applications a JOIN jp_job_postings j ON j.job_id = a.job_id
     WHERE a.jobseeker_id = :uid ORDER BY a.applied_at DESC'
);
$stmt->execute(['uid' => $me['user_id']]);

json_response(['success' => true, 'applications' => $stmt->fetchAll()]);
