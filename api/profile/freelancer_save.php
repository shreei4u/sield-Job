<?php
/** POST /api/profile/freelancer_save.php — upsert the logged-in freelancer's profile */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('freelancer');
$b = read_json_body();

$data = [
    'professional_title' => trim((string)($b['professional_title'] ?? '')),
    'experience'          => trim((string)($b['experience'] ?? '')),
    'portfolio_url'       => trim((string)($b['portfolio_url'] ?? '')),
    'skills'              => trim((string)($b['skills'] ?? '')),
    'about_bio'           => trim((string)($b['about_bio'] ?? '')),
];

$pdo = getDbConnection();
$exists = $pdo->prepare('SELECT profile_id FROM jp_freelancer_profiles WHERE user_id = :uid');
$exists->execute(['uid' => $me['user_id']]);

if ($exists->fetch()) {
    $sql = 'UPDATE jp_freelancer_profiles SET ' . implode(', ', array_map(fn($f) => "$f = :$f", array_keys($data)))
         . ', updated_at = NOW() WHERE user_id = :uid';
} else {
    $cols = array_keys($data);
    $sql = 'INSERT INTO jp_freelancer_profiles (user_id, ' . implode(', ', $cols) . ')
            VALUES (:uid, ' . implode(', ', array_map(fn($f) => ":$f", $cols)) . ')';
}
$data['uid'] = $me['user_id'];

$pdo->prepare($sql)->execute($data);
json_response(['success' => true]);
