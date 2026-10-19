<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';
require __DIR__ . '/includes/layout_header.php';
?>

<section class="hero">
  <div class="hero-grid">
    <div>
      <h1>Photo shoots, event videography, printing, and edits — booked or dropped off, your way.</h1>
      <p><?= e($cfg['shop_name']) ?> covers the shoot, the edit, and the print. Book a session for photo and video work, or send a request for printing and editing — then follow it from Pending to Ready.</p>
      <div class="hero-ctas">
        <a href="request.php" class="btn">Book or start a request</a>
        <a href="#gallery" class="btn-ghost">See our work</a>
      </div>
      <ul class="trust-strip">
        <li>Photo &amp; video sessions</li>
        <li>Printing &amp; editing</li>
        <li>Track your order online</li>
      </ul>
    </div>
    <div class="stack" aria-hidden="true">
      <div class="card"><div class="photo"></div><span class="cap">Portrait shoot</span></div>
      <div class="card"><div class="photo"></div><span class="cap">Event coverage</span></div>
      <div class="card"><div class="photo"></div><span class="cap">Print & edit</span></div>
    </div>
  </div>
</section>

<?php if (!empty($cfg['gallery'])): ?>
<section id="gallery">
  <div class="section-head">
    <h2>A look at our work</h2>
    <p>Photo shoots, video stills, and prints from recent jobs — yours could be next.</p>
  </div>
  <div class="gallery-grid">
    <?php foreach ($cfg['gallery'] as $item): ?>
      <figure class="gtile">
        <img src="assets/img/gallery/<?= e($item['file']) ?>" alt="<?= e($item['caption']) ?>" loading="lazy">
        <figcaption><?= e($item['caption']) ?></figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section id="services">
  <div class="section-head">
    <h2>What we offer</h2>
    <p>Photo shoots and videography are booked for a date and time. Printing and editing are drop-off requests.</p>
  </div>
  <div class="svc-grid">
    <?php foreach ($cfg['services'] as $svc): ?>
      <div class="svc">
        <div class="top"></div>
        <h3><?= e($svc['name']) ?></h3>
        <p>
          <?= e($svc['note']) ?>
          <span class="modepill"><?= $svc['mode'] === 'booking' ? 'Book a date & time' : 'Drop-off request' ?></span>
        </p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section>
  <div class="steps">
    <div class="step"><div class="num"></div><div><h3>Choose a service</h3><p>Photo shoot, videography, printing, or editing.</p></div></div>
    <div class="step"><div class="num"></div><div><h3>Book or submit</h3><p>Pick a date and time for a session, or attach a file for printing and editing.</p></div></div>
    <div class="step"><div class="num"></div><div><h3>We get to work</h3><p>Your request moves from Pending to Processing.</p></div></div>
    <div class="step"><div class="num"></div><div><h3>Ready for you</h3><p>Status turns Ready, then Completed once it's with you.</p></div></div>
  </div>
</section>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
