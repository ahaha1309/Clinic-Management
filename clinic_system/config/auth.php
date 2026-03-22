<?php
// =====================================================
// SESSION & AUTHENTICATION
// =====================================================

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /clinic_system/login.php');
        exit();
    }
}

function hasPermission($permission_code) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.id
        JOIN users u ON u.role_id = rp.role_id
        WHERE u.id = ? AND p.permission_code = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $permission_code]);
    $result = $stmt->fetch();
    
    return $result['count'] > 0;
}

function requirePermission($permission_code) {
    requireLogin();
    if (!hasPermission($permission_code)) {
        http_response_code(403);
        die('Access Denied: Bạn không có quyền truy cập chức năng này!');
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function auditLog($action, $table_name, $record_id, $old_data = null, $new_data = null) {
    if (!isLoggedIn()) return;
    
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, old_data, new_data, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $table_name,
        $record_id,
        json_encode($old_data),
        json_encode($new_data),
        $_SERVER['REMOTE_ADDR']
    ]);
}

function generateCode($prefix, $table, $column) {
    $db = getDB();
    $stmt = $db->prepare("SELECT $column FROM $table WHERE $column LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $result = $stmt->fetch();
    
    if ($result) {
        $lastCode = $result[$column];
        $number = intval(substr($lastCode, strlen($prefix))) + 1;
    } else {
        $number = 1;
    }
    
    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}
function checkPermission($permission) {
    if (!hasPermission($permission)) {
        die('Bạn không có quyền truy cập chức năng này');
    }
}

?>
