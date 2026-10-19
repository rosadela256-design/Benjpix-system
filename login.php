<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    header('Location: staff/dashboard.php');
    exit;
}

$cfg = load_config();
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (attempt_login($email, $password)) {
        header('Location: staff/dashboard.php');
        exit;
    }
    $error = 'Invalid email or password.';
}

$pageTitle = 'Staff sign-in';
require __DIR__ . '/includes/layout_header.php';
?>

<div class="section-head">
  <h2>Staff sign-in</h2>
  <p>For <?= e($cfg['shop_name']) ?> owner and authorized staff only.</p>
</div>

<?php if ($error): ?><div class="msg err" style="max-width:380px;"><?= e($error) ?></div><?php endif; ?>

<form class="card-panel" method="post" style="max-width:380px;">
  <?= csrf_field() ?>
  <div class="field"><label>Email</label><input required type="email" name="email" autofocus autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>"></div>
  <div class="field"><label>Password</label><input required type="password" name="password" autocomplete="current-password"></div>
  <button class="btn" type="submit">Sign in</button>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
