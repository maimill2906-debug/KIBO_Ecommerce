/**
 * KIBO — PET'S HOME CLIENT-SIDE APPLICATION (assets/js/app.js)
 * Quản lý giỏ hàng, Freeship >= 200k, Trích 5% Quỹ Cứu Trợ, Cart Drawer & Thanh Toán
 */

const FREESHIP_THRESHOLD = 200000;
const SHIPPING_FEE = 20000;
const RESCUE_PERCENT = 0.05;

// Khởi tạo giỏ hàng mặc định nếu chưa có
function getCart() {
  const data = localStorage.getItem('kibo_cart');
  if (data) {
    try {
      return JSON.parse(data);
    } catch (e) {
      console.error(e);
    }
  }
  // Giỏ hàng mẫu mặc định để người dùng thấy tính năng ngay
  return [
    {
      id: 'SP003',
      name: 'KIBO Pate Cá Ngừ Tươi',
      price: 170000,
      qty: 1,
      image: 'assets/images/products/sp_kibo_pate_cangu.png'
    }
  ];
}

function saveCart(cart) {
  localStorage.setItem('kibo_cart', JSON.stringify(cart));
  updateCartUI();
}

function formatVND(amount) {
  return new Intl.NumberFormat('vi-VN').format(Math.round(amount)) + 'đ';
}

// Mở và đóng Cart Drawer
function openCartDrawer() {
  const drawer = document.getElementById('cartDrawer');
  const overlay = document.getElementById('cartOverlay');
  if (drawer && overlay) {
    drawer.classList.add('active');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeCartDrawer() {
  const drawer = document.getElementById('cartDrawer');
  const overlay = document.getElementById('cartOverlay');
  if (drawer && overlay) {
    drawer.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }
}

// Cập nhật giao diện giỏ hàng (Header, Drawer & Trang giỏ hàng)
function updateCartUI() {
  const cart = getCart();
  const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

  // 1. Cập nhật Badge & Giá trên Header
  const countBadges = document.querySelectorAll('.js-cart-count');
  countBadges.forEach(el => el.textContent = totalQty);

  const headerSubtotal = document.querySelector('.js-cart-header-subtotal');
  if (headerSubtotal) {
    headerSubtotal.textContent = formatVND(subtotal);
  }

  // 2. Tính toán chính sách Freeship >= 200.000đ
  const isFreeship = subtotal >= FREESHIP_THRESHOLD;
  const shippingFee = (subtotal === 0 || isFreeship) ? 0 : SHIPPING_FEE;
  const grandTotal = subtotal + shippingFee;
  const rescueFundAmount = subtotal * RESCUE_PERCENT;

  // Thanh tiến trình Freeship trên Drawer
  const progressPercent = Math.min(100, (subtotal / FREESHIP_THRESHOLD) * 100);
  const progressFill = document.getElementById('freeshipProgressFill');
  const freeshipRemaining = document.getElementById('freeshipRemaining');
  const freeshipNotice = document.getElementById('freeshipNotice');

  if (progressFill) progressFill.style.width = progressPercent + '%';
  if (freeshipRemaining && freeshipNotice) {
    if (isFreeship) {
      freeshipNotice.innerHTML = '<span style="color: var(--ph-green);"><i class="fa-solid fa-circle-check"></i> Đơn hàng của bạn được <strong>Freeship toàn quốc!</strong></span>';
    } else {
      const remaining = FREESHIP_THRESHOLD - subtotal;
      freeshipNotice.innerHTML = `<span>Mua thêm <strong style="color: var(--ph-green);">${formatVND(remaining)}</strong> để được <strong>Freeship</strong>!</span>`;
    }
  }

  // Cập nhật giá tóm tắt trên Drawer
  const drawerSubtotal = document.getElementById('cartDrawerSubtotal');
  const drawerRescue = document.getElementById('cartDrawerRescueAmount');
  const drawerTotal = document.getElementById('cartDrawerTotal');

  if (drawerSubtotal) drawerSubtotal.textContent = formatVND(subtotal);
  if (drawerRescue) drawerRescue.textContent = formatVND(rescueFundAmount);
  if (drawerTotal) drawerTotal.textContent = formatVND(grandTotal);

  // Render danh sách sản phẩm trong Drawer
  const drawerItemsContainer = document.getElementById('cartItemsList');
  if (drawerItemsContainer) {
    if (cart.length === 0) {
      drawerItemsContainer.innerHTML = `
        <div style="text-align: center; padding: 40px 0; color: #888;">
          <i class="fa-solid fa-basket-shopping" style="font-size: 38px; color: #CCC; margin-bottom: 12px; display: block;"></i>
          <p>Giỏ hàng của bạn đang trống</p>
          <a href="index.php?action=products" class="btn-primary-green" style="margin-top: 14px; font-size: 11px;">Mua sắm ngay</a>
        </div>
      `;
    } else {
      drawerItemsContainer.innerHTML = cart.map(item => `
        <div style="display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--ph-border); align-items: center;">
          <img src="${item.image}" alt="${item.name}" style="width: 54px; height: 54px; object-fit: cover; border-radius: 4px; border: 1px solid #EEE;" onerror="this.src='https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=100&auto=format&fit=crop&q=80'">
          <div style="flex: 1;">
            <div style="font-size: 12px; font-weight: 700; color: var(--ph-brown); line-height: 1.3; margin-bottom: 4px;">${item.name}</div>
            <div style="font-size: 12px; font-weight: 800; color: var(--ph-green); font-family: var(--font-nav);">${formatVND(item.price)}</div>
            <div style="display: flex; align-items: center; gap: 8px; margin-top: 6px;">
              <div style="display: flex; border: 1px solid #DDD; border-radius: 3px; overflow: hidden;">
                <button type="button" onclick="changeQty('${item.id}', -1)" style="border: none; background: #F4F3F0; width: 22px; height: 22px; cursor: pointer;">-</button>
                <span style="width: 28px; text-align: center; font-size: 11px; line-height: 22px; font-weight: 700;">${item.qty}</span>
                <button type="button" onclick="changeQty('${item.id}', 1)" style="border: none; background: #F4F3F0; width: 22px; height: 22px; cursor: pointer;">+</button>
              </div>
              <button type="button" onclick="removeFromCart('${item.id}')" style="background: none; border: none; font-size: 11px; color: #999; cursor: pointer; margin-left: auto;">
                <i class="fa-regular fa-trash-can"></i> Xóa
              </button>
            </div>
          </div>
        </div>
      `).join('');
    }
  }

  // 3. Render giao diện Trang Giỏ Hàng & Đặt Hàng (/cart) nếu đang ở trang đó
  renderCartPage(cart, subtotal, shippingFee, isFreeship, rescueFundAmount, grandTotal);
}

function renderCartPage(cart, subtotal, shippingFee, isFreeship, rescueFundAmount, grandTotal) {
  const pageList = document.getElementById('pageCartList');
  if (!pageList) return;

  const countText = document.getElementById('pageCartItemCountText');
  if (countText) countText.textContent = `${cart.length} sản phẩm`;

  const freeshipBar = document.getElementById('pageFreeshipBar');
  const freeshipStatus = document.getElementById('pageFreeshipStatus');
  if (freeshipBar && freeshipStatus) {
    const pct = Math.min(100, (subtotal / FREESHIP_THRESHOLD) * 100);
    freeshipBar.style.width = pct + '%';
    if (isFreeship) {
      freeshipStatus.textContent = 'Bạn đã đạt điều kiện Freeship toàn quốc!';
    } else {
      freeshipStatus.textContent = `Cần thêm ${formatVND(FREESHIP_THRESHOLD - subtotal)} để Freeship!`;
    }
  }

  if (cart.length === 0) {
    pageList.innerHTML = `
      <div style="text-align: center; padding: 40px 0; color: #888;">
        <i class="fa-solid fa-basket-shopping" style="font-size: 48px; color: #CCC; margin-bottom: 12px; display: block;"></i>
        <p style="font-size: 15px;">Giỏ hàng của bạn đang trống</p>
        <a href="index.php?action=products" class="btn-primary-green" style="margin-top: 14px; font-size: 13px;">Khám phá sản phẩm ngay</a>
      </div>
    `;
  } else {
    pageList.innerHTML = cart.map(item => `
      <div style="display: flex; gap: 16px; padding: 14px; border: 1px solid var(--ph-border); border-radius: 4px; align-items: center; background: #FAF8F5;">
        <img src="${item.image}" alt="${item.name}" style="width: 72px; height: 72px; object-fit: cover; border-radius: 4px; border: 1px solid #EEE;" onerror="this.src='https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=120&auto=format&fit=crop&q=80'">
        <div style="flex: 1;">
          <h4 style="font-size: 14px; font-weight: 700; color: var(--ph-brown); margin-bottom: 4px;">${item.name}</h4>
          <div style="font-size: 13px; font-weight: 800; color: var(--ph-green); font-family: var(--font-nav);">${formatVND(item.price)}</div>
        </div>
        <div style="display: flex; border: 1px solid #CCC; border-radius: 4px; overflow: hidden; background: #FFF;">
          <button type="button" onclick="changeQty('${item.id}', -1)" style="border: none; background: #EFEFEF; width: 28px; height: 28px; cursor: pointer; font-weight: 700;">-</button>
          <span style="width: 36px; text-align: center; font-size: 13px; line-height: 28px; font-weight: 700;">${item.qty}</span>
          <button type="button" onclick="changeQty('${item.id}', 1)" style="border: none; background: #EFEFEF; width: 28px; height: 28px; cursor: pointer; font-weight: 700;">+</button>
        </div>
        <div style="font-family: var(--font-nav); font-size: 15px; font-weight: 800; color: var(--ph-brown); min-width: 90px; text-align: right;">
          ${formatVND(item.price * item.qty)}
        </div>
        <button type="button" onclick="removeFromCart('${item.id}')" style="background: none; border: none; color: #999; cursor: pointer; font-size: 14px;" title="Xóa sản phẩm">
          <i class="fa-solid fa-trash-can"></i>
        </button>
      </div>
    `).join('');
  }

  // Tóm tắt đơn hàng bên phải
  const summarySub = document.getElementById('summarySubtotal');
  const summaryShip = document.getElementById('summaryShipping');
  const summaryRescue = document.getElementById('summaryRescue');
  const summaryTot = document.getElementById('summaryTotal');

  if (summarySub) summarySub.textContent = formatVND(subtotal);
  if (summaryShip) summaryShip.textContent = shippingFee === 0 ? 'Miễn phí' : formatVND(shippingFee);
  if (summaryRescue) summaryRescue.textContent = formatVND(rescueFundAmount);
  if (summaryTot) summaryTot.textContent = formatVND(grandTotal);
}

// Thay đổi số lượng
function changeQty(id, delta) {
  let cart = getCart();
  const item = cart.find(x => x.id === id);
  if (item) {
    item.qty += delta;
    if (item.qty <= 0) {
      cart = cart.filter(x => x.id !== id);
    }
    saveCart(cart);
  }
}

// Xóa sản phẩm khỏi giỏ
function removeFromCart(id) {
  let cart = getCart();
  cart = cart.filter(x => x.id !== id);
  saveCart(cart);
}

// Xóa trắng giỏ
function clearCart() {
  if (confirm('Bạn có chắc chắn muốn xóa toàn bộ sản phẩm khỏi giỏ hàng?')) {
    saveCart([]);
  }
}

// Xử lý nút "Thêm vào giỏ" trên toàn bộ trang
function setupAddToCartButtons() {
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.js-add-cart-btn');
    if (!btn) return;

    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name');
    const price = parseInt(btn.getAttribute('data-price') || '0', 10);
    const img = btn.getAttribute('data-img');

    let cart = getCart();
    const existing = cart.find(x => x.id === id);
    if (existing) {
      existing.qty += 1;
    } else {
      cart.push({ id, name, price, qty: 1, image: img });
    }
    saveCart(cart);

    // Hiệu ứng nút chuyển xanh
    const origHTML = btn.innerHTML;
    btn.classList.add('is-added');
    btn.innerHTML = '<i class="fa-solid fa-check"></i> <span>Đã thêm!</span>';
    setTimeout(() => {
      btn.classList.remove('is-added');
      btn.innerHTML = origHTML;
    }, 1200);

    // Mở drawer giỏ hàng để khách thấy ngay
    openCartDrawer();
  });
}

// Xử lý Đặt Hàng thành công
function handlePlaceOrder(e) {
  e.preventDefault();
  const cart = getCart();
  if (cart.length === 0) {
    alert('Giỏ hàng của bạn đang trống. Vui lòng chọn ít nhất 1 sản phẩm trước khi thanh toán!');
    return;
  }

  const name = document.getElementById('custName').value.trim();
  const phone = document.getElementById('custPhone').value.trim();
  const address = document.getElementById('custAddress').value.trim();
  const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'COD';

  const orderId = 'KB-' + Math.floor(1000 + Math.random() * 9000);
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
  const rescueAmount = Math.round(subtotal * 0.05);

  const confirmMsg = `
    ĐẶT HÀNG THÀNH CÔNG!
    -------------------------------------------
    Mã đơn hàng: ${orderId}
    Người nhận: ${name} (${phone})
    Địa chỉ: ${address}
    Phương thức: ${paymentMethod === 'COD' ? 'Thanh toán COD khi nhận hàng' : 'VietQR Ngân Hàng'}
    Khoản trích Quỹ Cứu Trợ 5%: +${formatVND(rescueAmount)}
    
    Hệ thống sẽ chuyển bạn đến trang Quỹ Cứu Trợ để theo dõi hành trình đóng góp!
  `;

  alert(confirmMsg);

  // Xóa giỏ hàng sau khi đặt thành công
  saveCart([]);

  // Chuyển sang trang Quỹ Cứu Trợ để tra cứu mã đơn vừa đặt
  window.location.href = `index.php?action=rescue&order_code=${orderId}`;
}

// Khởi chạy khi DOM sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
  updateCartUI();
  setupAddToCartButtons();
});
