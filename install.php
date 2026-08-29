<?php
/**
 * Shield Job Portal — Installer
 * --------------------------------
 * Run once by visiting this file in a browser (e.g. https://yourdomain.com/install.php).
 * DELETE THIS FILE from the server once installation succeeds — see the
 * lock file check below, which blocks re-running it, but deleting it
 * entirely is safer than relying on the lock alone.
 *
 * What it does:
 *   1. Detects whether DB env vars are already set (Docker) or asks for
 *      connection details (shared hosting) and writes config/db_config.local.php.
 *   2. Tests the connection.
 *   3. Runs database/01_schema.sql, 02_login_attempts_addon.sql,
 *      03_feature_addons.sql in order (skipped if already applied — CREATE
 *      TABLE IF NOT EXISTS / ADD COLUMN IF NOT EXISTS make this safe to
 *      re-run against a partially-installed database).
 *   4. Creates the admin account with a securely hashed password — you
 *      type the password into this form, it's never written to disk or
 *      committed anywhere in plain text.
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1'); // OK during install; this file gets deleted right after

$lockFile = __DIR__ . '/install.lock';
if (file_exists($lockFile)) {
    die('This installation is already complete. Delete install.lock (or this file) if you really need to run it again.');
}

$dockerMode = getenv('DB_HOST') !== false && getenv('DB_HOST') !== '';
$errors = [];
$success = false;

function runSqlFile(PDO $pdo, string $path): void {
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException("Could not read {$path}");
    }
    // Strip -- comments, then split on ; at end of line. Safe for our own
    // generated schema files (no semicolons inside string literals).
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(";\n", $sql)));
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') continue;
        $pdo->exec($stmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $dockerMode ? getenv('DB_HOST') : trim((string)($_POST['db_host'] ?? ''));
    $dbPort = $dockerMode ? getenv('DB_PORT') : trim((string)($_POST['db_port'] ?? '3306'));
    $dbName = $dockerMode ? getenv('DB_NAME') : trim((string)($_POST['db_name'] ?? ''));
    $dbUser = $dockerMode ? getenv('DB_USER') : trim((string)($_POST['db_user'] ?? ''));
    $dbPass = $dockerMode ? getenv('DB_PASS') : (string)($_POST['db_pass'] ?? '');

    $adminName = trim((string)($_POST['admin_name'] ?? ''));
    $adminEmail = trim((string)($_POST['admin_email'] ?? ''));
    $adminPassword = (string)($_POST['admin_password'] ?? '');

    if ($dbHost === '' || $dbName === '' || $dbUser === '') {
        $errors[] = 'Database host, name, and user are required.';
    }
    if ($adminName === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid admin name and email are required.';
    }
    if (strlen($adminPassword) < 8 || !preg_match('/[A-Za-z]/', $adminPassword) || !preg_match('/[0-9]/', $adminPassword)) {
        $errors[] = 'Admin password must be at least 8 characters and include a letter and a number.';
    }

    if (!$errors) {
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            foreach (['01_schema.sql', '02_login_attempts_addon.sql', '03_feature_addons.sql'] as $file) {
                runSqlFile($pdo, __DIR__ . '/database/' . $file);
            }

            $check = $pdo->prepare('SELECT user_id FROM jp_users WHERE email = :email');
            $check->execute(['email' => $adminEmail]);
            if (!$check->fetch()) {
                $hash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('INSERT INTO jp_users (name, email, password_hash, role, created_at) VALUES (:n, :e, :h, "admin", NOW())')
                    ->execute(['n' => $adminName, 'e' => $adminEmail, 'h' => $hash]);
            }

            if (!$dockerMode) {
                $configContent = "<?php\n" .
                    "define('DB_HOST_LOCAL', " . var_export($dbHost, true) . ");\n" .
                    "define('DB_PORT_LOCAL', " . var_export($dbPort, true) . ");\n" .
                    "define('DB_NAME_LOCAL', " . var_export($dbName, true) . ");\n" .
                    "define('DB_USER_LOCAL', " . var_export($dbUser, true) . ");\n" .
                    "define('DB_PASS_LOCAL', " . var_export($dbPass, true) . ");\n";
                file_put_contents(__DIR__ . '/config/db_config.local.php', $configContent);
            }

            file_put_contents($lockFile, 'Installed ' . date('c') . " by installer.\n");
            $success = true;
        } catch (Throwable $e) {
            $errors[] = 'Installation failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Shield Job Portal — Installer</title>
<style>
  body { font-family: system-ui, sans-serif; max-width: 560px; margin: 40px auto; padding: 0 20px; color: #222; }
  h1 { font-size: 1.4rem; }
  label { display: block; margin-top: 12px; font-weight: 600; font-size: 0.9rem; }
  input { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; }
  button { margin-top: 20px; padding: 10px 18px; background: #c0392b; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
  .error { background: #fdecea; color: #c0392b; padding: 10px; border-radius: 4px; margin-top: 12px; }
  .success { background: #eafaf1; color: #1e7e34; padding: 14px; border-radius: 4px; }
  .hint { color: #666; font-size: 0.85rem; }
</style>
</head>
<body>
<h1>Shield Job Portal — Installer</h1>

<?php if ($success): ?>
  <div class="success">
    <strong>Installation complete.</strong> The database is set up and the admin account was created.
    <br><br>
    <strong>Delete <code>install.php</code> from the server now</strong> — leave a script that can (re)create
    admin accounts publicly reachable is a security risk. <code>install.lock</code> alone blocks re-running it,
    but deleting the file is safer.
  </div>
<?php else: ?>

  <?php if ($errors): ?>
    <div class="error"><?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?></div>
  <?php endif; ?>

  <?php if ($dockerMode): ?>
    <p class="hint">Docker environment detected — database connection details come from environment
    variables automatically. Just set your admin account below.</p>
  <?php endif; ?>

  <form method="post">
    <?php if (!$dockerMode): ?>
      <h3>Database connection</h3>
      <label>Host</label><input name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? '') ?>" required>
      <label>Port</label><input name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>">
      <label>Database name</label><input name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
      <label>Database user</label><input name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
      <label>Database password</label><input type="password" name="db_pass">
    <?php endif; ?>

    <h3>Admin account</h3>
    <label>Full name</label><input name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
    <label>Email</label><input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
    <label>Password</label><input type="password" name="admin_password" required>
    <p class="hint">At least 8 characters, with a letter and a number.</p>

    <button type="submit">Install</button>
  </form>
<?php endif; ?>
</body>
</html>
