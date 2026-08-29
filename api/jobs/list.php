<?php
/** GET /api/jobs/list.php — public: published + open jobs, with filters */
require_once __DIR__ . '/../bootstrap.php';
require_method('GET');

$pdo = getDbConnection();
$where = ["review_status = 'Published'", "open_status = 'Open'"];
$params = [];

if (!empty($_GET['title'])) {
    $where[] = '(title LIKE :title OR company_name LIKE :title)';
    $params['title'] = '%' . $_GET['title'] . '%';
}
if (!empty($_GET['location'])) {
    $where[] = 'location LIKE :location';
    $params['location'] = '%' . $_GET['location'] . '%';
}
if (!empty($_GET['category'])) {
    $where[] = 'category = :category';
    $params['category'] = $_GET['category'];
}

$sql = 'SELECT job_id, employer_id, title, company_name, department, location, employment_type,
               compensation, summary, category, posted_at
        FROM jp_job_postings WHERE ' . implode(' AND ', $where) . ' ORDER BY posted_at DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

json_response(['success' => true, 'jobs' => $stmt->fetchAll()]);
