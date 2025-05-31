<?php
session_start();
require '../config/db.php';
require '../config/mailer.php';
// Nếu chưa đăng nhập thì chuyển hướng
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Bạn chưa đăng nhập!'); window.location.href = '/auth/login.php';</script>";
    exit;
}
function sendEmailAccountCreated($to, $fullname, $username, $tempPassword) {
    $mail = getMailer(); 
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true); 

    try {
        $mail->addAddress($to, $fullname);
        $mail->Subject = 'Tài khoản đã được tạo';
        $mail->Body = "
            <p>Chào <strong>$fullname</strong>,</p>
            <p>Tài khoản của bạn đã được tạo bởi quản trị viên.</p>
            <p><strong>Tên đăng nhập:</strong> $username</p>
            <p><strong>Mật khẩu tạm thời:</strong> $tempPassword</p>
            <p>⚠️ Vui lòng <strong>đăng nhập và thay đổi mật khẩu ngay</strong> để bảo mật tài khoản.</p>
            <hr>
            <p>Trân trọng,<br>Quản trị hệ thống</p>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$action = $_GET['action'] ?? '';

if ($action === 'add') {
    $errors = [];
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fullname = trim($_POST['fullname']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'] ?? 'user';
        $status = 'active';
        $created_at = date('Y-m-d H:i:s');

        // Validate
        if (empty($fullname) || empty($username) || empty($email) || empty($phone)) {
            $errors[] = "Vui lòng điền đầy đủ thông tin.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email không hợp lệ.";
        } else {
            // Kiểm tra trùng username/email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Username hoặc Email đã tồn tại.";
            }
        }

        // Tạo mật khẩu ngẫu nhiên nếu không lỗi
        if (empty($errors)) {
            $tempPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password, email, phone, role, status, is_first_login, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $fullname, $username, $hashedPassword, $email, $phone, $role, $status, 1, $created_at
            ]);

            sendEmailAccountCreated($email, $fullname, $username, $tempPassword);
            $success = "Tạo tài khoản thành công!";
        }
    }
}

include '../includes/header_admin.php';
?>

<div class="container mt-4" style="max-width: 600px;">
    <div class="card shadow-sm rounded-4">
        <div class="card-body">
            <h4 class="mb-4 text-center">Thêm tài khoản mới</h4>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">👤 Họ tên</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">🆔 Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">📧 Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">📞 Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">🔐 Vai trò</label>
                    <select name="role" class="form-select">
                        <option value="user">Cộng tác viên</option>
                        <option value="admin">Quản trị viên</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-secondary px-4">Thêm</button>
                    <a href="../admin/approve_user.php?duyet=true" class="btn btn-primary px-4">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require '../includes/footer.php';
