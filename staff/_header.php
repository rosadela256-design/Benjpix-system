<?php
/** Included by every staff page after require_staff(). @var array $user */
$cfg = load_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle ?? 'Staff') ?> — <?= e($cfg['shop_name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="icon" href="../assets/img/favicon-64.png">
<meta name="theme-color" content="#0E0D0C">
</head>
<body>
<header>
  <nav class="wrap">
    <a class="brand" href="dashboard.php">
      <img src="../assets/img/logo.png" alt="<?= e($cfg['shop_name']) ?> logo">
      <span class="brand-word"><?= e($cfg['shop_name']) ?> staff</span>
    </a>
    <button class="navtoggle" id="navToggle" aria-label="Menu" aria-expanded="false">&#9776;</button>
    <div class="navlinks" id="navLinks">
      <a href="dashboard.php">Dashboard</a>
      <a href="requests.php">Requests</a>
      <a href="payments.php">Payments</a>
      <a href="customers.php">Customers</a>
      <a href="../logout.php" class="btn-ghost">Sign out (<?= e($user['display_name']) ?>)</a>
    </div>
  </nav>
</header>
<main class="wrap">
