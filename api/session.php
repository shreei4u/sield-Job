<?php
/** GET /api/session.php — returns the logged-in user, or null */
require_once __DIR__ . '/bootstrap.php';

require_method('GET');

json_response(['success' => true, 'user' => current_user()]);
