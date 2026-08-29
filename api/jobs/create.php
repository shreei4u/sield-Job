<?php
/** POST /api/jobs/create.php — employer creates a job posting */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('employer');
$b = read_json_body();

$title       = trim((string)($b['title'] ?? ''));
$companyName = trim((string)($b['company_name'] ?? ''));
$location    = trim((string)($b['location'] ?? ''));
$empType     = (string)($b['employment_type'] ?? '');

if ($title === '' || $companyName === '' || $location === '') {
    json_error('title, company_name and location are required.');
}
require_in_list($empType, ['Full-time', 'Part-time', 'Contract', 'Temporary'], 'employment_type');

$hiringAssistance = (string)($b['hiring_assistance'] ?? 'normal');
require_in_list($hiringAssistance, ['normal', 'custom'], 'hiring_assistance');

$pdo = getDbConnection();
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO jp_job_postings
         (employer_id, title, company_name, department, location, employment_type, compensation,
          summary, responsibilities, requirements, application_instructions, status_update_via,
          close_within, interview_mode, notice_period, special_notes, hiring_assistance, category)
         VALUES
         (:employer_id, :title, :company_name, :department, :location, :employment_type, :compensation,
          :summary, :responsibilities, :requirements, :application_instructions, :status_update_via,
          :close_within, :interview_mode, :notice_period, :special_notes, :hiring_assistance, :category)'
    );
    $stmt->execute([
        'employer_id' => $me['user_id'],
        'title' => $title,
        'company_name' => $companyName,
        'department' => $b['department'] ?? null,
        'location' => $location,
        'employment_type' => $empType,
        'compensation' => $b['compensation'] ?? null,
        'summary' => $b['summary'] ?? null,
        'responsibilities' => $b['responsibilities'] ?? null,
        'requirements' => $b['requirements'] ?? null,
        'application_instructions' => $b['application_instructions'] ?? 'Updated CV',
        'status_update_via' => $b['status_update_via'] ?? null,
        'close_within' => $b['close_within'] ?? null,
        'interview_mode' => $b['interview_mode'] ?? null,
        'notice_period' => $b['notice_period'] ?? null,
        'special_notes' => $b['special_notes'] ?? null,
        'hiring_assistance' => $hiringAssistance,
        'category' => $b['category'] ?? null,
    ]);
    $jobId = (int) $pdo->lastInsertId();

    $checks = is_array($b['background_checks'] ?? null) ? $b['background_checks'] : [];
    $allowedChecks = ['Experience', 'Residential', 'Reference', 'Educational', 'Police'];
    $checkStmt = $pdo->prepare('INSERT INTO jp_job_posting_background_checks (job_id, check_type) VALUES (:job_id, :type)');
    foreach ($checks as $c) {
        if (in_array($c, $allowedChecks, true)) {
            $checkStmt->execute(['job_id' => $jobId, 'type' => $c]);
        }
    }

    $pdo->prepare('INSERT INTO jp_activity_log (description, related_user_id) VALUES (:d, :uid)')
        ->execute(['d' => "New job posted: {$title} at {$companyName}", 'uid' => $me['user_id']]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Job creation failed: ' . $e->getMessage());
    json_error('Could not create job posting.', 500);
}

json_response(['success' => true, 'job_id' => $jobId]);
