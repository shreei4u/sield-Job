<?php
/** POST /api/contractor/project_request_create.php — employer requests project execution from a contractor
 *  (stored as a "received bid" so it appears in the contractor's Received Bids list) */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('employer');
$b = read_json_body();

$contractorId = require_positive_int($b['contractor_id'] ?? null, 'contractor_id');
$amount = filter_var($b['budget'] ?? 0, FILTER_VALIDATE_FLOAT);
if ($amount === false || $amount < 0) {
    json_error('A valid budget is required.');
}

$pdo = getDbConnection();
$contractor = $pdo->prepare("SELECT user_id FROM jp_users WHERE user_id = :id AND role = 'contractor'");
$contractor->execute(['id' => $contractorId]);
if (!$contractor->fetch()) {
    json_error('Contractor not found.', 404);
}

$nameStmt = $pdo->prepare('SELECT name, phone FROM jp_users WHERE user_id = :id');
$nameStmt->execute(['id' => $me['user_id']]);
$employer = $nameStmt->fetch();

$pdo->prepare(
    'INSERT INTO jp_contractor_received_bids (contractor_id, employer_id, project_id, bidder_name, bid_amount, contact_info)
     VALUES (:cid, :eid, :pid, :bname, :amount, :contact)'
)->execute([
    'cid' => $contractorId, 'eid' => $me['user_id'], 'pid' => $b['project_id'] ?? null,
    'bname' => $employer['name'] ?? 'Employer', 'amount' => $amount, 'contact' => $employer['phone'] ?? null,
]);
json_response(['success' => true]);
