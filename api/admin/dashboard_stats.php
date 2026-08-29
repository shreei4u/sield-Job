<?php
/** GET /api/admin/dashboard_stats.php — KPI tiles for the CRM dashboard */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');
require_role('admin');

$pdo = getDbConnection();
$stats = [
    'total_users'        => (int) $pdo->query("SELECT COUNT(*) FROM jp_users WHERE role != 'admin'")->fetchColumn(),
    'jobseekers'          => (int) $pdo->query("SELECT COUNT(*) FROM jp_users WHERE role = 'jobseeker'")->fetchColumn(),
    'employers'           => (int) $pdo->query("SELECT COUNT(*) FROM jp_users WHERE role = 'employer'")->fetchColumn(),
    'open_jobs'            => (int) $pdo->query("SELECT COUNT(*) FROM jp_job_postings WHERE review_status='Published' AND open_status='Open'")->fetchColumn(),
    'pending_review_jobs'  => (int) $pdo->query("SELECT COUNT(*) FROM jp_job_postings WHERE review_status='Pending'")->fetchColumn(),
    'total_applications'   => (int) $pdo->query('SELECT COUNT(*) FROM jp_job_applications')->fetchColumn(),
    'hired_count'          => (int) $pdo->query("SELECT COUNT(*) FROM jp_job_applications WHERE status='Hired'")->fetchColumn(),
    'premium_subscribers'  => (int) $pdo->query("SELECT COUNT(*) FROM jp_subscriptions WHERE plan='Premium'")->fetchColumn(),
];
json_response(['success' => true, 'stats' => $stats]);
