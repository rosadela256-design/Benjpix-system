<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_staff();
$pdo = get_db();

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT id, full_name, phone, email FROM customers';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE full_name LIKE ? OR phone LIKE ?';
    $params = ["%$q%", "%$q%"];
}
$sql .= ' ORDER BY full_name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$orderStmt = $pdo->prepare(
    'SELECT reference, service_type, status, created_at FROM requests WHERE customer_id = ? ORDER BY created_at DESC'
);

$pageTitle = 'Customers';
require __DIR__ . '/_header.php';
?>

<div class="section-head"><h2>Customers &amp; order records</h2><p>Search by name or contact number.</p></div>

<form class="lookup" method="get">
  <input name="q" placeholder="Search name or phone" value="<?= e($q) ?>">
  <button class="btn" type="submit">Search</button>
</form>

<?php foreach ($customers as $c): $orderStmt->execute([$c['id']]); $orders = $orderStmt->fetchAll(); ?>
  <div class="card-panel" style="margin-top:20px;max-width:none;">
    <strong><?= e($c['full_name']) ?></strong>
    <span style="color:#55504a;font-size:.9rem;"> · <?= e($c['phone']) ?><?= $c['email'] ? ' · ' . e($c['email']) : '' ?></span>
    <table>
      <thead><tr><th>Ref</th><th>Service</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= e($o['reference']) ?></td>
            <td><?= e($o['service_type']) ?></td>
            <td><span class="<?= status_class($o['status']) ?>"><?= e($o['status']) ?></span></td>
            <td><?= e($o['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endforeach; ?>
<?php if (!$customers): ?><p class="empty">No customers found.</p><?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>
