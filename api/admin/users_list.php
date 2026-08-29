<?php
/** GET /api/admin/users_list.php?role= — the six role-specific databases */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
require_role('admin');

$role = (string)($_GET['role'] ?? '');
require_in_list($role, ['jobseeker', 'employer', 'freelancer', 'trainer', 'manpower', 'contractor'], 'role');

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT user_id, name, email, phone, created_at FROM jp_users WHERE role = :role ORDER BY created_at DESC');
$stmt->execute(['role' => $role]);
json_response(['success' => true, 'users' => $stmt->fetchAll()]);
