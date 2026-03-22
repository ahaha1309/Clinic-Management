<?php
require_once 'config/database.php';
require_once 'config/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

$success = '';
$error   = '';

/* ==========================
   HANDLE POST UPDATE
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    if ($full_name === '' || $email === '' || $phone === '') {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } else {

        // avatar cũ
        $stmt = $db->prepare("SELECT avatar FROM users WHERE id=?");
        $stmt->execute([$user_id]);
        $oldAvatar = $stmt->fetchColumn();

        $newAvatar = $oldAvatar;

        // upload mới
        if (!empty($_FILES['avatar']['name'])) {

            $file = $_FILES['avatar'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allow = ['jpg','jpeg','png','webp'];

            if (!in_array($ext,$allow)) {
                $error = "Ảnh không hợp lệ.";
            } elseif ($file['size'] > 3*1024*1024) {
                $error = "Ảnh tối đa 3MB.";
            } elseif (!getimagesize($file['tmp_name'])) {
                $error = "File không phải ảnh.";
            } else {

                $dir = "uploads/avatars/";
                if (!is_dir($dir)) mkdir($dir,0755,true);

                $name = "user_{$user_id}_" . time() . ".$ext";
                $dest = $dir . $name;

                if (move_uploaded_file($file['tmp_name'],$dest)) {

                    if ($oldAvatar && file_exists($oldAvatar)) {
                        unlink($oldAvatar);
                    }

                    $newAvatar = $dest;

                } else {
                    $error = "Không upload được ảnh.";
                }
            }
        }

        // update nếu ko có lỗi
        if ($error === '') {

            $stmt = $db->prepare("
                UPDATE users 
                SET full_name=?, email=?, phone=?, avatar=?
                WHERE id=?
            ");

            $stmt->execute([
                $full_name,
                $email,
                $phone,
                $newAvatar,
                $user_id
            ]);

            $success = "Cập nhật thông tin thành công!";
        }
    }
}

/* ==========================
   HANDLE CHANGE PASSWORD
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $old_password     = trim($_POST['old_password'] ?? '');
    $new_password     = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($old_password === '' || $new_password === '' || $confirm_password === '') {
        $error = "Vui lòng nhập đầy đủ mật khẩu.";

    } elseif ($new_password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp.";

    } elseif (strlen($new_password) < 6) {
        $error = "Mật khẩu phải ít nhất 6 ký tự.";

    } else {

        $stmt = $db->prepare("SELECT password FROM users WHERE id=?");
        $stmt->execute([$user_id]);
        $currentHash = $stmt->fetchColumn();

        if (!$currentHash || !md5($old_password) === $currentHash) {

            $error = "Mật khẩu cũ không đúng.";

        } else {

            $newHash = md5($new_password);

            $stmt = $db->prepare("
                UPDATE users 
                SET password=?
                WHERE id=?
            ");

            $stmt->execute([$newHash, $user_id]);

            $success = "Đổi mật khẩu thành công!";
        }
    }
}


/* ==========================
   LOAD USER DATA
========================== */

$stmt = $db->prepare("
    SELECT id, full_name, username, email, role_id, avatar, phone
    FROM users
    WHERE id=?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">      
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang cá nhân - Hệ thống quản lý phòng khám</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php include 'includes/sidebar.php'; ?>
<?php include 'includes/header.php'; ?>

<div class="main-content">
     <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

    <h1>Hồ sơ cá nhân</h1>
    <p class="subtitle">Quản lý thông tin tài khoản của bạn</p>

    <div class="profile-wrapper">

        <!-- LEFT CARD -->
        <div class="profile-card">

            <div class="profile-avatar">
                <img src="<?= !empty($user['avatar']) ? $user['avatar'] : 'assets/img/default-avatar.jpg' ?>">
            </div>

            <div class="profile-name">
                <?= htmlspecialchars($user['full_name']) ?>
            </div>

            <span class="profile-role">
                <?= strtoupper($user['role_id']) ?>
            </span>

            <div class="profile-meta">
                <p><strong>Tài khoản:</strong> <?= $user['username'] ?></p>
                <p><strong>Email:</strong> <?= $user['email'] ?></p>
                <p><strong>Số điện thoại:</strong> <?= $user['phone'] ?></p>
            </div>

        </div>

        <!-- RIGHT CONTENT -->
        <div class="profile-content">

            <!-- UPDATE INFO -->
            <div class="profile-section">

                <h3>Cập nhật thông tin</h3>

                <form method="POST"
                      action=""
                      enctype="multipart/form-data">

                    <div class="form-grid">
                        <input type="hidden" name="update_profile" value="1">

                        <div class="form-group">
                            <label>Họ tên</label>
                            <input type="text"
                                   name="full_name"
                                   value="<?= $user['full_name'] ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email"
                                   name="email"
                                   value="<?= $user['email'] ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text"
                                   name="phone"
                                   value="<?= $user['phone'] ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Ảnh đại diện</label>
                            <input type="file" name="avatar">
                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary">
                        💾 Lưu thay đổi
                    </button>

                </form>

            </div>

            <!-- CHANGE PASSWORD -->
            <div class="profile-section">

                <h3>Đổi mật khẩu</h3>

                <form method="POST" action="">
                    <input type="hidden" name="change_password" value="1">
                    <div class="password-grid">

                        <div class="form-group">
                            <label>Mật khẩu cũ</label>
                            <input type="password"
                                   name="old_password"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Mật khẩu mới</label>
                            <input type="password"
                                   name="new_password"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Xác nhận mật khẩu</label>
                            <input type="password"
                                   name="confirm_password"
                                   required>
                        </div>

                    </div>

                    <button type="submit" class="btn btn-warning">
                        🔒 Đổi mật khẩu
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>
</body>
    </html>