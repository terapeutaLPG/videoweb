<?php
$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>

<div class="modal-backdrop" id="loginModal" aria-hidden="true">
  <div class="modal">
    <div class="modal-head">
      <div class="modal-title">Logowanie</div>
      <button type="button" class="modal-close" id="closeLogin">✕</button>
    </div>

    <form method="post" action="/login.php">
      <div class="field">
        <label>Login</label>
        <input type="text" name="login" required>
      </div>

      <div class="field">
        <label>Hasło</label>
        <input type="password" name="password" required>
      </div>

      <button class="btn" type="submit">Zaloguj</button>

      <?php if (!empty($loginError)): ?>
        <div class="error"><?= htmlspecialchars($loginError) ?></div>
      <?php endif; ?>
    </form>
  </div>
</div>

<script>
(function () {
  const modal = document.getElementById('loginModal');
  const openBtn = document.getElementById('openLogin');
  const closeBtn = document.getElementById('closeLogin');

  function openModal() {
    if (!modal) return;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
  }
  function closeModal() {
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
  }

  if (openBtn) openBtn.addEventListener('click', openModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);

  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal();
    });
  }

  // jeśli był błąd logowania, pokaż modal automatycznie
  const hasError = <?= $loginError ? 'true' : 'false' ?>;
  if (hasError) openModal();
})();
</script>
