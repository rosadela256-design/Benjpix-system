<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_staff();
$pdo = get_db();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ref = strtoupper(trim((string)($_POST['reference'] ?? '')));
    $amount = (float)($_POST['amount'] ?? 0);
    $date = (string)($_POST['payment_date'] ?? date('Y-m-d'));

    $stmt = $pdo->prepare('SELECT id FROM requests WHERE reference = ?');
    $stmt->execute([$ref]);
    $requestId = $stmt->fetchColumn();

    if (!$requestId) {
        $error = 'No request found with that reference number.';
    } elseif ($amount <= 0) {
        $error = 'Enter a valid amount.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO payments (request_id, amount, payment_date, recorded_by, created_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$requestId, $amount, $date, $user['id'], date('Y-m-d H:i:s')]);
    }
}

$payments = $pdo->query(
    'SELECT p.amount, p.payment_date, r.reference, c.full_name
     FROM payments p
     JOIN requests r ON r.id = p.request_id
     JOIN customers c ON c.id = r.customer_id
     ORDER BY p.created_at DESC LIMIT 20'
)->fetchAll();

$pageTitle = 'Payments';
require __DIR__ . '/_header.php';
?>

<div class="section-head"><h2>Payments</h2><p>Record a payment against an existing request.</p></div>

<?php if ($error): ?><div class="msg err" style="max-width:460px;"><?= e($error) ?></div><?php endif; ?>

<form class="card-panel" method="post" style="max-width:460px;">
  <?= csrf_field() ?>
  <div class="field"><label>Request reference</label><input required name="reference" placeholder="e.g. BJP-7F3K2Q"></div>
  <div class="row2">
    <div class="field"><label>Amount</label><input required type="number" step="0.01" min="0.01" name="amount"></div>
    <div class="field"><label>Date</label><input required type="date" name="payment_date" value="<?= e(date('Y-m-d')) ?>"></div>
  </div>
  <button class="btn" type="submit">Record payment</button>
</form>

<div class="section-head" style="margin-top:40px;"><h2>Recent payments</h2></div>
<table>
  <thead><tr><th>Ref</th><th>Customer</th><th>Amount</th><th>Date</th></tr></thead>
  <tbody>
    <?php foreach ($payments as $p): ?>
      <tr>
        <td><?= e($p['reference']) ?></td>
        <td><?= e($p['full_name']) ?></td>
        <td>₱<?= number_format((float)$p['amount'], 2) ?></td>
        <td><?= e($p['payment_date']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$payments): ?><tr><td colspan="4" class="empty">No payments recorded yet.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/_footer.php'; ?>
