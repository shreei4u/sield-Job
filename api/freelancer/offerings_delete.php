<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('freelancer');
$b = read_json_body();
$id = require_positive_int($b['offering_id'] ?? null, 'offering_id');

$pdo = getDbConnection();
require_owned_row($pdo, 'jp_freelancer_offerings', 'offering_id', $id, 'freelancer_id', $me['user_id']);
$pdo->prepare('DELETE FROM jp_freelancer_offerings WHERE offering_id = :id')->execute(['id' => $id]);
json_response(['success' => true]);
