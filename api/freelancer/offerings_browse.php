<?php
/** GET /api/freelancer/offerings_browse.php — published offerings feed (employers browse this) */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
require_login();

$pdo = getDbConnection();
$stmt = $pdo->prepare(
    "SELECT o.*, u.name AS freelancer_name FROM jp_freelancer_offerings o
     JOIN jp_users u ON u.user_id = o.freelancer_id
     WHERE o.review_status = 'Published' ORDER BY o.created_at DESC LIMIT 100"
);
$stmt->execute();
json_response(['success' => true, 'offerings' => $stmt->fetchAll()]);
