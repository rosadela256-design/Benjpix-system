(function () {
  var serviceSel = document.getElementById('serviceSel');
  var bookingFields = document.getElementById('bookingFields');
  var requestFields = document.getElementById('requestFields');
  var claimSel = document.getElementById('claimSel');
  var addrField = document.getElementById('addrField');

  function syncService() {
    if (!serviceSel) return;
    var opt = serviceSel.options[serviceSel.selectedIndex];
    var mode = opt ? opt.getAttribute('data-mode') : null;
    if (bookingFields) bookingFields.style.display = (mode === 'booking') ? '' : 'none';
    if (requestFields) requestFields.style.display = (mode === 'request') ? '' : 'none';
  }
  if (serviceSel) {
    serviceSel.addEventListener('change', syncService);
    syncService();
  }

  function syncClaim() {
    if (!claimSel || !addrField) return;
    addrField.style.display = claimSel.value === 'Delivery' ? '' : 'none';
  }
  if (claimSel) {
    claimSel.addEventListener('change', syncClaim);
    syncClaim();
  }

  // mark the current nav link active
  var here = location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('.navlinks a').forEach(function (a) {
    if (a.getAttribute('href') === here) a.classList.add('active');
  });

  // mobile hamburger menu
  var navToggle = document.getElementById('navToggle');
  var navLinks = document.getElementById('navLinks');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function () {
      var open = navLinks.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    navLinks.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        navLinks.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }
})();
