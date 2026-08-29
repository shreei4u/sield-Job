<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('contractor');
$b = read_json_body();

$projectName = trim((string)($b['project_name'] ?? ''));
$clientName = trim((string)($b['client_name'] ?? ''));
if ($projectName === '' || $clientName === '') {
    json_error('project_name and client_name are required.');
}

$pdo = getDbConnection();
$pdo->beginTransaction();
try {
    $pdo->prepare(
        'INSERT INTO jp_contractor_projects
         (contractor_id, project_name, client_name, location_address, contact_name, contact_number,
          contract_value, start_date, end_date, contract_period, manpower_required, description)
         VALUES (:cid, :pname, :cname, :addr, :cname2, :cnum, :value, :start, :end, :period, :manpower, :desc)'
    )->execute([
        'cid' => $me['user_id'], 'pname' => $projectName, 'cname' => $clientName,
        'addr' => $b['location_address'] ?? null, 'cname2' => $b['contact_name'] ?? null,
        'cnum' => $b['contact_number'] ?? null, 'value' => $b['contract_value'] ?? null,
        'start' => $b['start_date'] ?? null, 'end' => $b['end_date'] ?? null,
        'period' => $b['contract_period'] ?? null, 'manpower' => $b['manpower_required'] ?? null,
        'desc' => $b['description'] ?? null,
    ]);
    $projectId = (int) $pdo->lastInsertId();

    $roles = is_array($b['manpower_roles'] ?? null) ? $b['manpower_roles'] : [];
    $ins = $pdo->prepare('INSERT INTO jp_contractor_project_manpower_roles (project_id, role_name) VALUES (:pid, :role)');
    foreach ($roles as $r) {
        $r = trim((string) $r);
        if ($r !== '') {
            $ins->execute(['pid' => $projectId, 'role' => $r]);
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Project creation failed: ' . $e->getMessage());
    json_error('Could not create project.', 500);
}
json_response(['success' => true, 'project_id' => $projectId]);
