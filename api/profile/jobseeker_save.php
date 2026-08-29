<?php
/** POST /api/profile/jobseeker_save.php — upsert the logged-in job seeker's profile */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('jobseeker');
$b = read_json_body();

$fields = [
    'desired_title', 'total_experience', 'qualification', 'projects_done',
    'current_company', 'current_location', 'desired_location', 'current_salary',
    'expected_salary', 'notice_period', 'reason_for_change', 'key_skills',
    'about_summary', 'whatsapp_number', 'linkedin_url', 'project_link',
    'reference_1', 'reference_2',
];
$data = [];
foreach ($fields as $f) {
    $data[$f] = isset($b[$f]) ? trim((string) $b[$f]) : null;
}
$maritalStatus = $b['marital_status'] ?? null;
if ($maritalStatus !== null) {
    require_in_list($maritalStatus, ['Married', 'Not Married'], 'marital_status');
}

$pdo = getDbConnection();
$exists = $pdo->prepare('SELECT profile_id FROM jp_job_seeker_profiles WHERE user_id = :uid');
$exists->execute(['uid' => $me['user_id']]);

if ($exists->fetch()) {
    $sql = 'UPDATE jp_job_seeker_profiles SET ' . implode(', ', array_map(fn($f) => "$f = :$f", array_keys($data)))
         . ', marital_status = :marital_status, updated_at = NOW() WHERE user_id = :uid';
} else {
    $cols = array_keys($data);
    $sql = 'INSERT INTO jp_job_seeker_profiles (user_id, marital_status, ' . implode(', ', $cols) . ')
            VALUES (:uid, :marital_status, ' . implode(', ', array_map(fn($f) => ":$f", $cols)) . ')';
}
$data['uid'] = $me['user_id'];
$data['marital_status'] = $maritalStatus;

$stmt = $pdo->prepare($sql);
$stmt->execute($data);

json_response(['success' => true]);
