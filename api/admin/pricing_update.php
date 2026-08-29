<?php
/** POST /api/admin/pricing_update.php — admin updates platform pricing */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('admin');
$b = read_json_body();

$fields = ['ats_boost_price', 'employer_normal_posting_price', 'hiring_assistant_starting_price', 'premium_subscription_price'];
$values = [];
foreach ($fields as $f) {
    if (isset($b[$f])) {
        $v = filter_var($b[$f], FILTER_VALIDATE_FLOAT);
        if ($v === false || $v < 0) {
            json_error("Invalid {$f}.");
        }
        $values[$f] = $v;
    }
}
if (!$values) {
    json_error('No pricing fields provided.');
}

$pdo = getDbConnection();
$row = $pdo->query('SELECT setting_id FROM jp_pricing_settings ORDER BY setting_id DESC LIMIT 1')->fetch();

$priceCols = array_keys($values); // pricing fields only, captured before 'admin' is added below
$sets = implode(', ', array_map(fn($f) => "$f = :$f", $priceCols));
$values['admin'] = $me['user_id'];

if ($row) {
    $values['id'] = $row['setting_id'];
    $pdo->prepare("UPDATE jp_pricing_settings SET {$sets}, updated_by = :admin, updated_at = NOW() WHERE setting_id = :id")
        ->execute($values);
} else {
    $insertCols = array_merge($priceCols, ['updated_by']);
    $placeholders = array_merge(array_map(fn($c) => ":$c", $priceCols), [':admin']);
    $pdo->prepare('INSERT INTO jp_pricing_settings (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $placeholders) . ')')
        ->execute($values);
}
json_response(['success' => true]);
