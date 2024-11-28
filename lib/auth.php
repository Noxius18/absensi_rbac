<?php
require __DIR__ . '/../config.php';

function login($email, $password) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = :email AND status = 'aktif'");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['username'] = $user['username'];

        return true;
    }
    return false;
}

function hasPermission($resource, $action) {
    global $pdo;
    if(!isset($_SESSION['role_id'])) return false;

    $stmt = $pdo->prepare("SELECT COUNT(*) 
    FROM Role_Permissions rp 
    JOIN Permissions p ON rp.permission_id = p.permission_id 
    WHERE rp.role_id = :role_id 
    AND p.resource = :resource 
    AND p.action = :action;");
    $stmt->execute([
        'role_id' => $_SESSION['role_id'],
        'resource' => $resource,
        'action' => $action
    ]);
    return $stmt->fetchColumn() > 0;
}

function logout() {
    session_destroy();
}