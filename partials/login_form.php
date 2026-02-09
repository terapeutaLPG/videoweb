<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  require __DIR__ . '/../db.php';
}
?>

<div id="loginModal" class="modal-backdrop" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true" aria-label="Logowanie">
    <div class="modal-content">
      <span class="close" onclick="closeLoginModal()">&times;</span>

      <h2>Logowanie</h2>

      <?php if (!empty($_SESSION['login_error'])): ?>
        <div class="login-error">
          <?php
          echo $_SESSION['login_error'];
          unset($_SESSION['login_error']);
          ?>
        </div>
      <?php endif; ?>

      <form action="login.php" method="post">
        <div class="form-group">
          <label for="username">Login</label>
          <input type="text" id="username" name="username" class="input" required>
        </div>

        <div class="form-group">
          <label for="password">Hasło</label>
          <input type="password" id="password" name="password" class="input" required>
        </div>

        <button type="submit" class="btn">Zaloguj</button>
      </form>
    </div>
  </div>
</div>

<script>
  function openLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
      modal.classList.add('show');
      modal.setAttribute('aria-hidden', 'false');
    }
  }

  function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
      modal.classList.remove('show');
      modal.setAttribute('aria-hidden', 'true');
    }
  }

  document.addEventListener('click', function(e) {
    const modal = document.getElementById('loginModal');
    if (modal && e.target === modal) closeLoginModal();
  });
</script>

<?php if (!empty($_SESSION['is_admin'])): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const modal = document.getElementById('loginModal');
      if (modal) {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
      }
    });
  </script>
<?php endif; ?>