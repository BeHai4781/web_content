<?php
session_start();
require_once '../config/db.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../includes/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Hàm điều hướng theo role
function redirectByRole($role) {
    switch ($role) {
        case 'admin':
            header("Location: ../admin/index.php");
            break;
        case 'user':
            header("Location: ../user/index.php");
            break;
        default:
            header("Location: ../index.php");
            break;
    }
    exit;
}

// Lấy thông tin người dùng từ CSDL
$stmt = $pdo->prepare("SELECT role, is_first_login FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: ../includes/login.php");
    exit;
}

// Nếu đã đổi mật khẩu rồi thì chuyển hướng theo vai trò
if ($user['is_first_login'] == 0) {
    redirectByRole($user['role']);
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $errors[] = "Vui lòng nhập đầy đủ mật khẩu.";
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = "Mật khẩu xác nhận không khớp.";
    } elseif (strlen($newPassword) < 6) {
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự.";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, is_first_login = 0 WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);

        // Lấy lại role sau cập nhật
        $role = $user['role'];
        $success = "Đổi mật khẩu thành công! Đang chuyển hướng...";

        // Redirect sau vài giây
        header("refresh:2;url=" . ($role === 'admin' ? '../admin/index.php' : '../user/index.php'));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đổi mật khẩu lần đầu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h3 class="mb-4">🔐 Đổi mật khẩu lần đầu</h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>Mật khẩu mới</label>
            <input type="password" name="new_password" class="form-control" required minlength="6">
        </div>
        <div class="mb-3">
            <label>Nhập lại mật khẩu</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="6">
        </div>
        <button type="submit" class="btn btn-secondary">Cập nhật</button>
    </form>
</body>
</html>
