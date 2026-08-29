<?php
/**
 * POST /api/uploads/resume_upload.php — multipart/form-data with field "resume"
 * Stores the file OUTSIDE the web root and saves only a reference in the DB.
 * The browser can never request the raw path directly — downloads go through
 * resume_download.php, which checks permission first.
 */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('jobseeker');

if (empty($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    json_error('No file uploaded, or the upload failed.');
}

$file = $_FILES['resume'];
$maxBytes = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $maxBytes) {
    json_error('File too large (max 5MB).');
}

$allowedMimes = [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!isset($allowedMimes[$mime])) {
    json_error('Only PDF, DOC, or DOCX resumes are allowed.');
}
$ext = $allowedMimes[$mime];

// IMPORTANT: adjust this path for your actual server layout — it must be
// a directory OUTSIDE your public web root (not reachable by any URL).
$storageDir = dirname(__DIR__, 2) . '/private_storage/resumes';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0750, true);
}

$safeName = 'resume_' . $me['user_id'] . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$destination = $storageDir . '/' . $safeName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    json_error('Could not save the uploaded file.', 500);
}

$pdo = getDbConnection();
$exists = $pdo->prepare('SELECT profile_id FROM jp_job_seeker_profiles WHERE user_id = :uid');
$exists->execute(['uid' => $me['user_id']]);

if ($exists->fetch()) {
    $pdo->prepare('UPDATE jp_job_seeker_profiles SET resume_file_name = :orig, resume_file_url = :stored, updated_at = NOW() WHERE user_id = :uid')
        ->execute(['orig' => $file['name'], 'stored' => $safeName, 'uid' => $me['user_id']]);
} else {
    $pdo->prepare('INSERT INTO jp_job_seeker_profiles (user_id, resume_file_name, resume_file_url) VALUES (:uid, :orig, :stored)')
        ->execute(['orig' => $file['name'], 'stored' => $safeName, 'uid' => $me['user_id']]);
}

json_response(['success' => true, 'file_name' => $file['name']]);
