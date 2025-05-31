<?php
session_start();
require '../config/db.php';

// Nếu chưa đăng nhập thì chuyển hướng
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Bạn chưa đăng nhập!'); window.location.href = '/auth/login.php';</script>";
    exit;
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, fullname = ?, email = ?, password = ? WHERE id = ?");
        $stmt->execute([$username, $fullname, $email, $hashed, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, fullname = ?, email = ? WHERE id = ?");
        $stmt->execute([$username, $fullname, $email, $user_id]);
    }

    $message = "✅ Cập nhật thành công!";
}

$stmt = $pdo->prepare("SELECT username, fullname, email FROM users WHERE id = ? AND role = 'user'");
$stmt->execute([$user_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "Không tìm thấy thông tin cộng tác viên.";
    exit;
}

require '../user/header.php';
?>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow p-4" style="width: 100%; max-width: 500px;">
        <h1 class="mb-4 text-center">Thông tin tài khoản cộng tác viên</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Tên đăng nhập:</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" require>
            </div>
            <div class="mb-3">
                <label class="form-label">Họ tên:</label>
                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($admin['fullname']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email:</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Mật khẩu mới (để trống nếu không đổi):</label>
                <input type="password" name="password" class="form-control">
            </div>
            <button type="submit" class="btn btn-secondary">Cập nhật</button>
            <a href="../user/index.php" class="btn btn-primary">Quay lại</a>
            
        </form>
    </div>
</div>
<?php require '../includes/footer.php';