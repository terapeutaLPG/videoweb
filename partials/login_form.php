<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  require __DIR__ . '/../db.php';
}
$showReg = !empty($_SESSION['reg_error']) || (isset($_SERVER['HTTP_REFERER']) && str_contains($_SERVER['HTTP_REFERER'], 'register'));
?>

<div id="loginModal" class="modal-backdrop" aria-hidden="true">
  <div class="modal lm-modal" role="dialog" aria-modal="true" aria-label="Logowanie / Rejestracja">
    <div class="modal-content">
      <span class="close" onclick="closeLoginModal()">&times;</span>

      <div class="lm-tabs">
        <button type="button" class="lm-tab <?= $showReg ? '' : 'lm-tab--active' ?>"
          onclick="switchTab('login')">Zaloguj się</button>
        <button type="button" class="lm-tab <?= $showReg ? 'lm-tab--active' : '' ?>"
          onclick="switchTab('register')">Zarejestruj się</button>
        <span class="lm-tab-indicator" id="lmTabIndicator"></span>
      </div>

      <div id="lmPanelLogin" class="lm-panel <?= $showReg ? 'lm-panel--hidden' : '' ?>">
        <?php if (!empty($_SESSION['login_error'])): ?>
          <div class="login-error">
            <?php echo htmlspecialchars($_SESSION['login_error']);
            unset($_SESSION['login_error']); ?>
          </div>
        <?php endif; ?>

        <form action="login.php" method="post" id="loginForm">
          <div class="form-group">
            <label for="username">Email / Login</label>
            <input type="text" id="username" name="username" class="input" required autocomplete="email">
          </div>
          <div class="form-group">
            <label for="password">Hasło</label>
            <input type="password" id="password" name="password" class="input" required autocomplete="current-password">

            <p class="lm-switch-hint" style="margin-top:-4px; margin-bottom:14px;">
              <a href="forgot_password.php" class="lm-link">Nie pamiętasz hasła?</a>
            </p>

          </div>
          <button type="submit" class="btn btn-full btn-login-anim" id="loginSubmitBtn">
            <span class="btn-login-text">Zaloguj się</span>
            <span class="btn-login-ripple"></span>
          </button>
        </form>

        <p class="lm-switch-hint">Nie masz konta? <button type="button" class="lm-link"
            onclick="switchTab('register')">Zarejestruj się</button></p>
      </div>

      <div id="lmPanelRegister" class="lm-panel <?= $showReg ? '' : 'lm-panel--hidden' ?>">
        <?php if (!empty($_SESSION['reg_error'])): ?>
          <div class="login-error">
            <?php echo htmlspecialchars($_SESSION['reg_error']);
            unset($_SESSION['reg_error']); ?>
          </div>
        <?php endif; ?>

        <form action="register.php" method="post" id="registerForm">
          <div class="form-group">
            <label for="reg_email">Adres email</label>
            <input type="email" id="reg_email" name="reg_email" class="input" required autocomplete="email"
              placeholder="np. jan@example.com">
          </div>
          <div class="form-group">
            <label for="reg_password">Hasło <span class="lm-hint">(min. 6 znaków)</span></label>
            <input type="password" id="reg_password" name="reg_password" class="input" required
              autocomplete="new-password" minlength="6">
          </div>
          <div class="form-group">
            <label for="reg_password2">Powtórz hasło</label>
            <input type="password" id="reg_password2" name="reg_password2" class="input" required
              autocomplete="new-password" minlength="6">
          </div>
          <button type="submit" class="btn btn-full btn-reg-anim" id="registerSubmitBtn">
            <span class="btn-reg-text">Utwórz konto</span>
            <canvas class="btn-reg-particles" id="regParticlesCanvas"></canvas>
          </button>
        </form>

        <p class="lm-switch-hint">Masz już konto? <button type="button" class="lm-link"
            onclick="switchTab('login')">Zaloguj się</button></p>
      </div>

    </div>
  </div>
</div>

<style>
  .lm-modal {
    width: min(440px, 100%);
  }

  .lm-tabs {
    position: relative;
    display: flex;
    gap: 0;
    margin-bottom: 20px;
    background: rgba(255, 255, 255, 0.04);
    border-radius: 999px;
    padding: 4px;
    border: 1px solid rgba(255, 255, 255, 0.08);
  }

  .lm-tab {
    flex: 1;
    padding: 8px 12px;
    border: 0;
    background: transparent;
    color: var(--muted);
    font-size: 13px;
    font-weight: 600;
    border-radius: 999px;
    cursor: pointer;
    position: relative;
    z-index: 2;
    transition: color 0.25s ease;
    font-family: inherit;
  }

  .lm-tab--active {
    color: var(--text);
  }

  .lm-tab-indicator {
    position: absolute;
    top: 4px;
    bottom: 4px;
    left: 4px;
    width: calc(50% - 4px);
    background: rgba(57, 211, 255, 0.18);
    border: 1px solid rgba(57, 211, 255, 0.35);
    border-radius: 999px;
    transition: transform 0.3s cubic-bezier(.4, 0, .2, 1);
    z-index: 1;
  }

  .lm-tab-indicator.on-register {
    transform: translateX(100%);
  }

  .lm-panel {
    transition: opacity 0.2s ease, transform 0.2s ease;
  }

  .lm-panel--hidden {
    display: none;
  }

  .lm-switch-hint {
    margin-top: 14px;
    text-align: center;
    font-size: 12px;
    color: var(--muted);
  }

  .lm-link {
    background: none;
    border: 0;
    color: var(--accent);
    cursor: pointer;
    font-size: 12px;
    font-family: inherit;
    text-decoration: underline;
    text-underline-offset: 2px;
  }

  .lm-hint {
    color: var(--muted);
    font-size: 11px;
  }

  .btn-login-anim {
    position: relative;
    overflow: hidden;
    margin-top: 6px;
  }

  .btn-login-ripple {
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: radial-gradient(circle at var(--rx, 50%) var(--ry, 50%), rgba(57, 211, 255, 0.35) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.4s ease;
    pointer-events: none;
  }

  .btn-login-anim:hover .btn-login-ripple {
    opacity: 1;
  }

  .btn-reg-anim {
    position: relative;
    overflow: hidden;
    margin-top: 6px;
    background: linear-gradient(135deg, rgba(111, 92, 255, 0.28), rgba(57, 211, 255, 0.22));
    border-color: rgba(111, 92, 255, 0.5);
    transition: transform 0.15s ease, box-shadow 0.3s ease, border-color 0.3s ease;
  }

  .btn-reg-anim:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 0 24px rgba(111, 92, 255, 0.4), 0 0 48px rgba(57, 211, 255, 0.15);
    border-color: rgba(57, 211, 255, 0.6);
  }

  .btn-reg-anim:active {
    transform: scale(0.97);
  }

  .btn-reg-anim.success {
    background: linear-gradient(135deg, rgba(80, 220, 170, 0.3), rgba(57, 211, 255, 0.25));
    border-color: rgba(80, 220, 170, 0.6);
    box-shadow: 0 0 30px rgba(80, 220, 170, 0.3);
    animation: reg-success-pulse 0.5s ease;
  }

  .btn-reg-particles {
    position: absolute;
    inset: 0;
    pointer-events: none;
    width: 100%;
    height: 100%;
  }

  @keyframes reg-success-pulse {
    0% {
      transform: scale(1);
    }

    40% {
      transform: scale(1.04);
    }

    100% {
      transform: scale(1);
    }
  }

  @keyframes reg-shimmer {
    0% {
      background-position: -200% center;
    }

    100% {
      background-position: 200% center;
    }
  }

  .btn-reg-anim::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.08) 50%, transparent 100%);
    background-size: 200% auto;
    animation: reg-shimmer 2.5s linear infinite;
    pointer-events: none;
  }
</style>

<script>
  function openLoginModal(tab) {
    const modal = document.getElementById('loginModal');
    if (modal) {
      modal.classList.add('show');
      modal.setAttribute('aria-hidden', 'false');
      if (tab) switchTab(tab);
    }
  }

  function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
      modal.classList.remove('show');
      modal.setAttribute('aria-hidden', 'true');
    }
  }

  function switchTab(tab) {
    const panelLogin = document.getElementById('lmPanelLogin');
    const panelRegister = document.getElementById('lmPanelRegister');
    const indicator = document.getElementById('lmTabIndicator');
    const tabs = document.querySelectorAll('.lm-tab');

    if (tab === 'register') {
      panelLogin.classList.add('lm-panel--hidden');
      panelRegister.classList.remove('lm-panel--hidden');
      if (indicator) indicator.classList.add('on-register');
      tabs[0].classList.remove('lm-tab--active');
      tabs[1].classList.add('lm-tab--active');
    } else {
      panelRegister.classList.add('lm-panel--hidden');
      panelLogin.classList.remove('lm-panel--hidden');
      if (indicator) indicator.classList.remove('on-register');
      tabs[1].classList.remove('lm-tab--active');
      tabs[0].classList.add('lm-tab--active');
    }
  }

  document.addEventListener('click', function (e) {
    const modal = document.getElementById('loginModal');
    if (modal && e.target === modal) closeLoginModal();
  });

  const loginBtn = document.getElementById('loginSubmitBtn');
  if (loginBtn) {
    loginBtn.addEventListener('mousemove', function (e) {
      const r = loginBtn.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width * 100).toFixed(1);
      const y = ((e.clientY - r.top) / r.height * 100).toFixed(1);
      loginBtn.style.setProperty('--rx', x + '%');
      loginBtn.style.setProperty('--ry', y + '%');
    });
  }

  const regBtn = document.getElementById('registerSubmitBtn');
  const regCanvas = document.getElementById('regParticlesCanvas');

  function spawnParticles() {
    if (!regCanvas) return;
    const ctx = regCanvas.getContext('2d');
    regCanvas.width = regBtn.offsetWidth;
    regCanvas.height = regBtn.offsetHeight;

    const particles = Array.from({
      length: 22
    }, () => ({
      x: Math.random() * regCanvas.width,
      y: Math.random() * regCanvas.height,
      vx: (Math.random() - 0.5) * 3,
      vy: -(Math.random() * 3 + 1),
      r: Math.random() * 3 + 1,
      alpha: 1,
      color: Math.random() > 0.5 ? '111,92,255' : '57,211,255'
    }));

    let frame;

    function draw() {
      ctx.clearRect(0, 0, regCanvas.width, regCanvas.height);
      let alive = false;
      particles.forEach(p => {
        p.x += p.vx;
        p.y += p.vy;
        p.alpha -= 0.025;
        if (p.alpha > 0) {
          alive = true;
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
          ctx.fillStyle = `rgba(${p.color},${p.alpha.toFixed(2)})`;
          ctx.fill();
        }
      });
      if (alive) frame = requestAnimationFrame(draw);
      else ctx.clearRect(0, 0, regCanvas.width, regCanvas.height);
    }
    cancelAnimationFrame(frame);
    draw();
  }

  if (regBtn) {
    regBtn.addEventListener('mouseenter', spawnParticles);
    regBtn.addEventListener('click', function () {
      spawnParticles();
      regBtn.classList.add('success');
      setTimeout(() => regBtn.classList.remove('success'), 600);
    });
  }

  <?php if (!empty($_SESSION['is_admin']) || !empty($_SESSION['user_id'])): ?>
    document.addEventListener('DOMContentLoaded', function () {
      const modal = document.getElementById('loginModal');
      if (modal) {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
      }
    });
  <?php endif; ?>

  <?php if (!empty($_SESSION['reg_error'])): ?>
    document.addEventListener('DOMContentLoaded', function () {
      openLoginModal('register');
    });
  <?php elseif (!empty($_SESSION['login_error'])): ?>
    document.addEventListener('DOMContentLoaded', function () {
      openLoginModal('login');
    });
  <?php endif; ?>
</script>