<?php
/**
 * GET /api/uploads/resume_download.php?user_id=
 * Streams a candidate's resume — only to: the candidate themselves, an
 * admin, or an employer who has received an application from them.
 */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_login();

$targetUserId = require_positive_int($_GET['user_id'] ?? null, 'user_id');
$pdo = getDbConnection();

$allowed = false;
if ($me['user_id'] === $targetUserId || $me['role'] === 'admin') {
    $allowed = true;
} elseif ($me['role'] === 'employer') {
    $check = $pdo->prepare(
        'SELECT 1 FROM jp_job_applications a JOIN jp_job_postings j ON j.job_id = a.job_id
         WHERE a.jobseeker_id = :cand AND j.employer_id = :emp LIMIT 1'
    );
    $check->execute(['cand' => $targetUserId, 'emp' => $me['user_id']]);
    $allowed = (bool) $check->fetchColumn();
}
if (!$allowed) {
    json_error('Forbidden.', 403);
}

$stmt = $pdo->prepare('SELECT resume_file_name, resume_file_url FROM jp_job_seeker_profiles WHERE user_id = :uid');
$stmt->execute(['uid' => $targetUserId]);
$row = $stmt->fetch();
if (!$row || !$row['resume_file_url']) {
    json_error('No resume on file.', 404);
}

$path = dirname(__DIR__, 2) . '/private_storage/resumes/' . basename($row['resume_file_url']);
if (!is_file($path)) {
    json_error('File missing on server.', 404);
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($row['resume_file_name']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
