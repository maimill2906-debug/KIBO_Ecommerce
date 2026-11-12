<?php
/**
 * VIEW: views/customer/AuthView.php
 * Giao diện Đăng Nhập / Đăng Ký Tài Khoản KIBO chuẩn mã nguồn Figma
 */
$pageTitle = 'Đăng Nhập / Đăng Ký - KIBO PET\'S HOME';
$activeMenu = '';
require __DIR__ . '/header.php';

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
$registerError = $_SESSION['register_error'] ?? null;
unset($_SESSION['register_error']);
$registerSuccess = $_SESSION['register_success'] ?? null;
unset($_SESSION['register_success']);
$registeredEmail = $_SESSION['registered_email'] ?? '';
unset($_SESSION['registered_email']);
$initialMode = (($_GET['action'] ?? '') === 'register' || $registerError) && !$registerSuccess ? 'register' : 'login';
?>

<main class="page" style="display: flex; justify-content: center; align-items: center; padding: 70px 20px 100px; background: var(--cream);">
  <section class="modal auth-modal" style="position: static; transform: none; width: 100%; max-width: 540px; padding: 40px 44px; box-shadow: 0 16px 48px rgba(0,0,0,.08); border-radius: 12px; overflow-x: hidden;">
    <header style="margin-bottom: 20px;">
      <div class="auth-brand" style="margin-bottom: 16px;">
        <?= kibo_logo_svg('default', 48) ?>
        <h2 style="margin-top: 10px; font-size: 24px; font-weight: 800;">Tài khoản KIBO</h2>
      </div>
      <div class="tab-switch auth-tabs">
        <button type="button" class="btn active" id="pageTabLogin" onclick="switchPageAuthTab('login')">Đăng nhập</button>
        <button type="button" class="btn" id="pageTabRegister" onclick="switchPageAuthTab('register')">Đăng ký</button>
      </div>
    </header>

    <div class="modal-body">
      <?php if ($loginError): ?>
        <p class="auth-error" style="display: block; margin-bottom: 12px;"><?= htmlspecialchars($loginError) ?></p>
      <?php endif; ?>
      <?php if ($registerError): ?>
        <p class="auth-error" style="display: block; margin-bottom: 12px;"><?= htmlspecialchars($registerError) ?></p>
      <?php endif; ?>
      <?php if ($registerSuccess): ?>
        <p style="background: #f7f7f7; color: #222; border: 1px solid #dcdcdc; padding: 10px 14px; border-radius: 4px; font-size: 13.5px; margin-bottom: 14px; line-height: 1.4;">
          <span style="color: #2e7d32; font-weight: bold; margin-right: 4px;">✓</span> <?= htmlspecialchars($registerSuccess) ?>
        </p>
      <?php endif; ?>

      <form action="index.php?action=login_submit" method="POST" id="pageAuthForm">
        <?= function_exists('csrfField') ? csrfField() : '' ?>
        <input type="hidden" name="mode" id="pageAuthMode" value="login">

        <div id="pageRegisterFields" style="display: none;">
          <label class="field" style="margin-bottom: 14px;">
            <span>Họ và tên *</span>
            <input type="text" name="name" id="pageAuthName" placeholder="Họ và tên của bạn">
          </label>
        </div>

        <label class="field">
          <span id="pageAuthEmailLabel">Email hoặc Số điện thoại *</span>
          <input type="text" name="email" id="pageAuthEmail" placeholder="Email hoặc Số điện thoại của bạn" value="<?= htmlspecialchars($registeredEmail) ?>" required>
        </label>

        <div id="pageRegisterFieldsMore" style="display: none;">
          <label class="field" style="margin-top: 14px;">
            <span>Số điện thoại * (dùng để đăng nhập hoặc giao hàng)</span>
            <input type="tel" name="phone" id="pageAuthPhone" placeholder="Ví dụ: 0912345678 (10 số)" pattern="0[0-9]{9}" title="Vui lòng nhập số điện thoại Việt Nam gồm 10 chữ số bắt đầu bằng 0">
          </label>
          <label class="field" style="margin-top: 14px;">
            <span>Địa chỉ giao hàng</span>
            <input type="text" name="address" placeholder="Địa chỉ giao hàng (số nhà, đường...)">
          </label>
        </div>

        <label class="field password-field" style="margin-top: 14px;">
          <span>Mật khẩu *</span>
          <span class="password-input">
            <input type="password" name="password" id="pageAuthPassword" placeholder="Nhập mật khẩu" required>
            <button type="button" class="btn password-toggle" onclick="togglePagePassword(this)">
              <?= kibo_icon('eye', 18) ?>
            </button>
          </span>
        </label>

        <div id="pageRegisterConsentWrap" style="display: none; margin-top: 14px; padding: 10px 12px; background: #fbf9f6; border: 1px solid #e6dfd7; border-radius: 6px;">
          <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 12.8px; color: #4a3f37; line-height: 1.45; cursor: pointer;">
            <input type="checkbox" name="consent_pdpd" id="pageConsentCheckbox" style="width: 18px !important; height: 18px !important; max-width: 18px !important; max-height: 18px !important; min-width: 18px !important; min-height: 18px !important; margin: 2px 0 0 0 !important; padding: 0 !important; accent-color: #3f7a2f !important; flex: 0 0 18px !important; -webkit-appearance: checkbox !important; appearance: auto !important; cursor: pointer;">
            <span style="flex: 1; font-size: 12.8px; color: #4a3f37; line-height: 1.45;">Tôi đồng ý cho phép KIBO thu thập và xử lý dữ liệu cá nhân theo <a href="index.php?action=policy&tab=privacy" target="_blank" style="color: #c94336; font-weight: 700; text-decoration: underline;">Chính sách bảo mật</a> (Luật BVDLCN 91/2025/QH15) &amp; <a href="index.php?action=policy&tab=terms" target="_blank" style="color: #c94336; font-weight: 700; text-decoration: underline;">Điều khoản sử dụng</a>.</span>
          </label>
        </div>

        <div class="login-options" id="pageLoginOptions" style="margin-top: 14px;">
          <label class="remember">
            <input type="checkbox" name="remember" checked style="width: 16px !important; height: 16px !important; max-width: 16px !important; max-height: 16px !important; min-width: 16px !important; min-height: 16px !important; margin: 0 !important; flex: 0 0 16px !important; -webkit-appearance: checkbox !important; appearance: auto !important;">
            <span>Ghi nhớ đăng nhập</span>
          </label>
          <button type="button" class="btn forgot" onclick="if(typeof openForgotPasswordModal==='function') openForgotPasswordModal();">Quên mật khẩu?</button>
        </div>

        <button type="submit" class="btn primary confirm" id="pageAuthSubmitBtn" style="margin-top: 18px; width: 100%; min-height: 44px; height: 44px; font-size: 14px; font-weight: 700; border-radius: 6px;">
          <span id="pageAuthBtnText">Đăng nhập ngay</span> <?= kibo_icon('arrow') ?>
        </button>
      </form>
    </div>
  </section>
</main>

<script>
function switchPageAuthTab(mode) {
  const form = document.getElementById('pageAuthForm');
  const tabLogin = document.getElementById('pageTabLogin');
  const tabReg = document.getElementById('pageTabRegister');
  const regFields = document.getElementById('pageRegisterFields');
  const regFieldsMore = document.getElementById('pageRegisterFieldsMore');
  const regConsent = document.getElementById('pageRegisterConsentWrap');
  const loginOpts = document.getElementById('pageLoginOptions');
  const modeInput = document.getElementById('pageAuthMode');
  const submitBtn = document.getElementById('pageAuthSubmitBtn');
  const btnText = document.getElementById('pageAuthBtnText');

  const emailLabel = document.getElementById('pageAuthEmailLabel');
  const emailInput = document.getElementById('pageAuthEmail');
  if (mode === 'login') {
    tabLogin.classList.add('active');
    tabReg.classList.remove('active');
    regFields.style.display = 'none';
    regFieldsMore.style.display = 'none';
    if (regConsent) regConsent.style.display = 'none';
    loginOpts.style.display = 'flex';
    modeInput.value = 'login';
    form.action = 'index.php?action=login_submit';
    btnText.textContent = 'Đăng nhập ngay';
    submitBtn.classList.remove('green-button');
    if (emailLabel) emailLabel.textContent = 'Email hoặc Số điện thoại *';
    if (emailInput) emailInput.placeholder = 'Email hoặc Số điện thoại của bạn';
  } else {
    tabReg.classList.add('active');
    tabLogin.classList.remove('active');
    regFields.style.display = 'block';
    regFieldsMore.style.display = 'block';
    if (regConsent) regConsent.style.display = 'block';
    loginOpts.style.display = 'none';
    modeInput.value = 'register';
    form.action = 'index.php?action=register_submit';
    btnText.textContent = 'Tạo tài khoản khách hàng';
    submitBtn.classList.add('green-button');
    if (emailLabel) emailLabel.textContent = 'Địa chỉ email *';
    if (emailInput) emailInput.placeholder = 'Email của bạn';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if ('<?= $initialMode ?>' === 'register') {
    switchPageAuthTab('register');
  }

  const form = document.getElementById('pageAuthForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      const mode = document.getElementById('pageAuthMode').value;
      const email = document.getElementById('pageAuthEmail').value.trim();
      const password = document.getElementById('pageAuthPassword').value;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      const phoneRegex = /^0\d{9}$/;

      let errBox = document.getElementById('pageClientError');
      if (!errBox) {
        errBox = document.createElement('p');
        errBox.id = 'pageClientError';
        errBox.className = 'auth-error';
        errBox.style.display = 'block';
        errBox.style.marginBottom = '14px';
        form.parentNode.insertBefore(errBox, form);
      }

      if (mode === 'register') {
        const nameInput = document.getElementById('pageAuthName');
        const name = nameInput ? nameInput.value.trim() : '';
        const phoneInput = document.querySelector('input[name="phone"]');
        const phone = phoneInput ? phoneInput.value.replace(/\s+/g, '') : '';

        if (!name || name.length < 2) {
          e.preventDefault();
          errBox.textContent = 'Họ và tên phải có ít nhất 2 ký tự.';
          errBox.style.display = 'block';
          if (nameInput) nameInput.focus();
          return;
        }

        if (!emailRegex.test(email)) {
          e.preventDefault();
          errBox.textContent = 'Email không đúng định dạng (Ví dụ: name@example.com).';
          errBox.style.display = 'block';
          document.getElementById('pageAuthEmail').focus();
          return;
        }

        if (phone && !phoneRegex.test(phone)) {
          e.preventDefault();
          errBox.textContent = 'Số điện thoại phải gồm đúng 10 chữ số và bắt đầu bằng số 0 (Ví dụ: 0912345678).';
          errBox.style.display = 'block';
          phoneInput.focus();
          return;
        }

        if (password.length < 8) {
          e.preventDefault();
          errBox.textContent = 'Mật khẩu phải có độ dài tối thiểu từ 8 ký tự trở lên.';
          errBox.style.display = 'block';
          document.getElementById('pageAuthPassword').focus();
          return;
        }

        const consentCb = document.getElementById('pageConsentCheckbox');
        if (consentCb && !consentCb.checked) {
          e.preventDefault();
          errBox.textContent = 'Vui lòng tích chọn đồng ý với Chính sách bảo mật dữ liệu cá nhân và Điều khoản sử dụng trước khi tạo tài khoản.';
          errBox.style.display = 'block';
          consentCb.focus();
          return;
        }
      } else {
        const cleanLoginId = email.replace(/\s+/g, '');
        if (!emailRegex.test(email) && !phoneRegex.test(cleanLoginId)) {
          e.preventDefault();
          errBox.textContent = 'Vui lòng nhập đúng định dạng Email hoặc Số điện thoại 10 chữ số.';
          errBox.style.display = 'block';
          document.getElementById('pageAuthEmail').focus();
          return;
        }
        if (!password) {
          e.preventDefault();
          errBox.textContent = 'Vui lòng nhập mật khẩu.';
          errBox.style.display = 'block';
          document.getElementById('pageAuthPassword').focus();
          return;
        }
      }
      errBox.style.display = 'none';
    });
  }
});

function togglePagePassword(btn) {
  const input = document.getElementById('pageAuthPassword');
  if (input.type === 'password') {
    input.type = 'text';
    btn.innerHTML = `<?= kibo_icon('eyeOff', 18) ?>`;
  } else {
    input.type = 'password';
    btn.innerHTML = `<?= kibo_icon('eye', 18) ?>`;
  }
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
