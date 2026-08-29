<?php
/** POST /api/admin/users_delete.php — remove a user account (cascades to their data) */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
require_role('admin');
$b = read_json_body();

$id = require_positive_int($b['user_id'] ?? null, 'user_id');
$pdo = getDbConnection();

$role = $pdo->prepare('SELECT role FROM jp_users WHERE user_id = :id');
$role->execute(['id' => $id]);
$r = $role->fetchColumn();
if ($r === 'admin') {
    json_error('Cannot delete an admin account through this endpoint.', 403);
}
if ($r === false) {
    json_error('Not found.', 404);
}

$pdo->prepare('DELETE FROM jp_users WHERE user_id = :id')->execute(['id' => $id]);
json_response(['success' => true]);
