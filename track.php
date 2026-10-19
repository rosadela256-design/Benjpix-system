<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$result = null;
$notFound = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['ref'])) {
    $ref = strtoupper(trim((string)$_GET['ref']));
    $pdo = get_db();
    $stmt = $pdo->prepare(
        'SELECT r.reference, r.service_type, r.service_mode, r.quantity, r.claim_method, r.delivery_address,
                r.scheduled_date, r.scheduled_time, r.location, r.status, r.created_at
         FROM requests r WHERE r.reference = ?'
    );
    $stmt->execute([$ref]);
    $result = $stmt->fetch();
    $notFound = !$result;
}

$pageTitle = 'Track order';
require __DIR__ . '/includes/layout_header.php';
?>

<div class="section-head">
  <h2>Track your order</h2>
  <p>Enter the reference number you received after submitting.</p>
</div>

<form class="lookup" method="get">
  <input name="ref" placeholder="e.g. BJP-7F3K2Q" value="<?= e($_GET['ref'] ?? '') ?>">
  <button class="btn" type="submit">Check status</button>
</form>

<?php if ($notFound): ?>
  <div class="msg err" style="max-width:460px;margin-top:18px;">No request found with that reference number.</div>
<?php elseif ($result): ?>
  <div class="card-panel" style="margin-top:20px;">
    <strong><?= e($result['reference']) ?></strong> &nbsp;
    <span class="<?= status_class($result['status']) ?>"><?= e($result['status']) ?></span>
    <p style="margin-top:12px;color:#55504a;font-size:.92rem;">
      <?php if ($result['service_mode'] === 'booking'): ?>
        <?= e($result['service_type']) ?> · <?= e($result['scheduled_date']) ?> at <?= e($result['scheduled_time']) ?>
        · <?= e($result['location']) ?>
      <?php else: ?>
        <?= e($result['service_type']) ?><?= $result['quantity'] ? ' · qty ' . (int)$result['quantity'] : '' ?> · <?= e($result['claim_method']) ?>
        <?= $result['delivery_address'] ? ' to ' . e($result['delivery_address']) : '' ?>
      <?php endif; ?>
    </p>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
