<?php
/**
 * FOOTER: views/customer/footer.php
 * Thiết kế 100% chuẩn xác theo KIBO Pet E-Commerce Platform (1)
 */
?>
  <!-- ====================================================================
       FOOTER CHÍNH
       ==================================================================== -->
  <footer class="footer">
    <div class="container footer-grid">
      <!-- Cột 1: Thương hiệu KIBO -->
      <div>
        <a href="index.php?action=home" class="logo-button" style="display: inline-block; margin-bottom: 14px;">
          <?= kibo_logo_svg('light', 42) ?>
        </a>
        <p>KIBO tin rằng mọi thú cưng đều xứng đáng được yêu thương. Mỗi đơn hàng là một hành động nhỏ tạo nên thay đổi lớn.</p>
        <div class="footer-social">
          <span>f</span>
          <span>◎</span>
          <span>♪</span>
        </div>
      </div>

      <!-- Cột 2: Khám phá -->
      <div>
        <h3>Khám phá</h3>
        <a href="index.php?action=about">Về KIBO</a>
        <a href="index.php?action=products">Sản phẩm cho chó</a>
        <a href="index.php?action=products">Sản phẩm cho mèo</a>
        <a href="index.php?action=rescue">Quỹ cứu trợ 5%</a>
        <a href="index.php?action=tracking">Tra cứu đơn hàng</a>
      </div>

      <!-- Cột 3: Hỗ trợ khách hàng -->
      <div>
        <h3>Hỗ trợ khách hàng</h3>
        <a href="javascript:void(0)">Chính sách giao hàng</a>
        <a href="javascript:void(0)">Đổi trả trong 3 ngày</a>
        <a href="javascript:void(0)">Điều khoản sử dụng</a>
        <a href="javascript:void(0)">Chính sách bảo mật</a>
      </div>

      <!-- Cột 4: KIBO luôn lắng nghe -->
      <div>
        <h3>KIBO luôn lắng nghe</h3>
        <p class="footer-phone">
          <?= kibo_icon('phone', 22) ?>
          <span>
            <small>HOTLINE TƯ VẤN</small>
            <strong><?= htmlspecialchars(kibo_business('hotline') ?? '1900 6868') ?></strong>
          </span>
        </p>
        <p style="margin-top: 14px;">Thứ 2 - Chủ nhật: 8:00 - 21:00<br><?= htmlspecialchars(kibo_business('email') ?? 'support@kibo.vn') ?></p>
      </div>
    </div>

    <div class="container copyright">
      <span>© 2025 KIBO Pet's Home. All rights reserved.</span>
    </div>
  </footer>

  <!-- ====================================================================
       CART MODAL (Chuẩn 100% Theo Mẫu Ảnh 1)
       ==================================================================== -->
  <div class="modal-backdrop" id="cartModalBackdrop" style="display: none;" onclick="if(event.target === this) closeCartModal()">
    <section class="modal cart-modal" style="width: min(780px, 95vw); max-width: 780px; padding: 0; overflow: hidden; border-radius: 10px; background: #ffffff;">
      <!-- Toast thông báo trên cùng theo mẫu ảnh 1 -->
      <div style="background: #23160e; color: #ffffff; padding: 13px 20px; display: flex; align-items: center; justify-content: space-between; font-size: 14.5px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <span style="font-size: 16px;">🔔</span>
          <span>Bạn đã thêm <strong id="modalToastProdName" style="color: #f7ca54;">Combo Trị nấm cho Chó</strong> vào giỏ hàng</span>
        </div>
        <button type="button" onclick="closeCartModal()" style="background: transparent; border: none; color: #d5c8be; font-size: 18px; cursor: pointer;">✕</button>
      </div>

      <div style="padding: 24px 28px 28px; background: #ffffff;">
        <h2 style="font-size: 20px; font-weight: 800; color: #2c1a0e; margin: 0 0 18px;">
          Giỏ hàng của bạn có <span id="modalCartCount">1</span> sản phẩm
        </h2>

        <!-- BẢNG SẢN PHẨM THEO MẪU ẢNH 1 -->
        <table style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="background: #fbf9f6; border-bottom: 1.5px solid #ebe5df;">
              <th style="padding: 12px 14px; text-align: left; font-size: 13.5px; font-weight: 700; color: #6a5e54; width: 48%;">Sản phẩm</th>
              <th style="padding: 12px 14px; text-align: left; font-size: 13.5px; font-weight: 700; color: #6a5e54; width: 18%;">Đơn giá</th>
              <th style="padding: 12px 14px; text-align: center; font-size: 13.5px; font-weight: 700; color: #6a5e54; width: 16%;">Số lượng</th>
              <th style="padding: 12px 14px; text-align: right; font-size: 13.5px; font-weight: 700; color: #6a5e54; width: 18%;">Thành tiền</th>
            </tr>
          </thead>
          <tbody id="modalCartTableBody">
            <!-- Render từ JS -->
          </tbody>
        </table>

        <!-- FOOTER: TIẾP TỤC MUA HÀNG & THÀNH TIỀN -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 0 10px; border-bottom: 1px solid #eee8e2;">
          <a href="javascript:void(0)" onclick="closeCartModal()" style="color: #6a5e54; font-size: 15px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px;">
            ◂ Tiếp tục mua hàng
          </a>
          <div style="font-size: 16px; color: #2c1a0e;">
            Thành tiền: <strong id="modalTableTotal" style="font-size: 20px; font-weight: 800; color: #1c1108; margin-left: 6px;">1.075.000₫</strong>
          </div>
        </div>

        <!-- NÚT THANH TOÁN ĐƠN HÀNG TO BẢN ĐIỀU HƯỚNG SANG ẢNH 2 -->
        <button type="button" onclick="goToCheckoutFromModal()" style="width: 100%; height: 52px; background: #1a0f07; color: #ffffff; border: none; border-radius: 6px; font-size: 16px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; cursor: pointer; margin-top: 16px; transition: background .2s;">
          THANH TOÁN ĐƠN HÀNG
        </button>
      </div>

      <!-- Khung ẩn để tương thích backward-compatibility -->
      <div style="display: none;">
        <div id="cartModalItems"></div>
        <input type="text" id="modalCustName" value="">
        <input type="text" id="modalCustPhone" value="">
        <input type="text" id="modalCustAddress" value="">
        <span id="modalSubtotal">0đ</span>
        <span id="modalShipping">0đ</span>
        <span id="modalFreeshipRow"></span>
        <span id="modalRescueFund">+0đ</span>
        <span id="modalGrandTotal">0đ</span>
      </div>
    </section>
  </div>

  <!-- ====================================================================
       AUTH MODAL (Đăng nhập / Đăng ký)
       ==================================================================== -->
  <div class="modal-backdrop" id="authModalBackdrop" style="display: none;" onclick="if(event.target === this) closeAuthModal()">
    <section class="modal auth-modal">
      <button type="button" class="modal-close btn" onclick="closeAuthModal()" aria-label="Đóng popup"><?= kibo_icon('close', 20) ?></button>
      <div class="auth-brand">
        <?= kibo_logo_svg('default', 52) ?>
        <h2 id="authModalTitle" style="margin-top: 12px;">Đăng nhập</h2>
      </div>

      <div class="auth-tabs">
        <button type="button" id="tabLogin" class="btn active" onclick="switchAuthTab('login')">Đăng nhập</button>
        <button type="button" id="tabRegister" class="btn" onclick="switchAuthTab('register')">Đăng ký mới</button>
      </div>

      <form class="auth-form" onsubmit="handleAuthSubmit(event)">
        <div id="registerFields" style="display: none;">
          <label class="field">
            <span>Họ và tên</span>
            <input type="text" id="authName" placeholder="Họ và tên">
          </label>
        </div>

        <label class="field">
          <span>Email hoặc Số điện thoại</span>
          <input type="text" id="authEmail" placeholder="Email hoặc số điện thoại" required>
        </label>

        <label class="field">
          <span>Mật khẩu</span>
          <div class="password-input">
            <input type="password" id="authPassword" placeholder="Nhập mật khẩu" required>
            <button type="button" class="password-toggle btn" onclick="togglePasswordVisibility()" aria-label="Hiện/ẩn mật khẩu">
              <?= kibo_icon('eye', 20) ?>
            </button>
          </div>
        </label>

        <div class="login-options" id="loginOptions">
          <label class="remember">
            <input type="checkbox" checked>
            <span>Ghi nhớ tài khoản</span>
          </label>
          <a href="#" class="forgot" onclick="event.preventDefault(); showAuthError('Vui lòng liên hệ Hotline 0772 906 756 để được hỗ trợ đặt lại mật khẩu.');">Quên mật khẩu?</a>
        </div>

        <div id="authSuccess" style="display: none; background: #f7f7f7; color: #222; border: 1px solid #dcdcdc; padding: 10px 14px; border-radius: 4px; font-size: 13px; line-height: 1.4; margin-bottom: 12px; text-align: left;"></div>
        <div class="auth-error" id="authError" style="display: none;"></div>

        <button type="submit" class="btn primary confirm" id="authSubmitBtn" style="min-height: 52px; font-size: 16.5px; font-weight: 700; margin-top: 10px;">
          Đăng nhập ngay <?= kibo_icon('arrow', 18) ?>
        </button>
      </form>
    </section>
  </div>

  <!-- ====================================================================
       SCRIPTS TOÀN BỘ GIỎ HÀNG, ĐĂNG NHẬP, QUỸ 5%
       ==================================================================== -->
  <script>
    const KIBO_CART_KEY = 'kibo_cart_items_v2';
    const CART_OWNER_KEY = 'kibo_cart_owner';

    async function syncCartWithOwner() {
      const u = window.KIBO_USER;
      const currentOwner = localStorage.getItem(CART_OWNER_KEY);

      if (u && u.makh) {
        if (currentOwner !== u.makh) {
          try {
            const resp = await fetch('index.php?action=get_cart');
            const data = await resp.json();
            const dbCart = (data && data.cart) ? data.cart : [];
            const localCart = getCart();

            const map = {};
            localCart.forEach(it => { map[it.id] = it; });
            dbCart.forEach(it => {
              if (map[it.id]) {
                map[it.id].quantity = Math.max(map[it.id].quantity, it.quantity);
              } else {
                map[it.id] = it;
              }
            });
            const merged = Object.values(map).filter(it => it && /^BT\d+$/.test(it.id));
            localStorage.setItem(KIBO_CART_KEY, JSON.stringify(merged));
            localStorage.setItem(CART_OWNER_KEY, u.makh);
            updateCartHeader();
            fetch('index.php?action=sync_cart', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ items: merged })
            }).catch(() => {});
          } catch (e) {}
        }
      } else {
        if (currentOwner) {
          localStorage.removeItem(KIBO_CART_KEY);
          localStorage.removeItem(CART_OWNER_KEY);
          updateCartHeader();
        }
      }
    }
    syncCartWithOwner();

    function getCart() {
      try {
        const stored = localStorage.getItem(KIBO_CART_KEY);
        if (stored) {
          const parsed = JSON.parse(stored);
          if (Array.isArray(parsed)) {
            return parsed.filter(item => item && item.id && /^BT\d+$/.test(item.id));
          }
        }
      } catch (e) {}
      return [];
    }

    function saveCart(cart) {
      localStorage.setItem(KIBO_CART_KEY, JSON.stringify(cart));
      updateCartHeader();
      try {
        fetch('index.php?action=sync_cart', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ items: cart })
        }).catch(() => {});
      } catch (e) {}
    }

    function formatVND(num) {
      return new Intl.NumberFormat('vi-VN').format(num) + 'đ';
    }

    function updateCartHeader() {
      const cart = getCart();
      const count = cart.reduce((sum, item) => sum + item.quantity, 0);
      const badge = document.getElementById('headerCartCountText');
      if (badge) {
        badge.textContent = `(${count}) Sản phẩm`;
        badge.style.transition = 'transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275), color 0.2s ease';
        badge.style.transform = 'scale(1.3)';
        badge.style.color = '#2e7d32';
        setTimeout(() => {
          badge.style.transform = 'scale(1)';
          badge.style.color = '';
        }, 350);
      }
    }

    function showKiboToast(msg) {
      let toast = document.getElementById('kiboGlobalToast');
      if (!toast) {
        toast = document.createElement('div');
        toast.id = 'kiboGlobalToast';
        document.body.appendChild(toast);
      }
      const cleanMsg = String(msg).replace(/<\/?strong>/gi, '');
      toast.style.cssText = 'position: fixed; top: 18px; right: 18px; z-index: 9999999; background: #ebebeb; color: #222222; padding: 10px 16px; border-radius: 4px; font-family: Arial, sans-serif; font-size: 13.5px; font-weight: normal; line-height: 1.4; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.12); border: 1px solid #cccccc;';
      toast.innerHTML = `<span style="font-weight: bold; color: #2e7d32;">✓</span> <span style="font-family: Arial, sans-serif; font-size: 13.5px; font-weight: normal; color: #222222;">${cleanMsg}</span>`;
      toast.style.display = 'flex';

      clearTimeout(window._toastTimeout);
      window._toastTimeout = setTimeout(() => {
        toast.style.display = 'none';
      }, 3000);
    }

    function addToCart(item, maybeName, maybePrice, maybeVariant, maybeColor, maybeArt, maybeQty) {
      let id, name, price, variant, color, art, qty;
      if (typeof item === 'object' && item !== null) {
        id = item.id;
        name = item.name;
        price = Number(item.price) || 0;
        variant = item.variant || 'Tiêu chuẩn';
        color = item.color || '#c94336';
        art = item.art || 'royal';
        qty = Number(item.quantity || item.qty) || 1;
      } else {
        id = item;
        name = maybeName;
        price = Number(maybePrice) || 0;
        variant = maybeVariant || 'Tiêu chuẩn';
        color = maybeColor || '#c94336';
        art = maybeArt || 'royal';
        qty = Number(maybeQty) || 1;
      }

      const cart = getCart();
      const existing = cart.find(i => i.id === id && i.variant === variant);
      if (existing) {
        existing.quantity += qty;
      } else {
        cart.push({ id, name, variant, price, quantity: qty, color, art });
      }
      saveCart(cart);
      updateCartHeader();

      // Hiển thị thông báo toast nổi trên góc màn hình và cập nhật số lượng, KHÔNG điều hướng
      showKiboToast(`Đã thêm ${name} (+${qty}) vào giỏ hàng!`);
    }
    window.addToCart = addToCart;
    window.addToCartDirect = addToCart;

    function removeModalItem(id, variant) {
      let cart = getCart();
      cart = cart.filter(i => !(i.id === id && i.variant === variant));
      saveCart(cart);
      renderCartModal();
    }

    function openCartModal() {
      renderCartModal();
      document.getElementById('cartModalBackdrop').style.display = 'grid';
    }

    function closeCartModal() {
      document.getElementById('cartModalBackdrop').style.display = 'none';
    }

    function changeModalItemQty(id, variant, delta) {
      let cart = getCart();
      const item = cart.find(i => i.id === id && i.variant === variant);
      if (item) {
        item.quantity += delta;
        if (item.quantity <= 0) {
          cart = cart.filter(i => !(i.id === id && i.variant === variant));
        }
        saveCart(cart);
        renderCartModal();
      }
    }

    function renderCartModal() {
      const cart = getCart();
      const tableBody = document.getElementById('modalCartTableBody');
      const countEl = document.getElementById('modalCartCount');
      const toastNameEl = document.getElementById('modalToastProdName');
      const totalEl = document.getElementById('modalTableTotal');

      if (countEl) countEl.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
      if (toastNameEl && cart.length > 0) toastNameEl.textContent = cart[cart.length - 1].name;

      if (!tableBody) return;

      if (cart.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" style="padding: 24px 0; text-align: center; color: var(--muted); font-size: 15px;">Giỏ hàng của bạn đang trống.</td></tr>';
        if (totalEl) totalEl.textContent = '0₫';
      } else {
        tableBody.innerHTML = cart.map(item => `
          <tr style="border-bottom: 1px solid #eee8e2;">
            <td style="padding: 14px 12px;">
              <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 68px; height: 68px; border-radius: 6px; border: 1px solid #ebe5df; background: #faf8f5; display: grid; place-items: center; font-size: 26px; flex-shrink: 0;">
                  🐶
                </div>
                <div>
                  <strong style="display: block; font-size: 15.5px; color: #2c1a0e; margin-bottom: 3px;">${item.name}</strong>
                  <div style="display: flex; align-items: center; gap: 10px;">
                    <a href="javascript:void(0)" onclick="removeModalItem('${item.id}', '${item.variant}')" style="color: #887d74; font-size: 13px; text-decoration: none; cursor: pointer;">
                      ✖ Bỏ sản phẩm
                    </a>
                    <span style="color: #2e7d32; font-size: 13px; font-weight: 600;">✔ Sản phẩm vừa thêm!</span>
                  </div>
                </div>
              </div>
            </td>
            <td style="padding: 14px 12px; font-weight: 600; color: #3c3026; font-size: 15px;">
              ${formatVND(item.price)}
            </td>
            <td style="padding: 14px 12px; text-align: center;">
              <div style="display: inline-flex; align-items: center; border: 1.5px solid #d4ccc3; border-radius: 6px; overflow: hidden; background: #ffffff;">
                <button type="button" onclick="changeModalItemQty('${item.id}', '${item.variant}', -1)" style="width: 32px; height: 32px; background: #faf8f5; border: none; font-size: 16px; font-weight: 700; cursor: pointer;">−</button>
                <span style="width: 36px; text-align: center; font-size: 14.5px; font-weight: 700;">${item.quantity}</span>
                <button type="button" onclick="changeModalItemQty('${item.id}', '${item.variant}', 1)" style="width: 32px; height: 32px; background: #faf8f5; border: none; font-size: 16px; font-weight: 700; cursor: pointer;">+</button>
              </div>
            </td>
            <td style="padding: 14px 12px; text-align: right; font-weight: 800; color: #1c1108; font-size: 16px;">
              ${formatVND(item.price * item.quantity)}
            </td>
          </tr>
        `).join('');

        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        if (totalEl) totalEl.textContent = formatVND(subtotal);
      }
    }

    function goToCheckoutFromModal() {
      closeCartModal();
      window.location.href = 'index.php?action=cart&step=checkout';
    }

    function handleModalCheckout() {
      goToCheckoutFromModal();
    }

    // AUTH MODAL
    let currentAuthTab = 'login';
    function openAuthModal() {
      document.getElementById('authModalBackdrop').style.display = 'grid';
    }
    function closeAuthModal() {
      document.getElementById('authModalBackdrop').style.display = 'none';
    }
    function switchAuthTab(tab) {
      currentAuthTab = tab;
      const isLogin = tab === 'login';
      document.getElementById('tabLogin').className = 'btn ' + (isLogin ? 'active' : '');
      document.getElementById('tabRegister').className = 'btn ' + (!isLogin ? 'active' : '');
      document.getElementById('authModalTitle').textContent = isLogin ? 'Đăng nhập' : 'Tạo tài khoản KIBO';
      document.getElementById('registerFields').style.display = isLogin ? 'none' : 'block';
      document.getElementById('loginOptions').style.display = isLogin ? 'flex' : 'none';
      document.getElementById('authSubmitBtn').className = isLogin ? 'btn primary confirm' : 'btn primary green-button';
      document.getElementById('authSubmitBtn').innerHTML = (isLogin ? 'Đăng nhập ngay ' : 'Tạo tài khoản khách hàng ') + '<?= kibo_icon('arrow', 16) ?>';
      document.getElementById('authError').style.display = 'none';
      const succ = document.getElementById('authSuccess');
      if (succ) succ.style.display = 'none';
    }
    function togglePasswordVisibility() {
      const pwd = document.getElementById('authPassword');
      pwd.type = (pwd.type === 'password') ? 'text' : 'password';
    }
    async function handleAuthSubmit(e) {
      e.preventDefault();
      const email = document.getElementById('authEmail').value.trim();
      const password = document.getElementById('authPassword').value;
      const name = document.getElementById('authName') ? document.getElementById('authName').value.trim() : '';

      const isReg = (currentAuthTab === 'register');
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      // Validation Client-side
      if (isReg) {
        if (!name || name.length < 2) {
          showAuthError('Vui lòng nhập họ và tên đầy đủ (tối thiểu 2 ký tự).');
          if (document.getElementById('authName')) document.getElementById('authName').focus();
          return;
        }
      }

      if (!email || !emailRegex.test(email)) {
        showAuthError('Email không đúng định dạng (Ví dụ: name@example.com).');
        document.getElementById('authEmail').focus();
        return;
      }

      if (isReg) {
        if (password.length < 8) {
          showAuthError('Mật khẩu phải có độ dài tối thiểu từ 8 ký tự trở lên.');
          document.getElementById('authPassword').focus();
          return;
        }
      } else {
        if (!password) {
          showAuthError('Vui lòng nhập mật khẩu.');
          document.getElementById('authPassword').focus();
          return;
        }
      }

      const submitBtn = document.getElementById('authSubmitBtn');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '⏳ Vui lòng chờ...';

      try {
        const isReg = (currentAuthTab === 'register');
        const endpoint = isReg ? 'index.php?action=register_submit' : 'index.php?action=login_submit';
        const payload = isReg ? { name, email, password } : { email, password };

        const resp = await fetch(endpoint, {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(payload)
        });
        const res = await resp.json();

        if (isReg) {
          if (res.success) {
            switchAuthTab('login');
            document.getElementById('authEmail').value = email;
            document.getElementById('authPassword').value = '';
            const succ = document.getElementById('authSuccess');
            if (succ) {
              succ.innerHTML = '<span style="color: #2e7d32; font-weight: bold; margin-right: 4px;">✓</span> ' + (res.message || 'Tạo tài khoản thành công! Vui lòng nhập mật khẩu để đăng nhập.');
              succ.style.display = 'block';
              succ.style.background = '#f7f7f7';
              succ.style.color = '#222';
              succ.style.border = '1px solid #dcdcdc';
            }
            document.getElementById('authPassword').focus();
          } else {
            showAuthError(res.message || 'Đăng ký không thành công. Vui lòng kiểm tra lại!');
          }
        } else {
          if (res.success) {
            const userName = (res.user && (res.user.HoTen || res.user.TenDangNhap)) ? (res.user.HoTen || res.user.TenDangNhap) : '';
            const welcomeMsg = 'Đăng nhập thành công! Xin chào ' + (userName || 'bạn');

            const succ = document.getElementById('authSuccess');
            if (succ) {
              succ.innerHTML = '<span style="color: #2e7d32; font-weight: bold; margin-right: 4px;">✓</span> ' + welcomeMsg;
              succ.style.display = 'block';
              succ.style.background = '#f7f7f7';
              succ.style.color = '#222';
              succ.style.border = '1px solid #dcdcdc';
            }
            showKiboToast(welcomeMsg);

            try {
              sessionStorage.setItem('kibo_toast_success', welcomeMsg);
            } catch (e) {}

            setTimeout(() => {
              if (res.user && res.user.VaiTro === 'Admin') {
                window.location.href = 'index.php?action=admin';
              } else {
                closeAuthModal();
                window.location.reload();
              }
            }, 900);
          } else {
            showAuthError(res.message || 'Email hoặc mật khẩu không chính xác.');
          }
        }
      } catch (err) {
        showAuthError('Không thể kết nối đến máy chủ. Vui lòng thử lại!');
      } finally {
        submitBtn.disabled = false;
        const isLogin = (currentAuthTab === 'login');
        submitBtn.className = isLogin ? 'btn primary confirm' : 'btn primary green-button';
        submitBtn.innerHTML = (isLogin ? 'Đăng nhập ngay ' : 'Tạo tài khoản khách hàng ') + '<?= kibo_icon('arrow', 18) ?>';
      }
    }
    function showAuthError(msg) {
      const err = document.getElementById('authError');
      err.textContent = msg;
      err.style.display = 'block';
    }

    <?php
      $flashLoginSuccess = $_SESSION['login_success'] ?? null;
      unset($_SESSION['login_success']);
    ?>

    document.addEventListener('DOMContentLoaded', () => {
      updateCartHeader();

      const phpFlash = <?= json_encode($flashLoginSuccess) ?>;
      let sessionFlash = null;
      try {
        sessionFlash = sessionStorage.getItem('kibo_toast_success');
        if (sessionFlash) {
          sessionStorage.removeItem('kibo_toast_success');
        }
      } catch (e) {}

      if (sessionFlash) {
        showKiboToast(sessionFlash);
      } else if (phpFlash) {
        showKiboToast(phpFlash);
      }
    });
  </script>
</body>
</html>
