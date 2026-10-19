<?php
/** @var string $pageTitle set by the including page */
$cfg = load_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle ?? $cfg['shop_name']) ?> — <?= e($cfg['shop_name']) ?> <?= e($cfg['shop_tagline']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="assets/img/favicon-64.png">
<link rel="apple-touch-icon" href="assets/img/favicon-180.png">
<meta name="theme-color" content="#0E0D0C">
</head>
<body>
<header>
  <nav class="wrap">
    <a class="brand" href="index.php">
      <img src="assets/img/logo.png" alt="<?= e($cfg['shop_name']) ?> logo">
      <span class="brand-word"><?= e($cfg['shop_name']) ?></span>
    </a>
    <button class="navtoggle" id="navToggle" aria-label="Menu" aria-expanded="false">&#9776;</button>
    <div class="navlinks" id="navLinks">
      <a href="index.php#services">Services</a>
      <a href="index.php#gallery">Our work</a>
      <a href="request.php">Start a request</a>
      <a href="track.php">Track order</a>
      <a href="login.php" class="btn-ghost">Staff</a>
    </div>
  </nav>
</header>
<main class="wrap">
