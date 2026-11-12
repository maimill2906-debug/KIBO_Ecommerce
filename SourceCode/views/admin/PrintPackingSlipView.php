<?php
// ====================================================================
// VIEW: PrintPackingSlipView.php
// Phiếu xuất kho & Giao hàng kiêm Phiếu đóng gói (In A4 / A5)
// ====================================================================

if (empty($order)) {
    die("Không tìm thấy thông tin đơn hàng để in.");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Phiếu Giao Hàng & Đóng Gói - <?= htmlspecialchars($order['orderId']) ?></title>
  <style>
    @page { size: A5 landscape; margin: 12mm; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; color: #222; margin: 0; padding: 20px; line-height: 1.4; }
    .slip-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #2c1a0e; padding-bottom: 12px; margin-bottom: 16px; }
    .slip-brand h1 { margin: 0; font-size: 20px; color: #2c1a0e; text-transform: uppercase; }
    .slip-brand p { margin: 3px 0 0; font-size: 11px; color: #666; }
    .slip-meta { text-align: right; }
    .slip-meta .order-code { font-size: 18px; font-weight: bold; color: #2e7d32; }
    .slip-meta .tracking { font-size: 12px; color: #444; margin-top: 2px; }
    
    .customer-info { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; background: #fdfaf7; padding: 10px 14px; border-radius: 6px; border: 1px solid #ebdcd0; }
    .customer-info h4 { margin: 0 0 6px; font-size: 12px; color: #777; text-transform: uppercase; }
    
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table.items th, table.items td { border: 1px solid #ddd; padding: 7px 10px; text-align: left; }
    table.items th { background: #f5ede6; font-size: 11.5px; text-transform: uppercase; color: #333; }
    table.items td.num { text-align: right; }
    table.items td.center { text-align: center; }
    
    .summary { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .summary .rescue-note { max-width: 55%; font-size: 12px; color: #1e3a1e; background: #edf7ee; padding: 10px; border-radius: 6px; border-left: 3px solid #2e7d32; }
    .summary .totals { width: 40%; text-align: right; }
    .summary .totals .row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; }
    .summary .totals .grand { font-size: 15px; font-weight: bold; color: #2c1a0e; border-top: 1px solid #333; padding-top: 6px; margin-top: 4px; }
    
    .signatures { display: grid; grid-template-columns: 1fr 1fr 1fr; text-align: center; margin-top: 30px; font-size: 12px; }
    .signatures .sign-box { min-height: 70px; }
    
    @media print {
      .no-print { display: none !important; }
      body { padding: 0; }
    }
  </style>
</head>
<body>

  <div class="no-print" style="margin-bottom: 16px; text-align: right;">
    <button onclick="window.print()" style="padding: 8px 18px; font-size: 14px; background: #2e7d32; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">🖨️ In Phiếu Soạn Hàng</button>
    <button onclick="window.close()" style="padding: 8px 14px; font-size: 14px; background: #777; color: #fff; border: none; border-radius: 4px; cursor: pointer; margin-left: 8px;">Đóng</button>
  </div>

  <div class="slip-header">
    <div class="slip-brand">
      <h1>KIBO PET'S HOME</h1>
      <p>Thức ăn &amp; Dinh dưỡng Thú Cưng chính hãng | Hotline: <?= htmlspecialchars(kibo_business('hotline') ?? '0772 906 756') ?></p>
      <p>Kho hàng: Kho Trung Tâm TP.HCM</p>
    </div>
    <div class="slip-meta">
      <div class="order-code">ĐƠN #<?= htmlspecialchars($order['orderId']) ?></div>
      <div class="tracking">Mã vận đơn: <strong><?= htmlspecialchars($order['mavandon'] ?? 'CHƯA GÁN') ?></strong></div>
      <div style="font-size: 11px; color: #666; margin-top: 2px;">Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['createdAt'] ?? 'now')) ?></div>
    </div>
  </div>

  <div class="customer-info">
    <div>
      <h4>Thông tin người nhận</h4>
      <strong><?= htmlspecialchars($order['customerName'] ?? 'Khách hàng') ?></strong><br>
      SĐT: <strong><?= htmlspecialchars($order['customerPhone'] ?? '') ?></strong><br>
      Địa chỉ: <?= htmlspecialchars($order['deliveryAddress'] ?? '') ?>
    </div>
    <div>
      <h4>Thông tin vận chuyển &amp; Thanh toán</h4>
      Hình thức giao: <strong>Giao hàng tận nơi tiêu chuẩn</strong><br>
      Phương thức thanh toán: <strong><?= strtoupper(htmlspecialchars($order['paymentMethod'] ?? 'COD')) ?></strong><br>
      Ghi chú đơn: <em><?= htmlspecialchars($order['note'] ?: 'Không có ghi chú') ?></em>
    </div>
  </div>

  <table class="items">
    <thead>
      <tr>
        <th style="width: 5%;" class="center">STT</th>
        <th style="width: 50%;">Tên sản phẩm &amp; Quy cách</th>
        <th style="width: 15%;" class="center">Số lượng</th>
        <th style="width: 15%;" class="num">Đơn giá</th>
        <th style="width: 15%;" class="num">Thành tiền</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      $stt = 1;
      foreach ($order['items'] as $item): 
        $sub = (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1);
      ?>
        <tr>
          <td class="center"><?= $stt++ ?></td>
          <td>
            <strong><?= htmlspecialchars($item['name'] ?? 'Sản phẩm') ?></strong>
            <?php if (!empty($item['variant'])): ?>
              <span style="font-size: 11px; color: #666;">(<?= htmlspecialchars($item['variant']) ?>)</span>
            <?php endif; ?>
          </td>
          <td class="center"><strong><?= (int)$item['quantity'] ?></strong></td>
          <td class="num"><?= number_format((float)$item['price'], 0, ',', '.') ?>đ</td>
          <td class="num"><strong><?= number_format($sub, 0, ',', '.') ?>đ</strong></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="summary">
    <div class="rescue-note">
      🐾 <strong>Đồng hành cùng Quỹ cứu trợ KIBO:</strong><br>
      Đơn hàng này trích <strong><?= number_format((float)($order['fundAmount'] ?? 0), 0, ',', '.') ?>đ (5%)</strong> vào Quỹ cứu trợ động vật cơ nhỡ. Cảm ơn quý khách đã chung tay mang lại bữa ăn cho các bé thú cưng!
    </div>
    <div class="totals">
      <div class="row">
        <span>Tiền hàng:</span>
        <span><?= number_format((float)($order['subtotal'] ?? 0), 0, ',', '.') ?>đ</span>
      </div>
      <?php if (!empty($order['discount']) && (float)$order['discount'] > 0): ?>
        <div class="row" style="color: #c2410c;">
          <span>Giảm giá (<?= htmlspecialchars($order['voucherCode'] ?? 'Voucher') ?>):</span>
          <span>-<?= number_format((float)$order['discount'], 0, ',', '.') ?>đ</span>
        </div>
      <?php endif; ?>
      <div class="row">
        <span>Phí vận chuyển:</span>
        <span><?= ((float)($order['shipping'] ?? 0) === 0.0) ? 'Miễn phí' : number_format((float)$order['shipping'], 0, ',', '.') . 'đ' ?></span>
      </div>
      <div class="row grand">
        <span>Tổng thanh toán:</span>
        <span><?= number_format((float)($order['total'] ?? 0), 0, ',', '.') ?>đ</span>
      </div>
    </div>
  </div>

  <div class="signatures">
    <div>
      <strong>Người đóng hàng / Soạn kho</strong>
      <div class="sign-box"></div>
      <em>(Ký &amp; Ghi rõ họ tên)</em>
    </div>
    <div>
      <strong>Nhân viên giao vận</strong>
      <div class="sign-box"></div>
      <em>(Ký &amp; Ghi rõ họ tên)</em>
    </div>
    <div>
      <strong>Người nhận hàng</strong>
      <div class="sign-box"></div>
      <em>(Ký xác nhận nguyên vẹn)</em>
    </div>
  </div>

</body>
</html>
