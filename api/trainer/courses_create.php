<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('trainer');
$b = read_json_body();

$title = trim((string)($b['title'] ?? ''));
$fee = filter_var($b['fee'] ?? null, FILTER_VALIDATE_FLOAT);
if ($title === '' || $fee === false || $fee < 0) {
    json_error('title and a valid fee are required.');
}

$pdo = getDbConnection();
$pdo->prepare(
    'INSERT INTO jp_trainer_courses (trainer_id, title, description, fee, duration, category)
     VALUES (:tid, :title, :desc, :fee, :duration, :cat)'
)->execute([
    'tid' => $me['user_id'], 'title' => $title, 'desc' => $b['description'] ?? null,
    'fee' => $fee, 'duration' => $b['duration'] ?? null, 'cat' => $b['category'] ?? null,
]);

json_response(['success' => true, 'course_id' => (int) $pdo->lastInsertId()]);
