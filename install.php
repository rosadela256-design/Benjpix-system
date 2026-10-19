<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
$cfg = load_config();

$localConfig = __DIR__ . '/config/database.local.php';
$alreadyInstalled = file_exists($localConfig);

$error = null;
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled) {
    $host = trim((string)($_POST['host'] ?? 'localhost'));
    $port = trim((string)($_POST['port'] ?? '3306'));
    $name = trim((string)($_POST['name'] ?? 'binjpix'));
    $user = trim((string)($_POST['dbuser'] ?? 'root'));
    $pass = (string)($_POST['dbpass'] ?? '');

    $adminName  = trim((string)($_POST['admin_name'] ?? ''));
    $adminEmail = trim((string)($_POST['admin_email'] ?? ''));
    $adminPass  = (string)($_POST['admin_pass'] ?? '');

    if ($adminEmail === '' || $adminPass === '') {
        $error = 'Please set an owner/staff email and password.';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Connect without a database first, create it if needed.
            $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$name`");

            $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
            foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
                $pdo->exec($statement);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO users (display_name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $adminName ?: strtok($adminEmail, '@'),
                $adminEmail,
                password_hash($adminPass, PASSWORD_DEFAULT),
                'owner',
                date('Y-m-d H:i:s'),
            ]);

            $configCode = "<?php\n" .
                "define('DB_HOST', " . var_export($host, true) . ");\n" .
                "define('DB_PORT', " . var_export($port, true) . ");\n" .
                "define('DB_NAME', " . var_export($name, true) . ");\n" .
                "define('DB_USER', " . var_export($user, true) . ");\n" .
                "define('DB_PASS', " . var_export($pass, true) . ");\n";
            file_put_contents($localConfig, $configCode);

            $done = true;
        } catch (Throwable $ex) {
            $error = 'Setup failed: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($cfg['shop_name']) ?> — Install</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="assets/img/favicon-64.png">
</head>
<body><main class="wrap" style="padding:56px 28px;">

<?php if ($alreadyInstalled): ?>
  <div class="section-head"><h2>Already installed</h2></div>
  <p>A database is already configured. Delete <code>config/database.local.php</code> if you need to run setup again.</p>
  <p style="margin-top:14px;"><a class="btn" href="login.php">Go to staff sign-in</a></p>

<?php elseif ($done): ?>
  <div class="section-head"><h2>Setup complete</h2></div>
  <p>The database and your staff account were created.</p>
  <p style="margin-top:14px;"><a class="btn" href="login.php">Sign in now</a></p>

<?php else: ?>
  <div class="section-head"><h2><?= e($cfg['shop_name']) ?> setup</h2><p>Run this once to create the database, tables, and your staff account.</p></div>
  <?php if ($error): ?><div class="msg err" style="max-width:480px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form class="card-panel" method="post" style="max-width:480px;">
    <div class="field"><label>MySQL host</label><input name="host" value="localhost"></div>
    <div class="row2">
      <div class="field"><label>Port</label><input name="port" value="3306"></div>
      <div class="field"><label>Database name</label><input name="name" value="binjpix"></div>
    </div>
    <div class="row2">
      <div class="field"><label>MySQL user</label><input name="dbuser" value="root"></div>
      <div class="field"><label>MySQL password</label><input type="password" name="dbpass"></div>
    </div>
    <hr style="border:none;border-top:1px solid var(--line);margin:18px 0;">
    <div class="field"><label>Staff email (used to sign in)</label><input type="email" name="admin_email" required></div>
    <div class="row2">
      <div class="field"><label>Display name (optional)</label><input name="admin_name"></div>
      <div class="field"><label>Staff password</label><input type="password" name="admin_pass" required></div>
    </div>
    <button class="btn" type="submit">Run setup</button>
  </form>
<?php endif; ?>

</main></body></html>
