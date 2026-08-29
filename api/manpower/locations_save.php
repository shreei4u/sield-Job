<?php
/** POST /api/manpower/locations_save.php — replaces the provider's full list of service locations */
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');
$me = require_role('manpower');
$b = read_json_body();

$locations = is_array($b['locations'] ?? null) ? array_filter(array_map('trim', $b['locations'])) : [];

$pdo = getDbConnection();
$pdo->beginTransaction();
try {
    $pdo->prepare('DELETE FROM jp_manpower_service_locations WHERE provider_id = :pid')->execute(['pid' => $me['user_id']]);
    $ins = $pdo->prepare('INSERT INTO jp_manpower_service_locations (provider_id, location_name) VALUES (:pid, :loc)');
    foreach ($locations as $loc) {
        if ($loc !== '') {
            $ins->execute(['pid' => $me['user_id'], 'loc' => $loc]);
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error('Could not save locations.', 500);
}
json_response(['success' => true]);
