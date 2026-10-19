<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_staff();
$cfgData = load_config();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    csrf_check();
    $id = (int)($_POST['request_id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    if (in_array($status, $cfgData['statuses'], true) && $id > 0) {
        $stmt = $pdo->prepare('UPDATE requests SET status = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$status, date('Y-m-d H:i:s'), $id]);
    }
    header('Location: requests.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

$filter = $_GET['status'] ?? '';
$sql = 'SELECT r.id, r.reference, c.full_name, c.phone, r.service_type, r.service_mode, r.quantity, r.claim_method,
               r.scheduled_date, r.scheduled_time, r.location, r.status, r.created_at,
               f.id AS file_id, f.original_name
        FROM requests r
        JOIN customers c ON c.id = r.customer_id
        LEFT JOIN request_files f ON f.request_id = r.id';
$params = [];
if (in_array($filter, $cfgData['statuses'], true)) {
    $sql .= ' WHERE r.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY r.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Requests';
require __DIR__ . '/_header.php';
?>

<div class="section-head"><h2>Requests</h2><p>Update status as orders move through the shop.</p></div>

<div class="navlinks" style="margin-bottom:18px;">
  <a href="requests.php" class="btn-ghost<?= $filter === '' ? ' active' : '' ?>">All</a>
  <?php foreach ($cfgData['statuses'] as $s): ?>
    <a href="requests.php?status=<?= urlencode($s) ?>" class="btn-ghost<?= $filter === $s ? ' active' : '' ?>"><?= e($s) ?></a>
  <?php endforeach; ?>
</div>

<table>
  <thead><tr><th>Ref</th><th>Customer</th><th>Service</th><th>When / claim</th><th>File</th><th>Status</th></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['reference']) ?></td>
        <td><?= e($r['full_name']) ?><br><small style="color:#7a756a;"><?= e($r['phone']) ?></small></td>
        <td><?= e($r['service_type']) ?><?= $r['service_mode'] === 'request' && $r['quantity'] ? ' ×' . (int)$r['quantity'] : '' ?></td>
        <td>
          <?php if ($r['service_mode'] === 'booking'): ?>
            <?= e($r['scheduled_date']) ?> <?= e($r['scheduled_time']) ?><br><small style="color:#7a756a;"><?= e($r['location']) ?></small>
          <?php else: ?>
            <?= e($r['claim_method']) ?>
          <?php endif; ?>
        </td>
        <td><?php if ($r['file_id']): ?><a href="file.php?id=<?= (int)$r['file_id'] ?>"><?= e($r['original_name']) ?></a><?php else: ?>—<?php endif; ?></td>
        <td>
          <form method="post" style="display:flex;gap:6px;align-items:center;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
            <select name="status" class="statusedit" onchange="this.form.submit()">
              <?php foreach ($cfgData['statuses'] as $s): ?>
                <option <?= $s === $r['status'] ? 'selected' : '' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="empty">No requests yet.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/_footer.php'; ?>
