<?php
/** GET /api/jobs/mine.php — employer's own postings, any status */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
$me = require_role('employer');

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM jp_job_postings WHERE employer_id = :eid ORDER BY posted_at DESC');
$stmt->execute(['eid' => $me['user_id']]);

json_response(['success' => true, 'jobs' => $stmt->fetchAll()]);
