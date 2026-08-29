<?php
/** POST /api/applications/update_status.php — employer moves an applicant through the ATS pipeline */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('employer');
$b = read_json_body();

$applicationId = require_positive_int($b['application_id'] ?? null, 'application_id');
$status = require_in_list(
    (string)($b['status'] ?? ''),
    ['Pending', 'Shortlisted', 'Screening', 'Interview', 'Offer', 'Hired', 'Rejected'],
    'status'
);

$pdo = getDbConnection();

// Ownership check: the application's job must belong to this employer
$stmt = $pdo->prepare(
    'SELECT a.application_id, a.jobseeker_id, j.employer_id, j.title, u.name AS candidate_name
     FROM jp_job_applications a
     JOIN jp_job_postings j ON j.job_id = a.job_id
     JOIN jp_users u ON u.user_id = a.jobseeker_id
     WHERE a.application_id = :id LIMIT 1'
);
$stmt->execute(['id' => $applicationId]);
$app = $stmt->fetch();
if (!$app) {
    json_error('Not found.', 404);
}
if ((int) $app['employer_id'] !== $me['user_id']) {
    json_error('Forbidden.', 403);
}

$pdo->beginTransaction();
try {
    $pdo->prepare('UPDATE jp_job_applications SET status = :status, updated_at = NOW() WHERE application_id = :id')
        ->execute(['status' => $status, 'id' => $applicationId]);

    // Keep the ATS pipeline board in sync for stages it tracks
    $atsStages = ['Screening', 'Interview', 'Offer', 'Hired'];
    $pipelineStage = $status === 'Shortlisted' ? 'Applied' : $status;
    if (in_array($pipelineStage, array_merge(['Applied'], $atsStages), true)) {
        $existing = $pdo->prepare('SELECT pipeline_id FROM jp_ats_pipeline WHERE application_id = :aid');
        $existing->execute(['aid' => $applicationId]);
        if ($existing->fetch()) {
            $pdo->prepare('UPDATE jp_ats_pipeline SET stage = :stage WHERE application_id = :aid')
                ->execute(['stage' => $pipelineStage, 'aid' => $applicationId]);
        } else {
            $pdo->prepare(
                'INSERT INTO jp_ats_pipeline (application_id, candidate_name, role_title, stage) VALUES (:aid, :name, :title, :stage)'
            )->execute([
                'aid' => $applicationId, 'name' => $app['candidate_name'], 'title' => $app['title'], 'stage' => $pipelineStage,
            ]);
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Application status update failed: ' . $e->getMessage());
    json_error('Update failed.', 500);
}

json_response(['success' => true]);
