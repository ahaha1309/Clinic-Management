<?php
require_once 'config/database.php';
require_once 'config/auth.php';

$db = getDB();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    /* =====================
        VALIDATE
    ===================== */

    if (
        $full_name === '' ||
        $username === '' ||
        $email === '' ||
        $password === '' ||
        $confirm === ''
    ) {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    }

    elseif ($password !== $confirm) {
        $error = "Mật khẩu xác nhận không khớp.";
    }

    elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải ít nhất 6 ký tự.";
    }

    /* =====================
        CHECK TRÙNG
    ===================== */

    if ($error === '') {

        $stmt = $db->prepare("
            SELECT id FROM users
            WHERE username = ? OR email = ?
        ");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = "Username hoặc Email đã tồn tại.";
        }
    }

    /* =====================
        INSERT
    ===================== */

    if ($error === '') {

        $hash = md5($password);

        $stmt = $db->prepare("
            INSERT INTO users
            (username, password, role_id, full_name, email, phone, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");

        $stmt->execute([
            $username,
            $hash,
            6,
            $full_name,
            $email,
            $phone
        ]);

        $success = "Đăng ký thành công! Bạn có thể đăng nhập.";
        $_SESSION['success'] = $success;
        header('Location: login.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đăng ký - Hệ thống phòng khám</title>

<link rel="stylesheet" href="assets/css/auth.css">

</head>
<body>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    background: linear-gradient(135deg,#667eea,#764ba2);
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: 'Segoe UI', sans-serif;
}

/* =====================
 AUTH CONTAINER
===================== */

.auth-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
    padding: 20px;
}

.auth-box {
    background: white;
    width: 100%;
    max-width: 420px;
    padding: 35px;
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0,0,0,.25);
    text-align: center;
}

.auth-box h2 {
    font-size: 24px;
    margin-bottom: 5px;
}

.subtitle {
    font-size: 14px;
    color: #777;
    margin-bottom: 25px;
}

/* =====================
 FORM
===================== */

.form-group {
    text-align: left;
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
}

.form-group input {
    width: 100%;
    padding: 11px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 14px;
}

.form-group input:focus {
    border-color: #667eea;
    outline: none;
}

/* =====================
 BUTTON
===================== */

.btn-primary {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg,#667eea,#764ba2);
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: .3s;
    font-size: 15px;
}

.btn-primary:hover {
    opacity: .9;
    transform: translateY(-1px);
}

/* =====================
 ALERT
===================== */

.alert {
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 14px;
}

.alert.error {
    background: #ffe5e5;
    color: #c0392b;
}

.alert.success {
    background: #d4edda;
    color: #155724;
}

/* =====================
 FOOTER
===================== */

.auth-footer {
    margin-top: 18px;
    font-size: 14px;
}

.auth-footer a {
    color: #667eea;
    font-weight: 600;
    text-decoration: none;
}
    </style>
<div class="auth-wrapper">

    <div class="auth-box">

        <h2>📝 Đăng ký tài khoản</h2>
        <p class="subtitle">Tạo tài khoản để sử dụng hệ thống</p>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Họ tên</label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" name="phone">
            </div>

            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu</label>
                <input type="password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn-primary">
                🚀 Đăng ký
            </button>

        </form>

        <div class="auth-footer">
            Đã có tài khoản?
            <a href="login.php">Đăng nhập</a>
        </div>

    </div>

</div>

</body>
</html>
