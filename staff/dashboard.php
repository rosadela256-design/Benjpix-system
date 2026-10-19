<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_staff();

$pdo = get_db();
$counts = ['Pending' => 0, 'Processing' => 0, 'Ready' => 0, 'Completed' => 0];
$stmt = $pdo->query('SELECT status, COUNT(*) AS c FROM requests GROUP BY status');
foreach ($stmt as $row) {
    $counts[$row['status']] = (int)$row['c'];
}
$recent = $pdo->query(
    'SELECT r.reference, c.full_name, r.service_type, r.status, r.created_at
     FROM requests r JOIN customers c ON c.id = r.customer_id
     ORDER BY r.created_at DESC LIMIT 8'
)->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/_header.php';
?>

<div class="section-head"><h2>Dashboard</h2><p>Signed in as <?= e($user['display_name']) ?> (<?= e($user['role']) ?>).</p></div>

<div class="svc-grid" style="grid-template-columns:repeat(4,1fr);">
  <?php foreach ($counts as $status => $n): ?>
    <div class="svc" style="padding:18px;">
      <span class="<?= status_class($status) ?>"><?= e($status) ?></span>
      <h3 style="margin-top:10px;font-size:1.6rem;"><?= $n ?></h3>
    </div>
  <?php endforeach; ?>
</div>

<div class="section-head" style="margin-top:44px;"><h2>Recent requests</h2></div>
<table>
  <thead><tr><th>Ref</th><th>Customer</th><th>Service</th><th>Status</th><th>Submitted</th></tr></thead>
  <tbody>
    <?php foreach ($recent as $r): ?>
      <tr>
        <td><?= e($r['reference']) ?></td>
        <td><?= e($r['full_name']) ?></td>
        <td><?= e($r['service_type']) ?></td>
        <td><span class="<?= status_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
        <td><?= e($r['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="5" class="empty">No requests yet.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/_footer.php'; ?>
