<?php
require_once 'config/database.php';
require_once 'config/auth.php';

$db = getDB();

$invoice_id = $_GET['invoice_id'] ?? null;
$showQR = false;

$confirm_payment = isset($_POST['confirm_payment']);
// Lấy thông tin hóa đơn
$stmt = $db->prepare("
    SELECT i.*, p.full_name
    FROM invoices i
    JOIN patients p ON i.patient_id = p.id
    WHERE i.id = ?
");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);
$amount = ($invoice['total_amount'] ?? 0) - ($invoice['paid_amount'] ?? 0);
if (!$invoice) {
    die('Không tìm thấy hóa đơn');
}

if (isset($_POST['confirm_payment']) &&
    $_POST['payment_method'] === 'transfer') {
        // render QR trước — CHƯA insert DB
        $showQR = true;

        $bankCode = "BIDV";
        $account  = "8854495157";
        $name     = "PHONG KHAM TCLINIC";
        $qr = "https://img.vietqr.io/image/$bankCode-$account-compact.png"
            . "?amount=$amount"
            . "&accountName=" . urlencode($name);
}
// Submit thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_code = generateCode('TT', 'payments', 'payment_code');
    $amount = $_POST['amount'];
    $method = $_POST['payment_method'];
    $notes  = $_POST['notes'];

    $stmt = $db->prepare("
        INSERT INTO payments (
            payment_code,
            invoice_id,
            amount,
            payment_method,
            notes,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $payment_code,
        $invoice_id,
        $amount,
        $method,
        $notes
    ]);

    // cập nhật trạng thái invoice
    $stmt = $db->prepare("
        UPDATE invoices 
        SET paid_amount = ?
        WHERE id = ?
    ");
    $stmt->execute([$amount, $invoice_id]);
    auditLog('create', 'payments', $db->lastInsertId(), null, $_POST);
    if($method!='transfer'){
      header("Location: payments.php?success=1");
    exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán hóa đơn</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php 
     require 'includes/header.php';
     require 'includes/sidebar.php';    
    ?>
    <div class="main-content">


    <h1>💳 Thanh toán hóa đơn</h1>

    <div class="section-card">

        <h3>Thông tin hóa đơn</h3>

        <table class="info-table">
            <tr>
                <th>Mã hóa đơn</th>
                <td><?= $invoice['invoice_code'] ?></td>
            </tr>
            <tr>
                <th>Bệnh nhân</th>
                <td><?= $invoice['full_name'] ?></td>
            </tr>
            <tr>
                <th>Tổng tiền</th>
                <td>
                    <strong>
                        <?= number_format($invoice['total_amount']) ?> đ
                    </strong>
                </td>
            </tr>
            <tr>
                <th>Đã thanh toán</th>
                <td><strong style="color: green;"><?= number_format($invoice['paid_amount']) ?> đ</strong></td>
            </tr>
            <tr>
                <th>Còn lại</th>
                <td><strong style="color: red;"><?= number_format($amount) ?> đ</strong></td>
            </tr>
        </table>

    </div>

    <!-- FORM -->
    <div class="section-card">

        <h3>Nhập thông tin thanh toán</h3>

        <form method="POST" id="payment">

            <div class="form-grid">

                <div class="form-group money-input">
                    <label>Số tiền thanh toán</label>
                    <input type="number"
                           name="amount"
                           value="<?= $amount ?>"
                           required>
                           <span class="suffix">đ</span>
                </div>

                <div class="form-group">
                    <label>Phương thức</label>
                    <select name="payment_method" required>
                        <option value="">-- Chọn --</option>
                        <option value="cash">Tiền mặt</option>
                        <option value="card">Thẻ tín dụng</option>
                        <option value="transfer">Chuyển khoản</option>
                        <option value="other">Khác</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="notes"></textarea>
                </div>

            </div>

            <button class="btn btn-success" name="confirm_payment">
                💾 Xác nhận thanh toán
            </button>

<a href="<?= $confirm_payment ? 'invoices.php' : 'invoice_detail.php?id=' . $invoice_id . '&rollback=1' ?>"
   class="btn btn-secondary">
   <?= $confirm_payment ? 'Quay lại danh sách hóa đơn' : 'Quay lại' ?>
</a>

        </form>
        <?php if (isset($showQR) && $showQR): ?>
        <div id="bank-qr-box" >

       <h4>📲 Quét QR để chuyển khoản</h4>

       <div class="qr-wrap">

        <img id="bank-qr-img" src="<?= $qr ?>" width="260">

        <p><b>Ngân hàng:</b> <?=  $bankCode ?></p>
        <p><b>Số tài khoản:</b> <?=  $account ?></p>
        <p><b>Chủ tài khoản:</b> <?=  $name ?></p>
        <p><b>Nội dung:</b> Thanh toán hóa đơn</p>

      </div>

     </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>