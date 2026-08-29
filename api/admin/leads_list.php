<?php
/** GET /api/admin/leads_list.php — CRM lead list across all users */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
require_role('admin');

$pdo = getDbConnection();
$stmt = $pdo->prepare(
    "SELECT u.user_id, u.name, u.email, u.role, u.created_at,
            COALESCE(l.status, 'New') AS lead_status, l.updated_at AS status_updated_at
     FROM jp_users u LEFT JOIN jp_crm_lead_status l ON l.user_id = u.user_id
     WHERE u.role != 'admin' ORDER BY u.created_at DESC"
);
$stmt->execute();
json_response(['success' => true, 'leads' => $stmt->fetchAll()]);
