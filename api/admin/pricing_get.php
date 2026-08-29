<?php
/** GET /api/admin/pricing_get.php — anyone logged in can read current prices; only admin can change them */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
require_login();

$pdo = getDbConnection();
$row = $pdo->query('SELECT * FROM jp_pricing_settings ORDER BY setting_id DESC LIMIT 1')->fetch();
json_response(['success' => true, 'pricing' => $row ?: null]);
