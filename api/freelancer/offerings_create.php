<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('freelancer');
$b = read_json_body();

$title = trim((string)($b['title'] ?? ''));
$price = filter_var($b['price'] ?? null, FILTER_VALIDATE_FLOAT);
if ($title === '' || $price === false || $price <= 0) {
    json_error('title and a valid price are required.');
}

$pdo = getDbConnection();
$pdo->prepare(
    'INSERT INTO jp_freelancer_offerings (freelancer_id, title, description, price, delivery_time, category)
     VALUES (:fid, :title, :desc, :price, :delivery, :cat)'
)->execute([
    'fid' => $me['user_id'], 'title' => $title, 'desc' => $b['description'] ?? null,
    'price' => $price, 'delivery' => $b['delivery_time'] ?? null, 'cat' => $b['category'] ?? null,
]);

json_response(['success' => true, 'offering_id' => (int) $pdo->lastInsertId()]);
