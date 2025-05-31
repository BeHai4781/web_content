<?php
session_start();
require_once '../config/db.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../includes/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Hàm điều hướng theo vai trò
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

        // Lấy lại vai trò của người dùng để chuyển hướng phù hợp
        $role = $user['role'];
        $success = "Đổi mật khẩu thành công! Đang chuyển hướng...";

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
  <style>
    body {
      background: linear-gradient(135deg, #253342, #0091ae);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
    }
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
      width: 400px;
    }
    .card-header {
      background-color: #0091ae;
      color: #fff;
      font-size: 20px;
      font-weight: 500;
      text-align: center;
      border-top-left-radius: 12px;
      border-top-right-radius: 12px;
      padding: 1rem;
    }
    .btn-custom {
      background-color: #253342;
      color: #fff;
      border: none;
      border-radius: 5px;
      font-weight: bold;
    }
    .btn-custom:hover {
      background-color: #1f2b34;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-header">
      🔐 Đổi mật khẩu lần đầu
    </div>
    <div class="card-body">
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul>
            <?php foreach ($errors as $e): ?>
              <li><?= $e ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success">
          <?= $success ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label for="new_password" class="form-label">Mật khẩu mới</label>
          <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
        </div>
        <div class="mb-3">
          <label for="confirm_password" class="form-label">Nhập lại mật khẩu</label>
          <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-custom">Cập nhật</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
