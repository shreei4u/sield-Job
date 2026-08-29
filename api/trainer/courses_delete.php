<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('trainer');
$b = read_json_body();
$id = require_positive_int($b['course_id'] ?? null, 'course_id');

$pdo = getDbConnection();
require_owned_row($pdo, 'jp_trainer_courses', 'course_id', $id, 'trainer_id', $me['user_id']);
$pdo->prepare('DELETE FROM jp_trainer_courses WHERE course_id = :id')->execute(['id' => $id]);
json_response(['success' => true]);
