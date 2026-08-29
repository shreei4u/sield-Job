<?php
/** GET /api/applications/for_job.php?job_id= — employer views applicants for their own job */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_role('employer');

$jobId = require_positive_int($_GET['job_id'] ?? null, 'job_id');
$pdo = getDbConnection();
require_owned_row($pdo, 'jp_job_postings', 'job_id', $jobId, 'employer_id', $me['user_id']);

$stmt = $pdo->prepare(
    'SELECT a.application_id, a.status, a.applied_at, u.user_id, u.name, u.email, u.phone,
            p.desired_title, p.total_experience, p.resume_file_url
     FROM jp_job_applications a
     JOIN jp_users u ON u.user_id = a.jobseeker_id
     LEFT JOIN jp_job_seeker_profiles p ON p.user_id = u.user_id
     WHERE a.job_id = :jid ORDER BY a.applied_at DESC'
);
$stmt->execute(['jid' => $jobId]);

json_response(['success' => true, 'applications' => $stmt->fetchAll()]);
