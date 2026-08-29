<?php
/**
 * SHARED HOSTING ONLY. Copy this file to db_config.local.php (same folder)
 * and fill in your real values. db_config.local.php is gitignored so real
 * credentials never get committed. Docker deployments should ignore this
 * file entirely — docker-compose.yml supplies these as env vars instead.
 */
define('DB_HOST_LOCAL', '127.0.0.1');
define('DB_PORT_LOCAL', '3306');
define('DB_NAME_LOCAL', 'your_database_name');
define('DB_USER_LOCAL', 'your_database_user');
define('DB_PASS_LOCAL', 'your_database_password');
