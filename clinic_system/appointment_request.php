<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/mail_template.php';
require_once 'config/mail_config.php';

$db = getDB();
$message = "";

/* ==========================================
   1️⃣ XỬ LÝ DUYỆT / TỪ CHỐI
========================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($_POST['action']) && !empty($_POST['request_id'])) {

        $request_id = intval($_POST['request_id']);

if ($_POST['action'] === 'approve') {

    try {

        $db->beginTransaction();

        // 1️⃣ Lấy request
        $stmt = $db->prepare("SELECT * FROM appointment_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request || $request['status'] !== 'pending') {
            throw new Exception("Yêu cầu không hợp lệ hoặc đã xử lý.");
        }

        // 2️⃣ Kiểm tra bệnh nhân đã tồn tại chưa (dựa vào phone)
        $stmt = $db->prepare("SELECT id FROM patients WHERE phone = ?");
        $stmt->execute([$request['phone']]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($patient) {
            // Đã tồn tại
            $patient_id = $patient['id'];
        } else {
            // 3️⃣ Chưa tồn tại → Tạo bệnh nhân mới
            $patient_code = generateCode('BN', 'patients', 'patient_code');
            $stmt = $db->prepare("
                INSERT INTO patients 
                (patient_code, full_name, date_of_birth, gender, phone)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $patient_code,
                $request['full_name'],
                $request['date_of_birth'],
                $request['gender'],
                $request['phone']
            ]);

            $patient_id = $db->lastInsertId();
        }

        // 4️⃣ Tạo mã lịch hẹn
        $appointment_code = generateCode('LH', 'appointments', 'appointment_code');

        // 5️⃣ Insert vào appointments
        $stmt = $db->prepare("
            INSERT INTO appointments (
                appointment_code,
                patient_id,
                doctor_id,
                appointment_date,
                appointment_time,
                status,
                reason,
                notes,
                created_by,
                created_at
            ) VALUES (?, ?, ?, ?, ?, 'scheduled', ?, '', ?, NOW())
        ");

        $stmt->execute([
            $appointment_code,
            $patient_id,
            $request['doctor_id'],
            $request['appointment_date'],
            $request['appointment_time'],
            $request['reason'],
            $_SESSION['user_id'] ?? 1
        ]);

        // 6️⃣ Update request
        $stmt = $db->prepare("
            UPDATE appointment_requests
            SET status = 'approved'
            WHERE id = ?
        ");
        $stmt->execute([$request_id]);
         
        // Lấy thông tin bệnh nhân
$stmt = $db->prepare("SELECT * FROM appointment_requests WHERE id = ?");
$stmt->execute([$request_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$email = $row['email'];
$fullName = $row['full_name'];
$date = $row['appointment_date'];
$time = $row['appointment_time'];

$mail = getMailer();
$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';
$mail->AddEmbeddedImage('assets/img/logo.jpg', 'logo_cid');
$mail->addAddress($email);
$mail->Subject = "Lịch hẹn đã được phê duyệt";
$content = "
<p>Xin chào <b>$fullName</b>,</p>

<p>Lịch hẹn của bạn đã được <span style='color:green;font-weight:bold;'>PHÊ DUYỆT</span>.</p>

<p><b>Ngày:</b> $date</p>
<p><b>Giờ:</b> $time</p>

<p>Vui lòng đến đúng giờ.</p>
";

$mail->Body = appointmentTemplate(
    "Lịch hẹn đã được phê duyệt",
    $content
);

$mail->send();

        $db->commit();

        $message = "Đã duyệt lịch thành công!";

    } catch (Exception $e) {

        $db->rollBack();
        $message = "Lỗi: " . $e->getMessage();
    }
}
        elseif ($_POST['action'] === 'reject') {

            $stmt = $db->prepare("
                UPDATE appointment_requests
                SET status = 'rejected'
                WHERE id = ?
            ");
            $stmt->execute([$request_id]);
$stmt = $db->prepare("SELECT * FROM appointment_requests WHERE id = ?");
$stmt->execute([$request_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$email = $row['email'];
$fullName = $row['full_name'];
$date = $row['appointment_date'];
$time = $row['appointment_time'];

$mail = getMailer();
$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';
$mail->AddEmbeddedImage('assets/img/logo.jpg', 'logo_cid');
$mail->addAddress($email);
$mail->Subject = "Lịch hẹn đã bị từ chối";
$content = "
<p>Xin chào <b>$fullName</b>,</p>

<p>Chúng tôi rất tiếc phải thông báo rằng lịch hẹn của bạn vào:</p>

<p>
<b>Ngày:</b> $date <br>
<b>Giờ:</b> $time
</p>

<p style='color:#e74c3c;font-weight:bold;'>
Đã bị TỪ CHỐI.
</p>

<p>
Nguyên nhân có thể do lịch đã kín hoặc có thay đổi đột xuất.
</p>

<p>
Vui lòng đặt lại lịch vào thời gian khác phù hợp hơn.
</p>

<p>
Nếu cần hỗ trợ, vui lòng liên hệ hotline: <b>0909 999 999</b>
</p>
";
$mail->Body = appointmentTemplate(
    "Thông báo lịch hẹn",
    $content     
);

$mail->send();

            $message = "Đã từ chối yêu cầu!";
        }
    }
}

/* ==========================================
   2️⃣ LẤY DANH SÁCH REQUEST
========================================== */

$stmt = $db->query("
    SELECT * FROM appointment_requests
    ORDER BY created_at DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý yêu cầu đặt lịch</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<style>
    .form-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th, td {
        padding: 12px;
        border: 1px solid #ddd;
        text-align: left;
    }
    th {
        background-color: #f4f4f4;
    }       
    button {
        padding: 6px 12px;
        margin-right: 5px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    button[name="action"][value="approve"] {
        background-color: #4CAF50;
        color: white;
    }
    button[name="action"][value="reject"] {
        background-color: #f44336;
        color: white;
    }
</style>
<body>
      <div class="wrapper">
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>        
                <div class="main-content">
            <div class="form-container">

<h2>Danh sách yêu cầu đặt lịch</h2>

<?php if ($message): ?>
    <p style="color:green;"><?php echo $message; ?></p>
<?php endif; ?>

<table border="1" cellpadding="10">
    <tr>
        <th>Họ tên</th>
        <th>SĐT</th>
        <th>Ngày khám</th>
        <th>Giờ</th>
        <th>Trạng thái</th>
        <th>Hành động</th>
    </tr>

    <?php foreach ($requests as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><?php echo $row['phone']; ?></td>
            <td><?php echo $row['appointment_date']; ?></td>
            <td><?php echo $row['appointment_time']; ?></td>
            <td><?php echo $row['status']; ?></td>
            <td>
                <?php if ($row['status'] === 'pending'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                        <button name="action" value="approve">Duyệt</button>
                        <button name="action" value="reject">Từ chối</button>
                    </form>
                <?php else: ?>
                    Đã xử lý
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>

</table>
</div>
            </div>
      </div>
</body>
</html>
