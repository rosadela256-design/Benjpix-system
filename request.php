<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$cfgData = load_config();
$errors  = [];
$reference = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name    = trim((string)($_POST['name'] ?? ''));
    $phone   = trim((string)($_POST['phone'] ?? ''));
    $email   = trim((string)($_POST['email'] ?? ''));
    $service = trim((string)($_POST['service'] ?? ''));
    $svc     = find_service($cfgData['services'], $service);

    if ($name === '') $errors[] = 'Please enter your full name.';
    if ($phone === '') $errors[] = 'Please enter a contact number.';
    if (!$svc) $errors[] = 'Please choose a valid service.';

    // Mode-specific fields
    $qty = 1; $claim = null; $address = null; $upload = ['ok' => true, 'error' => null, 'ext' => null];
    $schedDate = null; $schedTime = null; $location = null;
    $notes = trim((string)($_POST['notes'] ?? ''));

    if ($svc && $svc['mode'] === 'booking') {
        $schedDate = trim((string)($_POST['scheduled_date'] ?? ''));
        $schedTime = trim((string)($_POST['scheduled_time'] ?? ''));
        $location  = trim((string)($_POST['location'] ?? ''));

        if ($schedDate === '') {
            $errors[] = 'Please choose a date.';
        } elseif ($schedDate < date('Y-m-d')) {
            $errors[] = 'Please choose a date that has not already passed.';
        }
        if ($schedTime === '') $errors[] = 'Please choose a time.';
        if ($location === '') $errors[] = 'Please enter a location (shop studio or your venue).';
    } elseif ($svc) {
        $qty     = (int)($_POST['qty'] ?? 1);
        $claim   = in_array($_POST['claim'] ?? '', ['Pickup', 'Delivery', 'Digital file'], true) ? $_POST['claim'] : 'Pickup';
        $address = trim((string)($_POST['address'] ?? ''));

        if ($qty < 1) $errors[] = 'Quantity must be at least 1.';
        if ($claim === 'Delivery' && $address === '') $errors[] = 'Please enter a delivery address.';

        $upload = validate_upload($_FILES['file'] ?? [], $cfgData);
        if (!$upload['ok']) $errors[] = $upload['error'];
    }

    if (!$errors) {
        $pdo = get_db();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT id FROM customers WHERE phone = ? LIMIT 1');
            $stmt->execute([$phone]);
            $customerId = $stmt->fetchColumn();

            if (!$customerId) {
                $stmt = $pdo->prepare(
                    'INSERT INTO customers (full_name, phone, email, created_at) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$name, $phone, $email ?: null, date('Y-m-d H:i:s')]);
                $customerId = (int)$pdo->lastInsertId();
            }

            $reference = generate_reference($pdo);
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare(
                'INSERT INTO requests
                    (reference, customer_id, service_type, service_mode, scheduled_date, scheduled_time, location,
                     quantity, claim_method, delivery_address, notes, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'Pending\', ?, ?)'
            );
            $stmt->execute([
                $reference, $customerId, $svc['name'], $svc['mode'],
                $schedDate ?: null, $schedTime ?: null, $location ?: null,
                $svc['mode'] === 'request' ? $qty : null,
                $claim, $claim === 'Delivery' ? $address : null,
                $notes ?: null, $now, $now,
            ]);
            $requestId = (int)$pdo->lastInsertId();

            if ($svc['mode'] === 'request' && !empty($_FILES['file']['name'])) {
                store_upload($pdo, $requestId, $_FILES['file'], $upload['ext'] ?? 'bin');
            }

            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            error_log('Request submit failed: ' . $ex->getMessage());
            $errors[] = 'Something went wrong saving your request. Please try again.';
            $reference = null;
        }
    }
}

$pageTitle = 'Start a request';
require __DIR__ . '/includes/layout_header.php';
?>

<div class="section-head">
  <h2>Book a session or start a request</h2>
  <p>Photo shoots and videography are booked for a date and time. Printing and editing are drop-off requests — no fixed time needed.</p>
</div>

<?php if ($reference): ?>
  <div class="card-panel">
    <div class="msg ok">
      Submitted. Your reference number:
      <div class="refbox"><?= e($reference) ?></div>
      Save it to check status on the <a href="track.php">Track order</a> page.
    </div>
  </div>
<?php else: ?>
  <?php if ($errors): ?>
    <div class="card-panel" style="margin-bottom:18px;">
      <div class="msg err"><?= implode('<br>', array_map('e', $errors)) ?></div>
    </div>
  <?php endif; ?>

  <form class="card-panel" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <div class="field"><label>Full name</label><input required name="name" value="<?= e($_POST['name'] ?? '') ?>"></div>
    <div class="row2">
      <div class="field"><label>Contact number</label><input required name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></div>
      <div class="field"><label>Email (optional)</label><input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"></div>
    </div>

    <div class="field">
      <label>Service</label>
      <select name="service" id="serviceSel" required>
        <option value="">Choose one</option>
        <?php foreach ($cfgData['services'] as $svc): ?>
          <option value="<?= e($svc['name']) ?>" data-mode="<?= e($svc['mode']) ?>" <?= (($_POST['service'] ?? '') === $svc['name']) ? 'selected' : '' ?>>
            <?= e($svc['name']) ?><?= $svc['note'] ? ' — ' . e($svc['note']) : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div id="bookingFields">
      <div class="row2">
        <div class="field"><label>Preferred date</label><input type="date" name="scheduled_date" min="<?= e(date('Y-m-d')) ?>" value="<?= e($_POST['scheduled_date'] ?? '') ?>"></div>
        <div class="field"><label>Preferred time</label><input type="time" name="scheduled_time" value="<?= e($_POST['scheduled_time'] ?? '') ?>"></div>
      </div>
      <div class="field"><label>Location</label><input name="location" placeholder="<?= e($cfgData['shop_name']) ?> studio, or your event venue" value="<?= e($_POST['location'] ?? '') ?>"></div>
    </div>

    <div id="requestFields">
      <div class="row2">
        <div class="field"><label>Quantity / copies</label><input type="number" name="qty" min="1" value="<?= e((string)($_POST['qty'] ?? 1)) ?>"></div>
        <div class="field">
          <label>Claim method</label>
          <select name="claim" id="claimSel">
            <option value="Pickup">Pickup at shop</option>
            <option value="Delivery" <?= (($_POST['claim'] ?? '') === 'Delivery') ? 'selected' : '' ?>>Delivery</option>
            <option value="Digital file" <?= (($_POST['claim'] ?? '') === 'Digital file') ? 'selected' : '' ?>>Digital file</option>
          </select>
        </div>
      </div>
      <div class="field" id="addrField"><label>Delivery address</label><input name="address" value="<?= e($_POST['address'] ?? '') ?>"></div>
      <div class="field">
        <label>File (<?= e(implode(', ', $cfgData['allowed_ext'])) ?>, up to <?= (int)$cfgData['max_size_mb'] ?>MB)</label>
        <input type="file" name="file">
      </div>
    </div>

    <div class="field"><label>Notes (optional)</label><textarea name="notes" placeholder="Package, sizes, style, anything we should know"><?= e($_POST['notes'] ?? '') ?></textarea></div>
    <button class="btn" type="submit">Submit</button>
  </form>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
