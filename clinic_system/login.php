<?php
require_once 'config/database.php';
require_once 'config/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && md5($password) === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['full_name'] = $user['full_name'];
            
            auditLog('login', 'users', $user['id']);
            if($user['role_id'] == 6){
                header('Location: ../customer_portal/index.php');
            }
            else{header('Location: index.php');
            }
            exit();
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
        }
    } else {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản lý Phòng khám</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            object-fit: cover;
            justify-content: center;
            align-items: center;
            background-image: url(assets/img/bg-login.jpg);
            background-size: cover;
            background-position: center;
        }
        .phu{
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(79, 79, 79, 0.5);
    z-index: -1;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .login-info {
            margin-top: 30px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
            font-size: 13px;
        }
        
        .login-info h3 {
            margin-bottom: 10px;
            color: #333;
            font-size: 14px;
        }
        
        .login-info ul {
            list-style: none;
            padding-left: 0;
        }
        
        .login-info li {
            margin-bottom: 5px;
            color: #666;
        }
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
    </style>
</head>
<body>
    <div class="phu"></div>
    <div class="login-container">
        <div class="login-header">
            <h1>🏥 Hệ thống Quản lý Phòng khám</h1>
            <p>Vui lòng đăng nhập để tiếp tục</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Đăng nhập</button>
           Bạn chưa có tài khoản? <a href="register.php" style="color:#667eea; text-decoration:none;">Đăng ký</a>
        </form>
        
        <div class="login-info">
            <h3>📋 Tài khoản demo:</h3>
            <ul>
                <li><strong>Admin:</strong> admin / 123456</li>
                <li><strong>Bác sĩ:</strong> doctor1 / 123456</li>
                <li><strong>Lễ tân:</strong> receptionist1 / 123456</li>
                <li><strong>Dược sĩ:</strong> pharmacist1 / 123456</li>
                <li><strong>Thu ngân:</strong> cashier1 / 123456</li>
            </ul>
        </div>
    </div>
</body>
</html>
