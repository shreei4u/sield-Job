<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('admin');
$b = read_json_body();

$userId = require_positive_int($b['user_id'] ?? null, 'user_id');
$note = trim((string)($b['note_text'] ?? ''));
if ($note === '') {
    json_error('note_text is required.');
}

$pdo = getDbConnection();
$pdo->prepare('INSERT INTO jp_crm_notes (user_id, note_text, created_by) VALUES (:uid, :note, :admin)')
    ->execute(['uid' => $userId, 'note' => $note, 'admin' => $me['user_id']]);
json_response(['success' => true]);
